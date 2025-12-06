<?php

namespace App\Services\Platform\Batchers;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LinkedInBatcher
 *
 * Implements LinkedIn-specific batch optimizations:
 * - Batch decoration: Get multiple resources with related data in 1 call
 * - Pagination consolidation: Fetch all pages efficiently
 * - Conservative rate limiting due to 100/day limit
 *
 * Expected reduction: 30-50% fewer API calls
 *
 * @see https://learn.microsoft.com/en-us/linkedin/marketing/
 */
class LinkedInBatcher implements PlatformBatcherInterface
{
    private const API_VERSION = '202312';
    private const BASE_URL = 'https://api.linkedin.com/rest';
    private const MAX_BATCH_SIZE = 50;
    private const FLUSH_INTERVAL = 1800; // 30 minutes (very conservative)
    private const REQUEST_TIMEOUT = 60;
    private const PAGE_SIZE = 100;

    /**
     * Request types this batcher can handle
     */
    private const SUPPORTED_REQUEST_TYPES = [
        // Account & Campaigns
        'get_ad_accounts',
        'get_campaigns',
        'get_campaign_groups',
        'get_creatives',

        // Performance data
        'get_analytics',
        'get_campaign_analytics',

        // Targeting & Audiences
        'get_audiences',
        'get_targeting_facets',

        // Conversions
        'get_conversions',

        // Lead Gen Forms
        'get_lead_gen_forms',
        'get_leads',
    ];

    /**
     * Endpoint mappings for LinkedIn API
     */
    private const ENDPOINTS = [
        'get_ad_accounts' => '/adAccounts',
        'get_campaigns' => '/adCampaigns',
        'get_campaign_groups' => '/adCampaignGroups',
        'get_creatives' => '/creatives',
        'get_analytics' => '/adAnalytics',
        'get_campaign_analytics' => '/adAnalytics',
        'get_audiences' => '/dmpSegments',
        'get_targeting_facets' => '/adTargetingFacets',
        'get_conversions' => '/conversions',
        'get_lead_gen_forms' => '/leadForms',
        'get_leads' => '/leadFormResponses',
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
            Log::warning('LinkedInBatcher: Invalid connection or missing token', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Invalid connection or missing token');
        }

        $accessToken = $connection->access_token;
        $accountId = $connection->account_id;

        $results = [];

        // Group requests by type for optimal batching strategy
        $grouped = $requests->groupBy('request_type');

        foreach ($grouped as $requestType => $typeRequests) {
            $typeResults = $this->executeByType(
                $requestType,
                $typeRequests,
                $accountId,
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
        ?string $accountId,
        string $accessToken
    ): array {
        return match ($requestType) {
            'get_analytics', 'get_campaign_analytics' => $this->getAnalyticsWithPivot($requestType, $requests, $accountId, $accessToken),
            'get_leads' => $this->getBatchLeads($requests, $accessToken),
            default => $this->executeStandardRequest($requestType, $requests, $accountId, $accessToken),
        };
    }

    /**
     * Get analytics with pivot - combines multiple metrics in single request
     */
    protected function getAnalyticsWithPivot(
        string $requestType,
        Collection $requests,
        ?string $accountId,
        string $accessToken
    ): array {
        Log::info('LinkedInBatcher: Getting analytics with pivot', [
            'request_type' => $requestType,
            'request_count' => $requests->count(),
        ]);

        $results = [];

        try {
            // Get date range from first request or use defaults
            $params = $requests->first()?->request_params ?? [];
            $startDate = $params['start_date'] ?? now()->subDays(30)->format('Y-m-d');
            $endDate = $params['end_date'] ?? now()->format('Y-m-d');

            $url = self::BASE_URL . '/adAnalytics';

            // Build query params with all metrics at once
            $queryParams = [
                'q' => 'analytics',
                'pivot' => 'CAMPAIGN',
                'dateRange' => "(start:(day:{$this->extractDay($startDate)},month:{$this->extractMonth($startDate)},year:{$this->extractYear($startDate)}),end:(day:{$this->extractDay($endDate)},month:{$this->extractMonth($endDate)},year:{$this->extractYear($endDate)}))",
                'timeGranularity' => 'DAILY',
                'fields' => 'impressions,clicks,costInLocalCurrency,conversions,externalWebsiteConversions,leadGenerationMailContactInfoShares',
            ];

            if ($accountId) {
                $queryParams['accounts'] = "urn:li:sponsoredAccount:{$accountId}";
            }

            $response = Http::timeout(self::REQUEST_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'LinkedIn-Version' => self::API_VERSION,
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'Content-Type' => 'application/json',
                ])
                ->get($url, $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                $elements = $data['elements'] ?? [];

                foreach ($requests as $request) {
                    $results[$request->id] = ['analytics' => $elements];
                }

                Log::info('LinkedInBatcher: Analytics completed', [
                    'elements_count' => count($elements),
                ]);
            } else {
                $error = $response->json();
                foreach ($requests as $request) {
                    $results[$request->id] = [
                        'error' => $error['message'] ?? 'Analytics request failed',
                        'code' => $response->status(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('LinkedInBatcher: Analytics failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get batch leads - combines multiple lead form responses
     */
    protected function getBatchLeads(
        Collection $requests,
        string $accessToken
    ): array {
        Log::info('LinkedInBatcher: Getting batch leads', [
            'request_count' => $requests->count(),
        ]);

        $results = [];

        // Collect all lead form IDs
        $formIds = $requests
            ->map(fn($r) => $r->request_params['form_id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($formIds)) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'No form IDs provided'];
            }
            return $results;
        }

        try {
            $allLeadsByForm = [];

            foreach ($formIds as $formId) {
                $url = self::BASE_URL . '/leadFormResponses';

                $allLeads = [];
                $start = 0;

                do {
                    $response = Http::timeout(self::REQUEST_TIMEOUT)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'LinkedIn-Version' => self::API_VERSION,
                            'X-Restli-Protocol-Version' => '2.0.0',
                            'Content-Type' => 'application/json',
                        ])
                        ->get($url, [
                            'q' => 'form',
                            'form' => "urn:li:leadGenForm:{$formId}",
                            'start' => $start,
                            'count' => self::PAGE_SIZE,
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $elements = $data['elements'] ?? [];
                        $allLeads = array_merge($allLeads, $elements);

                        $paging = $data['paging'] ?? [];
                        $start = ($paging['start'] ?? 0) + ($paging['count'] ?? 0);
                        $hasMore = count($elements) === self::PAGE_SIZE;
                    } else {
                        break;
                    }

                    // Small delay between pages
                    usleep(100000); // 100ms

                } while ($hasMore ?? false);

                $allLeadsByForm[$formId] = $allLeads;
            }

            // Map results back to requests
            foreach ($requests as $request) {
                $formId = $request->request_params['form_id'] ?? null;
                if ($formId && isset($allLeadsByForm[$formId])) {
                    $results[$request->id] = ['leads' => $allLeadsByForm[$formId]];
                } else {
                    $results[$request->id] = ['leads' => []];
                }
            }

            Log::info('LinkedInBatcher: Batch leads completed', [
                'forms_processed' => count($allLeadsByForm),
            ]);

        } catch (\Exception $e) {
            Log::error('LinkedInBatcher: Batch leads failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Execute standard LinkedIn API request with pagination
     */
    protected function executeStandardRequest(
        string $requestType,
        Collection $requests,
        ?string $accountId,
        string $accessToken
    ): array {
        Log::info('LinkedInBatcher: Executing standard request', [
            'request_type' => $requestType,
            'request_count' => $requests->count(),
        ]);

        $results = [];
        $endpoint = self::ENDPOINTS[$requestType] ?? null;

        if (!$endpoint) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'Unsupported request type'];
            }
            return $results;
        }

        try {
            $url = self::BASE_URL . $endpoint;

            $queryParams = [
                'q' => 'search',
                'start' => 0,
                'count' => self::PAGE_SIZE,
            ];

            if ($accountId) {
                $queryParams['account'] = "urn:li:sponsoredAccount:{$accountId}";
            }

            $allData = [];
            $hasMore = true;

            while ($hasMore) {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'LinkedIn-Version' => self::API_VERSION,
                        'X-Restli-Protocol-Version' => '2.0.0',
                        'Content-Type' => 'application/json',
                    ])
                    ->get($url, $queryParams);

                if ($response->successful()) {
                    $data = $response->json();
                    $elements = $data['elements'] ?? [];
                    $allData = array_merge($allData, $elements);

                    $paging = $data['paging'] ?? [];
                    $total = $paging['total'] ?? count($elements);
                    $queryParams['start'] += self::PAGE_SIZE;
                    $hasMore = $queryParams['start'] < $total;
                } else {
                    $hasMore = false;
                }

                // Delay between pages
                if ($hasMore) {
                    usleep(200000); // 200ms (LinkedIn is strict)
                }
            }

            // All requests get the same data
            foreach ($requests as $request) {
                $results[$request->id] = [$requestType => $allData];
            }

            Log::info('LinkedInBatcher: Standard request completed', [
                'request_type' => $requestType,
                'results_count' => count($allData),
            ]);

        } catch (\Exception $e) {
            Log::error('LinkedInBatcher: Standard request failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Helper to extract day from date string
     */
    protected function extractDay(string $date): int
    {
        return (int) date('d', strtotime($date));
    }

    /**
     * Helper to extract month from date string
     */
    protected function extractMonth(string $date): int
    {
        return (int) date('m', strtotime($date));
    }

    /**
     * Helper to extract year from date string
     */
    protected function extractYear(string $date): int
    {
        return (int) date('Y', strtotime($date));
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
        return 'pagination';
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
        return 'linkedin';
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
