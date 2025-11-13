<?php

namespace App\Services\Ads;

use App\Services\Social\AbstractSocialService;
use App\Models\{SocialAccount, AdCampaign, AdSet, Ad, AdMetric};
use Carbon\Carbon;

class MetaAdsService extends AbstractSocialService
{
    protected function getConfiguration(): array
    {
        return [
            'api_base' => 'https://graph.facebook.com',
            'api_version' => 'v18.0',
            'fields' => [
                'account' => 'id,name,account_status,currency,timezone_name,amount_spent,balance',
                'campaigns' => 'id,name,status,objective,start_time,stop_time,daily_budget,lifetime_budget,spend',
                'adsets' => 'id,name,status,campaign_id,daily_budget,lifetime_budget,start_time,end_time,targeting,optimization_goal',
                'ads' => 'id,name,status,adset_id,creative{id,title,body,image_url,video_id},effective_status',
                'insights' => 'impressions,clicks,spend,reach,frequency,cpc,cpm,ctr,conversions,cost_per_conversion'
            ]
        ];
    }

    public function syncAccount(): array
    {
        if (!$this->validateToken()) {
            return ['error' => 'Invalid or expired token'];
        }

        // Get ad account ID from integration metadata
        $adAccountId = $this->integration->metadata['ad_account_id'] ?? null;

        if (!$adAccountId) {
            return ['error' => 'Ad account ID not configured'];
        }

        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/act_{$adAccountId}";
        $params = ['fields' => $this->config['fields']['account']];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data)) {
            return ['error' => 'Failed to fetch ad account data'];
        }

        $account = SocialAccount::updateOrCreate(
            [
                'org_id' => $this->integration->org_id,
                'platform' => 'meta_ads',
                'platform_account_id' => $data['id']
            ],
            [
                'account_name' => $data['name'],
                'metadata' => [
                    'account_status' => $data['account_status'] ?? null,
                    'currency' => $data['currency'] ?? 'USD',
                    'timezone' => $data['timezone_name'] ?? null,
                    'amount_spent' => $data['amount_spent'] ?? 0,
                    'balance' => $data['balance'] ?? 0,
                ],
                'is_active' => ($data['account_status'] ?? null) === 1,
            ]
        );

        return [
            'success' => true,
            'account' => $account
        ];
    }

    public function syncPosts($from, $to, $limit = 25): array
    {
        // For Meta Ads, we sync campaigns/ads instead of posts
        return $this->syncCampaigns($from, $to, $limit);
    }

    public function syncCampaigns($from = null, $to = null, $limit = 25): array
    {
        $adAccountId = $this->integration->metadata['ad_account_id'] ?? null;

        if (!$adAccountId) {
            return ['campaigns' => []];
        }

        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/act_{$adAccountId}/campaigns";
        $params = [
            'fields' => $this->config['fields']['campaigns'],
            'limit' => $limit
        ];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data['data'])) {
            return ['campaigns' => []];
        }

        $campaigns = [];

        foreach ($data['data'] as $campaignData) {
            $campaign = AdCampaign::updateOrCreate(
                [
                    'org_id' => $this->integration->org_id,
                    'platform' => 'meta_ads',
                    'platform_campaign_id' => $campaignData['id']
                ],
                [
                    'campaign_name' => $campaignData['name'],
                    'status' => strtolower($campaignData['status'] ?? 'unknown'),
                    'objective' => $campaignData['objective'] ?? null,
                    'daily_budget' => $campaignData['daily_budget'] ?? null,
                    'lifetime_budget' => $campaignData['lifetime_budget'] ?? null,
                    'start_time' => isset($campaignData['start_time']) ? Carbon::parse($campaignData['start_time']) : null,
                    'end_time' => isset($campaignData['stop_time']) ? Carbon::parse($campaignData['stop_time']) : null,
                    'total_spend' => $campaignData['spend'] ?? 0,
                ]
            );

            // Sync ad sets for this campaign
            $this->syncAdSets($campaign, $campaignData['id']);

            $campaigns[] = $campaign;
        }

        return ['campaigns' => $campaigns];
    }

    protected function syncAdSets(AdCampaign $campaign, string $campaignId): void
    {
        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/{$campaignId}/adsets";
        $params = ['fields' => $this->config['fields']['adsets']];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data['data'])) {
            return;
        }

        foreach ($data['data'] as $adsetData) {
            $adset = AdSet::updateOrCreate(
                [
                    'org_id' => $this->integration->org_id,
                    'platform_adset_id' => $adsetData['id']
                ],
                [
                    'campaign_id' => $campaign->id,
                    'adset_name' => $adsetData['name'],
                    'status' => strtolower($adsetData['status'] ?? 'unknown'),
                    'daily_budget' => $adsetData['daily_budget'] ?? null,
                    'lifetime_budget' => $adsetData['lifetime_budget'] ?? null,
                    'optimization_goal' => $adsetData['optimization_goal'] ?? null,
                    'targeting' => $adsetData['targeting'] ?? null,
                    'start_time' => isset($adsetData['start_time']) ? Carbon::parse($adsetData['start_time']) : null,
                    'end_time' => isset($adsetData['end_time']) ? Carbon::parse($adsetData['end_time']) : null,
                ]
            );

            // Sync ads for this ad set
            $this->syncAds($adset, $adsetData['id']);
        }
    }

    protected function syncAds(AdSet $adset, string $adsetId): void
    {
        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/{$adsetId}/ads";
        $params = ['fields' => $this->config['fields']['ads']];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data['data'])) {
            return;
        }

        foreach ($data['data'] as $adData) {
            Ad::updateOrCreate(
                [
                    'org_id' => $this->integration->org_id,
                    'platform_ad_id' => $adData['id']
                ],
                [
                    'adset_id' => $adset->id,
                    'ad_name' => $adData['name'],
                    'status' => strtolower($adData['status'] ?? 'unknown'),
                    'effective_status' => strtolower($adData['effective_status'] ?? 'unknown'),
                    'creative_data' => $adData['creative'] ?? null,
                ]
            );
        }
    }

    public function syncMetrics(array $campaignIds = []): array
    {
        $metrics = [];

        foreach ($campaignIds as $campaignId) {
            $campaign = AdCampaign::where('org_id', $this->integration->org_id)
                ->where('id', $campaignId)
                ->first();

            if (!$campaign) {
                continue;
            }

            $insights = $this->fetchCampaignInsights($campaign->platform_campaign_id);

            if (!empty($insights)) {
                $this->storeCampaignMetrics($campaign, $insights);
                $metrics[$campaignId] = $insights;
            }
        }

        return $metrics;
    }

    protected function fetchCampaignInsights(string $campaignId): array
    {
        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/{$campaignId}/insights";
        $params = [
            'fields' => $this->config['fields']['insights'],
            'time_range' => json_encode([
                'since' => Carbon::now()->subDays(7)->format('Y-m-d'),
                'until' => Carbon::now()->format('Y-m-d')
            ])
        ];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data['data'][0])) {
            return [];
        }

        return $data['data'][0];
    }

    protected function storeCampaignMetrics(AdCampaign $campaign, array $insights): void
    {
        foreach ($insights as $metric => $value) {
            AdMetric::create([
                'org_id' => $this->integration->org_id,
                'campaign_id' => $campaign->id,
                'metric_name' => $metric,
                'metric_value' => $value,
                'recorded_at' => Carbon::now(),
            ]);
        }
    }

    public function refreshToken(): bool
    {
        // Meta Ads uses long-lived tokens that are refreshed via OAuth flow
        // This should be handled by the OAuth integration controller
        return true;
    }
}
