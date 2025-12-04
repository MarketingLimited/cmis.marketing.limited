# Profile-Connection Sync Audit Feature

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Status:** Implemented

## Summary

This feature implements a scheduled background job that periodically audits and fixes inconsistencies between platform connections, selected assets, and social media profiles. The audit runs every 6 hours and can also be triggered manually via artisan command.

## Problem Statement

Over time, the relationship between platform connections and profiles can become inconsistent due to:
- Connections being deleted without properly cleaning up profiles
- Assets being deselected but profiles remaining active
- Assets being re-selected but profiles remaining soft-deleted
- Stale queue settings pointing to deleted profiles
- Boost rules referencing deleted profiles

This audit job automatically detects and fixes these inconsistencies to maintain data integrity.

## Key Behaviors

### Audit Types

| Type | Description | Fix Action |
|------|-------------|------------|
| **Orphaned Profiles** | Profiles linked to deleted/inactive connections | Soft delete profile |
| **Missing Profiles** | Selected assets without corresponding profiles | Dispatch sync job |
| **Deselected Profiles** | Active profiles for deselected assets | Soft delete profile |
| **Profiles to Restore** | Soft-deleted profiles for selected assets | Restore profile |
| **Stale Queue Settings** | Queue settings for soft-deleted profiles | Soft delete settings |
| **Invalid Boost Rule Refs** | Boost rules with deleted profile references | Remove invalid refs |

### Schedule

- **Frequency:** Every 6 hours
- **Concurrency:** `onOneServer()` - Only runs on one server in multi-server setups
- **Overlap Protection:** `withoutOverlapping(60)` - Won't start if previous run is still going
- **Queue:** `maintenance` - Uses the maintenance queue for background processing

## Files Created

| File | Purpose |
|------|---------|
| `app/Services/Profile/ProfileConnectionAuditService.php` | Core service with audit and fix methods |
| `app/Console/Commands/AuditProfileConnectionSyncCommand.php` | Artisan command for manual audits |
| `app/Jobs/AuditProfileConnectionSyncJob.php` | Queued job for scheduled execution |
| `tests/Feature/Profile/ProfileConnectionAuditServiceTest.php` | Comprehensive test suite |

## Files Modified

| File | Change |
|------|--------|
| `app/Services/Profile/ProfileSoftDeleteService.php` | Added `isAssetSelectedInAnyConnection()` helper |
| `app/Console/Kernel.php` | Added scheduled job |
| `config/logging.php` | Added `profile-audit` logging channel |

## Technical Details

### ProfileConnectionAuditService

The core service provides comprehensive audit and fix methods:

```php
class ProfileConnectionAuditService
{
    // Find methods (return collections/arrays of issues)
    public function findOrphanedProfiles(string $orgId): Collection;
    public function findMissingProfiles(string $orgId): array;
    public function findProfilesToSoftDelete(string $orgId): Collection;
    public function findProfilesToRestore(string $orgId): Collection;
    public function findStaleQueueSettings(string $orgId): Collection;
    public function findInvalidBoostRuleRefs(string $orgId): Collection;

    // Fix methods (return counts of fixed items)
    public function softDeleteOrphanedProfiles(string $orgId): int;
    public function createMissingProfiles(string $orgId): int;
    public function softDeleteDeselectedProfiles(string $orgId): int;
    public function restoreSelectedProfiles(string $orgId): int;
    public function cleanupStaleData(string $orgId): array;

    // Full audit
    public function runFullAudit(string $orgId, bool $fix = false): array;
    public function runAuditForAllOrgs(bool $fix = false): array;
}
```

### Artisan Command Usage

```bash
# Dry run - show what issues exist (default)
php artisan profiles:audit-sync

# Fix all issues for all organizations
php artisan profiles:audit-sync --fix

# Audit specific organization
php artisan profiles:audit-sync --org=5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a --fix

# Run specific audit type
php artisan profiles:audit-sync --type=orphaned --fix
php artisan profiles:audit-sync --type=missing --fix
php artisan profiles:audit-sync --type=deselected --fix
php artisan profiles:audit-sync --type=restore --fix
php artisan profiles:audit-sync --type=cleanup --fix
```

### Scheduled Job Configuration

From `app/Console/Kernel.php`:

```php
$schedule->job(new AuditProfileConnectionSyncJob(null, true))
    ->everySixHours()
    ->withoutOverlapping(60)
    ->onOneServer()
    ->onQueue('maintenance')
    ->onSuccess(function () {
        Log::info('Profile-connection sync audit completed');
    })
    ->onFailure(function () {
        Log::error('Profile-connection sync audit failed');
    });
```

### Logging

All audit operations are logged to a dedicated channel:

- **Log file:** `storage/logs/profile-sync-audit.log`
- **Channel:** `profile-audit` (daily rotation, 30 days retention)

Log format example:

```php
Log::channel('profile-audit')->info('Profile-connection audit completed', [
    'org_id' => $orgId,
    'fix_mode' => true,
    'total_issues_found' => 5,
    'total_issues_fixed' => 5,
    'duration_seconds' => 2.341,
]);
```

## Asset Type Mappings

The service uses mappings to translate between asset types and integration platforms:

```php
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
```

## Test Scenarios

The test suite (`ProfileConnectionAuditServiceTest.php`) covers:

1. **Orphaned Profile Detection**
   - Profiles with deleted connections
   - Profiles with inactive connections
   - Profiles with active connections (should not be orphaned)

2. **Missing Profile Detection**
   - Selected assets without profiles
   - Multiple asset types

3. **Deselected Profile Detection**
   - Profiles for unselected assets
   - Profiles for selected assets (should not be flagged)

4. **Profile Restoration**
   - Soft-deleted profiles for selected assets
   - Soft-deleted profiles for unselected assets (should not restore)

5. **Stale Data Cleanup**
   - Queue settings for deleted profiles
   - Boost rules with invalid profile references

6. **Full Audit**
   - Dry-run mode (find but don't fix)
   - Fix mode (find and fix)
   - Duration tracking

7. **Multi-Org Isolation**
   - Audit respects org boundaries
   - Changes in one org don't affect another

8. **Artisan Command**
   - Command runs successfully
   - Accepts --org, --fix, and --type options

## Integration with Existing Features

This audit job complements the existing profile soft delete sync feature:

- **Real-time sync:** `SyncMetaIntegrationRecords` handles immediate asset selection changes
- **Scheduled audit:** This job catches any inconsistencies that slip through

The two systems work together to ensure data integrity.

## Performance Considerations

- **Chunked Processing:** All-org audits process organizations in chunks of 100
- **Timeout:** Job has 10-minute timeout to prevent long-running issues
- **Retries:** Job retries twice with 2-minute backoff on failure
- **Non-blocking:** Runs on `maintenance` queue to avoid affecting user-facing operations

## Related Documentation

- [Profile Soft Delete Sync Feature](./profile-soft-delete-sync.md)
- [Platform Connections Controller](../../app/Http/Controllers/Settings/PlatformConnectionsController.php)
- [Meta Assets Service](../../app/Services/Platform/MetaAssetsService.php)
