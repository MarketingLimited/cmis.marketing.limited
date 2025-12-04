<?php

namespace App\Services\Profile;

use App\Models\Core\Integration;
use App\Models\Platform\BoostRule;
use App\Models\Platform\PlatformConnection;
use App\Models\Social\IntegrationQueueSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling profile soft delete and restore operations
 * when platform connection assets are selected/deselected.
 */
class ProfileSoftDeleteService
{
    /**
     * Platform mapping: Integration platform -> Connection platform
     */
    protected array $platformToConnectionMap = [
        'facebook' => 'meta',
        'instagram' => 'meta',
        'threads' => 'meta',
        'youtube' => 'google',
        'google_business' => 'google',
        'linkedin' => 'linkedin',
        'twitter' => 'twitter',
        'tiktok' => 'tiktok',
        'snapchat' => 'snapchat',
        'pinterest' => 'pinterest',
        'reddit' => 'reddit',
    ];

    /**
     * Platform mapping: Integration platform -> Asset type key in selected_assets
     */
    protected array $platformToAssetTypeMap = [
        'facebook' => 'page',
        'instagram' => 'instagram_account',
        'threads' => 'threads_account',
        'youtube' => 'youtube_channel',
        'google_business' => 'business_profile',
        'linkedin' => 'account',
        'twitter' => 'account',
        'tiktok' => 'account',
        'snapchat' => 'account',
        'pinterest' => 'account',
        'reddit' => 'account',
    ];

    /**
     * Check if an asset is used in any OTHER active connection within the same org.
     *
     * @param string $orgId Organization ID
     * @param string $platform Integration platform name (e.g., 'facebook', 'instagram')
     * @param string $accountId Asset account ID
     * @param string $excludeConnectionId Connection ID to exclude from check
     * @return bool True if asset is used in another connection
     */
    public function isAssetUsedInOtherConnections(
        string $orgId,
        string $platform,
        string $accountId,
        string $excludeConnectionId
    ): bool {
        $connectionPlatform = $this->platformToConnectionMap[$platform] ?? $platform;
        $assetTypeKey = $this->platformToAssetTypeMap[$platform] ?? 'account';

        // Use PostgreSQL JSONB contains operator to check if asset is in selected_assets
        return PlatformConnection::where('org_id', $orgId)
            ->where('platform', $connectionPlatform)
            ->where('connection_id', '!=', $excludeConnectionId)
            ->where('status', 'active')
            ->whereRaw(
                "account_metadata->'selected_assets'->? @> ?::jsonb",
                [$assetTypeKey, json_encode([$accountId])]
            )
            ->exists();
    }

    /**
     * Soft delete a profile and cascade to related entities.
     *
     * @param Integration $integration The profile to delete
     * @param string $reason Reason for deletion (for logging)
     * @return void
     */
    public function softDeleteWithCascade(Integration $integration, string $reason = 'asset_deselected'): void
    {
        DB::transaction(function () use ($integration, $reason) {
            // 1. Soft delete queue settings
            if ($integration->queueSettings) {
                $integration->queueSettings->delete();
            }

            // 2. Remove from boost rules' apply_to_social_profiles array
            $this->removeFromBoostRules($integration);

            // 3. Soft delete the integration (profile)
            $integration->delete();

            Log::info('Profile soft deleted with cascade', [
                'integration_id' => $integration->integration_id,
                'platform' => $integration->platform,
                'account_id' => $integration->account_id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Restore a soft-deleted profile and its related entities.
     *
     * @param Integration $integration The profile to restore
     * @return void
     */
    public function restoreWithCascade(Integration $integration): void
    {
        DB::transaction(function () use ($integration) {
            // 1. Restore the profile
            $integration->restore();

            // 2. Restore queue settings if they exist
            IntegrationQueueSettings::withTrashed()
                ->where('integration_id', $integration->integration_id)
                ->restore();

            // Note: Boost rules were not deleted, just removed from array
            // User will need to re-add profile to boost rules if desired

            Log::info('Profile restored with cascade', [
                'integration_id' => $integration->integration_id,
                'platform' => $integration->platform,
                'account_id' => $integration->account_id,
            ]);
        });
    }

    /**
     * Soft delete all profiles for a connection (checking multi-connection usage).
     *
     * @param string $orgId Organization ID
     * @param string $connectionId Connection being deleted
     * @return int Number of profiles soft deleted
     */
    public function softDeleteProfilesForConnection(string $orgId, string $connectionId): int
    {
        $deletedCount = 0;

        // Get all integrations linked to this connection
        $integrations = Integration::where('org_id', $orgId)
            ->where('metadata->connection_id', $connectionId)
            ->get();

        foreach ($integrations as $integration) {
            // Check if this asset is used in another connection
            if (!$this->isAssetUsedInOtherConnections(
                $orgId,
                $integration->platform,
                $integration->account_id,
                $connectionId
            )) {
                $this->softDeleteWithCascade($integration, 'connection_deleted');
                $deletedCount++;
            } else {
                // Just mark inactive if used elsewhere
                $integration->update([
                    'is_active' => false,
                    'status' => 'inactive',
                ]);
                Log::info('Profile marked inactive (asset in other connection)', [
                    'integration_id' => $integration->integration_id,
                    'platform' => $integration->platform,
                ]);
            }
        }

        return $deletedCount;
    }

    /**
     * Get profiles that should be soft deleted when assets are deselected.
     *
     * @param string $orgId Organization ID
     * @param string $connectionId Connection ID
     * @param array $platformTypes Array of platform types (e.g., ['facebook', 'instagram', 'threads'])
     * @param array $expectedIntegrationIds IDs of integrations that should remain
     * @return Collection Integration profiles that should be soft deleted
     */
    public function getProfilesToSoftDelete(
        string $orgId,
        string $connectionId,
        array $platformTypes,
        array $expectedIntegrationIds
    ): Collection {
        return Integration::where('org_id', $orgId)
            ->whereIn('platform', $platformTypes)
            ->where('metadata->connection_id', $connectionId)
            ->whereNotIn('integration_id', $expectedIntegrationIds)
            ->get();
    }

    /**
     * Process deselected profiles - soft delete or mark inactive based on multi-connection check.
     *
     * @param string $orgId Organization ID
     * @param string $connectionId Connection ID
     * @param Collection $deselectedProfiles Profiles that were deselected
     * @param string $reason Reason for soft delete
     * @return array Stats about the operation
     */
    public function processDeselectedProfiles(
        string $orgId,
        string $connectionId,
        Collection $deselectedProfiles,
        string $reason = 'asset_deselected'
    ): array {
        $stats = [
            'soft_deleted' => 0,
            'marked_inactive' => 0,
        ];

        foreach ($deselectedProfiles as $profile) {
            if (!$this->isAssetUsedInOtherConnections(
                $orgId,
                $profile->platform,
                $profile->account_id,
                $connectionId
            )) {
                $this->softDeleteWithCascade($profile, $reason);
                $stats['soft_deleted']++;
            } else {
                // Just mark inactive if used in another connection
                $profile->update([
                    'is_active' => false,
                    'status' => 'inactive',
                ]);
                $stats['marked_inactive']++;
            }
        }

        return $stats;
    }

    /**
     * Check if a soft-deleted profile exists for the given asset and restore it.
     *
     * @param string $orgId Organization ID
     * @param string $platform Platform name
     * @param string $accountId Asset account ID
     * @return Integration|null The restored integration or null if not found
     */
    public function checkAndRestoreSoftDeleted(
        string $orgId,
        string $platform,
        string $accountId
    ): ?Integration {
        $integration = Integration::withTrashed()
            ->where('org_id', $orgId)
            ->where('platform', $platform)
            ->where('account_id', $accountId)
            ->first();

        if ($integration && $integration->trashed()) {
            $this->restoreWithCascade($integration);
            return $integration;
        }

        return null;
    }

    /**
     * Remove integration from boost rules' apply_to_social_profiles array.
     *
     * @param Integration $integration
     * @return void
     */
    protected function removeFromBoostRules(Integration $integration): void
    {
        BoostRule::where('org_id', $integration->org_id)
            ->whereJsonContains('apply_to_social_profiles', $integration->integration_id)
            ->each(function ($rule) use ($integration) {
                $profiles = collect($rule->apply_to_social_profiles ?? [])
                    ->reject(fn($id) => $id === $integration->integration_id)
                    ->values()
                    ->toArray();

                $rule->update(['apply_to_social_profiles' => $profiles]);

                Log::debug('Removed profile from boost rule', [
                    'boost_rule_id' => $rule->boost_rule_id,
                    'integration_id' => $integration->integration_id,
                ]);
            });
    }

    /**
     * Check if an asset is selected in ANY active connection within an org.
     * Unlike isAssetUsedInOtherConnections(), this doesn't exclude any connection.
     *
     * @param string $orgId Organization ID
     * @param string $platform Integration platform name (e.g., 'facebook', 'instagram')
     * @param string $accountId Asset account ID
     * @return bool True if asset is selected in any active connection
     */
    public function isAssetSelectedInAnyConnection(
        string $orgId,
        string $platform,
        string $accountId
    ): bool {
        $connectionPlatform = $this->platformToConnectionMap[$platform] ?? $platform;
        $assetTypeKey = $this->platformToAssetTypeMap[$platform] ?? 'account';

        return PlatformConnection::where('org_id', $orgId)
            ->where('platform', $connectionPlatform)
            ->where('status', 'active')
            ->whereRaw(
                "account_metadata->'selected_assets'->? @> ?::jsonb",
                [$assetTypeKey, json_encode([$accountId])]
            )
            ->exists();
    }

    /**
     * Get platform to connection mapping.
     *
     * @return array
     */
    public function getPlatformToConnectionMap(): array
    {
        return $this->platformToConnectionMap;
    }

    /**
     * Get platform to asset type mapping.
     *
     * @return array
     */
    public function getPlatformToAssetTypeMap(): array
    {
        return $this->platformToAssetTypeMap;
    }

    /**
     * Get the connection platform for an integration platform.
     *
     * @param string $integrationPlatform
     * @return string
     */
    public function getConnectionPlatform(string $integrationPlatform): string
    {
        return $this->platformToConnectionMap[$integrationPlatform] ?? $integrationPlatform;
    }

    /**
     * Get the asset type key for a platform.
     *
     * @param string $platform
     * @return string
     */
    public function getAssetTypeKey(string $platform): string
    {
        return $this->platformToAssetTypeMap[$platform] ?? 'account';
    }
}
