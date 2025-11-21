<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SnapchatAdsService
{
    private string $baseUrl = 'https://adsapi.snapchat.com';
    private string $apiVersion = 'v1';

    /**
     * Fetch campaigns for an ad account
     */
    public function fetchCampaigns(
        string $adAccountId,
        string $accessToken,
        int $limit = 50
    ): array {
        $cacheKey = "snapchat_campaigns_{$adAccountId}_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($adAccountId, $accessToken, $limit) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}"
                ])->get("{$this->baseUrl}/{$this->apiVersion}/adaccounts/{$adAccountId}/campaigns", [
                    'limit' => $limit
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Snapchat Ads API Error: ' . $response->body());
                }

                $data = $response->json();

                return [
                    'campaigns' => $this->transformCampaigns($data['campaigns'] ?? []),
                    'paging' => $data['paging'] ?? null
                ];
            } catch (\Exception $e) {
                Log::error('Snapchat Ads API error', [
                    'ad_account_id' => $adAccountId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create a new Snapchat Ads campaign
     */
    public function createCampaign(
        string $adAccountId,
        string $accessToken,
        array $campaignData
    ): array {
        try {
            $payload = [
                'campaigns' => [
                    [
                        'name' => $campaignData['name'],
                        'ad_account_id' => $adAccountId,
                        'status' => $campaignData['status'] ?? 'PAUSED',
                        'objective' => $campaignData['objective'],
                        'daily_budget_micro' => isset($campaignData['daily_budget'])
                            ? $this->toMicros($campaignData['daily_budget'])
                            : null,
                        'lifetime_spend_cap_micro' => isset($campaignData['lifetime_budget'])
                            ? $this->toMicros($campaignData['lifetime_budget'])
                            : null,
                        'start_time' => $campaignData['start_time'] ?? now()->toIso8601String()
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/{$this->apiVersion}/adaccounts/{$adAccountId}/campaigns", $payload);

            if (!$response->successful()) {
                throw new \Exception('Snapchat Ads API Error: ' . $response->body());
            }

            $data = $response->json();

            return $data['campaigns'][0]['campaign'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to create Snapchat campaign', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign details with metrics
     */
    public function getCampaignDetails(
        string $adAccountId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Get campaign info
            $campaignResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}"
            ])->get("{$this->baseUrl}/{$this->apiVersion}/campaigns/{$campaignId}");

            if (!$campaignResponse->successful()) {
                throw new \Exception('Failed to fetch campaign details');
            }

            $campaign = $this->transformCampaign($campaignResponse->json()['campaigns'][0]['campaign'] ?? []);

            // Get campaign metrics
            $metrics = $this->getCampaignMetrics($adAccountId, $campaignId, $accessToken, $startDate, $endDate);

            return [
                'campaign' => $campaign,
                'metrics' => $metrics
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Snapchat campaign details', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(
        string $adAccountId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $params = [
                'granularity' => 'TOTAL',
                'fields' => 'impressions,swipes,spend,video_views,conversions',
                'start_time' => $startDate ?? now()->subDays(7)->format('Y-m-d'),
                'end_time' => $endDate ?? now()->format('Y-m-d')
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}"
            ])->get("{$this->baseUrl}/{$this->apiVersion}/campaigns/{$campaignId}/stats", $params);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch metrics');
            }

            $data = $response->json();
            $stats = $data['total_stats'][0]['total_stat']['stats'] ?? [];

            return $this->transformMetrics($stats);
        } catch (\Exception $e) {
            Log::error('Failed to get Snapchat campaign metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Clear cache for an ad account
     */
    public function clearCache(string $adAccountId): void
    {
        $keys = Cache::getStore()->getRedis()->keys("*snapchat_*{$adAccountId}*");
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Transform Snapchat campaigns to common format
     */
    private function transformCampaigns(array $campaigns): array
    {
        return array_map(function ($item) {
            return $this->transformCampaign($item['campaign'] ?? []);
        }, $campaigns);
    }

    /**
     * Transform a single campaign
     */
    private function transformCampaign(array $campaign): array
    {
        return [
            'id' => $campaign['id'] ?? null,
            'name' => $campaign['name'] ?? null,
            'status' => $this->normalizeStatus($campaign['status'] ?? 'UNKNOWN'),
            'objective' => $campaign['objective'] ?? null,
            'daily_budget' => isset($campaign['daily_budget_micro'])
                ? $this->fromMicros($campaign['daily_budget_micro'])
                : null,
            'lifetime_budget' => isset($campaign['lifetime_spend_cap_micro'])
                ? $this->fromMicros($campaign['lifetime_spend_cap_micro'])
                : null,
            'start_time' => $campaign['start_time'] ?? null,
            'end_time' => $campaign['end_time'] ?? null,
            'created_at' => $campaign['created_at'] ?? null,
            'updated_at' => $campaign['updated_at'] ?? null,
            'platform' => 'snapchat'
        ];
    }

    /**
     * Transform metrics to common format
     */
    private function transformMetrics(array $stats): array
    {
        $impressions = $stats['impressions'] ?? 0;
        $swipes = $stats['swipes'] ?? 0; // Snapchat's version of clicks
        $spend = isset($stats['spend'])
            ? $this->fromMicros($stats['spend'])
            : 0;

        return [
            'impressions' => $impressions,
            'swipes' => $swipes, // Equivalent to clicks
            'clicks' => $swipes,
            'spend' => $spend,
            'video_views' => $stats['video_views'] ?? 0,
            'conversions' => $stats['conversions'] ?? 0,
            'swipe_up_rate' => $impressions > 0 ? ($swipes / $impressions) : 0,
            'ctr' => $impressions > 0 ? ($swipes / $impressions) : 0,
            'cps' => $swipes > 0 ? ($spend / $swipes) : 0, // Cost per swipe
            'cpc' => $swipes > 0 ? ($spend / $swipes) : 0,
            'cpa' => ($stats['conversions'] ?? 0) > 0 ? ($spend / $stats['conversions']) : 0
        ];
    }

    /**
     * Normalize Snapchat status to common format
     */
    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'ARCHIVED', 'DELETED' => 'archived',
            default => 'unknown'
        };
    }

    /**
     * Convert dollars to micros (Snapchat uses micro currency units)
     */
    private function toMicros(float $amount): int
    {
        return (int)($amount * 1000000);
    }

    /**
     * Convert micros to dollars
     */
    private function fromMicros(int $micros): float
    {
        return round($micros / 1000000, 2);
    }
}
