<?php

namespace App\Services\Sync;

use App\Services\Connectors\Providers\LinkedInConnector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LinkedIn Platform Sync Service
 *
 * Syncs content, metrics, and engagement data from LinkedIn Marketing API.
 * Uses LinkedInConnector for API interactions.
 */
class LinkedInSyncService extends BasePlatformSyncService
{
    protected $platform = 'linkedin';
    protected ?LinkedInConnector $connector = null;

    /**
     * Get LinkedIn connector instance
     */
    protected function getConnector(): LinkedInConnector
    {
        if (!$this->connector) {
            $this->connector = app(LinkedInConnector::class);
        }
        return $this->connector;
    }

    /**
     * Sync data from LinkedIn
     */
    public function sync(array $options = []): array
    {
        $since = isset($options['since'])
            ? Carbon::parse($options['since'])
            : Carbon::now()->subDays(config('sync.lookback_days', 7));

        Log::info("Starting LinkedIn sync", [
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
                'platform' => 'linkedin',
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
            Log::error("LinkedIn sync failed", [
                'error' => $e->getMessage(),
                'org_id' => $this->orgId,
            ]);

            $this->logSync('full_sync', 'error', [], $e->getMessage());

            return [
                'success' => false,
                'platform' => 'linkedin',
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
     * Sync posts/shares from LinkedIn
     */
    protected function syncPosts(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $posts = $connector->syncPosts($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            Log::info("LinkedIn posts synced", [
                'count' => $posts->count(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return $posts->count();
        } catch (\Exception $e) {
            Log::warning("LinkedIn posts sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync metrics/analytics from LinkedIn
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
                        'platform' => 'linkedin',
                        'entity_type' => 'account',
                        'entity_id' => $this->integration->external_account_id,
                        'date' => now()->toDateString(),
                    ],
                    [
                        'followers' => $metrics->get('follower_count', 0),
                        'following' => $metrics->get('connection_count', 0),
                        'posts_count' => $metrics->get('post_count', 0),
                        'raw_metrics' => json_encode($metrics->toArray()),
                        'updated_at' => now(),
                    ]
                );
            }

            Log::info("LinkedIn metrics synced", [
                'integration_id' => $this->integration->integration_id,
            ]);

            return 1;
        } catch (\Exception $e) {
            Log::warning("LinkedIn metrics sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync comments from LinkedIn
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
            Log::warning("LinkedIn comments sync failed", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync messages/InMail from LinkedIn
     */
    protected function syncMessages(Carbon $since): int
    {
        try {
            $connector = $this->getConnector();
            $messages = $connector->syncMessages($this->integration, [
                'since' => $since->toIso8601String(),
            ]);

            Log::info("LinkedIn messages synced", [
                'count' => $messages->count(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return $messages->count();
        } catch (\Exception $e) {
            Log::warning("LinkedIn messages sync failed", [
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

            Log::info("LinkedIn token refreshed", [
                'integration_id' => $this->integration->integration_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("LinkedIn token refresh failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get LinkedIn API client (via connector)
     */
    protected function getApiClient()
    {
        return $this->getConnector();
    }

    /**
     * Sync LinkedIn company page data
     */
    public function syncCompanyPage($integration): array
    {
        try {
            $connector = $this->getConnector();
            $metrics = $connector->getAccountMetrics($integration);

            return [
                'success' => true,
                'data' => $metrics->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error("LinkedIn company page sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync LinkedIn campaigns (for ad accounts)
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
            Log::error("LinkedIn campaigns sync failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
