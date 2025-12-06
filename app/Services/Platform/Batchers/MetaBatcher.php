<?php

namespace App\Services\Platform\Batchers;

use App\Models\Platform\PlatformConnection;
use App\Services\Platform\MetaAssetsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MetaBatcher
 *
 * Implements Meta-specific batch optimizations:
 * - Field Expansion: Get multiple related entities in 1 API call
 * - Batch API: Combine up to 50 different requests in 1 HTTP request
 *
 * Expected reduction: 90-95% fewer API calls
 *
 * @see https://developers.facebook.com/docs/graph-api/using-graph-api/#fieldexpansion
 * @see https://developers.facebook.com/docs/graph-api/batch-requests
 */
class MetaBatcher implements PlatformBatcherInterface
{
    private const API_VERSION = 'v21.0';
    private const BASE_URL = 'https://graph.facebook.com';
    private const MAX_BATCH_SIZE = 50;
    private const FLUSH_INTERVAL = 300; // 5 minutes
    private const REQUEST_TIMEOUT = 60;

    /**
     * Request types this batcher can handle
     */
    private const SUPPORTED_REQUEST_TYPES = [
        // Asset requests - use Field Expansion
        'get_all_assets',
        'get_pages',
        'get_instagram_accounts',
        'get_ad_accounts',
        'get_pixels',
        'get_catalogs',
        'get_businesses',
        'get_whatsapp_accounts',

        // Generic requests - use Batch API
        'get_page_details',
        'get_account_details',
        'get_insights',
        'get_campaigns',
        'get_ad_sets',
        'get_ads',
    ];

    public function __construct(
        protected ?MetaAssetsService $assetsService = null
    ) {
        $this->assetsService = $assetsService ?? app(MetaAssetsService::class);
    }

    /**
     * Execute a batch of queued requests
     */
    public function executeBatch(string $connectionId, Collection $requests): array
    {
        $connection = PlatformConnection::find($connectionId);

        if (!$connection || !$connection->access_token) {
            Log::warning('MetaBatcher: Invalid connection or missing token', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Invalid connection or missing token');
        }

        $accessToken = $connection->access_token;
        $results = [];

        // Group requests by type for optimal batching strategy
        $grouped = $requests->groupBy('request_type');

        foreach ($grouped as $requestType => $typeRequests) {
            $typeResults = $this->executeByType($requestType, $typeRequests, $connectionId, $accessToken);
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
        string $connectionId,
        string $accessToken
    ): array {
        return match ($requestType) {
            // Asset types - use Field Expansion via MetaAssetsService
            'get_all_assets' => $this->getAllAssetsWithFieldExpansion($requests, $connectionId, $accessToken),
            'get_pages' => $this->getAssetType($requests, $connectionId, $accessToken, 'pages'),
            'get_instagram_accounts' => $this->getAssetType($requests, $connectionId, $accessToken, 'instagram'),
            'get_ad_accounts' => $this->getAssetType($requests, $connectionId, $accessToken, 'ad_accounts'),
            'get_pixels' => $this->getAssetType($requests, $connectionId, $accessToken, 'pixels'),
            'get_catalogs' => $this->getAssetType($requests, $connectionId, $accessToken, 'catalogs'),
            'get_businesses' => $this->getAssetType($requests, $connectionId, $accessToken, 'businesses'),
            'get_whatsapp_accounts' => $this->getAssetType($requests, $connectionId, $accessToken, 'whatsapp'),

            // Generic requests - use Batch API
            default => $this->executeBatchApi($requests, $accessToken),
        };
    }

    /**
     * Get ALL assets in 1 API call using Field Expansion
     * This is the most efficient method - replaces 4+ separate API calls with 1
     */
    protected function getAllAssetsWithFieldExpansion(
        Collection $requests,
        string $connectionId,
        string $accessToken
    ): array {
        Log::info('MetaBatcher: Using Field Expansion for all assets');

        $results = [];

        try {
            // Use MetaAssetsService which already implements Field Expansion
            $this->assetsService->refreshAll($connectionId);

            // Fetch all asset types
            $pages = $this->assetsService->getPages($connectionId, $accessToken, true);
            $instagram = $this->assetsService->getInstagramAccounts($connectionId, $accessToken, true);
            $adAccounts = $this->assetsService->getAdAccounts($connectionId, $accessToken, true);
            $pixels = $this->assetsService->getPixels($connectionId, $accessToken, true);
            $catalogs = $this->assetsService->getCatalogs($connectionId, $accessToken, true);
            $businesses = $this->assetsService->getBusinesses($connectionId, $accessToken, true);
            $whatsapp = $this->assetsService->getWhatsappAccounts($connectionId, $accessToken, true);

            $allAssets = [
                'pages' => $pages,
                'instagram_accounts' => $instagram,
                'ad_accounts' => $adAccounts,
                'pixels' => $pixels,
                'catalogs' => $catalogs,
                'businesses' => $businesses,
                'whatsapp_accounts' => $whatsapp,
            ];

            // Mark all requests as completed with the combined response
            foreach ($requests as $request) {
                $results[$request->id] = $allAssets;
            }

            Log::info('MetaBatcher: Field Expansion completed', [
                'pages' => count($pages),
                'instagram' => count($instagram),
                'ad_accounts' => count($adAccounts),
                'pixels' => count($pixels),
                'catalogs' => count($catalogs),
            ]);

        } catch (\Exception $e) {
            Log::error('MetaBatcher: Field Expansion failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get a specific asset type via MetaAssetsService
     */
    protected function getAssetType(
        Collection $requests,
        string $connectionId,
        string $accessToken,
        string $assetType
    ): array {
        Log::debug("MetaBatcher: Fetching asset type: {$assetType}");

        $results = [];

        try {
            $assets = match ($assetType) {
                'pages' => $this->assetsService->getPages($connectionId, $accessToken, true),
                'instagram' => $this->assetsService->getInstagramAccounts($connectionId, $accessToken, true),
                'ad_accounts' => $this->assetsService->getAdAccounts($connectionId, $accessToken, true),
                'pixels' => $this->assetsService->getPixels($connectionId, $accessToken, true),
                'catalogs' => $this->assetsService->getCatalogs($connectionId, $accessToken, true),
                'businesses' => $this->assetsService->getBusinesses($connectionId, $accessToken, true),
                'whatsapp' => $this->assetsService->getWhatsappAccounts($connectionId, $accessToken, true),
                default => [],
            };

            foreach ($requests as $request) {
                $results[$request->id] = [$assetType => $assets];
            }

        } catch (\Exception $e) {
            Log::error("MetaBatcher: Failed to fetch {$assetType}", [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Execute generic requests using Meta Batch API
     * Combines up to 50 different requests in 1 HTTP call
     */
    protected function executeBatchApi(Collection $requests, string $accessToken): array
    {
        Log::info('MetaBatcher: Using Batch API', [
            'request_count' => $requests->count(),
        ]);

        $results = [];
        $chunks = $requests->chunk(self::MAX_BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $batch = [];

            foreach ($chunk as $request) {
                $params = $request->request_params ?? [];
                $batch[] = [
                    'method' => $params['method'] ?? 'GET',
                    'relative_url' => $params['endpoint'] ?? '',
                ];
            }

            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batch),
                    ]);

                if ($response->successful()) {
                    $batchResponses = $response->json();

                    foreach ($chunk->values() as $index => $request) {
                        $batchResponse = $batchResponses[$index] ?? null;

                        if ($batchResponse && ($batchResponse['code'] ?? 0) === 200) {
                            $body = json_decode($batchResponse['body'] ?? '{}', true);
                            $results[$request->id] = $body;
                        } else {
                            $errorBody = json_decode($batchResponse['body'] ?? '{}', true);
                            $results[$request->id] = [
                                'error' => $errorBody['error']['message'] ?? 'Batch request failed',
                                'code' => $batchResponse['code'] ?? 500,
                            ];
                        }
                    }
                } else {
                    $error = $response->json('error', []);
                    foreach ($chunk as $request) {
                        $results[$request->id] = [
                            'error' => $error['message'] ?? 'Batch API call failed',
                            'code' => $error['code'] ?? $response->status(),
                        ];
                    }
                }

            } catch (\Exception $e) {
                Log::error('MetaBatcher: Batch API exception', [
                    'error' => $e->getMessage(),
                ]);

                foreach ($chunk as $request) {
                    $results[$request->id] = ['error' => $e->getMessage()];
                }
            }

            // Small delay between batch chunks to respect rate limits
            if ($chunks->count() > 1) {
                usleep(500000); // 500ms
            }
        }

        return $results;
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
        return 'field_expansion';
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
        return 'meta';
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
