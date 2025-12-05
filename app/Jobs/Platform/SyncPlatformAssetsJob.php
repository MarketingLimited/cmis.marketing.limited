<?php

namespace App\Jobs\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use App\Services\Platform\MetaAssetsService;
use App\Services\Platform\GoogleAssetsService;
use App\Services\Platform\TikTokAssetsService;
use App\Services\Platform\LinkedInAssetsService;
use App\Services\Platform\TwitterAssetsService;
use App\Services\Platform\SnapchatAssetsService;
use App\Services\Platform\PinterestAssetsService;
use App\Services\RateLimiter\PlatformRateLimiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled job to sync platform assets from all active connections.
 *
 * Runs every 6 hours to refresh platform assets in the database.
 * Uses rate limiting to prevent API quota exhaustion.
 *
 * Asset Types Synced per Platform:
 * - Meta: pages, instagram, ad_account, pixel, catalog, business, whatsapp
 * - Google: youtube_channel, ads_account, analytics_property, merchant_center, etc.
 * - TikTok: tiktok_account, advertiser, pixel, catalog
 * - LinkedIn: organization, ad_account
 * - Twitter: account, ad_account
 * - Snapchat: organization, ad_account
 * - Pinterest: account, board, ad_account
 */
class SyncPlatformAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to attempt the job.
     */
    public int $tries = 3;

    /**
     * Backoff times in seconds: 5min, 10min, 30min.
     */
    public array $backoff = [300, 600, 1800];

    /**
     * Job timeout in seconds (30 minutes).
     */
    public int $timeout = 1800;

    /**
     * Optional: specific connection to sync (null = all connections).
     */
    protected ?string $connectionId;

    /**
     * Optional: specific platform to sync (null = all platforms).
     */
    protected ?string $platform;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $connectionId = null, ?string $platform = null)
    {
        $this->connectionId = $connectionId;
        $this->platform = $platform;
        $this->onQueue('asset-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(
        PlatformAssetRepositoryInterface $repository,
        PlatformRateLimiter $rateLimiter
    ): void {
        Log::info('SyncPlatformAssetsJob started', [
            'connection_id' => $this->connectionId,
            'platform' => $this->platform,
        ]);

        $connections = $this->getConnectionsToSync();
        $stats = [
            'total' => $connections->count(),
            'synced' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($connections as $connection) {
            try {
                // Check rate limit before syncing
                if (!$rateLimiter->attempt($connection->platform, $connection->org_id)) {
                    Log::warning('Rate limited, skipping connection sync', [
                        'connection_id' => $connection->connection_id,
                        'platform' => $connection->platform,
                    ]);
                    $stats['skipped']++;
                    continue;
                }

                $this->syncConnection($connection, $repository);
                $stats['synced']++;

                // Small delay between connections to avoid rate limit spikes
                usleep(rand(100000, 500000)); // 100-500ms

            } catch (\Exception $e) {
                Log::error('Failed to sync connection assets', [
                    'connection_id' => $connection->connection_id,
                    'platform' => $connection->platform,
                    'error' => $e->getMessage(),
                ]);
                $stats['failed']++;

                // Mark connection error
                $connection->markAsError($e->getMessage());
            }
        }

        Log::info('SyncPlatformAssetsJob completed', $stats);
    }

    /**
     * Get connections that need to be synced.
     */
    protected function getConnectionsToSync()
    {
        $query = PlatformConnection::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('auto_sync', true)
                    ->orWhereNull('auto_sync');
            });

        if ($this->connectionId) {
            $query->where('connection_id', $this->connectionId);
        }

        if ($this->platform) {
            $query->where('platform', $this->platform);
        }

        return $query->get();
    }

    /**
     * Sync assets for a specific connection.
     */
    protected function syncConnection(PlatformConnection $connection, PlatformAssetRepositoryInterface $repository): void
    {
        Log::info('Syncing assets for connection', [
            'connection_id' => $connection->connection_id,
            'platform' => $connection->platform,
            'account_name' => $connection->account_name,
        ]);

        // Get valid access token
        $accessToken = $this->getValidAccessToken($connection);
        if (!$accessToken) {
            throw new \Exception('No valid access token available');
        }

        // Get the appropriate service and sync assets
        $service = $this->getAssetService($connection->platform, $repository);
        if (!$service) {
            Log::warning('No asset service available for platform', [
                'platform' => $connection->platform,
            ]);
            return;
        }

        // Set org ID for access tracking
        if (method_exists($service, 'setOrgId')) {
            $service->setOrgId($connection->org_id);
        }

        // Sync assets based on platform
        $this->syncPlatformAssets($service, $connection, $accessToken);

        // Mark connection as synced
        $connection->markSynced();
    }

    /**
     * Get valid access token for connection.
     */
    protected function getValidAccessToken(PlatformConnection $connection): ?string
    {
        // Check if token is expired
        if ($connection->isTokenExpired()) {
            Log::warning('Token expired for connection', [
                'connection_id' => $connection->connection_id,
                'expires_at' => $connection->token_expires_at,
            ]);
            return null;
        }

        return $connection->access_token;
    }

    /**
     * Get the appropriate asset service for a platform.
     */
    protected function getAssetService(string $platform, PlatformAssetRepositoryInterface $repository)
    {
        return match ($platform) {
            'meta', 'facebook', 'instagram' => new MetaAssetsService($repository),
            'google' => new GoogleAssetsService($repository),
            'tiktok' => new TikTokAssetsService($repository),
            'linkedin' => new LinkedInAssetsService($repository),
            'twitter' => new TwitterAssetsService($repository),
            'snapchat' => new SnapchatAssetsService($repository),
            'pinterest' => new PinterestAssetsService($repository),
            default => null,
        };
    }

    /**
     * Sync assets for a specific platform.
     */
    protected function syncPlatformAssets($service, PlatformConnection $connection, string $accessToken): void
    {
        $connectionId = $connection->connection_id;
        $forceRefresh = true; // Always force refresh in scheduled sync

        match ($connection->platform) {
            'meta', 'facebook', 'instagram' => $this->syncMetaAssets($service, $connectionId, $accessToken, $forceRefresh),
            'google' => $this->syncGoogleAssets($service, $connectionId, $accessToken, $forceRefresh),
            'tiktok' => $this->syncTikTokAssets($service, $connection, $accessToken, $forceRefresh),
            'linkedin' => $this->syncLinkedInAssets($service, $connectionId, $accessToken, $forceRefresh),
            'twitter' => $this->syncTwitterAssets($service, $connectionId, $accessToken, $forceRefresh),
            'snapchat' => $this->syncSnapchatAssets($service, $connectionId, $accessToken, $forceRefresh),
            'pinterest' => $this->syncPinterestAssets($service, $connectionId, $accessToken, $forceRefresh),
            default => null,
        };
    }

    /**
     * Sync Meta (Facebook/Instagram) assets.
     */
    protected function syncMetaAssets(MetaAssetsService $service, string $connectionId, string $accessToken, bool $forceRefresh): void
    {
        try {
            $service->getPages($connectionId, $accessToken, $forceRefresh);
            $service->getInstagramAccounts($connectionId, $accessToken, $forceRefresh);
            $service->getAdAccounts($connectionId, $accessToken, $forceRefresh);
            $service->getPixels($connectionId, $accessToken, $forceRefresh);
            $service->getBusinesses($connectionId, $accessToken, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing Meta assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync Google assets.
     */
    protected function syncGoogleAssets(GoogleAssetsService $service, string $connectionId, string $accessToken, bool $forceRefresh): void
    {
        try {
            $service->getYouTubeChannels($connectionId, $accessToken, $forceRefresh);
            $service->getAdsAccounts($connectionId, $accessToken, $forceRefresh);
            $service->getAnalyticsProperties($connectionId, $accessToken, $forceRefresh);
            $service->getMerchantCenterAccounts($connectionId, $accessToken, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing Google assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync TikTok assets.
     */
    protected function syncTikTokAssets(TikTokAssetsService $service, PlatformConnection $connection, string $accessToken, bool $forceRefresh): void
    {
        try {
            // TikTok requires advertiser IDs from metadata
            $advertiserIds = $connection->account_metadata['advertiser_ids'] ?? [];
            $service->getTikTokAccounts($connection->org_id, $forceRefresh);
            $service->getAdvertisers($connection->connection_id, $accessToken, $advertiserIds, $forceRefresh);
            $service->getPixels($connection->connection_id, $accessToken, $advertiserIds, $forceRefresh);
            $service->getCatalogs($connection->connection_id, $accessToken, $advertiserIds, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing TikTok assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync LinkedIn assets.
     */
    protected function syncLinkedInAssets(LinkedInAssetsService $service, string $connectionId, string $accessToken, bool $forceRefresh): void
    {
        try {
            $service->getOrganizations($connectionId, $accessToken, $forceRefresh);
            $service->getAdAccounts($connectionId, $accessToken, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing LinkedIn assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync Twitter assets.
     */
    protected function syncTwitterAssets(TwitterAssetsService $service, string $connectionId, string $accessToken, bool $forceRefresh): void
    {
        try {
            $service->getAccount($connectionId, $accessToken, $forceRefresh);
            $service->getAdAccounts($connectionId, $accessToken, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing Twitter assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync Snapchat assets.
     */
    protected function syncSnapchatAssets(SnapchatAssetsService $service, string $connectionId, string $accessToken, bool $forceRefresh): void
    {
        try {
            $orgs = $service->getOrganizations($connectionId, $accessToken, $forceRefresh);
            $service->getAdAccounts($connectionId, $accessToken, null, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing Snapchat assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync Pinterest assets.
     */
    protected function syncPinterestAssets(PinterestAssetsService $service, string $connectionId, string $accessToken, bool $forceRefresh): void
    {
        try {
            $service->getAccount($connectionId, $accessToken, $forceRefresh);
            $service->getBoards($connectionId, $accessToken, $forceRefresh);
            $service->getAdAccounts($connectionId, $accessToken, $forceRefresh);
        } catch (\Exception $e) {
            Log::warning('Error syncing Pinterest assets', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncPlatformAssetsJob failed permanently', [
            'connection_id' => $this->connectionId,
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
