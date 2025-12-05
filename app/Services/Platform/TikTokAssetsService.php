<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching TikTok Business assets with caching.
 *
 * Features:
 * - Redis caching with 1-hour TTL
 * - Database persistence for three-tier caching (Cache → DB → API)
 * - Parallel-friendly design for AJAX loading
 * - Token handling for TikTok Business API
 *
 * Asset Types:
 * - TikTok Accounts (for video publishing via Login Kit)
 * - Advertiser Accounts (Ad Accounts)
 * - Pixels (Conversion Tracking)
 * - Catalogs (Product Catalogs)
 */
class TikTokAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const REQUEST_TIMEOUT = 30;
    private const API_BASE_URL = 'https://business-api.tiktok.com/open_api/v1.3';

    /**
     * Platform asset repository for database persistence.
     */
    protected ?PlatformAssetRepositoryInterface $repository;

    /**
     * Organization ID for recording asset access.
     */
    protected ?string $orgId = null;

    public function __construct(?PlatformAssetRepositoryInterface $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Set the organization ID for asset access recording.
     */
    public function setOrgId(string $orgId): self
    {
        $this->orgId = $orgId;
        return $this;
    }

    /**
     * Get cache key for a specific asset type and connection.
     */
    private function getCacheKey(string $connectionId, string $assetType): string
    {
        return "tiktok_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Make a TikTok Business API request.
     */
    private function makeApiRequest(string $url, string $accessToken, array $params = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->timeout(self::REQUEST_TIMEOUT)->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                // TikTok API returns code: 0 for success
                if (($data['code'] ?? -1) === 0) {
                    return $data['data'] ?? [];
                }
                Log::warning('TikTok API returned error code', [
                    'url' => $url,
                    'code' => $data['code'] ?? 'unknown',
                    'message' => $data['message'] ?? 'Unknown error',
                ]);
            } else {
                Log::warning('TikTok API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('TikTok API exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get TikTok accounts for video publishing.
     * These are separate from Business API connections.
     */
    public function getTikTokAccounts(string $orgId, bool $forceRefresh = false): array
    {
        $cacheKey = "tiktok_assets:{$orgId}:tiktok_accounts";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId) {
            Log::info('Fetching TikTok accounts for publishing', ['org_id' => $orgId]);

            $accounts = PlatformConnection::where('org_id', $orgId)
                ->where('platform', 'tiktok')
                ->where('status', 'active')
                ->get()
                ->map(function ($connection) {
                    return [
                        'id' => $connection->connection_id,
                        'account_name' => $connection->account_name,
                        'account_id' => $connection->account_id,
                        'status' => $connection->status,
                        'token_expires_at' => $connection->token_expires_at?->toIso8601String(),
                        'is_expired' => $connection->token_expires_at?->isPast() ?? false,
                        'expires_soon' => $connection->token_expires_at
                            ? $connection->token_expires_at->diffInHours(now()) < 24 && !$connection->token_expires_at->isPast()
                            : false,
                        'created_at' => $connection->created_at?->toIso8601String(),
                    ];
                })
                ->toArray();

            Log::info('TikTok accounts fetched', ['count' => count($accounts)]);

            // Persist to database for three-tier caching
            // Note: For org-based accounts, we use the first connection_id as sync source
            $firstConnectionId = $accounts[0]['id'] ?? null;
            $this->persistAssets($firstConnectionId, 'tiktok_account', $accounts);

            return $accounts;
        });
    }

    /**
     * Get advertiser (ad) accounts from TikTok Business API.
     */
    public function getAdvertisers(string $connectionId, string $accessToken, array $advertiserIds = [], bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'advertisers');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken, $advertiserIds) {
            Log::info('Fetching TikTok advertisers from API');

            if (empty($advertiserIds)) {
                Log::info('No advertiser IDs available');
                return [];
            }

            $config = config('social-platforms.tiktok_ads');
            $url = $config['advertiser_url'] ?? (self::API_BASE_URL . '/oauth2/advertiser/get/');

            $data = $this->makeApiRequest($url, $accessToken, [
                'app_id' => $config['app_id'],
                'secret' => $config['app_secret'],
            ]);

            if ($data === null) {
                // Fallback to showing just the IDs
                Log::warning('Falling back to ID-based advertiser list');
                $fallbackAdvertisers = array_map(function ($id) {
                    return [
                        'advertiser_id' => $id,
                        'advertiser_name' => 'Ad Account ' . $id,
                        'status' => 'unknown',
                    ];
                }, $advertiserIds);

                // Persist fallback advertisers
                $this->persistAssets($connectionId, 'advertiser', $fallbackAdvertisers);

                return $fallbackAdvertisers;
            }

            $advertisers = $data['list'] ?? [];
            Log::info('TikTok advertisers fetched', ['count' => count($advertisers)]);

            $formattedAdvertisers = array_map(function ($advertiser) {
                return [
                    'advertiser_id' => $advertiser['advertiser_id'] ?? '',
                    'advertiser_name' => $advertiser['advertiser_name'] ?? $advertiser['name'] ?? 'Unknown',
                    'status' => $advertiser['status'] ?? 'unknown',
                    'currency' => $advertiser['currency'] ?? null,
                    'timezone' => $advertiser['timezone'] ?? null,
                ];
            }, $advertisers);

            // Persist to database for three-tier caching
            $this->persistAssets($connectionId, 'advertiser', $formattedAdvertisers);

            return $formattedAdvertisers;
        });
    }

    /**
     * Get pixels from TikTok Business API.
     * Fetches pixels for all provided advertiser IDs.
     */
    public function getPixels(string $connectionId, string $accessToken, array $advertiserIds = [], bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'pixels');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken, $advertiserIds) {
            Log::info('Fetching TikTok pixels from API', ['advertiser_count' => count($advertiserIds)]);

            if (empty($advertiserIds)) {
                Log::info('No advertiser IDs available for pixel fetch');
                return [];
            }

            $allPixels = [];
            $url = self::API_BASE_URL . '/pixel/list/';

            foreach ($advertiserIds as $advertiserId) {
                $data = $this->makeApiRequest($url, $accessToken, [
                    'advertiser_id' => $advertiserId,
                ]);

                if ($data !== null && isset($data['pixels'])) {
                    foreach ($data['pixels'] as $pixel) {
                        $allPixels[] = [
                            'pixel_id' => $pixel['pixel_id'] ?? $pixel['id'] ?? '',
                            'pixel_name' => $pixel['pixel_name'] ?? $pixel['name'] ?? 'Unknown Pixel',
                            'pixel_code' => $pixel['pixel_code'] ?? null,
                            'advertiser_id' => $advertiserId,
                            'status' => $pixel['status'] ?? 'unknown',
                        ];
                    }
                }
            }

            Log::info('TikTok pixels fetched', ['count' => count($allPixels)]);

            // Persist to database for three-tier caching
            $this->persistAssets($connectionId, 'pixel', $allPixels);

            return $allPixels;
        });
    }

    /**
     * Get catalogs from TikTok Business API.
     * Fetches catalogs for all provided advertiser/business center IDs.
     */
    public function getCatalogs(string $connectionId, string $accessToken, array $advertiserIds = [], bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'catalogs');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken, $advertiserIds) {
            Log::info('Fetching TikTok catalogs from API', ['advertiser_count' => count($advertiserIds)]);

            if (empty($advertiserIds)) {
                Log::info('No advertiser IDs available for catalog fetch');
                return [];
            }

            $allCatalogs = [];
            $url = self::API_BASE_URL . '/catalog/list/';

            foreach ($advertiserIds as $advertiserId) {
                $data = $this->makeApiRequest($url, $accessToken, [
                    'bc_id' => $advertiserId, // Business Center ID
                ]);

                if ($data !== null && isset($data['list'])) {
                    foreach ($data['list'] as $catalog) {
                        $allCatalogs[] = [
                            'catalog_id' => $catalog['catalog_id'] ?? $catalog['id'] ?? '',
                            'catalog_name' => $catalog['catalog_name'] ?? $catalog['name'] ?? 'Unknown Catalog',
                            'advertiser_id' => $advertiserId,
                            'status' => $catalog['status'] ?? 'unknown',
                            'product_count' => $catalog['product_count'] ?? 0,
                        ];
                    }
                }
            }

            Log::info('TikTok catalogs fetched', ['count' => count($allCatalogs)]);

            // Persist to database for three-tier caching
            $this->persistAssets($connectionId, 'catalog', $allCatalogs);

            return $allCatalogs;
        });
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function clearCache(string $connectionId, ?string $orgId = null): void
    {
        $assetTypes = ['advertisers', 'pixels', 'catalogs'];

        foreach ($assetTypes as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        // Also clear TikTok accounts cache if org ID provided
        if ($orgId) {
            Cache::forget("tiktok_assets:{$orgId}:tiktok_accounts");
        }

        Log::info('TikTok assets cache cleared', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId, ?string $orgId = null): array
    {
        $status = [];
        $assetTypes = ['advertisers', 'pixels', 'catalogs'];

        foreach ($assetTypes as $type) {
            $status[$type] = Cache::has($this->getCacheKey($connectionId, $type));
        }

        if ($orgId) {
            $status['tiktok_accounts'] = Cache::has("tiktok_assets:{$orgId}:tiktok_accounts");
        }

        return $status;
    }

    /**
     * Delete a TikTok account connection.
     */
    public function deleteTikTokAccount(string $orgId, string $connectionId): bool
    {
        try {
            $connection = PlatformConnection::where('connection_id', $connectionId)
                ->where('org_id', $orgId)
                ->where('platform', 'tiktok')
                ->first();

            if (!$connection) {
                return false;
            }

            $connection->delete();

            // Clear the cache
            Cache::forget("tiktok_assets:{$orgId}:tiktok_accounts");

            Log::info('TikTok account deleted', [
                'connection_id' => $connectionId,
                'org_id' => $orgId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete TikTok account', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Persist assets to database for three-tier caching.
     *
     * @param string|null $connectionId Connection UUID (null for org-based accounts)
     * @param string $assetType Asset type (advertiser, pixel, catalog, tiktok_account)
     * @param array $assets Array of asset data
     */
    protected function persistAssets(?string $connectionId, string $assetType, array $assets): void
    {
        if (!$this->repository || empty($assets)) {
            return;
        }

        try {
            $count = $this->repository->bulkUpsert('tiktok', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId && $connectionId) {
                foreach ($assets as $assetData) {
                    $assetId = $this->extractAssetId($assetData, $assetType);
                    if (!$assetId) {
                        continue;
                    }

                    $asset = $this->repository->findOrCreate(
                        'tiktok',
                        $assetId,
                        $assetType,
                        $assetData
                    );

                    $this->repository->recordOrgAccess(
                        $this->orgId,
                        $asset->asset_id,
                        $connectionId,
                        [
                            'access_types' => $this->inferAccessTypes($assetData),
                            'permissions' => [],
                            'roles' => [],
                        ]
                    );
                }
            }

            Log::debug("Persisted TikTok {$assetType} assets to database", [
                'connection_id' => $connectionId,
                'count' => $count,
                'org_id' => $this->orgId,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to persist TikTok {$assetType} assets", [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract asset ID from asset data based on asset type.
     */
    protected function extractAssetId(array $data, string $assetType): ?string
    {
        return match ($assetType) {
            'tiktok_account' => $data['id'] ?? $data['account_id'] ?? null,
            'advertiser' => $data['advertiser_id'] ?? null,
            'pixel' => $data['pixel_id'] ?? $data['id'] ?? null,
            'catalog' => $data['catalog_id'] ?? $data['id'] ?? null,
            default => $data['id'] ?? null,
        };
    }

    /**
     * Infer access types from asset data.
     */
    protected function inferAccessTypes(array $data): array
    {
        $types = ['read'];

        // Check status
        $status = strtolower($data['status'] ?? 'unknown');
        if (in_array($status, ['active', 'enabled'])) {
            $types[] = 'write';
        }

        // Advertisers with enabled status have write access
        if (isset($data['advertiser_id']) && $status === 'active') {
            $types[] = 'admin';
        }

        return array_unique($types);
    }
}
