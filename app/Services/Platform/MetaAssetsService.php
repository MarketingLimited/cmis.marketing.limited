<?php

namespace App\Services\Platform;

use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching Meta (Facebook/Instagram) Business Manager assets.
 *
 * Features:
 * - Cursor-based pagination to fetch unlimited assets
 * - Three-tier caching: Memory Cache (15min) → Database (6hr) → Platform API
 * - Database persistence for cross-org asset sharing
 * - Parallel-friendly design for AJAX loading
 *
 * @see https://developers.facebook.com/docs/graph-api/using-graph-api/#paging
 */
class MetaAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const API_VERSION = 'v21.0';
    private const BASE_URL = 'https://graph.facebook.com';
    private const THREADS_BASE_URL = 'https://graph.threads.net';
    private const MAX_PAGES = 50; // Safety limit: 50 pages x 100 items = 5000 max (increased to fetch ALL assets)
    private const ITEMS_PER_PAGE = 100;
    private const REQUEST_TIMEOUT = 30;
    private const MAX_BUSINESSES = 200; // Limit businesses to prevent API exhaustion (increased for large accounts)
    private const DELAY_BETWEEN_REQUESTS_MS = 100; // 100ms delay between API calls
    private const BATCH_SIZE = 50;                    // Meta allows up to 50 requests per batch
    private const DELAY_BETWEEN_BATCHES_MS = 500;     // 500ms delay between batch requests

    /**
     * Repository for database persistence (optional - for three-tier caching)
     */
    protected ?PlatformAssetRepositoryInterface $repository = null;

    /**
     * Organization ID for access tracking (set per-request)
     */
    protected ?string $orgId = null;

    /**
     * Constructor with optional repository injection for database persistence
     */
    public function __construct(?PlatformAssetRepositoryInterface $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Set organization ID for access tracking
     */
    public function setOrgId(?string $orgId): self
    {
        $this->orgId = $orgId;
        return $this;
    }

    /**
     * Persist assets to database (if repository is configured)
     *
     * @param string $connectionId Connection UUID
     * @param string $assetType Asset type (page, instagram, ad_account, etc.)
     * @param array $assets Array of asset data
     * @return void
     */
    protected function persistAssets(string $connectionId, string $assetType, array $assets): void
    {
        if (!$this->repository || empty($assets)) {
            return;
        }

        try {
            $count = $this->repository->bulkUpsert('meta', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId) {
                foreach ($assets as $assetData) {
                    $asset = $this->repository->findOrCreate(
                        'meta',
                        $this->extractAssetId($assetData, $assetType),
                        $assetType,
                        $assetData
                    );

                    $this->repository->recordOrgAccess(
                        $this->orgId,
                        $asset->asset_id,
                        $connectionId,
                        [
                            'access_types' => $this->inferAccessTypes($assetData),
                            'permissions' => $assetData['permitted_tasks'] ?? [],
                            'roles' => $assetData['roles'] ?? [],
                        ]
                    );
                }
            }

            Log::debug('Meta assets persisted to database', [
                'asset_type' => $assetType,
                'count' => $count,
                'connection_id' => $connectionId,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to persist Meta assets to database', [
                'asset_type' => $assetType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // DATABASE-FIRST CACHING STRATEGY
    // =========================================================================
    // When users re-authenticate, we check database first to avoid redundant API calls.
    // Fresh assets (< 6 hours old) are returned from DB; only new/stale assets are fetched.
    // =========================================================================

    /**
     * Hours before asset data is considered stale and needs refresh
     */
    private const DB_FRESHNESS_HOURS = 6;

    /**
     * Get existing fresh assets from database by type.
     * Returns assets that were synced within the freshness threshold.
     *
     * @param string $assetType The asset type (page, instagram, ad_account, etc.)
     * @param int $freshHours Hours to consider data fresh (default: 6)
     * @return array<string, array> Map of platform_asset_id => asset_data
     */
    protected function getExistingFreshAssets(string $assetType, int $freshHours = self::DB_FRESHNESS_HOURS): array
    {
        if (!$this->repository) {
            return [];
        }

        try {
            $assets = $this->repository->getByPlatformAndType('meta', $assetType);
            $freshAssets = [];

            foreach ($assets as $asset) {
                // Check if asset is fresh (synced within threshold)
                if ($asset->last_synced_at && $asset->last_synced_at->isAfter(now()->subHours($freshHours))) {
                    $freshAssets[$asset->platform_asset_id] = $asset->asset_data ?? [];
                }
            }

            Log::debug('Got fresh assets from database', [
                'asset_type' => $assetType,
                'total_in_db' => $assets->count(),
                'fresh_count' => count($freshAssets),
                'freshness_hours' => $freshHours,
            ]);

            return $freshAssets;
        } catch (\Exception $e) {
            Log::warning('Failed to get existing assets from database', [
                'asset_type' => $assetType,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get IDs of assets that exist in database (for comparison with API results)
     *
     * @param string $assetType The asset type
     * @return array<string> List of platform_asset_ids that exist in DB
     */
    protected function getExistingAssetIds(string $assetType): array
    {
        if (!$this->repository) {
            return [];
        }

        try {
            $assets = $this->repository->getByPlatformAndType('meta', $assetType);
            return $assets->pluck('platform_asset_id')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if any assets need fetching from API.
     * Returns true if we have no fresh data or if this is a new connection.
     *
     * @param string $connectionId The connection ID
     * @param string $assetType The asset type
     * @return bool True if API call is needed
     */
    protected function shouldFetchFromApi(string $connectionId, string $assetType): bool
    {
        // Always fetch if no repository configured (no DB caching)
        if (!$this->repository) {
            return true;
        }

        try {
            // Check if we have any fresh assets for this asset type
            $freshAssets = $this->getExistingFreshAssets($assetType);

            // If no fresh assets, we need to fetch from API
            if (empty($freshAssets)) {
                Log::debug('No fresh assets in DB, will fetch from API', [
                    'connection_id' => $connectionId,
                    'asset_type' => $assetType,
                ]);
                return true;
            }

            // We have fresh data, no need to call API
            Log::info('Using fresh assets from database (skipping API call)', [
                'connection_id' => $connectionId,
                'asset_type' => $assetType,
                'fresh_count' => count($freshAssets),
            ]);
            return false;
        } catch (\Exception $e) {
            // On error, default to fetching from API
            return true;
        }
    }

    /**
     * Merge API results with existing database data.
     * Only fetches details for NEW assets; returns DB data for known assets.
     *
     * @param array $apiAssetIds IDs discovered from API (lightweight call)
     * @param string $assetType The asset type
     * @param callable $fetchDetailsCallback Callback to fetch details for new assets
     * @return array Combined array of all assets (DB + new from API)
     */
    protected function mergeWithExistingAssets(
        array $apiAssetIds,
        string $assetType,
        callable $fetchDetailsCallback
    ): array {
        $freshDbAssets = $this->getExistingFreshAssets($assetType);
        $knownIds = array_keys($freshDbAssets);

        // Find NEW asset IDs (in API but not in fresh DB data)
        $newAssetIds = array_diff($apiAssetIds, $knownIds);

        Log::info('Smart caching: merging API with DB assets', [
            'asset_type' => $assetType,
            'api_ids' => count($apiAssetIds),
            'fresh_in_db' => count($knownIds),
            'new_to_fetch' => count($newAssetIds),
            'api_calls_saved' => count($knownIds),
        ]);

        // Start with existing fresh assets from DB
        $allAssets = array_values($freshDbAssets);

        // Only fetch details for NEW assets
        if (!empty($newAssetIds)) {
            $newAssets = $fetchDetailsCallback($newAssetIds);
            $allAssets = array_merge($allAssets, $newAssets);
        }

        return $allAssets;
    }

    // =========================================================================
    // TWO-PHASE INCREMENTAL SYNC (Optimized for large accounts: 400+ pages)
    // =========================================================================
    // Phase 1: Fetch just IDs (lightweight, single paginated call)
    // Phase 2: Check database - which IDs are new/stale?
    // Phase 3: Batch fetch ONLY new/stale assets (50 per batch)
    // =========================================================================

    /**
     * Fetch just asset IDs from an endpoint (lightweight call).
     * Used for phase 1 of incremental sync.
     *
     * @param string $endpoint The API endpoint
     * @param string $accessToken Access token
     * @param int $limit Items per page (max 500)
     * @return array<string> List of asset IDs
     */
    protected function fetchAssetIdsOnly(string $endpoint, string $accessToken, int $limit = 500): array
    {
        $ids = [];
        $url = $endpoint;
        $pageCount = 0;

        while ($url && $pageCount < self::MAX_PAGES) {
            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get($url, $pageCount === 0 ? [
                        'access_token' => $accessToken,
                        'fields' => 'id',
                        'limit' => $limit,
                    ] : []);

                if (!$response->successful()) {
                    break;
                }

                $data = $response->json();
                foreach ($data['data'] ?? [] as $item) {
                    if (isset($item['id'])) {
                        $ids[] = $item['id'];
                    }
                }

                $url = $data['paging']['next'] ?? null;
                $pageCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to fetch asset IDs', ['error' => $e->getMessage()]);
                break;
            }
        }

        Log::debug('Fetched asset IDs (lightweight)', [
            'endpoint' => $endpoint,
            'total_ids' => count($ids),
            'pages_fetched' => $pageCount,
        ]);

        return $ids;
    }

    /**
     * Batch fetch page details for a list of page IDs.
     * Uses Meta Batch API to fetch 50 pages per request.
     *
     * @param array $pageIds List of page IDs to fetch
     * @param string $accessToken Access token
     * @return array Array of page data
     */
    protected function batchFetchPageDetails(array $pageIds, string $accessToken): array
    {
        if (empty($pageIds)) {
            return [];
        }

        $pages = [];
        $fields = 'id,name,category,picture{url},access_token,instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}';
        $chunks = array_chunk($pageIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($chunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            $batchRequests = [];
            foreach ($chunk as $pageId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $pageId . '?' . http_build_query(['fields' => $fields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if ($response->successful()) {
                    foreach ($response->json() ?? [] as $index => $batchResponse) {
                        if (($batchResponse['code'] ?? 0) === 200) {
                            $body = json_decode($batchResponse['body'] ?? '{}', true);
                            if (!empty($body['id'])) {
                                $pages[] = [
                                    'id' => $body['id'],
                                    'name' => $body['name'] ?? 'Unknown Page',
                                    'category' => $body['category'] ?? null,
                                    'picture' => $body['picture']['data']['url'] ?? null,
                                    'has_instagram' => isset($body['instagram_business_account']),
                                    'instagram_id' => $body['instagram_business_account']['id'] ?? null,
                                    'instagram_data' => $body['instagram_business_account'] ?? null,
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Batch page fetch failed', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch fetched page details', [
            'requested' => count($pageIds),
            'fetched' => count($pages),
            'batches_used' => $batchNumber,
            'api_calls' => $batchNumber, // Each batch = 1 API call
        ]);

        return $pages;
    }

    /**
     * Incremental sync for pages using two-phase approach.
     * Optimized for users with 400+ pages.
     *
     * Phase 1: Fetch just IDs (1 paginated call for up to 2500 pages)
     * Phase 2: Check DB for existing fresh data
     * Phase 3: Batch fetch only NEW pages (50 per batch)
     *
     * @param string $accessToken Access token
     * @param string $connectionId Connection ID
     * @return array Array of pages with instagram data
     */
    protected function incrementalSyncPages(string $accessToken, string $connectionId): array
    {
        // Phase 1: Get all page IDs (lightweight - just IDs)
        $allPageIds = $this->fetchAssetIdsOnly(
            self::BASE_URL . '/' . self::API_VERSION . '/me/accounts',
            $accessToken,
            500 // Get up to 500 per page
        );

        if (empty($allPageIds)) {
            Log::info('No pages found in incremental sync');
            return [];
        }

        // Phase 2: Check which pages we already have fresh data for
        $freshPages = $this->getExistingFreshAssets('page');
        $freshIds = array_keys($freshPages);

        // Find pages that need fetching (new or stale)
        $newPageIds = array_diff($allPageIds, $freshIds);

        Log::info('Incremental page sync analysis', [
            'total_pages' => count($allPageIds),
            'fresh_in_db' => count($freshIds),
            'new_to_fetch' => count($newPageIds),
            'api_calls_saved' => count($freshIds),
        ]);

        // Start with fresh pages from DB
        $allPages = array_values($freshPages);

        // Phase 3: Batch fetch only NEW pages
        if (!empty($newPageIds)) {
            $newPages = $this->batchFetchPageDetails($newPageIds, $accessToken);
            $allPages = array_merge($allPages, $newPages);
        }

        return $allPages;
    }

    /**
     * Batch fetch business asset details (pages, Instagram, catalogs, etc.)
     * Used when field expansion on /me/businesses times out or returns incomplete data.
     *
     * @param array $businessIds List of business IDs
     * @param string $accessToken Access token
     * @param array $fieldsToFetch Fields to include in response
     * @return array Array of business data
     */
    protected function batchFetchBusinessDetails(array $businessIds, string $accessToken, array $fieldsToFetch): array
    {
        if (empty($businessIds)) {
            return [];
        }

        $businesses = [];
        $fields = implode(',', $fieldsToFetch);
        $chunks = array_chunk($businessIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($chunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            $batchRequests = [];
            foreach ($chunk as $businessId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $businessId . '?' . http_build_query(['fields' => $fields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if ($response->successful()) {
                    foreach ($response->json() ?? [] as $batchResponse) {
                        if (($batchResponse['code'] ?? 0) === 200) {
                            $body = json_decode($batchResponse['body'] ?? '{}', true);
                            if (!empty($body['id'])) {
                                $businesses[] = $body;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Batch business fetch failed', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch fetched business details', [
            'requested' => count($businessIds),
            'fetched' => count($businesses),
            'batches_used' => $batchNumber,
        ]);

        return $businesses;
    }

    /**
     * Batch fetch ad account details for a list of ad account IDs.
     * Uses Meta Batch API to fetch 50 ad accounts per request.
     *
     * @param array $adAccountIds List of ad account IDs (format: act_XXXXX)
     * @param string $accessToken Access token
     * @return array Array of ad account data with pixels and custom conversions
     */
    protected function batchFetchAdAccountDetailsIncremental(array $adAccountIds, string $accessToken): array
    {
        if (empty($adAccountIds)) {
            return ['ad_accounts' => [], 'pixels' => [], 'custom_conversions' => []];
        }

        $adAccounts = [];
        $pixels = [];
        $customConversions = [];
        $fields = 'id,name,account_id,account_status,currency,timezone_name,spend_cap,amount_spent,balance,adspixels.limit(50){id,name,creation_time,last_fired_time},customconversions.limit(50){id,name,custom_event_type,pixel{id}}';
        $chunks = array_chunk($adAccountIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($chunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            $batchRequests = [];
            foreach ($chunk as $adAccountId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $adAccountId . '?' . http_build_query(['fields' => $fields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if ($response->successful()) {
                    foreach ($response->json() ?? [] as $batchResponse) {
                        if (($batchResponse['code'] ?? 0) === 200) {
                            $body = json_decode($batchResponse['body'] ?? '{}', true);
                            if (!empty($body['id'])) {
                                $accountId = $body['id'];
                                $adAccounts[] = [
                                    'id' => $accountId,
                                    'account_id' => $body['account_id'] ?? str_replace('act_', '', $accountId),
                                    'name' => $body['name'] ?? 'Unknown',
                                    'status' => $this->getAccountStatusLabel($body['account_status'] ?? 0),
                                    'status_code' => $body['account_status'] ?? 0,
                                    'currency' => $body['currency'] ?? 'USD',
                                    'timezone' => $body['timezone_name'] ?? 'UTC',
                                    'spend_cap' => $body['spend_cap'] ?? null,
                                    'amount_spent' => $body['amount_spent'] ?? '0',
                                    'balance' => $body['balance'] ?? '0',
                                ];

                                // Extract pixels
                                foreach ($body['adspixels']['data'] ?? [] as $pixel) {
                                    $pixels[] = [
                                        'id' => $pixel['id'],
                                        'name' => $pixel['name'] ?? 'Unnamed Pixel',
                                        'ad_account_id' => $accountId,
                                        'creation_time' => $pixel['creation_time'] ?? null,
                                        'last_fired_time' => $pixel['last_fired_time'] ?? null,
                                    ];
                                }

                                // Extract custom conversions
                                foreach ($body['customconversions']['data'] ?? [] as $conversion) {
                                    $customConversions[] = [
                                        'id' => $conversion['id'],
                                        'name' => $conversion['name'] ?? 'Unnamed Conversion',
                                        'custom_event_type' => $conversion['custom_event_type'] ?? null,
                                        'pixel_id' => $conversion['pixel']['id'] ?? null,
                                        'ad_account_id' => $accountId,
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Batch ad account fetch failed', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch fetched ad account details', [
            'requested' => count($adAccountIds),
            'fetched' => count($adAccounts),
            'pixels' => count($pixels),
            'custom_conversions' => count($customConversions),
            'batches_used' => $batchNumber,
        ]);

        return [
            'ad_accounts' => $adAccounts,
            'pixels' => $pixels,
            'custom_conversions' => $customConversions,
        ];
    }

    /**
     * Incremental sync for ad accounts using two-phase approach.
     * Optimized for users with many ad accounts.
     *
     * @param string $accessToken Access token
     * @param string $connectionId Connection ID
     * @return array Array with ad_accounts, pixels, custom_conversions
     */
    protected function incrementalSyncAdAccounts(string $accessToken, string $connectionId): array
    {
        // Phase 1: Get all ad account IDs (lightweight)
        $allAdAccountIds = $this->fetchAssetIdsOnly(
            self::BASE_URL . '/' . self::API_VERSION . '/me/adaccounts',
            $accessToken,
            500
        );

        if (empty($allAdAccountIds)) {
            Log::info('No ad accounts found in incremental sync');
            return ['ad_accounts' => [], 'pixels' => [], 'custom_conversions' => []];
        }

        // Phase 2: Check which ad accounts we already have fresh data for
        $freshAdAccounts = $this->getExistingFreshAssets('ad_account');
        $freshIds = array_keys($freshAdAccounts);
        $freshPixels = $this->getExistingFreshAssets('pixel');
        $freshConversions = $this->getExistingFreshAssets('custom_conversion');

        // Find ad accounts that need fetching (new or stale)
        $newAdAccountIds = array_diff($allAdAccountIds, $freshIds);

        Log::info('Incremental ad account sync analysis', [
            'total_ad_accounts' => count($allAdAccountIds),
            'fresh_in_db' => count($freshIds),
            'new_to_fetch' => count($newAdAccountIds),
            'api_calls_saved' => count($freshIds),
        ]);

        // Start with fresh data from DB
        $allAdAccounts = array_values($freshAdAccounts);
        $allPixels = array_values($freshPixels);
        $allConversions = array_values($freshConversions);

        // Phase 3: Batch fetch only NEW ad accounts
        if (!empty($newAdAccountIds)) {
            $newData = $this->batchFetchAdAccountDetailsIncremental($newAdAccountIds, $accessToken);
            $allAdAccounts = array_merge($allAdAccounts, $newData['ad_accounts'] ?? []);
            $allPixels = array_merge($allPixels, $newData['pixels'] ?? []);
            $allConversions = array_merge($allConversions, $newData['custom_conversions'] ?? []);
        }

        return [
            'ad_accounts' => $allAdAccounts,
            'pixels' => $allPixels,
            'custom_conversions' => $allConversions,
        ];
    }

    /**
     * Batch fetch Instagram account details for a list of Instagram IDs.
     *
     * @param array $instagramIds List of Instagram account IDs
     * @param string $accessToken Access token
     * @return array Array of Instagram account data
     */
    protected function batchFetchInstagramDetails(array $instagramIds, string $accessToken): array
    {
        if (empty($instagramIds)) {
            return [];
        }

        $accounts = [];
        $fields = 'id,username,name,profile_picture_url,followers_count,follows_count,media_count,biography';
        $chunks = array_chunk($instagramIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($chunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            $batchRequests = [];
            foreach ($chunk as $igId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $igId . '?' . http_build_query(['fields' => $fields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if ($response->successful()) {
                    foreach ($response->json() ?? [] as $batchResponse) {
                        if (($batchResponse['code'] ?? 0) === 200) {
                            $body = json_decode($batchResponse['body'] ?? '{}', true);
                            if (!empty($body['id'])) {
                                $accounts[] = [
                                    'id' => $body['id'],
                                    'username' => $body['username'] ?? null,
                                    'name' => $body['name'] ?? $body['username'] ?? 'Unknown',
                                    'profile_picture' => $body['profile_picture_url'] ?? null,
                                    'followers_count' => $body['followers_count'] ?? 0,
                                    'follows_count' => $body['follows_count'] ?? 0,
                                    'media_count' => $body['media_count'] ?? 0,
                                    'biography' => $body['biography'] ?? null,
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Batch Instagram fetch failed', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch fetched Instagram details', [
            'requested' => count($instagramIds),
            'fetched' => count($accounts),
            'batches_used' => $batchNumber,
        ]);

        return $accounts;
    }

    /**
     * Universal incremental sync for any asset type.
     * Works for pages, Instagram, ad accounts, catalogs, etc.
     *
     * @param string $assetType Asset type (page, instagram, ad_account, etc.)
     * @param string $endpoint API endpoint to fetch IDs from
     * @param string $accessToken Access token
     * @param callable $batchFetchCallback Callback to batch fetch details
     * @param string $connectionId Connection ID for caching
     * @return array Merged array of fresh DB data + new API data
     */
    protected function universalIncrementalSync(
        string $assetType,
        string $endpoint,
        string $accessToken,
        callable $batchFetchCallback,
        string $connectionId
    ): array {
        // Phase 1: Get all asset IDs (lightweight)
        $allIds = $this->fetchAssetIdsOnly($endpoint, $accessToken, 500);

        if (empty($allIds)) {
            Log::info("No {$assetType} found in incremental sync");
            return [];
        }

        // Phase 2: Check which assets we already have fresh data for
        $freshAssets = $this->getExistingFreshAssets($assetType);
        $freshIds = array_keys($freshAssets);

        // Find assets that need fetching (new or stale)
        $newIds = array_diff($allIds, $freshIds);

        Log::info("Incremental {$assetType} sync analysis", [
            'asset_type' => $assetType,
            'total_assets' => count($allIds),
            'fresh_in_db' => count($freshIds),
            'new_to_fetch' => count($newIds),
            'api_calls_saved' => count($freshIds),
            'batch_calls_needed' => empty($newIds) ? 0 : ceil(count($newIds) / self::BATCH_SIZE),
        ]);

        // Start with fresh assets from DB
        $allAssets = array_values($freshAssets);

        // Phase 3: Batch fetch only NEW assets
        if (!empty($newIds)) {
            $newAssets = $batchFetchCallback($newIds);
            $allAssets = array_merge($allAssets, $newAssets);
        }

        return $allAssets;
    }

    /**
     * Batch fetch business details with ALL embedded assets.
     * Optimized for both System User tokens and Normal User tokens.
     * Fetches: catalogs, whatsapp, pages, instagram, offline_event_sets
     *
     * @param array $businessIds List of business IDs to fetch
     * @param string $accessToken Access token
     * @param bool $isSystemUser Whether this is a System User token (can access more fields)
     * @return array Structured data with all asset types
     */
    protected function batchFetchBusinessDetailsWithAssets(
        array $businessIds,
        string $accessToken,
        bool $isSystemUser = false
    ): array {
        if (empty($businessIds)) {
            return [
                'businesses' => [],
                'catalogs' => [],
                'whatsapp' => [],
                'offline_event_sets' => [],
                'pages' => [],
                'instagram' => [],
            ];
        }

        $businesses = [];
        $catalogs = [];
        $whatsappAccounts = [];
        $offlineEventSets = [];
        $businessPages = [];
        $businessInstagram = [];

        // Base fields for all token types
        $baseFields = 'id,name,verification_status,' .
            'owned_product_catalogs.limit(100){id,name,product_count,vertical},' .
            'client_product_catalogs.limit(100){id,name,product_count,vertical},' .
            'owned_whatsapp_business_accounts.limit(100){id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating,code_verification_status}},' .
            'owned_pages.limit(100){id,name,category,instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}},' .
            'client_pages.limit(100){id,name,category,instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}}';

        // Extended fields for System User tokens
        $fields = $isSystemUser
            ? $baseFields . ',offline_conversion_data_sets.limit(100){id,name,description,upload_rate,duplicate_entries,match_rate_approx,event_stats,data_origin}'
            : $baseFields;

        $chunks = array_chunk($businessIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($chunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            $batchRequests = [];
            foreach ($chunk as $businessId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $businessId . '?' . http_build_query(['fields' => $fields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if ($response->successful()) {
                    foreach ($response->json() ?? [] as $batchResponse) {
                        if (($batchResponse['code'] ?? 0) === 200) {
                            $business = json_decode($batchResponse['body'] ?? '{}', true);
                            if (empty($business['id'])) continue;

                            $businessId = $business['id'];
                            $businessName = $business['name'] ?? 'Unknown Business';

                            // Store business info
                            $businesses[] = [
                                'id' => $businessId,
                                'name' => $businessName,
                                'verification_status' => $business['verification_status'] ?? null,
                            ];

                            // Extract owned catalogs
                            foreach ($business['owned_product_catalogs']['data'] ?? [] as $catalog) {
                                $catalogs[] = [
                                    'id' => $catalog['id'],
                                    'name' => $catalog['name'] ?? 'Unnamed Catalog',
                                    'product_count' => $catalog['product_count'] ?? 0,
                                    'vertical' => $catalog['vertical'] ?? 'commerce',
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                    'source' => 'owned',
                                ];
                            }

                            // Extract client catalogs
                            foreach ($business['client_product_catalogs']['data'] ?? [] as $catalog) {
                                $catalogs[] = [
                                    'id' => $catalog['id'],
                                    'name' => $catalog['name'] ?? 'Unnamed Catalog',
                                    'product_count' => $catalog['product_count'] ?? 0,
                                    'vertical' => $catalog['vertical'] ?? 'commerce',
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                    'source' => 'client',
                                ];
                            }

                            // Extract WhatsApp accounts
                            foreach ($business['owned_whatsapp_business_accounts']['data'] ?? [] as $waba) {
                                $wabaId = $waba['id'] ?? null;
                                $wabaName = $waba['name'] ?? 'Unnamed WABA';
                                foreach ($waba['phone_numbers']['data'] ?? [] as $phone) {
                                    $whatsappAccounts[] = [
                                        'id' => $phone['id'],
                                        'display_phone_number' => $phone['display_phone_number'] ?? '',
                                        'verified_name' => $phone['verified_name'] ?? '',
                                        'quality_rating' => $phone['quality_rating'] ?? null,
                                        'code_verification_status' => $phone['code_verification_status'] ?? null,
                                        'waba_id' => $wabaId,
                                        'waba_name' => $wabaName,
                                        'business_id' => $businessId,
                                        'business_name' => $businessName,
                                    ];
                                }
                            }

                            // Extract Offline Event Sets (System User only)
                            foreach ($business['offline_conversion_data_sets']['data'] ?? [] as $eventSet) {
                                $offlineEventSets[] = [
                                    'id' => $eventSet['id'],
                                    'name' => $eventSet['name'] ?? 'Unnamed Event Set',
                                    'description' => $eventSet['description'] ?? null,
                                    'upload_rate' => $eventSet['upload_rate'] ?? null,
                                    'duplicate_entries' => $eventSet['duplicate_entries'] ?? 0,
                                    'match_rate_approx' => $eventSet['match_rate_approx'] ?? null,
                                    'event_stats' => $eventSet['event_stats'] ?? null,
                                    'data_origin' => $eventSet['data_origin'] ?? null,
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                ];
                            }

                            // Extract owned pages with Instagram
                            foreach ($business['owned_pages']['data'] ?? [] as $page) {
                                $businessPages[] = [
                                    'id' => $page['id'],
                                    'name' => $page['name'] ?? 'Unknown Page',
                                    'category' => $page['category'] ?? null,
                                    'has_instagram' => isset($page['instagram_business_account']),
                                    'instagram_id' => $page['instagram_business_account']['id'] ?? null,
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                    'source' => 'owned',
                                ];

                                if (isset($page['instagram_business_account'])) {
                                    $ig = $page['instagram_business_account'];
                                    $businessInstagram[] = [
                                        'id' => $ig['id'],
                                        'username' => $ig['username'] ?? null,
                                        'name' => $ig['name'] ?? $ig['username'] ?? 'Unknown',
                                        'profile_picture' => $ig['profile_picture_url'] ?? null,
                                        'followers_count' => $ig['followers_count'] ?? 0,
                                        'media_count' => $ig['media_count'] ?? 0,
                                        'connected_page_id' => $page['id'],
                                        'connected_page_name' => $page['name'] ?? 'Unknown Page',
                                        'business_id' => $businessId,
                                        'business_name' => $businessName,
                                        'source' => 'owned',
                                    ];
                                }
                            }

                            // Extract client pages with Instagram
                            foreach ($business['client_pages']['data'] ?? [] as $page) {
                                $businessPages[] = [
                                    'id' => $page['id'],
                                    'name' => $page['name'] ?? 'Unknown Page',
                                    'category' => $page['category'] ?? null,
                                    'has_instagram' => isset($page['instagram_business_account']),
                                    'instagram_id' => $page['instagram_business_account']['id'] ?? null,
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                    'source' => 'client',
                                ];

                                if (isset($page['instagram_business_account'])) {
                                    $ig = $page['instagram_business_account'];
                                    $businessInstagram[] = [
                                        'id' => $ig['id'],
                                        'username' => $ig['username'] ?? null,
                                        'name' => $ig['name'] ?? $ig['username'] ?? 'Unknown',
                                        'profile_picture' => $ig['profile_picture_url'] ?? null,
                                        'followers_count' => $ig['followers_count'] ?? 0,
                                        'media_count' => $ig['media_count'] ?? 0,
                                        'connected_page_id' => $page['id'],
                                        'connected_page_name' => $page['name'] ?? 'Unknown Page',
                                        'business_id' => $businessId,
                                        'business_name' => $businessName,
                                        'source' => 'client',
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Batch business details fetch failed', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch fetched business details with all assets', [
            'requested_businesses' => count($businessIds),
            'fetched_businesses' => count($businesses),
            'catalogs' => count($catalogs),
            'whatsapp' => count($whatsappAccounts),
            'offline_event_sets' => count($offlineEventSets),
            'pages' => count($businessPages),
            'instagram' => count($businessInstagram),
            'batches_used' => $batchNumber,
            'is_system_user' => $isSystemUser,
        ]);

        return [
            'businesses' => $businesses,
            'catalogs' => $catalogs,
            'whatsapp' => $whatsappAccounts,
            'offline_event_sets' => $offlineEventSets,
            'pages' => $businessPages,
            'instagram' => $businessInstagram,
        ];
    }

    /**
     * Extract asset ID from asset data
     */
    protected function extractAssetId(array $data, string $assetType): string
    {
        return match($assetType) {
            'page' => $data['id'] ?? '',
            'instagram' => $data['id'] ?? '',
            'threads' => $data['id'] ?? '',
            'ad_account' => $data['id'] ?? $data['account_id'] ?? '',
            'pixel' => $data['id'] ?? '',
            'catalog' => $data['id'] ?? '',
            'whatsapp' => $data['id'] ?? '',
            'business' => $data['id'] ?? '',
            'custom_conversion' => $data['id'] ?? '',
            'offline_event_set' => $data['id'] ?? '',
            default => $data['id'] ?? '',
        };
    }

    /**
     * Infer access types from Meta asset data
     */
    protected function inferAccessTypes(array $data): array
    {
        $types = ['read'];

        if (isset($data['permitted_tasks'])) {
            $tasks = $data['permitted_tasks'];
            if (in_array('MANAGE', $tasks) || in_array('ADVERTISE', $tasks)) {
                $types[] = 'write';
            }
            if (in_array('MANAGE', $tasks)) {
                $types[] = 'admin';
            }
            if (in_array('CREATE_CONTENT', $tasks) || in_array('MODERATE', $tasks)) {
                $types[] = 'publish';
            }
            if (in_array('ANALYZE', $tasks)) {
                $types[] = 'analyze';
            }
        }

        // Check ownership source
        $source = $data['source'] ?? null;
        if ($source === 'owned') {
            $types = array_merge($types, ['write', 'admin', 'publish', 'analyze']);
        }

        return array_unique($types);
    }

    /**
     * Core pagination helper - fetches ALL pages from Meta API.
     * Follows paging.next cursor until all data is retrieved.
     * Includes retry logic for timeout errors and returns partial results on failure.
     */
    private function fetchAllPages(string $url, string $accessToken, array $params = []): array
    {
        $allData = [];
        $params['access_token'] = $accessToken;
        $params['limit'] = self::ITEMS_PER_PAGE;

        $nextUrl = $url . '?' . http_build_query($params);
        $pageCount = 0;
        $maxRetries = 2;
        $consecutiveTimeouts = 0;
        $maxConsecutiveTimeouts = 2; // Stop after 2 consecutive timeouts

        while ($nextUrl && $pageCount < self::MAX_PAGES) {
            // Add delay between requests to prevent rate limiting
            if ($pageCount > 0) {
                usleep(self::DELAY_BETWEEN_REQUESTS_MS * 1000);
            }

            $response = null;
            $lastError = null;

            // Retry loop for timeout errors
            for ($retry = 0; $retry <= $maxRetries; $retry++) {
                try {
                    // Use separate connect and read timeouts
                    $response = Http::connectTimeout(10)
                        ->timeout(self::REQUEST_TIMEOUT)
                        ->get($nextUrl);

                    // Reset consecutive timeout counter on success
                    $consecutiveTimeouts = 0;
                    break; // Success, exit retry loop

                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastError = $e->getMessage();
                    $consecutiveTimeouts++;

                    Log::warning('Meta API connection error, retrying', [
                        'url' => preg_replace('/access_token=[^&]+/', 'access_token=***', $nextUrl),
                        'page' => $pageCount + 1,
                        'retry' => $retry + 1,
                        'max_retries' => $maxRetries,
                        'error' => $lastError,
                    ]);

                    // Wait before retry (exponential backoff)
                    if ($retry < $maxRetries) {
                        usleep(($retry + 1) * 500000); // 500ms, 1000ms
                    }
                }
            }

            // If all retries failed or too many consecutive timeouts
            if ($response === null || $consecutiveTimeouts >= $maxConsecutiveTimeouts) {
                Log::warning('Meta API pagination stopped due to timeouts', [
                    'url' => preg_replace('/access_token=[^&]+/', 'access_token=***', $nextUrl),
                    'page' => $pageCount + 1,
                    'items_collected' => count($allData),
                    'consecutive_timeouts' => $consecutiveTimeouts,
                    'last_error' => $lastError,
                ]);
                break; // Return partial results
            }

            if (!$response->successful()) {
                $error = $response->json('error', []);

                // Check for rate limit error
                if (($error['code'] ?? 0) === 4 || ($error['code'] ?? 0) === 17) {
                    Log::warning('Meta API rate limit hit, stopping pagination', [
                        'url' => preg_replace('/access_token=[^&]+/', 'access_token=***', $nextUrl),
                        'page' => $pageCount + 1,
                        'items_collected' => count($allData),
                    ]);
                    break;
                }

                Log::warning('Meta API pagination failed', [
                    'url' => preg_replace('/access_token=[^&]+/', 'access_token=***', $nextUrl),
                    'page' => $pageCount + 1,
                    'error' => $error,
                ]);
                break;
            }

            $data = $response->json();
            $newItems = $data['data'] ?? [];
            $allData = array_merge($allData, $newItems);

            Log::debug('Meta API pagination progress', [
                'page' => $pageCount + 1,
                'items_this_page' => count($newItems),
                'total_items' => count($allData),
            ]);

            // Follow pagination cursor
            $nextUrl = $data['paging']['next'] ?? null;
            $pageCount++;
        }

        if ($pageCount >= self::MAX_PAGES) {
            Log::info('Meta API pagination reached limit', [
                'max_pages' => self::MAX_PAGES,
                'total_items' => count($allData),
            ]);
        }

        return $allData;
    }

    /**
     * Get cache key for a specific asset type and connection.
     */
    private function getCacheKey(string $connectionId, string $assetType): string
    {
        return "meta_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Fetch ALL user assets with pagination (pages + instagram).
     * Uses field expansion to get Instagram details embedded in the pages response.
     * Follows paging.next cursor to get ALL pages.
     *
     * DATABASE-FIRST STRATEGY:
     * 1. Check if we have fresh page/instagram data in database (< 6 hours old)
     * 2. If fresh data exists, return it without API calls
     * 3. If no fresh data, fetch from API and persist
     */
    private function getAllUserAssets(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'all_user_assets');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken, $connectionId) {
            // DATABASE-FIRST: Check if we have fresh page data
            $freshPages = $this->getExistingFreshAssets('page');
            $freshInstagram = $this->getExistingFreshAssets('instagram');

            // If we have fresh pages data, return from DB without API calls
            if (!empty($freshPages)) {
                Log::info('DATABASE-FIRST: Returning fresh user assets from database (0 API calls)', [
                    'pages' => count($freshPages),
                    'instagram' => count($freshInstagram),
                ]);

                return [
                    'pages' => array_values($freshPages),
                    'instagram' => array_values($freshInstagram),
                ];
            }

            Log::info('Fetching user assets using TWO-PHASE INCREMENTAL SYNC (optimized for 400+ pages)');

            try {
                // =====================================================================
                // TWO-PHASE INCREMENTAL SYNC FOR PAGES
                // Phase 1: Fetch just page IDs (lightweight - ~500 IDs per request)
                // Phase 2: Check which IDs are already fresh in database
                // Phase 3: Batch fetch only NEW page details (50 per batch request)
                // =====================================================================

                // Phase 1: Get all page IDs (lightweight call)
                $allPageIds = $this->fetchAssetIdsOnly(
                    self::BASE_URL . '/' . self::API_VERSION . '/me/accounts',
                    $accessToken,
                    500 // Max 500 IDs per page
                );

                Log::info('Phase 1 complete: Got page IDs', ['count' => count($allPageIds)]);

                if (empty($allPageIds)) {
                    return ['pages' => [], 'instagram' => []];
                }

                // Phase 2: Check DB for existing fresh page data
                $freshPagesById = $this->getExistingFreshAssets('page');
                $freshIds = array_keys($freshPagesById);
                $newPageIds = array_diff($allPageIds, $freshIds);

                Log::info('Phase 2 complete: Identified new pages to fetch', [
                    'total_pages' => count($allPageIds),
                    'fresh_in_db' => count($freshIds),
                    'new_to_fetch' => count($newPageIds),
                    'api_calls_saved' => count($freshIds),
                    'batch_calls_needed' => empty($newPageIds) ? 0 : ceil(count($newPageIds) / self::BATCH_SIZE),
                ]);

                // Start with fresh pages from DB
                $pages = array_values($freshPagesById);
                $instagramAccounts = array_values($this->getExistingFreshAssets('instagram'));

                // Phase 3: Batch fetch only NEW pages (50 per batch)
                if (!empty($newPageIds)) {
                    $newPages = $this->batchFetchPageDetails(array_values($newPageIds), $accessToken);

                    // Extract Instagram from new pages
                    foreach ($newPages as $page) {
                        $pages[] = $page;

                        // Extract Instagram if present (already included via field expansion in batch)
                        if (!empty($page['instagram_data'])) {
                            $ig = $page['instagram_data'];
                            $existingIgIds = array_column($instagramAccounts, 'id');
                            if (!in_array($ig['id'], $existingIgIds)) {
                                $instagramAccounts[] = [
                                    'id' => $ig['id'],
                                    'username' => $ig['username'] ?? null,
                                    'name' => $ig['name'] ?? $ig['username'] ?? 'Unknown',
                                    'profile_picture' => $ig['profile_picture_url'] ?? null,
                                    'followers_count' => $ig['followers_count'] ?? 0,
                                    'media_count' => $ig['media_count'] ?? 0,
                                    'connected_page_id' => $page['id'],
                                    'connected_page_name' => $page['name'] ?? 'Unknown Page',
                                ];
                            }
                        }
                    }

                    Log::info('Phase 3 complete: Batch fetched new pages', [
                        'new_pages_fetched' => count($newPages),
                        'instagram_extracted' => count($instagramAccounts),
                    ]);
                }

                Log::info('TWO-PHASE user asset sync complete', [
                    'total_pages' => count($pages),
                    'total_instagram' => count($instagramAccounts),
                    'api_efficiency' => sprintf(
                        '%d batch calls instead of %d paginated calls',
                        empty($newPageIds) ? 0 : ceil(count($newPageIds) / self::BATCH_SIZE),
                        ceil(count($allPageIds) / 100)
                    ),
                ]);

                return [
                    'pages' => $pages,
                    'instagram' => $instagramAccounts,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to fetch user assets', ['error' => $e->getMessage()]);
                return ['pages' => [], 'instagram' => []];
            }
        });
    }

    /**
     * Get Facebook Pages with ownership priority: business owned > client > personal.
     * Uses shared cache from getAllUserAssets and getAllBusinessAssets - 0 extra API calls.
     */
    public function getPages(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_user_assets'));
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        // Get pages from businesses (owned_pages and client_pages) - HIGHEST PRIORITY
        $businessAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        $businessPages = $businessAssets['pages'] ?? [];

        // Separate owned and client pages for proper priority ordering
        $ownedPages = array_filter($businessPages, fn($p) => ($p['source'] ?? '') === 'owned');
        $clientPages = array_filter($businessPages, fn($p) => ($p['source'] ?? '') === 'client');

        // Start with owned pages (highest priority)
        $allPages = array_values($ownedPages);
        $existingIds = array_column($allPages, 'id');

        // Add client pages (second priority) - only if not already in owned
        foreach ($clientPages as $page) {
            if (!in_array($page['id'], $existingIds)) {
                $allPages[] = $page;
                $existingIds[] = $page['id'];
            }
        }

        // Get pages from /me/accounts (pages where user has a role) - LOWEST PRIORITY
        $userAssets = $this->getAllUserAssets($accessToken, $connectionId);
        $userPages = $userAssets['pages'] ?? [];

        // Add personal pages only if not already in business pages
        foreach ($userPages as $page) {
            if (!in_array($page['id'], $existingIds)) {
                // Mark as personal since it's only from /me/accounts
                $page['source'] = 'personal';
                $allPages[] = $page;
                $existingIds[] = $page['id'];
            }
        }

        Log::debug('Pages merged with ownership priority (owned > client > personal)', [
            'owned_pages' => count($ownedPages),
            'client_pages' => count($clientPages),
            'personal_pages' => count(array_filter($allPages, fn($p) => ($p['source'] ?? '') === 'personal')),
            'total_unique' => count($allPages),
        ]);

        // Persist to database for three-tier caching
        $this->persistAssets($connectionId, 'page', $allPages);

        return $allPages;
    }

    /**
     * Get Instagram Business accounts with ownership priority: business owned > client > personal.
     * Sources: business owned pages, business client pages, business instagram_accounts edge, user assets.
     */
    public function getInstagramAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'instagram_merged');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
            Cache::forget($this->getCacheKey($connectionId, 'all_user_assets'));
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken, $connectionId) {
            $allInstagram = [];
            $existingIds = [];

            // Get business assets - Instagram is NOW EMBEDDED in pages via field expansion
            // This means 0 EXTRA API calls for Instagram (previously made 100+ batch calls)
            $businessAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
            $businessInstagram = $businessAssets['instagram'] ?? [];

            // STEP 1: Use Instagram data already extracted from pages (via field expansion)
            // Instagram is organized by source (owned/client) during getAllBusinessAssets extraction
            $businessInstagramCount = 0;
            foreach ($businessInstagram as $ig) {
                if (!in_array($ig['id'], $existingIds)) {
                    // Source is already set during extraction in getAllBusinessAssets
                    $allInstagram[] = $ig;
                    $existingIds[] = $ig['id'];
                    $businessInstagramCount++;
                }
            }

            Log::info('Instagram from business pages (via field expansion - 0 extra API calls)', [
                'count' => $businessInstagramCount,
            ]);

            // STEP 2: Get Instagram from /me/accounts (via connected pages) - LOWEST PRIORITY (personal)
            $userAssets = $this->getAllUserAssets($accessToken, $connectionId);
            $userInstagram = $userAssets['instagram'] ?? [];

            $personalCount = 0;
            foreach ($userInstagram as $ig) {
                if (!in_array($ig['id'], $existingIds)) {
                    $ig['source'] = 'personal';
                    $allInstagram[] = $ig;
                    $existingIds[] = $ig['id'];
                    $personalCount++;
                }
            }

            Log::debug('Instagram merged with ownership priority (business > personal)', [
                'business_instagram' => $businessInstagramCount,
                'personal_instagram' => $personalCount,
                'total_unique' => count($allInstagram),
                'optimization' => '0 extra API calls (Instagram embedded in page field expansion)',
            ]);

            // Persist to database for three-tier caching
            $this->persistAssets($connectionId, 'instagram', $allInstagram);

            return $allInstagram;
        });
    }

    /**
     * Get Threads accounts.
     * Note: Threads API requires separate OAuth with threads_* scopes.
     * This method performs a quick test first to fail fast if scopes aren't available.
     *
     * OPTIMIZED with TWO-PHASE INCREMENTAL SYNC:
     * 1. DATABASE-FIRST: Check for fresh Threads data in DB
     * 2. PHASE 1: Get Instagram IDs (already have from getInstagramAccounts)
     * 3. PHASE 2: Check DB for existing Threads data
     * 4. PHASE 3: Batch fetch only NEW Threads accounts (50 per batch)
     */
    public function getThreadsAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'threads');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            // DATABASE-FIRST: Check for fresh Threads data
            $freshThreads = $this->getExistingFreshAssets('threads');

            // Get Instagram accounts (already optimized with two-phase sync)
            $instagramAccounts = $this->getInstagramAccounts($connectionId, $accessToken, false);

            if (empty($instagramAccounts)) {
                Log::debug('No Instagram accounts found, skipping Threads');
                return array_values($freshThreads);
            }

            // Check if we have fresh data for all Instagram IDs
            $allIgIds = array_column($instagramAccounts, 'id');
            $freshIgIds = array_column($freshThreads, 'instagram_id');
            $newIgIds = array_diff($allIgIds, $freshIgIds);

            // If all Threads data is fresh, return from DB without API calls
            if (empty($newIgIds) && !empty($freshThreads)) {
                Log::info('DATABASE-FIRST: Returning fresh Threads accounts from database (0 API calls)', [
                    'threads_count' => count($freshThreads),
                ]);
                return array_values($freshThreads);
            }

            // Quick test if Threads API is accessible with this token (5s timeout)
            // Threads requires separate OAuth with threads_* scopes
            try {
                $testResponse = Http::connectTimeout(5)->timeout(5)->get(
                    self::THREADS_BASE_URL . '/v1.0/me',
                    ['access_token' => $accessToken]
                );

                if (!$testResponse->successful()) {
                    Log::debug('Threads API not accessible - requires separate OAuth', [
                        'status' => $testResponse->status(),
                    ]);
                    // Return fresh data from DB if available
                    return array_values($freshThreads);
                }
            } catch (\Exception $e) {
                Log::debug('Threads API unavailable - fast fail', [
                    'error' => $e->getMessage(),
                ]);
                return array_values($freshThreads);
            }

            // Test passed - token has threads_* scopes, proceed with batch fetching
            Log::info('Threads API accessible, batch fetching NEW accounts', [
                'total_instagram' => count($instagramAccounts),
                'fresh_in_db' => count($freshThreads),
                'new_to_fetch' => count($newIgIds),
            ]);

            // Build Instagram ID to data map for new IDs only
            $igDataMap = [];
            foreach ($instagramAccounts as $ig) {
                if (in_array($ig['id'] ?? '', $newIgIds)) {
                    $igDataMap[$ig['id']] = $ig;
                }
            }

            // Batch fetch Threads accounts using Threads API batch endpoint
            $newThreadsAccounts = $this->batchFetchThreadsAccounts(array_keys($igDataMap), $igDataMap, $accessToken);

            // Merge fresh DB data + new API data
            $allThreadsAccounts = array_merge(array_values($freshThreads), $newThreadsAccounts);

            // Deduplicate by ID
            $allThreadsAccounts = $this->deduplicateById($allThreadsAccounts);

            Log::info('Threads accounts fetched via TWO-PHASE INCREMENTAL SYNC', [
                'total' => count($allThreadsAccounts),
                'from_db' => count($freshThreads),
                'new_fetched' => count($newThreadsAccounts),
            ]);

            // Persist NEW data to database for future caching
            if (!empty($newThreadsAccounts)) {
                $this->persistAssets($connectionId, 'threads', $newThreadsAccounts);
            }

            return $allThreadsAccounts;
        });
    }

    /**
     * Batch fetch Threads accounts using Threads API batch endpoint.
     * Fetches 50 Threads accounts per batch request.
     *
     * @param array $igIds Instagram IDs to fetch Threads data for
     * @param array $igDataMap Map of Instagram ID to Instagram data
     * @param string $accessToken The access token with threads_* scopes
     * @return array Array of Threads account data
     */
    protected function batchFetchThreadsAccounts(array $igIds, array $igDataMap, string $accessToken): array
    {
        if (empty($igIds)) {
            return [];
        }

        $threadsAccounts = [];
        $fields = 'id,username,name,threads_profile_picture_url,threads_biography';
        $chunks = array_chunk($igIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($chunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            $batchRequests = [];
            foreach ($chunk as $igId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => "{$igId}?" . http_build_query(['fields' => $fields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->asForm()
                    ->post(self::THREADS_BASE_URL . '/v1.0/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if ($response->successful()) {
                    foreach ($response->json() ?? [] as $index => $batchResponse) {
                        $igId = $chunk[$index] ?? null;
                        $ig = $igDataMap[$igId] ?? [];

                        if (($batchResponse['code'] ?? 0) === 200) {
                            $threadsData = json_decode($batchResponse['body'] ?? '{}', true);
                            if (!empty($threadsData['id'])) {
                                $threadsAccounts[] = [
                                    'id' => $threadsData['id'],
                                    'username' => $threadsData['username'] ?? $ig['username'] ?? null,
                                    'name' => $threadsData['name'] ?? $ig['name'] ?? 'Threads Account',
                                    'profile_picture' => $threadsData['threads_profile_picture_url'] ?? $ig['profile_picture'] ?? null,
                                    'biography' => $threadsData['threads_biography'] ?? null,
                                    'connected_instagram' => $ig['username'] ?? null,
                                    'instagram_id' => $igId,
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Threads batch fetch failed', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch fetched Threads accounts', [
            'requested' => count($igIds),
            'fetched' => count($threadsAccounts),
            'batches_used' => $batchNumber,
        ]);

        return $threadsAccounts;
    }

    /**
     * Fetch ALL ad account assets using Batch API for efficiency.
     * Uses field expansion on businesses to get ad accounts in fewer API calls.
     *
     * OPTIMIZATION: Instead of N calls per business, uses:
     * 1. Single /me/businesses call with embedded owned_ad_accounts and client_ad_accounts
     * 2. Batch API fallback if field expansion doesn't work
     * 3. Single /me/adaccounts call for personal accounts
     *
     * DATABASE-FIRST STRATEGY:
     * 1. Check if we have fresh ad account data in database (< 6 hours old)
     * 2. If fresh data exists, return it without API calls
     * 3. If no fresh data, fetch from API and persist
     */
    private function getAllAdAccountAssets(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'all_adaccount_assets');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken, $connectionId) {
            // DATABASE-FIRST: Check if we have fresh ad account data
            $freshAdAccounts = $this->getExistingFreshAssets('ad_account');
            $freshPixels = $this->getExistingFreshAssets('pixel');
            $freshConversions = $this->getExistingFreshAssets('custom_conversion');

            // If we have fresh ad account data, return from DB without API calls
            if (!empty($freshAdAccounts)) {
                Log::info('DATABASE-FIRST: Returning fresh ad account assets from database (0 API calls)', [
                    'ad_accounts' => count($freshAdAccounts),
                    'pixels' => count($freshPixels),
                    'custom_conversions' => count($freshConversions),
                ]);

                return [
                    'ad_accounts' => array_values($freshAdAccounts),
                    'pixels' => array_values($freshPixels),
                    'custom_conversions' => array_values($freshConversions),
                ];
            }

            Log::info('Fetching ad accounts using TWO-PHASE INCREMENTAL SYNC (optimized for large accounts)');

            try {
                // =====================================================================
                // TWO-PHASE INCREMENTAL SYNC FOR AD ACCOUNTS
                // Phase 1: Fetch just ad account IDs (lightweight - ~500 IDs per request)
                // Phase 2: Check which IDs are already fresh in database
                // Phase 3: Batch fetch only NEW ad account details (50 per batch)
                // =====================================================================

                // Phase 1: Get all ad account IDs (lightweight call)
                $allAdAccountIds = $this->fetchAssetIdsOnly(
                    self::BASE_URL . '/' . self::API_VERSION . '/me/adaccounts',
                    $accessToken,
                    500
                );

                Log::info('Phase 1 complete: Got ad account IDs', ['count' => count($allAdAccountIds)]);

                if (empty($allAdAccountIds)) {
                    return ['ad_accounts' => [], 'pixels' => [], 'custom_conversions' => []];
                }

                // Phase 2: Check DB for existing fresh ad account data
                $freshAdAccountsById = $this->getExistingFreshAssets('ad_account');
                $freshIds = array_keys($freshAdAccountsById);
                $newAdAccountIds = array_diff($allAdAccountIds, $freshIds);

                Log::info('Phase 2 complete: Identified new ad accounts to fetch', [
                    'total_ad_accounts' => count($allAdAccountIds),
                    'fresh_in_db' => count($freshIds),
                    'new_to_fetch' => count($newAdAccountIds),
                    'api_calls_saved' => count($freshIds),
                    'batch_calls_needed' => empty($newAdAccountIds) ? 0 : ceil(count($newAdAccountIds) / self::BATCH_SIZE),
                ]);

                // Start with fresh data from DB
                $adAccounts = array_values($freshAdAccountsById);
                $pixels = array_values($freshPixels);
                $customConversions = array_values($freshConversions);

                // Phase 3: Batch fetch only NEW ad accounts with pixels/conversions (50 per batch)
                if (!empty($newAdAccountIds)) {
                    $newData = $this->batchFetchAdAccountDetailsIncremental(
                        array_values($newAdAccountIds),
                        $accessToken
                    );

                    // Merge new data with existing DB data
                    $adAccounts = array_merge($adAccounts, $newData['ad_accounts'] ?? []);
                    $pixels = array_merge($pixels, $newData['pixels'] ?? []);
                    $customConversions = array_merge($customConversions, $newData['custom_conversions'] ?? []);

                    Log::info('Phase 3 complete: Batch fetched new ad accounts', [
                        'new_ad_accounts' => count($newData['ad_accounts'] ?? []),
                        'new_pixels' => count($newData['pixels'] ?? []),
                        'new_conversions' => count($newData['custom_conversions'] ?? []),
                    ]);
                }

                Log::info('TWO-PHASE ad account sync complete', [
                    'total_ad_accounts' => count($adAccounts),
                    'total_pixels' => count($pixels),
                    'total_conversions' => count($customConversions),
                    'api_efficiency' => sprintf(
                        '%d batch calls instead of %d individual calls',
                        empty($newAdAccountIds) ? 0 : ceil(count($newAdAccountIds) / self::BATCH_SIZE),
                        count($allAdAccountIds)
                    ),
                ]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'ad_account', $adAccounts);
                $this->persistAssets($connectionId, 'pixel', $pixels);
                $this->persistAssets($connectionId, 'custom_conversion', $customConversions);

                return [
                    'ad_accounts' => $adAccounts,
                    'pixels' => $pixels,
                    'custom_conversions' => $customConversions,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to fetch ad account assets', ['error' => $e->getMessage()]);
                return ['ad_accounts' => [], 'pixels' => [], 'custom_conversions' => []];
            }
        });
    }

    /**
     * Format ad account with basic fields (used during initial fetch)
     */
    private function formatAdAccountBasic(array $account, ?string $businessId, ?string $businessName, string $source): array
    {
        $statusCode = $account['account_status'] ?? 0;
        return [
            'id' => $account['id'],
            'account_id' => $account['account_id'] ?? str_replace('act_', '', $account['id']),
            'name' => $account['name'] ?? 'Unknown',
            'business_name' => $businessName,
            'business_id' => $businessId,
            'source' => $source,
            'currency' => $account['currency'] ?? 'USD',
            'timezone' => $account['timezone_name'] ?? 'UTC',
            'status' => $this->getAccountStatusLabel($statusCode),
            'status_code' => $statusCode,
            'can_create_ads' => $statusCode === 1,
        ];
    }

    /**
     * Batch fetch pixels and custom conversions for ad accounts using Batch API.
     * Reduces N individual calls to ceil(N/50) batch calls.
     */
    private function batchFetchAdAccountDetails(array $adAccounts, string $accessToken): array
    {
        if (empty($adAccounts)) {
            return ['ad_accounts' => $adAccounts, 'pixels' => [], 'custom_conversions' => []];
        }

        $pixels = [];
        $customConversions = [];
        $enrichedAccounts = [];

        // Build account metadata for enrichment
        $accountMetadata = [];
        foreach ($adAccounts as $account) {
            $accountMetadata[$account['id']] = $account;
        }

        $accountIds = array_keys($accountMetadata);
        $batchChunks = array_chunk($accountIds, self::BATCH_SIZE);
        $batchNumber = 0;

        // Fields for detailed fetch
        $detailFields = 'id,name,account_status,disable_reason,spend_cap,amount_spent,balance,is_prepay_account,min_daily_budget,capabilities,funding_source_details,created_time,adspixels.limit(50){id,name,creation_time,last_fired_time},customconversions.limit(50){id,name,description,custom_event_type,rule,pixel,creation_time,last_fired_time,is_archived}';

        foreach ($batchChunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            // Build batch request
            $batchRequests = [];
            foreach ($chunk as $accountId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $accountId . '?' . http_build_query(['fields' => $detailFields]),
                ];
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if (!$response->successful()) {
                    Log::warning('Batch ad account details fetch failed', [
                        'batch' => $batchNumber + 1,
                        'status' => $response->status(),
                    ]);
                    $batchNumber++;
                    continue;
                }

                $batchResponses = $response->json();
                foreach ($batchResponses as $index => $batchResponse) {
                    $accountId = $chunk[$index] ?? null;
                    if (!$accountId) continue;

                    $baseAccount = $accountMetadata[$accountId] ?? [];

                    if (($batchResponse['code'] ?? 0) === 200) {
                        $body = json_decode($batchResponse['body'] ?? '{}', true);

                        // Enrich account with detailed info
                        $enrichedAccount = array_merge($baseAccount, [
                            'disable_reason' => isset($body['disable_reason']) ? $this->getDisableReasonLabel($body['disable_reason']) : null,
                            'spend_cap' => $body['spend_cap'] ?? null,
                            'amount_spent' => $body['amount_spent'] ?? '0',
                            'balance' => $body['balance'] ?? '0',
                            'is_prepay' => $body['is_prepay_account'] ?? false,
                            'min_daily_budget' => $body['min_daily_budget'] ?? null,
                            'capabilities' => $body['capabilities'] ?? [],
                            'funding_source' => $body['funding_source_details']['display_string'] ?? null,
                            'created_at' => $body['created_time'] ?? null,
                        ]);
                        $enrichedAccounts[] = $enrichedAccount;

                        // Extract pixels
                        foreach ($body['adspixels']['data'] ?? [] as $pixel) {
                            $existingIds = array_column($pixels, 'id');
                            if (!in_array($pixel['id'], $existingIds)) {
                                $pixels[] = [
                                    'id' => $pixel['id'],
                                    'name' => $pixel['name'] ?? 'Unnamed Pixel',
                                    'ad_account_id' => $accountId,
                                    'ad_account_name' => $baseAccount['name'] ?? 'Unknown',
                                    'business_name' => $baseAccount['business_name'] ?? null,
                                    'business_id' => $baseAccount['business_id'] ?? null,
                                    'source' => $baseAccount['source'] ?? 'unknown',
                                    'creation_time' => $pixel['creation_time'] ?? null,
                                    'last_fired_time' => $pixel['last_fired_time'] ?? null,
                                ];
                            }
                        }

                        // Extract custom conversions
                        foreach ($body['customconversions']['data'] ?? [] as $conversion) {
                            $existingIds = array_column($customConversions, 'id');
                            if (!in_array($conversion['id'], $existingIds)) {
                                $customConversions[] = [
                                    'id' => $conversion['id'],
                                    'name' => $conversion['name'] ?? 'Unnamed Conversion',
                                    'description' => $conversion['description'] ?? null,
                                    'custom_event_type' => $conversion['custom_event_type'] ?? null,
                                    'rule' => $conversion['rule'] ?? null,
                                    'pixel_id' => $conversion['pixel']['id'] ?? null,
                                    'ad_account_id' => $accountId,
                                    'ad_account_name' => $baseAccount['name'] ?? 'Unknown',
                                    'business_name' => $baseAccount['business_name'] ?? null,
                                    'business_id' => $baseAccount['business_id'] ?? null,
                                    'source' => $baseAccount['source'] ?? 'unknown',
                                    'creation_time' => $conversion['creation_time'] ?? null,
                                    'last_fired_time' => $conversion['last_fired_time'] ?? null,
                                    'is_archived' => $conversion['is_archived'] ?? false,
                                ];
                            }
                        }
                    } else {
                        // Keep basic account info if detailed fetch failed
                        $enrichedAccounts[] = $baseAccount;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Exception in batch ad account details fetch', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
                // Add remaining accounts without enrichment
                foreach ($chunk as $accountId) {
                    if (isset($accountMetadata[$accountId]) && !in_array($accountMetadata[$accountId], $enrichedAccounts)) {
                        $enrichedAccounts[] = $accountMetadata[$accountId];
                    }
                }
            }

            $batchNumber++;
        }

        Log::debug('Batch ad account details fetch completed', [
            'accounts_enriched' => count($enrichedAccounts),
            'pixels_found' => count($pixels),
            'conversions_found' => count($customConversions),
            'batches_used' => $batchNumber,
        ]);

        return [
            'ad_accounts' => $enrichedAccounts,
            'pixels' => $pixels,
            'custom_conversions' => $customConversions,
        ];
    }

    /**
     * Helper method to add an ad account and its nested assets to results.
     */
    private function addAdAccountToResults(
        array $account,
        ?string $businessId,
        ?string $businessName,
        string $source,
        array &$adAccounts,
        array &$pixels,
        array &$customConversions
    ): void {
        $statusCode = $account['account_status'] ?? 0;
        $disableReason = $account['disable_reason'] ?? null;
        $accountId = $account['id'];
        $accountName = $account['name'] ?? 'Unknown';

        // Store ad account info with ownership
        $adAccounts[] = [
            'id' => $accountId,
            'account_id' => $account['account_id'] ?? str_replace('act_', '', $accountId),
            'name' => $accountName,
            'business_name' => $businessName,
            'business_id' => $businessId,
            'source' => $source, // 'owned', 'client', or 'personal'
            'currency' => $account['currency'] ?? 'USD',
            'timezone' => $account['timezone_name'] ?? 'UTC',
            'timezone_id' => $account['timezone_id'] ?? null,
            'status' => $this->getAccountStatusLabel($statusCode),
            'status_code' => $statusCode,
            'disable_reason' => $disableReason ? $this->getDisableReasonLabel($disableReason) : null,
            'spend_cap' => $account['spend_cap'] ?? null,
            'amount_spent' => $account['amount_spent'] ?? '0',
            'balance' => $account['balance'] ?? '0',
            'is_prepay' => $account['is_prepay_account'] ?? false,
            'min_daily_budget' => $account['min_daily_budget'] ?? null,
            'capabilities' => $account['capabilities'] ?? [],
            'funding_source' => $account['funding_source_details']['display_string'] ?? null,
            'created_at' => $account['created_time'] ?? null,
            'can_create_ads' => $statusCode === 1,
        ];

        // Extract pixels (inherit business info from ad account)
        foreach ($account['adspixels']['data'] ?? [] as $pixel) {
            $existingIds = array_column($pixels, 'id');
            if (!in_array($pixel['id'], $existingIds)) {
                $pixels[] = [
                    'id' => $pixel['id'],
                    'name' => $pixel['name'] ?? 'Unnamed Pixel',
                    'ad_account_id' => $accountId,
                    'ad_account_name' => $accountName,
                    'business_name' => $businessName,
                    'business_id' => $businessId,
                    'source' => $source,
                    'creation_time' => $pixel['creation_time'] ?? null,
                    'last_fired_time' => $pixel['last_fired_time'] ?? null,
                ];
            }
        }

        // Extract custom conversions (inherit business info from ad account)
        foreach ($account['customconversions']['data'] ?? [] as $conversion) {
            $existingIds = array_column($customConversions, 'id');
            if (!in_array($conversion['id'], $existingIds)) {
                $customConversions[] = [
                    'id' => $conversion['id'],
                    'name' => $conversion['name'] ?? 'Unnamed Conversion',
                    'description' => $conversion['description'] ?? null,
                    'custom_event_type' => $conversion['custom_event_type'] ?? null,
                    'rule' => $conversion['rule'] ?? null,
                    'pixel_id' => $conversion['pixel']['id'] ?? null,
                    'ad_account_id' => $accountId,
                    'ad_account_name' => $accountName,
                    'business_name' => $businessName,
                    'business_id' => $businessId,
                    'source' => $source,
                    'creation_time' => $conversion['creation_time'] ?? null,
                    'last_fired_time' => $conversion['last_fired_time'] ?? null,
                    'is_archived' => $conversion['is_archived'] ?? false,
                ];
            }
        }
    }

    /**
     * Get Meta Ad Accounts (uses shared cache from getAllAdAccountAssets - 0 extra API calls).
     */
    public function getAdAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_adaccount_assets'));
        }

        $allAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        return $allAssets['ad_accounts'] ?? [];
    }

    /**
     * Get Meta Pixels (uses shared cache from getAllAdAccountAssets - 0 extra API calls).
     */
    public function getPixels(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_adaccount_assets'));
        }

        $allAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        return $allAssets['pixels'] ?? [];
    }

    /**
     * Get Product Catalogs (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getCatalogs(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['catalogs'] ?? [];
    }

    /**
     * Get WhatsApp Business Accounts (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getWhatsappAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['whatsapp'] ?? [];
    }

    /**
     * Fetch ALL business assets with pagination (businesses + catalogs + whatsapp).
     * This is the core method that minimizes API calls by using field expansion.
     * All business-related data is fetched together and cached.
     *
     * DATABASE-FIRST STRATEGY:
     * 1. Check if we have fresh data in database (< 6 hours old)
     * 2. If fresh data exists, return it without API calls
     * 3. If no fresh data, fetch from API and persist
     */
    private function getAllBusinessAssets(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'all_business_assets');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken, $connectionId) {
            // DATABASE-FIRST: Check if we have fresh business data
            $freshBusinesses = $this->getExistingFreshAssets('business');
            $freshCatalogs = $this->getExistingFreshAssets('catalog');
            $freshWhatsapp = $this->getExistingFreshAssets('whatsapp');
            $freshOfflineEventSets = $this->getExistingFreshAssets('offline_event_set');
            $freshPages = $this->getExistingFreshAssets('page');
            $freshInstagram = $this->getExistingFreshAssets('instagram');

            // If we have fresh data for all key asset types, return from DB without API calls
            if (!empty($freshBusinesses) && !empty($freshPages)) {
                Log::info('DATABASE-FIRST: Returning fresh business assets from database (0 API calls)', [
                    'businesses' => count($freshBusinesses),
                    'pages' => count($freshPages),
                    'catalogs' => count($freshCatalogs),
                ]);

                return [
                    'businesses' => array_values($freshBusinesses),
                    'catalogs' => array_values($freshCatalogs),
                    'whatsapp' => array_values($freshWhatsapp),
                    'offline_event_sets' => array_values($freshOfflineEventSets),
                    'pages' => array_values($freshPages),
                    'instagram' => array_values($freshInstagram),
                ];
            }

            Log::info('Fetching business assets using TWO-PHASE INCREMENTAL SYNC');

            $isSystemUser = false;
            $allBusinessIds = [];

            try {
                // ========== PHASE 1: Fetch business IDs only (lightweight) ==========
                // Try Normal User endpoint first
                $allBusinessIds = $this->fetchAssetIdsOnly(
                    self::BASE_URL . '/' . self::API_VERSION . '/me/businesses',
                    $accessToken
                );

                // If empty, try System User fallback - extract business IDs from ad accounts
                if (empty($allBusinessIds)) {
                    Log::info('No businesses from /me/businesses, trying System User fallback via ad accounts');
                    $isSystemUser = true;

                    // Get ad account IDs to extract business IDs
                    $adAccountIds = $this->fetchAssetIdsOnly(
                        self::BASE_URL . '/' . self::API_VERSION . '/me/adaccounts',
                        $accessToken
                    );

                    if (!empty($adAccountIds)) {
                        // Batch fetch ad accounts to get their business IDs
                        $businessIdSet = [];
                        $chunks = array_chunk($adAccountIds, self::BATCH_SIZE);

                        foreach ($chunks as $chunk) {
                            $batchRequests = [];
                            foreach ($chunk as $adAccountId) {
                                $batchRequests[] = [
                                    'method' => 'GET',
                                    'relative_url' => $adAccountId . '?' . http_build_query(['fields' => 'business']),
                                ];
                            }

                            $response = Http::connectTimeout(10)
                                ->timeout(self::REQUEST_TIMEOUT)
                                ->asForm()
                                ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                                    'access_token' => $accessToken,
                                    'include_headers' => 'false',
                                    'batch' => json_encode($batchRequests),
                                ]);

                            if ($response->successful()) {
                                foreach ($response->json() ?? [] as $batchResponse) {
                                    if (($batchResponse['code'] ?? 0) === 200) {
                                        $body = json_decode($batchResponse['body'] ?? '{}', true);
                                        if (!empty($body['business']['id'])) {
                                            $businessIdSet[$body['business']['id']] = true;
                                        }
                                    }
                                }
                            }
                        }

                        $allBusinessIds = array_keys($businessIdSet);
                        Log::info('Extracted business IDs from ad accounts (System User)', [
                            'business_count' => count($allBusinessIds),
                        ]);
                    }
                }

                Log::info('PHASE 1 complete: fetched business IDs', [
                    'total_business_ids' => count($allBusinessIds),
                    'is_system_user' => $isSystemUser,
                ]);

                // ========== PHASE 2: Check DB for fresh data ==========
                $freshBusinessIds = array_keys($freshBusinesses);
                $newBusinessIds = array_diff($allBusinessIds, $freshBusinessIds);

                Log::info('PHASE 2 complete: identified businesses needing fetch', [
                    'total_ids' => count($allBusinessIds),
                    'fresh_in_db' => count($freshBusinessIds),
                    'new_to_fetch' => count($newBusinessIds),
                ]);

                // ========== PHASE 3: Batch fetch only NEW businesses ==========
                $newData = [
                    'businesses' => [],
                    'catalogs' => [],
                    'whatsapp' => [],
                    'offline_event_sets' => [],
                    'pages' => [],
                    'instagram' => [],
                ];

                if (!empty($newBusinessIds)) {
                    Log::info('PHASE 3: Batch fetching NEW businesses', [
                        'count' => count($newBusinessIds),
                        'batches_needed' => ceil(count($newBusinessIds) / self::BATCH_SIZE),
                        'is_system_user' => $isSystemUser,
                    ]);

                    $newData = $this->batchFetchBusinessDetailsWithAssets(
                        array_values($newBusinessIds),
                        $accessToken,
                        $isSystemUser
                    );
                }

                // ========== MERGE: Combine fresh DB data + new API data ==========
                $businesses = array_merge(array_values($freshBusinesses), $newData['businesses']);
                $catalogs = array_merge(array_values($freshCatalogs), $newData['catalogs']);
                $whatsappAccounts = array_merge(array_values($freshWhatsapp), $newData['whatsapp']);
                $offlineEventSets = array_merge(array_values($freshOfflineEventSets), $newData['offline_event_sets']);
                $businessPages = array_merge(array_values($freshPages), $newData['pages']);
                $businessInstagram = array_merge(array_values($freshInstagram), $newData['instagram']);

                // Deduplicate by ID
                $businesses = $this->deduplicateById($businesses);
                $catalogs = $this->deduplicateById($catalogs);
                $whatsappAccounts = $this->deduplicateById($whatsappAccounts);
                $offlineEventSets = $this->deduplicateById($offlineEventSets);
                $businessPages = $this->deduplicateById($businessPages);
                $businessInstagram = $this->deduplicateById($businessInstagram);

                Log::info('ALL business assets fetched via TWO-PHASE INCREMENTAL SYNC', [
                    'businesses' => count($businesses),
                    'catalogs' => count($catalogs),
                    'whatsapp_accounts' => count($whatsappAccounts),
                    'offline_event_sets' => count($offlineEventSets),
                    'business_pages' => count($businessPages),
                    'business_instagram' => count($businessInstagram),
                    'new_businesses_fetched' => count($newData['businesses']),
                    'from_db_cache' => count($freshBusinesses),
                    'is_system_user' => $isSystemUser,
                ]);

                // Persist NEW data to database for future caching
                if (!empty($newData['businesses'])) {
                    $this->persistAssets($connectionId, 'business', $newData['businesses']);
                }
                if (!empty($newData['catalogs'])) {
                    $this->persistAssets($connectionId, 'catalog', $newData['catalogs']);
                }
                if (!empty($newData['whatsapp'])) {
                    $this->persistAssets($connectionId, 'whatsapp', $newData['whatsapp']);
                }
                if (!empty($newData['offline_event_sets'])) {
                    $this->persistAssets($connectionId, 'offline_event_set', $newData['offline_event_sets']);
                }
                if (!empty($newData['pages'])) {
                    $this->persistAssets($connectionId, 'page', $newData['pages']);
                }
                if (!empty($newData['instagram'])) {
                    $this->persistAssets($connectionId, 'instagram', $newData['instagram']);
                }

                return [
                    'businesses' => $businesses,
                    'catalogs' => $catalogs,
                    'whatsapp' => $whatsappAccounts,
                    'offline_event_sets' => $offlineEventSets,
                    'pages' => $businessPages,
                    'instagram' => $businessInstagram,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to fetch business assets', ['error' => $e->getMessage()]);
                return ['businesses' => [], 'catalogs' => [], 'whatsapp' => [], 'offline_event_sets' => [], 'pages' => [], 'instagram' => []];
            }
        });
    }

    /**
     * Deduplicate array of assets by 'id' field
     */
    private function deduplicateById(array $items): array
    {
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if ($id && !isset($seen[$id])) {
                $seen[$id] = true;
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Query multiple businesses in a single batch request using Meta's Batch API.
     * Reduces N sequential API calls to 1 batch call (max 50 per batch).
     *
     * @see https://developers.facebook.com/docs/graph-api/making-multiple-requests
     *
     * @param array $businessIds Array of business IDs to query
     * @param string $accessToken The Meta API access token
     * @return array Array of business data (successful responses only)
     */
    private function batchQueryBusinesses(array $businessIds, string $accessToken): array
    {
        if (empty($businessIds)) {
            return [];
        }

        // Build the fields string - includes pages for business-owned pages
        // Using lighter fields for pages to avoid payload limits
        $fields = implode(',', [
            'id',
            'name',
            'verification_status',
            'owned_product_catalogs.limit(100){id,name,product_count,vertical}',
            'client_product_catalogs.limit(100){id,name,product_count,vertical}',
            'owned_whatsapp_business_accounts.limit(100){id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating,code_verification_status}}',
            // Include pages owned/managed by each business (lighter fields - no nested IG to avoid payload limits)
            'owned_pages.limit(100){id,name,category}',
            'client_pages.limit(100){id,name,category}',
            // Instagram accounts owned by this business (direct access)
            'instagram_accounts.limit(100){id,username,name,profile_picture_url,followers_count,media_count}',
        ]);

        // Build batch request array
        $batchRequests = [];
        foreach ($businessIds as $businessId) {
            $batchRequests[] = [
                'method' => 'GET',
                'relative_url' => $businessId . '?' . http_build_query(['fields' => $fields]),
            ];
        }

        try {
            // Make batch request using POST with form data
            $response = Http::timeout(self::REQUEST_TIMEOUT * 2) // Double timeout for batch
                ->asForm()
                ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                    'access_token' => $accessToken,
                    'include_headers' => 'false',
                    'batch' => json_encode($batchRequests),
                ]);

            if (!$response->successful()) {
                $error = $response->json('error', []);
                Log::warning('Meta Batch API request failed', [
                    'status' => $response->status(),
                    'error_code' => $error['code'] ?? null,
                    'error_message' => $error['message'] ?? 'Unknown error',
                    'batch_size' => count($businessIds),
                ]);
                return [];
            }

            // Parse batch response
            $batchResponses = $response->json();
            $businesses = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($batchResponses as $index => $batchResponse) {
                $businessId = $businessIds[$index] ?? 'unknown';

                // Check if individual request succeeded
                if (($batchResponse['code'] ?? 0) === 200) {
                    $body = json_decode($batchResponse['body'] ?? '{}', true);
                    if (!empty($body['id'])) {
                        $businesses[] = $body;
                        $successCount++;
                    }
                } else {
                    // Individual request failed within batch
                    $errorBody = json_decode($batchResponse['body'] ?? '{}', true);
                    $errorCode = $errorBody['error']['code'] ?? $batchResponse['code'] ?? 0;

                    Log::debug('Individual business query failed in batch', [
                        'business_id' => $businessId,
                        'code' => $batchResponse['code'] ?? 0,
                        'error_code' => $errorCode,
                    ]);

                    $errorCount++;
                }
            }

            Log::debug('Batch query completed', [
                'requested' => count($businessIds),
                'success' => $successCount,
                'errors' => $errorCount,
            ]);

            return $businesses;

        } catch (\Exception $e) {
            Log::error('Exception in batchQueryBusinesses', [
                'error' => $e->getMessage(),
                'batch_size' => count($businessIds),
            ]);
            return [];
        }
    }

    /**
     * Fallback method to get businesses from ad accounts using Batch API.
     * Used when /me/businesses returns empty (common for System User tokens).
     * Extracts unique business IDs from ad accounts and queries in batches of 50.
     *
     * Optimization: Uses Meta's Batch API to query 50 businesses per request,
     * reducing 200 sequential calls to 4 batch calls (98% reduction).
     *
     * @param string $accessToken The Meta API access token
     * @param string $connectionId The connection ID for cache lookup
     * @return array Array of business objects with expanded fields
     */
    private function getBusinessesFromAdAccounts(string $accessToken, string $connectionId): array
    {
        // Get ad account data (may already be cached from previous call)
        $adAccountAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        $adAccounts = $adAccountAssets['ad_accounts'] ?? [];

        if (empty($adAccounts)) {
            Log::info('No ad accounts available for business extraction in fallback');
            return [];
        }

        // Extract unique business IDs from ad accounts
        $businessIds = [];
        foreach ($adAccounts as $account) {
            $businessId = $account['business_id'] ?? null;
            if ($businessId && !isset($businessIds[$businessId])) {
                $businessIds[$businessId] = true;
            }
        }

        if (empty($businessIds)) {
            Log::info('No business IDs found in ad accounts for fallback');
            return [];
        }

        // Limit to prevent API exhaustion and convert to array
        $businessIds = array_slice(array_keys($businessIds), 0, self::MAX_BUSINESSES);

        Log::info('Using Batch API for System User business fallback', [
            'unique_businesses' => count($businessIds),
            'total_ad_accounts' => count($adAccounts),
            'expected_batches' => ceil(count($businessIds) / self::BATCH_SIZE),
        ]);

        // Split into batches and query using Batch API
        $allBusinesses = [];
        $batchChunks = array_chunk($businessIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($batchChunks as $chunk) {
            // Rate limiting between batches (not between individual requests)
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            Log::debug('Processing business batch', [
                'batch' => $batchNumber + 1,
                'of' => count($batchChunks),
                'size' => count($chunk),
            ]);

            $batchResults = $this->batchQueryBusinesses($chunk, $accessToken);
            $allBusinesses = array_merge($allBusinesses, $batchResults);

            $batchNumber++;

            // If a batch returned empty (possible rate limit), stop processing
            if (empty($batchResults) && !empty($chunk)) {
                Log::warning('Batch returned no results, stopping further batch processing', [
                    'batch' => $batchNumber,
                    'businesses_collected' => count($allBusinesses),
                ]);
                break;
            }
        }

        $efficiency = count($businessIds) > 0
            ? round((1 - ($batchNumber / count($businessIds))) * 100, 1) . '% reduction'
            : 'N/A';

        Log::info('System User business fallback completed with Batch API', [
            'businesses_fetched' => count($allBusinesses),
            'api_calls' => $batchNumber,
            'efficiency' => $efficiency,
        ]);

        return $allBusinesses;
    }

    /**
     * Fetch Instagram accounts from pages using Meta's Batch API.
     * Queries pages' instagram_business_account field in batches of 50.
     *
     * @param array $pages Array of pages with 'id', 'name', 'business_id', 'business_name'
     * @param string $accessToken The Meta API access token
     * @return array Array of Instagram account data
     */
    private function batchQueryPagesInstagram(array $pages, string $accessToken): array
    {
        if (empty($pages)) {
            return [];
        }

        // Build page IDs list with metadata for later enrichment
        $pageMetadata = [];
        foreach ($pages as $page) {
            $pageId = $page['id'] ?? null;
            if ($pageId) {
                $pageMetadata[$pageId] = [
                    'page_name' => $page['name'] ?? 'Unknown Page',
                    'business_id' => $page['business_id'] ?? null,
                    'business_name' => $page['business_name'] ?? null,
                ];
            }
        }

        $pageIds = array_keys($pageMetadata);
        if (empty($pageIds)) {
            return [];
        }

        Log::info('Fetching Instagram from business pages using Batch API', [
            'total_pages' => count($pageIds),
            'expected_batches' => ceil(count($pageIds) / self::BATCH_SIZE),
        ]);

        $allInstagram = [];
        $batchChunks = array_chunk($pageIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($batchChunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            // Build batch request for this chunk
            $batchRequests = [];
            foreach ($chunk as $pageId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $pageId . '?' . http_build_query([
                        'fields' => 'instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}',
                    ]),
                ];
            }

            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if (!$response->successful()) {
                    Log::warning('Batch Instagram query failed', [
                        'batch' => $batchNumber + 1,
                        'status' => $response->status(),
                    ]);
                    $batchNumber++;
                    continue;
                }

                $batchResponses = $response->json();
                foreach ($batchResponses as $index => $batchResponse) {
                    $pageId = $chunk[$index] ?? null;
                    if (!$pageId) continue;

                    if (($batchResponse['code'] ?? 0) === 200) {
                        $body = json_decode($batchResponse['body'] ?? '{}', true);
                        $ig = $body['instagram_business_account'] ?? null;

                        if ($ig && !empty($ig['id'])) {
                            $meta = $pageMetadata[$pageId] ?? [];
                            $allInstagram[] = [
                                'id' => $ig['id'],
                                'username' => $ig['username'] ?? null,
                                'name' => $ig['name'] ?? $ig['username'] ?? 'Unknown',
                                'profile_picture' => $ig['profile_picture_url'] ?? null,
                                'followers_count' => $ig['followers_count'] ?? 0,
                                'media_count' => $ig['media_count'] ?? 0,
                                'connected_page_id' => $pageId,
                                'connected_page_name' => $meta['page_name'] ?? 'Unknown Page',
                                'business_id' => $meta['business_id'],
                                'business_name' => $meta['business_name'],
                                'source' => 'business_page',
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Exception in batch Instagram query', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch Instagram query completed', [
            'pages_queried' => count($pageIds),
            'instagram_found' => count($allInstagram),
            'batches_used' => $batchNumber,
        ]);

        return $allInstagram;
    }

    /**
     * Fetch Instagram accounts directly from businesses using Meta's Batch API.
     * Queries the instagram_accounts edge on each business.
     *
     * @param array $businesses Array of businesses with 'id' and 'name'
     * @param string $accessToken The Meta API access token
     * @return array Array of Instagram account data
     */
    private function batchQueryBusinessInstagram(array $businesses, string $accessToken): array
    {
        if (empty($businesses)) {
            return [];
        }

        // Build business metadata for enrichment
        $businessMetadata = [];
        foreach ($businesses as $biz) {
            $bizId = $biz['id'] ?? null;
            if ($bizId) {
                $businessMetadata[$bizId] = [
                    'name' => $biz['name'] ?? 'Unknown Business',
                ];
            }
        }

        $businessIds = array_keys($businessMetadata);
        if (empty($businessIds)) {
            return [];
        }

        Log::info('Fetching Instagram from businesses using Batch API', [
            'total_businesses' => count($businessIds),
            'expected_batches' => ceil(count($businessIds) / self::BATCH_SIZE),
        ]);

        $allInstagram = [];
        $batchChunks = array_chunk($businessIds, self::BATCH_SIZE);
        $batchNumber = 0;

        foreach ($batchChunks as $chunk) {
            if ($batchNumber > 0) {
                usleep(self::DELAY_BETWEEN_BATCHES_MS * 1000);
            }

            // Build batch request for this chunk
            $batchRequests = [];
            foreach ($chunk as $bizId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $bizId . '?' . http_build_query([
                        'fields' => 'instagram_accounts.limit(100){id,username,name,profile_picture_url,followers_count,media_count}',
                    ]),
                ];
            }

            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT * 2)
                    ->asForm()
                    ->post(self::BASE_URL . '/' . self::API_VERSION . '/', [
                        'access_token' => $accessToken,
                        'include_headers' => 'false',
                        'batch' => json_encode($batchRequests),
                    ]);

                if (!$response->successful()) {
                    Log::warning('Batch business Instagram query failed', [
                        'batch' => $batchNumber + 1,
                        'status' => $response->status(),
                    ]);
                    $batchNumber++;
                    continue;
                }

                $batchResponses = $response->json();
                foreach ($batchResponses as $index => $batchResponse) {
                    $bizId = $chunk[$index] ?? null;
                    if (!$bizId) continue;

                    if (($batchResponse['code'] ?? 0) === 200) {
                        $body = json_decode($batchResponse['body'] ?? '{}', true);
                        $igAccounts = $body['instagram_accounts']['data'] ?? [];
                        $meta = $businessMetadata[$bizId] ?? [];

                        foreach ($igAccounts as $ig) {
                            if (!empty($ig['id'])) {
                                $existingIds = array_column($allInstagram, 'id');
                                if (!in_array($ig['id'], $existingIds)) {
                                    $allInstagram[] = [
                                        'id' => $ig['id'],
                                        'username' => $ig['username'] ?? null,
                                        'name' => $ig['name'] ?? $ig['username'] ?? 'Unknown',
                                        'profile_picture' => $ig['profile_picture_url'] ?? null,
                                        'followers_count' => $ig['followers_count'] ?? 0,
                                        'media_count' => $ig['media_count'] ?? 0,
                                        'connected_page_id' => null,
                                        'connected_page_name' => null,
                                        'business_id' => $bizId,
                                        'business_name' => $meta['name'] ?? 'Unknown Business',
                                        'source' => 'business_direct',
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Exception in batch business Instagram query', [
                    'batch' => $batchNumber + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $batchNumber++;
        }

        Log::info('Batch business Instagram query completed', [
            'businesses_queried' => count($businessIds),
            'instagram_found' => count($allInstagram),
            'batches_used' => $batchNumber,
        ]);

        return $allInstagram;
    }

    /**
     * Get Business Managers (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getBusinesses(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['businesses'] ?? [];
    }

    /**
     * Get Custom Conversions (uses shared cache from getAllAdAccountAssets - 0 extra API calls).
     */
    public function getCustomConversions(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_adaccount_assets'));
        }

        $allAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        return $allAssets['custom_conversions'] ?? [];
    }

    /**
     * Get Offline Event Sets (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getOfflineEventSets(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['offline_event_sets'] ?? [];
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function refreshAll(string $connectionId): void
    {
        // Clear the 3 super-query caches (primary optimization)
        $superCaches = [
            'all_user_assets',      // Pages + Instagram
            'all_adaccount_assets', // Ad Accounts + Pixels + Custom Conversions
            'all_business_assets',  // Businesses + Catalogs + WhatsApp + Offline Event Sets
        ];

        // Also clear legacy individual caches (for backwards compatibility)
        $legacyCaches = [
            'pages', 'instagram', 'threads', 'ad_accounts', 'pixels', 'catalogs', 'whatsapp', 'businesses',
            'custom_conversions', 'offline_event_sets'
        ];

        $allCaches = array_merge($superCaches, $legacyCaches);

        foreach ($allCaches as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Cache cleared for all Meta assets', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        // Super-query caches (optimized - 3 API calls total)
        $superCaches = [
            'all_user_assets',      // Pages + Instagram (1 API call)
            'all_adaccount_assets', // Ad Accounts + Pixels + Custom Conversions (1 API call)
            'all_business_assets',  // Businesses + Catalogs + WhatsApp + Offline Event Sets (1 API call)
        ];

        // Individual asset type caches (derived from super-caches)
        $assetTypes = [
            'pages', 'instagram', 'threads', 'ad_accounts', 'pixels', 'catalogs', 'whatsapp',
            'custom_conversions', 'offline_event_sets'
        ];

        $allCacheTypes = array_merge($superCaches, $assetTypes);
        $status = [];

        foreach ($allCacheTypes as $type) {
            $cacheKey = $this->getCacheKey($connectionId, $type);
            $status[$type] = [
                'cached' => Cache::has($cacheKey),
                'key' => $cacheKey,
            ];
        }

        return $status;
    }

    /**
     * Get label for Meta ad account status.
     */
    private function getAccountStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Active',
            2 => 'Disabled',
            3 => 'Unsettled',
            7 => 'Pending Risk Review',
            8 => 'Pending Settlement',
            9 => 'In Grace Period',
            100 => 'Pending Closure',
            101 => 'Closed',
            201 => 'Any Active',
            202 => 'Any Closed',
            default => 'Unknown',
        };
    }

    /**
     * Get label for Meta ad account disable reason.
     */
    private function getDisableReasonLabel(int $reason): string
    {
        return match ($reason) {
            0 => 'None',
            1 => 'Ads violate policy',
            2 => 'Ads and business violate policy',
            3 => 'Unusual activity',
            4 => 'Business information incomplete',
            5 => 'Chargeback',
            6 => 'Overdue balance',
            7 => 'Gray area and abuse',
            8 => 'Risky payment pattern',
            9 => 'Risk assessment',
            10 => 'Business verification issues',
            default => 'Unknown',
        };
    }
}
