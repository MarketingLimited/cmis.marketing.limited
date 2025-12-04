# Fix: Google Business Profiles Not Appearing on Profile Management Page

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Commits:** 31791317 (stats fix), plus earlier commits for visibility fix
**Related Files:**
- `app/Services/Social/ProfileManagementService.php`
- `resources/views/settings/profiles/index.blade.php`
- `resources/views/settings/profiles/show.blade.php`

## Summary

Fixed multiple issues with Google Business Profiles on the Profile Management page:
1. Profiles not appearing in the list
2. Profile stats showing incorrect count (2 instead of 5)
3. Removed inappropriate "Remove Profile" and "Refresh Connection" buttons

## Problem 1: Profiles Not Appearing

**Symptom:** Users selected Google Business Profiles on the assets page, but they didn't appear on the Profile Management page.

**Root Cause:** The `getProfiles()` method in `ProfileManagementService.php` had a `whereIn` filter that excluded `google_business` platform.

```php
// BEFORE (problematic):
->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads'])
// 'google_business' was missing!
```

**Fix:** Added `google_business` to the platform filter:
```php
// AFTER (fixed):
->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads', 'google_business'])
```

## Problem 2: Profile Stats Incorrect Count

**Symptom:** Page showed "2 Total profiles" and "2 Active" but displayed 5 profiles in the table.

**Root Cause:** The `getProfileStats()` method had the same missing `google_business` in all 4 queries:
- Total count query
- Active count query
- By-platform count query
- With-groups count query

**Fix:** Added `google_business` to all 4 `whereIn` clauses in `getProfileStats()` (lines 276-293).

## Problem 3: Inappropriate UI Actions

**Symptom:** Users could remove profiles directly from the Profile Management page.

**Business Rule:** Profiles should only be removed via the Platform Connections assets page to maintain data integrity and sync consistency.

**Fix:** Removed from `index.blade.php`:
- "Remove Profile" button from dropdown menu
- "Refresh Connection" button from dropdown menu

Removed from `show.blade.php`:
- "Remove" button from profile detail page

## Files Modified

### `app/Services/Social/ProfileManagementService.php`

| Method | Change |
|--------|--------|
| `getProfiles()` | Added `google_business` to platform filter (line 31) |
| `getProfileStats()` | Added `google_business` to all 4 queries (lines 277, 281, 286, 293) |
| `getAvailablePlatforms()` | Added `'google_business' => 'Google Business'` (line 479) |

### `resources/views/settings/profiles/index.blade.php`

- Removed "Remove Profile" dropdown button (lines 247-248)
- Removed "Refresh Connection" dropdown button (line 226)

### `resources/views/settings/profiles/show.blade.php`

- Removed "Remove" button (line 36)

## Testing

1. Go to Profile Management page
2. Verify all 5 profiles appear (including Google Business)
3. Verify stats show "5 Total profiles" and "5 Active"
4. Verify no "Remove Profile" or "Refresh Connection" buttons exist
5. Verify dropdown only shows "View Details" option

## Related Documentation

- [Google Asset Profile Unknown Name Fix](./2025-12-04-google-asset-profile-unknown-name.md)
- [Profile Soft Delete Sync](../features/profile-soft-delete-sync.md)
