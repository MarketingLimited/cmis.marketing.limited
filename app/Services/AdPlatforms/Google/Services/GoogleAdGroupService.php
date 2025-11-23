<?php

namespace App\Services\AdPlatforms\Google\Services;

/**
 * Google Ads AdGroup Service
 *
 * Handles ad group (ad set) operations:
 * - Create ad groups
 * - Update ad groups
 * - Manage ad group bidding
 *
 * Single Responsibility: Ad Group lifecycle management
 */
class GoogleAdGroupService
{
    protected string $customerId;
    protected GoogleHelperService $helper;
    protected $makeRequestCallback;

    public function __construct(
        string $customerId,
        GoogleHelperService $helper,
        callable $makeRequestCallback
    ) {
        $this->customerId = $customerId;
        $this->helper = $helper;
        $this->makeRequestCallback = $makeRequestCallback;
    }

    /**
     * Create ad group (Ad Set equivalent)
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $url = $this->helper->buildUrl('/customers/{customer_id}/adGroups:mutate');

            $adGroup = [
                'name' => $data['name'],
                'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                'status' => $this->helper->mapStatus($data['status'] ?? 'ENABLED'),
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

            $response = ($this->makeRequestCallback)('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $this->helper->extractAdGroupId($response['results'][0]['resourceName']),
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
}
