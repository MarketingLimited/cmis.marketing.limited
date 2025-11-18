# Platform Integrations Documentation

This directory contains comprehensive documentation for all social media and advertising platform integrations supported by CMIS.

---

## Supported Platforms

### Meta Platforms
- **[Instagram](instagram/)** - Instagram Feed, Stories, and Reels
- **[Facebook](facebook/)** - Facebook Pages and Groups

### Professional Networks
- **[LinkedIn](linkedin/)** - Company pages and personal profiles

### Video Platforms
- **[TikTok](tiktok/)** - Video publishing and analytics

### Advertising Platforms
- **[Google Ads](google/)** - Google advertising integration
- **[Meta Ads](meta/)** - Facebook and Instagram advertising

---

## Integration Overview

Each platform integration provides:

- **Authentication** - OAuth 2.0 setup and management
- **Publishing** - Content publishing capabilities
- **Analytics** - Performance metrics and insights
- **Media Management** - Image and video handling
- **API Documentation** - Platform-specific API reference

---

## Quick Start

### 1. Configure Platform Credentials

```php
// config/services.php
'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
],

'instagram' => [
    'client_id' => env('INSTAGRAM_CLIENT_ID'),
    'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
],
```

### 2. Authorize Platform

```php
// Initiate OAuth flow
Route::get('/platforms/{platform}/authorize', [PlatformController::class, 'authorize']);

// Handle callback
Route::get('/platforms/{platform}/callback', [PlatformController::class, 'callback']);
```

### 3. Publish Content

```php
use App\Services\Platform\PlatformPublisher;

$publisher = app(PlatformPublisher::class);

$result = $publisher->publish([
    'platform' => 'instagram',
    'content' => 'Check out our new collection! 🌟',
    'media' => ['image1.jpg', 'image2.jpg'],
    'scheduled_at' => '2024-12-01 10:00:00',
]);
```

---

## Platform-Specific Documentation

### Instagram Integration
**[Full Documentation](instagram/)**

- **Overview** - Instagram integration overview
- **Artisan Commands** - CLI commands for Instagram
- **AI Integration** - AI-powered features
- **Debugging** - Troubleshooting guide
- **Examples** - Code examples

**Key Features:**
- Feed posts (photos, videos, carousels)
- Stories publishing
- Reels creation
- Hashtag recommendations
- Analytics and insights

### Facebook Integration
**[Full Documentation](facebook/)**

- **English Guide** - Facebook integration (EN)
- **Arabic Guide** - دليل فيسبوك (AR)

**Key Features:**
- Page posts
- Group publishing
- Photo albums
- Video uploads
- Events and promotions

### LinkedIn Integration
**[Full Documentation](linkedin/)**

- **English Guide** - LinkedIn integration (EN)
- **Arabic Guide** - دليل لينكد إن (AR)

**Key Features:**
- Company page posts
- Personal profile updates
- Article publishing
- Document sharing
- Analytics

### TikTok Integration
**[Full Documentation](tiktok/)**

- **English Guide** - TikTok integration (EN)
- **Arabic Guide** - دليل تيك توك (AR)

**Key Features:**
- Video uploads
- Video management
- Analytics
- Trending sounds
- Hashtag research

---

## Common Integration Patterns

### OAuth 2.0 Flow

All platforms use OAuth 2.0 for authentication:

```
1. User clicks "Connect [Platform]"
   ↓
2. Redirect to platform authorization URL
   ↓
3. User grants permissions
   ↓
4. Platform redirects to callback URL with code
   ↓
5. Exchange code for access token
   ↓
6. Store access token securely
   ↓
7. Use token for API calls
```

### Publishing Flow

```
1. Create content in CMIS
   ↓
2. Select target platforms
   ↓
3. Platform-specific adaptation
   ↓
4. Media optimization
   ↓
5. Validation
   ↓
6. Schedule or publish immediately
   ↓
7. Queue for background processing
   ↓
8. Platform API call
   ↓
9. Store result and metrics
```

### Analytics Collection

```
1. Platform webhook notification
   ↓
2. Webhook handler validates signature
   ↓
3. Extract metrics data
   ↓
4. Store in database
   ↓
5. Update analytics dashboard
   ↓
6. Trigger notifications if needed
```

---

## Platform Adapter Pattern

All platforms implement a common interface:

```php
interface PlatformAdapterInterface
{
    /**
     * Authenticate with the platform
     */
    public function authorize(): string;

    /**
     * Handle OAuth callback
     */
    public function handleCallback(string $code): AccessToken;

    /**
     * Publish content to the platform
     */
    public function publish(Post $post): PublishResult;

    /**
     * Get post metrics
     */
    public function getMetrics(string $postId): Metrics;

    /**
     * Upload media
     */
    public function uploadMedia(Media $media): MediaResult;
}
```

---

## Rate Limiting

Each platform has different rate limits:

| Platform | Rate Limit | Per |
|----------|-----------|-----|
| Instagram | 200 calls | hour |
| Facebook | 200 calls | hour per user |
| LinkedIn | 100 calls | day |
| TikTok | 100 calls | 15 minutes |

### Handling Rate Limits

```php
use App\Services\Platform\RateLimiter;

$limiter = new RateLimiter('instagram');

if ($limiter->tooManyAttempts()) {
    $availableAt = $limiter->availableAt();
    throw new RateLimitException("Rate limit exceeded. Try again at {$availableAt}");
}

$limiter->hit();

// Make API call
$result = $platform->publish($post);
```

---

## Error Handling

### Common Errors

**Authentication Errors:**
- Expired tokens → Refresh token or re-authenticate
- Invalid credentials → Update credentials
- Insufficient permissions → Request additional permissions

**Publishing Errors:**
- Media format not supported → Convert media
- Content violates policies → Review content
- Post too long → Truncate or split content

**Rate Limit Errors:**
- Too many requests → Queue and retry later
- Daily limit exceeded → Wait for reset

### Error Handling Example

```php
try {
    $result = $publisher->publish($post);
} catch (AuthenticationException $e) {
    // Token expired, refresh it
    $platform->refreshToken();
    return $publisher->publish($post);
} catch (RateLimitException $e) {
    // Queue for later
    PublishPostJob::dispatch($post)->delay($e->getRetryAfter());
} catch (ValidationException $e) {
    // Content issue, notify user
    $this->notifyUser($post, $e->getMessage());
}
```

---

## Media Handling

### Media Optimization

Different platforms have different media requirements:

```php
use App\Services\Media\MediaOptimizer;

$optimizer = new MediaOptimizer();

// Optimize for Instagram
$optimized = $optimizer->optimize($media, [
    'platform' => 'instagram',
    'type' => 'feed',
    'format' => 'jpg',
    'max_width' => 1080,
    'max_height' => 1350,
    'quality' => 85
]);

// Optimize for TikTok
$optimized = $optimizer->optimize($video, [
    'platform' => 'tiktok',
    'format' => 'mp4',
    'max_duration' => 60,
    'resolution' => '1080x1920',
    'fps' => 30
]);
```

### Media Requirements by Platform

**Instagram:**
- Images: JPG/PNG, max 1080x1350px, max 8MB
- Videos: MP4, max 60s, max 100MB
- Carousels: 2-10 images/videos

**Facebook:**
- Images: JPG/PNG, max 2048x2048px, max 4MB
- Videos: MP4, max 120min, max 4GB

**LinkedIn:**
- Images: JPG/PNG, max 5000x5000px, max 5MB
- Videos: MP4, max 10min, max 5GB

**TikTok:**
- Videos: MP4, 9:16 aspect ratio, max 60s, max 500MB

---

## Testing Platform Integrations

### Unit Tests

```bash
# Test platform adapters
php artisan test --filter=PlatformAdapterTest

# Test specific platform
php artisan test --filter=InstagramAdapterTest
```

### Integration Tests

```bash
# Test OAuth flow
php artisan test --filter=OAuthFlowTest

# Test publishing
php artisan test --filter=PublishingTest
```

### Manual Testing

Use sandbox/test accounts:
- Instagram: Create test account via Meta App Dashboard
- Facebook: Use test pages
- LinkedIn: Use test company pages
- TikTok: Contact TikTok for sandbox access

---

## Troubleshooting

### Common Issues

**OAuth Fails:**
1. Check redirect URI matches configured value
2. Verify app credentials
3. Check app permissions
4. Review error logs

**Publishing Fails:**
1. Verify token is valid
2. Check media format
3. Review content for policy violations
4. Check rate limits

**Analytics Not Updating:**
1. Verify webhook configuration
2. Check webhook secret
3. Review webhook logs
4. Test webhook endpoint

For platform-specific troubleshooting, see individual platform documentation.

---

## Best Practices

### Security
- Store tokens encrypted
- Use environment variables for credentials
- Validate webhook signatures
- Implement token rotation
- Log all API calls for audit

### Performance
- Cache platform data where appropriate
- Use background jobs for publishing
- Implement retry logic
- Batch operations when possible
- Monitor API usage

### Content
- Respect platform guidelines
- Optimize media before upload
- Use platform-specific features
- Test content on each platform
- Monitor engagement metrics

---

## Related Documentation

- **[API Documentation](../api/)** - REST API reference
- **[Social Publishing](../features/social-publishing/)** - Social publishing features
- **[Campaign Management](../features/campaigns/)** - Campaign management

---

## Support

- **Integration Issues** → Check platform-specific documentation
- **OAuth Problems** → Review authentication guides
- **Publishing Errors** → See troubleshooting sections
- **API Questions** → Check [API Documentation](../api/)

---

**Last Updated:** 2025-11-18
**Maintained by:** CMIS Platform Integration Team
