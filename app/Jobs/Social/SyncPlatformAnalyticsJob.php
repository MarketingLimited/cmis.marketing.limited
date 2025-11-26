<?php

namespace App\Jobs\Social;

use App\Models\Social\PlatformPost;
use App\Services\Social\PublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync analytics/metrics from social media platforms
 *
 * This job runs periodically to fetch latest engagement metrics
 * (likes, comments, shares, views) for published posts
 */
class SyncPlatformAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900; // 15 minutes

    /**
     * Organization ID to sync analytics for
     */
    protected ?string $orgId;

    /**
     * Specific platform to sync (optional)
     */
    protected ?string $platform;

    /**
     * Create a new job instance.
     *
     * @param string|null $orgId Specific organization to sync, or null for all
     * @param string|null $platform Specific platform to sync, or null for all
     */
    public function __construct(?string $orgId = null, ?string $platform = null)
    {
        $this->orgId = $orgId;
        $this->platform = $platform;
    }

    /**
     * Execute the job.
     */
    public function handle(PublishingService $publishingService): void
    {
        try {
            Log::info('Syncing platform analytics', [
                'org_id' => $this->orgId ?? 'all',
                'platform' => $this->platform ?? 'all',
            ]);

            if ($this->orgId) {
                // Sync for specific organization
                $this->syncForOrganization($publishingService, $this->orgId);
            } else {
                // Sync for all organizations with published posts
                $orgIds = PlatformPost::where('status', 'published')
                    ->select('org_id')
                    ->distinct()
                    ->pluck('org_id');

                foreach ($orgIds as $orgId) {
                    $this->syncForOrganization($publishingService, $orgId);
                }
            }

        } catch (\Exception $e) {
            Log::error('SyncPlatformAnalyticsJob failed', [
                'org_id' => $this->orgId,
                'platform' => $this->platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync analytics for a specific organization.
     */
    protected function syncForOrganization(PublishingService $publishingService, string $orgId): void
    {
        // Set RLS context
        DB::statement('SELECT cmis.init_transaction_context(?, ?)',
            [config('cmis.system_user_id'), $orgId]);

        // Use PublishingService's bulk sync method
        $results = $publishingService->bulkSyncMetrics($orgId);

        Log::info('Organization analytics sync completed', [
            'org_id' => $orgId,
            'synced' => $results['synced'],
            'failed' => $results['failed'],
        ]);
    }

    /**
     * Get posts that need analytics sync.
     */
    protected function getPostsNeedingSync(): \Illuminate\Database\Eloquent\Collection
    {
        $query = PlatformPost::where('status', 'published')
            ->where(function ($q) {
                // Never synced OR last synced over 1 hour ago
                $q->whereNull('last_synced_at')
                  ->orWhere('last_synced_at', '<', now()->subHours(1));
            });

        // Filter by org if specified
        if ($this->orgId) {
            $query->where('org_id', $this->orgId);
        }

        // Filter by platform if specified
        if ($this->platform) {
            $query->where('platform', $this->platform);
        }

        // Prioritize older posts that haven't been synced recently
        return $query->orderBy('last_synced_at', 'asc')
            ->orderBy('published_at', 'desc')
            ->limit(100) // Process max 100 posts per job run
            ->get();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncPlatformAnalyticsJob failed permanently', [
            'org_id' => $this->orgId ?? 'all',
            'platform' => $this->platform ?? 'all',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
