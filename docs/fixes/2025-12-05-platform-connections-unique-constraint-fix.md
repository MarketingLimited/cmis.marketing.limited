# Fix: Platform Connections Unique Constraint Violation

**Date:** 2025-12-05
**Author:** Claude Code Agent
**Severity:** High (500 Internal Server Error)
**Related Files:**
- `app/Http/Controllers/Settings/PlatformConnectionsController.php`
- `database/migrations/2025_12_05_102050_fix_platform_connections_unique_index_for_soft_deletes.php`

## Summary

Fixed a unique constraint violation (`SQLSTATE[23505]`) that occurred when attempting to save a Meta platform connection for an organization that previously had a soft-deleted connection with the same account.

## Root Cause

The `platform_connections` table had a unique index on `(org_id, platform, account_id)` that did not account for soft deletes. When:

1. A platform connection was soft-deleted (`deleted_at` is NOT NULL)
2. A user tried to create a new connection with the same org/platform/account combination
3. Laravel's `updateOrCreate()` would ignore the soft-deleted record (due to SoftDeletes global scope)
4. PostgreSQL would reject the INSERT due to the unique constraint seeing the soft-deleted row

**Conflicting Constraint:** `uq_platform_connections` on `(org_id, platform, account_id)`

## Changes Made

### 1. Controller Fix (`PlatformConnectionsController.php`)

Modified the `storeMetaToken()` method (around line 160) to explicitly handle soft-deleted records:

```php
// Before: Simple updateOrCreate that ignores soft-deleted records
$connection = PlatformConnection::updateOrCreate([...]);

// After: Check for soft-deleted record and restore if exists
$connection = PlatformConnection::withTrashed()
    ->where('org_id', $orgId)
    ->where('platform', 'meta')
    ->where('account_id', $accountId)
    ->first();

if ($connection) {
    if ($connection->trashed()) {
        $connection->restore();
    }
    $connection->update($connectionData);
} else {
    $connection = PlatformConnection::create([...]);
}
```

### 2. Database Migration

Created a new migration to convert the unique index to a **partial index** that excludes soft-deleted records:

```sql
-- Drop old constraint/index
ALTER TABLE cmis.platform_connections DROP CONSTRAINT IF EXISTS uq_platform_connections;
DROP INDEX IF EXISTS cmis.idx_platform_connections_unique;

-- Create new partial unique index
CREATE UNIQUE INDEX idx_platform_connections_unique
ON cmis.platform_connections (org_id, platform, account_id)
WHERE deleted_at IS NULL;
```

This ensures:
- Non-deleted records maintain uniqueness on (org_id, platform, account_id)
- Soft-deleted records don't block new insertions with the same key
- The same account can be reconnected after being deleted

## Testing

### Verification Steps

1. **Database Check:** Verified the new partial index exists:
   ```sql
   SELECT indexdef FROM pg_indexes
   WHERE tablename = 'platform_connections'
   AND indexname LIKE '%unique%';
   ```
   Result: `WHERE (deleted_at IS NULL)` clause is present

2. **Browser Test:** Ran Playwright test (`scripts/browser-tests/test-meta-fix.cjs`):
   - Login successful
   - Platform Connections page loads
   - Meta Add form loads without 500 error
   - All form elements present (connection name, access token, submit button)

3. **Laravel Logs:** No errors in `storage/logs/laravel.log`

### Test Command

```bash
node scripts/browser-tests/test-meta-fix.cjs
```

## Impact

- **User Impact:** Users can now reconnect previously disconnected Meta accounts without encountering a 500 error
- **Data Impact:** Soft-deleted records are restored and updated rather than creating duplicates
- **Backward Compatibility:** 100% - existing connections are unaffected

## Related Documentation

- [PostgreSQL Partial Indexes](https://www.postgresql.org/docs/current/indexes-partial.html)
- [Laravel Soft Deletes](https://laravel.com/docs/eloquent#soft-deleting)

## Lessons Learned

When using Laravel's SoftDeletes trait with unique database constraints:
1. Always use **partial unique indexes** with `WHERE deleted_at IS NULL` in PostgreSQL
2. When restoring soft-deleted records, use `withTrashed()` to query them
3. The `updateOrCreate()` method doesn't automatically handle soft-deleted records
