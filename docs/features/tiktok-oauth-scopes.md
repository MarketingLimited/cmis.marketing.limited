# TikTok OAuth Scopes Implementation

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Related Files:**
- `config/social-platforms.php`
- `app/Integrations/TikTok/TikTokOAuthClient.php`
- `app/Services/Connectors/Providers/TikTokConnector.php`
- `app/Http/Controllers/Integration/IntegrationController.php`
- `resources/views/settings/platform-connections/index.blade.php`
- `app/Integrations/README.md`

## Summary

Standardized TikTok OAuth scope configuration across all implementation files and added two new scopes (`user.info.profile` and `user.info.stats`) to enable enhanced user metrics collection.

## OAuth Scopes

| Scope | Description | Product |
|-------|-------------|---------|
| `user.info.basic` | Open ID, avatar, display name | Login Kit |
| `user.info.profile` | Profile links, bio, verification status | Login Kit |
| `user.info.stats` | Follower count, likes count, video count | Login Kit |
| `video.upload` | Upload videos as drafts | Content Posting API |
| `video.publish` | Directly publish videos | Content Posting API |
| `video.list` | Read user's public videos | Content Posting API |

## Changes Made

### 1. Configuration Files Updated
All four TikTok configuration locations now have consistent scopes:

```php
// All files now use these 6 scopes:
'scopes' => [
    'user.info.basic',
    'user.info.profile',
    'user.info.stats',
    'video.upload',
    'video.publish',
    'video.list',
]
```

### 2. Enhanced getAccountMetrics()
The `TikTokConnector::getAccountMetrics()` method now requests all available fields:

```php
$response = $this->makeRequest($integration, 'POST', '/user/info/', [
    'open_id' => $openId,
    'fields' => [
        // user.info.basic fields
        'open_id', 'union_id', 'avatar_url', 'avatar_url_100',
        'avatar_large_url', 'display_name',
        // user.info.profile fields
        'bio_description', 'profile_deep_link', 'is_verified',
        // user.info.stats fields
        'follower_count', 'following_count', 'likes_count', 'video_count',
    ],
]);
```

### 3. Documentation Updated
- Platform connections blade now shows complete scope list
- Integrations README documents all scopes with descriptions

## API Response Fields

### user.info.basic
- `open_id` - Unique user identifier
- `union_id` - Cross-app user identifier
- `avatar_url` - Small avatar URL
- `avatar_url_100` - 100px avatar URL
- `avatar_large_url` - Large avatar URL
- `display_name` - User's display name

### user.info.profile
- `bio_description` - User's bio text
- `profile_deep_link` - TikTok app deep link
- `is_verified` - Verification badge status

### user.info.stats
- `follower_count` - Number of followers
- `following_count` - Number of accounts followed
- `likes_count` - Total likes received
- `video_count` - Total published videos

## Re-Authentication Required

After this update, existing TikTok connections must be re-authenticated to grant the new scope permissions:

1. User disconnects TikTok account in Settings > Platform Connections
2. User reconnects to TikTok
3. TikTok authorization screen will show new permissions
4. Enhanced metrics will be available after reconnection

## Testing

To verify the implementation:

1. Clear config cache: `php artisan config:clear`
2. Navigate to Platform Connections page
3. Click "Connect TikTok" button
4. Verify the OAuth URL contains all 6 scopes
5. Complete authorization flow
6. Check that account metrics include follower counts

## Related Documentation

- [TikTok Content Posting API](https://developers.tiktok.com/doc/content-posting-api-get-started)
- [TikTok Login Kit Scopes](https://developers.tiktok.com/doc/login-kit-manage-user-access-tokens/)
