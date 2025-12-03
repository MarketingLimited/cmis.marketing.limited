<?php

namespace App\Jobs;

use App\Models\Platform\PlatformConnection;
use App\Services\Platform\MetaAssetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled job to pre-fetch Meta assets and warm up the cache.
 *
 * This job runs hourly to ensure Meta Business Manager assets are
 * always cached and ready when users visit the Meta Assets page.
 * This eliminates wait times for users by pre-loading:
 * - Facebook Pages
 * - Instagram Accounts
 * - Threads Accounts (fast-fail if OAuth not available)
 * - Ad Accounts
 * - Pixels
 * - Product Catalogs
 * - WhatsApp Business Accounts
 * - Custom Conversions
 * - Creative Folders
 * - Verified Domains
 * - Offline Event Sets
 * - Apps
 */
class FetchMetaAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Optional: Specific connection ID to fetch (null = all Meta connections).
     */
    public ?string $connectionId;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $connectionId = null)
    {
        $this->connectionId = $connectionId;
    }

    /**
     * Execute the job.
     */
    public function handle(MetaAssetsService $service): void
    {
        $startTime = microtime(true);

        Log::info('FetchMetaAssetsJob: Starting Meta assets cache warm-up', [
            'connection_id' => $this->connectionId ?? 'all',
        ]);

        // Get Meta connections to process
        $query = PlatformConnection::active()
            ->forPlatform('meta')
            ->whereNotNull('access_token');

        if ($this->connectionId) {
            $query->where('connection_id', $this->connectionId);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            Log::info('FetchMetaAssetsJob: No active Meta connections found');
            return;
        }

        $totalConnections = $connections->count();
        $successCount = 0;
        $errorCount = 0;

        foreach ($connections as $connection) {
            try {
                $this->fetchAssetsForConnection($service, $connection);
                $successCount++;

                // Update last sync timestamp
                $connection->markSynced();

            } catch (\Exception $e) {
                $errorCount++;
                Log::error('FetchMetaAssetsJob: Failed to fetch assets for connection', [
                    'connection_id' => $connection->connection_id,
                    'org_id' => $connection->org_id,
                    'error' => $e->getMessage(),
                ]);

                // Don't fail the whole job for one connection error
                continue;
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        Log::info('FetchMetaAssetsJob: Completed', [
            'total_connections' => $totalConnections,
            'success' => $successCount,
            'errors' => $errorCount,
            'duration_seconds' => $duration,
        ]);
    }

    /**
     * Fetch all asset types for a single connection.
     */
    protected function fetchAssetsForConnection(MetaAssetsService $service, PlatformConnection $connection): void
    {
        $connectionId = $connection->connection_id;
        $accessToken = $connection->access_token;

        if (!$accessToken) {
            Log::warning('FetchMetaAssetsJob: No access token for connection', [
                'connection_id' => $connectionId,
            ]);
            return;
        }

        $assetTypes = [
            'pages' => 'getPages',
            'instagram' => 'getInstagramAccounts',
            'threads' => 'getThreadsAccounts',
            'ad_accounts' => 'getAdAccounts',
            'pixels' => 'getPixels',
            'catalogs' => 'getCatalogs',
            'whatsapp' => 'getWhatsappAccounts',
            'custom_conversions' => 'getCustomConversions',
            'offline_event_sets' => 'getOfflineEventSets',
        ];

        $results = [];

        foreach ($assetTypes as $type => $method) {
            try {
                // Force refresh to ensure fresh data
                $assets = $service->$method($connectionId, $accessToken, true);
                $results[$type] = count($assets);
            } catch (\Exception $e) {
                $results[$type] = 'error: ' . $e->getMessage();
                Log::debug("FetchMetaAssetsJob: Failed to fetch {$type}", [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('FetchMetaAssetsJob: Assets fetched for connection', [
            'connection_id' => $connectionId,
            'org_id' => $connection->org_id,
            'results' => $results,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchMetaAssetsJob: Job failed', [
            'connection_id' => $this->connectionId ?? 'all',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
