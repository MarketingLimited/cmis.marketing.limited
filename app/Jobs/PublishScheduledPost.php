<?php

namespace App\Jobs;

use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected string $scheduleId;
    protected string $integrationId;
    protected ContentItem $contentItem;

    public function __construct(string $scheduleId, string $integrationId, ContentItem $contentItem)
    {
        $this->scheduleId = $scheduleId;
        $this->integrationId = $integrationId;
        $this->contentItem = $contentItem;
    }

    public function handle(): void
    {
        try {
            Log::info("Publishing scheduled post {$this->scheduleId} to integration {$this->integrationId}");

            // Check if still pending
            $scheduled = DB::table('cmis_creative.scheduled_posts')
                ->where('schedule_id', $this->scheduleId)
                ->where('status', 'pending')
                ->first();

            if (!$scheduled) {
                Log::info("Scheduled post {$this->scheduleId} is not pending, skipping");
                return;
            }

            // Get integration
            $integration = Integration::where('integration_id', $this->integrationId)
                ->where('is_active', true)
                ->firstOrFail();

            // Publish to platform
            $connector = ConnectorFactory::make($integration->platform);
            $platformPostId = $connector->publishPost($integration, $this->contentItem);

            // Store in social_posts
            DB::table('cmis_social.social_posts')->insert([
                'post_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $integration->org_id,
                'integration_id' => $this->integrationId,
                'platform' => $integration->platform,
                'platform_post_id' => $platformPostId,
                'content' => $this->contentItem->content,
                'published_at' => now(),
                'status' => 'published',
                'created_at' => now(),
            ]);

            // Update scheduled_posts status
            DB::table('cmis_creative.scheduled_posts')
                ->where('schedule_id', $this->scheduleId)
                ->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'platform_post_id' => $platformPostId,
                ]);

            Log::info("Successfully published scheduled post {$this->scheduleId}");
        } catch (\Exception $e) {
            Log::error("Failed to publish scheduled post {$this->scheduleId}: {$e->getMessage()}");

            // Update status to failed
            DB::table('cmis_creative.scheduled_posts')
                ->where('schedule_id', $this->scheduleId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Publish job failed for schedule {$this->scheduleId}: {$exception->getMessage()}");

        DB::table('cmis_creative.scheduled_posts')
            ->where('schedule_id', $this->scheduleId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
