<?php

namespace App\Services\AdPlatforms\Meta;

use App\Models\AdPlatform\{AdCampaign, AdSet, AdEntity, AdMetric, AdAccount};
use App\Services\AdPlatforms\AbstractAdPlatform;
use Carbon\Carbon;

/**
 * Meta Ads Platform Service (Facebook & Instagram)
 *
 * Implements ad campaign management for Meta platforms including Facebook and Instagram.
 * Uses Meta Marketing API v18.0
 *
 * @package App\Services\AdPlatforms\Meta
 * @link https://developers.facebook.com/docs/marketing-apis
 */
class MetaAdsPlatform extends AbstractAdPlatform
{
    protected string $apiVersion = 'v18.0';
    protected string $apiBaseUrl = 'https://graph.facebook.com';

    /**
     * Get Meta-specific configuration
     */
    protected function getConfig(): array
    {
        return [
            'api_version' => $this->apiVersion,
            'api_base_url' => $this->apiBaseUrl,
            'endpoints' => [
                'account' => '/{version}/act_{account_id}',
                'campaigns' => '/{version}/act_{account_id}/campaigns',
                'campaign' => '/{version}/{campaign_id}',
                'ad_sets' => '/{version}/{campaign_id}/adsets',
                'ads' => '/{version}/{ad_set_id}/ads',
                'insights' => '/{version}/{entity_id}/insights',
            ],
        ];
    }

    /**
     * Get platform name
     */
    protected function getPlatformName(): string
    {
        return 'meta';
    }

    /**
     * Get default headers for Meta API
     */
    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Authorization' => 'Bearer ' . $this->integration->access_token,
        ]);
    }

    /**
     * Create campaign on Meta
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

            $adAccountId = $this->integration->account_id;
            $url = $this->buildUrl('/act_{account_id}/campaigns', ['account_id' => $adAccountId]);

            $payload = [
                'name' => $data['name'],
                'objective' => $this->mapObjective($data['objective']),
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'special_ad_categories' => $data['special_ad_categories'] ?? [],
            ];

            if (isset($data['daily_budget'])) {
                $payload['daily_budget'] = $data['daily_budget'] * 100; // Convert to cents
            }

            if (isset($data['lifetime_budget'])) {
                $payload['lifetime_budget'] = $data['lifetime_budget'] * 100;
            }

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $response['id'],
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
            $url = $this->buildUrl('/{campaign_id}', ['campaign_id' => $externalId]);

            $payload = array_filter([
                'name' => $data['name'] ?? null,
                'status' => isset($data['status']) ? $this->mapStatus($data['status']) : null,
                'daily_budget' => isset($data['daily_budget']) ? $data['daily_budget'] * 100 : null,
                'lifetime_budget' => isset($data['lifetime_budget']) ? $data['lifetime_budget'] * 100 : null,
            ], fn($value) => $value !== null);

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => $response['success'] ?? true,
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
            $url = $this->buildUrl('/{campaign_id}', ['campaign_id' => $externalId]);

            $response = $this->makeRequest('GET', $url, [
                'fields' => 'id,name,objective,status,daily_budget,lifetime_budget,start_time,stop_time,created_time,updated_time',
            ]);

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
     * Delete campaign
     */
    public function deleteCampaign(string $externalId): array
    {
        try {
            $url = $this->buildUrl('/{campaign_id}', ['campaign_id' => $externalId]);

            $response = $this->makeRequest('DELETE', $url);

            return [
                'success' => $response['success'] ?? true,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch campaigns from Meta
     */
    public function fetchCampaigns(array $filters = []): array
    {
        try {
            $adAccountId = $this->integration->account_id;
            $url = $this->buildUrl('/act_{account_id}/campaigns', ['account_id' => $adAccountId]);

            $params = [
                'fields' => 'id,name,objective,status,daily_budget,lifetime_budget,start_time,stop_time,created_time,updated_time',
                'limit' => $filters['limit'] ?? 100,
            ];

            if (isset($filters['status'])) {
                $params['filtering'] = json_encode([
                    ['field' => 'status', 'operator' => 'IN', 'value' => [$this->mapStatus($filters['status'])]],
                ]);
            }

            $response = $this->makeRequest('GET', $url, $params);

            return [
                'success' => true,
                'campaigns' => $response['data'] ?? [],
                'paging' => $response['paging'] ?? null,
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
            $url = $this->buildUrl('/{campaign_id}/insights', ['campaign_id' => $externalId]);

            $response = $this->makeRequest('GET', $url, [
                'fields' => 'impressions,clicks,spend,reach,frequency,cpc,cpm,ctr,conversions,cost_per_conversion,actions,action_values',
                'time_range' => json_encode([
                    'since' => $startDate,
                    'until' => $endDate,
                ]),
                'time_increment' => 1, // Daily breakdown
            ]);

            return [
                'success' => true,
                'metrics' => $response['data'] ?? [],
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

    /**
     * Create ad set
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $adAccountId = $this->integration->account_id;
            $url = $this->buildUrl('/act_{account_id}/adsets', ['account_id' => $adAccountId]);

            $payload = [
                'campaign_id' => $campaignExternalId,
                'name' => $data['name'],
                'billing_event' => $data['billing_event'] ?? 'IMPRESSIONS',
                'optimization_goal' => $data['optimization_goal'] ?? 'REACH',
                'bid_strategy' => $data['bid_strategy'] ?? 'LOWEST_COST_WITHOUT_CAP',
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'targeting' => $data['targeting'] ?? ['geo_locations' => ['countries' => ['US']]],
            ];

            if (isset($data['daily_budget'])) {
                $payload['daily_budget'] = $data['daily_budget'] * 100;
            }

            if (isset($data['lifetime_budget'])) {
                $payload['lifetime_budget'] = $data['lifetime_budget'] * 100;
            }

            if (isset($data['start_time'])) {
                $payload['start_time'] = Carbon::parse($data['start_time'])->toIso8601String();
            }

            if (isset($data['end_time'])) {
                $payload['end_time'] = Carbon::parse($data['end_time'])->toIso8601String();
            }

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $response['id'],
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
     * Create ad
     */
    public function createAd(string $adSetExternalId, array $data): array
    {
        try {
            $adAccountId = $this->integration->account_id;
            $url = $this->buildUrl('/act_{account_id}/ads', ['account_id' => $adAccountId]);

            $payload = [
                'adset_id' => $adSetExternalId,
                'name' => $data['name'],
                'creative' => $data['creative'],
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
            ];

            if (isset($data['tracking_specs'])) {
                $payload['tracking_specs'] = $data['tracking_specs'];
            }

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $response['id'],
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
     * Get available objectives
     */
    public function getAvailableObjectives(): array
    {
        return [
            'OUTCOME_AWARENESS',
            'OUTCOME_ENGAGEMENT',
            'OUTCOME_LEADS',
            'OUTCOME_SALES',
            'OUTCOME_TRAFFIC',
            'OUTCOME_APP_PROMOTION',
        ];
    }

    /**
     * Get available placements
     */
    public function getAvailablePlacements(): array
    {
        return [
            'facebook' => ['feed', 'right_hand_column', 'instant_article', 'marketplace', 'video_feeds', 'story', 'search', 'instream_video'],
            'instagram' => ['stream', 'story', 'explore', 'reels'],
            'messenger' => ['messenger_home', 'sponsored_messages', 'story'],
            'audience_network' => ['classic', 'instream_video', 'rewarded_video'],
        ];
    }

    /**
     * Sync account data
     */
    public function syncAccount(): array
    {
        try {
            $adAccountId = $this->integration->account_id;
            $url = $this->buildUrl('/act_{account_id}', ['account_id' => $adAccountId]);

            $response = $this->makeRequest('GET', $url, [
                'fields' => 'id,name,account_status,currency,timezone_name,amount_spent,balance,spend_cap,business',
            ]);

            return [
                'success' => true,
                'account' => $response,
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
        // Meta uses long-lived tokens that don't typically need refreshing
        // This should be handled via OAuth flow
        return [
            'success' => false,
            'error' => 'Meta tokens must be refreshed via OAuth flow',
        ];
    }

    /**
     * Build full API URL
     */
    protected function buildUrl(string $endpoint, array $params = []): string
    {
        $url = $this->apiBaseUrl . str_replace('{version}', $this->apiVersion, $endpoint);

        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        return $url;
    }

    /**
     * Map internal objective to Meta objective
     */
    protected function mapObjective(string $objective): string
    {
        return match (strtolower($objective)) {
            'awareness' => 'OUTCOME_AWARENESS',
            'engagement' => 'OUTCOME_ENGAGEMENT',
            'leads' => 'OUTCOME_LEADS',
            'sales', 'conversions' => 'OUTCOME_SALES',
            'traffic' => 'OUTCOME_TRAFFIC',
            'app_installs' => 'OUTCOME_APP_PROMOTION',
            default => $objective,
        };
    }

    /**
     * Map internal status to Meta status
     */
    protected function mapStatus(string $internalStatus): string
    {
        return match (strtolower($internalStatus)) {
            'active' => 'ACTIVE',
            'paused' => 'PAUSED',
            'deleted', 'archived' => 'ARCHIVED',
            default => 'PAUSED',
        };
    }
}
