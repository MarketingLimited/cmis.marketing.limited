<?php

namespace App\Services\Platform;

use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use App\Services\RateLimiter\PlatformRateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Asset Persistence Service
 *
 * Implements the three-tier caching strategy for platform assets:
 * 1. Cache (15 min TTL) - Fastest, for repeated requests
 * 2. Database (6 hr fresh) - Persistent, cross-org shared
 * 3. Platform API - Source of truth, rate-limited
 *
 * Benefits:
 * - 90%+ reduction in redundant API calls
 * - Cross-org asset sharing (same Page = 1 DB record)
 * - Historical tracking (first_seen_at, sync_count)
 * - Rate limit protection via PlatformRateLimiter
 *
 * Usage in platform services:
 * ```php
 * return $this->persistenceService->getAssets(
 *     $connectionId,
 *     'meta',
 *     'page',
 *     fn() => $this->fetchPagesFromApi($accessToken),
 *     $forceRefresh
 * );
 * ```
 */
class AssetPersistenceService
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Database freshness threshold in hours
     */
    private const DB_FRESHNESS_HOURS = 6;

    /**
     * @var PlatformAssetRepositoryInterface
     */
    protected PlatformAssetRepositoryInterface $repository;

    /**
     * @var PlatformRateLimiter
     */
    protected PlatformRateLimiter $rateLimiter;

    public function __construct(
        PlatformAssetRepositoryInterface $repository,
        PlatformRateLimiter $rateLimiter
    ) {
        $this->repository = $repository;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Get assets using three-tier retrieval strategy
     *
     * @param string $connectionId Connection UUID
     * @param string $platform Platform identifier (meta, google, tiktok, etc.)
     * @param string $assetType Asset type (page, instagram, ad_account, etc.)
     * @param callable $apiFetcher Callable that fetches from platform API
     * @param bool $forceRefresh Force API refresh bypassing cache and DB
     * @param string|null $orgId Organization UUID (optional, for access tracking)
     * @return array Array of asset data
     */
    public function getAssets(
        string $connectionId,
        string $platform,
        string $assetType,
        callable $apiFetcher,
        bool $forceRefresh = false,
        ?string $orgId = null
    ): array {
        $cacheKey = $this->getCacheKey($platform, $connectionId, $assetType);
        $startTime = microtime(true);

        // Tier 1: Cache (15 min TTL)
        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::debug("Asset cache hit", [
                    'platform' => $platform,
                    'asset_type' => $assetType,
                    'connection_id' => $connectionId,
                    'count' => count($cached),
                ]);
                return $cached;
            }
        }

        // Tier 2: Database (fresh = less than 6 hours old)
        if (!$forceRefresh) {
            $dbAssets = $this->repository->getFreshAssets(
                $platform,
                $assetType,
                $connectionId,
                self::DB_FRESHNESS_HOURS
            );

            if ($dbAssets->isNotEmpty()) {
                $result = $dbAssets->pluck('asset_data')->toArray();

                // Update cache from DB
                Cache::put($cacheKey, $result, self::CACHE_TTL);

                Log::debug("Asset DB hit", [
                    'platform' => $platform,
                    'asset_type' => $assetType,
                    'connection_id' => $connectionId,
                    'count' => count($result),
                ]);

                return $result;
            }
        }

        // Tier 3: Platform API
        // Check rate limiting first
        if (!$this->rateLimiter->attempt($platform, $connectionId)) {
            Log::warning("Rate limited - returning stale DB data", [
                'platform' => $platform,
                'asset_type' => $assetType,
                'connection_id' => $connectionId,
            ]);

            // Return stale data if available
            $staleAssets = $this->repository->getByPlatformAndType($platform, $assetType);
            if ($staleAssets->isNotEmpty()) {
                return $staleAssets->pluck('asset_data')->toArray();
            }

            return [];
        }

        try {
            // Call the platform API
            $apiAssets = $apiFetcher();
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // Log the API call
            $this->repository->logApiCall(
                $platform,
                $connectionId,
                "/{$assetType}",
                200,
                $duration,
                true
            );

            // Persist to database
            $this->persistAssets(
                $platform,
                $assetType,
                $apiAssets,
                $connectionId,
                $orgId
            );

            // Update cache
            Cache::put($cacheKey, $apiAssets, self::CACHE_TTL);

            Log::debug("Asset API fetch", [
                'platform' => $platform,
                'asset_type' => $assetType,
                'connection_id' => $connectionId,
                'count' => count($apiAssets),
                'duration_ms' => $duration,
            ]);

            return $apiAssets;

        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // Log the failed API call
            $this->repository->logApiCall(
                $platform,
                $connectionId,
                "/{$assetType}",
                500,
                $duration,
                false
            );

            Log::error("Asset API fetch failed", [
                'platform' => $platform,
                'asset_type' => $assetType,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);

            // Return stale data on failure
            $staleAssets = $this->repository->getByPlatformAndType($platform, $assetType);
            if ($staleAssets->isNotEmpty()) {
                Log::info("Returning stale data after API failure", [
                    'platform' => $platform,
                    'asset_type' => $assetType,
                    'count' => $staleAssets->count(),
                ]);
                return $staleAssets->pluck('asset_data')->toArray();
            }

            throw $e;
        }
    }

    /**
     * Persist assets to database
     *
     * @param string $platform Platform identifier
     * @param string $assetType Asset type
     * @param array $assets Array of asset data from API
     * @param string $connectionId Connection UUID
     * @param string|null $orgId Organization UUID
     * @return int Number of assets persisted
     */
    public function persistAssets(
        string $platform,
        string $assetType,
        array $assets,
        string $connectionId,
        ?string $orgId = null
    ): int {
        if (empty($assets)) {
            return 0;
        }

        // Bulk upsert assets
        $count = $this->repository->bulkUpsert(
            $platform,
            $assetType,
            $assets,
            $connectionId
        );

        // Record org access if org_id provided
        if ($orgId) {
            foreach ($assets as $assetData) {
                $asset = $this->repository->findOrCreate(
                    $platform,
                    $this->extractAssetId($assetData, $platform, $assetType),
                    $assetType,
                    $assetData
                );

                $this->repository->recordOrgAccess(
                    $orgId,
                    $asset->asset_id,
                    $connectionId,
                    [
                        'access_types' => $this->inferAccessTypes($assetData),
                        'permissions' => $assetData['permissions'] ?? $assetData['permitted_tasks'] ?? [],
                        'roles' => $assetData['roles'] ?? [],
                    ]
                );
            }
        }

        return $count;
    }

    /**
     * Record an asset relationship
     *
     * @param string $parentPlatformId Parent platform asset ID
     * @param string $childPlatformId Child platform asset ID
     * @param string $platform Platform identifier
     * @param string $parentType Parent asset type
     * @param string $childType Child asset type
     * @param string $relationshipType Relationship type
     * @param array $data Additional relationship data
     * @return void
     */
    public function recordRelationship(
        string $parentPlatformId,
        string $childPlatformId,
        string $platform,
        string $parentType,
        string $childType,
        string $relationshipType,
        array $data = []
    ): void {
        $parentAsset = $this->repository->getByPlatformId($platform, $parentPlatformId, $parentType);
        $childAsset = $this->repository->getByPlatformId($platform, $childPlatformId, $childType);

        if ($parentAsset && $childAsset) {
            $this->repository->recordRelationship(
                $parentAsset->asset_id,
                $childAsset->asset_id,
                $relationshipType,
                $data
            );
        }
    }

    /**
     * Get assets for an organization
     *
     * @param string $orgId Organization UUID
     * @param string $platform Platform identifier
     * @param string $assetType Asset type
     * @param bool $selectedOnly Only return selected assets
     * @return array Array of asset data
     */
    public function getOrgAssets(
        string $orgId,
        string $platform,
        string $assetType,
        bool $selectedOnly = false
    ): array {
        $assets = $this->repository->getOrgAssets(
            $orgId,
            $platform,
            $assetType,
            $selectedOnly
        );

        return $assets->pluck('asset_data')->toArray();
    }

    /**
     * Clear cache for a specific connection/asset type
     *
     * @param string $connectionId Connection UUID
     * @param string $platform Platform identifier
     * @param string|null $assetType Asset type (null = all types)
     * @return void
     */
    public function clearCache(
        string $connectionId,
        string $platform,
        ?string $assetType = null
    ): void {
        if ($assetType) {
            Cache::forget($this->getCacheKey($platform, $connectionId, $assetType));
        } else {
            // Clear all asset type caches for this connection
            $assetTypes = $this->getAssetTypesForPlatform($platform);
            foreach ($assetTypes as $type) {
                Cache::forget($this->getCacheKey($platform, $connectionId, $type));
            }
        }

        Log::info("Asset cache cleared", [
            'platform' => $platform,
            'connection_id' => $connectionId,
            'asset_type' => $assetType ?? 'all',
        ]);
    }

    /**
     * Get cache key for assets
     */
    private function getCacheKey(string $platform, string $connectionId, string $assetType): string
    {
        return "{$platform}_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Extract asset ID from asset data
     */
    private function extractAssetId(array $data, string $platform, string $assetType): string
    {
        // Common ID fields
        $idFields = ['id', 'account_id', 'page_id', 'pixel_id', 'catalog_id', 'channel_id'];

        foreach ($idFields as $field) {
            if (isset($data[$field])) {
                return (string) $data[$field];
            }
        }

        // Platform-specific extractions
        return match($platform) {
            'meta' => $data['instagram_business_account']['id']
                ?? $data['instagram_accounts']['data'][0]['id']
                ?? '',
            'google' => $data['customerId'] ?? $data['channelId'] ?? '',
            'tiktok' => $data['advertiser_id'] ?? $data['bc_id'] ?? '',
            default => '',
        };
    }

    /**
     * Infer access types from asset data
     */
    private function inferAccessTypes(array $data): array
    {
        $types = ['read'];

        // Check for write/admin access indicators
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

        // Check for role-based access
        if (isset($data['role'])) {
            $role = strtolower($data['role']);
            if (in_array($role, ['admin', 'owner'])) {
                $types = array_merge($types, ['write', 'admin', 'publish', 'analyze']);
            } elseif (in_array($role, ['editor', 'advertiser'])) {
                $types = array_merge($types, ['write', 'publish']);
            }
        }

        return array_unique($types);
    }

    /**
     * Get asset types for a platform
     */
    private function getAssetTypesForPlatform(string $platform): array
    {
        return match($platform) {
            'meta' => [
                'page',
                'instagram',
                'threads',
                'ad_account',
                'pixel',
                'catalog',
                'whatsapp',
                'business',
                'custom_conversion',
                'offline_event_set',
            ],
            'google' => [
                'account',
                'ad_account',
                'youtube_channel',
                'analytics_property',
                'campaign',
            ],
            'tiktok' => [
                'advertiser',
                'business_center',
                'pixel',
                'catalog',
            ],
            'linkedin' => [
                'organization',
                'ad_account',
            ],
            'twitter' => [
                'account',
                'ad_account',
            ],
            'snapchat' => [
                'organization',
                'ad_account',
            ],
            'pinterest' => [
                'account',
                'board',
                'ad_account',
            ],
            default => [],
        };
    }

    /**
     * Get repository instance (for advanced operations)
     */
    public function getRepository(): PlatformAssetRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Get rate limiter instance
     */
    public function getRateLimiter(): PlatformRateLimiter
    {
        return $this->rateLimiter;
    }

    /**
     * Check if API call is allowed (rate limit check without incrementing)
     */
    public function canMakeApiCall(string $platform, string $connectionId): bool
    {
        $remaining = $this->rateLimiter->remaining($platform, $connectionId);
        return $remaining['remaining'] > 0;
    }

    /**
     * Get rate limit status
     */
    public function getRateLimitStatus(string $platform, string $connectionId): array
    {
        return $this->rateLimiter->remaining($platform, $connectionId);
    }
}
