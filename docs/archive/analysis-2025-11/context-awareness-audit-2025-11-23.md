# CMIS Context Awareness & Multi-Tenancy Audit Report

**Date:** 2025-11-23
**Audit Type:** Comprehensive Context Awareness & RLS Compliance
**Auditor:** CMIS Context Awareness Agent
**Status:** ⚠️ CRITICAL ISSUES IDENTIFIED - IMMEDIATE ACTION REQUIRED

---

## 📋 Executive Summary

This audit identified **critical multi-tenancy and context awareness issues** in the CMIS codebase that pose security risks and architectural inconsistencies.

### Severity Breakdown

| Severity | Count | Description |
|----------|-------|-------------|
| 🔴 **CRITICAL** | 3 | Security vulnerabilities, data leakage risks |
| 🟠 **HIGH** | 136+ | RLS bypass patterns in services/controllers |
| 🟡 **MEDIUM** | 4 | Deprecated middleware still in use |
| 🟢 **LOW** | 2 | Documentation inconsistencies |

### Key Findings

1. ✅ **FIXED**: Routes using deprecated `set.db.context` middleware → Changed to `org.context`
2. ✅ **FIXED**: `ResolveActiveOrg` middleware using incorrect context initialization
3. ✅ **FIXED**: Added deprecation warnings to `HasOrganization::scopeForOrganization()`
4. ⚠️ **PENDING**: 136+ files bypassing RLS with hardcoded `where('org_id')` filtering
5. ⚠️ **PENDING**: Service layer needs refactoring to respect RLS context

---

## 🔴 CRITICAL ISSUES (Severity: CRITICAL)

### Issue #1: Deprecated Middleware in Production Routes

**Status:** ✅ FIXED

**Description:**
Main API routes (`routes/api.php` line 182) were using deprecated `set.db.context` middleware instead of the canonical `org.context` middleware.

**Security Impact:**
- Potential race conditions between multiple context middleware
- Inconsistent context initialization
- Risk of context not being properly cleared

**Fix Applied:**
```php
// BEFORE (routes/api.php:182)
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])

// AFTER
Route::middleware(['auth:sanctum', 'validate.org.access', 'org.context'])
```

**Files Modified:**
- `/home/user/cmis.marketing.limited/routes/api.php` (line 182)

---

### Issue #2: ResolveActiveOrg Middleware Incorrect Context Initialization

**Status:** ✅ FIXED

**Description:**
The `ResolveActiveOrg` middleware was checking for a PHP function `init_transaction_context()` which doesn't exist (it's a PostgreSQL function), then falling back to incorrect `set_config()` call.

**Security Impact:**
- Context not being properly initialized with user_id
- PostgreSQL RLS policies may not receive correct parameters
- Potential for partial context setup (org_id without user_id)

**Code Issues:**
```php
// BEFORE (lines 73-82)
if (function_exists('init_transaction_context')) {  // ❌ Always false - PG function
    init_transaction_context($activeOrgId);
} else {
    \DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$activeOrgId]);  // ❌ Missing user_id
}
```

**Fix Applied:**
```php
// AFTER
try {
    \DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $user->id,
        $activeOrgId
    ]);

    \Log::debug('ResolveActiveOrg: RLS context set successfully', [
        'user_id' => $user->id,
        'org_id' => $activeOrgId
    ]);
} catch (\Exception $e) {
    \Log::error('ResolveActiveOrg: Failed to set RLS context', [
        'user_id' => $user->id,
        'org_id' => $activeOrgId,
        'error' => $e->getMessage()
    ]);

    return response()->json([
        'error' => 'Failed to initialize context',
        'message' => 'An error occurred while setting up your organization context.'
    ], 500);
}
```

**Files Modified:**
- `/home/user/cmis.marketing.limited/app/Http/Middleware/ResolveActiveOrg.php`

**Improvements:**
- ✅ Properly calls `cmis.init_transaction_context(user_id, org_id)`
- ✅ Added proper error handling with user-friendly messages
- ✅ Added `terminate()` method to clean up context after response
- ✅ Added logging for debugging

---

### Issue #3: HasOrganization Trait Encouraging RLS Bypass

**Status:** ✅ FIXED (Warnings Added)

**Description:**
The `HasOrganization` trait provides `scopeForOrganization()` method that encourages manual `where('org_id')` filtering, bypassing PostgreSQL RLS policies.

**Security Impact:**
- Developers inadvertently bypass RLS security layer
- Manual filtering adds complexity and potential for bugs
- Inconsistent multi-tenancy patterns across codebase

**Usage Analysis:**
- **Trait used by:** 99+ models
- **Scope usage found in:** 136+ service/controller files
- **Risk level:** HIGH (widespread pattern)

**Fix Applied:**
```php
/**
 * ⚠️ WARNING: This method BYPASSES Row-Level Security (RLS)!
 *
 * @deprecated Use RLS context instead of manual org filtering
 */
public function scopeForOrganization($query, string $orgId)
{
    // Log warning when this scope is used
    \Illuminate\Support\Facades\Log::warning(
        'HasOrganization::scopeForOrganization() called - this bypasses RLS. ' .
        'Consider using RLS context instead of manual org filtering.',
        [
            'model' => get_class($this),
            'org_id' => $orgId,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]
    );

    return $query->where('org_id', $orgId);
}
```

**Files Modified:**
- `/home/user/cmis.marketing.limited/app/Models/Concerns/HasOrganization.php`

**Developer Guidance Added:**
```php
// ✅ CORRECT - RLS filters automatically
$campaigns = Campaign::all();

// ❌ WRONG - Bypasses RLS
$campaigns = Campaign::forOrganization($orgId)->get();
```

---

## 🟠 HIGH PRIORITY ISSUES (Severity: HIGH)

### Issue #4: Widespread RLS Bypass in Services

**Status:** ⚠️ PENDING - REQUIRES REFACTORING

**Description:**
136+ files use manual `where('org_id', $orgId)` filtering instead of relying on RLS context. This bypasses the database-level security layer.

**Pattern Found:**
```php
// ❌ BYPASSING RLS - Found in 136+ files
$campaigns = Campaign::where('org_id', $orgId)->get();

// ✅ CORRECT - Let RLS handle filtering
$campaigns = Campaign::all();  // RLS automatically filters by current org
```

**Critical Files Requiring Immediate Attention:**

1. **Analytics Services** (15 instances)
   - `app/Services/Analytics/ReportGeneratorService.php:112`
   - `app/Services/Analytics/CustomMetricsService.php`
   - `app/Services/Analytics/RealTimeAnalyticsService.php`
   - `app/Services/Analytics/DataExportService.php`
   - `app/Services/Analytics/ROICalculationEngine.php`

2. **Automation Services** (10 instances)
   - `app/Services/Automation/CampaignLifecycleManager.php:108,161,256,314,395`
   - `app/Services/Automation/AutomatedBudgetAllocator.php:61,524`
   - `app/Services/Automation/CampaignOptimizationService.php`
   - `app/Services/Automation/AutomationRulesEngine.php`

3. **AI Services** (8 instances)
   - `app/Services/AI/PredictiveAnalyticsService.php:40`
   - `app/Services/AI/KnowledgeLearningService.php:21,625`
   - `app/Services/AI/AiQuotaService.php`

4. **Optimization Services** (6 instances)
   - `app/Services/Optimization/CreativeAnalyzer.php:18`
   - `app/Services/Optimization/AudienceAnalyzer.php:18`
   - `app/Services/Optimization/BudgetOptimizer.php`
   - `app/Services/Optimization/InsightGenerator.php`
   - `app/Services/Optimization/AttributionEngine.php`

5. **Platform & Social Services** (12 instances)
   - `app/Services/Platform/MetaPostsService.php`
   - `app/Services/Social/ContentCalendarService.php`
   - `app/Services/Social/PublishingService.php`
   - `app/Services/Social/SchedulingService.php`

6. **Controllers** (85+ instances)
   - Multiple controllers in `app/Http/Controllers/` directory
   - Pattern: Controllers receiving `$orgId` parameter and manually filtering

**Impact:**
- **Security:** Bypasses database-level multi-tenancy enforcement
- **Performance:** Adds unnecessary WHERE clauses (RLS handles this)
- **Maintenance:** Duplicated filtering logic across codebase
- **Bugs:** Potential for forgetting org_id filter leading to data leakage

**Recommended Fix Strategy:**

**Option A: Assume RLS Context (Preferred for Controller-Called Services)**
```php
// Service method called from authenticated routes
public function generateOrganizationReport(array $options = []): array
{
    // RLS context is already set by middleware
    // Query automatically filtered by org_id
    $campaigns = Campaign::whereBetween('start_date', [
        $options['start_date'],
        $options['end_date']
    ])->get();

    // No need to pass or filter by org_id - RLS handles it!
}
```

**Option B: Explicit Context Check (Safer, Defensive)**
```php
public function generateOrganizationReport(string $orgId, array $options = []): array
{
    // Check if RLS context matches expected org_id
    $currentOrgId = DB::selectOne(
        "SELECT current_setting('app.current_org_id', true) as org_id"
    )?->org_id;

    if ($currentOrgId !== $orgId) {
        throw new \RuntimeException(
            "RLS context mismatch. Expected org_id: {$orgId}, " .
            "Current context: " . ($currentOrgId ?? 'not set')
        );
    }

    // Context verified - query without manual org_id filtering
    $campaigns = Campaign::whereBetween('start_date', [...])->get();
}
```

**Option C: Set Context in Service (For Console Commands/Jobs)**
```php
public function generateOrganizationReport(string $orgId, array $options = []): array
{
    return DB::transaction(function () use ($orgId, $options) {
        // Set context if not already set (for console commands)
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            config('cmis.system_user_id'),
            $orgId
        ]);

        // Now RLS handles filtering
        $campaigns = Campaign::whereBetween('start_date', [...])->get();

        return $this->formatReport($campaigns, $options);
    });
}
```

---

## 🟡 MEDIUM PRIORITY ISSUES (Severity: MEDIUM)

### Issue #5: Multiple Deprecated Middleware Files

**Status:** ⚠️ PENDING - DEPRECATION WARNINGS IN PLACE

**Description:**
Four deprecated middleware classes still exist in codebase, marked as deprecated but not removed.

**Files:**
1. `app/Http/Middleware/SetRLSContext.php` - DEPRECATED (use `org.context`)
2. `app/Http/Middleware/SetOrgContextMiddleware.php` - DEPRECATED (use `org.context`)
3. `app/Http/Middleware/SetDatabaseContext.php` - DEPRECATED (use `org.context`)
4. All three replaced by: `app/Http/Middleware/SetOrganizationContext.php` (alias: `org.context`)

**Deprecation Warnings:**
All deprecated middleware log warnings when used:
```php
Log::warning(
    '⚠️  DEPRECATED MIDDLEWARE IN USE: SetRLSContext is deprecated. ' .
    'Please update your routes to use org.context middleware instead. ' .
    'Using multiple context middleware can cause race conditions and data leakage.',
    [
        'route' => $request->path(),
        'middleware' => 'SetRLSContext',
        'replacement' => 'SetOrganizationContext (alias: org.context)'
    ]
);
```

**Recommendation:**
- Monitor logs for deprecated middleware warnings
- If no warnings for 30 days, safe to remove files
- Update any remaining routes to use `org.context`

---

### Issue #6: ValidateOrgAccess Uses Manual Table Queries

**Status:** ℹ️ INFORMATIONAL - BY DESIGN

**Description:**
`ValidateOrgAccess` middleware uses direct table queries instead of Eloquent models.

**Code:**
```php
// Lines 46-51
$hasAccess = DB::table('cmis.user_orgs')
    ->where('user_id', $userId)
    ->where('org_id', $orgId)
    ->where('status', 'active')
    ->whereNull('deleted_at')
    ->exists();
```

**Analysis:**
This is actually **CORRECT** behavior because:
- Middleware runs BEFORE context is set
- RLS would prevent checking user's access to other orgs
- This is a pre-authentication check, not a data query

**No action required** - this is proper use of manual queries for authorization checks.

---

## ✅ GOOD PRACTICES FOUND

### 1. Jobs Properly Set Context

**Example: SyncMetaAdsJob** ✅
```php
public function handle(): void
{
    DB::transaction(function () {
        // ✅ CORRECT - Sets context for RLS
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            config('cmis.system_user_id'),
            $this->integration->org_id
        ]);

        $service = new MetaAdsService($this->integration);
        // ... rest of sync logic
    });
}
```

**Example: ProcessScheduledReportJob** ✅
```php
public function handle(EmailReportService $emailService): void
{
    $schedule = ScheduledReport::find($this->scheduleId);

    // ✅ CORRECT - Initialize RLS context
    DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
        $schedule->user_id,
        $schedule->org_id
    ]);

    $log = $emailService->sendScheduledReport($schedule);
}
```

**Jobs Audited:** ✅ 10+ jobs reviewed - all properly set context

---

### 2. Console Commands Use HandlesOrgContext Trait

**Trait: App\Console\Traits\HandlesOrgContext** ✅

Provides standardized context management for console commands:
```php
// ✅ EXCELLENT PATTERN
protected function executePerOrg(\Closure $callback, ?array $orgIds = null)
{
    $systemUser = User::where('email', 'system@cmis.app')->first();
    $orgs = Org::query()->whereNull('deleted_at')->get();

    foreach ($orgs as $org) {
        DB::transaction(function () use ($systemUser, $org, $callback) {
            // Set database context
            DB::statement(
                "SELECT cmis.init_transaction_context(?, ?)",
                [$systemUser->user_id, $org->org_id]
            );

            // Execute org-specific logic
            $callback($org);
        });
    }
}
```

**Usage:** ✅ Used in multiple sync commands

---

### 3. SetOrganizationContext Middleware (Canonical Implementation)

**File:** `app/Http/Middleware/SetOrganizationContext.php` ✅

**Excellent features:**
- ✅ Race condition detection
- ✅ UUID validation
- ✅ Context verification after initialization
- ✅ Proper cleanup in both `handle()` and `terminate()`
- ✅ Comprehensive logging
- ✅ User-friendly error messages

**Example:**
```php
// ✅ Detects race conditions
$existingContext = DB::selectOne(
    "SELECT current_setting('app.current_org_id', true) as org_id"
);

if ($existingContext && $existingContext->org_id) {
    Log::warning('Context already set - RACE CONDITION DETECTED');
    return response()->json([
        'error' => 'Multiple context middleware detected'
    ], 500);
}

// ✅ Initialize context
DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
    $user->id,
    $orgId
]);

// ✅ Verify it was set correctly
$currentOrg = DB::selectOne(
    "SELECT current_setting('app.current_org_id', true) as org_id"
);

if (!$currentOrg || $currentOrg->org_id !== $orgId) {
    return response()->json([
        'error' => 'Context initialization failed'
    ], 500);
}
```

---

## 📊 Middleware Usage Analysis

### Active Middleware

| Middleware | Alias | Purpose | Status |
|------------|-------|---------|--------|
| `SetOrganizationContext` | `org.context` | **PRIMARY** - Set RLS context | ✅ ACTIVE |
| `ResolveActiveOrg` | `resolve.active.org` | Auto-resolve user's active org | ✅ FIXED |
| `ValidateOrgAccess` | `validate.org.access` | Check user has org access | ✅ CORRECT |

### Deprecated Middleware (Keep for Backward Compatibility)

| Middleware | Alias | Replacement | Warnings |
|------------|-------|-------------|----------|
| `SetRLSContext` | `set.rls.context` | `org.context` | ⚠️ Logs warning |
| `SetDatabaseContext` | `set.db.context` | `org.context` | ⚠️ Logs warning |
| `SetOrgContextMiddleware` | `set.org.context` | `org.context` | ⚠️ Logs warning |

### Middleware Registration (bootstrap/app.php)

```php
$middleware->alias([
    // ✅ ACTIVE - Use these
    'org.context' => \App\Http\Middleware\SetOrganizationContext::class,
    'resolve.active.org' => \App\Http\Middleware\ResolveActiveOrg::class,
    'validate.org.access' => \App\Http\Middleware\ValidateOrgAccess::class,

    // ⚠️ DEPRECATED - Update routes to use 'org.context'
    'set.db.context' => \App\Http\Middleware\SetDatabaseContext::class,
    'set.rls.context' => \App\Http\Middleware\SetRLSContext::class,
    'set.org.context' => \App\Http\Middleware\SetOrgContextMiddleware::class,
]);
```

---

## 🎯 Recommended Action Plan

### Phase 1: Immediate (This PR) ✅ COMPLETED

- [x] Fix `routes/api.php` to use `org.context` instead of `set.db.context`
- [x] Fix `ResolveActiveOrg` middleware context initialization
- [x] Add deprecation warnings to `HasOrganization::scopeForOrganization()`
- [x] Create comprehensive audit documentation

### Phase 2: High Priority (Next Sprint)

- [ ] Audit all 136+ files with hardcoded `where('org_id')` filtering
- [ ] Refactor top 20 critical services (Analytics, Automation, AI)
- [ ] Create RLS context helper trait for services
- [ ] Update service layer patterns documentation

### Phase 3: Medium Priority (Following Sprint)

- [ ] Refactor remaining services to respect RLS
- [ ] Create comprehensive test suite for multi-tenancy
- [ ] Monitor and remove deprecated middleware (if unused)
- [ ] Update developer documentation with RLS best practices

### Phase 4: Long-term (Next Quarter)

- [ ] Implement automated RLS bypass detection (linting)
- [ ] Create PHPStan custom rules for org_id filtering detection
- [ ] Add CI/CD checks for multi-tenancy compliance
- [ ] Training session for developers on RLS patterns

---

## 🧪 Testing Recommendations

### 1. Multi-Tenancy Isolation Tests

**Critical Test:** Verify RLS prevents cross-org data access
```php
public function test_rls_prevents_cross_org_data_access()
{
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $user1 = User::factory()->create(['org_id' => $org1->org_id]);
    $campaign1 = Campaign::factory()->create(['org_id' => $org1->org_id]);

    $user2 = User::factory()->create(['org_id' => $org2->org_id]);
    $campaign2 = Campaign::factory()->create(['org_id' => $org2->org_id]);

    // Set context for org1
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $user1->id, $org1->org_id
    ]);

    // Should only see org1's campaign
    $campaigns = Campaign::all();
    $this->assertCount(1, $campaigns);
    $this->assertEquals($campaign1->id, $campaigns->first()->id);

    // Should NOT see org2's campaign
    $this->assertNull(Campaign::find($campaign2->id));
}
```

### 2. Context Middleware Tests

**Test Suite:**
```php
// Test context is set correctly
public function test_org_context_middleware_sets_context()

// Test race condition detection
public function test_org_context_detects_double_initialization()

// Test UUID validation
public function test_org_context_rejects_invalid_uuid()

// Test context cleanup
public function test_org_context_cleans_up_after_request()
```

### 3. Service Layer RLS Compliance Tests

**Pattern:**
```php
public function test_service_respects_rls_context()
{
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    Campaign::factory()->count(5)->create(['org_id' => $org1->org_id]);
    Campaign::factory()->count(3)->create(['org_id' => $org2->org_id]);

    // Set context for org1
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $systemUserId, $org1->org_id
    ]);

    $service = new ReportGeneratorService();
    $report = $service->generateOrganizationReport();

    // Should only include org1's 5 campaigns, not org2's 3
    $this->assertEquals(5, $report['summary']['total_campaigns']);
}
```

### 4. Background Job Context Tests

**Test Pattern:**
```php
public function test_job_sets_context_correctly()
{
    $integration = Integration::factory()->create();

    $job = new SyncMetaAdsJob($integration);
    $job->handle();

    // Verify context was set during job execution
    // (Check logs or side effects)
}
```

---

## 📖 Developer Guidelines

### Rule #1: NEVER Manually Filter by org_id in Controllers/Services

```php
// ❌ WRONG - Bypasses RLS
public function index(string $orgId)
{
    $campaigns = Campaign::where('org_id', $orgId)->get();
    return response()->json($campaigns);
}

// ✅ CORRECT - Let RLS handle filtering
public function index()
{
    // Middleware already set context
    $campaigns = Campaign::all();
    return response()->json($campaigns);
}
```

### Rule #2: Jobs MUST Set Context Before Queries

```php
// ✅ CORRECT
public function handle()
{
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $this->userId,
        $this->orgId
    ]);

    // Now queries are automatically filtered
    $data = Model::all();
}
```

### Rule #3: Console Commands Use HandlesOrgContext Trait

```php
use App\Console\Traits\HandlesOrgContext;

class SyncCommand extends Command
{
    use HandlesOrgContext;

    public function handle()
    {
        $this->executePerOrg(function ($org) {
            // Context already set for $org
            $this->syncOrgData($org);
        });
    }
}
```

### Rule #4: Validate Context in Critical Operations

```php
public function deleteAllCampaigns()
{
    // ⚠️ DANGEROUS OPERATION - Verify context is set
    $currentOrg = DB::selectOne(
        "SELECT current_setting('app.current_org_id', true) as org_id"
    )?->org_id;

    if (!$currentOrg) {
        throw new \RuntimeException('Cannot delete campaigns: No org context set!');
    }

    // RLS will only delete current org's campaigns
    Campaign::query()->delete();
}
```

---

## 🔍 Monitoring & Metrics

### Log Monitoring

**Search for deprecation warnings:**
```bash
# Find deprecated middleware usage
grep "DEPRECATED MIDDLEWARE IN USE" storage/logs/*.log

# Find RLS bypass warnings
grep "scopeForOrganization() called - this bypasses RLS" storage/logs/*.log
```

### Metrics to Track

1. **Deprecated Middleware Usage**
   - Count of log warnings per day
   - Unique routes using deprecated middleware
   - Target: 0 warnings within 30 days

2. **RLS Bypass Frequency**
   - `scopeForOrganization()` calls per day
   - Services still using manual org_id filtering
   - Target: Reduce by 80% within 60 days

3. **Multi-Tenancy Test Coverage**
   - % of models with RLS isolation tests
   - % of services with context compliance tests
   - Target: 100% critical paths tested

---

## 📎 Appendix

### A. PostgreSQL RLS Functions Reference

**init_transaction_context(user_id UUID, org_id UUID)**
- Sets `app.current_user_id` session variable
- Sets `app.current_org_id` session variable
- Used by RLS policies to filter queries

**clear_transaction_context()**
- Resets session variables
- Called in middleware `terminate()` methods

**get_current_org_id()**
- Returns current `app.current_org_id` value
- Used in RLS policy expressions

### B. Files Modified in This Audit

1. ✅ `/routes/api.php` - Line 182 (middleware updated)
2. ✅ `/app/Http/Middleware/ResolveActiveOrg.php` - Context initialization fixed
3. ✅ `/app/Models/Concerns/HasOrganization.php` - Deprecation warnings added

### C. Files Requiring Future Attention

**High Priority (20 files):**
- Analytics Services (5 files)
- Automation Services (5 files)
- AI Services (3 files)
- Optimization Services (4 files)
- Core Services (3 files)

**Medium Priority (30 files):**
- Platform Services
- Social Services
- Enterprise Services
- Dashboard Services

**Low Priority (86+ files):**
- Various controllers with minor RLS bypass patterns

**Full list available in:** `grep` output from audit

---

## ✅ Sign-Off

**Audit Completed By:** CMIS Context Awareness Agent
**Date:** 2025-11-23
**Fixes Applied:** 3 Critical Issues
**Issues Identified:** 136+ Files Requiring Attention
**Next Review:** After Phase 2 refactoring complete

**Recommendation:** Proceed with Phase 2 service layer refactoring immediately. The widespread RLS bypass pattern poses a security risk and should be addressed within the next sprint.

---

**End of Report**
