<?php

namespace App\Repositories\Platform;

use App\Models\Platform\AssetRelationship;
use App\Models\Platform\OrgAssetAccess;
use App\Models\Platform\PlatformAsset;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Platform Asset Repository Implementation
 *
 * Handles all database operations for platform assets, org access, and relationships.
 * Used by AssetPersistenceService for the three-tier caching strategy:
 * Cache (15min) → Database (6hr fresh) → Platform API
 */
class PlatformAssetRepository implements PlatformAssetRepositoryInterface
{
    /**
     * Find an asset by platform identifiers or create it
     */
    public function findOrCreate(
        string $platform,
        string $platformAssetId,
        string $assetType,
        array $data = []
    ): PlatformAsset {
        return PlatformAsset::firstOrCreate(
            [
                'platform' => $platform,
                'platform_asset_id' => $platformAssetId,
                'asset_type' => $assetType,
            ],
            array_merge([
                'asset_name' => $data['name'] ?? $data['title'] ?? null,
                'asset_data' => $data,
                'ownership_type' => $data['ownership_type'] ?? 'unknown',
                'business_id' => $data['business_id'] ?? null,
                'business_name' => $data['business_name'] ?? null,
                'first_seen_at' => now(),
                'last_synced_at' => now(),
                'sync_count' => 1,
                'is_active' => true,
            ], $data)
        );
    }

    /**
     * Bulk upsert multiple assets
     */
    public function bulkUpsert(
        string $platform,
        string $assetType,
        array $assets,
        ?string $syncSource = null
    ): int {
        if (empty($assets)) {
            return 0;
        }

        $count = 0;

        foreach ($assets as $assetData) {
            // Get the platform-specific ID
            $platformAssetId = $this->extractPlatformAssetId($assetData, $platform, $assetType);

            if (!$platformAssetId) {
                continue;
            }

            $asset = PlatformAsset::updateOrCreate(
                [
                    'platform' => $platform,
                    'platform_asset_id' => $platformAssetId,
                    'asset_type' => $assetType,
                ],
                [
                    'asset_name' => $assetData['name'] ?? $assetData['title'] ?? $assetData['username'] ?? null,
                    'asset_data' => $assetData,
                    'ownership_type' => $assetData['ownership_type'] ?? $this->inferOwnershipType($assetData),
                    'business_id' => $assetData['business_id'] ?? $assetData['business']?->id ?? null,
                    'business_name' => $assetData['business_name'] ?? $assetData['business']?->name ?? null,
                    'last_synced_at' => now(),
                    'last_sync_source' => $syncSource,
                    'is_active' => true,
                ]
            );

            // Increment sync count for existing records
            if (!$asset->wasRecentlyCreated) {
                $asset->increment('sync_count');
            }

            $count++;
        }

        return $count;
    }

    /**
     * Get assets that are stale (need refresh)
     */
    public function getStaleAssets(
        string $platform,
        int $hoursStale = 6,
        int $limit = 100
    ): Collection {
        return PlatformAsset::forPlatform($platform)
            ->active()
            ->stale($hoursStale)
            ->limit($limit)
            ->get();
    }

    /**
     * Get assets by platform and type
     */
    public function getByPlatformAndType(string $platform, string $assetType): Collection
    {
        return PlatformAsset::forPlatformAndType($platform, $assetType)
            ->active()
            ->get();
    }

    /**
     * Get fresh assets for a connection (within freshness threshold)
     */
    public function getFreshAssets(
        string $platform,
        string $assetType,
        string $connectionId,
        int $hoursFresh = 6
    ): Collection {
        return PlatformAsset::forPlatformAndType($platform, $assetType)
            ->active()
            ->where('last_synced_at', '>=', now()->subHours($hoursFresh))
            ->whereHas('orgAccess', function ($query) use ($connectionId) {
                $query->where('connection_id', $connectionId)
                    ->where('is_active', true);
            })
            ->get();
    }

    /**
     * Mark assets as inactive
     */
    public function markInactive(array $assetIds): int
    {
        if (empty($assetIds)) {
            return 0;
        }

        return PlatformAsset::whereIn('asset_id', $assetIds)
            ->update(['is_active' => false]);
    }

    /**
     * Mark assets as active
     */
    public function markActive(array $assetIds): int
    {
        if (empty($assetIds)) {
            return 0;
        }

        return PlatformAsset::whereIn('asset_id', $assetIds)
            ->update(['is_active' => true]);
    }

    /**
     * Record org access to an asset via a connection
     */
    public function recordOrgAccess(
        string $orgId,
        string $assetId,
        string $connectionId,
        array $accessData = []
    ): void {
        OrgAssetAccess::updateOrCreate(
            [
                'org_id' => $orgId,
                'asset_id' => $assetId,
                'connection_id' => $connectionId,
            ],
            [
                'access_types' => $accessData['access_types'] ?? ['read'],
                'permissions' => $accessData['permissions'] ?? [],
                'roles' => $accessData['roles'] ?? [],
                'last_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Increment verification count for existing records
        OrgAssetAccess::where('org_id', $orgId)
            ->where('asset_id', $assetId)
            ->where('connection_id', $connectionId)
            ->increment('verification_count');
    }

    /**
     * Record a relationship between assets
     */
    public function recordRelationship(
        string $parentAssetId,
        string $childAssetId,
        string $relationshipType,
        array $relationshipData = []
    ): void {
        AssetRelationship::updateOrCreate(
            [
                'parent_asset_id' => $parentAssetId,
                'child_asset_id' => $childAssetId,
                'relationship_type' => $relationshipType,
            ],
            [
                'relationship_data' => $relationshipData,
                'last_verified_at' => now(),
            ]
        );
    }

    /**
     * Get assets accessible by an organization for a platform/type
     */
    public function getOrgAssets(
        string $orgId,
        string $platform,
        string $assetType,
        bool $selectedOnly = false
    ): Collection {
        $query = PlatformAsset::forPlatformAndType($platform, $assetType)
            ->active()
            ->whereHas('orgAccess', function ($query) use ($orgId, $selectedOnly) {
                $query->where('org_id', $orgId)
                    ->where('is_active', true);

                if ($selectedOnly) {
                    $query->where('is_selected', true);
                }
            })
            ->with(['orgAccess' => function ($query) use ($orgId) {
                $query->where('org_id', $orgId)
                    ->where('is_active', true);
            }]);

        return $query->get();
    }

    /**
     * Verify access records are still valid via API
     */
    public function verifyAccessForConnection(string $connectionId): int
    {
        // Mark all access records for this connection as verified
        return OrgAssetAccess::where('connection_id', $connectionId)
            ->where('is_active', true)
            ->update([
                'last_verified_at' => now(),
            ]);
    }

    /**
     * Clean up stale access records
     */
    public function cleanupStaleAccess(int $daysStale = 7): int
    {
        return OrgAssetAccess::where('last_verified_at', '<', now()->subDays($daysStale))
            ->update(['is_active' => false]);
    }

    /**
     * Log an API call for tracking/analytics
     */
    public function logApiCall(
        string $platform,
        string $connectionId,
        string $endpoint,
        int $httpStatus,
        int $durationMs,
        bool $success
    ): void {
        // Get org_id from connection
        $orgId = DB::table('cmis.platform_connections')
            ->where('connection_id', $connectionId)
            ->value('org_id');

        DB::table('cmis.platform_api_calls')->insert([
            'call_id' => Str::uuid(),
            'org_id' => $orgId,
            'connection_id' => $connectionId,
            'platform' => $platform,
            'endpoint' => $endpoint,
            'method' => 'GET',
            'action_type' => 'asset_sync',
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'success' => $success,
            'called_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Extract platform asset ID from asset data
     */
    private function extractPlatformAssetId(array $data, string $platform, string $assetType): ?string
    {
        // Common ID fields across platforms
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
                ?? null,
            'google' => $data['customerId'] ?? $data['channelId'] ?? null,
            'tiktok' => $data['advertiser_id'] ?? $data['bc_id'] ?? null,
            default => null,
        };
    }

    /**
     * Infer ownership type from asset data
     */
    private function inferOwnershipType(array $data): string
    {
        // Check for ownership indicators
        if (isset($data['permitted_tasks']) && in_array('MANAGE', $data['permitted_tasks'])) {
            return 'owned';
        }

        if (isset($data['relationship_type'])) {
            return match($data['relationship_type']) {
                'OWNER' => 'owned',
                'CLIENT' => 'client',
                'AGENCY' => 'managed',
                default => 'unknown',
            };
        }

        if (isset($data['is_owned'])) {
            return $data['is_owned'] ? 'owned' : 'client';
        }

        if (isset($data['account_type'])) {
            return match(strtolower($data['account_type'])) {
                'business', 'owned' => 'owned',
                'client' => 'client',
                'personal' => 'personal',
                default => 'unknown',
            };
        }

        return 'unknown';
    }

    /**
     * Get asset by platform identifiers
     */
    public function getByPlatformId(
        string $platform,
        string $platformAssetId,
        string $assetType
    ): ?PlatformAsset {
        return PlatformAsset::where('platform', $platform)
            ->where('platform_asset_id', $platformAssetId)
            ->where('asset_type', $assetType)
            ->first();
    }

    /**
     * Get all children of an asset
     */
    public function getAssetChildren(string $assetId, ?string $relationshipType = null): Collection
    {
        $query = AssetRelationship::where('parent_asset_id', $assetId);

        if ($relationshipType) {
            $query->where('relationship_type', $relationshipType);
        }

        return $query->with('childAsset')->get()->pluck('childAsset')->filter();
    }

    /**
     * Get all parents of an asset
     */
    public function getAssetParents(string $assetId, ?string $relationshipType = null): Collection
    {
        $query = AssetRelationship::where('child_asset_id', $assetId);

        if ($relationshipType) {
            $query->where('relationship_type', $relationshipType);
        }

        return $query->with('parentAsset')->get()->pluck('parentAsset')->filter();
    }

    /**
     * Bulk record org access for multiple assets
     */
    public function bulkRecordOrgAccess(
        string $orgId,
        string $connectionId,
        array $assetIds,
        array $accessData = []
    ): int {
        $count = 0;

        foreach ($assetIds as $assetId) {
            $this->recordOrgAccess($orgId, $assetId, $connectionId, $accessData);
            $count++;
        }

        return $count;
    }

    /**
     * Get connection assets with their access data
     */
    public function getConnectionAssets(
        string $connectionId,
        ?string $platform = null,
        ?string $assetType = null
    ): Collection {
        $query = OrgAssetAccess::where('connection_id', $connectionId)
            ->where('is_active', true)
            ->with('asset');

        if ($platform || $assetType) {
            $query->whereHas('asset', function ($q) use ($platform, $assetType) {
                if ($platform) {
                    $q->where('platform', $platform);
                }
                if ($assetType) {
                    $q->where('asset_type', $assetType);
                }
            });
        }

        return $query->get();
    }

    /**
     * Get assets needing sync for a connection
     */
    public function getAssetsNeedingSync(string $connectionId, int $hoursFresh = 6): Collection
    {
        return PlatformAsset::whereHas('orgAccess', function ($query) use ($connectionId) {
            $query->where('connection_id', $connectionId)
                ->where('is_active', true);
        })
        ->where(function ($query) use ($hoursFresh) {
            $query->where('last_synced_at', '<', now()->subHours($hoursFresh))
                ->orWhereNull('last_synced_at');
        })
        ->active()
        ->get();
    }
}
