<?php

namespace App\Services\Sync;

use App\Services\Connectors\Providers\TikTokConnector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * TikTok Platform Sync Service
 *
 * Syncs content, metrics, and engagement data from TikTok Business API.
 * Uses TikTokConnector for API interactions.
 */
class TikTokSyncService extends BasePlatformSyncService
{
    protected $platform = 'tiktok';
    protected ?TikTokConnector $connector = null;

    /**
     * Get TikTok connector instance
     */
    protected function getConnector(): TikTokConnector
    {
        if (!$this->connector) {
            $this->connector = app(TikTokConnector::class);
        }
        return $this->connector;
    }

    /**
     * Sync data from TikTok
     */
    public function sync(array $options = []): array
    {
        $since = isset($options['since'])
            ? Carbon::parse($options['since'])
            : Carbon::now()->subDays(config('sync.lookback_days', 7));

        Log::info("Starting TikTok sync", [
            'org_id' => $this->orgId,
            'integration_id' => $this->integration->integration_id,
            'since' => $since->toDateTimeString(),
        ]);

        try {
            // Ensure token is valid (TikTok tokens are long-lived)
            $this->ensureValidToken();

            $postsCount = $this->syncPosts($since);
            $metricsCount = $this->syncMetrics($since);
            $commentsCount = $this->syncComments($since);
            $messagesCount = $this->syncMessages($since);

            $result = [
                'success' => true,
                'platform' => 'tiktok',
                'synced' => [
                    'posts' => $postsCount,
                    'metrics' => $metricsCount,
                    'comments' => $commentsCount,
                    'messages' => $messagesCount,
                ],
                'errors' => [],
            ];

            $this->logSync('full_sync', 'success', $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("TikTok sync failed", [
                'error' => $e->getMessage(),
                'org_id' => $this->orgId,
            ]);

            $this->logSync('full_sync', 'error', [], $e->getMessage());

            return [
                'success' => false,
                'platform' => 'tiktok',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure access token is valid
     * Note: TikTok tokens are long-lived and don't have refresh tokens
     */
    protected function ensureValidToken(): void
    {
        if ($this->integration->token_expires_at &&
            Carbon::parse($this->integration->token_expires_at)->isPast()) {
            Log::warning("TikTok token expired, re-authentication required", [
                'integration_id' => $this->integration->integration_id,
            ]);
        }
    }

    /**
     * Sync posts/videos from TikTok
     */
    protected function syncPosts(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $posts = $connector->syncPosts($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            Log::info("TikTok posts synced", [
                'count' => $posts->count(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return $posts->count();
        } catch (\Exception $e) {
            Log::warning("TikTok posts sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync metrics/analytics from TikTok
     */
    protected function syncMetrics(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $metrics = $connector->getAccountMetrics($this->integration);

            // Store metrics in unified_metrics table if we have data
            if ($metrics->isNotEmpty()) {
                DB::table('cmis.unified_metrics')->updateOrInsert(
                    [
                        'org_id' => $this->orgId,
                        'platform' => 'tiktok',
                        'entity_type' => 'account',
                        'entity_id' => $this->integration->external_account_id,
                        'date' => now()->toDateString(),
                    ],
                    [
                        'followers' => $metrics->get('follower_count', 0),
                        'following' => $metrics->get('following_count', 0),
                        'posts_count' => $metrics->get('video_count', 0),
                        'likes' => $metrics->get('likes_count', 0),
                        'raw_metrics' => json_encode($metrics->toArray()),
                        'updated_at' => now(),
                    ]
                );
            }

            Log::info("TikTok metrics synced", [
                'integration_id' => $this->integration->integration_id,
            ]);

            return 1;
        } catch (\Exception $e) {
            Log::warning("TikTok metrics sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync comments from TikTok
     */
    protected function syncComments(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $comments = $connector->syncComments($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            Log::info("TikTok comments synced", [
                'count' => $comments->count(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return $comments->count();
        } catch (\Exception $e) {
            Log::warning("TikTok comments sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync messages/inbox from TikTok
     * Note: TikTok doesn't have a public messages API yet
     */
    protected function syncMessages(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $messages = $connector->syncMessages($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            return $messages->count();
        } catch (\Exception $e) {
            Log::warning("TikTok messages sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Refresh OAuth access token
     * Note: TikTok tokens are long-lived and don't have refresh tokens
     */
    protected function refreshAccessToken(): bool
    {
        try {
            $connector = $this->getConnector();
            $this->integration = $connector->refreshToken($this->integration);

            Log::info("TikTok token refresh attempted", [
                'integration_id' => $this->integration->integration_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("TikTok token refresh failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get TikTok API client (via connector)
     */
    protected function getApiClient()
    {
        return $this->getConnector();
    }

    /**
     * Sync TikTok account information
     */
    public function syncAccountInfo($integration): array
    {
        try {
            $connector = $this->getConnector();
            $metrics = $connector->getAccountMetrics($integration);

            return [
                'success' => true,
                'data' => $metrics->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error("TikTok account info sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync TikTok videos
     */
    public function syncVideos($integration): array
    {
        try {
            $connector = $this->getConnector();
            $posts = $connector->syncPosts($integration);

            return [
                'success' => true,
                'data' => $posts->toArray(),
                'count' => $posts->count(),
            ];
        } catch (\Exception $e) {
            Log::error("TikTok videos sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync TikTok campaigns (for ad accounts)
     */
    public function syncCampaigns($integration, array $options = []): array
    {
        try {
            $connector = $this->getConnector();
            $campaigns = $connector->syncCampaigns($integration, $options);

            return [
                'success' => true,
                'data' => $campaigns->toArray(),
                'count' => $campaigns->count(),
            ];
        } catch (\Exception $e) {
            Log::error("TikTok campaigns sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
