<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleAdsService
{
    private string $apiVersion = 'v17';
    private string $baseUrl = 'https://googleads.googleapis.com';

    /**
     * Check if Google Ads service is configured
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.google.ads_developer_token'))
            && !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'));
    }

    /**
     * Fetch campaigns from Google Ads account
     */
    public function fetchCampaigns(
        string $customerId,
        string $accessToken,
        ?string $refreshToken = null,
        int $limit = 50
    ): array {
        $cacheKey = "google_ads_campaigns_{$customerId}";

        return Cache::remember($cacheKey, 300, function () use ($customerId, $accessToken, $limit) {
            try {
                $query = "
                    SELECT
                        campaign.id,
                        campaign.name,
                        campaign.status,
                        campaign.advertising_channel_type,
                        campaign.start_date,
                        campaign.end_date,
                        campaign.campaign_budget,
                        metrics.impressions,
                        metrics.clicks,
                        metrics.cost_micros,
                        metrics.conversions,
                        metrics.ctr,
                        metrics.average_cpc
                    FROM campaign
                    WHERE campaign.status != 'REMOVED'
                    ORDER BY metrics.impressions DESC
                    LIMIT {$limit}
                ";

                $response = $this->searchStream($customerId, $accessToken, $query);

                if (!$response) {
                    return [];
                }

                return $this->transformCampaigns($response);
            } catch (\Exception $e) {
                Log::error('Google Ads fetch campaigns error', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to fetch Google Ads campaigns: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get campaign details with performance metrics
     */
    public function getCampaignDetails(
        string $customerId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $end = $endDate ?? Carbon::now()->format('Y-m-d');

        $query = "
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.advertising_channel_type,
                campaign.start_date,
                campaign.end_date,
                campaign.optimization_score,
                campaign_budget.amount_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.conversions_value,
                metrics.ctr,
                metrics.average_cpc,
                metrics.cost_per_conversion
            FROM campaign
            WHERE campaign.id = {$campaignId}
            AND segments.date BETWEEN '{$start}' AND '{$end}'
        ";

        $response = $this->searchStream($customerId, $accessToken, $query);

        if (!$response || empty($response)) {
            throw new \Exception('Campaign not found');
        }

        return $this->transformCampaignDetails($response[0]);
    }

    /**
     * Fetch ad groups for a campaign
     */
    public function fetchAdGroups(
        string $customerId,
        string $campaignId,
        string $accessToken
    ): array {
        $query = "
            SELECT
                ad_group.id,
                ad_group.name,
                ad_group.status,
                ad_group.type,
                ad_group.cpc_bid_micros,
                campaign.id,
                campaign.name,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.ctr
            FROM ad_group
            WHERE campaign.id = {$campaignId}
            AND ad_group.status != 'REMOVED'
        ";

        $response = $this->searchStream($customerId, $accessToken, $query);

        return $this->transformAdGroups($response);
    }

    /**
     * Fetch ads for an ad group
     */
    public function fetchAds(
        string $customerId,
        string $adGroupId,
        string $accessToken
    ): array {
        $query = "
            SELECT
                ad_group_ad.ad.id,
                ad_group_ad.ad.name,
                ad_group_ad.status,
                ad_group_ad.ad.type,
                ad_group_ad.ad.final_urls,
                ad_group_ad.ad.responsive_search_ad.headlines,
                ad_group_ad.ad.responsive_search_ad.descriptions,
                ad_group.id,
                ad_group.name,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions
            FROM ad_group_ad
            WHERE ad_group.id = {$adGroupId}
            AND ad_group_ad.status != 'REMOVED'
        ";

        $response = $this->searchStream($customerId, $accessToken, $query);

        return $this->transformAds($response);
    }

    /**
     * Create a new Google Ads campaign
     */
    public function createCampaign(
        string $customerId,
        string $accessToken,
        array $campaignData
    ): array {
        try {
            $operations = [
                [
                    'create' => [
                        'name' => $campaignData['name'],
                        'status' => $campaignData['status'] ?? 'PAUSED',
                        'advertisingChannelType' => $campaignData['channel_type'] ?? 'SEARCH',
                        'biddingStrategyType' => $campaignData['bidding_strategy'] ?? 'MAXIMIZE_CLICKS',
                        'campaignBudget' => $campaignData['budget_resource_name'],
                        'networkSettings' => [
                            'targetGoogleSearch' => true,
                            'targetSearchNetwork' => true,
                            'targetContentNetwork' => false,
                            'targetPartnerSearchNetwork' => false
                        ]
                    ]
                ]
            ];

            $response = $this->mutate($customerId, $accessToken, 'CampaignService', $operations);

            return [
                'success' => true,
                'campaign_id' => $response['results'][0]['resourceName'] ?? null,
                'message' => 'Campaign created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Google Ads create campaign error', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to create Google Ads campaign: ' . $e->getMessage());
        }
    }

    /**
     * Create campaign budget
     */
    public function createCampaignBudget(
        string $customerId,
        string $accessToken,
        string $budgetName,
        int $amountMicros,
        string $deliveryMethod = 'STANDARD'
    ): string {
        $operations = [
            [
                'create' => [
                    'name' => $budgetName,
                    'amountMicros' => $amountMicros,
                    'deliveryMethod' => $deliveryMethod
                ]
            ]
        ];

        $response = $this->mutate($customerId, $accessToken, 'CampaignBudgetService', $operations);

        return $response['results'][0]['resourceName'];
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(
        string $customerId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $end = $endDate ?? Carbon::now()->format('Y-m-d');

        $query = "
            SELECT
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.conversions_value,
                metrics.ctr,
                metrics.average_cpc,
                metrics.cost_per_conversion
            FROM campaign
            WHERE campaign.id = {$campaignId}
            AND segments.date BETWEEN '{$start}' AND '{$end}'
            ORDER BY segments.date ASC
        ";

        $response = $this->searchStream($customerId, $accessToken, $query);

        return $this->transformMetrics($response);
    }

    /**
     * Execute Google Ads API search stream request
     */
    private function searchStream(string $customerId, string $accessToken, string $query): array
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/customers/{$customerId}/googleAds:searchStream";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'developer-token' => config('services.google.ads_developer_token'),
            'login-customer-id' => $this->getLoginCustomerId($customerId),
            'Content-Type' => 'application/json'
        ])->post($url, [
            'query' => $query
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            Log::error('Google Ads API error', [
                'status' => $response->status(),
                'error' => $error
            ]);
            throw new \Exception('Google Ads API error: ' . ($error['error']['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Execute Google Ads API mutate request
     */
    private function mutate(
        string $customerId,
        string $accessToken,
        string $service,
        array $operations
    ): array {
        $url = "{$this->baseUrl}/{$this->apiVersion}/customers/{$customerId}/{$service}:mutate";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'developer-token' => config('services.google.ads_developer_token'),
            'login-customer-id' => $this->getLoginCustomerId($customerId),
            'Content-Type' => 'application/json'
        ])->post($url, [
            'operations' => $operations
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            Log::error('Google Ads mutate error', [
                'service' => $service,
                'status' => $response->status(),
                'error' => $error
            ]);
            throw new \Exception('Google Ads mutate error: ' . ($error['error']['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Transform campaigns response to standard format
     */
    private function transformCampaigns(array $campaigns): array
    {
        return array_map(function ($campaign) {
            return [
                'id' => $campaign['campaign']['id'] ?? null,
                'name' => $campaign['campaign']['name'] ?? null,
                'status' => $campaign['campaign']['status'] ?? null,
                'channel_type' => $campaign['campaign']['advertisingChannelType'] ?? null,
                'start_date' => $campaign['campaign']['startDate'] ?? null,
                'end_date' => $campaign['campaign']['endDate'] ?? null,
                'metrics' => [
                    'impressions' => $campaign['metrics']['impressions'] ?? 0,
                    'clicks' => $campaign['metrics']['clicks'] ?? 0,
                    'cost' => ($campaign['metrics']['costMicros'] ?? 0) / 1000000,
                    'conversions' => $campaign['metrics']['conversions'] ?? 0,
                    'ctr' => $campaign['metrics']['ctr'] ?? 0,
                    'average_cpc' => ($campaign['metrics']['averageCpc'] ?? 0) / 1000000
                ],
                'platform' => 'google_ads'
            ];
        }, $campaigns);
    }

    /**
     * Transform campaign details
     */
    private function transformCampaignDetails(array $campaign): array
    {
        return [
            'id' => $campaign['campaign']['id'] ?? null,
            'name' => $campaign['campaign']['name'] ?? null,
            'status' => $campaign['campaign']['status'] ?? null,
            'channel_type' => $campaign['campaign']['advertisingChannelType'] ?? null,
            'start_date' => $campaign['campaign']['startDate'] ?? null,
            'end_date' => $campaign['campaign']['endDate'] ?? null,
            'optimization_score' => $campaign['campaign']['optimizationScore'] ?? null,
            'budget' => [
                'amount' => ($campaign['campaignBudget']['amountMicros'] ?? 0) / 1000000
            ],
            'metrics' => [
                'impressions' => $campaign['metrics']['impressions'] ?? 0,
                'clicks' => $campaign['metrics']['clicks'] ?? 0,
                'cost' => ($campaign['metrics']['costMicros'] ?? 0) / 1000000,
                'conversions' => $campaign['metrics']['conversions'] ?? 0,
                'conversions_value' => $campaign['metrics']['conversionsValue'] ?? 0,
                'ctr' => $campaign['metrics']['ctr'] ?? 0,
                'average_cpc' => ($campaign['metrics']['averageCpc'] ?? 0) / 1000000,
                'cost_per_conversion' => ($campaign['metrics']['costPerConversion'] ?? 0) / 1000000
            ],
            'platform' => 'google_ads'
        ];
    }

    /**
     * Transform ad groups response
     */
    private function transformAdGroups(array $adGroups): array
    {
        return array_map(function ($adGroup) {
            return [
                'id' => $adGroup['adGroup']['id'] ?? null,
                'name' => $adGroup['adGroup']['name'] ?? null,
                'status' => $adGroup['adGroup']['status'] ?? null,
                'type' => $adGroup['adGroup']['type'] ?? null,
                'cpc_bid' => ($adGroup['adGroup']['cpcBidMicros'] ?? 0) / 1000000,
                'campaign_id' => $adGroup['campaign']['id'] ?? null,
                'campaign_name' => $adGroup['campaign']['name'] ?? null,
                'metrics' => [
                    'impressions' => $adGroup['metrics']['impressions'] ?? 0,
                    'clicks' => $adGroup['metrics']['clicks'] ?? 0,
                    'cost' => ($adGroup['metrics']['costMicros'] ?? 0) / 1000000,
                    'ctr' => $adGroup['metrics']['ctr'] ?? 0
                ]
            ];
        }, $adGroups);
    }

    /**
     * Transform ads response
     */
    private function transformAds(array $ads): array
    {
        return array_map(function ($ad) {
            return [
                'id' => $ad['adGroupAd']['ad']['id'] ?? null,
                'name' => $ad['adGroupAd']['ad']['name'] ?? null,
                'status' => $ad['adGroupAd']['status'] ?? null,
                'type' => $ad['adGroupAd']['ad']['type'] ?? null,
                'final_urls' => $ad['adGroupAd']['ad']['finalUrls'] ?? [],
                'headlines' => $ad['adGroupAd']['ad']['responsiveSearchAd']['headlines'] ?? [],
                'descriptions' => $ad['adGroupAd']['ad']['responsiveSearchAd']['descriptions'] ?? [],
                'ad_group_id' => $ad['adGroup']['id'] ?? null,
                'ad_group_name' => $ad['adGroup']['name'] ?? null,
                'metrics' => [
                    'impressions' => $ad['metrics']['impressions'] ?? 0,
                    'clicks' => $ad['metrics']['clicks'] ?? 0,
                    'cost' => ($ad['metrics']['costMicros'] ?? 0) / 1000000,
                    'conversions' => $ad['metrics']['conversions'] ?? 0
                ]
            ];
        }, $ads);
    }

    /**
     * Transform metrics response
     */
    private function transformMetrics(array $metrics): array
    {
        return array_map(function ($metric) {
            return [
                'date' => $metric['segments']['date'] ?? null,
                'impressions' => $metric['metrics']['impressions'] ?? 0,
                'clicks' => $metric['metrics']['clicks'] ?? 0,
                'cost' => ($metric['metrics']['costMicros'] ?? 0) / 1000000,
                'conversions' => $metric['metrics']['conversions'] ?? 0,
                'conversions_value' => $metric['metrics']['conversionsValue'] ?? 0,
                'ctr' => $metric['metrics']['ctr'] ?? 0,
                'average_cpc' => ($metric['metrics']['averageCpc'] ?? 0) / 1000000,
                'cost_per_conversion' => ($metric['metrics']['costPerConversion'] ?? 0) / 1000000
            ];
        }, $metrics);
    }

    /**
     * Get login customer ID (manager account if available)
     */
    private function getLoginCustomerId(string $customerId): string
    {
        // If using a manager account, return manager account ID
        // Otherwise return the customer ID itself
        return config('services.google.ads_login_customer_id', $customerId);
    }

    /**
     * Clear cache for customer
     */
    public function clearCache(string $customerId): void
    {
        Cache::forget("google_ads_campaigns_{$customerId}");
    }
}
