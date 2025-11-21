<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TikTokAdsService
{
    private string $apiVersion = 'v1.3';
    private string $baseUrl = 'https://business-api.tiktok.com/open_api';

    /**
     * Check if TikTok Ads service is configured
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.tiktok.app_id'))
            && !empty(config('services.tiktok.app_secret'));
    }

    /**
     * Fetch campaigns from TikTok Ads account
     */
    public function fetchCampaigns(
        string $advertiserId,
        string $accessToken,
        int $page = 1,
        int $pageSize = 50
    ): array {
        $cacheKey = "tiktok_ads_campaigns_{$advertiserId}_{$page}";

        return Cache::remember($cacheKey, 300, function () use ($advertiserId, $accessToken, $page, $pageSize) {
            try {
                $response = Http::withHeaders([
                    'Access-Token' => $accessToken
                ])->get("{$this->baseUrl}/{$this->apiVersion}/campaign/get/", [
                    'advertiser_id' => $advertiserId,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'filtering' => json_encode([
                        'campaign_status' => ['CAMPAIGN_STATUS_ENABLE', 'CAMPAIGN_STATUS_DISABLE']
                    ])
                ]);

                if (!$response->successful()) {
                    throw new \Exception('TikTok API error: ' . $response->body());
                }

                $data = $response->json();

                if ($data['code'] !== 0) {
                    throw new \Exception('TikTok API error: ' . ($data['message'] ?? 'Unknown error'));
                }

                return [
                    'campaigns' => $this->transformCampaigns($data['data']['list'] ?? []),
                    'page_info' => $data['data']['page_info'] ?? null
                ];
            } catch (\Exception $e) {
                Log::error('TikTok Ads fetch campaigns error', [
                    'advertiser_id' => $advertiserId,
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
        string $advertiserId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Get campaign basic info
            $response = Http::withHeaders([
                'Access-Token' => $accessToken
            ])->get("{$this->baseUrl}/{$this->apiVersion}/campaign/get/", [
                'advertiser_id' => $advertiserId,
                'filtering' => json_encode([
                    'campaign_ids' => [$campaignId]
                ])
            ]);

            if (!$response->successful() || $response->json()['code'] !== 0) {
                throw new \Exception('Campaign not found');
            }

            $campaign = $response->json()['data']['list'][0] ?? null;
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Get performance metrics
            $metrics = $this->getCampaignMetrics($advertiserId, $campaignId, $accessToken, $startDate, $endDate);

            return $this->transformCampaignDetails($campaign, $metrics);
        } catch (\Exception $e) {
            Log::error('TikTok Ads campaign details error', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch ad groups for a campaign
     */
    public function fetchAdGroups(
        string $advertiserId,
        string $campaignId,
        string $accessToken
    ): array {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken
            ])->get("{$this->baseUrl}/{$this->apiVersion}/adgroup/get/", [
                'advertiser_id' => $advertiserId,
                'filtering' => json_encode([
                    'campaign_ids' => [$campaignId]
                ])
            ]);

            if (!$response->successful() || $response->json()['code'] !== 0) {
                throw new \Exception('Failed to fetch ad groups');
            }

            $adGroups = $response->json()['data']['list'] ?? [];

            return $this->transformAdGroups($adGroups);
        } catch (\Exception $e) {
            Log::error('TikTok Ads fetch ad groups error', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch ads for an ad group
     */
    public function fetchAds(
        string $advertiserId,
        string $adGroupId,
        string $accessToken
    ): array {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken
            ])->get("{$this->baseUrl}/{$this->apiVersion}/ad/get/", [
                'advertiser_id' => $advertiserId,
                'filtering' => json_encode([
                    'adgroup_ids' => [$adGroupId]
                ])
            ]);

            if (!$response->successful() || $response->json()['code'] !== 0) {
                throw new \Exception('Failed to fetch ads');
            }

            $ads = $response->json()['data']['list'] ?? [];

            return $this->transformAds($ads);
        } catch (\Exception $e) {
            Log::error('TikTok Ads fetch ads error', [
                'ad_group_id' => $adGroupId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new TikTok Ads campaign
     */
    public function createCampaign(
        string $advertiserId,
        string $accessToken,
        array $campaignData
    ): array {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/{$this->apiVersion}/campaign/create/", [
                'advertiser_id' => $advertiserId,
                'campaign_name' => $campaignData['name'],
                'objective_type' => $campaignData['objective'],
                'budget_mode' => $campaignData['budget_mode'] ?? 'BUDGET_MODE_INFINITE',
                'budget' => isset($campaignData['budget']) ? $campaignData['budget'] * 100 : null, // Convert to cents
                'operation_status' => $campaignData['status'] ?? 'DISABLE'
            ]);

            if (!$response->successful()) {
                throw new \Exception('TikTok API error: ' . $response->body());
            }

            $data = $response->json();

            if ($data['code'] !== 0) {
                throw new \Exception('TikTok API error: ' . ($data['message'] ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'campaign_id' => $data['data']['campaign_id'] ?? null,
                'message' => 'Campaign created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('TikTok Ads create campaign error', [
                'advertiser_id' => $advertiserId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(
        string $advertiserId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $end = $endDate ?? Carbon::now()->format('Y-m-d');

        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken
            ])->get("{$this->baseUrl}/{$this->apiVersion}/reports/integrated/get/", [
                'advertiser_id' => $advertiserId,
                'report_type' => 'BASIC',
                'data_level' => 'AUCTION_CAMPAIGN',
                'dimensions' => json_encode(['campaign_id', 'stat_time_day']),
                'metrics' => json_encode([
                    'spend',
                    'impressions',
                    'clicks',
                    'conversions',
                    'conversion_rate',
                    'cpc',
                    'cpm',
                    'ctr'
                ]),
                'start_date' => $start,
                'end_date' => $end,
                'filtering' => json_encode([
                    'campaign_ids' => [$campaignId]
                ])
            ]);

            if (!$response->successful() || $response->json()['code'] !== 0) {
                return [];
            }

            $data = $response->json()['data']['list'] ?? [];

            return $this->transformMetrics($data);
        } catch (\Exception $e) {
            Log::error('TikTok Ads metrics error', [
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
                'id' => $campaign['campaign_id'] ?? null,
                'name' => $campaign['campaign_name'] ?? null,
                'status' => $this->normalizeStatus($campaign['operation_status'] ?? null),
                'objective' => $campaign['objective_type'] ?? null,
                'budget_mode' => $campaign['budget_mode'] ?? null,
                'budget' => isset($campaign['budget']) ? $campaign['budget'] / 100 : null, // Convert from cents
                'created_at' => $campaign['create_time'] ?? null,
                'modified_at' => $campaign['modify_time'] ?? null,
                'platform' => 'tiktok'
            ];
        }, $campaigns);
    }

    /**
     * Transform campaign details
     */
    private function transformCampaignDetails(array $campaign, array $metrics): array
    {
        return [
            'id' => $campaign['campaign_id'] ?? null,
            'name' => $campaign['campaign_name'] ?? null,
            'status' => $this->normalizeStatus($campaign['operation_status'] ?? null),
            'objective' => $campaign['objective_type'] ?? null,
            'budget_mode' => $campaign['budget_mode'] ?? null,
            'budget' => isset($campaign['budget']) ? $campaign['budget'] / 100 : null,
            'created_at' => $campaign['create_time'] ?? null,
            'modified_at' => $campaign['modify_time'] ?? null,
            'metrics' => $metrics,
            'platform' => 'tiktok'
        ];
    }

    /**
     * Transform ad groups response
     */
    private function transformAdGroups(array $adGroups): array
    {
        return array_map(function ($adGroup) {
            return [
                'id' => $adGroup['adgroup_id'] ?? null,
                'name' => $adGroup['adgroup_name'] ?? null,
                'status' => $this->normalizeStatus($adGroup['operation_status'] ?? null),
                'campaign_id' => $adGroup['campaign_id'] ?? null,
                'placement_type' => $adGroup['placement_type'] ?? null,
                'budget' => isset($adGroup['budget']) ? $adGroup['budget'] / 100 : null,
                'bid' => isset($adGroup['bid']) ? $adGroup['bid'] / 100 : null,
                'created_at' => $adGroup['create_time'] ?? null
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
                'id' => $ad['ad_id'] ?? null,
                'name' => $ad['ad_name'] ?? null,
                'status' => $this->normalizeStatus($ad['operation_status'] ?? null),
                'ad_group_id' => $ad['adgroup_id'] ?? null,
                'ad_text' => $ad['ad_text'] ?? null,
                'call_to_action' => $ad['call_to_action'] ?? null,
                'created_at' => $ad['create_time'] ?? null
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
                'date' => $metric['dimensions']['stat_time_day'] ?? null,
                'spend' => isset($metric['metrics']['spend']) ? $metric['metrics']['spend'] / 100 : 0,
                'impressions' => $metric['metrics']['impressions'] ?? 0,
                'clicks' => $metric['metrics']['clicks'] ?? 0,
                'conversions' => $metric['metrics']['conversions'] ?? 0,
                'conversion_rate' => $metric['metrics']['conversion_rate'] ?? 0,
                'cpc' => isset($metric['metrics']['cpc']) ? $metric['metrics']['cpc'] / 100 : 0,
                'cpm' => isset($metric['metrics']['cpm']) ? $metric['metrics']['cpm'] / 100 : 0,
                'ctr' => $metric['metrics']['ctr'] ?? 0
            ];
        }, $metrics);
    }

    /**
     * Normalize status to standard format
     */
    private function normalizeStatus(?string $status): string
    {
        return match($status) {
            'CAMPAIGN_STATUS_ENABLE', 'ENABLE' => 'ENABLED',
            'CAMPAIGN_STATUS_DISABLE', 'DISABLE' => 'PAUSED',
            'CAMPAIGN_STATUS_DELETE', 'DELETE' => 'REMOVED',
            default => 'UNKNOWN'
        };
    }

    /**
     * Clear cache for advertiser
     */
    public function clearCache(string $advertiserId): void
    {
        // Clear all pages
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("tiktok_ads_campaigns_{$advertiserId}_{$page}");
        }
    }
}
