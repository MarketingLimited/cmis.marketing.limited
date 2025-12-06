<?php

namespace App\Services\Platform\Batchers;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TikTokBatcher
 *
 * Implements TikTok-specific batch optimizations:
 * - Bulk Advertiser Info: Up to 100 advertisers per request
 * - Bulk Conversion Events: Up to 2000 events per request
 * - Combined pagination: Fetch multiple pages efficiently
 *
 * Expected reduction: 40-60% fewer API calls
 *
 * @see https://ads.tiktok.com/marketing_api/docs
 */
class TikTokBatcher implements PlatformBatcherInterface
{
    private const API_VERSION = 'v1.3';
    private const BASE_URL = 'https://business-api.tiktok.com/open_api';
    private const MAX_BATCH_SIZE = 100;
    private const FLUSH_INTERVAL = 600; // 10 minutes
    private const REQUEST_TIMEOUT = 60;
    private const BULK_EVENTS_LIMIT = 2000;

    /**
     * Request types this batcher can handle
     */
    private const SUPPORTED_REQUEST_TYPES = [
        // Account & Advertiser data
        'get_advertisers',
        'get_advertiser_info',
        'get_campaigns',
        'get_ad_groups',
        'get_ads',

        // Performance data
        'get_campaign_metrics',
        'get_ad_group_metrics',
        'get_ad_metrics',

        // Assets & Creatives
        'get_creatives',
        'get_images',
        'get_videos',

        // Audiences
        'get_audiences',
        'get_custom_audiences',

        // Conversion events
        'upload_conversions',
    ];

    /**
     * Endpoint mappings for TikTok API
     */
    private const ENDPOINTS = [
        'get_advertisers' => '/advertiser/info/',
        'get_advertiser_info' => '/advertiser/info/',
        'get_campaigns' => '/campaign/get/',
        'get_ad_groups' => '/adgroup/get/',
        'get_ads' => '/ad/get/',
        'get_campaign_metrics' => '/report/integrated/get/',
        'get_ad_group_metrics' => '/report/integrated/get/',
        'get_ad_metrics' => '/report/integrated/get/',
        'get_creatives' => '/creative/get/',
        'get_images' => '/file/image/get/',
        'get_videos' => '/file/video/get/',
        'get_audiences' => '/audience/list/',
        'get_custom_audiences' => '/custom_audience/list/',
        'upload_conversions' => '/pixel/track/',
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
            Log::warning('TikTokBatcher: Invalid connection or missing token', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Invalid connection or missing token');
        }

        $accessToken = $connection->access_token;
        $advertiserId = $connection->account_id;

        if (!$advertiserId) {
            Log::warning('TikTokBatcher: Missing advertiser ID', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Missing TikTok advertiser ID');
        }

        $results = [];

        // Group requests by type for optimal batching strategy
        $grouped = $requests->groupBy('request_type');

        foreach ($grouped as $requestType => $typeRequests) {
            $typeResults = $this->executeByType(
                $requestType,
                $typeRequests,
                $advertiserId,
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
        string $advertiserId,
        string $accessToken
    ): array {
        return match ($requestType) {
            'get_advertisers', 'get_advertiser_info' => $this->getBulkAdvertiserInfo($requests, $accessToken),
            'upload_conversions' => $this->uploadBulkConversions($requests, $advertiserId, $accessToken),
            default => $this->executeStandardRequest($requestType, $requests, $advertiserId, $accessToken),
        };
    }

    /**
     * Get bulk advertiser info (up to 100 advertisers)
     */
    protected function getBulkAdvertiserInfo(
        Collection $requests,
        string $accessToken
    ): array {
        Log::info('TikTokBatcher: Getting bulk advertiser info', [
            'request_count' => $requests->count(),
        ]);

        $results = [];

        // Collect all advertiser IDs from requests
        $advertiserIds = $requests
            ->map(fn($r) => $r->request_params['advertiser_id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($advertiserIds)) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'No advertiser IDs provided'];
            }
            return $results;
        }

        try {
            // Chunk to respect bulk limit
            $chunks = array_chunk($advertiserIds, self::MAX_BATCH_SIZE);

            $allAdvertiserData = [];
            foreach ($chunks as $chunk) {
                $url = self::BASE_URL . '/' . self::API_VERSION . '/advertiser/info/';

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'Access-Token' => $accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->get($url, [
                        'advertiser_ids' => json_encode($chunk),
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['code'] ?? 0) === 0) {
                        $advertiserList = $data['data']['list'] ?? [];
                        foreach ($advertiserList as $advertiser) {
                            $allAdvertiserData[$advertiser['advertiser_id']] = $advertiser;
                        }
                    }
                }

                // Small delay between chunks
                if (count($chunks) > 1) {
                    usleep(100000); // 100ms
                }
            }

            // Map results back to requests
            foreach ($requests as $request) {
                $reqAdvertiserId = $request->request_params['advertiser_id'] ?? null;
                if ($reqAdvertiserId && isset($allAdvertiserData[$reqAdvertiserId])) {
                    $results[$request->id] = ['advertiser' => $allAdvertiserData[$reqAdvertiserId]];
                } else {
                    $results[$request->id] = ['error' => 'Advertiser not found'];
                }
            }

            Log::info('TikTokBatcher: Bulk advertiser info completed', [
                'advertisers_fetched' => count($allAdvertiserData),
            ]);

        } catch (\Exception $e) {
            Log::error('TikTokBatcher: Bulk advertiser info failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Upload bulk conversion events (up to 2000 per request)
     */
    protected function uploadBulkConversions(
        Collection $requests,
        string $advertiserId,
        string $accessToken
    ): array {
        Log::info('TikTokBatcher: Uploading bulk conversions', [
            'request_count' => $requests->count(),
        ]);

        $results = [];

        // Collect all conversion events
        $allEvents = [];
        $requestEventMap = []; // Track which events belong to which request

        foreach ($requests as $request) {
            $events = $request->request_params['events'] ?? [];
            foreach ($events as $event) {
                $allEvents[] = $event;
                $requestEventMap[count($allEvents) - 1] = $request->id;
            }
        }

        if (empty($allEvents)) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'No events provided'];
            }
            return $results;
        }

        try {
            // Chunk to respect bulk limit
            $chunks = array_chunk($allEvents, self::BULK_EVENTS_LIMIT);
            $totalSuccess = 0;
            $totalFailed = 0;

            foreach ($chunks as $chunkIndex => $chunk) {
                $url = self::BASE_URL . '/' . self::API_VERSION . '/pixel/batch/';

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'Access-Token' => $accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, [
                        'advertiser_id' => $advertiserId,
                        'batch' => $chunk,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['code'] ?? 0) === 0) {
                        $totalSuccess += count($chunk);
                    } else {
                        $totalFailed += count($chunk);
                    }
                } else {
                    $totalFailed += count($chunk);
                }

                // Small delay between chunks
                if (count($chunks) > 1 && $chunkIndex < count($chunks) - 1) {
                    usleep(200000); // 200ms
                }
            }

            // Mark all requests based on overall success
            foreach ($requests as $request) {
                $results[$request->id] = [
                    'success' => $totalFailed === 0,
                    'events_uploaded' => $totalSuccess,
                    'events_failed' => $totalFailed,
                ];
            }

            Log::info('TikTokBatcher: Bulk conversions completed', [
                'success' => $totalSuccess,
                'failed' => $totalFailed,
            ]);

        } catch (\Exception $e) {
            Log::error('TikTokBatcher: Bulk conversions failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Execute standard TikTok API request with pagination
     */
    protected function executeStandardRequest(
        string $requestType,
        Collection $requests,
        string $advertiserId,
        string $accessToken
    ): array {
        Log::info('TikTokBatcher: Executing standard request', [
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
            $url = self::BASE_URL . '/' . self::API_VERSION . $endpoint;

            // Build params from first request, apply to all
            $params = [
                'advertiser_id' => $advertiserId,
                'page_size' => 100,
            ];

            // Add type-specific params
            if (str_contains($requestType, 'metrics')) {
                $params['service_type'] = 'AUCTION';
                $params['data_level'] = $this->getDataLevel($requestType);
                $params['dimensions'] = json_encode(['stat_time_day']);
                $params['metrics'] = json_encode([
                    'spend', 'impressions', 'clicks', 'ctr',
                    'conversion', 'cost_per_conversion', 'conversion_rate',
                ]);
            }

            $allData = [];
            $page = 1;
            $hasMore = true;

            while ($hasMore) {
                $params['page'] = $page;

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'Access-Token' => $accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['code'] ?? 0) === 0) {
                        $list = $data['data']['list'] ?? [];
                        $allData = array_merge($allData, $list);

                        $pageInfo = $data['data']['page_info'] ?? [];
                        $totalPage = $pageInfo['total_page'] ?? 1;
                        $hasMore = $page < $totalPage;
                        $page++;
                    } else {
                        $hasMore = false;
                    }
                } else {
                    $hasMore = false;
                }

                // Small delay between pages
                if ($hasMore) {
                    usleep(100000); // 100ms
                }
            }

            // All requests get the same data
            foreach ($requests as $request) {
                $results[$request->id] = [$requestType => $allData];
            }

            Log::info('TikTokBatcher: Standard request completed', [
                'request_type' => $requestType,
                'results_count' => count($allData),
            ]);

        } catch (\Exception $e) {
            Log::error('TikTokBatcher: Standard request failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get data level for metrics request
     */
    protected function getDataLevel(string $requestType): string
    {
        return match ($requestType) {
            'get_campaign_metrics' => 'AUCTION_CAMPAIGN',
            'get_ad_group_metrics' => 'AUCTION_ADGROUP',
            'get_ad_metrics' => 'AUCTION_AD',
            default => 'AUCTION_CAMPAIGN',
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
        return 'bulk';
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
        return 'tiktok';
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
