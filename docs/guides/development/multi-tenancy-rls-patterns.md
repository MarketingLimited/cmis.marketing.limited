# Multi-Tenancy & RLS Patterns Guide

**CMIS PostgreSQL Row-Level Security (RLS) Best Practices**

**Last Updated:** 2025-11-23
**Status:** Official Development Guidelines
**Mandatory Compliance:** YES - Security Critical

---

## 🎯 Core Principle

**CMIS uses PostgreSQL Row-Level Security (RLS) for database-level multi-tenancy.**

This means:
- ✅ Database automatically filters queries by organization
- ✅ Security enforced at the database layer (not application)
- ✅ Zero-trust architecture: even if app logic fails, DB protects data
- ❌ Manual `WHERE org_id = ?` filtering is **REDUNDANT** and **BYPASSES** this security

---

## 📋 Quick Reference

### ✅ DO THIS

```php
// Controllers - Let middleware set context
public function index()
{
    $campaigns = Campaign::all();  // RLS filters automatically
    return response()->json($campaigns);
}

// Services - Assume context is set
public function getCampaigns()
{
    return Campaign::where('status', 'active')->get();  // RLS filters by org
}

// Jobs - Set context explicitly
public function handle()
{
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $this->userId, $this->orgId
    ]);

    $data = Campaign::all();  // Now RLS filters correctly
}
```

### ❌ DON'T DO THIS

```php
// ❌ Controllers - Don't manually filter
public function index(string $orgId)
{
    $campaigns = Campaign::where('org_id', $orgId)->get();  // Bypasses RLS!
    return response()->json($campaigns);
}

// ❌ Services - Don't accept org_id parameter
public function getCampaigns(string $orgId)  // Why pass this?
{
    return Campaign::where('org_id', $orgId)->get();  // RLS already does this!
}

// ❌ Jobs - Don't query without context
public function handle()
{
    $data = Campaign::all();  // ⚠️ NO CONTEXT SET - Will return ALL orgs' data!
}
```

---

## 🏗️ Architecture Overview

### How RLS Works in CMIS

```
┌─────────────────────────────────────────────────────────────┐
│  1. HTTP Request arrives                                     │
│     GET /api/orgs/{org_id}/campaigns                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Middleware Chain                                         │
│     ├─ auth:sanctum        (Authenticate user)              │
│     ├─ validate.org.access (Check user has org access)      │
│     └─ org.context         (Set PostgreSQL session vars)    │
│                                                              │
│     Calls: cmis.init_transaction_context(user_id, org_id)   │
│                                                              │
│     Sets:  app.current_user_id = '...'                      │
│            app.current_org_id = '...'                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Controller Method                                        │
│     public function index()                                  │
│     {                                                        │
│         $campaigns = Campaign::all();  ← No org_id needed!  │
│         return response()->json($campaigns);                │
│     }                                                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  4. Eloquent Query Builder                                   │
│     SELECT * FROM cmis.campaigns;                           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  5. PostgreSQL RLS Policy (Automatic!)                      │
│                                                              │
│     CREATE POLICY campaigns_org_isolation                   │
│     ON cmis.campaigns                                       │
│     FOR ALL                                                 │
│     USING (org_id = current_setting('app.current_org_id')::uuid); │
│                                                              │
│     Transforms query to:                                    │
│     SELECT * FROM cmis.campaigns                            │
│     WHERE org_id = current_setting('app.current_org_id')::uuid; │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  6. Result: Only current org's campaigns returned           │
│     Even if developer forgets to filter, DB enforces it!    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎨 Pattern Categories

### Pattern 1: Controllers (API Endpoints)

**Rule:** Controllers should NEVER receive or filter by `org_id`.

**Reason:** Middleware already set RLS context from the route `{org_id}` parameter.

#### ❌ Anti-Pattern
```php
// routes/api.php
Route::get('/orgs/{org_id}/campaigns', [CampaignController::class, 'index']);

// CampaignController.php - WRONG!
public function index(string $orgId)  // ❌ Don't need this parameter
{
    // ❌ Manual filtering - bypasses RLS!
    $campaigns = Campaign::where('org_id', $orgId)
        ->where('status', 'active')
        ->get();

    return response()->json($campaigns);
}
```

**Problems:**
- Redundant: RLS already filters by org_id
- Security risk: Developer might use wrong org_id variable
- Performance: Extra WHERE clause (RLS already adds it)

#### ✅ Correct Pattern
```php
// routes/api.php (unchanged)
Route::get('/orgs/{org_id}/campaigns', [CampaignController::class, 'index']);

// CampaignController.php - CORRECT!
public function index()  // ✅ No org_id parameter needed
{
    // ✅ RLS filters automatically
    $campaigns = Campaign::where('status', 'active')->get();

    return response()->json($campaigns);
}
```

**Benefits:**
- Simpler code
- Database enforces security
- Impossible to accidentally query wrong org

---

### Pattern 2: Service Layer

**Rule:** Service methods should assume RLS context is set.

#### ❌ Anti-Pattern
```php
class CampaignService
{
    // ❌ Accepting org_id encourages manual filtering
    public function getActiveCampaigns(string $orgId): Collection
    {
        return Campaign::where('org_id', $orgId)  // ❌ Bypasses RLS
            ->where('status', 'active')
            ->get();
    }
}
```

#### ✅ Correct Pattern (Option A: Trust RLS)
```php
class CampaignService
{
    /**
     * Get active campaigns for the current organization.
     *
     * Requires: RLS context must be set (via middleware or manually)
     */
    public function getActiveCampaigns(): Collection
    {
        // ✅ RLS context already set by middleware
        return Campaign::where('status', 'active')->get();
    }
}
```

#### ✅ Correct Pattern (Option B: Defensive - Verify Context)
```php
class CampaignService
{
    public function getActiveCampaigns(?string $expectedOrgId = null): Collection
    {
        // Verify RLS context is set
        $this->ensureContextSet($expectedOrgId);

        return Campaign::where('status', 'active')->get();
    }

    private function ensureContextSet(?string $expectedOrgId = null): void
    {
        $currentOrgId = DB::selectOne(
            "SELECT current_setting('app.current_org_id', true) as org_id"
        )?->org_id;

        if (!$currentOrgId) {
            throw new \RuntimeException(
                'No RLS context set. Cannot query organization data safely.'
            );
        }

        if ($expectedOrgId && $currentOrgId !== $expectedOrgId) {
            throw new \RuntimeException(
                "RLS context mismatch! Expected: {$expectedOrgId}, Got: {$currentOrgId}"
            );
        }
    }
}
```

---

### Pattern 3: Background Jobs

**Rule:** Jobs MUST explicitly set RLS context before any database queries.

**Reason:** Jobs run outside HTTP request cycle, so middleware doesn't set context.

#### ❌ Anti-Pattern
```php
class SyncCampaignDataJob implements ShouldQueue
{
    protected string $orgId;

    public function handle()
    {
        // ❌ NO CONTEXT SET - Will return ALL organizations' data!
        $campaigns = Campaign::all();  // ⚠️ SECURITY BREACH!

        foreach ($campaigns as $campaign) {
            $this->syncCampaignMetrics($campaign);
        }
    }
}
```

**This is a CRITICAL security bug!** Without RLS context, queries return ALL organizations' data.

#### ✅ Correct Pattern
```php
class SyncCampaignDataJob implements ShouldQueue
{
    protected string $userId;
    protected string $orgId;

    public function __construct(string $userId, string $orgId)
    {
        $this->userId = $userId;
        $this->orgId = $orgId;
    }

    public function handle()
    {
        // ✅ Set RLS context FIRST
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $this->userId,
            $this->orgId
        ]);

        // ✅ Now queries are automatically filtered to this org
        $campaigns = Campaign::all();

        foreach ($campaigns as $campaign) {
            $this->syncCampaignMetrics($campaign);
        }

        // Optional: Clear context (will be cleared when connection closes anyway)
        DB::statement('SELECT cmis.clear_transaction_context()');
    }
}
```

#### ✅ Better Pattern (With Transaction)
```php
public function handle()
{
    DB::transaction(function () {
        // Set context within transaction
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $this->userId,
            $this->orgId
        ]);

        $campaigns = Campaign::all();

        foreach ($campaigns as $campaign) {
            $this->syncCampaignMetrics($campaign);
        }
    });

    // Context automatically cleared when transaction ends
}
```

---

### Pattern 4: Console Commands

**Rule:** Console commands MUST set context per organization using `HandlesOrgContext` trait.

#### ❌ Anti-Pattern
```php
class SyncAllOrganizationsCommand extends Command
{
    public function handle()
    {
        $orgs = Org::all();

        foreach ($orgs as $org) {
            // ❌ NO CONTEXT SET - Queries will see ALL orgs' data!
            $campaigns = Campaign::where('org_id', $org->org_id)  // ❌ Manual filter
                ->get();

            $this->syncOrgCampaigns($campaigns);
        }
    }
}
```

#### ✅ Correct Pattern (Using Trait)
```php
use App\Console\Traits\HandlesOrgContext;

class SyncAllOrganizationsCommand extends Command
{
    use HandlesOrgContext;  // ✅ Provides executePerOrg() helper

    public function handle()
    {
        $this->executePerOrg(function ($org) {
            // ✅ Context already set for $org
            // ✅ No need to filter by org_id - RLS handles it!
            $campaigns = Campaign::all();

            $this->info("Syncing {$campaigns->count()} campaigns for {$org->name}");
            $this->syncOrgCampaigns($campaigns);
        });
    }
}
```

**What `executePerOrg()` does:**
```php
protected function executePerOrg(\Closure $callback, ?array $orgIds = null)
{
    $systemUser = User::where('email', 'system@cmis.app')->first();
    $orgs = Org::query()->whereNull('deleted_at')->get();

    foreach ($orgs as $org) {
        DB::transaction(function () use ($systemUser, $org, $callback) {
            // ✅ Sets RLS context
            DB::statement(
                "SELECT cmis.init_transaction_context(?, ?)",
                [$systemUser->user_id, $org->org_id]
            );

            // Execute callback with context set
            $callback($org);
        });
    }
}
```

---

### Pattern 5: Repository Pattern

**Rule:** Repositories assume RLS context is set (called from controllers/services).

#### ❌ Anti-Pattern
```php
class CampaignRepository
{
    public function findByOrganization(string $orgId): Collection
    {
        // ❌ Manual filtering - bypasses RLS!
        return Campaign::where('org_id', $orgId)->get();
    }
}
```

#### ✅ Correct Pattern
```php
class CampaignRepository
{
    /**
     * Get all campaigns for current organization (via RLS)
     */
    public function all(): Collection
    {
        // ✅ RLS filters automatically
        return Campaign::all();
    }

    /**
     * Find active campaigns
     */
    public function findActive(): Collection
    {
        // ✅ RLS context is already set
        return Campaign::where('status', 'active')->get();
    }

    /**
     * Find campaign by ID (within current org only)
     */
    public function find(string $campaignId): ?Campaign
    {
        // ✅ RLS ensures we can only find campaigns in current org
        return Campaign::find($campaignId);
    }
}
```

**Benefits:**
- Repository methods don't need org_id parameter
- Impossible to accidentally query wrong organization
- Database enforces data isolation

---

## 🛠️ Helper Methods

### Check if RLS Context is Set

```php
/**
 * Get current organization ID from RLS context
 *
 * @return string|null UUID of current org, or null if not set
 */
function getCurrentOrgContext(): ?string
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
 * Get current user ID from RLS context
 */
function getCurrentUserContext(): ?string
{
    try {
        $result = DB::selectOne(
            "SELECT current_setting('app.current_user_id', true) as user_id"
        );

        return $result?->user_id;
    } catch (\Exception $e) {
        return null;
    }
}
```

**Usage:**
```php
public function criticalOperation()
{
    if (!getCurrentOrgContext()) {
        throw new \RuntimeException('Cannot perform operation: No org context set!');
    }

    // Safe to proceed
    $data = Model::all();
}
```

---

## 🚨 Common Mistakes & Solutions

### Mistake #1: Passing org_id to Service Methods

```php
// ❌ WRONG
public function index(string $orgId)
{
    return $this->campaignService->getActive($orgId);
}

// ✅ CORRECT
public function index()
{
    return $this->campaignService->getActive();  // RLS handles org filtering
}
```

### Mistake #2: Using scopeForOrganization()

```php
// ❌ WRONG - Bypasses RLS
$campaigns = Campaign::forOrganization($orgId)->get();

// ✅ CORRECT - Let RLS filter
$campaigns = Campaign::all();
```

**Note:** `scopeForOrganization()` now logs warnings when used. Use only in console commands where you're intentionally setting context per org.

### Mistake #3: Forgetting Context in Jobs

```php
// ❌ WRONG
class ProcessDataJob implements ShouldQueue
{
    public function handle()
    {
        $data = Model::all();  // ⚠️ Returns ALL orgs' data!
    }
}

// ✅ CORRECT
class ProcessDataJob implements ShouldQueue
{
    protected string $userId;
    protected string $orgId;

    public function handle()
    {
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $this->userId, $this->orgId
        ]);

        $data = Model::all();  // ✅ RLS filters to org
    }
}
```

### Mistake #4: Direct Table Queries

```php
// ❌ WRONG - Bypasses Eloquent and RLS!
$campaigns = DB::table('cmis.campaigns')
    ->where('org_id', $orgId)
    ->get();

// ✅ CORRECT - Use Eloquent (RLS policies apply)
$campaigns = Campaign::all();
```

**Exception:** Authorization checks in middleware can use direct queries (before context is set).

---

## 🧪 Testing RLS Compliance

### Test Template: Multi-Tenancy Isolation

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignMultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that RLS prevents cross-org data access
     */
    public function test_campaigns_isolated_by_organization()
    {
        // Arrange: Create two organizations
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        $user1 = User::factory()->create(['org_id' => $org1->org_id]);
        $user2 = User::factory()->create(['org_id' => $org2->org_id]);

        // Create 5 campaigns for org1, 3 for org2
        $org1Campaigns = Campaign::factory()->count(5)->create(['org_id' => $org1->org_id]);
        $org2Campaigns = Campaign::factory()->count(3)->create(['org_id' => $org2->org_id]);

        // Act: Set context for org1
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user1->id, $org1->org_id
        ]);

        // Assert: Only see org1's campaigns
        $visibleCampaigns = Campaign::all();

        $this->assertCount(5, $visibleCampaigns, 'Should only see 5 campaigns (org1)');
        $this->assertTrue(
            $visibleCampaigns->pluck('org_id')->unique()->first() === $org1->org_id,
            'All campaigns should belong to org1'
        );

        // Assert: Cannot access org2's campaigns by ID
        foreach ($org2Campaigns as $campaign) {
            $this->assertNull(
                Campaign::find($campaign->id),
                "Should not be able to find org2's campaign: {$campaign->id}"
            );
        }

        // Clean up context
        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Test service respects RLS context
     */
    public function test_service_respects_rls_context()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Campaign::factory()->count(5)->create(['org_id' => $org1->org_id]);
        Campaign::factory()->count(3)->create(['org_id' => $org2->org_id]);

        // Set context
        $user = User::factory()->create(['org_id' => $org1->org_id]);
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $user->id, $org1->org_id
        ]);

        // Test service
        $service = new CampaignService();
        $campaigns = $service->getActiveCampaigns();

        $this->assertCount(5, $campaigns, 'Service should respect RLS context');

        DB::statement('SELECT cmis.clear_transaction_context()');
    }
}
```

### Test Template: Job Context Setting

```php
public function test_job_sets_context_correctly()
{
    $org = Organization::factory()->create();
    $user = User::factory()->create(['org_id' => $org->org_id]);

    Campaign::factory()->count(3)->create(['org_id' => $org->org_id]);

    // Create and dispatch job
    $job = new SyncCampaignDataJob($user->id, $org->org_id);

    // Assert job completes without error
    $this->expectNotToPerformAssertions();  // Just verify no exceptions
    $job->handle();
}
```

---

## 📊 Migration Checklist

**Refactoring existing code to be RLS-compliant:**

- [ ] **Step 1:** Identify all `where('org_id')` usages
  ```bash
  grep -rn "where('org_id'" app/
  ```

- [ ] **Step 2:** For each occurrence, determine context:
  - Controller? → Remove org_id parameter and manual filtering
  - Service? → Remove org_id parameter and manual filtering
  - Job? → Add `init_transaction_context()` call
  - Console command? → Use `HandlesOrgContext` trait

- [ ] **Step 3:** Update method signatures
  ```php
  // Before
  public function getCampaigns(string $orgId): Collection

  // After
  public function getCampaigns(): Collection
  ```

- [ ] **Step 4:** Remove manual WHERE clauses
  ```php
  // Before
  return Campaign::where('org_id', $orgId)->get();

  // After
  return Campaign::all();  // RLS handles filtering
  ```

- [ ] **Step 5:** Add tests for multi-tenancy isolation

- [ ] **Step 6:** Monitor logs for warnings
  ```bash
  tail -f storage/logs/laravel.log | grep "bypasses RLS"
  ```

---

## 🎓 Training Resources

### Key Concepts

1. **Row-Level Security (RLS)**
   - PostgreSQL feature that filters rows based on session variables
   - Policy: `USING (org_id = current_setting('app.current_org_id')::uuid)`
   - Applied BEFORE query results are returned

2. **Session Variables**
   - `app.current_user_id` - Set by middleware/jobs
   - `app.current_org_id` - Set by middleware/jobs
   - Scoped to database connection

3. **Context Lifecycle**
   - HTTP: Middleware sets → Query → Middleware clears
   - Job: Job sets → Query → Job clears (or connection close)
   - Console: Command sets per org → Query → Clear between orgs

### Further Reading

- PostgreSQL RLS Docs: https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- CMIS Multi-Tenancy Architecture: `/docs/architecture/multi-tenancy.md`
- Context Awareness Audit: `/docs/active/analysis/context-awareness-audit-2025-11-23.md`

---

## ✅ Compliance Checklist

**For Code Reviews:**

- [ ] Controllers don't receive `$orgId` parameter
- [ ] Services don't receive `$orgId` parameter
- [ ] No `where('org_id')` clauses in controllers/services
- [ ] Jobs call `init_transaction_context()` before queries
- [ ] Console commands use `HandlesOrgContext` trait
- [ ] Tests verify multi-tenancy isolation
- [ ] Critical operations validate context is set

---

**Remember:**

> "If you're manually filtering by org_id in a controller or service, you're doing it wrong."
>
> **Let RLS do its job!**

---

**Last Updated:** 2025-11-23
**Maintainer:** CMIS Development Team
**Questions?** Check `/docs/active/analysis/context-awareness-audit-2025-11-23.md`
