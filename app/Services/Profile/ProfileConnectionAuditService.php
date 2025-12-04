<?php

namespace App\Services\Profile;

use App\Jobs\SyncMetaIntegrationRecords;
use App\Models\Core\Integration;
use App\Models\Core\Org;
use App\Models\Platform\BoostRule;
use App\Models\Platform\PlatformConnection;
use App\Models\Social\IntegrationQueueSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for auditing and fixing profile-connection-asset synchronization.
 *
 * This service provides methods to:
 * - Find orphaned profiles (connection deleted/inactive)
 * - Find missing profiles (selected assets without profiles)
 * - Find profiles to soft-delete (deselected assets)
 * - Find profiles to restore (re-selected assets)
 * - Find stale queue settings and boost rule references
 * - Fix all of the above issues
 */
class ProfileConnectionAuditService
{
    /**
     * Asset type to integration platform mapping.
     */
    protected array $assetTypeToIntegrationPlatform = [
        'page' => 'facebook',
        'instagram_account' => 'instagram',
        'threads_account' => 'threads',
        'youtube_channel' => 'youtube',
        'business_profile' => 'google_business',
        'linkedin_page' => 'linkedin',
        'twitter_account' => 'twitter',
        'tiktok_account' => 'tiktok',
        'snapchat_account' => 'snapchat',
    ];

    /**
     * Supported integration platforms for auditing.
     */
    protected array $supportedPlatforms = [
        'facebook',
        'instagram',
        'threads',
        'youtube',
        'google_business',
        'linkedin',
        'twitter',
        'tiktok',
        'snapchat',
    ];

    public function __construct(
        protected ProfileSoftDeleteService $profileSoftDeleteService
    ) {}

    /**
     * Find orphaned profiles - profiles where the connection doesn't exist or is inactive.
     *
     * @param string $orgId Organization ID
     * @return Collection Orphaned Integration profiles
     */
    public function findOrphanedProfiles(string $orgId): Collection
    {
        return Integration::where('org_id', $orgId)
            ->whereNotNull('metadata->connection_id')
            ->whereNull('deleted_at')
            ->whereIn('platform', $this->supportedPlatforms)
            ->get()
            ->filter(function ($profile) {
                $connectionId = $profile->metadata['connection_id'] ?? null;
                if (!$connectionId) {
                    return false;
                }

                // Check if connection exists and is active
                $connectionExists = PlatformConnection::where('connection_id', $connectionId)
                    ->where('status', 'active')
                    ->exists();

                return !$connectionExists;
            });
    }

    /**
     * Find missing profiles - selected assets that don't have corresponding active profiles.
     *
     * @param string $orgId Organization ID
     * @return array Array of missing profile info with connection_id, platform, asset_type, asset_id
     */
    public function findMissingProfiles(string $orgId): array
    {
        $missing = [];

        $connections = PlatformConnection::where('org_id', $orgId)
            ->where('status', 'active')
            ->whereNotNull('account_metadata')
            ->get();

        foreach ($connections as $connection) {
            $selectedAssets = $connection->account_metadata['selected_assets'] ?? [];

            foreach ($this->assetTypeToIntegrationPlatform as $assetType => $integrationPlatform) {
                $assetIds = $selectedAssets[$assetType] ?? [];

                foreach ($assetIds as $assetId) {
                    $exists = Integration::where('org_id', $orgId)
                        ->where('platform', $integrationPlatform)
                        ->where('account_id', $assetId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$exists) {
                        $missing[] = [
                            'connection_id' => $connection->connection_id,
                            'connection_platform' => $connection->platform,
                            'platform' => $integrationPlatform,
                            'asset_type' => $assetType,
                            'asset_id' => $assetId,
                        ];
                    }
                }
            }
        }

        return $missing;
    }

    /**
     * Find profiles to soft delete - active profiles whose assets are not selected in ANY connection.
     *
     * @param string $orgId Organization ID
     * @return Collection Profiles that should be soft deleted
     */
    public function findProfilesToSoftDelete(string $orgId): Collection
    {
        return Integration::where('org_id', $orgId)
            ->whereIn('platform', $this->supportedPlatforms)
            ->whereNull('deleted_at')
            ->get()
            ->filter(function ($profile) use ($orgId) {
                // Check if asset is selected in ANY active connection
                return !$this->profileSoftDeleteService->isAssetSelectedInAnyConnection(
                    $orgId,
                    $profile->platform,
                    $profile->account_id
                );
            });
    }

    /**
     * Find profiles to restore - soft-deleted profiles whose assets ARE selected in an active connection.
     *
     * @param string $orgId Organization ID
     * @return Collection Profiles that should be restored
     */
    public function findProfilesToRestore(string $orgId): Collection
    {
        return Integration::withTrashed()
            ->where('org_id', $orgId)
            ->whereIn('platform', $this->supportedPlatforms)
            ->whereNotNull('deleted_at')
            ->get()
            ->filter(function ($profile) use ($orgId) {
                // Check if asset is selected in ANY active connection
                return $this->profileSoftDeleteService->isAssetSelectedInAnyConnection(
                    $orgId,
                    $profile->platform,
                    $profile->account_id
                );
            });
    }

    /**
     * Find stale queue settings - queue settings for soft-deleted profiles.
     *
     * @param string $orgId Organization ID
     * @return Collection Stale queue settings
     */
    public function findStaleQueueSettings(string $orgId): Collection
    {
        return IntegrationQueueSettings::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->get()
            ->filter(function ($queueSetting) {
                // Check if the integration is soft-deleted
                $integration = Integration::withTrashed()
                    ->where('integration_id', $queueSetting->integration_id)
                    ->first();

                return $integration && $integration->trashed();
            });
    }

    /**
     * Find invalid boost rule references - boost rules containing soft-deleted profile IDs.
     *
     * @param string $orgId Organization ID
     * @return Collection Boost rules with invalid references
     */
    public function findInvalidBoostRuleRefs(string $orgId): Collection
    {
        return BoostRule::where('org_id', $orgId)
            ->whereNotNull('apply_to_social_profiles')
            ->get()
            ->filter(function ($rule) {
                $profileIds = $rule->apply_to_social_profiles ?? [];

                foreach ($profileIds as $profileId) {
                    $exists = Integration::where('integration_id', $profileId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$exists) {
                        return true;
                    }
                }

                return false;
            });
    }

    /**
     * Soft delete orphaned profiles.
     *
     * @param string $orgId Organization ID
     * @return int Number of profiles soft deleted
     */
    public function softDeleteOrphanedProfiles(string $orgId): int
    {
        $orphaned = $this->findOrphanedProfiles($orgId);
        $count = 0;

        foreach ($orphaned as $profile) {
            $this->profileSoftDeleteService->softDeleteWithCascade($profile, 'connection_orphaned');
            $count++;

            Log::channel('profile-audit')->info('Soft deleted orphaned profile', [
                'integration_id' => $profile->integration_id,
                'platform' => $profile->platform,
                'account_id' => $profile->account_id,
                'org_id' => $orgId,
            ]);
        }

        return $count;
    }

    /**
     * Create missing profiles by dispatching sync jobs for each connection.
     *
     * @param string $orgId Organization ID
     * @return int Number of sync jobs dispatched
     */
    public function createMissingProfiles(string $orgId): int
    {
        $missing = $this->findMissingProfiles($orgId);
        $connectionJobsDispatched = [];

        foreach ($missing as $missingProfile) {
            $connectionId = $missingProfile['connection_id'];
            $connectionPlatform = $missingProfile['connection_platform'];

            // Only dispatch one job per connection (it will sync all assets)
            if (isset($connectionJobsDispatched[$connectionId])) {
                continue;
            }

            $connection = PlatformConnection::find($connectionId);
            if (!$connection) {
                continue;
            }

            $selectedAssets = $connection->account_metadata['selected_assets'] ?? [];

            // Dispatch appropriate sync job based on connection platform
            if ($connectionPlatform === 'meta') {
                SyncMetaIntegrationRecords::dispatch($orgId, $connectionId, $selectedAssets);
                $connectionJobsDispatched[$connectionId] = true;

                Log::channel('profile-audit')->info('Dispatched Meta sync job for missing profiles', [
                    'connection_id' => $connectionId,
                    'org_id' => $orgId,
                ]);
            }
            // Add other platform sync jobs as needed (Google, TikTok, etc.)
        }

        return count($connectionJobsDispatched);
    }

    /**
     * Soft delete deselected profiles.
     *
     * @param string $orgId Organization ID
     * @return int Number of profiles soft deleted
     */
    public function softDeleteDeselectedProfiles(string $orgId): int
    {
        $toDelete = $this->findProfilesToSoftDelete($orgId);
        $count = 0;

        foreach ($toDelete as $profile) {
            $this->profileSoftDeleteService->softDeleteWithCascade($profile, 'asset_deselected_audit');
            $count++;

            Log::channel('profile-audit')->info('Soft deleted deselected profile', [
                'integration_id' => $profile->integration_id,
                'platform' => $profile->platform,
                'account_id' => $profile->account_id,
                'org_id' => $orgId,
            ]);
        }

        return $count;
    }

    /**
     * Restore selected profiles that are currently soft-deleted.
     *
     * @param string $orgId Organization ID
     * @return int Number of profiles restored
     */
    public function restoreSelectedProfiles(string $orgId): int
    {
        $toRestore = $this->findProfilesToRestore($orgId);
        $count = 0;

        foreach ($toRestore as $profile) {
            $this->profileSoftDeleteService->restoreWithCascade($profile);
            $count++;

            Log::channel('profile-audit')->info('Restored selected profile', [
                'integration_id' => $profile->integration_id,
                'platform' => $profile->platform,
                'account_id' => $profile->account_id,
                'org_id' => $orgId,
            ]);
        }

        return $count;
    }

    /**
     * Clean up stale queue settings and invalid boost rule references.
     *
     * @param string $orgId Organization ID
     * @return array Stats about cleanup: ['queue_settings' => int, 'boost_rules' => int]
     */
    public function cleanupStaleData(string $orgId): array
    {
        $stats = [
            'queue_settings' => 0,
            'boost_rules' => 0,
        ];

        // Soft delete stale queue settings
        $staleSettings = $this->findStaleQueueSettings($orgId);
        foreach ($staleSettings as $setting) {
            $setting->delete();
            $stats['queue_settings']++;

            Log::channel('profile-audit')->info('Soft deleted stale queue setting', [
                'queue_setting_id' => $setting->id,
                'integration_id' => $setting->integration_id,
                'org_id' => $orgId,
            ]);
        }

        // Clean invalid boost rule references
        $invalidRules = $this->findInvalidBoostRuleRefs($orgId);
        foreach ($invalidRules as $rule) {
            $profileIds = $rule->apply_to_social_profiles ?? [];
            $validProfileIds = collect($profileIds)->filter(function ($profileId) {
                return Integration::where('integration_id', $profileId)
                    ->whereNull('deleted_at')
                    ->exists();
            })->values()->toArray();

            $rule->update(['apply_to_social_profiles' => $validProfileIds]);
            $stats['boost_rules']++;

            Log::channel('profile-audit')->info('Cleaned invalid boost rule references', [
                'boost_rule_id' => $rule->boost_rule_id,
                'removed_count' => count($profileIds) - count($validProfileIds),
                'org_id' => $orgId,
            ]);
        }

        return $stats;
    }

    /**
     * Run a full audit for an organization.
     *
     * @param string $orgId Organization ID
     * @param bool $fix Whether to actually fix issues (false = dry run)
     * @return array Full audit results
     */
    public function runFullAudit(string $orgId, bool $fix = false): array
    {
        $startTime = microtime(true);

        $results = [
            'org_id' => $orgId,
            'fix_mode' => $fix,
            'orphaned_profiles' => [
                'found' => 0,
                'fixed' => 0,
            ],
            'missing_profiles' => [
                'found' => 0,
                'jobs_dispatched' => 0,
            ],
            'deselected_profiles' => [
                'found' => 0,
                'fixed' => 0,
            ],
            'profiles_to_restore' => [
                'found' => 0,
                'fixed' => 0,
            ],
            'stale_data' => [
                'queue_settings_found' => 0,
                'queue_settings_fixed' => 0,
                'boost_rules_found' => 0,
                'boost_rules_fixed' => 0,
            ],
            'duration_seconds' => 0,
        ];

        // 1. Find orphaned profiles
        $orphaned = $this->findOrphanedProfiles($orgId);
        $results['orphaned_profiles']['found'] = $orphaned->count();
        if ($fix && $orphaned->count() > 0) {
            $results['orphaned_profiles']['fixed'] = $this->softDeleteOrphanedProfiles($orgId);
        }

        // 2. Find missing profiles
        $missing = $this->findMissingProfiles($orgId);
        $results['missing_profiles']['found'] = count($missing);
        if ($fix && count($missing) > 0) {
            $results['missing_profiles']['jobs_dispatched'] = $this->createMissingProfiles($orgId);
        }

        // 3. Find profiles to soft delete
        $toDelete = $this->findProfilesToSoftDelete($orgId);
        $results['deselected_profiles']['found'] = $toDelete->count();
        if ($fix && $toDelete->count() > 0) {
            $results['deselected_profiles']['fixed'] = $this->softDeleteDeselectedProfiles($orgId);
        }

        // 4. Find profiles to restore
        $toRestore = $this->findProfilesToRestore($orgId);
        $results['profiles_to_restore']['found'] = $toRestore->count();
        if ($fix && $toRestore->count() > 0) {
            $results['profiles_to_restore']['fixed'] = $this->restoreSelectedProfiles($orgId);
        }

        // 5. Find stale data
        $staleSettings = $this->findStaleQueueSettings($orgId);
        $invalidRules = $this->findInvalidBoostRuleRefs($orgId);
        $results['stale_data']['queue_settings_found'] = $staleSettings->count();
        $results['stale_data']['boost_rules_found'] = $invalidRules->count();
        if ($fix && ($staleSettings->count() > 0 || $invalidRules->count() > 0)) {
            $cleanupStats = $this->cleanupStaleData($orgId);
            $results['stale_data']['queue_settings_fixed'] = $cleanupStats['queue_settings'];
            $results['stale_data']['boost_rules_fixed'] = $cleanupStats['boost_rules'];
        }

        $results['duration_seconds'] = round(microtime(true) - $startTime, 3);

        // Log summary
        Log::channel('profile-audit')->info('Profile-connection audit completed', $results);

        return $results;
    }

    /**
     * Run audit for all organizations.
     *
     * @param bool $fix Whether to fix issues
     * @return array Summary of all org audits
     */
    public function runAuditForAllOrgs(bool $fix = false): array
    {
        $startTime = microtime(true);
        $summary = [
            'total_orgs' => 0,
            'total_orphaned_fixed' => 0,
            'total_missing_jobs' => 0,
            'total_deselected_fixed' => 0,
            'total_restored' => 0,
            'total_stale_cleaned' => 0,
            'duration_seconds' => 0,
        ];

        Org::chunk(100, function ($orgs) use (&$summary, $fix) {
            foreach ($orgs as $org) {
                $results = $this->runFullAudit($org->org_id, $fix);
                $summary['total_orgs']++;
                $summary['total_orphaned_fixed'] += $results['orphaned_profiles']['fixed'];
                $summary['total_missing_jobs'] += $results['missing_profiles']['jobs_dispatched'];
                $summary['total_deselected_fixed'] += $results['deselected_profiles']['fixed'];
                $summary['total_restored'] += $results['profiles_to_restore']['fixed'];
                $summary['total_stale_cleaned'] += $results['stale_data']['queue_settings_fixed'] + $results['stale_data']['boost_rules_fixed'];
            }
        });

        $summary['duration_seconds'] = round(microtime(true) - $startTime, 3);

        Log::channel('profile-audit')->info('All-org profile audit completed', $summary);

        return $summary;
    }
}
