# خطة عمل تنفيذية - إصلاح وتحسين تكاملات المنصات الخارجية

**تاريخ الإنشاء:** 2025-12-06
**المدة المقترحة:** 3 أسابيع
**الأولوية:** حرجة
**الفريق المطلوب:** 2-3 مطورين

---

## 🎯 الهدف

تحويل نظام تكاملات المنصات من prototype إلى production-ready خلال 3 أسابيع.

---

## 📅 الأسبوع الأول: إصلاح المشاكل الحرجة

### يوم 1-2: تنفيذ Data Synchronization الفعلي

#### المهمة 1: تنفيذ fetchChannelsFromPlatform()
```php
// app/Jobs/SyncPlatformDataJob.php
protected function fetchChannelsFromPlatform(): array
{
    $connector = $this->getConnectorForPlatform($this->integration->platform);

    switch ($this->integration->platform) {
        case 'meta':
            return $this->fetchMetaChannels($connector);
        case 'google':
            return $this->fetchGoogleChannels($connector);
        case 'tiktok':
            return $this->fetchTikTokChannels($connector);
        default:
            Log::warning("No channel fetcher for platform: {$this->integration->platform}");
            return [];
    }
}

private function fetchMetaChannels($connector): array
{
    $channels = [];

    // Fetch Facebook Pages
    $pages = $connector->makeRequest(
        $this->integration,
        'GET',
        '/me/accounts',
        ['fields' => 'id,name,access_token,category']
    );

    foreach ($pages['data'] ?? [] as $page) {
        $channels[] = [
            'id' => $page['id'],
            'name' => $page['name'],
            'type' => 'facebook_page',
            'settings' => [
                'page_access_token' => $page['access_token'],
                'category' => $page['category'],
            ],
        ];
    }

    // Fetch Instagram Business Accounts
    $instagram = $connector->makeRequest(
        $this->integration,
        'GET',
        '/me/instagram_business_accounts',
        ['fields' => 'id,username,profile_picture_url']
    );

    foreach ($instagram['data'] ?? [] as $account) {
        $channels[] = [
            'id' => $account['id'],
            'name' => $account['username'],
            'type' => 'instagram_business',
            'settings' => [
                'profile_picture' => $account['profile_picture_url'],
            ],
        ];
    }

    return $channels;
}
```

#### المهمة 2: تنفيذ fetchAdAccountsFromPlatform()
```php
protected function fetchAdAccountsFromPlatform(): array
{
    $connector = $this->getConnectorForPlatform($this->integration->platform);

    switch ($this->integration->platform) {
        case 'meta':
            return $this->fetchMetaAdAccounts($connector);
        case 'google':
            return $this->fetchGoogleAdAccounts($connector);
        // ... إضافة باقي المنصات
    }
}
```

### يوم 3: إضافة Idempotency للـ Webhooks

#### المهمة 3: إنشاء جدول webhook_events_processed
```sql
CREATE TABLE cmis_platform.webhook_events_processed (
    event_id VARCHAR(255) PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    processed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    org_id UUID,
    INDEX idx_platform_processed (platform, processed_at),
    INDEX idx_org_processed (org_id, processed_at)
);

-- Auto cleanup old events (> 30 days)
CREATE EVENT cleanup_old_webhook_events
ON SCHEDULE EVERY 1 DAY
DO DELETE FROM cmis_platform.webhook_events_processed
WHERE processed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

#### المهمة 4: تحديث WebhookController
```php
private function isDuplicateEvent(string $eventId, string $platform): bool
{
    return DB::table('cmis_platform.webhook_events_processed')
        ->where('event_id', $eventId)
        ->where('platform', $platform)
        ->exists();
}

private function markEventAsProcessed(string $eventId, string $platform, ?string $orgId = null): void
{
    DB::table('cmis_platform.webhook_events_processed')->insert([
        'event_id' => $eventId,
        'platform' => $platform,
        'org_id' => $orgId,
        'processed_at' => now(),
    ]);
}

public function handleMetaWebhook(Request $request)
{
    // Extract event ID
    $eventId = $request->input('id') ??
               $request->header('X-Event-Id') ??
               md5($request->getContent());

    // Check for duplicate
    if ($this->isDuplicateEvent($eventId, 'meta')) {
        Log::info('Duplicate Meta webhook ignored', ['event_id' => $eventId]);
        return response()->json(['status' => 'already_processed']);
    }

    // Verify signature...
    // Process event...

    // Mark as processed
    $this->markEventAsProcessed($eventId, 'meta', $orgId);
}
```

### يوم 4-5: تنفيذ Token Refresh الفعلي

#### المهمة 5: إنشاء TokenRefreshService
```php
namespace App\Services\Platform;

class TokenRefreshService
{
    private array $refreshStrategies = [];

    public function __construct()
    {
        $this->refreshStrategies = [
            'meta' => new MetaTokenRefreshStrategy(),
            'google' => new GoogleTokenRefreshStrategy(),
            'tiktok' => new TikTokTokenRefreshStrategy(),
            // ...
        ];
    }

    public function refreshIfNeeded(Integration $integration): Integration
    {
        if (!$this->shouldRefresh($integration)) {
            return $integration;
        }

        $strategy = $this->refreshStrategies[$integration->platform] ?? null;

        if (!$strategy) {
            throw new UnsupportedPlatformException(
                "No refresh strategy for platform: {$integration->platform}"
            );
        }

        try {
            $newTokenData = $strategy->refresh($integration);

            $integration->update([
                'access_token' => encrypt($newTokenData['access_token']),
                'refresh_token' => isset($newTokenData['refresh_token'])
                    ? encrypt($newTokenData['refresh_token'])
                    : $integration->refresh_token,
                'token_expires_at' => now()->addSeconds($newTokenData['expires_in']),
                'last_refreshed_at' => now(),
                'refresh_count' => $integration->refresh_count + 1,
            ]);

            Log::info('Token refreshed successfully', [
                'platform' => $integration->platform,
                'integration_id' => $integration->id,
                'expires_at' => $integration->token_expires_at,
            ]);

            return $integration->fresh();

        } catch (RefreshFailedException $e) {
            $this->handleRefreshFailure($integration, $e);
            throw $e;
        }
    }

    private function shouldRefresh(Integration $integration): bool
    {
        // Refresh if expires in less than 10 minutes
        if (!$integration->token_expires_at) {
            // Check token validity with API call if no expiry stored
            return $this->checkTokenValidity($integration) === false;
        }

        return $integration->token_expires_at->subMinutes(10)->isPast();
    }
}
```

#### المهمة 6: إنشاء Scheduled Command للـ Token Refresh
```php
// app/Console/Commands/RefreshExpringTokens.php
class RefreshExpiringTokens extends Command
{
    protected $signature = 'tokens:refresh';
    protected $description = 'Refresh expiring platform tokens';

    public function handle(TokenRefreshService $refreshService)
    {
        $expiringIntegrations = Integration::query()
            ->where('is_active', true)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addHours(24))
            ->get();

        $this->info("Found {$expiringIntegrations->count()} tokens expiring soon");

        foreach ($expiringIntegrations as $integration) {
            try {
                $refreshService->refreshIfNeeded($integration);
                $this->info("✓ Refreshed token for {$integration->platform} - Org: {$integration->org_id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to refresh {$integration->platform}: {$e->getMessage()}");

                // Notify organization admin
                NotificationService::notifyTokenExpiring($integration);
            }
        }
    }
}

// Add to Kernel.php
$schedule->command('tokens:refresh')->hourly();
```

---

## 📅 الأسبوع الثاني: تحسين Error Handling والـ Async Processing

### يوم 6-7: Async Webhook Processing

#### المهمة 7: إنشاء ProcessWebhookJob
```php
namespace App\Jobs\Webhooks;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 120;

    private array $payload;
    private string $platform;
    private string $eventId;

    public function __construct(array $payload, string $platform, string $eventId)
    {
        $this->payload = $payload;
        $this->platform = $platform;
        $this->eventId = $eventId;
        $this->onQueue('webhooks');
    }

    public function handle()
    {
        $processor = WebhookProcessorFactory::make($this->platform);

        try {
            $processor->process($this->payload);

            Log::info('Webhook processed successfully', [
                'platform' => $this->platform,
                'event_id' => $this->eventId,
            ]);

        } catch (TransientException $e) {
            // Retry-able error
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                $this->moveToDeadLetter($e->getMessage());
            }
        } catch (PermanentException $e) {
            // Don't retry
            $this->fail($e);
            $this->moveToDeadLetter($e->getMessage());
        }
    }
}
```

### يوم 8-9: Circuit Breaker Implementation

#### المهمة 8: إنشاء CircuitBreakerService
```php
namespace App\Services\Platform;

class CircuitBreakerService
{
    private const FAILURE_THRESHOLD = 5;
    private const SUCCESS_THRESHOLD = 2;
    private const TIMEOUT = 60; // seconds

    public function call(string $service, callable $callback)
    {
        $state = $this->getState($service);

        if ($state === 'open') {
            if ($this->shouldAttemptReset($service)) {
                $state = 'half-open';
            } else {
                throw new CircuitOpenException("Service {$service} is unavailable");
            }
        }

        try {
            $result = $callback();

            if ($state === 'half-open') {
                $this->recordSuccess($service);

                if ($this->getSuccessCount($service) >= self::SUCCESS_THRESHOLD) {
                    $this->close($service);
                }
            }

            return $result;

        } catch (\Exception $e) {
            $this->recordFailure($service);

            if ($this->getFailureCount($service) >= self::FAILURE_THRESHOLD) {
                $this->open($service);
            }

            throw $e;
        }
    }

    private function open(string $service): void
    {
        Cache::put("circuit:{$service}:state", 'open', self::TIMEOUT);
        Cache::put("circuit:{$service}:opened_at", now());

        Log::warning("Circuit breaker opened for service: {$service}");

        // Notify ops team
        NotificationService::notifyCircuitOpen($service);
    }
}
```

### يوم 10: Custom Exception Classes

#### المهمة 9: إنشاء Platform-Specific Exceptions
```php
// app/Exceptions/Platform/PlatformException.php
abstract class PlatformException extends Exception
{
    protected string $platform;
    protected ?string $orgId;
    protected array $context = [];

    public function report()
    {
        Log::error($this->getMessage(), [
            'platform' => $this->platform,
            'org_id' => $this->orgId,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}

// app/Exceptions/Platform/TokenExpiredException.php
class TokenExpiredException extends PlatformException
{
    protected bool $shouldNotifyUser = true;

    public function __construct(Integration $integration)
    {
        $this->platform = $integration->platform;
        $this->orgId = $integration->org_id;

        parent::__construct(
            "Access token expired for {$this->platform} integration"
        );
    }
}

// app/Exceptions/Platform/RateLimitExceededException.php
class RateLimitExceededException extends PlatformException
{
    protected int $retryAfter;

    public function __construct(string $platform, int $retryAfter)
    {
        $this->platform = $platform;
        $this->retryAfter = $retryAfter;

        parent::__construct(
            "Rate limit exceeded for {$platform}. Retry after {$retryAfter} seconds."
        );
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
```

---

## 📅 الأسبوع الثالث: Testing والـ Monitoring

### يوم 11-13: Integration Tests

#### المهمة 10: إنشاء Integration Tests
```php
// tests/Feature/Platform/MetaIntegrationTest.php
class MetaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_flow()
    {
        // Mock Meta API responses
        Http::fake([
            'graph.facebook.com/*/oauth/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ]),
            'graph.facebook.com/*/me' => Http::response([
                'id' => '123456',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]),
        ]);

        $connector = new MetaConnector();

        $integration = $connector->connect('test-auth-code', [
            'org_id' => 'test-org-id',
        ]);

        $this->assertNotNull($integration);
        $this->assertEquals('meta', $integration->platform);
        $this->assertEquals('123456', $integration->external_account_id);
        $this->assertTrue($integration->is_active);
    }

    public function test_webhook_signature_verification()
    {
        $payload = json_encode(['test' => 'data']);
        $secret = 'test-secret';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $request = Request::create(
            '/webhooks/meta',
            'POST',
            [],
            [],
            [],
            ['HTTP_X-HUB-SIGNATURE-256' => $signature],
            $payload
        );

        $middleware = new VerifyWebhookSignature();

        $result = $middleware->handle($request, function () {
            return response('OK');
        }, 'meta');

        $this->assertEquals('OK', $result->getContent());
    }

    public function test_idempotent_webhook_processing()
    {
        $eventId = 'test-event-123';
        $platform = 'meta';

        // First processing
        $response1 = $this->postJson('/api/webhooks/meta', [
            'id' => $eventId,
            'object' => 'page',
            'entry' => [],
        ]);

        $response1->assertStatus(200);
        $response1->assertJson(['status' => 'queued']);

        // Second processing (duplicate)
        $response2 = $this->postJson('/api/webhooks/meta', [
            'id' => $eventId,
            'object' => 'page',
            'entry' => [],
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['status' => 'already_processed']);
    }
}
```

### يوم 14-15: Monitoring Dashboard

#### المهمة 11: إنشاء Monitoring Service
```php
namespace App\Services\Monitoring;

class PlatformIntegrationMonitor
{
    public function getHealthStatus(): array
    {
        return [
            'integrations' => $this->checkIntegrations(),
            'webhooks' => $this->checkWebhooks(),
            'api_calls' => $this->checkApiCalls(),
            'rate_limits' => $this->checkRateLimits(),
            'circuit_breakers' => $this->checkCircuitBreakers(),
        ];
    }

    private function checkIntegrations(): array
    {
        $total = Integration::count();
        $active = Integration::where('is_active', true)->count();
        $expiring = Integration::where('token_expires_at', '<=', now()->addDays(7))
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiring,
            'health' => $expiring > 0 ? 'warning' : 'healthy',
        ];
    }

    private function checkWebhooks(): array
    {
        $stats = app(WebhookRetryService::class)->getRetryStats();

        return [
            'pending_retries' => $stats['pending_retries'],
            'dead_letter_queue' => $stats['dead_letter_queue_size'],
            'success_rate' => $stats['retry_success_rate'],
            'health' => $stats['retry_success_rate'] < 90 ? 'critical' : 'healthy',
        ];
    }

    private function checkApiCalls(): array
    {
        $last24Hours = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subDay())
            ->selectRaw('
                platform,
                COUNT(*) as total_calls,
                AVG(duration_ms) as avg_duration,
                SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN success = false THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('platform')
            ->get();

        return $last24Hours->mapWithKeys(function ($stat) {
            return [$stat->platform => [
                'total' => $stat->total_calls,
                'success_rate' => ($stat->successful / $stat->total_calls) * 100,
                'avg_duration_ms' => round($stat->avg_duration),
                'health' => $stat->failed > 10 ? 'warning' : 'healthy',
            ]];
        })->toArray();
    }
}
```

#### المهمة 12: إنشاء Health Check Endpoint
```php
// routes/api.php
Route::get('/health/integrations', function () {
    $monitor = app(PlatformIntegrationMonitor::class);
    $status = $monitor->getHealthStatus();

    $overallHealth = 'healthy';

    foreach ($status as $component => $data) {
        if (isset($data['health'])) {
            if ($data['health'] === 'critical') {
                $overallHealth = 'critical';
                break;
            } elseif ($data['health'] === 'warning' && $overallHealth === 'healthy') {
                $overallHealth = 'warning';
            }
        }
    }

    return response()->json([
        'status' => $overallHealth,
        'timestamp' => now()->toIso8601String(),
        'components' => $status,
    ], $overallHealth === 'critical' ? 503 : 200);
});
```

---

## 📊 معايير النجاح (Success Metrics)

### بعد الأسبوع الأول:
- ✅ Data sync يعمل لكل المنصات
- ✅ Webhooks idempotent
- ✅ Token refresh automated
- ✅ 0 duplicate webhook events

### بعد الأسبوع الثاني:
- ✅ 100% webhook async processing
- ✅ Circuit breaker يحمي من API failures
- ✅ Custom exceptions مع proper logging
- ✅ < 1% API call failure rate

### بعد الأسبوع الثالث:
- ✅ 80%+ test coverage للـ integrations
- ✅ Monitoring dashboard متاح
- ✅ Health checks automated
- ✅ < 5 دقائق mean time to detection للـ failures

---

## 🚀 خطوات التنفيذ

### Phase 1: Development Environment Setup
```bash
# Create feature branch
git checkout -b feature/platform-integrations-improvement

# Install dependencies
composer require guzzlehttp/guzzle
composer require predis/predis

# Create test database
createdb cmis_test

# Run migrations on test DB
php artisan migrate --database=testing
```

### Phase 2: Implementation
1. قم بتنفيذ المهام حسب الترتيب المذكور
2. اختبر كل feature بشكل منفصل
3. قم بعمل commit بعد كل مهمة منجزة

### Phase 3: Testing & Deployment
```bash
# Run all tests
vendor/bin/phpunit --testsuite=Feature

# Run integration tests
vendor/bin/phpunit tests/Feature/Platform/

# Check code coverage
vendor/bin/phpunit --coverage-html coverage/

# Deploy to staging
git push origin feature/platform-integrations-improvement
# Create PR for review
```

---

## 👥 توزيع المهام المقترح

### Developer 1 (Senior):
- Data Synchronization (يوم 1-2)
- Token Refresh (يوم 4-5)
- Circuit Breaker (يوم 8-9)

### Developer 2:
- Idempotency (يوم 3)
- Async Processing (يوم 6-7)
- Custom Exceptions (يوم 10)

### Developer 3 (QA/Testing):
- Integration Tests (يوم 11-13)
- Monitoring Dashboard (يوم 14-15)
- Documentation & Training

---

## 📝 ملاحظات مهمة

1. **لا تنسى RLS context** في كل database operations
2. **استخدم transactions** للـ critical operations
3. **أضف logging** في كل نقاط الفشل المحتملة
4. **راجع rate limits** قبل deployment
5. **اختبر مع production-like data** قبل الـ release

---

**آخر تحديث:** 2025-12-06
**الإصدار:** 1.0
**المؤلف:** Claude Code Agent