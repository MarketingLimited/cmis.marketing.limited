# Platform API Batch Optimization

**Date:** 2025-12-06
**Author:** Claude Code Agent
**Status:** Implemented

## Summary

Implementation of a "Collect & Batch" strategy to optimize platform API usage across all supported ad platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat). This system queues API requests and batches them using platform-specific optimizations, dramatically reducing API calls and avoiding rate limits.

## Expected Impact

| Platform | Strategy | API Reduction |
|----------|----------|---------------|
| Meta | Field Expansion + Batch API | 90-95% |
| Google | SearchStream API | 70-80% |
| TikTok | Bulk Endpoints | 50-70% |
| LinkedIn | Batch Decoration | 40-60% |
| Twitter | Batch User Lookup | 40-60% |
| Snapchat | Org-Level Fetch | 50-70% |

## Architecture

### Database Tables

1. **`cmis.batch_request_queue`** - Queued API requests awaiting batch execution
   - Org-scoped with RLS
   - Deduplication via `request_key` (hash of platform:type:params)
   - Priority-based processing (1-10, lower = higher priority)
   - Status: `pending`, `processing`, `completed`, `failed`, `skipped`

2. **`cmis.webhook_events`** - Stored webhook events for reliable processing
   - PUBLIC RLS (events arrive before org identification)
   - Duplicate detection via `external_event_id`
   - Audit trail with full payload storage

3. **`cmis.batch_execution_log`** - Execution metrics and analytics
   - PUBLIC RLS (monitoring data)
   - Tracks success rate, duration, rate limit status

### Core Services

**`App\Services\Platform\BatchQueueService`**
- Central queue management
- Request deduplication
- Batcher registration and dispatch
- Rate limiter integration

**Platform Batchers** (`App\Services\Platform\Batchers\*`)
- `MetaBatcher` - Field Expansion, Batch API (50 requests/call)
- `GoogleBatcher` - SearchStream for unlimited data in single request
- `TikTokBatcher` - 100 advertisers, 2000 events per request
- `LinkedInBatcher` - Analytics pivot, batch decoration
- `TwitterBatcher` - 100 users per lookup request
- `SnapchatBatcher` - Org-level fetch with includes

### Scheduled Jobs

Jobs run on the `platform-batch` and `webhooks` queues:

| Job | Schedule | Purpose |
|-----|----------|---------|
| `FlushBatchRequestsJob('meta')` | Every 5 min | Flush Meta batch queue |
| `FlushBatchRequestsJob('google')` | Every 10 min | Flush Google batch queue |
| `FlushBatchRequestsJob('tiktok')` | Every 10 min | Flush TikTok batch queue |
| `FlushBatchRequestsJob('linkedin')` | Every 30 min | Flush LinkedIn batch queue (conservative) |
| `FlushBatchRequestsJob('twitter')` | Every 5 min | Flush Twitter batch queue |
| `FlushBatchRequestsJob('snapchat')` | Every 10 min | Flush Snapchat batch queue |
| `ProcessWebhookEventsJob` | Every minute | Process stored webhook events |

## Files Created

### Migrations
- `database/migrations/2025_12_07_000001_create_batch_request_queue_table.php`
- `database/migrations/2025_12_07_000002_create_webhook_events_table.php`
- `database/migrations/2025_12_07_000003_create_batch_execution_log_table.php`

### Models
- `app/Models/Platform/BatchRequestQueue.php`
- `app/Models/Platform/WebhookEvent.php`
- `app/Models/Platform/BatchExecutionLog.php`

### Services
- `app/Services/Platform/Batchers/PlatformBatcherInterface.php`
- `app/Services/Platform/BatchQueueService.php`
- `app/Services/Platform/Batchers/MetaBatcher.php`
- `app/Services/Platform/Batchers/GoogleBatcher.php`
- `app/Services/Platform/Batchers/TikTokBatcher.php`
- `app/Services/Platform/Batchers/LinkedInBatcher.php`
- `app/Services/Platform/Batchers/TwitterBatcher.php`
- `app/Services/Platform/Batchers/SnapchatBatcher.php`

### Jobs
- `app/Jobs/Platform/FlushBatchRequestsJob.php`
- `app/Jobs/Platform/ProcessWebhookEventsJob.php`

### Configuration
- `config/platform-batch.php`
- `app/Providers/PlatformBatchServiceProvider.php`

### Modified Files
- `app/Console/Kernel.php` - Added scheduled jobs
- `bootstrap/providers.php` - Registered service provider
- `app/Http/Controllers/API/WebhookController.php` - Store WebhookEvent for Meta, WhatsApp, TikTok, Twitter
- `app/Http/Controllers/Webhooks/LinkedInWebhookController.php` - Store WebhookEvent for LinkedIn

## Usage

### Queue an API Request

```php
use App\Services\Platform\BatchQueueService;

$batchService = app(BatchQueueService::class);

// Queue a request for batch processing
$requestId = $batchService->queue(
    orgId: $orgId,
    platform: 'meta',
    connectionId: $connection->id,
    requestType: 'get_ad_accounts',
    params: ['fields' => ['name', 'account_status', 'amount_spent']],
    priority: 5 // Default priority
);
```

### Immediate Flush (for critical operations)

```php
// Flush pending requests immediately
$results = $batchService->flush('meta', 100);

// Check results
echo "Processed: {$results['processed']}";
echo "Succeeded: {$results['success']}";
echo "Failed: {$results['failed']}";
```

### Store Webhook Event

Webhook events are now automatically stored by the webhook controllers before processing. Events are tracked with:
- Signature validation status
- Processing status (received, processing, processed, failed, ignored, duplicate)
- Automatic retry with exponential backoff for failures
- Full audit trail (headers, payload, IP, user agent)

```php
use App\Models\Platform\WebhookEvent;

// Using the convenient factory method (used by controllers)
$event = WebhookEvent::createFromRequest(
    platform: 'meta',
    payload: $request->all(),
    headers: $request->headers->all(),
    rawPayload: $request->getContent(),
    signature: $request->header('X-Hub-Signature-256'),
    signatureValid: true,
    sourceIp: $request->ip(),
    userAgent: $request->userAgent()
);

// Mark event status after processing
$event->markProcessed($orgId, $connectionId);  // Success
$event->markFailed('Error message');            // Failed (will retry)
$event->markIgnored('No matching integration'); // Ignored (no action needed)
```

### Webhook Controller Integration

All webhook controllers now store events before processing:

| Platform | Controller | Methods |
|----------|------------|---------|
| Meta | `WebhookController` | `handleMetaWebhook()` |
| WhatsApp | `WebhookController` | `handleWhatsAppWebhook()` |
| TikTok | `WebhookController` | `handleTikTokWebhook()` |
| Twitter | `WebhookController` | `handleTwitterWebhook()` |
| LinkedIn | `LinkedInWebhookController` | `handleLeadGenForm()`, `handleCampaignNotification()` |

**Key Features:**
- Events stored immediately after signature verification
- Failed signatures are logged with `signature_valid: false`
- Processing errors mark event as failed (with retry capability)
- Successful processing marks event with org/connection context

## Configuration

Environment variables:

```env
PLATFORM_BATCH_ENABLED=true
PLATFORM_BATCH_META_ENABLED=true
PLATFORM_BATCH_GOOGLE_ENABLED=true
PLATFORM_BATCH_TIKTOK_ENABLED=true
PLATFORM_BATCH_LINKEDIN_ENABLED=true
PLATFORM_BATCH_TWITTER_ENABLED=true
PLATFORM_BATCH_SNAPCHAT_ENABLED=true
PLATFORM_BATCH_LOG_LEVEL=info
PLATFORM_BATCH_LOG_REQUESTS=false
WEBHOOK_STORE_RAW=true
WEBHOOK_RETENTION_DAYS=30
```

See `config/platform-batch.php` for full configuration options.

## Monitoring

### Queue Statistics

```php
$stats = $batchService->getQueueStats('meta');
// Returns: ['pending' => 10, 'processing' => 2, 'failed' => 1]
```

### Execution Logs

```php
use App\Models\Platform\BatchExecutionLog;

$logs = BatchExecutionLog::forPlatform('meta')
    ->recent(24) // Last 24 hours
    ->get();
```

### Alerts

Configure alert thresholds in `config/platform-batch.php`:

```php
'alerts' => [
    'queue_size' => 1000,       // Alert if queue exceeds this
    'failure_rate' => 0.1,      // Alert if 10% failure rate
    'processing_time_ms' => 30000, // Alert if batch takes > 30s
],
```

## Testing

```bash
# Run all platform batch tests
php artisan test --filter=BatchQueue

# Test specific batcher
php artisan test --filter=MetaBatcher
```

## Related Documentation

- [Plan File](/home/cmis-test/.claude/plans/abundant-prancing-cocoa.md)
- [Multi-Tenancy Patterns](/.claude/knowledge/MULTI_TENANCY_PATTERNS.md)
- [Platform Integration Guide](/.claude/knowledge/PLATFORM_INTEGRATION.md)
