<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MetaSyncService extends BasePlatformSyncService
{
    protected $platform = 'meta';
    protected $apiVersion = 'v19.0';
    protected $baseUrl;

    public function __construct($integration)
    {
        parent::__construct($integration);
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Main sync method for Meta platforms
     */
    public function sync(array $options = []): array
    {
        $startTime = now();
        $results = [
            'posts' => 0,
            'comments' => 0,
            'messages' => 0,
            'metrics' => 0,
            'ads' => 0,
        ];

        if (!$this->validateAccessToken()) {
            return ['error' => 'Invalid or expired access token'];
        }

        try {
            $since = $options['since'] ?? $this->getLastSyncTime('full');

            // Sync Facebook Page data
            if ($facebookPageId = $this->integration->settings['facebook_page_id'] ?? null) {
                $results['posts'] += $this->syncPagePosts($facebookPageId, $since);
                $results['comments'] += $this->syncComments($since);
                $results['messages'] += $this->syncMessages($since);
                $results['metrics'] += $this->syncPageInsights($facebookPageId, $since);
            }

            // Sync Instagram Business Account data
            if ($instagramAccountId = $this->integration->settings['instagram_account_id'] ?? null) {
                $results['posts'] += $this->syncInstagramPosts($instagramAccountId, $since);
                $results['comments'] += $this->syncInstagramComments($instagramAccountId, $since);
                $results['messages'] += $this->syncInstagramMessages($instagramAccountId, $since);
                $results['metrics'] += $this->syncInstagramInsights($instagramAccountId, $since);
            }

            // Sync Ad Campaigns
            if ($adAccountId = $this->integration->settings['ad_account_id'] ?? null) {
                $results['ads'] += $this->syncAdCampaigns($adAccountId, $since);
            }

            $this->logSync('full', 'success', [
                'started_at' => $startTime,
                'items_synced' => array_sum($results),
                'breakdown' => $results
            ]);

            return $results;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'full_sync');
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sync Facebook Page Posts
     */
    protected function syncPagePosts(string $pageId, Carbon $since): int
    {
        $synced = 0;
        $url = "{$this->baseUrl}/{$pageId}/posts";

        try {
            $response = $this->makeApiRequest($url, [
                'fields' => 'id,message,created_time,permalink_url,attachments,reactions.summary(true),comments.summary(true),shares',
                'since' => $since->timestamp,
                'limit' => 100,
            ]);

            if (isset($response['data'])) {
                foreach ($response['data'] as $post) {
                    $this->storePost([
                        'platform_post_id' => $post['id'],
                        'post_type' => 'facebook_post',
                        'content' => $post['message'] ?? null,
                        'media_urls' => $this->extractMediaUrls($post['attachments'] ?? []),
                        'permalink' => $post['permalink_url'] ?? null,
                        'published_at' => Carbon::parse($post['created_time']),
                        'metrics' => [
                            'reactions' => $post['reactions']['summary']['total_count'] ?? 0,
                            'comments' => $post['comments']['summary']['total_count'] ?? 0,
                            'shares' => $post['shares']['count'] ?? 0,
                        ],
                    ]);
                    $synced++;
                }

                // Handle pagination
                while (isset($response['paging']['next'])) {
                    $response = $this->makeApiRequest($response['paging']['next']);
                    if (isset($response['data'])) {
                        foreach ($response['data'] as $post) {
                            $this->storePost([
                                'platform_post_id' => $post['id'],
                                'post_type' => 'facebook_post',
                                'content' => $post['message'] ?? null,
                                'media_urls' => $this->extractMediaUrls($post['attachments'] ?? []),
                                'permalink' => $post['permalink_url'] ?? null,
                                'published_at' => Carbon::parse($post['created_time']),
                                'metrics' => [
                                    'reactions' => $post['reactions']['summary']['total_count'] ?? 0,
                                    'comments' => $post['comments']['summary']['total_count'] ?? 0,
                                    'shares' => $post['shares']['count'] ?? 0,
                                ],
                            ]);
                            $synced++;
                        }
                    } else {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->handleApiError($e, 'sync_page_posts');
        }

        return $synced;
    }

    /**
     * Sync Instagram Posts
     */
    protected function syncInstagramPosts(string $accountId, Carbon $since): int
    {
        $synced = 0;
        $url = "{$this->baseUrl}/{$accountId}/media";

        try {
            $response = $this->makeApiRequest($url, [
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count',
                'since' => $since->timestamp,
                'limit' => 100,
            ]);

            if (isset($response['data'])) {
                foreach ($response['data'] as $post) {
                    $this->storePost([
                        'platform_post_id' => $post['id'],
                        'post_type' => 'instagram_' . strtolower($post['media_type']),
                        'content' => $post['caption'] ?? null,
                        'media_urls' => [$post['media_url'] ?? null],
                        'permalink' => $post['permalink'] ?? null,
                        'published_at' => Carbon::parse($post['timestamp']),
                        'metrics' => [
                            'likes' => $post['like_count'] ?? 0,
                            'comments' => $post['comments_count'] ?? 0,
                        ],
                    ]);
                    $synced++;
                }
            }
        } catch (\Exception $e) {
            $this->handleApiError($e, 'sync_instagram_posts');
        }

        return $synced;
    }

    /**
     * Sync Comments
     */
    protected function syncComments(Carbon $since): int
    {
        // Implementation for syncing Facebook comments
        return 0; // Placeholder
    }

    /**
     * Sync Instagram Comments
     */
    protected function syncInstagramComments(string $accountId, Carbon $since): int
    {
        // Implementation for syncing Instagram comments
        return 0; // Placeholder
    }

    /**
     * Sync Messages
     */
    protected function syncMessages(Carbon $since): int
    {
        // Implementation for syncing Facebook Messenger
        return 0; // Placeholder
    }

    /**
     * Sync Instagram Messages
     */
    protected function syncInstagramMessages(string $accountId, Carbon $since): int
    {
        // Implementation for syncing Instagram DMs
        return 0; // Placeholder
    }

    /**
     * Sync Page Insights
     */
    protected function syncPageInsights(string $pageId, Carbon $since): int
    {
        $url = "{$this->baseUrl}/{$pageId}/insights";

        try {
            $response = $this->makeApiRequest($url, [
                'metric' => 'page_impressions,page_engaged_users,page_post_engagements,page_fans',
                'period' => 'day',
                'since' => $since->timestamp,
            ]);

            // Store insights in database
            // Implementation here

            return 1;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'sync_page_insights');
            return 0;
        }
    }

    /**
     * Sync Instagram Insights
     */
    protected function syncInstagramInsights(string $accountId, Carbon $since): int
    {
        // Implementation for Instagram insights
        return 0; // Placeholder
    }

    /**
     * Sync Metrics
     */
    protected function syncMetrics(Carbon $since): int
    {
        // Implementation
        return 0;
    }

    /**
     * Sync Ad Campaigns
     */
    protected function syncAdCampaigns(string $adAccountId, Carbon $since): int
    {
        $synced = 0;
        $url = "{$this->baseUrl}/act_{$adAccountId}/campaigns";

        try {
            $response = $this->makeApiRequest($url, [
                'fields' => 'id,name,objective,status,daily_budget,lifetime_budget,created_time,updated_time,insights{spend,impressions,clicks,ctr,cpc,cpp,cpm,reach}',
                'limit' => 100,
            ]);

            if (isset($response['data'])) {
                foreach ($response['data'] as $campaign) {
                    // Store campaign in database
                    DB::table('cmis_ads.ad_campaigns')->updateOrInsert(
                        [
                            'platform_campaign_id' => $campaign['id'],
                            'org_id' => $this->orgId,
                        ],
                        [
                            'integration_id' => $this->integration->integration_id,
                            'platform' => 'meta',
                            'campaign_name' => $campaign['name'],
                            'objective' => $campaign['objective'] ?? null,
                            'status' => $campaign['status'],
                            'daily_budget' => $campaign['daily_budget'] ?? null,
                            'lifetime_budget' => $campaign['lifetime_budget'] ?? null,
                            'metrics' => json_encode($campaign['insights']['data'][0] ?? []),
                            'created_at' => Carbon::parse($campaign['created_time']),
                            'updated_at' => now(),
                        ]
                    );
                    $synced++;
                }
            }
        } catch (\Exception $e) {
            $this->handleApiError($e, 'sync_ad_campaigns');
        }

        return $synced;
    }

    /**
     * Get API Client
     */
    protected function getApiClient(): mixed
    {
        return Http::withToken($this->integration->access_token)
            ->timeout(30)
            ->retry(3, 100);
    }

    /**
     * Make API Request
     */
    protected function makeApiRequest(string $url, array $params = [])
    {
        if (!$this->checkRateLimit('api_call')) {
            throw new \Exception('Rate limit exceeded');
        }

        $response = $this->getApiClient()->get($url, $params);

        if ($response->failed()) {
            throw new \Exception("API request failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Extract media URLs from attachments
     */
    protected function extractMediaUrls(array $attachments): array
    {
        $urls = [];
        if (isset($attachments['data'])) {
            foreach ($attachments['data'] as $attachment) {
                if (isset($attachment['media']['image']['src'])) {
                    $urls[] = $attachment['media']['image']['src'];
                } elseif (isset($attachment['media']['source'])) {
                    $urls[] = $attachment['media']['source'];
                }
            }
        }
        return $urls;
    }

    /**
     * Refresh Access Token
     */
    protected function refreshAccessToken(): bool
    {
        // Meta's long-lived tokens don't need regular refresh
        // They last 60 days and can be exchanged for new ones
        return true;
    }

    /**
     * Sync Posts (generic method)
     */
    protected function syncPosts(Carbon $since): int
    {
        return $this->syncPagePosts(
            $this->integration->settings['facebook_page_id'] ?? '',
            $since
        );
    }

    /**
     * Sync Page Data
     * Fetches and stores Facebook Page information
     */
    public function syncPageData($integration): array
    {
        try {
            $pageId = $integration->settings['facebook_page_id'] ?? null;

            if (!$pageId) {
                return [
                    'success' => false,
                    'error' => 'No Facebook page ID configured'
                ];
            }

            $url = "{$this->baseUrl}/{$pageId}";

            $response = $this->makeApiRequest($url, [
                'fields' => 'id,name,about,category,description,emails,fan_count,followers_count,link,username,website,picture{url},cover{source},location,phone,verification_status,is_published'
            ]);

            if (!$response) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch page data'
                ];
            }

            // Store page data in database
            DB::table('cmis_meta.facebook_pages')->updateOrInsert(
                [
                    'page_id' => $response['id'],
                    'org_id' => $this->orgId,
                ],
                [
                    'integration_id' => $integration->integration_id,
                    'name' => $response['name'] ?? null,
                    'about' => $response['about'] ?? null,
                    'category' => $response['category'] ?? null,
                    'description' => $response['description'] ?? null,
                    'fan_count' => $response['fan_count'] ?? 0,
                    'followers_count' => $response['followers_count'] ?? 0,
                    'link' => $response['link'] ?? null,
                    'username' => $response['username'] ?? null,
                    'website' => $response['website'] ?? null,
                    'picture_url' => $response['picture']['data']['url'] ?? null,
                    'cover_url' => $response['cover']['source'] ?? null,
                    'location' => json_encode($response['location'] ?? []),
                    'phone' => $response['phone'] ?? null,
                    'verification_status' => $response['verification_status'] ?? null,
                    'is_published' => $response['is_published'] ?? true,
                    'last_synced_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Log::info("Facebook page data synced", [
                'org_id' => $this->orgId,
                'page_id' => $pageId,
                'page_name' => $response['name'] ?? 'Unknown',
                'followers' => $response['followers_count'] ?? 0,
            ]);

            return [
                'success' => true,
                'data' => [
                    'page_id' => $response['id'],
                    'name' => $response['name'] ?? null,
                    'followers' => $response['followers_count'] ?? 0,
                    'fans' => $response['fan_count'] ?? 0,
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to sync page data", [
                'org_id' => $this->orgId ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync Insights
     * Fetches and stores Facebook Page insights/analytics
     */
    public function syncInsights($integration): array
    {
        try {
            $pageId = $integration->settings['facebook_page_id'] ?? null;

            if (!$pageId) {
                return [
                    'success' => false,
                    'error' => 'No Facebook page ID configured'
                ];
            }

            $url = "{$this->baseUrl}/{$pageId}/insights";

            // Fetch various page insights metrics
            $metrics = [
                'page_impressions',
                'page_impressions_unique',
                'page_impressions_paid',
                'page_impressions_organic',
                'page_engaged_users',
                'page_post_engagements',
                'page_consumptions',
                'page_fans',
                'page_fans_online',
                'page_views_total',
                'page_video_views',
                'page_actions_post_reactions_total',
            ];

            $response = $this->makeApiRequest($url, [
                'metric' => implode(',', $metrics),
                'period' => 'day',
                'since' => now()->subDays(7)->timestamp,
                'until' => now()->timestamp,
            ]);

            if (!isset($response['data']) || empty($response['data'])) {
                return [
                    'success' => false,
                    'error' => 'No insights data available'
                ];
            }

            $synced = 0;

            // Store each insight metric
            foreach ($response['data'] as $insight) {
                $metricName = $insight['name'];
                $values = $insight['values'] ?? [];

                foreach ($values as $value) {
                    DB::table('cmis_meta.page_insights')->updateOrInsert(
                        [
                            'page_id' => $pageId,
                            'org_id' => $this->orgId,
                            'metric_name' => $metricName,
                            'date' => Carbon::parse($value['end_time'])->toDateString(),
                        ],
                        [
                            'integration_id' => $integration->integration_id,
                            'value' => $value['value'] ?? 0,
                            'period' => $insight['period'] ?? 'day',
                            'synced_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $synced++;
                }
            }

            Log::info("Facebook page insights synced", [
                'org_id' => $this->orgId,
                'page_id' => $pageId,
                'metrics_synced' => $synced,
            ]);

            return [
                'success' => true,
                'data' => [
                    'page_id' => $pageId,
                    'metrics_synced' => $synced,
                    'metrics' => $response['data'],
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to sync insights", [
                'org_id' => $this->orgId ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
