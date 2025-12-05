<?php

namespace App\Repositories\Contracts;

use App\Models\Platform\PlatformAsset;
use Illuminate\Support\Collection;

/**
 * Platform Asset Repository Interface
 *
 * Defines the contract for managing platform assets in the database.
 * Used by AssetPersistenceService for the three-tier caching strategy.
 */
interface PlatformAssetRepositoryInterface
{
    /**
     * Find an asset by platform identifiers or create it
     *
     * @param string $platform Platform identifier (meta, google, tiktok, etc.)
     * @param string $platformAssetId External ID from the platform
     * @param string $assetType Asset type (page, instagram, ad_account, etc.)
     * @param array $data Additional data for creation/update
     * @return PlatformAsset
     */
    public function findOrCreate(
        string $platform,
        string $platformAssetId,
        string $assetType,
        array $data = []
    ): PlatformAsset;

    /**
     * Bulk upsert multiple assets
     *
     * @param string $platform Platform identifier
     * @param string $assetType Asset type
     * @param array $assets Array of asset data from API
     * @param string|null $syncSource Connection ID that triggered sync
     * @return int Number of assets upserted
     */
    public function bulkUpsert(
        string $platform,
        string $assetType,
        array $assets,
        ?string $syncSource = null
    ): int;

    /**
     * Get assets that are stale (need refresh)
     *
     * @param string $platform Platform identifier
     * @param int $hoursStale Hours since last sync to consider stale
     * @param int $limit Maximum number of assets to return
     * @return Collection<PlatformAsset>
     */
    public function getStaleAssets(
        string $platform,
        int $hoursStale = 6,
        int $limit = 100
    ): Collection;

    /**
     * Get assets by platform and type
     *
     * @param string $platform Platform identifier
     * @param string $assetType Asset type
     * @return Collection<PlatformAsset>
     */
    public function getByPlatformAndType(string $platform, string $assetType): Collection;

    /**
     * Get fresh assets for a connection (within freshness threshold)
     *
     * @param string $platform Platform identifier
     * @param string $assetType Asset type
     * @param string $connectionId Connection that has access
     * @param int $hoursFresh Hours to consider fresh
     * @return Collection<PlatformAsset>
     */
    public function getFreshAssets(
        string $platform,
        string $assetType,
        string $connectionId,
        int $hoursFresh = 6
    ): Collection;

    /**
     * Mark assets as inactive
     *
     * @param array $assetIds Array of asset UUIDs
     * @return int Number of assets marked inactive
     */
    public function markInactive(array $assetIds): int;

    /**
     * Mark assets as active
     *
     * @param array $assetIds Array of asset UUIDs
     * @return int Number of assets marked active
     */
    public function markActive(array $assetIds): int;

    /**
     * Record org access to an asset via a connection
     *
     * @param string $orgId Organization UUID
     * @param string $assetId Asset UUID
     * @param string $connectionId Connection UUID
     * @param array $accessData Access types, permissions, roles
     * @return void
     */
    public function recordOrgAccess(
        string $orgId,
        string $assetId,
        string $connectionId,
        array $accessData = []
    ): void;

    /**
     * Record a relationship between assets
     *
     * @param string $parentAssetId Parent asset UUID
     * @param string $childAssetId Child asset UUID
     * @param string $relationshipType Type of relationship
     * @param array $relationshipData Additional relationship data
     * @return void
     */
    public function recordRelationship(
        string $parentAssetId,
        string $childAssetId,
        string $relationshipType,
        array $relationshipData = []
    ): void;

    /**
     * Get assets accessible by an organization for a platform/type
     *
     * @param string $orgId Organization UUID
     * @param string $platform Platform identifier
     * @param string $assetType Asset type
     * @param bool $selectedOnly Only return selected assets
     * @return Collection<PlatformAsset>
     */
    public function getOrgAssets(
        string $orgId,
        string $platform,
        string $assetType,
        bool $selectedOnly = false
    ): Collection;

    /**
     * Verify access records are still valid via API
     *
     * @param string $connectionId Connection UUID
     * @return int Number of access records verified
     */
    public function verifyAccessForConnection(string $connectionId): int;

    /**
     * Clean up stale access records
     *
     * @param int $daysStale Days since last verification
     * @return int Number of access records cleaned
     */
    public function cleanupStaleAccess(int $daysStale = 7): int;

    /**
     * Log an API call for tracking/analytics
     *
     * @param string $platform Platform identifier
     * @param string $connectionId Connection UUID
     * @param string $endpoint API endpoint called
     * @param int $httpStatus HTTP status code
     * @param int $durationMs Request duration in milliseconds
     * @param bool $success Whether call was successful
     * @return void
     */
    public function logApiCall(
        string $platform,
        string $connectionId,
        string $endpoint,
        int $httpStatus,
        int $durationMs,
        bool $success
    ): void;
}
