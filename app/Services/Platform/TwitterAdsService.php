<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitterAdsService
{
    private string $baseUrl = 'https://ads-api.twitter.com';
    private string $apiVersion = '12'; // Twitter Ads API v12

    /**
     * Fetch campaigns for an ad account
     */
    public function fetchCampaigns(
        string $accountId,
        string $accessToken,
        int $count = 50,
        ?string $cursor = null
    ): array {
        $cacheKey = "twitter_campaigns_{$accountId}_{$count}_" . ($cursor ?? 'initial');

        return Cache::remember($cacheKey, 300, function () use ($accountId, $accessToken, $count, $cursor) {
            try {
                $params = [
                    'account_id' => $accountId,
                    'count' => $count
                ];

                if ($cursor) {
                    $params['cursor'] = $cursor;
                }

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'X-API-Version' => $this->apiVersion
                ])->get("{$this->baseUrl}/{$this->apiVersion}/accounts/{$accountId}/campaigns", $params);

                if (!$response->successful()) {
                    throw new \Exception('Twitter Ads API Error: ' . $response->body());
                }

                $data = $response->json();

                return [
                    'campaigns' => $this->transformCampaigns($data['data'] ?? []),
                    'next_cursor' => $data['next_cursor'] ?? null,
                    'total_count' => $data['total_count'] ?? 0
                ];
            } catch (\Exception $e) {
                Log::error('Twitter Ads API error', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create a new Twitter Ads campaign
     */
    public function createCampaign(
        string $accountId,
        string $accessToken,
        array $campaignData
    ): array {
        try {
            $payload = [
                'name' => $campaignData['name'],
                'funding_instrument_id' => $campaignData['funding_instrument_id'],
                'daily_budget_amount_local_micro' => isset($campaignData['daily_budget'])
                    ? $this->toMicros($campaignData['daily_budget'])
                    : null,
                'total_budget_amount_local_micro' => isset($campaignData['total_budget'])
                    ? $this->toMicros($campaignData['total_budget'])
                    : null,
                'start_time' => $campaignData['start_time'] ?? now()->toIso8601String(),
                'currency' => $campaignData['currency'] ?? 'USD',
                'entity_status' => $campaignData['status'] ?? 'PAUSED'
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'X-API-Version' => $this->apiVersion,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/{$this->apiVersion}/accounts/{$accountId}/campaigns", $payload);

            if (!$response->successful()) {
                throw new \Exception('Twitter Ads API Error: ' . $response->body());
            }

            $data = $response->json();

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to create Twitter campaign', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign details with metrics
     */
    public function getCampaignDetails(
        string $accountId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Get campaign info
            $campaignResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'X-API-Version' => $this->apiVersion
            ])->get("{$this->baseUrl}/{$this->apiVersion}/accounts/{$accountId}/campaigns/{$campaignId}");

            if (!$campaignResponse->successful()) {
                throw new \Exception('Failed to fetch campaign details');
            }

            $campaign = $this->transformCampaign($campaignResponse->json()['data'] ?? []);

            // Get campaign metrics
            $metrics = $this->getCampaignMetrics($accountId, $campaignId, $accessToken, $startDate, $endDate);

            return [
                'campaign' => $campaign,
                'metrics' => $metrics
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Twitter campaign details', [
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
        string $accountId,
        string $campaignId,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $params = [
                'entity' => 'CAMPAIGN',
                'entity_ids' => $campaignId,
                'granularity' => 'TOTAL',
                'metric_groups' => 'ENGAGEMENT,BILLING',
                'placement' => 'ALL_ON_TWITTER',
                'start_time' => $startDate ?? now()->subDays(7)->toIso8601String(),
                'end_time' => $endDate ?? now()->toIso8601String()
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'X-API-Version' => $this->apiVersion
            ])->get("{$this->baseUrl}/{$this->apiVersion}/stats/accounts/{$accountId}", $params);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch metrics');
            }

            $data = $response->json();
            $metrics = $data['data'][0]['id_data'][0]['metrics'] ?? [];

            return $this->transformMetrics($metrics);
        } catch (\Exception $e) {
            Log::error('Failed to get Twitter campaign metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Clear cache for an account
     */
    public function clearCache(string $accountId): void
    {
        $keys = Cache::getStore()->getRedis()->keys("*twitter_*{$accountId}*");
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Transform Twitter campaigns to common format
     */
    private function transformCampaigns(array $campaigns): array
    {
        return array_map(function ($campaign) {
            return $this->transformCampaign($campaign);
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
            'status' => $this->normalizeStatus($campaign['entity_status'] ?? 'UNKNOWN'),
            'daily_budget' => isset($campaign['daily_budget_amount_local_micro'])
                ? $this->fromMicros($campaign['daily_budget_amount_local_micro'])
                : null,
            'total_budget' => isset($campaign['total_budget_amount_local_micro'])
                ? $this->fromMicros($campaign['total_budget_amount_local_micro'])
                : null,
            'currency' => $campaign['currency'] ?? 'USD',
            'start_time' => $campaign['start_time'] ?? null,
            'end_time' => $campaign['end_time'] ?? null,
            'created_at' => $campaign['created_at'] ?? null,
            'updated_at' => $campaign['updated_at'] ?? null,
            'platform' => 'twitter'
        ];
    }

    /**
     * Transform metrics to common format
     */
    private function transformMetrics(array $metrics): array
    {
        $impressions = $metrics['impressions'] ?? 0;
        $clicks = $metrics['clicks'] ?? 0;
        $spend = isset($metrics['billed_charge_local_micro'])
            ? $this->fromMicros($metrics['billed_charge_local_micro'])
            : 0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'spend' => $spend,
            'engagements' => $metrics['engagements'] ?? 0,
            'retweets' => $metrics['retweets'] ?? 0,
            'likes' => $metrics['likes'] ?? 0,
            'replies' => $metrics['replies'] ?? 0,
            'follows' => $metrics['follows'] ?? 0,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) : 0,
            'cpc' => $clicks > 0 ? ($spend / $clicks) : 0,
            'cpe' => ($metrics['engagements'] ?? 0) > 0 ? ($spend / $metrics['engagements']) : 0
        ];
    }

    /**
     * Normalize Twitter status to common format
     */
    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'archived',
            default => 'unknown'
        };
    }

    /**
     * Convert dollars to micros (Twitter uses micro currency units)
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
