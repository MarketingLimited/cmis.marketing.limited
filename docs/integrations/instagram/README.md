# Instagram Integration Documentation

Complete documentation for CMIS Instagram integration, including setup, usage, AI features, and troubleshooting.

---

## Documentation Index

1. **[Overview](01_overview.md)** - Instagram integration overview and setup
2. **[Artisan Commands](02_artisan_commands.md)** - CLI commands for Instagram management
3. **[AI Integration](03_ai_integration.md)** - AI-powered Instagram features
4. **[Debugging & Logs](04_debugging_and_logs.md)** - Troubleshooting and logging
5. **[Examples](05_examples.md)** - Code examples and use cases
6. **[Help Guide (English)](help_en.md)** - Instagram integration help

---

## Quick Start

### 1. Configure Instagram App

Add Instagram credentials to your `.env`:

```env
INSTAGRAM_CLIENT_ID=your_client_id
INSTAGRAM_CLIENT_SECRET=your_client_secret
INSTAGRAM_REDIRECT_URI=https://your-domain.com/auth/instagram/callback
```

### 2. Authorize Instagram Account

```bash
# Using artisan command
php artisan instagram:authorize

# Or via web interface
# Navigate to: /platforms/instagram/authorize
```

### 3. Publish to Instagram

```php
use App\Services\Platform\Instagram\InstagramPublisher;

$publisher = app(InstagramPublisher::class);

$result = $publisher->publish([
    'caption' => 'Check out our new collection! 🌟 #fashion #style',
    'media' => [
        storage_path('app/images/photo1.jpg'),
        storage_path('app/images/photo2.jpg'),
    ],
    'type' => 'carousel',
]);
```

---

## Key Features

### Content Publishing
- **Feed Posts** - Single images, videos, and carousels
- **Stories** - 24-hour temporary content
- **Reels** - Short-form video content (up to 60s)

### AI-Powered Features
- **Caption Generation** - AI-generated captions
- **Hashtag Recommendations** - Smart hashtag suggestions
- **Content Analysis** - Performance predictions
- **Image Enhancement** - AI-powered image optimization

### Analytics & Insights
- **Engagement Metrics** - Likes, comments, shares
- **Reach & Impressions** - Post performance
- **Follower Growth** - Audience analytics
- **Best Time to Post** - Optimal posting times

---

## Artisan Commands

### Authorization

```bash
# Authorize Instagram account
php artisan instagram:authorize

# Refresh access token
php artisan instagram:refresh-token
```

### Publishing

```bash
# Publish single image
php artisan instagram:publish /path/to/image.jpg --caption="Your caption"

# Publish carousel
php artisan instagram:carousel /path/to/images/*.jpg

# Publish story
php artisan instagram:story /path/to/image.jpg
```

### Analytics

```bash
# Get account insights
php artisan instagram:insights

# Get post metrics
php artisan instagram:post-metrics {post_id}

# Get follower analytics
php artisan instagram:followers
```

### Management

```bash
# List Instagram accounts
php artisan instagram:list

# Get account info
php artisan instagram:info

# Delete post
php artisan instagram:delete {media_id}
```

See [Artisan Commands](02_artisan_commands.md) for complete reference.

---

## AI Integration

### AI Caption Generation

```php
use App\Services\AI\CaptionGenerator;

$generator = app(CaptionGenerator::class);

$caption = $generator->generate([
    'platform' => 'instagram',
    'image' => $imagePath,
    'tone' => 'casual',
    'length' => 'medium',
    'include_hashtags' => true,
]);

// Result: "Summer vibes in the city ☀️ Making memories that last forever ✨ #summer #citylife #photography #instagood"
```

### Smart Hashtag Recommendations

```php
use App\Services\AI\HashtagRecommender;

$recommender = app(HashtagRecommender::class);

$hashtags = $recommender->recommend([
    'content' => 'Fashion photoshoot in Paris',
    'image' => $imagePath,
    'niche' => 'fashion',
    'count' => 10,
    'include_trending' => true,
]);

// Result: ['#fashion', '#paris', '#photoshoot', '#style', '#ootd', ...]
```

### Content Performance Prediction

```php
use App\Services\AI\ContentAnalyzer;

$analyzer = app(ContentAnalyzer::class);

$prediction = $analyzer->predict([
    'caption' => $caption,
    'image' => $imagePath,
    'post_time' => '2024-12-01 18:00:00',
]);

// Result:
// {
//     "predicted_engagement": 8.5,
//     "optimal_time": "18:30:00",
//     "suggestions": [
//         "Add more emojis for better engagement",
//         "Consider posting during peak hours (18:00-20:00)"
//     ]
// }
```

See [AI Integration](03_ai_integration.md) for complete AI features documentation.

---

## Publishing Examples

### Single Image Post

```php
$result = $publisher->publish([
    'caption' => 'Beautiful sunset 🌅 #nature',
    'media' => 'storage/images/sunset.jpg',
]);
```

### Carousel Post (Multiple Images)

```php
$result = $publisher->publish([
    'caption' => 'Our latest collection! Swipe to see more 👉',
    'media' => [
        'storage/images/photo1.jpg',
        'storage/images/photo2.jpg',
        'storage/images/photo3.jpg',
    ],
    'type' => 'carousel',
]);
```

### Video Post

```php
$result = $publisher->publish([
    'caption' => 'Behind the scenes 🎬 #bts',
    'media' => 'storage/videos/bts.mp4',
    'type' => 'video',
]);
```

### Story

```php
$result = $publisher->publishStory([
    'media' => 'storage/images/story.jpg',
    'link' => 'https://example.com/product',
]);
```

### Reel

```php
$result = $publisher->publishReel([
    'caption' => 'Quick tutorial 💡 #tutorial',
    'media' => 'storage/videos/tutorial.mp4',
    'cover_image' => 'storage/images/cover.jpg',
    'audio' => 'trending_audio_id',
]);
```

See [Examples](05_examples.md) for more code examples.

---

## Media Requirements

### Images
- **Formats:** JPG, PNG
- **Aspect Ratios:**
  - Square: 1:1 (1080x1080px)
  - Portrait: 4:5 (1080x1350px)
  - Landscape: 1.91:1 (1080x566px)
- **File Size:** Max 8MB
- **Resolution:** Up to 1080x1350px

### Videos
- **Formats:** MP4, MOV
- **Duration:**
  - Feed: 3-60 seconds
  - Reels: 15-60 seconds
  - Stories: Up to 15 seconds per clip
- **File Size:** Max 100MB
- **Resolution:** Min 720px
- **Aspect Ratio:** 9:16 (vertical) recommended for Reels

### Carousels
- **Number of Items:** 2-10 images/videos
- **Mixed Media:** Can combine images and videos
- **Per-item Requirements:** Same as individual posts

---

## Error Handling

### Common Errors

**Invalid Access Token:**
```php
try {
    $result = $publisher->publish($post);
} catch (InvalidAccessTokenException $e) {
    // Refresh token and retry
    $platform->refreshToken();
    $result = $publisher->publish($post);
}
```

**Media Format Error:**
```php
try {
    $result = $publisher->publish($post);
} catch (InvalidMediaException $e) {
    // Convert or optimize media
    $optimizedMedia = $optimizer->optimize($media, 'instagram');
    $result = $publisher->publish(['media' => $optimizedMedia, ...]);
}
```

**Rate Limit:**
```php
try {
    $result = $publisher->publish($post);
} catch (RateLimitException $e) {
    // Queue for later
    PublishInstagramJob::dispatch($post)->delay($e->getRetryAfter());
}
```

See [Debugging & Logs](04_debugging_and_logs.md) for comprehensive troubleshooting.

---

## Debugging

### Enable Debug Logging

```env
INSTAGRAM_DEBUG=true
LOG_LEVEL=debug
```

### View Logs

```bash
# View Instagram-specific logs
tail -f storage/logs/instagram.log

# View all logs
tail -f storage/logs/laravel.log

# Using artisan
php artisan instagram:logs
```

### Common Debug Commands

```bash
# Test connection
php artisan instagram:test-connection

# Validate token
php artisan instagram:validate-token

# Check API status
php artisan instagram:api-status
```

See [Debugging & Logs](04_debugging_and_logs.md) for detailed debugging guide.

---

## Best Practices

### Content Guidelines
- Use high-quality images (1080x1080px or higher)
- Keep captions concise and engaging
- Use 5-10 relevant hashtags
- Post consistently (1-2 times daily)
- Engage with your audience

### Hashtag Strategy
- Mix popular and niche hashtags
- Use branded hashtags
- Avoid banned or spammy hashtags
- Research trending hashtags in your niche
- Place hashtags at the end of caption or first comment

### Posting Schedule
- Post during peak engagement hours
- Use Instagram Insights to find best times
- Maintain consistent posting schedule
- Consider timezone of target audience

### Media Optimization
- Use proper aspect ratios
- Compress images without quality loss
- Add alt text for accessibility
- Use high-quality thumbnails for videos
- Test media before publishing

---

## Analytics

### Engagement Metrics

```php
$metrics = $instagram->getPostMetrics($mediaId);

// Returns:
// {
//     "likes": 1234,
//     "comments": 56,
//     "shares": 12,
//     "saves": 89,
//     "reach": 5678,
//     "impressions": 7890,
//     "engagement_rate": 0.085
// }
```

### Account Insights

```php
$insights = $instagram->getAccountInsights();

// Returns:
// {
//     "followers": 10000,
//     "following": 500,
//     "posts": 250,
//     "engagement_rate": 0.075,
//     "reach_7d": 25000,
//     "impressions_7d": 50000
// }
```

---

## Troubleshooting

### Publishing Issues

**Problem:** Post fails to publish
- Check access token validity
- Verify media format and size
- Review caption for policy violations
- Check API rate limits

**Problem:** Media upload fails
- Verify file format
- Check file size limits
- Ensure proper permissions
- Try optimizing media

### Authentication Issues

**Problem:** OAuth flow fails
- Verify redirect URI matches configuration
- Check app credentials
- Ensure proper permissions requested
- Review error logs

**Problem:** Token expired
- Implement automatic token refresh
- Monitor token expiration
- Handle token refresh errors

See [Debugging & Logs](04_debugging_and_logs.md) for more troubleshooting scenarios.

---

## Related Documentation

- **[Platform Integrations](../)** - All platform integrations
- **[Social Publishing](../../features/social-publishing/)** - Social publishing features
- **[AI Features](../../features/ai-semantic/)** - AI capabilities
- **[API Documentation](../../api/)** - REST API reference

---

## Support

- **Setup Issues** → See [Overview](01_overview.md)
- **Command Help** → See [Artisan Commands](02_artisan_commands.md)
- **AI Features** → See [AI Integration](03_ai_integration.md)
- **Troubleshooting** → See [Debugging & Logs](04_debugging_and_logs.md)
- **Code Examples** → See [Examples](05_examples.md)

---

**Last Updated:** 2025-11-18
**Maintained by:** CMIS Platform Integration Team
