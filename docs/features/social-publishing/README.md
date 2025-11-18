# Social Publishing Documentation

This directory contains comprehensive documentation for CMIS social publishing features, including multi-platform publishing, scheduling, and analytics.

---

## Quick Navigation

- **[Overview](overview.md)** - Social publishing capabilities
- **[Analysis Report](analysis-report.md)** - Detailed technical analysis
- **[Critical Issues](critical-issues.md)** - Known issues and fixes
- **[Implementation Guide](implementation-guide.md)** - Implementation steps
- **[Platform Integration](../../integrations/)** - Platform-specific docs

---

## Overview

CMIS Social Publishing enables multi-platform content distribution with:

- **Multi-Platform Support** - Facebook, Instagram, LinkedIn, TikTok, Twitter
- **Unified Publishing** - Single interface for all platforms
- **Scheduling** - Advanced scheduling with timezone support
- **Content Optimization** - Platform-specific content adaptation
- **Analytics** - Cross-platform performance tracking

---

## Supported Platforms

### Meta Platforms
- **Facebook Pages** - Posts, images, videos, stories
- **Facebook Groups** - Group posting and management
- **Instagram Feed** - Photos, videos, carousels
- **Instagram Stories** - Story publishing
- **Instagram Reels** - Reel creation and publishing

### Professional Networks
- **LinkedIn** - Company pages and personal profiles
- **LinkedIn Articles** - Long-form content

### Video Platforms
- **TikTok** - Video publishing and analytics
- **YouTube** - Video uploads and management (planned)

### Microblogging
- **Twitter/X** - Tweets, threads, media

---

## Key Features

### 1. Unified Publishing Interface
- Single composer for all platforms
- Platform-specific preview
- Content adaptation suggestions
- Media optimization per platform

### 2. Advanced Scheduling
- Timezone-aware scheduling
- Best time recommendations
- Recurring posts
- Queue management
- Bulk scheduling

### 3. Content Management
- Media library integration
- Asset organization
- Version control
- Template library
- Content recycling

### 4. Multi-Platform Analytics
- Unified dashboard
- Cross-platform metrics
- Engagement tracking
- ROI calculation
- Custom reports

### 5. Workflow & Approval
- Multi-step approval process
- Role-based permissions
- Review and feedback
- Revision history

---

## Documentation Structure

### For Product Managers
- [Overview](overview.md) - Feature overview
- [Critical Issues](critical-issues.md) - Current status and blockers

### For Developers
- [Analysis Report](analysis-report.md) - Technical architecture
- [Implementation Guide](implementation-guide.md) - Development guide
- [Platform Integration Docs](../../integrations/) - Platform-specific APIs

### For QA Engineers
- [Critical Issues](critical-issues.md) - Known bugs and test cases
- [Implementation Guide](implementation-guide.md) - Testing requirements

### For Support Team
- [Critical Issues](critical-issues.md) - Troubleshooting guide
- [Platform Integration Docs](../../integrations/) - Platform-specific help

---

## Platform Documentation

Detailed platform-specific documentation:

- **[Instagram Integration](../../integrations/instagram/)** - Instagram setup and usage
- **[Facebook Integration](../../integrations/facebook/)** - Facebook configuration
- **[LinkedIn Integration](../../integrations/linkedin/)** - LinkedIn setup
- **[TikTok Integration](../../integrations/tiktok/)** - TikTok integration
- **[Meta Platforms](../../integrations/meta/)** - General Meta integration

---

## Common Tasks

### Publishing a Post

```php
// Create a multi-platform post
$post = SocialPost::create([
    'content' => 'Your post content',
    'platforms' => ['facebook', 'instagram', 'linkedin'],
    'scheduled_at' => '2024-12-01 10:00:00',
    'media' => [
        'type' => 'image',
        'url' => 'https://example.com/image.jpg'
    ]
]);

// Publish immediately
$post->publish();

// Or schedule for later
$post->schedule();
```

### Platform-Specific Content

```php
// Customize content per platform
$post = SocialPost::create([
    'content' => [
        'facebook' => 'Facebook-specific content with #hashtags',
        'instagram' => 'Instagram content with emojis 📸',
        'linkedin' => 'Professional LinkedIn content'
    ],
    'platforms' => ['facebook', 'instagram', 'linkedin']
]);
```

### Bulk Scheduling

```php
// Schedule multiple posts
SocialPost::bulkSchedule([
    ['content' => 'Post 1', 'scheduled_at' => '2024-12-01 09:00:00'],
    ['content' => 'Post 2', 'scheduled_at' => '2024-12-01 12:00:00'],
    ['content' => 'Post 3', 'scheduled_at' => '2024-12-01 15:00:00'],
], ['facebook', 'instagram']);
```

---

## Architecture

### Components

1. **Publishing Service** - Core publishing logic
2. **Platform Adapters** - Platform-specific implementations
3. **Queue System** - Background job processing
4. **Media Processor** - Image/video optimization
5. **Analytics Collector** - Metrics gathering

### Data Flow

```
Content Creation → Validation → Platform Adaptation →
Media Processing → Scheduling → Queue → Publishing →
Analytics Collection → Reporting
```

### Database Schema

- `social_posts` - Post content and metadata
- `social_platforms` - Platform configurations
- `social_schedules` - Scheduling information
- `social_analytics` - Performance metrics
- `social_media` - Media assets

---

## Known Issues

See [Critical Issues](critical-issues.md) for:
- Current bugs and workarounds
- Platform API limitations
- Rate limiting issues
- Known incompatibilities
- Planned fixes

---

## Best Practices

### Content Strategy
- Optimize content for each platform
- Use platform-specific features
- Schedule posts at optimal times
- Monitor engagement metrics

### Media Management
- Optimize images before upload
- Use platform-recommended sizes
- Compress videos appropriately
- Test media on each platform

### Error Handling
- Implement retry logic
- Log platform errors
- Monitor API rate limits
- Handle webhook failures gracefully

### Security
- Secure API credentials
- Use OAuth 2.0 properly
- Rotate access tokens
- Audit platform permissions

---

## API Reference

For detailed API documentation, see:
- [Main API Documentation](../../api/)
- [Platform Integration Endpoints](../../api/)
- [Instagram API Instructions](../../instagram_api_Instructions.json)

---

## Testing

### Unit Tests
```bash
php artisan test --filter=SocialPublishingTest
```

### Integration Tests
```bash
php artisan test --filter=PlatformIntegrationTest
```

### E2E Tests
```bash
npm run test:e2e -- social-publishing
```

---

## Troubleshooting

### Common Issues

**Publishing Fails**
- Check platform API credentials
- Verify rate limits
- Check media file formats
- Review platform-specific requirements

**Scheduling Not Working**
- Verify cron jobs are running
- Check queue worker status
- Review scheduled_at timestamps
- Verify timezone settings

**Analytics Not Updating**
- Check webhook configurations
- Verify analytics collector is running
- Review platform API permissions
- Check database connections

For more troubleshooting, see [Critical Issues](critical-issues.md).

---

## Related Documentation

- **[Campaign Management](../campaigns/)** - Campaign features
- **[Platform Integrations](../../integrations/)** - Platform setup
- **[API Documentation](../../api/)** - API reference
- **[Analytics](../../features/)** - Analytics documentation

---

## Contributing

When contributing to social publishing features:

1. Test on all supported platforms
2. Handle platform-specific edge cases
3. Update platform documentation
4. Add integration tests
5. Document API changes

---

## Support

- **Platform Issues** → Check [Platform Integration Docs](../../integrations/)
- **Publishing Problems** → See [Critical Issues](critical-issues.md)
- **API Questions** → Check [API Documentation](../../api/)
- **General Help** → See [Overview](overview.md)

---

**Last Updated:** 2025-11-18
**Maintained by:** CMIS Social Media Team
