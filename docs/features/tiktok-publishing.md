# TikTok Publishing Implementation

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Related Files:**
- `app/Services/Social/SocialPostPublishService.php`
- `app/Services/Social/Publishers/TikTokPublisher.php`
- `app/Models/Platform/PlatformConnection.php`

## Summary

Fixed TikTok publishing support in `SocialPostPublishService` which was previously hardcoded to only use Meta (Facebook/Instagram) connections. The service now properly routes publishing requests to platform-specific methods.

## Problem

Users were seeing the error:
```
Failure Reason: TikTok publishing not yet implemented. Please check back soon.
```

Despite `TikTokPublisher` being fully implemented, it was never being called because `SocialPostPublishService::publishPost()` was hardcoded to:
1. Only look for 'meta' platform connections
2. Always call `publishToMeta()` regardless of the actual platform

## Solution

### 1. Platform Connection Mapping

Added `getConnectionPlatform()` helper to map post platforms to connection types:

```php
protected function getConnectionPlatform(string $platform): string
{
    return match ($platform) {
        'facebook', 'instagram' => 'meta',
        'tiktok' => 'tiktok',
        'twitter', 'x' => 'twitter',
        'linkedin' => 'linkedin',
        'youtube' => 'youtube',
        default => $platform,
    };
}
```

### 2. Platform Routing

Added `publishToPlatform()` to route to the correct publisher:

```php
protected function publishToPlatform(
    string $postId,
    string $platform,
    ?string $accountId,
    string $content,
    array $mediaUrls,
    PlatformConnection $connection
): array {
    return match ($platform) {
        'facebook', 'instagram' => $this->publishToMeta(...),
        'tiktok' => $this->publishToTikTok(...),
        default => [
            'success' => false,
            'message' => "{$platform} publishing not yet implemented.",
        ],
    };
}
```

### 3. TikTok Publishing Method

Added `publishToTikTok()` which:
- Validates video content is present (TikTok requires video)
- Resolves local file paths from storage
- Delegates to `TikTokPublisher` for the actual API calls

## TikTok API Integration

### Content Posting API v2

TikTok uses the Content Posting API v2 with FILE_UPLOAD method:

1. **Initialize Upload**: POST to `/v2/post/publish/inbox/video/init/`
2. **Upload Video**: PUT video file to the upload URL
3. **Publish**: API handles publishing after upload completes

### OAuth Scopes Required

| Scope | Purpose |
|-------|---------|
| `video.upload` | Upload videos as drafts |
| `video.publish` | Directly publish videos |

### Sandbox Testing

For unaudited apps, videos can only be published as PRIVATE/SELF_ONLY visibility. To test:

1. Add test users in TikTok Developer Console > Sandbox Settings
2. Test users must accept invitation
3. Only test users can see the published content

## File Path Resolution

The `getLocalFilePath()` helper resolves video files from multiple sources:

```php
protected function getLocalFilePath(array $mediaItem): ?string
{
    // Priority 1: Direct storage path
    if (!empty($mediaItem['storage_path'])) {
        return storage_path('app/' . $mediaItem['storage_path']);
    }

    // Priority 2: Processed video path
    if (!empty($mediaItem['processed_path'])) {
        return storage_path('app/' . $mediaItem['processed_path']);
    }

    // Priority 3: URL-based resolution
    // ...
}
```

## Error Handling

The service maps TikTok API error codes to user-friendly messages:

| Error Code | Message |
|------------|---------|
| `invalid_video` | Video format not supported |
| `video_too_long` | Video exceeds maximum duration |
| `video_too_short` | Video is too short |
| `privacy_level_option_mismatch` | Check account privacy settings |

## Testing

### Prerequisites
1. TikTok account connected in Settings > Platform Connections
2. OAuth scopes: `video.upload`, `video.publish`
3. For sandbox: Test user added in TikTok Developer Console

### Test Steps
1. Navigate to `/orgs/{org_id}/social`
2. Create a new post for TikTok with video content
3. Click "Publish Now"
4. Check the result message

### Verification
```bash
# Check Laravel logs for publish attempts
tail -f storage/logs/laravel.log | grep -i "tiktok"
```

## Related Documentation

- [TikTok OAuth Scopes](./tiktok-oauth-scopes.md)
- [TikTok Content Posting API](https://developers.tiktok.com/doc/content-posting-api-get-started)
- [TikTok Sandbox Testing](https://developers.tiktok.com/doc/sandbox-testing)
