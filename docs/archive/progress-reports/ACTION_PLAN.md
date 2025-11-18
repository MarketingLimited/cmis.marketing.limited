# CMIS Implementation Action Plan

**Document Version:** 1.0
**Created:** 2025-11-16
**Status:** Ready for Execution
**Estimated Total Time:** 240 hours (10 weeks)
**Current Grade:** C+ (75/100)
**Target Grade:** A (95/100)

---

## Table of Contents

1. [Overview](#overview)
2. [Phase 0: Emergency Security Fixes](#phase-0-emergency-security-fixes)
3. [Phase 1: Data & Infrastructure](#phase-1-data--infrastructure)
4. [Phase 2: Core Features Completion](#phase-2-core-features-completion)
5. [Phase 3: GPT Interface Foundation](#phase-3-gpt-interface-foundation)
6. [Phase 4: GPT Interface Completion](#phase-4-gpt-interface-completion)
7. [Phase 5: Testing & Documentation](#phase-5-testing--documentation)
8. [Success Criteria](#success-criteria)
9. [Risk Management](#risk-management)

---

## Overview

This action plan addresses the critical gaps identified in the CMIS audit and provides a step-by-step implementation roadmap to bring the system to production readiness.

**Priority Levels:**
- 🔴 **CRITICAL** - Production blocker, must fix immediately
- 🟠 **HIGH** - Core functionality, required for launch
- 🟡 **MEDIUM** - Important feature, schedule for near-term
- 🟢 **LOW** - Enhancement, can be deferred

**Current Status:**
- ✅ Audit completed
- ✅ Issues identified and prioritized
- 🔄 Implementation in progress
- ⏳ Testing pending
- ⏳ Deployment pending

---

## Phase 0: Emergency Security Fixes

**Timeline:** Week 1 (Days 1-2)
**Effort:** 15 hours
**Priority:** 🔴 CRITICAL

### Task 0.1: Fix Login Password Verification (2h)

**File:** `app/Http/Controllers/Auth/AuthController.php`
**Priority:** 🔴 CRITICAL
**Status:** ⏳ Pending

**Current Issue:**
```php
public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();
    $token = $user->createToken('api-token')->plainTextToken;
    return response()->json(['token' => $token]);
}
```

**Required Changes:**
```php
public function login(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ]);

    $user = User::where('email', $validated['email'])->first();

    if (!$user || !Hash::check($validated['password'], $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    // Check if user is active
    if ($user->status !== 'active') {
        return response()->json([
            'message' => 'Account is not active'
        ], 403);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user->only(['id', 'name', 'email', 'role'])
    ]);
}
```

**Test Case:**
```php
// tests/Feature/Auth/LoginTest.php
public function test_login_requires_correct_password()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password')
    ]);

    // Test with wrong password
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password'
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Invalid credentials']);

    // Test with correct password
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'correct-password'
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['token', 'user']);
}
```

**Success Criteria:**
- [ ] Password verification implemented with Hash::check()
- [ ] Returns 401 for invalid credentials
- [ ] Returns 403 for inactive accounts
- [ ] Test passes successfully
- [ ] No existing users can login with wrong passwords

---

### Task 0.2: Enable Token Expiration (4h)

**File:** `config/sanctum.php`
**Priority:** 🔴 CRITICAL
**Status:** ⏳ Pending

**Current Issue:**
```php
'expiration' => null, // Tokens never expire
```

**Required Changes:**

**Step 1:** Update config/sanctum.php
```php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 10080), // 7 days in minutes
```

**Step 2:** Add to .env
```env
SANCTUM_TOKEN_EXPIRATION=10080
```

**Step 3:** Update .env.example
```env
# Sanctum token expiration in minutes (default: 10080 = 7 days)
SANCTUM_TOKEN_EXPIRATION=10080
```

**Step 4:** Create token refresh endpoint
```php
// app/Http/Controllers/Auth/AuthController.php

public function refresh(Request $request)
{
    $user = $request->user();

    // Revoke current token
    $request->user()->currentAccessToken()->delete();

    // Create new token
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'expires_in' => config('sanctum.expiration')
    ]);
}
```

**Step 5:** Add route
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});
```

**Test Case:**
```php
// tests/Feature/Auth/TokenExpirationTest.php
public function test_tokens_expire_after_configured_time()
{
    config(['sanctum.expiration' => 1]); // 1 minute for testing

    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    // Token should work initially
    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/user');
    $response->assertStatus(200);

    // Wait for expiration
    $this->travel(2)->minutes();

    // Token should be expired
    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/user');
    $response->assertStatus(401);
}

public function test_token_refresh_works()
{
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/auth/refresh');

    $response->assertStatus(200);
    $response->assertJsonStructure(['token', 'expires_in']);

    // Old token should no longer work
    $oldTokenResponse = $this->withToken($token)
        ->getJson('/api/user');
    $oldTokenResponse->assertStatus(401);
}
```

**Success Criteria:**
- [ ] Sanctum expiration configured
- [ ] Tokens expire after 7 days
- [ ] Refresh endpoint implemented
- [ ] Tests pass successfully
- [ ] Frontend updated to handle token refresh

---

### Task 0.3: Enable Row-Level Security (RLS) (3h)

**Files:** Multiple database migration and model files
**Priority:** 🔴 CRITICAL
**Status:** ⏳ Pending

**Current Issue:**
RLS policies are defined in schema but not enabled on tables.

**Required Changes:**

**Step 1:** Create migration
```php
// database/migrations/2025_11_16_enable_rls.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $tables = [
            'cmis.orgs',
            'cmis.org_markets',
            'cmis.campaigns',
            'cmis.content_plans',
            'cmis.content_items',
            'cmis.creative_assets',
            'cmis.copy_components',
            'cmis.knowledge_base',
            'cmis.knowledge_embeddings',
            'cmis.ad_accounts',
            'cmis.ad_campaigns',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.compliance_rules',
            'cmis.compliance_audits',
            'cmis.ab_tests',
            'cmis.ab_test_variations',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        }

        // Create RLS function to get current org_id from session
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.current_org_id()
            RETURNS UUID AS $$
            BEGIN
                RETURN current_setting('app.current_org_id', true)::UUID;
            EXCEPTION
                WHEN OTHERS THEN
                    RETURN NULL;
            END;
            $$ LANGUAGE plpgsql STABLE;
        ");

        // Apply policies (already defined in schema.sql, just ensure they exist)
        foreach ($tables as $table) {
            $tableName = explode('.', $table)[1];
            DB::statement("
                DROP POLICY IF EXISTS {$tableName}_tenant_isolation ON {$table};
                CREATE POLICY {$tableName}_tenant_isolation ON {$table}
                    USING (org_id = cmis.current_org_id());
            ");
        }
    }

    public function down()
    {
        $tables = [
            'cmis.orgs',
            'cmis.org_markets',
            'cmis.campaigns',
            'cmis.content_plans',
            'cmis.content_items',
            'cmis.creative_assets',
            'cmis.copy_components',
            'cmis.knowledge_base',
            'cmis.knowledge_embeddings',
            'cmis.ad_accounts',
            'cmis.ad_campaigns',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.compliance_rules',
            'cmis.compliance_audits',
            'cmis.ab_tests',
            'cmis.ab_test_variations',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }

        DB::statement("DROP FUNCTION IF EXISTS cmis.current_org_id()");
    }
};
```

**Step 2:** Update database service provider
```php
// app/Providers/DatabaseServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class DatabaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Set org_id for RLS on every query
        DB::listen(function ($query) {
            if (auth()->check() && auth()->user()->current_org_id) {
                DB::statement(
                    "SET LOCAL app.current_org_id = ?",
                    [auth()->user()->current_org_id]
                );
            }
        });
    }
}
```

**Step 3:** Register provider in config/app.php
```php
'providers' => [
    // ...
    App\Providers\DatabaseServiceProvider::class,
],
```

**Test Case:**
```php
// tests/Feature/Security/RLSTest.php
public function test_rls_prevents_cross_tenant_data_access()
{
    $org1 = Org::factory()->create();
    $org2 = Org::factory()->create();

    $user1 = User::factory()->create(['current_org_id' => $org1->id]);
    $user2 = User::factory()->create(['current_org_id' => $org2->id]);

    $campaign1 = Campaign::factory()->create(['org_id' => $org1->id]);
    $campaign2 = Campaign::factory()->create(['org_id' => $org2->id]);

    // User 1 should only see their org's campaigns
    $this->actingAs($user1);
    $campaigns = Campaign::all();

    $this->assertCount(1, $campaigns);
    $this->assertEquals($campaign1->id, $campaigns->first()->id);

    // User 2 should only see their org's campaigns
    $this->actingAs($user2);
    $campaigns = Campaign::all();

    $this->assertCount(1, $campaigns);
    $this->assertEquals($campaign2->id, $campaigns->first()->id);
}
```

**Success Criteria:**
- [ ] RLS enabled on all tenant-scoped tables
- [ ] current_org_id function created
- [ ] Policies applied and tested
- [ ] DatabaseServiceProvider sets org_id on every query
- [ ] Tests confirm data isolation
- [ ] No cross-tenant data leakage

---

### Task 0.4: Add Rate Limiting to AI Routes (2h)

**File:** `routes/api.php`, `app/Http/Kernel.php`
**Priority:** 🔴 CRITICAL
**Status:** ⏳ Pending

**Current Issue:**
AI endpoints have no rate limiting, exposing to DDoS and cost overruns.

**Required Changes:**

**Step 1:** Create AI throttle middleware
```php
// app/Http/Middleware/ThrottleAI.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAI
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = config('services.ai.rate_limit', 10); // 10 per minute

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many AI requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($key)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->limiter->hit($key, 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->remaining($key, $maxAttempts),
        ]);
    }

    protected function resolveRequestSignature($request)
    {
        return 'ai-throttle:' . ($request->user()?->id ?? $request->ip());
    }
}
```

**Step 2:** Register middleware
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    // ...
    'throttle.ai' => \App\Http\Middleware\ThrottleAI::class,
];
```

**Step 3:** Apply to AI routes
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle.ai'])->prefix('ai')->group(function () {
    Route::post('/generate', [AIController::class, 'generate']);
    Route::post('/captions', [AIController::class, 'generateCaptions']);
    Route::post('/hashtags', [AIController::class, 'generateHashtags']);
    Route::post('/translate', [AIController::class, 'translate']);
    Route::post('/embeddings', [AIController::class, 'generateEmbeddings']);
});
```

**Step 4:** Add configuration
```php
// config/services.php
'ai' => [
    'rate_limit' => env('AI_RATE_LIMIT', 10), // requests per minute
    'openai_key' => env('OPENAI_API_KEY'),
],
```

**Test Case:**
```php
// tests/Feature/AI/RateLimitTest.php
public function test_ai_endpoints_are_rate_limited()
{
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $maxAttempts = config('services.ai.rate_limit');

    // Make requests up to the limit
    for ($i = 0; $i < $maxAttempts; $i++) {
        $response = $this->postJson('/api/ai/generate', [
            'prompt' => 'test prompt'
        ]);
        $response->assertStatus(200);
    }

    // Next request should be rate limited
    $response = $this->postJson('/api/ai/generate', [
        'prompt' => 'test prompt'
    ]);

    $response->assertStatus(429);
    $response->assertJsonStructure(['message', 'retry_after']);
}
```

**Success Criteria:**
- [ ] ThrottleAI middleware created
- [ ] Applied to all AI routes
- [ ] Rate limit configurable via .env
- [ ] Headers show remaining requests
- [ ] Tests confirm rate limiting works
- [ ] Monitoring alerts configured

---

### Task 0.5: Add Security Headers Middleware (2h)

**Files:** `app/Http/Middleware/SecurityHeaders.php`, `app/Http/Kernel.php`
**Priority:** 🟠 HIGH
**Status:** ⏳ Pending

**Required Changes:**

**Step 1:** Create middleware
```php
// app/Http/Middleware/SecurityHeaders.php
namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

**Step 2:** Register in Kernel
```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\SecurityHeaders::class,
];
```

**Success Criteria:**
- [ ] Security headers middleware created
- [ ] Applied globally
- [ ] HSTS enabled in production
- [ ] Verified with security scanner

---

### Task 0.6: Update Documentation (2h)

**Files:** `docs/SECURITY.md`, `docs/API_AUTH.md`
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Required Changes:**
- Document new authentication flow
- Document token refresh process
- Document rate limiting
- Document RLS implementation
- Update API examples

**Success Criteria:**
- [ ] Documentation updated
- [ ] Examples tested
- [ ] Team reviewed

---

## Phase 1: Data & Infrastructure

**Timeline:** Week 1 (Days 3-5) + Week 2
**Effort:** 24 hours
**Priority:** 🟠 HIGH

### Task 1.1: Fix UUID/BigInt Conflict (8h)

**Files:** Multiple migrations, models
**Priority:** 🟠 HIGH
**Status:** ⏳ Pending

**Current Issue:**
`users` table uses `bigint` for ID while all related tables expect `UUID`.

**Required Changes:**

**Step 1:** Create backup
```bash
pg_dump -h 127.0.0.1 -U begin -d cmis > backup_before_uuid_migration.sql
```

**Step 2:** Create migration
```php
// database/migrations/2025_11_16_migrate_users_to_uuid.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        DB::beginTransaction();

        try {
            // Step 1: Add new UUID column
            DB::statement('ALTER TABLE cmis.users ADD COLUMN id_uuid UUID DEFAULT gen_random_uuid()');

            // Step 2: Create mapping table
            DB::statement('
                CREATE TEMP TABLE user_id_mapping AS
                SELECT id as old_id, id_uuid as new_id FROM cmis.users
            ');

            // Step 3: Update all foreign key references
            $fkTables = [
                ['table' => 'cmis.user_orgs', 'column' => 'user_id'],
                ['table' => 'cmis.user_permissions', 'column' => 'user_id'],
                ['table' => 'cmis.user_activity', 'column' => 'user_id'],
                ['table' => 'cmis.api_tokens', 'column' => 'user_id'],
                ['table' => 'cmis.knowledge_base', 'column' => 'created_by'],
                ['table' => 'cmis.campaigns', 'column' => 'created_by'],
                // Add all tables with user_id foreign keys
            ];

            foreach ($fkTables as $fk) {
                // Add new UUID column
                DB::statement("ALTER TABLE {$fk['table']} ADD COLUMN {$fk['column']}_uuid UUID");

                // Drop existing foreign key constraint
                $constraint = DB::select("
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_schema = 'cmis'
                    AND table_name = ?
                    AND constraint_type = 'FOREIGN KEY'
                ", [explode('.', $fk['table'])[1]]);

                if (!empty($constraint)) {
                    DB::statement("ALTER TABLE {$fk['table']} DROP CONSTRAINT {$constraint[0]->constraint_name}");
                }

                // Copy UUIDs using mapping
                DB::statement("
                    UPDATE {$fk['table']} t
                    SET {$fk['column']}_uuid = m.new_id
                    FROM user_id_mapping m
                    WHERE t.{$fk['column']} = m.old_id
                ");
            }

            // Step 4: Drop old columns and rename new ones
            DB::statement('ALTER TABLE cmis.users DROP COLUMN id CASCADE');
            DB::statement('ALTER TABLE cmis.users RENAME COLUMN id_uuid TO id');
            DB::statement('ALTER TABLE cmis.users ADD PRIMARY KEY (id)');

            foreach ($fkTables as $fk) {
                DB::statement("ALTER TABLE {$fk['table']} DROP COLUMN {$fk['column']}");
                DB::statement("ALTER TABLE {$fk['table']} RENAME COLUMN {$fk['column']}_uuid TO {$fk['column']}");
                DB::statement("ALTER TABLE {$fk['table']} ADD CONSTRAINT fk_{$fk['column']} FOREIGN KEY ({$fk['column']}) REFERENCES cmis.users(id) ON DELETE CASCADE");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function down()
    {
        // This migration is not reversible
        throw new \Exception('Cannot reverse UUID migration. Restore from backup.');
    }
};
```

**Step 3:** Update User model
```php
// app/Models/Core/User.php
class User extends Authenticatable
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
```

**Test Case:**
```php
// tests/Feature/Data/UUIDMigrationTest.php
public function test_users_table_uses_uuid()
{
    $user = User::factory()->create();

    $this->assertTrue(Str::isUuid($user->id));
    $this->assertIsString($user->id);
}

public function test_user_relationships_work_with_uuid()
{
    $user = User::factory()->create();
    $org = Org::factory()->create();

    $userOrg = UserOrg::create([
        'user_id' => $user->id,
        'org_id' => $org->id,
        'role' => 'admin'
    ]);

    $this->assertEquals($user->id, $userOrg->user_id);
    $this->assertTrue(Str::isUuid($userOrg->user_id));
}
```

**Success Criteria:**
- [ ] Backup created
- [ ] Migration tested on development database
- [ ] All foreign keys updated
- [ ] No data loss
- [ ] Tests pass
- [ ] Sanctum tokens still work

---

### Task 1.2: Add Database Indexes (4h)

**Files:** New migration
**Priority:** 🟠 HIGH
**Status:** ⏳ Pending

**Required Changes:**

```php
// database/migrations/2025_11_16_add_performance_indexes.php
return new class extends Migration
{
    public function up()
    {
        // Campaigns
        DB::statement('CREATE INDEX CONCURRENTLY idx_campaigns_org_status ON cmis.campaigns(org_id, status)');
        DB::statement('CREATE INDEX CONCURRENTLY idx_campaigns_dates ON cmis.campaigns(start_date, end_date)');

        // Content Plans
        DB::statement('CREATE INDEX CONCURRENTLY idx_content_plans_campaign ON cmis.content_plans(campaign_id, status)');

        // Knowledge Base
        DB::statement('CREATE INDEX CONCURRENTLY idx_knowledge_org_type ON cmis.knowledge_base(org_id, content_type)');
        DB::statement('CREATE INDEX CONCURRENTLY idx_knowledge_created ON cmis.knowledge_base(created_at DESC)');

        // Embeddings (for similarity search)
        DB::statement('CREATE INDEX CONCURRENTLY idx_embeddings_vector ON cmis.knowledge_embeddings USING ivfflat (embedding vector_cosine_ops)');

        // Ad Metrics
        DB::statement('CREATE INDEX CONCURRENTLY idx_ad_metrics_date ON cmis.ad_metrics(recorded_at DESC)');
        DB::statement('CREATE INDEX CONCURRENTLY idx_ad_metrics_entity ON cmis.ad_metrics(ad_entity_id, recorded_at)');

        // Audit logs
        DB::statement('CREATE INDEX CONCURRENTLY idx_audit_user_date ON cmis_audit.activity_logs(user_id, created_at DESC)');
        DB::statement('CREATE INDEX CONCURRENTLY idx_audit_action ON cmis_audit.activity_logs(action, created_at DESC)');
    }

    public function down()
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_campaigns_org_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_campaigns_dates');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_content_plans_campaign');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_knowledge_org_type');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_knowledge_created');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_embeddings_vector');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_ad_metrics_date');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_ad_metrics_entity');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_audit_user_date');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS cmis.idx_audit_action');
    }
};
```

**Test Performance:**
```php
// tests/Performance/QueryPerformanceTest.php
public function test_campaign_queries_are_fast()
{
    // Create test data
    $org = Org::factory()->create();
    Campaign::factory()->count(1000)->create(['org_id' => $org->id]);

    $start = microtime(true);
    $campaigns = Campaign::where('org_id', $org->id)
        ->where('status', 'active')
        ->get();
    $duration = microtime(true) - $start;

    $this->assertLessThan(0.1, $duration, 'Query took more than 100ms');
}
```

**Success Criteria:**
- [ ] Indexes created without blocking
- [ ] Query performance improved >50%
- [ ] No impact on write performance
- [ ] Performance tests pass

---

### Task 1.3: Implement Redis Caching (6h)

**Files:** Multiple service classes
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Required Changes:**

**Step 1:** Configure Redis
```env
# .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Step 2:** Create cache service
```php
// app/Services/CacheService.php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 3600;    // 1 hour
    const TTL_LONG = 86400;     // 24 hours

    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function invalidate(string $pattern): void
    {
        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    public function tags(array $tags)
    {
        return Cache::tags($tags);
    }
}
```

**Step 3:** Apply caching to campaigns
```php
// app/Services/CampaignService.php
public function getActiveCampaigns($orgId)
{
    return $this->cache->remember(
        "org:{$orgId}:campaigns:active",
        CacheService::TTL_MEDIUM,
        fn() => Campaign::where('org_id', $orgId)
            ->where('status', 'active')
            ->with(['contentPlans', 'adAccounts'])
            ->get()
    );
}

public function updateCampaign($campaignId, $data)
{
    $campaign = Campaign::findOrFail($campaignId);
    $campaign->update($data);

    // Invalidate cache
    $this->cache->invalidate("org:{$campaign->org_id}:campaigns:*");

    return $campaign;
}
```

**Test Case:**
```php
// tests/Feature/Cache/CacheTest.php
public function test_campaigns_are_cached()
{
    $org = Org::factory()->create();
    Campaign::factory()->count(10)->create(['org_id' => $org->id]);

    // First call - should hit database
    $start = microtime(true);
    $campaigns1 = $this->campaignService->getActiveCampaigns($org->id);
    $duration1 = microtime(true) - $start;

    // Second call - should hit cache
    $start = microtime(true);
    $campaigns2 = $this->campaignService->getActiveCampaigns($org->id);
    $duration2 = microtime(true) - $start;

    $this->assertLessThan($duration1, $duration2);
    $this->assertEquals($campaigns1->count(), $campaigns2->count());
}

public function test_cache_invalidation_works()
{
    $campaign = Campaign::factory()->create();

    // Cache it
    $this->campaignService->getActiveCampaigns($campaign->org_id);

    // Update it
    $this->campaignService->updateCampaign($campaign->id, ['name' => 'Updated']);

    // Should fetch fresh data
    $campaigns = $this->campaignService->getActiveCampaigns($campaign->org_id);
    $this->assertEquals('Updated', $campaigns->first()->name);
}
```

**Success Criteria:**
- [ ] Redis configured
- [ ] CacheService implemented
- [ ] Applied to high-traffic endpoints
- [ ] Cache invalidation working
- [ ] Tests pass
- [ ] Response times improved

---

### Task 1.4: Add Queue System (6h)

**Files:** Queue config, job classes
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Required Changes:**

**Step 1:** Configure queues
```env
# .env
QUEUE_CONNECTION=redis
```

**Step 2:** Create jobs
```php
// app/Jobs/GenerateAIContent.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAIContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public string $contentPlanId,
        public string $prompt,
        public string $type
    ) {}

    public function handle(AIService $aiService)
    {
        $contentPlan = ContentPlan::findOrFail($this->contentPlanId);

        $result = $aiService->generate($this->prompt, $this->type);

        $contentPlan->update([
            'generated_content' => $result,
            'status' => 'generated'
        ]);
    }

    public function failed(\Throwable $exception)
    {
        // Handle failure
        $contentPlan = ContentPlan::find($this->contentPlanId);
        $contentPlan?->update(['status' => 'failed']);

        Log::error('AI generation failed', [
            'content_plan_id' => $this->contentPlanId,
            'error' => $exception->getMessage()
        ]);
    }
}
```

**Step 3:** Dispatch jobs
```php
// app/Services/ContentPlanService.php
public function generateContent($contentPlanId)
{
    $contentPlan = ContentPlan::findOrFail($contentPlanId);

    $contentPlan->update(['status' => 'generating']);

    GenerateAIContent::dispatch(
        $contentPlanId,
        $contentPlan->prompt,
        $contentPlan->content_type
    );

    return ['message' => 'Content generation started'];
}
```

**Step 4:** Start queue worker
```bash
php artisan queue:work --queue=default,ai,exports --tries=3
```

**Test Case:**
```php
// tests/Feature/Queue/JobTest.php
public function test_ai_generation_is_queued()
{
    Queue::fake();

    $contentPlan = ContentPlan::factory()->create();

    $this->contentPlanService->generateContent($contentPlan->id);

    Queue::assertPushed(GenerateAIContent::class, function ($job) use ($contentPlan) {
        return $job->contentPlanId === $contentPlan->id;
    });
}
```

**Success Criteria:**
- [ ] Queue configured
- [ ] Jobs created for AI tasks
- [ ] Worker running in production
- [ ] Failed job handling
- [ ] Tests pass
- [ ] Monitoring in place

---

## Phase 2: Core Features Completion

**Timeline:** Week 3-4
**Effort:** 79 hours
**Priority:** 🟠 HIGH

### Task 2.1: Implement Content Plan CRUD (33h)

**Files:** Controllers, views, tests
**Priority:** 🟠 HIGH
**Status:** ⏳ Pending

**Required Implementation:**

**Step 1:** Create controller
```php
// app/Http/Controllers/Creative/ContentPlanController.php
namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\Creative\ContentPlan;
use App\Services\ContentPlanService;
use Illuminate\Http\Request;

class ContentPlanController extends Controller
{
    public function __construct(
        private ContentPlanService $service
    ) {}

    public function index(Request $request)
    {
        $plans = ContentPlan::query()
            ->where('org_id', $request->user()->current_org_id)
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with(['campaign', 'contentItems'])
            ->latest()
            ->paginate(20);

        return view('creative.content-plans.index', compact('plans'));
    }

    public function create(Request $request)
    {
        $campaigns = Campaign::where('org_id', $request->user()->current_org_id)->get();
        return view('creative.content-plans.create', compact('campaigns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:social_post,blog_article,ad_copy,email,video_script',
            'target_platforms' => 'required|array',
            'target_platforms.*' => 'in:facebook,instagram,twitter,linkedin,youtube,tiktok',
            'tone' => 'nullable|string|max:100',
            'key_messages' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $plan = $this->service->create($validated);

        return redirect()
            ->route('content-plans.show', $plan)
            ->with('success', 'Content plan created successfully');
    }

    public function show(ContentPlan $contentPlan)
    {
        $this->authorize('view', $contentPlan);

        $contentPlan->load(['campaign', 'contentItems', 'creativeAssets']);

        return view('creative.content-plans.show', compact('contentPlan'));
    }

    public function edit(ContentPlan $contentPlan)
    {
        $this->authorize('update', $contentPlan);

        $campaigns = Campaign::where('org_id', auth()->user()->current_org_id)->get();

        return view('creative.content-plans.edit', compact('contentPlan', 'campaigns'));
    }

    public function update(Request $request, ContentPlan $contentPlan)
    {
        $this->authorize('update', $contentPlan);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:social_post,blog_article,ad_copy,email,video_script',
            'target_platforms' => 'required|array',
            'tone' => 'nullable|string|max:100',
            'key_messages' => 'nullable|array',
            'metadata' => 'nullable|array',
            'status' => 'in:draft,scheduled,published,archived',
        ]);

        $plan = $this->service->update($contentPlan, $validated);

        return redirect()
            ->route('content-plans.show', $plan)
            ->with('success', 'Content plan updated successfully');
    }

    public function destroy(ContentPlan $contentPlan)
    {
        $this->authorize('delete', $contentPlan);

        $this->service->delete($contentPlan);

        return redirect()
            ->route('content-plans.index')
            ->with('success', 'Content plan deleted successfully');
    }

    // API endpoints
    public function apiIndex(Request $request)
    {
        $plans = ContentPlan::query()
            ->where('org_id', $request->user()->current_org_id)
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with(['campaign', 'contentItems'])
            ->latest()
            ->paginate(20);

        return response()->json($plans);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:social_post,blog_article,ad_copy,email,video_script',
            'target_platforms' => 'required|array',
            'tone' => 'nullable|string|max:100',
            'key_messages' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $plan = $this->service->create($validated);

        return response()->json($plan, 201);
    }

    public function generateContent(Request $request, ContentPlan $contentPlan)
    {
        $this->authorize('update', $contentPlan);

        $validated = $request->validate([
            'prompt' => 'nullable|string',
            'options' => 'nullable|array',
        ]);

        $result = $this->service->generateContent(
            $contentPlan,
            $validated['prompt'] ?? null,
            $validated['options'] ?? []
        );

        return response()->json($result);
    }
}
```

**Step 2:** Create service
```php
// app/Services/ContentPlanService.php
namespace App\Services;

use App\Models\Creative\ContentPlan;
use App\Jobs\GenerateAIContent;

class ContentPlanService
{
    public function create(array $data): ContentPlan
    {
        $data['org_id'] = auth()->user()->current_org_id;
        $data['status'] = 'draft';

        return ContentPlan::create($data);
    }

    public function update(ContentPlan $plan, array $data): ContentPlan
    {
        $plan->update($data);
        return $plan->fresh();
    }

    public function delete(ContentPlan $plan): bool
    {
        return $plan->delete();
    }

    public function generateContent(ContentPlan $plan, ?string $prompt = null, array $options = [])
    {
        $prompt = $prompt ?? $this->buildPrompt($plan);

        $plan->update(['status' => 'generating']);

        GenerateAIContent::dispatch($plan->id, $prompt, $plan->content_type);

        return [
            'message' => 'Content generation started',
            'content_plan_id' => $plan->id,
            'status' => 'generating'
        ];
    }

    private function buildPrompt(ContentPlan $plan): string
    {
        $prompt = "Generate {$plan->content_type} content for:\n\n";
        $prompt .= "Campaign: {$plan->campaign->name}\n";
        $prompt .= "Description: {$plan->description}\n";
        $prompt .= "Platforms: " . implode(', ', $plan->target_platforms) . "\n";

        if ($plan->tone) {
            $prompt .= "Tone: {$plan->tone}\n";
        }

        if ($plan->key_messages) {
            $prompt .= "Key Messages:\n";
            foreach ($plan->key_messages as $message) {
                $prompt .= "- {$message}\n";
            }
        }

        return $prompt;
    }
}
```

**Step 3:** Create views
```blade
{{-- resources/views/creative/content-plans/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Content Plans</h1>
        <a href="{{ route('content-plans.create') }}" class="btn btn-primary">
            Create Content Plan
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($plans as $plan)
                <tr>
                    <td class="px-6 py-4">
                        <a href="{{ route('content-plans.show', $plan) }}" class="text-blue-600 hover:text-blue-900">
                            {{ $plan->name }}
                        </a>
                    </td>
                    <td class="px-6 py-4">{{ $plan->campaign->name }}</td>
                    <td class="px-6 py-4">
                        <span class="badge">{{ $plan->content_type }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="badge badge-{{ $plan->status }}">{{ $plan->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $plan->created_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('content-plans.edit', $plan) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('content-plans.destroy', $plan) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No content plans found. <a href="{{ route('content-plans.create') }}" class="text-blue-600">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $plans->links() }}
    </div>
</div>
@endsection
```

**Step 4:** Add routes
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::resource('content-plans', ContentPlanController::class);
    Route::post('content-plans/{contentPlan}/generate', [ContentPlanController::class, 'generateContent'])
        ->name('content-plans.generate');
});

// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/content-plans', [ContentPlanController::class, 'apiIndex']);
    Route::post('/content-plans', [ContentPlanController::class, 'apiStore']);
    Route::get('/content-plans/{contentPlan}', [ContentPlanController::class, 'apiShow']);
    Route::put('/content-plans/{contentPlan}', [ContentPlanController::class, 'apiUpdate']);
    Route::delete('/content-plans/{contentPlan}', [ContentPlanController::class, 'apiDestroy']);
    Route::post('/content-plans/{contentPlan}/generate', [ContentPlanController::class, 'generateContent']);
});
```

**Test Cases:**
```php
// tests/Feature/ContentPlan/ContentPlanTest.php
public function test_can_create_content_plan()
{
    $user = User::factory()->create();
    $org = Org::factory()->create();
    $user->update(['current_org_id' => $org->id]);

    $campaign = Campaign::factory()->create(['org_id' => $org->id]);

    $this->actingAs($user)
        ->post('/content-plans', [
            'campaign_id' => $campaign->id,
            'name' => 'Test Content Plan',
            'description' => 'Test description',
            'content_type' => 'social_post',
            'target_platforms' => ['facebook', 'instagram'],
            'tone' => 'professional',
            'key_messages' => ['Message 1', 'Message 2'],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('cmis.content_plans', [
        'name' => 'Test Content Plan',
        'org_id' => $org->id,
    ]);
}

public function test_can_list_content_plans()
{
    $user = User::factory()->create();
    $org = Org::factory()->create();
    $user->update(['current_org_id' => $org->id]);

    ContentPlan::factory()->count(5)->create(['org_id' => $org->id]);

    $response = $this->actingAs($user)
        ->get('/content-plans');

    $response->assertStatus(200);
    $response->assertSee('Content Plans');
}

public function test_can_generate_content()
{
    Queue::fake();

    $user = User::factory()->create();
    $contentPlan = ContentPlan::factory()->create();
    $user->update(['current_org_id' => $contentPlan->org_id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/content-plans/{$contentPlan->id}/generate", [
            'prompt' => 'Generate engaging content'
        ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Content generation started']);

    Queue::assertPushed(GenerateAIContent::class);
}

public function test_cannot_access_other_org_content_plans()
{
    $user1 = User::factory()->create();
    $org1 = Org::factory()->create();
    $user1->update(['current_org_id' => $org1->id]);

    $org2 = Org::factory()->create();
    $contentPlan = ContentPlan::factory()->create(['org_id' => $org2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/content-plans/{$contentPlan->id}");

    $response->assertStatus(403);
}
```

**Success Criteria:**
- [ ] Full CRUD implemented (Web + API)
- [ ] Views created and styled
- [ ] Service layer completed
- [ ] Authorization policies working
- [ ] Tests pass (100% coverage)
- [ ] Integration with AI generation
- [ ] RLS enforced

**Time Estimate:** 33 hours

---

### Task 2.2: Implement org_markets CRUD (18h)

**Similar implementation to ContentPlan above**

**Success Criteria:**
- [ ] Full CRUD implemented
- [ ] Market configuration working
- [ ] Tests pass
- [ ] Documentation updated

**Time Estimate:** 18 hours

---

### Task 2.3: Complete Compliance UI (16h)

**Files:** Compliance controllers, views
**Priority:** 🟠 HIGH
**Status:** ⏳ Pending

**Implementation details similar to above...**

**Success Criteria:**
- [ ] Compliance rules CRUD
- [ ] Audit UI completed
- [ ] Real-time validation
- [ ] Tests pass

**Time Estimate:** 16 hours

---

### Task 2.4: Fix Frontend-API Binding (12h)

**Files:** Multiple view files, JavaScript
**Priority:** 🟠 HIGH
**Status:** ⏳ Pending

**Required Changes:**

**Step 1:** Audit current API usage
```bash
grep -r "fetch(" resources/views/
grep -r "axios" resources/views/
grep -r "/api/" resources/views/
```

**Step 2:** Create JavaScript API client
```javascript
// resources/js/api/client.js
class APIClient {
    constructor() {
        this.baseURL = '/api';
        this.token = document.querySelector('meta[name="api-token"]')?.content;
    }

    async request(method, endpoint, data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${this.token}`
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(this.baseURL + endpoint, options);

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }

        return response.json();
    }

    get(endpoint) {
        return this.request('GET', endpoint);
    }

    post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }

    put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    delete(endpoint) {
        return this.request('DELETE', endpoint);
    }
}

export const api = new APIClient();
```

**Step 3:** Update views to use API
```javascript
// Before (hardcoded routes)
fetch('/campaigns/' + campaignId + '/update', {...})

// After (using API client)
import { api } from '@/api/client';
api.put(`/campaigns/${campaignId}`, data)
```

**Success Criteria:**
- [ ] API client created
- [ ] All views use consistent API endpoints
- [ ] Error handling standardized
- [ ] No hardcoded routes
- [ ] Tests pass

**Time Estimate:** 12 hours

---

## Phase 3: GPT Interface Foundation

**Timeline:** Week 5-6
**Effort:** 35 hours
**Priority:** 🟡 MEDIUM

### Task 3.1: Design GPT Actions Schema (8h)

**File:** `gpt-actions.yaml`
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Required Implementation:**

```yaml
# gpt-actions.yaml
openapi: 3.1.0
info:
  title: CMIS GPT Actions API
  description: API for interacting with the Cognitive Marketing Intelligence System
  version: 1.0.0
servers:
  - url: https://cmis.kazaaz.com/api/gpt
    description: Production server

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

security:
  - bearerAuth: []

paths:
  /campaigns:
    get:
      operationId: listCampaigns
      summary: List all campaigns for the current organization
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum: [draft, active, paused, completed]
      responses:
        '200':
          description: List of campaigns
          content:
            application/json:
              schema:
                type: object
                properties:
                  campaigns:
                    type: array
                    items:
                      $ref: '#/components/schemas/Campaign'

    post:
      operationId: createCampaign
      summary: Create a new campaign
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateCampaignRequest'
      responses:
        '201':
          description: Campaign created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Campaign'

  /campaigns/{campaignId}:
    get:
      operationId: getCampaign
      summary: Get campaign details
      parameters:
        - name: campaignId
          in: path
          required: true
          schema:
            type: string
            format: uuid
      responses:
        '200':
          description: Campaign details
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Campaign'

    put:
      operationId: updateCampaign
      summary: Update a campaign
      parameters:
        - name: campaignId
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/UpdateCampaignRequest'
      responses:
        '200':
          description: Campaign updated
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Campaign'

  /content-plans:
    get:
      operationId: listContentPlans
      summary: List all content plans
      parameters:
        - name: campaign_id
          in: query
          schema:
            type: string
            format: uuid
        - name: status
          in: query
          schema:
            type: string
      responses:
        '200':
          description: List of content plans
          content:
            application/json:
              schema:
                type: object
                properties:
                  content_plans:
                    type: array
                    items:
                      $ref: '#/components/schemas/ContentPlan'

    post:
      operationId: createContentPlan
      summary: Create a new content plan
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateContentPlanRequest'
      responses:
        '201':
          description: Content plan created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ContentPlan'

  /content-plans/{contentPlanId}/generate:
    post:
      operationId: generateContent
      summary: Generate AI content for a content plan
      parameters:
        - name: contentPlanId
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                prompt:
                  type: string
                  description: Custom prompt for content generation
                options:
                  type: object
                  description: Additional generation options
      responses:
        '200':
          description: Content generation started
          content:
            application/json:
              schema:
                type: object
                properties:
                  message:
                    type: string
                  content_plan_id:
                    type: string
                    format: uuid
                  status:
                    type: string

  /knowledge/search:
    post:
      operationId: searchKnowledge
      summary: Search the knowledge base using semantic search
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                query:
                  type: string
                  description: Search query
                limit:
                  type: integer
                  default: 10
                content_type:
                  type: string
                  enum: [brand_guideline, market_research, competitor_analysis, campaign_brief, product_info, creative_asset]
      responses:
        '200':
          description: Search results
          content:
            application/json:
              schema:
                type: object
                properties:
                  results:
                    type: array
                    items:
                      $ref: '#/components/schemas/KnowledgeItem'

  /analytics/campaign/{campaignId}:
    get:
      operationId: getCampaignAnalytics
      summary: Get analytics for a campaign
      parameters:
        - name: campaignId
          in: path
          required: true
          schema:
            type: string
            format: uuid
        - name: start_date
          in: query
          schema:
            type: string
            format: date
        - name: end_date
          in: query
          schema:
            type: string
            format: date
      responses:
        '200':
          description: Campaign analytics
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CampaignAnalytics'

components:
  schemas:
    Campaign:
      type: object
      properties:
        id:
          type: string
          format: uuid
        name:
          type: string
        description:
          type: string
        status:
          type: string
          enum: [draft, active, paused, completed]
        start_date:
          type: string
          format: date
        end_date:
          type: string
          format: date
        budget:
          type: number
        spent:
          type: number
        created_at:
          type: string
          format: date-time

    CreateCampaignRequest:
      type: object
      required:
        - name
        - start_date
        - end_date
      properties:
        name:
          type: string
        description:
          type: string
        start_date:
          type: string
          format: date
        end_date:
          type: string
          format: date
        budget:
          type: number
        target_audience:
          type: object
        objectives:
          type: array
          items:
            type: string

    UpdateCampaignRequest:
      type: object
      properties:
        name:
          type: string
        description:
          type: string
        status:
          type: string
          enum: [draft, active, paused, completed]
        budget:
          type: number

    ContentPlan:
      type: object
      properties:
        id:
          type: string
          format: uuid
        name:
          type: string
        campaign_id:
          type: string
          format: uuid
        content_type:
          type: string
        target_platforms:
          type: array
          items:
            type: string
        status:
          type: string

    CreateContentPlanRequest:
      type: object
      required:
        - campaign_id
        - name
        - content_type
        - target_platforms
      properties:
        campaign_id:
          type: string
          format: uuid
        name:
          type: string
        description:
          type: string
        content_type:
          type: string
          enum: [social_post, blog_article, ad_copy, email, video_script]
        target_platforms:
          type: array
          items:
            type: string
        tone:
          type: string
        key_messages:
          type: array
          items:
            type: string

    KnowledgeItem:
      type: object
      properties:
        id:
          type: string
          format: uuid
        title:
          type: string
        content_type:
          type: string
        content:
          type: string
        relevance_score:
          type: number
        created_at:
          type: string
          format: date-time

    CampaignAnalytics:
      type: object
      properties:
        campaign_id:
          type: string
          format: uuid
        impressions:
          type: integer
        clicks:
          type: integer
        conversions:
          type: integer
        spend:
          type: number
        ctr:
          type: number
        cpc:
          type: number
        conversion_rate:
          type: number
```

**Success Criteria:**
- [ ] OpenAPI 3.1 schema created
- [ ] All core operations defined
- [ ] Request/response schemas complete
- [ ] Authentication defined
- [ ] Validated with OpenAPI tools

**Time Estimate:** 8 hours

---

### Task 3.2: Implement GPT-Specific API Endpoints (15h)

**Files:** New GPT controller and routes
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Required Implementation:**

```php
// app/Http/Controllers/GPT/GPTController.php
namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Services\CampaignService;
use App\Services\ContentPlanService;
use App\Services\KnowledgeService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class GPTController extends Controller
{
    // Campaigns
    public function listCampaigns(Request $request)
    {
        $campaigns = Campaign::query()
            ->where('org_id', $request->user()->current_org_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with(['contentPlans', 'adAccounts'])
            ->latest()
            ->get();

        return response()->json([
            'campaigns' => $campaigns->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'status' => $c->status,
                'start_date' => $c->start_date,
                'end_date' => $c->end_date,
                'budget' => $c->budget,
                'spent' => $c->spent,
                'created_at' => $c->created_at,
            ])
        ]);
    }

    public function createCampaign(Request $request, CampaignService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|array',
            'objectives' => 'nullable|array',
        ]);

        $campaign = $service->create($validated);

        return response()->json($campaign, 201);
    }

    // Content Plans
    public function listContentPlans(Request $request)
    {
        $plans = ContentPlan::query()
            ->where('org_id', $request->user()->current_org_id)
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with('campaign')
            ->latest()
            ->get();

        return response()->json(['content_plans' => $plans]);
    }

    public function createContentPlan(Request $request, ContentPlanService $service)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:social_post,blog_article,ad_copy,email,video_script',
            'target_platforms' => 'required|array',
            'tone' => 'nullable|string',
            'key_messages' => 'nullable|array',
        ]);

        $plan = $service->create($validated);

        return response()->json($plan, 201);
    }

    public function generateContent(Request $request, string $contentPlanId, ContentPlanService $service)
    {
        $contentPlan = ContentPlan::findOrFail($contentPlanId);

        $this->authorize('update', $contentPlan);

        $validated = $request->validate([
            'prompt' => 'nullable|string',
            'options' => 'nullable|array',
        ]);

        $result = $service->generateContent(
            $contentPlan,
            $validated['prompt'] ?? null,
            $validated['options'] ?? []
        );

        return response()->json($result);
    }

    // Knowledge Base
    public function searchKnowledge(Request $request, KnowledgeService $service)
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:50',
            'content_type' => 'nullable|in:brand_guideline,market_research,competitor_analysis,campaign_brief,product_info,creative_asset',
        ]);

        $results = $service->semanticSearch(
            $validated['query'],
            $request->user()->current_org_id,
            $validated['limit'] ?? 10,
            $validated['content_type'] ?? null
        );

        return response()->json(['results' => $results]);
    }

    // Analytics
    public function getCampaignAnalytics(Request $request, string $campaignId, AnalyticsService $service)
    {
        $campaign = Campaign::findOrFail($campaignId);

        $this->authorize('view', $campaign);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $analytics = $service->getCampaignAnalytics(
            $campaignId,
            $validated['start_date'] ?? $campaign->start_date,
            $validated['end_date'] ?? now()
        );

        return response()->json($analytics);
    }
}
```

**Add routes:**
```php
// routes/api.php
Route::prefix('gpt')->middleware(['auth:sanctum', 'throttle:gpt'])->group(function () {
    // Campaigns
    Route::get('/campaigns', [GPTController::class, 'listCampaigns']);
    Route::post('/campaigns', [GPTController::class, 'createCampaign']);
    Route::get('/campaigns/{campaign}', [GPTController::class, 'getCampaign']);
    Route::put('/campaigns/{campaign}', [GPTController::class, 'updateCampaign']);

    // Content Plans
    Route::get('/content-plans', [GPTController::class, 'listContentPlans']);
    Route::post('/content-plans', [GPTController::class, 'createContentPlan']);
    Route::post('/content-plans/{contentPlanId}/generate', [GPTController::class, 'generateContent']);

    // Knowledge Base
    Route::post('/knowledge/search', [GPTController::class, 'searchKnowledge']);

    // Analytics
    Route::get('/analytics/campaign/{campaign}', [GPTController::class, 'getCampaignAnalytics']);
});
```

**Success Criteria:**
- [ ] All GPT endpoints implemented
- [ ] Responses match OpenAPI schema
- [ ] Authentication working
- [ ] Rate limiting applied
- [ ] Tests pass
- [ ] Postman collection created

**Time Estimate:** 15 hours

---

### Task 3.3: Create GPT Authentication System (6h)

**Files:** GPT auth middleware, token management
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Implementation details...**

**Success Criteria:**
- [ ] OAuth flow for GPT
- [ ] Token management
- [ ] Refresh tokens
- [ ] Tests pass

**Time Estimate:** 6 hours

---

### Task 3.4: Create GPT Documentation (6h)

**Files:** `docs/GPT_INTEGRATION.md`
**Priority:** 🟡 MEDIUM
**Status:** ⏳ Pending

**Success Criteria:**
- [ ] Setup guide created
- [ ] API reference complete
- [ ] Example prompts documented
- [ ] Troubleshooting guide

**Time Estimate:** 6 hours

---

## Phase 4: GPT Interface Completion

**Timeline:** Week 7-8
**Effort:** 27 hours
**Priority:** 🟡 MEDIUM

### Task 4.1: Implement Conversational Context Management (12h)

Track conversation history and context for GPT interactions.

**Success Criteria:**
- [ ] Context stored per session
- [ ] Multi-turn conversations
- [ ] Context retrieval working

**Time Estimate:** 12 hours

---

### Task 4.2: Add GPT Action Handlers (10h)

Implement handlers for complex GPT actions.

**Success Criteria:**
- [ ] All actions have handlers
- [ ] Error handling robust
- [ ] Tests pass

**Time Estimate:** 10 hours

---

### Task 4.3: GPT Integration Testing (5h)

End-to-end testing with ChatGPT.

**Success Criteria:**
- [ ] GPT can create campaigns
- [ ] GPT can search knowledge
- [ ] GPT can generate content
- [ ] All actions tested

**Time Estimate:** 5 hours

---

## Phase 5: Testing & Documentation

**Timeline:** Week 9-10
**Effort:** 32 hours
**Priority:** 🟠 HIGH

### Task 5.1: Write Comprehensive Tests (20h)

**Unit Tests:**
- Models (100% coverage)
- Services (100% coverage)
- Helpers (100% coverage)

**Feature Tests:**
- Authentication flow
- Campaign CRUD
- Content Plan CRUD
- Knowledge search
- AI generation
- Analytics

**Browser Tests:**
- User registration
- Campaign creation
- Content generation workflow
- Dashboard interactions

**Success Criteria:**
- [ ] 90%+ code coverage
- [ ] All critical paths tested
- [ ] CI/CD passing
- [ ] No flaky tests

**Time Estimate:** 20 hours

---

### Task 5.2: Create User Documentation (6h)

**Files:** `docs/USER_GUIDE.md`, `docs/API_REFERENCE.md`

**Content:**
- Getting started guide
- Feature walkthrough
- API documentation
- CLI commands reference
- GPT usage guide
- Troubleshooting

**Success Criteria:**
- [ ] All features documented
- [ ] Screenshots added
- [ ] Examples working
- [ ] Reviewed by team

**Time Estimate:** 6 hours

---

### Task 5.3: Performance Testing & Optimization (6h)

**Load Testing:**
- 100 concurrent users
- API response times < 200ms
- Database query optimization
- Caching effectiveness

**Success Criteria:**
- [ ] Load tests passing
- [ ] No N+1 queries
- [ ] Cache hit rate > 80%
- [ ] Response times acceptable

**Time Estimate:** 6 hours

---

## Success Criteria

### Overall System Requirements

**Security:**
- [x] Login requires password verification
- [x] Tokens expire after 7 days
- [x] RLS enabled on all tenant-scoped tables
- [x] Rate limiting on AI endpoints
- [x] Security headers applied
- [ ] HTTPS enforced in production
- [ ] Regular security audits scheduled

**Functionality:**
- [ ] All 4 interfaces working (Web, API, CLI, GPT)
- [ ] Content Plan CRUD complete
- [ ] org_markets CRUD complete
- [ ] Compliance UI complete
- [ ] Knowledge search working
- [ ] AI generation working
- [ ] Analytics dashboard functional

**Performance:**
- [ ] API response time < 200ms (p95)
- [ ] Database queries optimized
- [ ] Caching implemented
- [ ] Load testing passed

**Testing:**
- [ ] 90%+ code coverage
- [ ] All critical paths tested
- [ ] No flaky tests
- [ ] CI/CD pipeline green

**Documentation:**
- [ ] User guide complete
- [ ] API reference complete
- [ ] GPT integration guide complete
- [ ] Deployment guide complete

### Grade Targets

| Component | Current | Target | Gap |
|-----------|---------|--------|-----|
| Database Schema | 92% | 95% | 3% |
| Models | 94% | 95% | 1% |
| API | 85% | 95% | 10% |
| Web UI | 77% | 90% | 13% |
| CLI | 88% | 95% | 7% |
| Authentication | 78% | 95% | 17% |
| Knowledge/AI | 82% | 95% | 13% |
| GPT | 35% | 90% | 55% |
| **OVERALL** | **75%** | **95%** | **20%** |

---

## Risk Management

### High-Risk Items

**1. UUID Migration (Task 1.1)**
- **Risk:** Data loss during migration
- **Mitigation:** Full backup, test on dev first, rollback plan
- **Contingency:** Restore from backup

**2. RLS Enablement (Task 0.3)**
- **Risk:** Performance degradation
- **Mitigation:** Test on staging with production data size
- **Contingency:** Selective RLS (enable on critical tables only)

**3. GPT Integration (Phase 3-4)**
- **Risk:** Complex integration, external dependency
- **Mitigation:** Phased approach, thorough testing
- **Contingency:** Manual workflows as fallback

### Dependencies

- **External Services:**
  - OpenAI API (for AI generation)
  - Meta API (for ad management)
  - Google API (for analytics)

- **Infrastructure:**
  - PostgreSQL 16+ with pgvector
  - Redis for caching
  - Queue workers

---

## Deployment Checklist

### Pre-Deployment

- [ ] All tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Database backup created
- [ ] Environment variables configured
- [ ] SSL certificate valid
- [ ] Monitoring configured

### Deployment Steps

1. **Maintenance Mode**
   ```bash
   php artisan down --refresh=15
   ```

2. **Backup Database**
   ```bash
   pg_dump -h 127.0.0.1 -U begin -d cmis > backup_$(date +%Y%m%d).sql
   ```

3. **Pull Latest Code**
   ```bash
   git pull origin main
   ```

4. **Install Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci --production
   npm run build
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   php artisan optimize
   ```

7. **Restart Services**
   ```bash
   php artisan queue:restart
   sudo systemctl restart php8.3-fpm
   sudo systemctl restart nginx
   ```

8. **Exit Maintenance Mode**
   ```bash
   php artisan up
   ```

9. **Smoke Tests**
   - [ ] Login works
   - [ ] Can create campaign
   - [ ] AI generation works
   - [ ] Analytics loading

### Post-Deployment

- [ ] Monitor error logs
- [ ] Check application metrics
- [ ] Verify all services running
- [ ] Test critical user flows
- [ ] Notify stakeholders

---

## Timeline Summary

| Phase | Duration | Effort | Priority |
|-------|----------|--------|----------|
| Phase 0: Security | Days 1-2 | 15h | 🔴 CRITICAL |
| Phase 1: Infrastructure | Days 3-10 | 24h | 🟠 HIGH |
| Phase 2: Core Features | Week 3-4 | 79h | 🟠 HIGH |
| Phase 3: GPT Foundation | Week 5-6 | 35h | 🟡 MEDIUM |
| Phase 4: GPT Completion | Week 7-8 | 27h | 🟡 MEDIUM |
| Phase 5: Testing & Docs | Week 9-10 | 32h | 🟠 HIGH |
| **TOTAL** | **10 weeks** | **240h** | - |

---

## Progress Tracking

Use this section to track implementation progress:

### Week 1
- [ ] Task 0.1: Fix login (2h)
- [ ] Task 0.2: Token expiration (4h)
- [ ] Task 0.3: RLS (3h)
- [ ] Task 0.4: Rate limiting (2h)
- [ ] Task 0.5: Security headers (2h)
- [ ] Task 0.6: Documentation (2h)
- [ ] Task 1.1: UUID migration (8h)

### Week 2
- [ ] Task 1.2: Indexes (4h)
- [ ] Task 1.3: Redis (6h)
- [ ] Task 1.4: Queues (6h)

### Week 3-4
- [ ] Task 2.1: Content Plan CRUD (33h)
- [ ] Task 2.2: org_markets CRUD (18h)
- [ ] Task 2.3: Compliance UI (16h)
- [ ] Task 2.4: Frontend-API (12h)

### Week 5-6
- [ ] Task 3.1: GPT schema (8h)
- [ ] Task 3.2: GPT endpoints (15h)
- [ ] Task 3.3: GPT auth (6h)
- [ ] Task 3.4: GPT docs (6h)

### Week 7-8
- [ ] Task 4.1: Context management (12h)
- [ ] Task 4.2: Action handlers (10h)
- [ ] Task 4.3: Integration testing (5h)

### Week 9-10
- [ ] Task 5.1: Tests (20h)
- [ ] Task 5.2: Documentation (6h)
- [ ] Task 5.3: Performance (6h)

---

## Notes

- All estimates include testing time
- Code review not included in estimates (add 20% buffer)
- Priority can be adjusted based on business needs
- Each completed task should be reviewed before proceeding
- Update this document as implementation progresses

---

**Document Status:** Ready for execution
**Next Action:** Begin Phase 0, Task 0.1 (Fix Login Password Verification)
**Approved By:** [Pending]
**Date:** 2025-11-16
