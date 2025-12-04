# Fix: Google Asset Profiles "Unknown" Name Issue

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Commits:** 11808a11, 2452c54f
**Related Files:**
- `app/Http/Controllers/Api/GoogleAssetsApiController.php`
- `app/Http/Controllers/Settings/PlatformConnectionsController.php`

## Summary

Fixed a bug where YouTube channels and Google Business Profiles were created with "Unknown" as the `account_name` instead of actual names when selected on the Google assets page.

**Affected Asset Types:**
- YouTube Channels
- Google Business Profiles

## Problem

When users selected assets on the Google assets page, the profiles on the Profile Management page showed "Unknown" instead of the actual names (e.g., "Marketing Dot Limited", "Kazaaz Promotion Services").

## Root Cause

The `syncGoogleIntegrationRecords()` method re-fetched assets from API during profile creation. The API sometimes returned different results than what the user saw on the selection page, causing lookups to fail and defaulting to "Unknown".

```php
// BEFORE (problematic):
$assets = $this->getGoogleYouTubeChannels($connection);  // RE-FETCHES FROM API
$assetsById = collect($assets)->keyBy('id')->toArray();
$assetData = $assetsById[$assetId] ?? null;  // LOOKUP FAILS
$accountName = $assetData['title'] ?? 'Unknown';  // DEFAULTS TO 'Unknown'
```

## Solution

Store asset data in connection metadata when fetched by the API controller, then use stored metadata during sync instead of re-fetching.

### Implementation

**1. Store assets when fetched (GoogleAssetsApiController.php):**

For YouTube channels (line ~89-101):
```php
if (!empty($channels)) {
    $connection = $data['connection'];
    $metadata = $connection->account_metadata ?? [];
    $metadata['youtube_channels'] = $channels;
    $connection->update(['account_metadata' => $metadata]);
}
```

For Business Profiles (line ~270-283):
```php
if (!empty($profiles)) {
    $connection = $data['connection'];
    $metadata = $connection->account_metadata ?? [];
    $metadata['business_profiles'] = $profiles;
    $connection->update(['account_metadata' => $metadata]);
}
```

**2. Use stored metadata during sync (PlatformConnectionsController.php ~5369-5405):**

```php
if ($method === 'getGoogleYouTubeChannels') {
    $storedChannels = $connection->account_metadata['youtube_channels'] ?? [];
    if (!empty($storedChannels)) {
        $assets = $storedChannels;
    } else {
        $assets = $this->getGoogleYouTubeChannels($connection);
    }
} elseif ($method === 'getGoogleBusinessProfiles') {
    $storedProfiles = $connection->account_metadata['business_profiles'] ?? [];
    if (!empty($storedProfiles)) {
        $assets = $storedProfiles;
    } else {
        $assets = $this->getGoogleBusinessProfiles($connection);
    }
}
```

## Testing

1. Go to Google assets page for a connection
2. Select YouTube channels or Business Profiles
3. Save the selection
4. Go to Profile Management page
5. Verify profiles display actual names (not "Unknown")

## Fixing Existing Profiles

### Fix Unknown YouTube Profiles:
```php
$connection = \App\Models\Platform\PlatformConnection::find($connectionId);
$storedChannels = $connection->account_metadata['youtube_channels'] ?? [];
$channelsById = collect($storedChannels)->keyBy('id')->toArray();

$profiles = \App\Models\Integration::where('org_id', $orgId)
    ->where('platform', 'youtube')
    ->where('account_name', 'Unknown')
    ->get();

foreach ($profiles as $p) {
    $channelData = $channelsById[$p->account_id] ?? null;
    if ($channelData) {
        $p->update(['account_name' => $channelData['title']]);
    }
}
```

### Fix Unknown Business Profiles:
```php
$storedProfiles = $connection->account_metadata['business_profiles'] ?? [];
$profilesById = collect($storedProfiles)->keyBy('id')->toArray();

$profiles = \App\Models\Integration::where('org_id', $orgId)
    ->where('platform', 'google_business')
    ->where('account_name', 'Unknown')
    ->get();

foreach ($profiles as $p) {
    $profileData = $profilesById[$p->account_id] ?? null;
    if ($profileData) {
        $p->update(['account_name' => $profileData['title']]);
    }
}
```

## Related Documentation

- [Profile Soft Delete Sync](../features/profile-soft-delete-sync.md)
- [Profile-Connection Sync Audit](../features/profile-connection-sync-audit.md)
