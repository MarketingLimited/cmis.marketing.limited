# Profile Soft Delete Sync Feature

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Status:** Implemented

## Summary

This feature implements automatic synchronization between platform connection assets and social media profiles. When assets are deselected from platform connections (Meta, Google, etc.), the corresponding profiles are automatically soft deleted. When assets are re-selected, profiles and their related data are restored.

## Key Behaviors

### One Profile Per Asset
- Same Facebook page in 2 Meta connections = 1 profile displayed
- First connection is used as primary, others serve as fallback

### Soft Delete on Deselect
- When an asset is deselected from a platform connection, the corresponding profile is soft deleted
- **Multi-connection check**: Profile is only soft deleted if the asset is NOT in any other active connection in the same organization
- If asset is in another connection, profile is marked inactive instead

### Cascade Delete
When a profile is soft deleted:
- Queue settings are automatically soft deleted via Observer pattern
- References in boost rules' `apply_to_social_profiles` array are removed

### Restore on Re-select
- When an asset is re-selected, any soft-deleted profile for that asset is restored
- Queue settings are also automatically restored via Observer

### Connection Deletion
- When a platform connection is deleted, all profiles belonging ONLY to that connection are soft deleted
- Profiles shared with other connections are marked inactive instead

## Files Created

| File | Purpose |
|------|---------|
| `app/Services/Profile/ProfileSoftDeleteService.php` | Core service with multi-connection check and cascade logic |
| `app/Observers/IntegrationObserver.php` | Handles cascade soft delete/restore on Integration model events |
| `tests/Feature/Profile/ProfileSoftDeleteServiceTest.php` | Comprehensive test suite |

## Files Modified

| File | Change |
|------|--------|
| `app/Models/Social/IntegrationQueueSettings.php` | Added `SoftDeletes` trait |
| `app/Models/Core/Integration.php` | Added `metadata` to fillable and casts |
| `app/Providers/AppServiceProvider.php` | Registered IntegrationObserver |
| `app/Jobs/SyncMetaIntegrationRecords.php` | Added soft delete and restore logic |
| `app/Http/Controllers/Settings/PlatformConnectionsController.php` | Modified Google sync and destroy methods |

## Technical Details

### ProfileSoftDeleteService

The core service provides three main methods:

```php
class ProfileSoftDeleteService
{
    /**
     * Check if an asset is used in another active connection.
     * Uses PostgreSQL JSONB queries to check selected_assets.
     */
    public function isAssetUsedInOtherConnections(
        string $orgId,
        string $platform,
        string $accountId,
        string $excludeConnectionId
    ): bool;

    /**
     * Soft delete a profile with cascade to related entities.
     * Uses DB transaction for atomicity.
     */
    public function softDeleteWithCascade(
        Integration $integration,
        string $reason = 'asset_deselected'
    ): void;

    /**
     * Restore a soft-deleted profile with cascade.
     */
    public function restoreWithCascade(Integration $integration): void;

    /**
     * Soft delete all profiles for a connection being deleted.
     * Returns count of deleted profiles.
     */
    public function softDeleteProfilesForConnection(
        string $orgId,
        string $connectionId
    ): int;
}
```

### Platform Mapping

The service uses mappings to translate between integration platforms and connection platforms:

```php
// Integration platform → Connection platform
'facebook' => 'meta'
'instagram' => 'meta'
'threads' => 'meta'
'youtube' => 'google'
'google_business' => 'google'

// Integration platform → Asset type key in selected_assets JSON
'facebook' => 'page'
'instagram' => 'instagram_account'
'threads' => 'threads_account'
'youtube' => 'youtube_channel'
'google_business' => 'business_profile'
```

### IntegrationObserver

Handles cascade operations automatically:

```php
class IntegrationObserver
{
    public function deleting(Integration $integration): void
    {
        // Cascade soft delete queue settings
        // Remove from boost rules' apply_to_social_profiles
    }

    public function restoring(Integration $integration): void
    {
        // Cascade restore queue settings
    }
}
```

## Usage Examples

### Asset Selection Change (Meta)

When user deselects a Facebook page in Meta connection settings:

1. `SyncMetaIntegrationRecords` job is dispatched
2. Job identifies deselected assets
3. For each deselected asset:
   - Checks if asset is in another connection
   - If NOT in another connection: soft deletes profile (observer cascades)
   - If IS in another connection: marks profile inactive
4. Logs the operation

### Asset Re-selection

When user re-selects a previously deselected asset:

1. Job checks for soft-deleted integration matching org + platform + account_id
2. If found: restores the integration (observer restores queue settings)
3. Updates integration with latest access token and metadata

### Connection Deletion

When user deletes a platform connection:

1. `destroy()` method calls `ProfileSoftDeleteService::softDeleteProfilesForConnection()`
2. Service finds all integrations with `metadata->connection_id` matching
3. For each integration:
   - Checks if asset is in another connection
   - Soft deletes or marks inactive as appropriate
4. Logs count of deleted profiles
5. Connection is deleted

## Test Scenarios

### Soft Delete Scenarios
1. Single connection, deselect asset → Profile + queue settings soft deleted
2. Asset in 2 connections, deselect from 1 → Profile NOT deleted (just marked inactive)
3. Asset in 2 connections, deselect from both → Profile soft deleted
4. Delete entire connection → All unique profiles soft deleted
5. Cascade delete → Queue settings soft deleted, boost rule references removed

### Restore Scenarios
6. Re-select asset after soft delete → Profile + queue settings restored
7. Reconnect platform after connection deleted → Profiles restored if same assets selected

### Edge Cases
8. Same asset, different orgs → Independent profiles (no cross-org interference)
9. Profile without queue settings → Deletion still works
10. Profile not in any boost rules → Deletion still works

## Database Schema Requirements

The feature relies on:

1. **Integration model with SoftDeletes** (`cmis.integrations`)
   - Must have `deleted_at` column
   - Must have `metadata` JSONB column with `connection_id`

2. **IntegrationQueueSettings with SoftDeletes** (`cmis.integration_queue_settings`)
   - Must have `deleted_at` column

3. **PlatformConnection** (`cmis.platform_connections`)
   - Must have `account_metadata` JSONB column with `selected_assets` structure:
     ```json
     {
       "selected_assets": {
         "page": ["123456789"],
         "instagram_account": ["987654321"],
         "threads_account": ["111222333"]
       }
     }
     ```

## Logging

All operations are logged with context:

```php
Log::info('SyncMetaIntegrationRecords completed', [
    'org_id' => $this->orgId,
    'connection_id' => $this->connectionId,
    'integrations_synced' => count($expectedIntegrationIds),
    'profiles_soft_deleted' => $softDeletedCount,
    'profiles_marked_inactive' => $markedInactiveCount,
]);
```

## Related Documentation

- Plan file: `.claude/plans/jiggly-leaping-alpaca.md`
- Platform connections: `app/Http/Controllers/Settings/PlatformConnectionsController.php`
- Meta assets service: `app/Services/Platform/MetaAssetsService.php`
