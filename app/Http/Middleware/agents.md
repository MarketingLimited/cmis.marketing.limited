# دليل الوكلاء - Middleware Layer (app/Http/Middleware/)

## 1. Purpose (الغرض)

طبقة Middleware توفر **Request/Response Filtering** و **Security**:
- **19 Middleware Classes**: معالجة وتصفية طلبات HTTP قبل الوصول للكونترولرز
- **Multi-Tenancy Context**: تطبيق RLS عبر `SetOrganizationContext`
- **Rate Limiting**: حماية من تجاوز الحدود (AI APIs, Platform APIs)
- **Security**: تحقق من الصلاحيات، CORS، headers أمنية
- **Monitoring**: تتبع الأداء والتسجيل

## 2. Owned Scope (النطاق المملوك)

### Middleware Organization (19 Files)

```
app/Http/Middleware/
├── SetOrganizationContext.php      # ✨ Core: RLS context (consolidated)
│   └── Replaces: SetRLSContext, SetDatabaseContext, SetOrgContextMiddleware
│
├── ValidateOrgAccess.php           # Organization access validation
├── CheckPermission.php             # Permission-based authorization
├── AdminOnly.php                   # Admin-only access
│
├── AiRateLimitMiddleware.php       # AI API rate limiting
├── ThrottleAI.php                  # AI request throttling
├── CheckAiQuotaMiddleware.php      # AI quota checking
│
├── ApiRateLimiting.php             # General API rate limiting
├── ThrottlePlatformRequests.php    # Platform API rate limiting
│
├── VerifyWebhookSignature.php      # Webhook signature verification
├── CheckPlatformFeatureEnabled.php # Feature flag checking
│
├── SecurityHeaders.php             # Security headers (CSP, HSTS, etc.)
├── AuditLogger.php                 # Audit logging
├── PerformanceMonitoring.php       # Performance tracking
│
├── CacheResponse.php               # Response caching
├── RefreshExpiredTokens.php        # Auto token refresh
│
├── SetRLSContext.php               # ⚠️ Legacy (use SetOrganizationContext)
├── SetDatabaseContext.php          # ⚠️ Legacy (use SetOrganizationContext)
└── SetOrgContextMiddleware.php     # ⚠️ Legacy (use SetOrganizationContext)
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Core Multi-Tenancy Middleware

#### SetOrganizationContext (`SetOrganizationContext.php`)
**Purpose**: Set organization context for Row-Level Security (RLS)

**Consolidates 3 legacy middleware**:
- ✅ Replaces `SetRLSContext`
- ✅ Replaces `SetDatabaseContext`
- ✅ Replaces `SetOrgContextMiddleware`

**How it works**:
1. Extract `org_id` from authenticated user
2. Call `cmis.init_transaction_context(user_id, org_id)`
3. Set PostgreSQL session variables for RLS
4. Verify context was set correctly
5. Clean up after request completes

**Key Features**:
- UUID validation
- Error handling with rollback
- Logging for debugging
- Context cleanup in `terminate()` method
- Request attribute injection

**Usage**:
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'org.context'])->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index']);
});

// Or in controller constructor
public function __construct()
{
    $this->middleware('org.context');
}
```

**Example Flow**:
```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user) {
        return $next($request);
    }

    $orgId = $user->current_org_id ?? $user->org_id;

    if (!$orgId) {
        return response()->json(['error' => 'No organization assigned'], 403);
    }

    // Set PostgreSQL session variable
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->id, $orgId]);

    // Verify
    $currentOrg = DB::selectOne("SELECT current_setting('app.current_org_id', true) as org_id");

    if ($currentOrg->org_id !== $orgId) {
        return response()->json(['error' => 'Context initialization failed'], 500);
    }

    // Add to request
    $request->merge(['_org_id' => $orgId]);
    $request->attributes->set('current_org_id', $orgId);

    // Process request
    $response = $next($request);

    // Cleanup
    DB::statement('SELECT cmis.clear_transaction_context()');

    return $response;
}

public function terminate(Request $request, Response $response): void
{
    // Final cleanup (even if handle() didn't execute)
    DB::statement('SELECT cmis.clear_transaction_context()');
}
```

### Security & Authorization

#### CheckPermission (`CheckPermission.php`)
**Purpose**: Check user permissions before accessing route

**Usage**:
```php
Route::middleware(['auth:sanctum', 'permission:manage-campaigns'])->group(function () {
    Route::post('/campaigns', [CampaignController::class, 'store']);
});
```

#### AdminOnly (`AdminOnly.php`)
**Purpose**: Restrict access to admin users only

**Usage**:
```php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
});
```

#### ValidateOrgAccess (`ValidateOrgAccess.php`)
**Purpose**: Validate user has access to requested organization

**Usage**:
```php
Route::middleware(['auth:sanctum', 'org.access'])->group(function () {
    Route::get('/orgs/{orgId}/campaigns', [CampaignController::class, 'index']);
});
```

### Rate Limiting

#### AiRateLimitMiddleware (`AiRateLimitMiddleware.php`)
**Purpose**: Rate limit AI API requests (Gemini)

**Limits**:
- 30 requests per minute
- 500 requests per hour

**Usage**:
```php
Route::middleware(['auth:sanctum', 'ai.rate-limit'])->group(function () {
    Route::post('/ai/generate-insights', [AIController::class, 'generateInsights']);
});
```

#### ThrottlePlatformRequests (`ThrottlePlatformRequests.php`)
**Purpose**: Rate limit platform API requests (Meta, Google, etc.)

**Limits** (per platform):
- Meta: 200 requests/hour
- Google: 2000 requests/day
- TikTok: 10000 requests/day

**Usage**:
```php
Route::middleware(['auth:sanctum', 'platform.throttle:meta'])->group(function () {
    Route::post('/integrations/meta/sync', [MetaController::class, 'sync']);
});
```

### Monitoring & Logging

#### AuditLogger (`AuditLogger.php`)
**Purpose**: Log sensitive operations for audit trail

**Logged Operations**:
- Campaign creation/deletion
- Budget changes
- Integration connections/disconnections
- User permission changes

**Usage**:
```php
Route::middleware(['auth:sanctum', 'audit.log'])->group(function () {
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy']);
});
```

#### PerformanceMonitoring (`PerformanceMonitoring.php`)
**Purpose**: Track request performance and log slow queries

**Usage**:
```php
Route::middleware(['auth:sanctum', 'perf.monitor'])->group(function () {
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'index']);
});
```

### Webhooks

#### VerifyWebhookSignature (`VerifyWebhookSignature.php`)
**Purpose**: Verify webhook signatures from platforms (Meta, Google, TikTok)

**Verification Methods**:
- HMAC SHA256 for Meta
- JWT for Google
- Custom signature for TikTok

**Usage**:
```php
Route::middleware(['webhook.verify:meta'])->group(function () {
    Route::post('/webhooks/meta', [WebhookController::class, 'handleMeta']);
});
```

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Middleware Stack
```
HTTP Request
   ↓
1. Laravel Default Middleware (TrustProxies, EncryptCookies, etc.)
   ↓
2. Sanctum Authentication ('auth:sanctum')
   ↓
3. SetOrganizationContext ('org.context')
   ↓
4. Rate Limiting ('ai.rate-limit', 'platform.throttle')
   ↓
5. Permission Checking ('permission:xxx', 'admin')
   ↓
6. Controller
   ↓
7. Response
   ↓
8. Middleware terminate() methods
```

### Internal Dependencies
- **PostgreSQL RLS**: `cmis.init_transaction_context()`
- **Cache (Redis)**: Rate limiting counters
- **Logging**: Laravel Log facade
- **Events**: Audit events

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Middleware Pattern

#### ✅ Standard Middleware Structure
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class YourMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // BEFORE request processing

        // 1. Validate/check something
        if (!$this->isValid($request)) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        // 2. Modify request
        $request->merge(['custom_attribute' => 'value']);

        // 3. Process request
        $response = $next($request);

        // AFTER request processing

        // 4. Modify response
        $response->headers->set('X-Custom-Header', 'value');

        return $response;
    }

    /**
     * Cleanup after response sent (optional)
     */
    public function terminate(Request $request, Response $response): void
    {
        // Cleanup logic here
    }

    private function isValid(Request $request): bool
    {
        // Validation logic
        return true;
    }
}
```

### Rate Limiting Pattern

```php
use Illuminate\Support\Facades\Cache;

class CustomRateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $key = "rate_limit:{$user->id}:api";

        // Get current count
        $count = Cache::get($key, 0);

        // Check limit
        if ($count >= 100) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Maximum 100 requests per hour'
            ], 429);
        }

        // Increment counter
        Cache::increment($key);

        // Set expiry if first request
        if ($count === 0) {
            Cache::put($key, 1, now()->addHour());
        }

        return $next($request);
    }
}
```

### Permission Checking Pattern

```php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => "You don't have permission: {$permission}"
            ], 403);
        }

        return $next($request);
    }
}
```

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Testing Middleware

```php
// tests/Feature/Middleware/SetOrganizationContextTest.php
namespace Tests\Feature\Middleware;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Organization;

class SetOrganizationContextTest extends TestCase
{
    public function test_sets_organization_context()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id]);

        $response = $this->actingAs($user)
                         ->getJson('/api/campaigns');

        $response->assertStatus(200);

        // Verify context was set
        $currentOrg = DB::selectOne(
            "SELECT current_setting('app.current_org_id', true) as org_id"
        );

        $this->assertEquals($org->id, $currentOrg->org_id);
    }

    public function test_rejects_user_without_organization()
    {
        $user = User::factory()->create(['org_id' => null]);

        $response = $this->actingAs($user)
                         ->getJson('/api/campaigns');

        $response->assertStatus(403)
                 ->assertJson([
                     'error' => 'No organization assigned'
                 ]);
    }
}
```

### Manual Testing

```bash
# Test with curl
curl -X GET http://localhost/api/campaigns \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -v  # Verbose to see headers
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Create New Middleware

```bash
# Create middleware
php artisan make:middleware YourMiddleware
```

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class YourMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Your logic here

        return $next($request);
    }
}
```

**Register in Kernel**:
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    // ...
    'your.middleware' => \App\Http\Middleware\YourMiddleware::class,
];
```

**Use in routes**:
```php
Route::middleware(['auth:sanctum', 'your.middleware'])->group(function () {
    // Routes
});
```

### Add Middleware to Route

```php
// Single route
Route::get('/campaigns', [CampaignController::class, 'index'])
     ->middleware(['auth:sanctum', 'org.context', 'permission:view-campaigns']);

// Route group
Route::middleware(['auth:sanctum', 'org.context'])->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
});
```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Mistakes

1. **Using Legacy Middleware**
   ```php
   ❌ Route::middleware(['auth:sanctum', 'rls.context'])  // Old

   ✅ Route::middleware(['auth:sanctum', 'org.context'])  // New (consolidated)
   ```

2. **Forgetting to Clean Up Context**
   ```php
   ❌ // No cleanup in terminate()

   ✅ public function terminate(Request $request, Response $response): void {
       DB::statement('SELECT cmis.clear_transaction_context()');
   }
   ```

3. **Not Handling Middleware Failures**
   ```php
   ❌ if (!$valid) {
       abort(403);  // No context for user
   }

   ✅ if (!$valid) {
       return response()->json(['error' => 'Forbidden', 'message' => 'Reason...'], 403);
   }
   ```

### 🎯 Best Practices

1. **Always Use SetOrganizationContext for RLS**
   - Consolidated middleware replaces 3 legacy ones
   - Handles all RLS context setup

2. **Rate Limit External APIs**
   - Use `ai.rate-limit` for Gemini API
   - Use `platform.throttle` for platform APIs

3. **Verify Webhook Signatures**
   - Always use `webhook.verify` for platform webhooks
   - Prevents unauthorized webhook calls

4. **Log Sensitive Operations**
   - Use `audit.log` for sensitive operations
   - Creates audit trail for compliance

### 📊 Statistics

- **Total Middleware**: 19 files
- **Active Middleware**: 16 (3 legacy)
- **Core Middleware**: `SetOrganizationContext` (most critical)
- **Security Middleware**: 6 (permissions, admin, validation)
- **Rate Limiting**: 5 (AI, platform, general)

### 🔗 Related Files

- **Kernel**: `app/Http/Kernel.php` - Middleware registration
- **Routes**: `routes/api.php`, `routes/web.php` - Middleware application
- **Controllers**: `app/Http/Controllers/` - Middleware consumers
- **Tests**: `tests/Feature/Middleware/` - Middleware tests

### 🚨 Critical Middleware

**MUST USE** on all authenticated routes:
1. `auth:sanctum` - Authentication
2. `org.context` - Multi-tenancy (RLS)

**SHOULD USE** based on route:
- `permission:xxx` - Permission-based authorization
- `ai.rate-limit` - AI API routes
- `platform.throttle` - Platform API routes
- `webhook.verify` - Webhook routes
- `audit.log` - Sensitive operations

### 🔧 Middleware Aliases (Kernel.php)

```php
protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'org.context' => \App\Http\Middleware\SetOrganizationContext::class,
    'permission' => \App\Http\Middleware\CheckPermission::class,
    'admin' => \App\Http\Middleware\AdminOnly::class,
    'ai.rate-limit' => \App\Http\Middleware\AiRateLimitMiddleware::class,
    'platform.throttle' => \App\Http\Middleware\ThrottlePlatformRequests::class,
    'webhook.verify' => \App\Http\Middleware\VerifyWebhookSignature::class,
    'audit.log' => \App\Http\Middleware\AuditLogger::class,
    // ...
];
```
