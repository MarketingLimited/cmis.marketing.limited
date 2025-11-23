<?php

namespace App\Services\AdPlatforms\Google\Services;

use Carbon\Carbon;

/**
 * Google Ads Campaign Service
 *
 * Handles all campaign-related operations:
 * - Create, update, delete campaigns
 * - Fetch campaigns and metrics
 * - Campaign status management
 * - Campaign budget management
 *
 * Single Responsibility: Campaign lifecycle management
 */
class GoogleCampaignService
{
    protected string $customerId;
    protected GoogleHelperService $helper;
    protected $makeRequestCallback;
    protected $executeQueryCallback;

    public function __construct(
        string $customerId,
        GoogleHelperService $helper,
        callable $makeRequestCallback,
        callable $executeQueryCallback
    ) {
        $this->customerId = $customerId;
        $this->helper = $helper;
        $this->makeRequestCallback = $makeRequestCallback;
        $this->executeQueryCallback = $executeQueryCallback;
    }

    /**
     * Create campaign on Google Ads
     */
    public function createCampaign(array $data): array
    {
        try {
            $url = $this->helper->buildUrl('/customers/{customer_id}/campaigns:mutate');

            // Build campaign resource
            $campaign = [
                'name' => $data['name'],
                'advertisingChannelType' => $this->helper->mapCampaignType($data['campaign_type'] ?? 'SEARCH'),
                'status' => $this->helper->mapStatus($data['status'] ?? 'PAUSED'),
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
            $this->applyCampaignTypeSettings($campaign, $data);

            $payload = [
                'operations' => [
                    ['create' => $campaign],
                ],
            ];

            $response = ($this->makeRequestCallback)('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $this->helper->extractCampaignId($response['results'][0]['resourceName']),
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
            $url = $this->helper->buildUrl('/customers/{customer_id}/campaigns:mutate');

            $resourceName = "customers/{$this->customerId}/campaigns/{$externalId}";

            $updateMask = [];
            $campaign = ['resourceName' => $resourceName];

            if (isset($data['name'])) {
                $campaign['name'] = $data['name'];
                $updateMask[] = 'name';
            }

            if (isset($data['status'])) {
                $campaign['status'] = $this->helper->mapStatus($data['status']);
                $updateMask[] = 'status';
            }

            if (isset($data['daily_budget'])) {
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

            $response = ($this->makeRequestCallback)('POST', $url, $payload);

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

            $response = ($this->executeQueryCallback)($query);

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
            $url = $this->helper->buildUrl('/customers/{customer_id}/campaigns:mutate');

            $payload = [
                'operations' => [
                    [
                        'remove' => "customers/{$this->customerId}/campaigns/{$externalId}",
                    ],
                ],
            ];

            $response = ($this->makeRequestCallback)('POST', $url, $payload);

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
                $query .= " AND campaign.status = '{$this->helper->mapStatus($filters['status'])}'";
            }

            if (isset($filters['campaign_type'])) {
                $query .= " AND campaign.advertising_channel_type = '{$this->helper->mapCampaignType($filters['campaign_type'])}'";
            }

            $query .= " ORDER BY campaign.id DESC LIMIT " . ($filters['limit'] ?? 100);

            $response = ($this->executeQueryCallback)($query);

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

            $response = ($this->executeQueryCallback)($query);

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

    /**
     * Create campaign budget
     */
    protected function createCampaignBudget(float $dailyBudget): string
    {
        $url = $this->helper->buildUrl('/customers/{customer_id}/campaignBudgets:mutate');

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

        $response = ($this->makeRequestCallback)('POST', $url, $payload);

        return $response['results'][0]['resourceName'];
    }

    /**
     * Update campaign budget (placeholder - implement if needed)
     */
    protected function updateCampaignBudget(string $externalId, float $dailyBudget): void
    {
        // Implementation depends on whether budget is shared or campaign-specific
    }

    /**
     * Apply campaign-type-specific settings
     */
    protected function applyCampaignTypeSettings(array &$campaign, array $data): void
    {
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
    }
}
