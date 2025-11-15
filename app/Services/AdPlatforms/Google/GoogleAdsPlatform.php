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
    // EXTENSIONS / ASSETS
    // ==========================================

    /**
     * Add Sitelink Extensions
     */
    public function addSitelinkExtensions(string $campaignOrAdGroupId, array $sitelinks, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($sitelinks as $sitelink) {
                // First create the asset
                $assetResponse = $this->createSitelinkAsset($sitelink);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'SITELINK',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'sitelinks_added' => count($response['results']),
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
     * Create Sitelink Asset
     */
    protected function createSitelinkAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'SITELINK',
                'sitelinkAsset' => [
                    'linkText' => $data['link_text'],
                    'description1' => $data['description1'] ?? '',
                    'description2' => $data['description2'] ?? '',
                    'finalUrls' => [$data['final_url']],
                ],
            ];

            $payload = [
                'operations' => [
                    ['create' => $asset],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add Callout Extensions
     */
    public function addCalloutExtensions(string $campaignOrAdGroupId, array $callouts, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($callouts as $callout) {
                $assetResponse = $this->createCalloutAsset($callout);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'CALLOUT',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'callouts_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function createCalloutAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'CALLOUT',
                'calloutAsset' => [
                    'calloutText' => $data['text'],
                ],
            ];

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Structured Snippet Extensions
     */
    public function addStructuredSnippetExtensions(string $campaignOrAdGroupId, array $snippets, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($snippets as $snippet) {
                $assetResponse = $this->createStructuredSnippetAsset($snippet);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'STRUCTURED_SNIPPET',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'structured_snippets_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createStructuredSnippetAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'STRUCTURED_SNIPPET',
                'structuredSnippetAsset' => [
                    'header' => $data['header'],
                    'values' => $data['values'],
                ],
            ];

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Call Extensions
     */
    public function addCallExtensions(string $campaignOrAdGroupId, array $calls, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($calls as $call) {
                $assetResponse = $this->createCallAsset($call);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'CALL',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'call_extensions_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createCallAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'CALL',
                'callAsset' => [
                    'countryCode' => $data['country_code'],
                    'phoneNumber' => $data['phone_number'],
                    'callConversionReportingState' => $data['conversion_reporting'] ?? 'DISABLED',
                ],
            ];

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Price Extensions
     */
    public function addPriceExtensions(string $campaignOrAdGroupId, array $prices, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($prices as $price) {
                $assetResponse = $this->createPriceAsset($price);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'PRICE',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'price_extensions_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createPriceAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $priceOfferings = [];
            foreach ($data['offerings'] as $offering) {
                $priceOfferings[] = [
                    'header' => $offering['header'],
                    'description' => $offering['description'],
                    'price' => [
                        'amountMicros' => $offering['price'] * 1000000,
                        'currencyCode' => $data['currency'] ?? 'USD',
                    ],
                    'unit' => $offering['unit'] ?? 'PER_UNIT',
                    'finalUrls' => [$offering['final_url']],
                ];
            }

            $asset = [
                'type' => 'PRICE',
                'priceAsset' => [
                    'type' => $data['type'] ?? 'SERVICES',
                    'priceQualifier' => $data['qualifier'] ?? 'FROM',
                    'languageCode' => $data['language'] ?? 'en',
                    'priceOfferings' => $priceOfferings,
                ],
            ];

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Promotion Extensions
     */
    public function addPromotionExtensions(string $campaignOrAdGroupId, array $promotions, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($promotions as $promotion) {
                $assetResponse = $this->createPromotionAsset($promotion);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'PROMOTION',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'promotions_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createPromotionAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'PROMOTION',
                'promotionAsset' => [
                    'promotionTarget' => $data['target'],
                    'discountModifier' => $data['discount_modifier'] ?? 'UP_TO',
                    'languageCode' => $data['language'] ?? 'en',
                    'finalUrls' => [$data['final_url']],
                ],
            ];

            if (isset($data['percent_off'])) {
                $asset['promotionAsset']['percentOff'] = $data['percent_off'];
            } elseif (isset($data['money_amount_off'])) {
                $asset['promotionAsset']['moneyAmountOff'] = [
                    'amountMicros' => $data['money_amount_off'] * 1000000,
                    'currencyCode' => $data['currency'] ?? 'USD',
                ];
            }

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Image Extensions
     */
    public function addImageExtensions(string $campaignOrAdGroupId, array $images, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($images as $image) {
                $assetResponse = $this->createImageAsset($image);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'MARKETING_IMAGE',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'images_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createImageAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'IMAGE',
                'imageAsset' => [
                    'data' => base64_encode(file_get_contents($data['image_path'])),
                ],
                'name' => $data['name'] ?? 'Image ' . time(),
            ];

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Lead Form Extensions
     */
    public function addLeadFormExtensions(string $campaignOrAdGroupId, array $leadForms, string $level = 'campaign'): array
    {
        try {
            $url = $this->buildUrl($level === 'campaign'
                ? '/customers/{customer_id}/campaignAssets:mutate'
                : '/customers/{customer_id}/adGroupAssets:mutate'
            );

            $operations = [];
            foreach ($leadForms as $leadForm) {
                $assetResponse = $this->createLeadFormAsset($leadForm);
                if (!$assetResponse['success']) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        $level => "customers/{$this->customerId}/" . ($level === 'campaign' ? "campaigns/{$campaignOrAdGroupId}" : "adGroups/{$campaignOrAdGroupId}"),
                        'asset' => $assetResponse['resource_name'],
                        'fieldType' => 'LEAD_FORM',
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'lead_forms_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createLeadFormAsset(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/assets:mutate');

            $asset = [
                'type' => 'LEAD_FORM',
                'leadFormAsset' => [
                    'businessName' => $data['business_name'],
                    'callToActionType' => $data['cta_type'] ?? 'LEARN_MORE',
                    'callToActionDescription' => $data['cta_description'],
                    'headline' => $data['headline'],
                    'description' => $data['description'],
                    'privacyPolicyUrl' => $data['privacy_policy_url'],
                    'fields' => $data['fields'] ?? [], // e.g., ['FULL_NAME', 'EMAIL', 'PHONE_NUMBER']
                ],
            ];

            $payload = ['operations' => [['create' => $asset]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // AUDIENCE TARGETING
    // ==========================================

    /**
     * Add In-Market Audience Targeting
     */
    public function addInMarketAudience(string $adGroupExternalId, array $inMarketIds): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($inMarketIds as $audienceId) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'userInterest' => [
                            'userInterestCategory' => "userInterests/{$audienceId}",
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'audiences_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Affinity Audience Targeting
     */
    public function addAffinityAudience(string $adGroupExternalId, array $affinityIds): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($affinityIds as $audienceId) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'userInterest' => [
                            'userInterestCategory' => "userInterests/{$audienceId}",
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'audiences_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create Custom Audience
     */
    public function createCustomAudience(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/customAudiences:mutate');

            $audience = [
                'name' => $data['name'],
                'type' => 'AUTO',
                'description' => $data['description'] ?? '',
                'members' => [],
            ];

            // Add keywords
            if (isset($data['keywords'])) {
                foreach ($data['keywords'] as $keyword) {
                    $audience['members'][] = [
                        'memberType' => 'KEYWORD',
                        'keyword' => $keyword,
                    ];
                }
            }

            // Add URLs
            if (isset($data['urls'])) {
                foreach ($data['urls'] as $url) {
                    $audience['members'][] = [
                        'memberType' => 'URL',
                        'url' => $url,
                    ];
                }
            }

            // Add apps
            if (isset($data['apps'])) {
                foreach ($data['apps'] as $app) {
                    $audience['members'][] = [
                        'memberType' => 'APP',
                        'app' => $app,
                    ];
                }
            }

            $payload = [
                'operations' => [
                    ['create' => $audience],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
                'audience_id' => $this->extractAudienceId($response['results'][0]['resourceName']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Custom Audience to Ad Group
     */
    public function addCustomAudience(string $adGroupExternalId, array $customAudienceIds): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($customAudienceIds as $audienceId) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'customAudience' => [
                            'customAudience' => "customers/{$this->customerId}/customAudiences/{$audienceId}",
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'audiences_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create Remarketing List (User List)
     */
    public function createRemarketingList(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/userLists:mutate');

            $userList = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'membershipLifeSpan' => $data['membership_days'] ?? 30,
                'membershipStatus' => 'OPEN',
            ];

            // Different types of remarketing lists
            if (isset($data['rule_based'])) {
                $userList['ruleBasedUserList'] = [
                    'prepopulationStatus' => 'REQUESTED',
                    'flexibleRuleUserList' => [
                        'inclusiveRuleOperator' => 'AND',
                        'inclusiveOperands' => $data['rules'],
                    ],
                ];
            } elseif (isset($data['crm_based'])) {
                $userList['crmBasedUserList'] = [
                    'uploadKeyType' => $data['upload_key_type'] ?? 'CONTACT_INFO',
                    'dataSourceType' => 'FIRST_PARTY',
                ];
            }

            $payload = ['operations' => [['create' => $userList]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
                'user_list_id' => $this->extractUserListId($response['results'][0]['resourceName']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload Customer Match Data
     */
    public function uploadCustomerMatch(string $userListId, array $customers): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}:uploadUserData');

            $operations = [];
            foreach ($customers as $customer) {
                $userData = [];

                if (isset($customer['email'])) {
                    $userData['hashedEmail'] = hash('sha256', strtolower(trim($customer['email'])));
                }
                if (isset($customer['phone'])) {
                    $userData['hashedPhoneNumber'] = hash('sha256', preg_replace('/[^0-9]/', '', $customer['phone']));
                }
                if (isset($customer['first_name'])) {
                    $userData['addressInfo'] = [
                        'hashedFirstName' => hash('sha256', strtolower(trim($customer['first_name']))),
                        'hashedLastName' => hash('sha256', strtolower(trim($customer['last_name'] ?? ''))),
                        'countryCode' => $customer['country'] ?? '',
                        'zipCode' => $customer['zip'] ?? '',
                    ];
                }

                $operations[] = ['create' => $userData];
            }

            $payload = [
                'customerMatchUserListMetadata' => [
                    'userList' => "customers/{$this->customerId}/userLists/{$userListId}",
                ],
                'operations' => $operations,
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'uploaded_count' => count($operations),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Remarketing Audience to Ad Group
     */
    public function addRemarketingAudience(string $adGroupExternalId, array $userListIds): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($userListIds as $listId) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'userList' => [
                            'userList' => "customers/{$this->customerId}/userLists/{$listId}",
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'audiences_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // LOCATION & LANGUAGE TARGETING
    // ==========================================

    /**
     * Add Location Targeting (Campaign Level)
     */
    public function addLocationTargeting(string $campaignExternalId, array $locations): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($locations as $location) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'status' => 'ENABLED',
                        'location' => [
                            'geoTargetConstant' => "geoTargetConstants/{$location['geo_target_id']}",
                        ],
                        'bidModifier' => $location['bid_modifier'] ?? 1.0,
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'locations_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Proximity Targeting (Radius Targeting)
     */
    public function addProximityTargeting(string $campaignExternalId, array $proximities): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($proximities as $proximity) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'status' => 'ENABLED',
                        'proximity' => [
                            'geoPoint' => [
                                'longitudeInMicroDegrees' => $proximity['longitude'] * 1000000,
                                'latitudeInMicroDegrees' => $proximity['latitude'] * 1000000,
                            ],
                            'radius' => $proximity['radius'],
                            'radiusUnits' => $proximity['radius_unit'] ?? 'KILOMETERS',
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'proximities_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Language Targeting (Campaign Level)
     */
    public function addLanguageTargeting(string $campaignExternalId, array $languageIds): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($languageIds as $languageId) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'status' => 'ENABLED',
                        'language' => [
                            'languageConstant' => "languageConstants/{$languageId}",
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'languages_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // DEVICE & AD SCHEDULE TARGETING
    // ==========================================

    /**
     * Add Device Targeting with Bid Modifiers
     */
    public function addDeviceBidModifiers(string $campaignExternalId, array $devices): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($devices as $device) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'device' => [
                            'type' => $device['type'], // MOBILE, DESKTOP, TABLET
                        ],
                        'bidModifier' => $device['bid_modifier'],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'device_modifiers_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Ad Schedule (Day Parting)
     */
    public function addAdSchedule(string $campaignExternalId, array $schedules): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($schedules as $schedule) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'adSchedule' => [
                            'dayOfWeek' => $schedule['day'], // MONDAY, TUESDAY, etc.
                            'startHour' => $schedule['start_hour'],
                            'startMinute' => $schedule['start_minute'] ?? 'ZERO',
                            'endHour' => $schedule['end_hour'],
                            'endMinute' => $schedule['end_minute'] ?? 'ZERO',
                        ],
                        'bidModifier' => $schedule['bid_modifier'] ?? 1.0,
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'schedules_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // ADVANCED DEMOGRAPHICS
    // ==========================================

    /**
     * Add Parental Status Targeting
     */
    public function addParentalStatusTargeting(string $adGroupExternalId, array $parentalStatuses): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($parentalStatuses as $status) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'parentalStatus' => [
                            'type' => $status, // PARENT, NOT_A_PARENT, UNDETERMINED
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'parental_statuses_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add Household Income Targeting
     */
    public function addHouseholdIncomeTargeting(string $adGroupExternalId, array $incomeRanges): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($incomeRanges as $range) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'incomeRange' => [
                            'type' => $range, // INCOME_RANGE_0_50, INCOME_RANGE_50_60, etc.
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'income_ranges_added' => count($response['results']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // BIDDING STRATEGIES
    // ==========================================

    /**
     * Create Portfolio Bidding Strategy
     */
    public function createBiddingStrategy(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/biddingStrategies:mutate');

            $strategy = [
                'name' => $data['name'],
                'type' => $data['type'],
            ];

            // Configure based on strategy type
            switch ($data['type']) {
                case 'TARGET_CPA':
                    $strategy['targetCpa'] = [
                        'targetCpaMicros' => $data['target_cpa'] * 1000000,
                    ];
                    break;

                case 'TARGET_ROAS':
                    $strategy['targetRoas'] = [
                        'targetRoas' => $data['target_roas'],
                    ];
                    break;

                case 'MAXIMIZE_CONVERSIONS':
                    $strategy['maximizeConversions'] = [
                        'targetCpaMicros' => $data['target_cpa_micros'] ?? null,
                    ];
                    break;

                case 'MAXIMIZE_CONVERSION_VALUE':
                    $strategy['maximizeConversionValue'] = [
                        'targetRoas' => $data['target_roas'] ?? null,
                    ];
                    break;

                case 'TARGET_SPEND':
                    $strategy['targetSpend'] = [
                        'targetSpendMicros' => $data['target_spend'] * 1000000,
                        'cpcBidCeilingMicros' => $data['cpc_ceiling'] * 1000000 ?? null,
                    ];
                    break;

                case 'TARGET_IMPRESSION_SHARE':
                    $strategy['targetImpressionShare'] = [
                        'location' => $data['location'] ?? 'ABSOLUTE_TOP_OF_PAGE',
                        'locationFractionMicros' => $data['location_fraction'] * 1000000,
                        'cpcBidCeilingMicros' => $data['cpc_ceiling'] * 1000000 ?? null,
                    ];
                    break;
            }

            $payload = ['operations' => [['create' => $strategy]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
                'strategy_id' => $this->extractBiddingStrategyId($response['results'][0]['resourceName']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Assign Bidding Strategy to Campaign
     */
    public function assignBiddingStrategy(string $campaignExternalId, string $biddingStrategyId): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/campaigns:mutate');

            $payload = [
                'operations' => [
                    [
                        'update' => [
                            'resourceName' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                            'biddingStrategy' => "customers/{$this->customerId}/biddingStrategies/{$biddingStrategyId}",
                        ],
                        'updateMask' => 'biddingStrategy',
                    ],
                ],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // CONVERSION TRACKING
    // ==========================================

    /**
     * Create Conversion Action
     */
    public function createConversionAction(array $data): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}/conversionActions:mutate');

            $conversionAction = [
                'name' => $data['name'],
                'category' => $data['category'] ?? 'DEFAULT',
                'type' => $data['type'] ?? 'WEBPAGE',
                'status' => 'ENABLED',
                'valueSettings' => [
                    'defaultValue' => $data['default_value'] ?? 0,
                    'alwaysUseDefaultValue' => $data['always_use_default'] ?? false,
                ],
            ];

            // Attribution model
            if (isset($data['attribution_model'])) {
                $conversionAction['attributionModelSettings'] = [
                    'attributionModel' => $data['attribution_model'],
                    'dataSourceScope' => 'ACCOUNT',
                ];
            }

            // Click-through lookback window
            if (isset($data['click_through_lookback_window'])) {
                $conversionAction['clickThroughLookbackWindowDays'] = $data['click_through_lookback_window'];
            }

            // Tag snippets for webpage conversions
            if ($conversionAction['type'] === 'WEBPAGE') {
                $conversionAction['tagSnippets'] = [
                    [
                        'type' => 'WEBPAGE',
                        'pageFormat' => 'HTML',
                    ],
                ];
            }

            $payload = ['operations' => [['create' => $conversionAction]]];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'resource_name' => $response['results'][0]['resourceName'],
                'conversion_action_id' => $this->extractConversionActionId($response['results'][0]['resourceName']),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload Offline Conversions
     */
    public function uploadOfflineConversions(array $conversions): array
    {
        try {
            $url = $this->buildUrl('/customers/{customer_id}:uploadClickConversions');

            $operations = [];
            foreach ($conversions as $conversion) {
                $operations[] = [
                    'gclid' => $conversion['gclid'],
                    'conversionAction' => "customers/{$this->customerId}/conversionActions/{$conversion['conversion_action_id']}",
                    'conversionDateTime' => $conversion['conversion_time'],
                    'conversionValue' => $conversion['value'] ?? 0,
                    'currencyCode' => $conversion['currency'] ?? 'USD',
                ];
            }

            $payload = ['conversions' => $operations];
            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'conversions_uploaded' => count($operations),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Conversion Actions
     */
    public function getConversionActions(): array
    {
        try {
            $query = "
                SELECT
                    conversion_action.id,
                    conversion_action.name,
                    conversion_action.category,
                    conversion_action.status,
                    metrics.all_conversions,
                    metrics.all_conversions_value
                FROM conversion_action
                WHERE conversion_action.status = 'ENABLED'
            ";

            $response = $this->executeQuery($query);

            return [
                'success' => true,
                'conversion_actions' => $response,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
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
     * Extract audience ID from resource name
     */
    protected function extractAudienceId(string $resourceName): string
    {
        preg_match('/customAudiences\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract user list ID from resource name
     */
    protected function extractUserListId(string $resourceName): string
    {
        preg_match('/userLists\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract bidding strategy ID from resource name
     */
    protected function extractBiddingStrategyId(string $resourceName): string
    {
        preg_match('/biddingStrategies\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract conversion action ID from resource name
     */
    protected function extractConversionActionId(string $resourceName): string
    {
        preg_match('/conversionActions\/(\d+)/', $resourceName, $matches);
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
