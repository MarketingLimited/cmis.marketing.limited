# Fix: YouTube Profile "Unknown" Name Issue

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Commit:** 11808a11
**Related Files:**
- `app/Http/Controllers/Api/GoogleAssetsApiController.php`
- `app/Http/Controllers/Settings/PlatformConnectionsController.php`

## Summary

Fixed a bug where YouTube channel profiles were created with "Unknown" as the `account_name` instead of the actual channel names (e.g., "Marketing Dot Limited", "اوفرنا - Offerna").

## Problem

When users selected YouTube channels on the Google assets page, the profiles were displayed with "Unknown" name on the Profile Management page, despite the selection page showing correct channel names.

## Root Cause Analysis

### Data Flow Issue

1. **Asset Selection Page** (`google-assets.blade.php`)
   - Fetched YouTube channels via `GoogleAssetsApiController`
   - Displayed channel titles correctly to user
   - User selected channels (checkbox value = channel ID only)

2. **Form Submission** (`storeGoogleAssets()`)
   - Received only channel IDs (not the full channel data)
   - Saved IDs to `account_metadata['selected_assets']['youtube_channel']`
   - Called `syncGoogleIntegrationRecords()`

3. **Sync Method - THE PROBLEM** (`syncGoogleIntegrationRecords()`)
   ```php
   // BEFORE (problematic):
   $assets = $this->getGoogleYouTubeChannels($connection);  // RE-FETCHES FROM API
   $assetsById = collect($assets)->keyBy('id')->toArray();

   foreach ($selectedAssets['youtube_channel'] as $assetId) {
       $assetData = $assetsById[$assetId] ?? null;  // LOOKUP FAILS
       $accountName = $assetData['title'] ?? 'Unknown';  // DEFAULTS TO 'Unknown'
   }
   ```

4. **Why Lookup Failed:**
   - Sync re-fetched channels from YouTube API
   - API may return different channels due to:
     - OAuth scope differences
     - Brand Account vs personal channel distinction
     - API endpoint returning different subset
   - If selected ID not found in re-fetched data, defaults to "Unknown"

## Solution

### Approach: Use Stored Metadata Instead of Re-fetching

When the assets page fetches channels, store them in `account_metadata['youtube_channels']`. During sync, use this stored data instead of re-fetching from API.

### Implementation

**Change 1: Store channels when fetched by API**

`app/Http/Controllers/Api/GoogleAssetsApiController.php`:
```php
// After fetching channels successfully, store in connection metadata
if (!empty($channels)) {
    $connection = $data['connection'];
    $metadata = $connection->account_metadata ?? [];
    $metadata['youtube_channels'] = $channels;
    $connection->update(['account_metadata' => $metadata]);
}
```

**Change 2: Use stored metadata during sync**

`app/Http/Controllers/Settings/PlatformConnectionsController.php`:
```php
if ($method === 'getGoogleYouTubeChannels') {
    // First try stored metadata from when user viewed the assets page
    $storedChannels = $connection->account_metadata['youtube_channels'] ?? [];

    if (!empty($storedChannels)) {
        $assets = $storedChannels;
        Log::info('Using stored YouTube channels for profile sync', [
            'connection_id' => $connection->connection_id,
            'channel_count' => count($assets),
        ]);
    } else {
        // Fall back to API fetch if no stored data
        $assets = $this->getGoogleYouTubeChannels($connection);
    }
}
```

## Testing

To verify the fix works:

1. Go to Google assets page for a connection
2. Select YouTube channels
3. Save the selection
4. Go to Profile Management page
5. Verify profiles display actual channel names (not "Unknown")

## Fixing Existing Profiles

Existing profiles with "Unknown" name can be fixed using artisan tinker:

```php
$orgId = 'YOUR_ORG_ID';
$connectionId = 'YOUR_CONNECTION_ID';

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
        $p->update([
            'account_name' => $channelData['title'],
            'avatar_url' => $channelData['thumbnail'] ?? null,
            'username' => $channelData['custom_url'] ?? $channelData['handle'] ?? null,
        ]);
    }
}
```

## Related Documentation

- [Profile Soft Delete Sync](../features/profile-soft-delete-sync.md)
- [Profile-Connection Sync Audit](../features/profile-connection-sync-audit.md)
- [Google Assets Service](../../app/Services/Platform/GoogleAssetsService.php)
