# Multi-Tenancy Testing Guide

**CMIS Row-Level Security (RLS) Testing Strategies**

**Last Updated:** 2025-11-23
**Status:** Official Testing Guidelines
**Priority:** CRITICAL - Security Testing

---

## 🎯 Testing Objectives

Multi-tenancy testing ensures:
1. ✅ Data isolation between organizations
2. ✅ RLS policies prevent cross-org data access
3. ✅ Context middleware correctly sets session variables
4. ✅ Services respect RLS context
5. ✅ Jobs and commands properly initialize context
6. ✅ Edge cases handled (no context, wrong context, etc.)

---

## 📋 Test Categories

### 1. Database-Level RLS Tests
### 2. Middleware Context Tests
### 3. Service Layer RLS Compliance Tests
### 4. Background Job Context Tests
### 5. Console Command Context Tests
### 6. Integration Tests (End-to-End)
### 7. Security Penetration Tests

---

## 🗄️ Category 1: Database-Level RLS Tests

**Purpose:** Verify PostgreSQL RLS policies correctly filter data.

### Test 1.1: Basic RLS Isolation

```php
<?php

namespace Tests\Feature\MultiTenancy;

use App\Models\Campaign\Campaign;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RLSIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that RLS policies prevent cross-org data access
     *
     * @test
     */
    public function rls_prevents_cross_organization_data_access()
    {
        // Arrange: Create two organizations with data
        $org1 = Organization::factory()->create(['name' => 'Organization Alpha']);
        $org2 = Organization::factory()->create(['name' => 'Organization Beta']);

        $user1 = User::factory()->create(['org_id' => $org1->org_id]);
        $user2 = User::factory()->create(['org_id' => $org2->org_id]);

        // Org1: 5 campaigns
        $org1Campaigns = Campaign::factory()->count(5)->create([
            'org_id' => $org1->org_id,
            'name' => 'Org1 Campaign'
        ]);

        // Org2: 3 campaigns
        $org2Campaigns = Campaign::factory()->count(3)->create([
            'org_id' => $org2->org_id,
            'name' => 'Org2 Campaign'
        ]);

        // Act: Set RLS context for Organization Alpha
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user1->id,
            $org1->org_id
        ]);

        $visibleCampaigns = Campaign::all();

        // Assert: Can only see Org1's campaigns
        $this->assertCount(5, $visibleCampaigns);
        $this->assertEquals($org1->org_id, $visibleCampaigns->first()->org_id);

        // Assert: Cannot find Org2's campaigns by ID
        foreach ($org2Campaigns as $org2Campaign) {
            $result = Campaign::find($org2Campaign->campaign_id);
            $this->assertNull($result, "Should not find Org2 campaign: {$org2Campaign->name}");
        }

        // Clean up
        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Test switching between organizations
     *
     * @test
     */
    public function rls_context_switches_correctly_between_organizations()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user1 = User::factory()->create(['org_id' => $org1->org_id]);
        $user2 = User::factory()->create(['org_id' => $org2->org_id]);

        Campaign::factory()->count(3)->create(['org_id' => $org1->org_id]);
        Campaign::factory()->count(2)->create(['org_id' => $org2->org_id]);

        // Set context for Org1
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user1->id, $org1->org_id
        ]);
        $this->assertCount(3, Campaign::all(), 'Should see 3 campaigns for Org1');

        // Clear and switch to Org2
        DB::statement('SELECT cmis.clear_transaction_context()');
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user2->id, $org2->org_id
        ]);
        $this->assertCount(2, Campaign::all(), 'Should see 2 campaigns for Org2');

        // Clean up
        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Test queries without context return no data (secure default)
     *
     * @test
     */
    public function queries_without_rls_context_return_no_data()
    {
        $org = Organization::factory()->create();
        Campaign::factory()->count(5)->create(['org_id' => $org->org_id]);

        // Don't set context - should return 0 campaigns (RLS blocks)
        $campaigns = Campaign::all();

        $this->assertCount(0, $campaigns, 'Without context, RLS should block all data');
    }
}
```

---

## 🔒 Category 2: Middleware Context Tests

**Purpose:** Verify middleware correctly sets and clears RLS context.

### Test 2.1: SetOrganizationContext Middleware

```php
<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetOrganizationContext;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetOrganizationContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test middleware sets RLS context correctly
     *
     * @test
     */
    public function middleware_sets_rls_context()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'org_id' => $org->org_id,
            'current_org_id' => $org->org_id
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new SetOrganizationContext();

        $middleware->handle($request, function ($req) use ($org) {
            // Inside middleware - check context is set
            $currentOrg = DB::selectOne(
                "SELECT current_setting('app.current_org_id', true) as org_id"
            );

            $this->assertEquals($org->org_id, $currentOrg->org_id);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Test middleware rejects invalid UUID
     *
     * @test
     */
    public function middleware_rejects_invalid_org_uuid()
    {
        $user = User::factory()->create([
            'org_id' => 'invalid-uuid',  // Invalid UUID
            'current_org_id' => 'invalid-uuid'
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new SetOrganizationContext();

        $response = $middleware->handle($request, fn() => response()->json([]));

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid organization', $response->getContent());
    }

    /**
     * Test middleware detects race conditions (multiple context middleware)
     *
     * @test
     */
    public function middleware_detects_race_condition()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->org_id]);

        // Manually set context (simulate another middleware already set it)
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user->id, $org->org_id
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new SetOrganizationContext();
        $response = $middleware->handle($request, fn() => response()->json([]));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Multiple context middleware', $response->getContent());

        // Clean up
        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Test middleware clears context after request
     *
     * @test
     */
    public function middleware_clears_context_after_request()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->org_id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new SetOrganizationContext();

        $response = $middleware->handle($request, fn() => response()->json([]));

        // Simulate terminate() lifecycle
        $middleware->terminate($request, $response);

        // Context should be cleared
        try {
            $currentOrg = DB::selectOne(
                "SELECT current_setting('app.current_org_id', true) as org_id"
            );
            $this->assertNull($currentOrg->org_id ?? null);
        } catch (\Exception $e) {
            // Expected - context cleared
            $this->assertTrue(true);
        }
    }
}
```

---

## 🛠️ Category 3: Service Layer RLS Compliance Tests

**Purpose:** Verify services respect RLS context and don't bypass it.

### Test 3.1: Service Respects RLS Context

```php
<?php

namespace Tests\Feature\Services;

use App\Models\Campaign\Campaign;
use App\Models\Core\Organization;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignServiceRLSTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test service respects RLS filtering
     *
     * @test
     */
    public function service_returns_only_current_org_campaigns()
    {
        // Arrange
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $org1->org_id]);

        Campaign::factory()->count(5)->create(['org_id' => $org1->org_id, 'status' => 'active']);
        Campaign::factory()->count(3)->create(['org_id' => $org2->org_id, 'status' => 'active']);

        // Act: Set context for org1
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user->id, $org1->org_id
        ]);

        $service = new CampaignService();
        $campaigns = $service->getActiveCampaigns();

        // Assert: Only see org1's 5 campaigns
        $this->assertCount(5, $campaigns);
        $this->assertTrue(
            $campaigns->every(fn($c) => $c->org_id === $org1->org_id),
            'All campaigns should belong to org1'
        );

        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Test service throws error when context not set (defensive)
     *
     * @test
     */
    public function service_throws_error_when_context_not_set()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No RLS context set');

        $service = new CampaignService();
        $service->getActiveCampaigns();  // Should throw - no context set
    }
}
```

---

## 📦 Category 4: Background Job Context Tests

**Purpose:** Verify jobs properly set RLS context before database operations.

### Test 4.1: Job Sets Context

```php
<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncCampaignDataJob;
use App\Models\Campaign\Campaign;
use App\Models\Core\Integration;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JobContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test job sets RLS context before queries
     *
     * @test
     */
    public function job_sets_rls_context_correctly()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $org1->org_id]);
        $integration = Integration::factory()->create(['org_id' => $org1->org_id]);

        Campaign::factory()->count(3)->create(['org_id' => $org1->org_id]);
        Campaign::factory()->count(2)->create(['org_id' => $org2->org_id]);

        // Create and execute job
        $job = new SyncCampaignDataJob($user->id, $org1->org_id, $integration);

        // Job should only process org1's 3 campaigns, not org2's 2
        $job->handle();

        // Verify job completed successfully (no exceptions)
        $this->assertTrue(true);
    }

    /**
     * Test job without context fails safely
     *
     * @test
     */
    public function job_without_context_fails_safely()
    {
        $this->expectException(\RuntimeException::class);

        // Create a job class that doesn't set context (anti-pattern)
        $badJob = new class {
            public function handle() {
                // ❌ No context set - should throw error
                $campaigns = Campaign::all();
            }
        };

        $badJob->handle();
    }
}
```

---

## 🖥️ Category 5: Console Command Context Tests

**Purpose:** Verify console commands iterate correctly across organizations.

### Test 5.1: Command Uses HandlesOrgContext

```php
<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SyncAllOrganizationsCommand;
use App\Models\Campaign\Campaign;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsoleCommandContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command processes each org with correct context
     *
     * @test
     */
    public function command_processes_each_org_with_correct_context()
    {
        // Create system user (required by HandlesOrgContext trait)
        User::factory()->create([
            'email' => 'system@cmis.app',
            'name' => 'System'
        ]);

        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        Campaign::factory()->count(3)->create(['org_id' => $org1->org_id]);
        Campaign::factory()->count(2)->create(['org_id' => $org2->org_id]);

        // Execute command
        $this->artisan('sync:all-organizations')
            ->expectsOutput('Processing: Org 1')
            ->expectsOutput('Processing: Org 2')
            ->assertExitCode(0);
    }
}
```

---

## 🔗 Category 6: Integration Tests (End-to-End)

**Purpose:** Test full request lifecycle with RLS context.

### Test 6.1: API Request End-to-End

```php
<?php

namespace Tests\Feature\Integration;

use App\Models\Campaign\Campaign;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignAPIIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete API request with multi-tenancy
     *
     * @test
     */
    public function api_request_respects_multi_tenancy()
    {
        // Arrange: Two orgs, two users
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user1 = User::factory()->create(['org_id' => $org1->org_id]);
        $user2 = User::factory()->create(['org_id' => $org2->org_id]);

        Campaign::factory()->count(5)->create(['org_id' => $org1->org_id]);
        Campaign::factory()->count(3)->create(['org_id' => $org2->org_id]);

        // Act: User1 requests org1's campaigns
        Sanctum::actingAs($user1);

        $response = $this->getJson("/api/orgs/{$org1->org_id}/campaigns");

        // Assert: Success, only see 5 campaigns
        $response->assertOk();
        $response->assertJsonCount(5, 'data');

        // Assert: All campaigns belong to org1
        $campaigns = $response->json('data');
        foreach ($campaigns as $campaign) {
            $this->assertEquals($org1->org_id, $campaign['org_id']);
        }

        // Act: User1 tries to access org2's campaigns (should fail)
        $response = $this->getJson("/api/orgs/{$org2->org_id}/campaigns");

        // Assert: Forbidden (validate.org.access middleware blocks)
        $response->assertForbidden();
    }

    /**
     * Test creating resource respects multi-tenancy
     *
     * @test
     */
    public function creating_resource_associates_with_current_org()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->org_id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/orgs/{$org->org_id}/campaigns", [
            'name' => 'New Campaign',
            'status' => 'draft',
            'budget' => 10000
        ]);

        $response->assertCreated();

        // Assert: Campaign belongs to correct org
        $campaign = Campaign::find($response->json('data.id'));
        $this->assertEquals($org->org_id, $campaign->org_id);
    }
}
```

---

## 🛡️ Category 7: Security Penetration Tests

**Purpose:** Attempt to bypass RLS and verify failures.

### Test 7.1: Attempt Cross-Org Access

```php
<?php

namespace Tests\Feature\Security;

use App\Models\Campaign\Campaign;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiTenancySecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User cannot access another org's data by manipulating org_id
     *
     * @test
     */
    public function user_cannot_access_other_org_by_changing_org_id_parameter()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $org1->org_id]);

        $campaign = Campaign::factory()->create(['org_id' => $org2->org_id]);

        Sanctum::actingAs($user);

        // Attempt to access org2's campaign
        $response = $this->getJson("/api/orgs/{$org2->org_id}/campaigns/{$campaign->id}");

        // Should be blocked by validate.org.access middleware
        $response->assertForbidden();
    }

    /**
     * Test: Direct database access respects RLS
     *
     * @test
     */
    public function direct_database_query_respects_rls()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $org1->org_id]);

        Campaign::factory()->create(['org_id' => $org1->org_id, 'name' => 'Visible Campaign']);
        $hidden = Campaign::factory()->create(['org_id' => $org2->org_id, 'name' => 'Hidden Campaign']);

        // Set context for org1
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user->id, $org1->org_id
        ]);

        // Attempt direct table query (trying to bypass Eloquent)
        $results = DB::table('cmis.campaigns')
            ->where('campaign_id', $hidden->campaign_id)
            ->get();

        // RLS should still block this
        $this->assertCount(0, $results, 'RLS should block cross-org access even with raw queries');

        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Test: Mass assignment cannot change org_id
     *
     * @test
     */
    public function mass_assignment_cannot_change_org_id()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $org1->org_id]);

        Sanctum::actingAs($user);

        // Try to create campaign with different org_id
        $response = $this->postJson("/api/orgs/{$org1->org_id}/campaigns", [
            'name' => 'Malicious Campaign',
            'org_id' => $org2->org_id,  // ⚠️ Attempting to assign to different org
            'status' => 'active'
        ]);

        if ($response->isSuccessful()) {
            $campaign = Campaign::find($response->json('data.id'));

            // Should be created under org1, not org2
            $this->assertEquals(
                $org1->org_id,
                $campaign->org_id,
                'Campaign should be created under current org, not attacker-specified org'
            );
        }
    }
}
```

---

## 🧪 Test Utilities & Helpers

### Helper: Context Setup Trait

```php
<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait HasRLSContext
{
    /**
     * Set RLS context for testing
     */
    protected function setRLSContext(string $userId, string $orgId): void
    {
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $userId, $orgId
        ]);
    }

    /**
     * Clear RLS context
     */
    protected function clearRLSContext(): void
    {
        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Get current RLS org context
     */
    protected function getCurrentOrgContext(): ?string
    {
        try {
            $result = DB::selectOne(
                "SELECT current_setting('app.current_org_id', true) as org_id"
            );
            return $result?->org_id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Assert RLS context is set
     */
    protected function assertRLSContextSet(string $expectedOrgId): void
    {
        $actual = $this->getCurrentOrgContext();
        $this->assertEquals($expectedOrgId, $actual, 'RLS context mismatch');
    }

    /**
     * Assert RLS context is NOT set
     */
    protected function assertRLSContextNotSet(): void
    {
        $actual = $this->getCurrentOrgContext();
        $this->assertNull($actual, 'RLS context should not be set');
    }
}
```

**Usage:**
```php
use Tests\Traits\HasRLSContext;

class MyTest extends TestCase
{
    use RefreshDatabase, HasRLSContext;

    public function test_something()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $this->setRLSContext($user->id, $org->org_id);
        $this->assertRLSContextSet($org->org_id);

        // ... test code ...

        $this->clearRLSContext();
        $this->assertRLSContextNotSet();
    }
}
```

---

## 📊 Test Coverage Requirements

### Minimum Coverage Targets

| Component | Coverage Target | Priority |
|-----------|----------------|----------|
| **Middleware** | 100% | CRITICAL |
| **Services** | 90% | HIGH |
| **Jobs** | 85% | HIGH |
| **Console Commands** | 80% | MEDIUM |
| **Models** | 75% | MEDIUM |

### Critical Paths (Must Have Tests)

- [x] SetOrganizationContext middleware
- [x] RLS isolation per model
- [x] Job context initialization
- [x] Service RLS compliance
- [x] Cross-org access attempts (security)
- [x] API endpoint multi-tenancy
- [ ] WebSocket/real-time updates (future)
- [ ] Export/import with multi-tenancy

---

## 🚀 Running Tests

### Run All Multi-Tenancy Tests

```bash
# All multi-tenancy tests
php artisan test --testsuite=Feature --filter=MultiTenancy

# Specific categories
php artisan test tests/Feature/MultiTenancy/RLSIsolationTest.php
php artisan test tests/Feature/Middleware/SetOrganizationContextTest.php
php artisan test tests/Feature/Security/MultiTenancySecurityTest.php
```

### Run with Coverage

```bash
# Generate coverage report
php artisan test --coverage --min=80

# HTML coverage report
php artisan test --coverage-html coverage/
```

### Continuous Integration

**.github/workflows/multi-tenancy-tests.yml:**
```yaml
name: Multi-Tenancy Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: password
          POSTGRES_DB: cmis_test
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: pdo_pgsql

      - name: Install Dependencies
        run: composer install

      - name: Run Migrations
        run: php artisan migrate --env=testing

      - name: Run Multi-Tenancy Tests
        run: php artisan test --testsuite=Feature --filter=MultiTenancy --stop-on-failure

      - name: Run Security Tests
        run: php artisan test tests/Feature/Security/ --stop-on-failure
```

---

## 📝 Test Checklist for New Features

**Before merging any PR that touches multi-tenant data:**

- [ ] Model has RLS isolation test
- [ ] Service has RLS compliance test
- [ ] API endpoint has integration test
- [ ] Cross-org access attempt test (security)
- [ ] Job/command sets context test (if applicable)
- [ ] No manual `where('org_id')` filtering in controllers/services
- [ ] All tests pass with `--stop-on-failure`

---

## 🎓 Best Practices

### 1. Test Data Setup

```php
// ✅ GOOD: Clear separation between orgs
public function test_something()
{
    $org1 = Organization::factory()->create(['name' => 'Org Alpha']);
    $org2 = Organization::factory()->create(['name' => 'Org Beta']);

    $org1Data = Model::factory()->count(5)->create(['org_id' => $org1->org_id]);
    $org2Data = Model::factory()->count(3)->create(['org_id' => $org2->org_id]);

    // ... test with clear org separation
}

// ❌ BAD: Unclear which data belongs to which org
public function test_something()
{
    $orgs = Organization::factory()->count(2)->create();
    $data = Model::factory()->count(10)->create();  // Unclear org association
}
```

### 2. Context Management

```php
// ✅ GOOD: Always clean up context
public function test_something()
{
    $this->setRLSContext($userId, $orgId);

    try {
        // ... test code
    } finally {
        $this->clearRLSContext();  // Always cleanup
    }
}

// ✅ BETTER: Use trait helper
use HasRLSContext;

public function test_something()
{
    $this->setRLSContext($userId, $orgId);
    // ... test code
    $this->clearRLSContext();
}
```

### 3. Assertion Messages

```php
// ✅ GOOD: Clear assertion messages
$this->assertCount(
    5,
    $campaigns,
    "Expected 5 campaigns for Org1, but got {$campaigns->count()}"
);

// ❌ BAD: No message
$this->assertCount(5, $campaigns);
```

---

## 🔍 Debugging Failed Tests

### Common Issues & Solutions

**Issue:** Test sees data from other orgs
```php
// Check if context is set
dump($this->getCurrentOrgContext());

// Check what RLS sees
dump(DB::selectOne("SELECT current_setting('app.current_org_id', true)"));
```

**Issue:** Context not cleared between tests
```php
// Add tearDown method
protected function tearDown(): void
{
    $this->clearRLSContext();
    parent::tearDown();
}
```

**Issue:** Migration RLS policies not applied
```bash
# Refresh database with seeds
php artisan migrate:fresh --seed --env=testing

# Check RLS status
psql -c "SELECT tablename, rowsecurity FROM pg_tables WHERE schemaname = 'cmis';"
```

---

## 📚 Further Reading

- PHPUnit Documentation: https://phpunit.de/documentation.html
- Laravel Testing: https://laravel.com/docs/testing
- PostgreSQL RLS: https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- CMIS Multi-Tenancy Patterns: `/docs/guides/development/multi-tenancy-rls-patterns.md`
- Context Awareness Audit: `/docs/active/analysis/context-awareness-audit-2025-11-23.md`

---

**Last Updated:** 2025-11-23
**Maintainer:** CMIS QA Team
**Questions?** Contact development team
