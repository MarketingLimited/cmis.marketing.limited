<?php

namespace App\Services\Platform\Batchers;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GoogleBatcher
 *
 * Implements Google Ads-specific batch optimizations:
 * - SearchStream API: Fetch unlimited data in a single streaming request
 * - GAQL Queries: Single query can join multiple resource types
 * - Batch mutate: Combine up to 5000 operations per request
 *
 * Expected reduction: 70-80% fewer API calls
 *
 * @see https://developers.google.com/google-ads/api/docs/reporting/streaming
 * @see https://developers.google.com/google-ads/api/docs/batch-processing
 */
class GoogleBatcher implements PlatformBatcherInterface
{
    private const API_VERSION = 'v15';
    private const BASE_URL = 'https://googleads.googleapis.com';
    private const MAX_BATCH_SIZE = 100;
    private const FLUSH_INTERVAL = 600; // 10 minutes (conservative for daily limits)
    private const REQUEST_TIMEOUT = 120;
    private const STREAM_TIMEOUT = 300; // 5 minutes for streaming

    /**
     * Request types this batcher can handle
     */
    private const SUPPORTED_REQUEST_TYPES = [
        // Account & Campaigns - use SearchStream
        'get_account',
        'get_campaigns',
        'get_ad_groups',
        'get_ads',
        'get_keywords',

        // Performance data - use SearchStream
        'get_campaign_metrics',
        'get_ad_group_metrics',
        'get_keyword_metrics',

        // Assets & Extensions
        'get_assets',
        'get_extensions',

        // Audiences
        'get_audiences',
        'get_user_lists',

        // Conversion tracking
        'get_conversion_actions',
        'get_conversions',

        // Mutate operations - use batch mutate
        'update_campaigns',
        'update_ad_groups',
        'update_keywords',
        'update_ads',
    ];

    /**
     * GAQL query templates for different request types
     */
    private const QUERY_TEMPLATES = [
        'get_account' => "
            SELECT
                customer.id,
                customer.descriptive_name,
                customer.currency_code,
                customer.time_zone,
                customer.test_account
            FROM customer
        ",

        'get_campaigns' => "
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.advertising_channel_type,
                campaign.bidding_strategy_type,
                campaign.campaign_budget,
                campaign.start_date,
                campaign.end_date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions
            FROM campaign
            WHERE campaign.status != 'REMOVED'
            ORDER BY campaign.id DESC
        ",

        'get_ad_groups' => "
            SELECT
                ad_group.id,
                ad_group.name,
                ad_group.status,
                ad_group.type,
                ad_group.campaign,
                ad_group.cpc_bid_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros
            FROM ad_group
            WHERE ad_group.status != 'REMOVED'
            ORDER BY ad_group.id DESC
        ",

        'get_ads' => "
            SELECT
                ad_group_ad.ad.id,
                ad_group_ad.ad.type,
                ad_group_ad.ad.final_urls,
                ad_group_ad.ad.responsive_search_ad.headlines,
                ad_group_ad.ad.responsive_search_ad.descriptions,
                ad_group_ad.status,
                ad_group_ad.ad_group,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros
            FROM ad_group_ad
            WHERE ad_group_ad.status != 'REMOVED'
        ",

        'get_keywords' => "
            SELECT
                ad_group_criterion.criterion_id,
                ad_group_criterion.keyword.text,
                ad_group_criterion.keyword.match_type,
                ad_group_criterion.status,
                ad_group_criterion.cpc_bid_micros,
                ad_group_criterion.ad_group,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions
            FROM ad_group_criterion
            WHERE ad_group_criterion.type = 'KEYWORD'
            AND ad_group_criterion.status != 'REMOVED'
        ",

        'get_campaign_metrics' => "
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
                metrics.average_cpm
            FROM campaign
            WHERE segments.date DURING LAST_30_DAYS
            AND campaign.status != 'REMOVED'
            ORDER BY segments.date DESC
        ",

        'get_conversion_actions' => "
            SELECT
                conversion_action.id,
                conversion_action.name,
                conversion_action.category,
                conversion_action.type,
                conversion_action.status,
                metrics.all_conversions,
                metrics.all_conversions_value
            FROM conversion_action
            WHERE conversion_action.status = 'ENABLED'
        ",

        'get_audiences' => "
            SELECT
                user_list.id,
                user_list.name,
                user_list.type,
                user_list.size_for_display,
                user_list.size_for_search,
                user_list.membership_status
            FROM user_list
            WHERE user_list.membership_status != 'CLOSED'
        ",
    ];

    public function __construct()
    {
    }

    /**
     * Execute a batch of queued requests
     */
    public function executeBatch(string $connectionId, Collection $requests): array
    {
        $connection = PlatformConnection::find($connectionId);

        if (!$connection || !$connection->access_token) {
            Log::warning('GoogleBatcher: Invalid connection or missing token', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Invalid connection or missing token');
        }

        $accessToken = $connection->access_token;
        $customerId = $connection->account_id ? str_replace('-', '', $connection->account_id) : null;

        if (!$customerId) {
            Log::warning('GoogleBatcher: Missing customer ID', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Missing Google Ads customer ID');
        }

        $results = [];

        // Group requests by type for optimal batching strategy
        $grouped = $requests->groupBy('request_type');

        foreach ($grouped as $requestType => $typeRequests) {
            $typeResults = $this->executeByType(
                $requestType,
                $typeRequests,
                $customerId,
                $accessToken
            );
            $results = array_merge($results, $typeResults);
        }

        return $results;
    }

    /**
     * Execute requests by type using the most efficient method
     */
    protected function executeByType(
        string $requestType,
        Collection $requests,
        string $customerId,
        string $accessToken
    ): array {
        // Determine if this is a read (search) or write (mutate) operation
        $isReadOperation = !str_starts_with($requestType, 'update_');

        if ($isReadOperation) {
            return $this->executeSearchStream($requestType, $requests, $customerId, $accessToken);
        } else {
            return $this->executeBatchMutate($requestType, $requests, $customerId, $accessToken);
        }
    }

    /**
     * Execute read requests using SearchStream API
     * Returns all data in a single streaming response
     */
    protected function executeSearchStream(
        string $requestType,
        Collection $requests,
        string $customerId,
        string $accessToken
    ): array {
        Log::info('GoogleBatcher: Using SearchStream API', [
            'request_type' => $requestType,
            'request_count' => $requests->count(),
        ]);

        $results = [];
        $query = $this->buildQuery($requestType, $requests);

        if (!$query) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'Unsupported request type'];
            }
            return $results;
        }

        try {
            // Use searchStream for efficient data retrieval
            $url = self::BASE_URL . '/' . self::API_VERSION
                . '/customers/' . $customerId
                . '/googleAds:searchStream';

            $response = Http::timeout(self::STREAM_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'developer-token' => config('services.google_ads.developer_token'),
                    'login-customer-id' => $customerId,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, ['query' => $query]);

            if ($response->successful()) {
                $data = $response->json();
                $allResults = $this->parseStreamResponse($data);

                // All requests of this type get the same data
                foreach ($requests as $request) {
                    // If request has specific filters, apply them
                    $params = $request->request_params ?? [];
                    if (!empty($params['filters'])) {
                        $filteredResults = $this->filterResults($allResults, $params['filters']);
                        $results[$request->id] = [$requestType => $filteredResults];
                    } else {
                        $results[$request->id] = [$requestType => $allResults];
                    }
                }

                Log::info('GoogleBatcher: SearchStream completed', [
                    'request_type' => $requestType,
                    'results_count' => count($allResults),
                ]);
            } else {
                $error = $response->json('error', []);
                foreach ($requests as $request) {
                    $results[$request->id] = [
                        'error' => $error['message'] ?? 'SearchStream request failed',
                        'code' => $error['code'] ?? $response->status(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('GoogleBatcher: SearchStream exception', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Build GAQL query for request type
     */
    protected function buildQuery(string $requestType, Collection $requests): ?string
    {
        $template = self::QUERY_TEMPLATES[$requestType] ?? null;

        if (!$template) {
            return null;
        }

        // Get any date range params from requests
        $params = $requests->first()?->request_params ?? [];

        // Apply date range if provided
        if (isset($params['start_date']) && isset($params['end_date'])) {
            $dateClause = "segments.date BETWEEN '{$params['start_date']}' AND '{$params['end_date']}'";
            $template = preg_replace('/segments\.date DURING LAST_\d+_DAYS/', $dateClause, $template);
        }

        // Apply limit if provided
        if (isset($params['limit'])) {
            $template .= " LIMIT {$params['limit']}";
        }

        return trim($template);
    }

    /**
     * Parse SearchStream response format
     */
    protected function parseStreamResponse(array $streamData): array
    {
        $results = [];

        // SearchStream returns an array of result batches
        foreach ($streamData as $batch) {
            if (isset($batch['results'])) {
                foreach ($batch['results'] as $row) {
                    $results[] = $row;
                }
            }
        }

        // If response is a single batch (non-streaming fallback)
        if (isset($streamData['results'])) {
            $results = $streamData['results'];
        }

        return $results;
    }

    /**
     * Filter results based on criteria
     */
    protected function filterResults(array $results, array $filters): array
    {
        return array_filter($results, function ($row) use ($filters) {
            foreach ($filters as $field => $value) {
                $rowValue = data_get($row, $field);
                if ($rowValue !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Execute mutate operations using batch processing
     */
    protected function executeBatchMutate(
        string $requestType,
        Collection $requests,
        string $customerId,
        string $accessToken
    ): array {
        Log::info('GoogleBatcher: Using Batch Mutate', [
            'request_type' => $requestType,
            'request_count' => $requests->count(),
        ]);

        $results = [];
        $resourceType = $this->getResourceTypeForMutate($requestType);

        if (!$resourceType) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'Unsupported mutate type'];
            }
            return $results;
        }

        try {
            $url = self::BASE_URL . '/' . self::API_VERSION
                . '/customers/' . $customerId
                . '/' . $resourceType . ':mutate';

            $operations = [];
            $requestMap = []; // Map operation index to request ID

            foreach ($requests->values() as $index => $request) {
                $params = $request->request_params ?? [];
                if (!empty($params['operation'])) {
                    $operations[] = $params['operation'];
                    $requestMap[$index] = $request->id;
                }
            }

            if (empty($operations)) {
                foreach ($requests as $request) {
                    $results[$request->id] = ['error' => 'No operations provided'];
                }
                return $results;
            }

            $response = Http::timeout(self::REQUEST_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'developer-token' => config('services.google_ads.developer_token'),
                    'login-customer-id' => $customerId,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, ['operations' => $operations]);

            if ($response->successful()) {
                $responseData = $response->json();
                $mutateResults = $responseData['results'] ?? [];

                foreach ($requestMap as $index => $requestId) {
                    $mutateResult = $mutateResults[$index] ?? null;
                    if ($mutateResult) {
                        $results[$requestId] = [
                            'success' => true,
                            'resource_name' => $mutateResult['resourceName'] ?? null,
                        ];
                    } else {
                        $results[$requestId] = ['error' => 'No result for operation'];
                    }
                }
            } else {
                $error = $response->json('error', []);
                foreach ($requests as $request) {
                    $results[$request->id] = [
                        'error' => $error['message'] ?? 'Batch mutate failed',
                        'code' => $error['code'] ?? $response->status(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('GoogleBatcher: Batch mutate exception', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get resource type for mutate operations
     */
    protected function getResourceTypeForMutate(string $requestType): ?string
    {
        return match ($requestType) {
            'update_campaigns' => 'campaigns',
            'update_ad_groups' => 'adGroups',
            'update_keywords' => 'adGroupCriteria',
            'update_ads' => 'adGroupAds',
            default => null,
        };
    }

    /**
     * Mark all requests as failed with error message
     */
    protected function markAllFailed(Collection $requests, string $error): array
    {
        $results = [];
        foreach ($requests as $request) {
            $results[$request->id] = ['error' => $error];
        }
        return $results;
    }

    // ===== Interface Implementation =====

    public function getBatchType(): string
    {
        return 'search_stream';
    }

    public function getMaxBatchSize(): int
    {
        return self::MAX_BATCH_SIZE;
    }

    public function getFlushInterval(): int
    {
        return self::FLUSH_INTERVAL;
    }

    public function getPlatform(): string
    {
        return 'google';
    }

    public function canHandle(string $requestType): bool
    {
        return in_array($requestType, self::SUPPORTED_REQUEST_TYPES);
    }

    public function getSupportedRequestTypes(): array
    {
        return self::SUPPORTED_REQUEST_TYPES;
    }
}
