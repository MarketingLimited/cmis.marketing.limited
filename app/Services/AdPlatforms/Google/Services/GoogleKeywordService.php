<?php

namespace App\Services\AdPlatforms\Google\Services;

/**
 * Google Ads Keyword Service
 *
 * Handles keyword operations:
 * - Add keywords to ad groups
 * - Add negative keywords
 * - Remove keywords
 * - Get keyword lists
 *
 * Single Responsibility: Keyword management
 */
class GoogleKeywordService
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
     * Add keywords to ad group
     */
    public function addKeywords(string $adGroupExternalId, array $keywords): array
    {
        try {
            $url = $this->helper->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($keywords as $keyword) {
                $operations[] = [
                    'create' => [
                        'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                        'status' => 'ENABLED',
                        'keyword' => [
                            'text' => $keyword['text'],
                            'matchType' => $this->helper->mapKeywordMatchType($keyword['match_type'] ?? 'BROAD'),
                        ],
                        'cpcBidMicros' => $keyword['bid_micros'] ?? null,
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = ($this->makeRequestCallback)('POST', $url, $payload);

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
            $url = $this->helper->buildUrl('/customers/{customer_id}/campaignCriteria:mutate');

            $operations = [];
            foreach ($keywords as $keyword) {
                $operations[] = [
                    'create' => [
                        'campaign' => "customers/{$this->customerId}/campaigns/{$campaignExternalId}",
                        'negative' => true,
                        'keyword' => [
                            'text' => $keyword['text'],
                            'matchType' => $this->helper->mapKeywordMatchType($keyword['match_type'] ?? 'BROAD'),
                        ],
                    ],
                ];
            }

            $payload = ['operations' => $operations];
            $response = ($this->makeRequestCallback)('POST', $url, $payload);

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
            $url = $this->helper->buildUrl('/customers/{customer_id}/adGroupCriteria:mutate');

            $operations = [];
            foreach ($keywordResourceNames as $resourceName) {
                $operations[] = ['remove' => $resourceName];
            }

            $payload = ['operations' => $operations];
            $response = ($this->makeRequestCallback)('POST', $url, $payload);

            return [
                'success' => true,
                'keywords_removed' => count($response['results']),
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
     * Get keywords
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

            $response = ($this->executeQueryCallback)($query);

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
}
