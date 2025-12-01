<?php

namespace App\Services\AdPlatforms\Pinterest;

use App\Models\Core\Integration;
use App\Services\AdPlatforms\AbstractAdPlatform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pinterest Ads Platform Service
 *
 * Complete implementation of Pinterest Ads API v5
 * Supports all campaign objectives, targeting options, and ad formats
 *
 * @see https://developers.pinterest.com/docs/api/v5/
 */
class PinterestAdsPlatform extends AbstractAdPlatform
{
    protected string $advertiserId;
    protected string $accessToken;

    /**
     * API version for Pinterest Ads API
     */
    protected string $apiVersion = 'v5';

    /**
     * Base URL for Pinterest Ads API
     */
    protected string $apiBaseUrl = 'https://api.pinterest.com';

    /**
     * Initialize platform with advertiser ID from integration
     */
    public function __construct(Integration $integration)
    {
        parent::__construct($integration);

        // Extract advertiser ID from integration metadata
        if (empty($integration->metadata['ad_account_id'])) {
            throw new \InvalidArgumentException('Pinterest ad_account_id not configured in integration metadata');
        }
        $this->advertiserId = $integration->metadata['ad_account_id'];

        // Extract and decrypt access token
        if (empty($integration->access_token)) {
            throw new \InvalidArgumentException('Pinterest integration not authenticated');
        }
        $this->accessToken = decrypt($integration->access_token);

        // Check token expiration and refresh if needed
        $this->ensureValidToken();
    }

    protected function getConfig(): array
    {
        return [
            'api_version' => config('services.pinterest.api_version', 'v5'),
            'api_base_url' => config('services.pinterest.base_url', 'https://api.pinterest.com'),
        ];
    }

    protected function getPlatformName(): string
    {
        return 'pinterest';
    }

    /**
     * Override to add Pinterest-specific Authorization header
     */
    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);
    }

    /**
     * Build API URL
     */
    protected function buildUrl(string $endpoint, array $params = []): string
    {
        $url = rtrim($this->apiBaseUrl, '/') . '/' . $this->apiVersion . '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url = str_replace('{' . $key . '}', $value, $url);
            }
        }

        return $url;
    }

    /**
     * Ensure token is valid, refresh if expired
     */
    protected function ensureValidToken(): void
    {
        if ($this->integration->token_expires_at &&
            $this->integration->token_expires_at->isPast()) {
            Log::info('Pinterest token expired, refreshing', [
                'integration_id' => $this->integration->integration_id,
            ]);

            $result = $this->refreshAccessToken();
            if ($result['success']) {
                $this->accessToken = $result['access_token'];
            } else {
                throw new \Exception('Failed to refresh Pinterest access token: ' . ($result['error'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Create a new Pinterest campaign
     *
     * @param array $data Campaign data including:
     *   - name: Campaign name
     *   - objective_type: Campaign objective
     *   - daily_spend_cap: Daily budget (optional)
     *   - lifetime_spend_cap: Lifetime budget (optional)
     *   - status: Campaign status (ACTIVE, PAUSED, ARCHIVED)
     * @return array Response with campaign details or error
     */
    public function createCampaign(array $data): array
    {
        try {
            $payload = [
                'ad_account_id' => $this->advertiserId,
                'name' => $data['name'],
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'objective_type' => $this->mapObjective($data['objective']),
            ];

            // Budget settings
            if (isset($data['daily_budget'])) {
                $payload['daily_spend_cap'] = (int) ($data['daily_budget'] * 1000000); // Convert to micros
            }

            if (isset($data['lifetime_budget'])) {
                $payload['lifetime_spend_cap'] = (int) ($data['lifetime_budget'] * 1000000);
            }

            // Default tracking URLs
            if (isset($data['tracking_urls'])) {
                $payload['tracking_urls'] = $data['tracking_urls'];
            }

            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/campaigns', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('POST', $url, [$payload]); // Pinterest expects array of campaigns

            if (isset($response[0]['id'])) {
                return [
                    'success' => true,
                    'campaign_id' => $response[0]['id'],
                    'data' => $response[0],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest createCampaign failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing Pinterest campaign
     */
    public function updateCampaign(string $externalId, array $data): array
    {
        try {
            $payload = [
                'id' => $externalId,
            ];

            if (isset($data['name'])) {
                $payload['name'] = $data['name'];
            }

            if (isset($data['status'])) {
                $payload['status'] = $this->mapStatus($data['status']);
            }

            if (isset($data['daily_budget'])) {
                $payload['daily_spend_cap'] = (int) ($data['daily_budget'] * 1000000);
            }

            if (isset($data['lifetime_budget'])) {
                $payload['lifetime_spend_cap'] = (int) ($data['lifetime_budget'] * 1000000);
            }

            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/campaigns', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('PATCH', $url, [$payload]);

            if (isset($response[0]['id'])) {
                return [
                    'success' => true,
                    'data' => $response[0],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to update campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest updateCampaign failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign details
     */
    public function getCampaign(string $externalId): array
    {
        try {
            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/campaigns/{campaign_id}', [
                'ad_account_id' => $this->advertiserId,
                'campaign_id' => $externalId,
            ]);

            $response = $this->makeRequest('GET', $url);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => 'Campaign not found',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getCampaign failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete (archive) a Pinterest campaign
     */
    public function deleteCampaign(string $externalId): array
    {
        return $this->updateCampaignStatus($externalId, 'ARCHIVED');
    }

    /**
     * Fetch all campaigns with optional filters
     */
    public function fetchCampaigns(array $filters = []): array
    {
        try {
            $params = [];

            if (isset($filters['campaign_ids'])) {
                $params['campaign_ids'] = implode(',', $filters['campaign_ids']);
            }

            if (isset($filters['page_size'])) {
                $params['page_size'] = $filters['page_size'];
            }

            if (isset($filters['bookmark'])) {
                $params['bookmark'] = $filters['bookmark'];
            }

            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/campaigns', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['items'])) {
                return [
                    'success' => true,
                    'campaigns' => $response['items'],
                    'pagination' => [
                        'bookmark' => $response['bookmark'] ?? null,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch campaigns',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest fetchCampaigns failed', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign analytics/metrics
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        try {
            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/campaigns/analytics', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'campaign_ids' => $externalId,
                'columns' => implode(',', [
                    'SPEND_IN_DOLLAR',
                    'IMPRESSION',
                    'CLICKTHROUGH',
                    'CTR',
                    'ECPC_IN_DOLLAR',
                    'ECPM_IN_DOLLAR',
                    'TOTAL_ENGAGEMENT',
                    'OUTBOUND_CLICK',
                    'PIN_CLICK',
                    'SAVE',
                    'VIDEO_V50_WATCH',
                    'VIDEO_MRC_VIEWS',
                    'VIDEO_START',
                    'TOTAL_CONVERSIONS',
                    'TOTAL_CONVERSIONS_VALUE_IN_MICRO_DOLLAR',
                    'ROAS',
                ]),
                'granularity' => 'TOTAL',
            ];

            $response = $this->makeRequest('GET', $url, $params);

            if (is_array($response) && !empty($response)) {
                $metrics = $response[0] ?? [];

                return [
                    'success' => true,
                    'metrics' => [
                        'spend' => $metrics['SPEND_IN_DOLLAR'] ?? 0,
                        'impressions' => $metrics['IMPRESSION'] ?? 0,
                        'clicks' => $metrics['CLICKTHROUGH'] ?? 0,
                        'ctr' => $metrics['CTR'] ?? 0,
                        'cpc' => $metrics['ECPC_IN_DOLLAR'] ?? 0,
                        'cpm' => $metrics['ECPM_IN_DOLLAR'] ?? 0,
                        'engagement' => $metrics['TOTAL_ENGAGEMENT'] ?? 0,
                        'outbound_clicks' => $metrics['OUTBOUND_CLICK'] ?? 0,
                        'pin_clicks' => $metrics['PIN_CLICK'] ?? 0,
                        'saves' => $metrics['SAVE'] ?? 0,
                        'video_views_50' => $metrics['VIDEO_V50_WATCH'] ?? 0,
                        'video_views_mrc' => $metrics['VIDEO_MRC_VIEWS'] ?? 0,
                        'video_starts' => $metrics['VIDEO_START'] ?? 0,
                        'conversions' => $metrics['TOTAL_CONVERSIONS'] ?? 0,
                        'conversion_value' => ($metrics['TOTAL_CONVERSIONS_VALUE_IN_MICRO_DOLLAR'] ?? 0) / 1000000,
                        'roas' => $metrics['ROAS'] ?? 0,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => 'No metrics data available',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getCampaignMetrics failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update campaign status
     */
    public function updateCampaignStatus(string $externalId, string $status): array
    {
        return $this->updateCampaign($externalId, ['status' => $status]);
    }

    /**
     * Create an Ad Group
     *
     * @param string $campaignExternalId Parent campaign ID
     * @param array $data Ad group data
     * @return array Response with ad group ID or error
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $payload = [
                'ad_account_id' => $this->advertiserId,
                'campaign_id' => $campaignExternalId,
                'name' => $data['name'],
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'budget_type' => $data['budget_type'] ?? 'DAILY',
                'budget_in_micro_currency' => (int) (($data['budget'] ?? 0) * 1000000),
                'bid_strategy_type' => $data['bid_strategy'] ?? 'AUTOMATIC_BID',
            ];

            // Scheduling
            if (isset($data['start_time'])) {
                $payload['start_time'] = $data['start_time'];
            }
            if (isset($data['end_time'])) {
                $payload['end_time'] = $data['end_time'];
            }

            // Targeting spec
            $targetingSpec = [];

            if (isset($data['targeting']['geo_ids'])) {
                $targetingSpec['GEO'] = $data['targeting']['geo_ids'];
            }

            if (isset($data['targeting']['age_bucket'])) {
                $targetingSpec['AGE_BUCKET'] = $data['targeting']['age_bucket'];
            }

            if (isset($data['targeting']['gender'])) {
                $targetingSpec['GENDER'] = $data['targeting']['gender'];
            }

            if (isset($data['targeting']['interest_ids'])) {
                $targetingSpec['INTEREST'] = $data['targeting']['interest_ids'];
            }

            if (isset($data['targeting']['keywords'])) {
                $targetingSpec['KEYWORD'] = $data['targeting']['keywords'];
            }

            if (isset($data['targeting']['audience_ids'])) {
                $targetingSpec['AUDIENCE_INCLUDE'] = $data['targeting']['audience_ids'];
            }

            if (isset($data['targeting']['exclude_audience_ids'])) {
                $targetingSpec['AUDIENCE_EXCLUDE'] = $data['targeting']['exclude_audience_ids'];
            }

            if (!empty($targetingSpec)) {
                $payload['targeting_spec'] = $targetingSpec;
            }

            // Placements
            if (isset($data['placements'])) {
                $payload['placement_group'] = $data['placements'];
            }

            // Pacing type
            if (isset($data['pacing_type'])) {
                $payload['pacing_delivery_type'] = $data['pacing_type'];
            }

            // Optimization goal
            if (isset($data['optimization_goal'])) {
                $payload['optimization_goal_metadata'] = [
                    'conversion_tag_v3_goal_metadata' => [
                        'attribution_windows' => [
                            'click_window_days' => 30,
                            'engagement_window_days' => 30,
                            'view_window_days' => 1,
                        ],
                    ],
                ];
            }

            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/ad_groups', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('POST', $url, [$payload]);

            if (isset($response[0]['id'])) {
                return [
                    'success' => true,
                    'adgroup_id' => $response[0]['id'],
                    'data' => $response[0],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create ad group',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest createAdSet failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignExternalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a Pinterest Ad (Pin)
     *
     * @param string $adSetExternalId Parent ad group ID
     * @param array $data Ad data
     * @return array Response with ad ID or error
     */
    public function createAd(string $adSetExternalId, array $data): array
    {
        try {
            $payload = [
                'ad_account_id' => $this->advertiserId,
                'ad_group_id' => $adSetExternalId,
                'name' => $data['name'],
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'creative_type' => $data['creative_type'] ?? 'REGULAR',
            ];

            // Pin reference (existing organic pin)
            if (isset($data['pin_id'])) {
                $payload['pin_id'] = $data['pin_id'];
            }

            // Destination URL
            if (isset($data['destination_url'])) {
                $payload['destination_url'] = $data['destination_url'];
            }

            // Tracking URLs
            if (isset($data['tracking_urls'])) {
                $payload['tracking_urls'] = $data['tracking_urls'];
            }

            // Carousel cards (for carousel ads)
            if (isset($data['carousel_cards'])) {
                $payload['carousel_android_deep_links'] = $data['carousel_cards'];
            }

            // Quiz pin
            if (isset($data['quiz_pin_data'])) {
                $payload['quiz_pin_data'] = $data['quiz_pin_data'];
            }

            // Shopping
            if (isset($data['catalog_product_group_id'])) {
                $payload['catalog_product_group_id'] = $data['catalog_product_group_id'];
            }

            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/ads', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('POST', $url, [$payload]);

            if (isset($response[0]['id'])) {
                return [
                    'success' => true,
                    'ad_id' => $response[0]['id'],
                    'data' => $response[0],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create ad',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest createAd failed', [
                'error' => $e->getMessage(),
                'adgroup_id' => $adSetExternalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available campaign objectives
     */
    public function getAvailableObjectives(): array
    {
        return [
            'AWARENESS' => 'Brand Awareness',
            'VIDEO_VIEW' => 'Video Views',
            'CONSIDERATION' => 'Consideration (Clicks)',
            'CONVERSIONS' => 'Conversions',
            'CATALOG_SALES' => 'Catalog Sales',
        ];
    }

    /**
     * Get available placements
     */
    public function getAvailablePlacements(): array
    {
        return [
            'ALL' => 'All Placements',
            'BROWSE' => 'Home Feed',
            'SEARCH' => 'Search Results',
            'RELATED_PINS' => 'Related Pins',
        ];
    }

    /**
     * Get available ad formats
     */
    public function getAvailableAdFormats(): array
    {
        return [
            'REGULAR' => 'Standard Pin',
            'VIDEO' => 'Video Pin',
            'CAROUSEL' => 'Carousel',
            'SHOPPING' => 'Shopping Ad',
            'COLLECTIONS' => 'Collections',
            'IDEA' => 'Idea Pin',
            'QUIZ' => 'Quiz Pin',
        ];
    }

    /**
     * Get available bid strategies
     */
    public function getAvailableBidStrategies(): array
    {
        return [
            'AUTOMATIC_BID' => 'Automatic Bidding',
            'MAX_BID' => 'Maximum Bid',
            'TARGET_AVG' => 'Target Average',
        ];
    }

    /**
     * Get available optimization goals
     */
    public function getAvailableOptimizationGoals(): array
    {
        return [
            'IMPRESSION' => 'Impressions',
            'OUTBOUND_CLICK' => 'Outbound Clicks',
            'PIN_CLICK' => 'Pin Clicks',
            'SAVE' => 'Saves',
            'VIDEO_V50' => 'Video Views (50%)',
            'CONVERSION' => 'Conversions',
        ];
    }

    /**
     * Sync account data from Pinterest
     */
    public function syncAccount(): array
    {
        try {
            $url = $this->buildUrl('/ad_accounts/{ad_account_id}', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('GET', $url);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'account' => [
                        'id' => $response['id'],
                        'name' => $response['name'] ?? '',
                        'currency' => $response['currency'] ?? 'USD',
                        'country' => $response['country'] ?? '',
                        'owner_username' => $response['owner']['username'] ?? '',
                        'permissions' => $response['permissions'] ?? [],
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to sync account',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest syncAccount failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh OAuth access token
     */
    public function refreshAccessToken(): array
    {
        try {
            if (empty($this->integration->refresh_token)) {
                return [
                    'success' => false,
                    'error' => 'No refresh token available',
                ];
            }

            $refreshToken = decrypt($this->integration->refresh_token);

            $response = Http::withBasicAuth(
                config('services.pinterest.client_id'),
                config('services.pinterest.client_secret')
            )->asForm()->post('https://api.pinterest.com/v5/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if ($response->failed()) {
                throw new \Exception('Token refresh request failed: ' . $response->body());
            }

            $data = $response->json();

            if (isset($data['access_token'])) {
                $newAccessToken = $data['access_token'];
                $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
                $expiresIn = $data['expires_in'] ?? 3600;

                // Update integration with new encrypted tokens
                $this->integration->update([
                    'access_token' => encrypt($newAccessToken),
                    'refresh_token' => encrypt($newRefreshToken),
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'token_refreshed_at' => now(),
                ]);

                Log::info('Pinterest token refreshed successfully', [
                    'integration_id' => $this->integration->integration_id,
                    'expires_at' => now()->addSeconds($expiresIn),
                ]);

                return [
                    'success' => true,
                    'access_token' => $newAccessToken,
                    'expires_in' => $expiresIn,
                ];
            }

            return [
                'success' => false,
                'error' => $data['error_description'] ?? 'Failed to refresh token',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest refreshAccessToken failed', [
                'error' => $e->getMessage(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get interest categories for targeting
     */
    public function getInterestCategories(): array
    {
        try {
            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/targeting_analytics', [
                'ad_account_id' => $this->advertiserId,
            ]);

            // Pinterest doesn't have a direct interest category endpoint
            // We use targeting analytics to get available targeting options
            $response = $this->makeRequest('GET', $url, [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'targeting_types' => 'INTEREST',
            ]);

            return [
                'success' => true,
                'categories' => $response ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getInterestCategories failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get audiences (Actalike and custom)
     */
    public function getAudiences(): array
    {
        try {
            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/audiences', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('GET', $url);

            if (isset($response['items'])) {
                return [
                    'success' => true,
                    'audiences' => array_map(function ($audience) {
                        return [
                            'id' => $audience['id'],
                            'name' => $audience['name'],
                            'type' => $audience['audience_type'],
                            'size' => $audience['size'] ?? null,
                            'status' => $audience['status'],
                        ];
                    }, $response['items']),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch audiences',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getAudiences failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create an Actalike audience (Pinterest's lookalike)
     */
    public function createActalikeAudience(string $sourceAudienceId, array $data): array
    {
        try {
            $payload = [
                'ad_account_id' => $this->advertiserId,
                'name' => $data['name'],
                'audience_type' => 'ACTALIKE',
                'description' => $data['description'] ?? '',
                'rule' => [
                    'actalike_rule' => [
                        'seed_id' => [$sourceAudienceId],
                        'country' => $data['country'] ?? 'US',
                        'percentage' => $data['percentage'] ?? 10, // 1-10% of Pinterest users
                    ],
                ],
            ];

            $url = $this->buildUrl('/ad_accounts/{ad_account_id}/audiences', [
                'ad_account_id' => $this->advertiserId,
            ]);

            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'audience_id' => $response['id'],
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create Actalike audience',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest createActalikeAudience failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get product catalogs
     */
    public function getCatalogs(): array
    {
        try {
            $url = $this->buildUrl('/catalogs');

            $response = $this->makeRequest('GET', $url);

            if (isset($response['items'])) {
                return [
                    'success' => true,
                    'catalogs' => $response['items'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch catalogs',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getCatalogs failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper: Map objective to Pinterest format
     */
    protected function mapObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'AWARENESS', 'BRAND_AWARENESS' => 'AWARENESS',
            'VIDEO_VIEWS', 'VIDEO_VIEW' => 'VIDEO_VIEW',
            'CONSIDERATION', 'TRAFFIC', 'CLICKS' => 'CONSIDERATION',
            'CONVERSIONS', 'CONVERSION' => 'CONVERSIONS',
            'CATALOG_SALES', 'SHOPPING' => 'CATALOG_SALES',
            default => 'AWARENESS',
        };
    }

    /**
     * Helper: Map status to Pinterest format
     */
    protected function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE', 'ENABLED', 'ENABLE' => 'ACTIVE',
            'PAUSED', 'DISABLED', 'DISABLE' => 'PAUSED',
            'ARCHIVED', 'DELETED', 'DELETE' => 'ARCHIVED',
            default => 'PAUSED',
        };
    }

    /**
     * Helper: Map status from Pinterest to internal format
     */
    protected function mapStatusFromPlatform(string $platformStatus): string
    {
        return match (strtoupper($platformStatus)) {
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'ARCHIVED' => 'archived',
            default => strtolower($platformStatus),
        };
    }
}
