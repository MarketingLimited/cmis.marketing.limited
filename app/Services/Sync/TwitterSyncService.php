<?php

namespace App\Services\Sync;

use App\Services\Connectors\Providers\TwitterConnector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Twitter/X Platform Sync Service
 *
 * Syncs content, metrics, and engagement data from Twitter/X API v2.
 * Uses TwitterConnector for API interactions.
 */
class TwitterSyncService extends BasePlatformSyncService
{
    protected $platform = 'twitter';
    protected ?TwitterConnector $connector = null;

    /**
     * Get Twitter connector instance
     */
    protected function getConnector(): TwitterConnector
    {
        if (!$this->connector) {
            $this->connector = app(TwitterConnector::class);
        }
        return $this->connector;
    }

    /**
     * Sync data from Twitter/X
     */
    public function sync(array $options = []): array
    {
        $since = isset($options['since'])
            ? Carbon::parse($options['since'])
            : Carbon::now()->subDays(config('sync.lookback_days', 7));

        Log::info("Starting Twitter sync", [
            'org_id' => $this->orgId,
            'integration_id' => $this->integration->integration_id,
            'since' => $since->toDateTimeString(),
        ]);

        try {
            // Ensure token is valid
            $this->ensureValidToken();

            $postsCount = $this->syncPosts($since);
            $metricsCount = $this->syncMetrics($since);
            $commentsCount = $this->syncComments($since);
            $messagesCount = $this->syncMessages($since);

            $result = [
                'success' => true,
                'platform' => 'twitter',
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
            Log::error("Twitter sync failed", [
                'error' => $e->getMessage(),
                'org_id' => $this->orgId,
            ]);

            $this->logSync('full_sync', 'error', [], $e->getMessage());

            return [
                'success' => false,
                'platform' => 'twitter',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure access token is valid, refresh if needed
     */
    protected function ensureValidToken(): void
    {
        if ($this->integration->token_expires_at &&
            Carbon::parse($this->integration->token_expires_at)->isPast()) {
            $this->refreshAccessToken();
        }
    }

    /**
     * Sync tweets from Twitter/X
     */
    protected function syncPosts(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $posts = $connector->syncPosts($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            Log::info("Twitter posts synced", [
                'count' => $posts->count(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return $posts->count();
        } catch (\Exception $e) {
            Log::warning("Twitter posts sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync metrics/analytics from Twitter/X
     */
    protected function syncMetrics(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $metrics = $connector->getAccountMetrics($this->integration);

            // Store metrics in unified_metrics table
            if ($metrics->isNotEmpty()) {
                DB::table('cmis.unified_metrics')->updateOrInsert(
                    [
                        'org_id' => $this->orgId,
                        'platform' => 'twitter',
                        'entity_type' => 'account',
                        'entity_id' => $this->integration->external_account_id,
                        'date' => now()->toDateString(),
                    ],
                    [
                        'followers' => $metrics->get('followers_count', 0),
                        'following' => $metrics->get('following_count', 0),
                        'posts_count' => $metrics->get('tweet_count', 0),
                        'raw_metrics' => json_encode($metrics->toArray()),
                        'updated_at' => now(),
                    ]
                );
            }

            Log::info("Twitter metrics synced", [
                'integration_id' => $this->integration->integration_id,
            ]);

            return 1;
        } catch (\Exception $e) {
            Log::warning("Twitter metrics sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync replies/mentions from Twitter/X
     */
    protected function syncComments(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $comments = $connector->syncComments($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            return $comments->count();
        } catch (\Exception $e) {
            Log::warning("Twitter comments sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync direct messages from Twitter/X
     */
    protected function syncMessages(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $messages = $connector->syncMessages($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            Log::info("Twitter messages synced", [
                'count' => $messages->count(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return $messages->count();
        } catch (\Exception $e) {
            Log::warning("Twitter messages sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Refresh access token
     */
    protected function refreshAccessToken(): bool
    {
        try {
            $connector = $this->getConnector();
            $this->integration = $connector->refreshToken($this->integration);

            Log::info("Twitter token refreshed", [
                'integration_id' => $this->integration->integration_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Twitter token refresh failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Twitter API client (via connector)
     */
    protected function getApiClient()
    {
        return $this->getConnector();
    }

    /**
     * Sync Twitter user profile data
     */
    public function syncProfile($integration): array
    {
        try {
            $connector = $this->getConnector();
            $metrics = $connector->getAccountMetrics($integration);

            return [
                'success' => true,
                'data' => $metrics->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error("Twitter profile sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync Twitter tweets data
     */
    public function syncTweets($integration): array
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
            Log::error("Twitter tweets sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
