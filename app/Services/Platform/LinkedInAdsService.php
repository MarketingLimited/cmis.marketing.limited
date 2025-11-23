<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * LinkedIn Ads Service
 *
 * ⚠️ DEPRECATED: This service is deprecated as of 2025-11-23
 *
 * Please use App\Services\AdPlatforms\LinkedIn\LinkedInAdsPlatform instead.
 *
 * Reason for deprecation:
 * - Overlaps with LinkedInAdsPlatform functionality
 * - LinkedInAdsPlatform extends AbstractAdPlatform (better architecture)
 * - LinkedInAdsPlatform has RLS context support
 * - LinkedInAdsPlatform has proper token management
 * - LinkedInAdsPlatform has Lead Gen Forms support
 *
 * Migration guide:
 * - Replace LinkedInAdsService with LinkedInAdsPlatform
 * - Use Integration model instead of passing tokens manually
 * - Caching is handled by AbstractAdPlatform
 *
 * This class will be removed in version 2.0 (planned for 2026)
 *
 * @deprecated Since 2025-11-23, use LinkedInAdsPlatform instead
 */
class LinkedInAdsService
{
    private string $apiVersion = 'v2';
    private string $baseUrl = 'https://api.linkedin.com';

    /**
     * Check if LinkedIn Ads service is configured
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.linkedin.client_id'))
            && !empty(config('services.linkedin.client_secret'));
    }

    /**
     * Fetch campaigns from LinkedIn Ads account
     */
    public function fetchCampaigns(
        string $accountId,
        string $accessToken,
        int $start = 0,
        int $count = 50
    ): array {
        $cacheKey = "linkedin_ads_campaigns_{$accountId}_{$start}";

        return Cache::remember($cacheKey, 300, function () use ($accountId, $accessToken, $start, $count) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'LinkedIn-Version' => '202401'
                ])->get("{$this->baseUrl}/{$this->apiVersion}/adCampaignsV2", [
                    'q' => 'search',
                    'search' => "(account:(values:List(urn:li:sponsoredAccount:{$accountId})))",
                    'start' => $start,
                    'count' => $count
                ]);

                if (!$response->successful()) {
                    throw new \Exception('LinkedIn API error: ' . $response->body());
                }

                $data = $response->json();

                return [
                    'campaigns' => $this->transformCampaigns($data['elements'] ?? []),
                    'paging' => $data['paging'] ?? null
                ];
            } catch (\Exception $e) {
                Log::error('LinkedIn Ads fetch campaigns error', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get campaign details with performance metrics
     */
    public function getCampaignDetails(
        string $accountId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Get campaign basic info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202401'
            ])->get("{$this->baseUrl}/{$this->apiVersion}/adCampaignsV2/{$campaignId}");

            if (!$response->successful()) {
                throw new \Exception('Campaign not found');
            }

            $campaign = $response->json();

            // Get performance metrics
            $metrics = $this->getCampaignMetrics($accountId, $campaignId, $accessToken, $startDate, $endDate);

            return $this->transformCampaignDetails($campaign, $metrics);
        } catch (\Exception $e) {
            Log::error('LinkedIn Ads campaign details error', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch creatives for a campaign
     */
    public function fetchCreatives(
        string $accountId,
        string $campaignId,
        string $accessToken
    ): array {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202401'
            ])->get("{$this->baseUrl}/{$this->apiVersion}/adCreativesV2", [
                'q' => 'search',
                'search' => "(campaign:(values:List({$campaignId})))"
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch creatives');
            }

            $creatives = $response->json()['elements'] ?? [];

            return $this->transformCreatives($creatives);
        } catch (\Exception $e) {
            Log::error('LinkedIn Ads fetch creatives error', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new LinkedIn Ads campaign
     */
    public function createCampaign(
        string $accountId,
        string $accessToken,
        array $campaignData
    ): array {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202401',
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/{$this->apiVersion}/adCampaignsV2", [
                'account' => "urn:li:sponsoredAccount:{$accountId}",
                'name' => $campaignData['name'],
                'type' => $campaignData['type'] ?? 'SPONSORED_UPDATES',
                'costType' => $campaignData['cost_type'] ?? 'CPM',
                'objectiveType' => $campaignData['objective'],
                'status' => $campaignData['status'] ?? 'PAUSED',
                'dailyBudget' => [
                    'amount' => $campaignData['daily_budget'] ?? null,
                    'currencyCode' => $campaignData['currency'] ?? 'USD'
                ],
                'totalBudget' => [
                    'amount' => $campaignData['total_budget'] ?? null,
                    'currencyCode' => $campaignData['currency'] ?? 'USD'
                ],
                'runSchedule' => [
                    'start' => $campaignData['start_date'] ?? time() * 1000,
                    'end' => $campaignData['end_date'] ?? null
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('LinkedIn API error: ' . $response->body());
            }

            $data = $response->json();

            return [
                'success' => true,
                'campaign_id' => $data['id'] ?? null,
                'message' => 'Campaign created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn Ads create campaign error', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(
        string $accountId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $end = $endDate ?? Carbon::now()->format('Y-m-d');

        try {
            // Convert dates to LinkedIn timestamp format (milliseconds)
            $startTimestamp = Carbon::parse($start)->startOfDay()->timestamp * 1000;
            $endTimestamp = Carbon::parse($end)->endOfDay()->timestamp * 1000;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202401'
            ])->get("{$this->baseUrl}/{$this->apiVersion}/adAnalyticsV2", [
                'q' => 'analytics',
                'pivot' => 'CAMPAIGN',
                'campaigns[0]' => $campaignId,
                'dateRange' => "(start:(day:{$start}),end:(day:{$end}))",
                'timeGranularity' => 'DAILY',
                'fields' => implode(',', [
                    'impressions',
                    'clicks',
                    'costInLocalCurrency',
                    'externalWebsiteConversions',
                    'oneClickLeads',
                    'actionClicks',
                    'reactions',
                    'comments',
                    'shares',
                    'follows',
                    'landingPageClicks',
                    'videoViews'
                ])
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json()['elements'] ?? [];

            return $this->transformMetrics($data);
        } catch (\Exception $e) {
            Log::error('LinkedIn Ads metrics error', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Transform campaigns response to standard format
     */
    private function transformCampaigns(array $campaigns): array
    {
        return array_map(function ($campaign) {
            return [
                'id' => $campaign['id'] ?? null,
                'name' => $campaign['name'] ?? null,
                'status' => $campaign['status'] ?? null,
                'type' => $campaign['type'] ?? null,
                'objective' => $campaign['objectiveType'] ?? null,
                'cost_type' => $campaign['costType'] ?? null,
                'daily_budget' => $campaign['dailyBudget']['amount'] ?? null,
                'total_budget' => $campaign['totalBudget']['amount'] ?? null,
                'currency' => $campaign['dailyBudget']['currencyCode'] ?? 'USD',
                'created_at' => isset($campaign['createdAt']) ? date('Y-m-d H:i:s', $campaign['createdAt'] / 1000) : null,
                'modified_at' => isset($campaign['lastModifiedAt']) ? date('Y-m-d H:i:s', $campaign['lastModifiedAt'] / 1000) : null,
                'platform' => 'linkedin'
            ];
        }, $campaigns);
    }

    /**
     * Transform campaign details
     */
    private function transformCampaignDetails(array $campaign, array $metrics): array
    {
        return [
            'id' => $campaign['id'] ?? null,
            'name' => $campaign['name'] ?? null,
            'status' => $campaign['status'] ?? null,
            'type' => $campaign['type'] ?? null,
            'objective' => $campaign['objectiveType'] ?? null,
            'cost_type' => $campaign['costType'] ?? null,
            'daily_budget' => $campaign['dailyBudget']['amount'] ?? null,
            'total_budget' => $campaign['totalBudget']['amount'] ?? null,
            'currency' => $campaign['dailyBudget']['currencyCode'] ?? 'USD',
            'run_schedule' => [
                'start' => isset($campaign['runSchedule']['start']) ? date('Y-m-d H:i:s', $campaign['runSchedule']['start'] / 1000) : null,
                'end' => isset($campaign['runSchedule']['end']) ? date('Y-m-d H:i:s', $campaign['runSchedule']['end'] / 1000) : null
            ],
            'created_at' => isset($campaign['createdAt']) ? date('Y-m-d H:i:s', $campaign['createdAt'] / 1000) : null,
            'modified_at' => isset($campaign['lastModifiedAt']) ? date('Y-m-d H:i:s', $campaign['lastModifiedAt'] / 1000) : null,
            'metrics' => $metrics,
            'platform' => 'linkedin'
        ];
    }

    /**
     * Transform creatives response
     */
    private function transformCreatives(array $creatives): array
    {
        return array_map(function ($creative) {
            return [
                'id' => $creative['id'] ?? null,
                'campaign_id' => $creative['campaign'] ?? null,
                'status' => $creative['status'] ?? null,
                'type' => $creative['type'] ?? null,
                'content' => $creative['content'] ?? null,
                'created_at' => isset($creative['createdAt']) ? date('Y-m-d H:i:s', $creative['createdAt'] / 1000) : null
            ];
        }, $creatives);
    }

    /**
     * Transform metrics response
     */
    private function transformMetrics(array $metrics): array
    {
        return array_map(function ($metric) {
            $impressions = $metric['impressions'] ?? 0;
            $clicks = $metric['clicks'] ?? 0;
            $cost = $metric['costInLocalCurrency'] ?? 0;

            return [
                'date' => $metric['dateRange']['start']['day'] ?? null,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'cost' => $cost,
                'conversions' => $metric['externalWebsiteConversions'] ?? 0,
                'leads' => $metric['oneClickLeads'] ?? 0,
                'ctr' => $impressions > 0 ? ($clicks / $impressions) : 0,
                'cpc' => $clicks > 0 ? ($cost / $clicks) : 0,
                'cpm' => $impressions > 0 ? ($cost / $impressions * 1000) : 0,
                'engagement' => [
                    'reactions' => $metric['reactions'] ?? 0,
                    'comments' => $metric['comments'] ?? 0,
                    'shares' => $metric['shares'] ?? 0,
                    'follows' => $metric['follows'] ?? 0,
                    'video_views' => $metric['videoViews'] ?? 0
                ]
            ];
        }, $metrics);
    }

    /**
     * Clear cache for account
     */
    public function clearCache(string $accountId): void
    {
        // Clear all pages
        for ($start = 0; $start < 500; $start += 50) {
            Cache::forget("linkedin_ads_campaigns_{$accountId}_{$start}");
        }
    }
}
