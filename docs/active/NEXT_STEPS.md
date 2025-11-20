# Next Steps - Laravel Test Suite Fixes

**Date:** 2025-11-20
**Current Status:** 33.4% pass rate (657/1,969 tests) - Best result
**Target:** 40% → 60%+ pass rate
**Branch:** `claude/test-suite-fixes-20251120-01BtPQR8xX4ZWbQ5unCiNNx4`

---

## Immediate Priority: Fix Migration Blocker (30-60 minutes)

### Problem
Migration `database/migrations/2025_11_14_000003_create_views.php` fails with:
```
SQLSTATE[42P01]: relation "public.awareness_stages" does not exist
```

This blocks 39 additional migrations and affects 300-500 tests.

### Solution

**Option 1: Comment Out Legacy Views (Quick Fix)**
```bash
nano database/migrations/2025_11_14_000003_create_views.php

# Comment out or wrap in try-catch:
# - awareness_stages_view
# - Any other views referencing public.* tables that don't exist

php artisan migrate --force
php artisan test --compact
# Expected: 45-50% pass rate (885-985 tests)
```

**Option 2: Refactor Views to Use Current Schema (Better Fix)**
```php
// Change FROM public.awareness_stages
// To FROM cmis.awareness_stages or cmis_meta.awareness_stages

DB::statement("
    CREATE OR REPLACE VIEW cmis.awareness_stages_view AS
    SELECT * FROM cmis.awareness_stages;  -- Use actual schema
");
```

### Expected Result
- ✅ All 39 pending migrations complete
- ✅ 45-50% pass rate (EXCEEDS 40% target!)
- ✅ +228-328 tests immediately fixed

---

## Phase 2: Create Missing Factories (30 minutes)

Create these 5 factories to unlock 50-100 tests:

```bash
php artisan make:factory NotificationFactory
php artisan make:factory WebhookFactory
php artisan make:factory LeadFactory
php artisan make:factory SettingsFactory
php artisan make:factory ReportFactory
```

**Templates in:** `docs/active/reports/test-suite-40-percent-assessment-2025-11-20.md`

### Expected Result
- ✅ 52-57% pass rate
- ✅ +99-148 tests

---

## Phase 3: Fix Controller RLS Context (1-2 hours)

### Pattern to Apply

Add to every controller method that queries the database:

```php
use Illuminate\Support\Facades\DB;

public function index(Request $request)
{
    $user = $request->user();
    $orgId = $this->resolveOrgId($request);

    // Initialize RLS context
    DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
        $user->user_id,
        $orgId
    ]);

    // Now query normally - RLS will filter automatically
    $campaigns = Campaign::paginate(15);

    return response()->json(['data' => $campaigns]);
}
```

### Controllers to Fix
1. WebhookController
2. IntegrationController
3. SettingsController
4. LeadController
5. NotificationController
6. AssetController (expand)
7. AnalyticsController (expand)

### Expected Result
- ✅ 60-65% pass rate
- ✅ +257-377 tests

---

## Phase 4: Implement Missing Routes (2-3 hours)

Use the **convenience route pattern** established in:
- `app/Http/Controllers/DashboardController.php` (100% passing)
- `app/Http/Controllers/Campaigns/CampaignController.php` (91.7% passing)

### Controllers Needing Routes
1. **WebhookController** (20-30 tests)
   - POST /webhooks/meta
   - POST /webhooks/google
   - POST /webhooks/tiktok
   - POST /webhooks/linkedin

2. **IntegrationController** (15-25 tests)
   - GET /integrations
   - POST /integrations/connect
   - DELETE /integrations/{id}

3. **SettingsController** (15-20 tests)
   - GET /settings
   - PUT /settings
   - GET /settings/notifications
   - PUT /settings/notifications

4. **LeadController** (15-25 tests)
   - GET /leads
   - POST /leads
   - PUT /leads/{id}
   - GET /leads/{id}/score

### Expected Result
- ✅ 70-75% pass rate
- ✅ +197-355 tests

---

## Success Metrics

| Phase | Time | Pass Rate | Tests Gained | Cumulative |
|-------|------|-----------|--------------|------------|
| Baseline | - | 33.4% | - | 657 |
| **1. Fix Migrations** | 30-60 min | **45-50%** | **+228-328** | **885-985** |
| 2. Create Factories | 30 min | 52-57% | +99-148 | 984-1,133 |
| 3. Fix RLS Context | 1-2 hours | 60-65% | +257-377 | 1,181-1,280 |
| 4. Implement Routes | 2-3 hours | 70-75% | +197-355 | 1,378-1,477 |
| **Total** | **4-6 hours** | **70-75%** | **+721-870** | **1,378-1,477** |

---

## Commands Reference

### Run Full Test Suite
```bash
php artisan test --compact
```

### Run Specific Test Suites (Faster)
```bash
php artisan test --testsuite=Unit          # 40% pass rate, fast
php artisan test --testsuite=Feature       # Slower, needs DB
php artisan test --filter=Controller       # Just controllers
php artisan test --filter=Dashboard        # Specific controller
```

### Check Migration Status
```bash
php artisan migrate:status
```

### Reset Database (If Needed)
```bash
php artisan migrate:fresh --seed
```

---

## Production Deployment

**Before deploying to cmis-test.kazaaz.com:**

1. ✅ All migrations must complete successfully locally
2. ✅ Test suite at least 60% passing
3. ✅ Run migrations on production:
   ```bash
   ssh cmis-test.kazaaz.com
   cd /home/cmis-test/public_html
   php artisan migrate --force
   php artisan optimize:clear
   ```

---

## Documentation

**Detailed Guides:**
- `docs/active/reports/test-suite-40-percent-assessment-2025-11-20.md` - Complete analysis
- `docs/active/reports/session-summary-2025-11-20.md` - Quick reference
- `docs/active/reports/test-fixes-report-2025-11-20.md` - Changelog

**Code Patterns:**
- Dashboard: `app/Http/Controllers/DashboardController.php`
- Campaign: `app/Http/Controllers/Campaigns/CampaignController.php`
- Factories: `database/factories/*/`

---

## Git Workflow

```bash
# Continue on existing branch
git checkout claude/test-suite-fixes-20251120-01BtPQR8xX4ZWbQ5unCiNNx4

# Make changes
git add -A
git commit -m "fix: [description]"
git push

# Commit every 30-60 minutes or after each phase
```

---

## Contact & Support

**Questions?** Check the documentation first:
- CLAUDE.md - Project guidelines
- .claude/knowledge/ - Multi-tenancy patterns
- docs/active/reports/ - Session reports

**Branch:** `claude/test-suite-fixes-20251120-01BtPQR8xX4ZWbQ5unCiNNx4`
**Status:** Ready for Phase 1 (Migration fix)
**Next Session:** Start with migration blocker fix

---

**The path to 70%+ is clear. Fix migrations first, everything else follows.** 🚀
