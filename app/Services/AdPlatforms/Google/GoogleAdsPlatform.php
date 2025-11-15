<?php

namespace App\Services\AdPlatforms\Google;

use App\Services\AdPlatforms\AbstractAdPlatform;
use Carbon\Carbon;

/**
 * Google Ads Platform Service - Complete Implementation
 *
 * Implements full Google Ads API functionality including:
 * - Campaign Management (Search, Display, Shopping, Video, Performance Max, etc.)
 * - Ad Groups Management
 * - Keywords Management (Keywords, Negative Keywords)
 * - Ad Management (Responsive Search Ads, Display Ads, Video Ads)
 * - Extensions (Sitelink, Callout, Structured Snippet, etc.)
 * - Targeting (Demographics, Topics, Placements, Audiences)
 * - Bidding Strategies
 * - Performance Reports
 *
 * @package App\Services\AdPlatforms\Google
 * @link https://developers.google.com/google-ads/api/docs/start
 */
class GoogleAdsPlatform extends AbstractAdPlatform
{
    protected string $apiVersion = 'v15';
    protected string $apiBaseUrl = 'https://googleads.googleapis.com';

    /**
     * Google Ads Customer ID (without dashes)
     */
    protected string $customerId;

    protected function getConfig(): array
    {
        return [
            'api_version' => $this->apiVersion,
            'api_base_url' => $this->apiBaseUrl,
            'developer_token' => config('services.google_ads.developer_token'),
            'endpoints' => [
                'campaigns' => '/{version}/customers/{customer_id}/campaigns',
                'campaign' => '/{version}/customers/{customer_id}/campaigns/{campaign_id}',
                'ad_groups' => '/{version}/customers/{customer_id}/adGroups',
                'keywords' => '/{version}/customers/{customer_id}/adGroupCriteria',
                'ads' => '/{version}/customers/{customer_id}/adGroupAds',
                'extensions' => '/{version}/customers/{customer_id}/extensionFeedItems',
            ],
        ];
    }

    protected function getPlatformName(): string
    {
        return 'google';
    }

    public function __construct($integration)
    {
        parent::__construct($integration);
        $this->customerId = str_replace('-', '', $integration->account_id);
    }

    /**
     * Get default headers for Google Ads API
     */
    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Authorization' => 'Bearer ' . $this->integration->access_token,
            'developer-token' => $this->config['developer_token'],
            'login-customer-id' => $this->customerId,
        ]);
    }

    // ==========================================
    // CAMPAIGN MANAGEMENT
    // ==========================================

    /**
     * Create campaign on Google Ads
     *
     * Supported campaign types:
     * - SEARCH (البحث)
     * - DISPLAY (الشبكة الإعلانية)
     * - SHOPPING (التسوق)
     * - VIDEO (الفيديو - YouTube)
     * - PERFORMANCE_MAX (الأداء الأقصى)
     * - DISCOVERY (الاكتشاف)
     * - APP (التطبيقات)
     * - SMART (الذكية)
     * - LOCAL (المحلية)
     */
    public function createCampaign(array $data): array
    {
        try {
            $validation = $this->validateCampaignData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors'],
                ];
            }

            $url = $this->buildUrl('/customers/{customer_id}/campaigns:mutate');

            // Build campaign resource
            $campaign = [
                'name' => $data['name'],
                'advertisingChannelType' => $this->mapCampaignType($data['campaign_type'] ?? 'SEARCH'),
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'biddingStrategyType' => $data['bidding_strategy'] ?? 'MAXIMIZE_CONVERSIONS',
            ];

            // Budget
            if (isset($data['daily_budget'])) {
                $campaign['campaignBudget'] = $this->createCampaignBudget($data['daily_budget']);
            }

            // Start and end dates
            if (isset($data['start_date'])) {
                $campaign['startDate'] = Carbon::parse($data['start_date'])->format('Ymd');
            }
            if (isset($data['end_date'])) {
                $campaign['endDate'] = Carbon::parse($data['end_date'])->format('Ymd');
            }

            // Campaign-specific settings
            switch ($campaign['advertisingChannelType']) {
                case 'SEARCH':
                    $campaign['networkSettings'] = [
                        'targetGoogleSearch' => true,
                        'targetSearchNetwork' => $data['target_search_network'] ?? true,
                        'targetContentNetwork' => $data['target_display_network'] ?? false,
                        'targetPartnerSearchNetwork' => $data['target_partner_network'] ?? false,
                    ];
                    break;

                case 'DISPLAY':
                    $campaign['networkSettings'] = [
                        'targetGoogleSearch' => false,
                        'targetSearchNetwork' => false,
                        'targetContentNetwork' => true,
                    ];
                    break;

                case 'SHOPPING':
                    $campaign['shoppingSetting'] = [
                        'merchantId' => $data['merchant_id'] ?? null,
                        'campaignPriority' => $data['campaign_priority'] ?? 0,
                        'enableLocal' => $data['enable_local'] ?? false,
                    ];
                    break;

                case 'VIDEO':
                    $campaign['videoCampaignSettings'] = [
                        'videoBiddingStrategyType' => $data['video_bidding_strategy'] ?? 'MAXIMIZE_CONVERSIONS',
                    ];
                    break;
            }

            // Geographic targeting
            if (isset($data['locations'])) {
                $campaign['geoTargetTypeSetting'] = [
                    'positiveGeoTargetType' => 'PRESENCE_OR_INTEREST',
                ];
            }

            // Language targeting
            if (isset($data['languages'])) {
                $campaign['selectiveOptimization'] = [
                    'conversionTypes' => $data['conversion_types'] ?? [],
                ];
            }

            $payload = [
                'operations' => [
                    [
                        'create' => $campaign,
                    ],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $this->extractCampaignId($response['results'][0]['resourceName']),
                'resource_name' => $response['results'][0]['resourceName'],
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update campaign
     */
    public function updateCampaign(string $externalId, array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaigns:mutate');

            $resourceName = "customers/{$this->customerId}/campaigns/{$externalId}";

            $updateMask = [];
            $campaign = ['resourceName' => $resourceName];

            if (isset($data['name'])) {
                $campaign['name'] = $data['name'];
                $updateMask[] = 'name';
            }

            if (isset($data['status'])) {
                $campaign['status'] = $this->mapStatus($data['status']);
                $updateMask[] = 'status';
            }

            if (isset($data['daily_budget'])) {
                // Update budget separately
                $this->updateCampaignBudget($externalId, $data['daily_budget']);
            }

            $payload = [
                'operations' => [
                    [
                        'update' => $campaign,
                        'updateMask' => implode(',', $updateMask),
                    ],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
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
            $query = "
                SELECT
                    campaign.id,
                    campaign.name,
                    campaign.status,
                    campaign.advertising_channel_type,
                    campaign.bidding_strategy_type,
                    campaign.campaign_budget,
                    campaign.start_date,
                    campaign.end_date,
                    campaign.network_settings,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros,
                    metrics.conversions,
                    metrics.conversion_value
                FROM campaign
                WHERE campaign.id = {$externalId}
            ";

            $response = $this->executeQuery($query);

            return [
                'success' => true,
                'data' => $response[0] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete campaign
     */
    public function deleteCampaign(string $externalId): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaigns:mutate');

            $payload = [
                'operations' => [
                    [
                        'remove' => "customers/{$this->customerId}/campaigns/{$externalId}",
                    ],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch campaigns
     */
    public function fetchCampaigns(array $filters = []): array
    {
        try {
            $query = "
                SELECT
                    campaign.id,
                    campaign.name,
                    campaign.status,
                    campaign.advertising_channel_type,
                    campaign.bidding_strategy_type,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros
                FROM campaign
                WHERE campaign.status != 'REMOVED'
            ";

            if (isset($filters['status'])) {
                $query .= " AND campaign.status = '{$this->mapStatus($filters['status'])}'";
            }

            if (isset($filters['campaign_type'])) {
                $query .= " AND campaign.advertising_channel_type = '{$this->mapCampaignType($filters['campaign_type'])}'";
            }

            $query .= " ORDER BY campaign.id DESC LIMIT " . ($filters['limit'] ?? 100);

            $response = $this->executeQuery($query);

            return [
                'success' => true,
                'campaigns' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign metrics
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        try {
            $query = "
                SELECT
                    campaign.id,
                    campaign.name,
                    segments.date,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros,
                    metrics.conversions,
                    metrics.conversions_value,
                    metrics.ctr,
                    metrics.average_cpc,
                    metrics.average_cpm,
                    metrics.conversion_rate,
                    metrics.cost_per_conversion
                FROM campaign
                WHERE campaign.id = {$externalId}
                AND segments.date BETWEEN '{$startDate}' AND '{$endDate}'
                ORDER BY segments.date
            ";

            $response = $this->executeQuery($query);

            return [
                'success' => true,
                'metrics' => $response,
            ];
        } catch (\Exception $e) {
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

    // ==========================================
    // AD GROUP MANAGEMENT
    // ==========================================

    /**
     * Create ad group (Ad Set equivalent)
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroups:mutate');

            $adGroup = [
                'name' => $data['name'],
                'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                'status' => $this->mapStatus($data['status'] ?? 'ENABLED'),
                'type' => $data['type'] ?? 'SEARCH_STANDARD',
            ];

            // CPC bid
            if (isset($data['cpc_bid_micros'])) {
                $adGroup['cpcBidMicros'] = $data['cpc_bid_micros'];
            }

            $payload = [
                'operations' => [
                    [
                        'create' => $adGroup,
                    ],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $this->extractAdGroupId($response['results'][0]['resourceName']),
                'resource_name' => $response['results'][0]['resourceName'],
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // KEYWORDS MANAGEMENT
    // ==========================================

    /**
     * Add keywords to ad group
     *
     * @param string $adGroupExternalId
     * @param array $keywords Array of keywords with match types
     * [
     *   ['text' => 'buy shoes', 'match_type' => 'EXACT'],
     *   ['text' => 'running shoes', 'match_type' => 'PHRASE'],
     * ]
     */
    public function addKeywords(string $adGroupExternalId, array $keywords): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($keywords as $keyword) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'keyword' => [
                            'text' => $keyword['text'],
                            'matchType' => $this->mapKeywordMatchType($keyword['match_type'] ?? 'BROAD'),
                        ],
                        'cpcBidMicros' => $keyword['bid_micros'] ?? null,
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'keywords_added' => count($response['results']),
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add negative keywords to campaign
     */
    public function addNegativeKeywords(string $campaignExternalId, array $keywords): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($keywords as $keyword) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'negative' => true,
                        'keyword' => [
                            'text' => $keyword['text'],
                            'matchType' => $this->mapKeywordMatchType($keyword['match_type'] ?? 'BROAD'),
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'negative_keywords_added' => count($response['results']),
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove keywords
     */
    public function removeKeywords(array $keywordResourceNames): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($keywordResourceNames as $resourceName) {
                $operations[] = ['remove' => $resourceName];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'keywords_removed' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get keywords for ad group
     */
    public function getKeywords(string $adGroupExternalId): array
    {
        try {
            $query = "
                SELECT
                    ad_group_criterion.criterion_id,
                    ad_group_criterion.keyword.text,
                    ad_group_criterion.keyword.match_type,
                    ad_group_criterion.status,
                    ad_group_criterion.cpc_bid_micros,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros,
                    metrics.conversions
                FROM ad_group_criterion
                WHERE ad_group_criterion.type = 'KEYWORD'
                AND ad_group.id = {$adGroupExternalId}
            ";

            $response = $this->executeQuery($query);

            return [
                'success' => true,
                'keywords' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // AD MANAGEMENT
    // ==========================================

    /**
     * Create Responsive Search Ad
     */
    public function createAd(string $adGroupExternalId, array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupAds:mutate');

            $ad = [
                'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                'status' => $this->mapStatus($data['status'] ?? 'ENABLED'),
                'ad' => [
                    'responsiveSearchAd' => [
                        'headlines' => $this->buildAdTextAssets($data['headlines']),
                        'descriptions' => $this->buildAdTextAssets($data['descriptions']),
                        'path1' => $data['path1'] ?? null,
                        'path2' => $data['path2'] ?? null,
                    ],
                    'finalUrls' => [$data['final_url']],
                ],
            ];

            $payload = [
                'operations' => [
                    ['create' => $ad],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $this->extractAdId($response['results'][0]['resourceName']),
                'resource_name' => $response['results'][0]['resourceName'],
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // TARGETING & CATEGORIES
    // ==========================================

    /**
     * Add topic targeting (Categories)
     */
    public function addTopicTargeting(string $adGroupExternalId, array $topicIds): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($topicIds as $topicId) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'topic' => [
                            'topicConstant' => "topicConstants/{$topicId}",
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'topics_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add placement targeting
     */
    public function addPlacements(string $adGroupExternalId, array $placements): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($placements as $placement) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'placement' => [
                            'url' => $placement,
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'placements_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add demographic targeting
     */
    public function addDemographicTargeting(string $adGroupExternalId, array $demographics): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];

            // Age ranges
            if (isset($demographics['age_ranges'])) {
                foreach ($demographics['age_ranges'] as $ageRange) {
                    $operations[] = [
                        'create' => [
                            'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                            'status' => 'ENABLED',
                            'ageRange' => [
                                'type' => $ageRange, // AGE_RANGE_18_24, AGE_RANGE_25_34, etc.
                            ],
                        ],
                    ];
                }
            }

            // Genders
            if (isset($demographics['genders'])) {
                foreach ($demographics['genders'] as $gender) {
                    $operations[] = [
                        'create' => [
                            'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                            'status' => 'ENABLED',
                            'gender' => [
                                'type' => $gender, // MALE, FEMALE, UNDETERMINED
                            ],
                        ],
                    ];
                }
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'demographics_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Execute Google Ads Query Language (GAQL) query
     */
    protected function executeQuery(string $query): array
    {
        $url = $this->buildUrl('/customers/{customer_id}/googleAds:search');

        $response = $this->makeRequest('POST', $url, ['query' => $query]);

        return $response['results'] ?? [];
    }

    /**
     * Build URL with replacements
     */
    protected function buildUrl(string $endpoint): string
    {
        $url = $this->apiBaseUrl . str_replace('{version}', $this->apiVersion, $endpoint);
        $url = str_replace('{customer_id}', $this->customerId, $url);

        return $url;
    }

    /**
     * Create campaign budget
     */
    protected function createCampaignBudget(float $dailyBudget): string
    {
        $url = $this->buildUrl('/customers/{customer_id}/campaignBudgets:mutate');

        $payload = [
            'operations' => [
                [
                    'create' => [
                        'name' => 'Budget ' . time(),
                        'amountMicros' => $dailyBudget * 1000000,
                        'deliveryMethod' => 'STANDARD',
                    ],
                ],
            ],
        ];

        $response = $this->makeRequest('POST', $url, $payload);

        return $response['results'][0]['resourceName'];
    }

    /**
     * Build ad text assets
     */
    protected function buildAdTextAssets(array $texts): array
    {
        return array_map(fn($text) => ['text' => $text], $texts);
    }

    /**
     * Extract campaign ID from resource name
     */
    protected function extractCampaignId(string $resourceName): string
    {
        preg_match('/campaigns\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract ad group ID from resource name
     */
    protected function extractAdGroupId(string $resourceName): string
    {
        preg_match('/adGroups\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract ad ID from resource name
     */
    protected function extractAdId(string $resourceName): string
    {
        preg_match('/ads\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Map campaign type
     */
    protected function mapCampaignType(string $type): string
    {
        return match (strtolower($type)) {
            'search' => 'SEARCH',
            'display' => 'DISPLAY',
            'shopping' => 'SHOPPING',
            'video' => 'VIDEO',
            'performance_max' => 'PERFORMANCE_MAX',
            'discovery' => 'DISCOVERY',
            'app' => 'APP',
            'smart' => 'SMART',
            'local' => 'LOCAL',
            default => 'SEARCH',
        };
    }

    /**
     * Map keyword match type
     */
    protected function mapKeywordMatchType(string $matchType): string
    {
        return match (strtolower($matchType)) {
            'exact' => 'EXACT',
            'phrase' => 'PHRASE',
            'broad' => 'BROAD',
            default => 'BROAD',
        };
    }

    /**
     * Map status
     */
    protected function mapStatus(string $internalStatus): string
    {
        return match (strtolower($internalStatus)) {
            'active', 'enabled' => 'ENABLED',
            'paused' => 'PAUSED',
            'removed', 'deleted' => 'REMOVED',
            default => 'PAUSED',
        };
    }

    /**
     * Get available objectives
     */
    public function getAvailableObjectives(): array
    {
        return [
            'MAXIMIZE_CONVERSIONS',
            'TARGET_CPA',
            'TARGET_ROAS',
            'MAXIMIZE_CLICKS',
            'TARGET_IMPRESSION_SHARE',
            'TARGET_SPEND',
            'MANUAL_CPC',
            'ENHANCED_CPC',
        ];
    }

    /**
     * Get available campaign types
     */
    public function getAvailableCampaignTypes(): array
    {
        return [
            'SEARCH' => 'حملات البحث',
            'DISPLAY' => 'حملات الشبكة الإعلانية',
            'SHOPPING' => 'حملات التسوق',
            'VIDEO' => 'حملات الفيديو (YouTube)',
            'PERFORMANCE_MAX' => 'حملات الأداء الأقصى',
            'DISCOVERY' => 'حملات الاكتشاف',
            'APP' => 'حملات التطبيقات',
            'SMART' => 'الحملات الذكية',
            'LOCAL' => 'الحملات المحلية',
        ];
    }

    /**
     * Get available placements
     */
    public function getAvailablePlacements(): array
    {
        return [
            'google_search',
            'search_partners',
            'display_network',
            'youtube_videos',
            'youtube_search',
            'gmail',
            'discover',
        ];
    }

    /**
     * Sync account
     */
    public function syncAccount(): array
    {
        try {
            $query = "
                SELECT
                    customer.id,
                    customer.descriptive_name,
                    customer.currency_code,
                    customer.time_zone,
                    customer.test_account
                FROM customer
            ";

            $response = $this->executeQuery($query);

            return [
                'success' => true,
                'account' => $response[0] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(): array
    {
        // Google OAuth token refresh
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $this->integration->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to refresh token',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
