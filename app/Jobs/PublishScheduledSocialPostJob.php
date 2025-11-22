<?php

namespace App\Jobs;

use App\Models\Social\ScheduledSocialPost;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishScheduledSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The scheduled social post instance.
     *
     * @var \App\Models\ScheduledSocialPost
     */
    protected $post;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledSocialPost $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Set database context for RLS
            DB::statement('SELECT cmis.init_transaction_context(?, ?)',
                [$this->post->created_by ?? config('cmis.system_user_id'), $this->post->org_id]);

            // Validate post is ready
            if ($this->post->status !== 'scheduled') {
                Log::warning('Post not in scheduled status', [
                    'post_id' => $this->post->post_id,
                    'status' => $this->post->status
                ]);
                return;
            }

            // Get integrations
            $integrationIds = $this->post->integration_ids ?? [];

            if (empty($integrationIds)) {
                throw new \Exception('No integrations found for post');
            }

            // Get integration models (use Eloquent for proper model hydration)
            $integrations = \App\Models\Core\Integration::whereIn('integration_id', $integrationIds)
                ->where('org_id', $this->post->org_id)
                ->where('is_active', true)
                ->get();

            if ($integrations->isEmpty()) {
                throw new \Exception('No active integrations found');
            }

            $results = [];
            foreach ($integrations as $integration) {
                try {
                    // Get connector for this integration's platform
                    $connector = ConnectorFactory::make($integration->platform);

                    // Create a ContentItem-like object from post data
                    $contentItem = new \stdClass();
                    $contentItem->content = $this->post->content;
                    $contentItem->media_urls = $this->post->media_urls ?? [];
                    $contentItem->scheduled_at = $this->post->scheduled_at;

                    // Publish post with proper parameters
                    $platformPostId = $connector->publishPost($integration, $contentItem);

                    $results[$integration->integration_id] = [
                        'success' => true,
                        'platform_post_id' => $platformPostId,
                        'published_at' => now()->toISOString(),
                        'platform' => $integration->platform,
                    ];

                    Log::info('Post published successfully', [
                        'post_id' => $this->post->post_id,
                        'integration_id' => $integration->integration_id,
                        'platform' => $integration->platform,
                        'platform_post_id' => $result['id'] ?? null,
                    ]);

                } catch (\Exception $e) {
                    $results[$integration->integration_id] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'platform' => $integration->platform,
                    ];

                    Log::error('Failed to publish to platform', [
                        'post_id' => $this->post->post_id,
                        'integration_id' => $integration->integration_id,
                        'platform' => $integration->platform,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Update post status
            $allSuccess = collect($results)->every(fn($r) => $r['success']);
            $anySuccess = collect($results)->some(fn($r) => $r['success']);

            $status = $allSuccess ? 'published' : ($anySuccess ? 'partially_published' : 'failed');

            $this->post->update([
                'status' => $status,
                'published_at' => $allSuccess || $anySuccess ? now() : null,
                'publish_results' => $results,
            ]);

            if (!$allSuccess) {
                Log::warning('Post published with errors', [
                    'post_id' => $this->post->post_id,
                    'status' => $status,
                    'results' => $results,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Job failed', [
                'post_id' => $this->post->post_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->post->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job failed permanently', [
            'post_id' => $this->post->post_id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->post->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
