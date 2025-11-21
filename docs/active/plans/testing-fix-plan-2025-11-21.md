# CMIS Testing Fix Plan - Immediate Actions

**Date:** 2025-11-21
**Status:** Ready to Execute
**Expected Time:** 30 minutes
**Expected Improvement:** 33.4% → 50-60% pass rate

---

## Critical Fix Applied ✅

**File:** `tests/Feature/Api/AiContentGenerationTest.php`
**Line:** 171
**Issue:** Syntax error - extra quote after `$media->status')`
**Status:** ✅ FIXED

```php
// BEFORE (BROKEN):
$this->assertEquals('completed', $media->status');

// AFTER (FIXED):
$this->assertEquals('completed', $media->status);
```

**Verification:**
```bash
php -l tests/Feature/Api/AiContentGenerationTest.php
# Output: No syntax errors detected ✅
```

---

## Remaining Infrastructure Fixes (25 minutes)

### 1. Fix Database Migration (15 min)

**Issue:** `public.markets` view migration failing

**Command:**
```bash
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test << 'EOF'
DROP VIEW IF EXISTS cmis.markets CASCADE;

CREATE VIEW cmis.markets AS
SELECT
    market_id,
    market_name,
    language_code,
    currency_code,
    text_direction,
    created_at,
    updated_at
FROM public.markets;
EOF
```

**Then re-run migrations:**
```bash
php artisan migrate:fresh --env=testing --force
```

### 2. Create Parallel Test Databases (10 min)

**Command:**
```bash
# Create 15 parallel test databases
for i in {1..15}; do
    echo "Creating cmis_test_$i..."
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres -c "CREATE DATABASE cmis_test_$i;"
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test_$i -c "CREATE EXTENSION IF NOT EXISTS vector;"
done
```

**Or use setup script:**
```bash
./setup-parallel-databases.sh
```

---

## Run Tests & Verify

### Step 1: Run Unit Tests
```bash
php artisan test --testsuite=Unit 2>&1 | tail -20
```

**Expected:**
- Some tests pass
- Some may fail (factory issues, missing columns)
- Pass rate: 50-60%

### Step 2: Run Feature Tests
```bash
php artisan test --testsuite=Feature 2>&1 | tail -20
```

**Expected:**
- API tests pass
- RLS tests pass
- Pass rate: 60-70%

### Step 3: Run Integration Tests
```bash
php artisan test --testsuite=Integration 2>&1 | tail -20
```

**Expected:**
- Most pass
- Pass rate: 70-80%

### Step 4: Get Overall Summary
```bash
php artisan test 2>&1 | grep "Tests:"
```

**Expected Output:**
```
Tests:  115 passed, 85 failed, 30 pending
```

**Pass Rate Calculation:**
- Passed: 115
- Total: 200
- Pass Rate: 57.5% ✅ (improved from 33.4%)

---

## Quick Commands Reference

```bash
# Fix markets view
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test -c "DROP VIEW IF EXISTS cmis.markets CASCADE;"

# Re-run migrations
php artisan migrate:fresh --env=testing --force

# Create parallel databases
for i in {1..15}; do
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres -c "CREATE DATABASE cmis_test_$i;"
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test_$i -c "CREATE EXTENSION IF NOT EXISTS vector;"
done

# Run all tests
php artisan test

# Run parallel tests (after setup)
./run-tests-parallel.sh
```

---

## Post-Fix Validation

### 1. Verify PostgreSQL Setup
```bash
# Check role exists
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres -c "\du begin"

# Check databases exist
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres -c "\l" | grep cmis_test

# Should show:
# - cmis_test
# - cmis_test_1 through cmis_test_15
```

### 2. Verify Migrations
```bash
php artisan migrate:status --env=testing
```

**Expected:**
- All migrations show [Ran]
- No pending migrations

### 3. Verify Test Configuration
```bash
cat phpunit.xml | grep -A 5 "DB_DATABASE"
```

**Expected:**
```xml
<env name="DB_DATABASE" value="cmis_test"/>
<env name="DB_USERNAME" value="begin"/>
<env name="DB_PASSWORD" value="123@Marketing@321"/>
```

---

## Next Steps (After Basic Fixes)

### Phase 2: Test Improvements (2 hours)

**Objective:** Reach 65-75% pass rate

**Actions:**
1. Fix factory configuration issues
2. Update tests with RLS context problems
3. Fix migration-dependent test failures
4. Verify all repository tests pass

**See:** `docs/active/analysis/testing-audit-2025-11-21.md` Section 10

### Phase 3: Coverage Expansion (4 hours)

**Objective:** Reach 70-80% pass rate

**Actions:**
1. Create missing factories (Org, Role, ContentPlan, etc.)
2. Add missing repository tests
3. Add missing service tests
4. Expand edge case coverage

**See:** `docs/active/analysis/testing-audit-2025-11-21.md` Section 10

---

## Troubleshooting

### Issue: Tests still fail after syntax fix

**Check:**
```bash
php artisan test --filter=AiContentGenerationTest
```

**If fails:**
- Check database connection
- Verify migrations ran successfully
- Check for other syntax errors

### Issue: Cannot connect to database

**Check:**
```bash
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test -c "SELECT 1;"
```

**If fails:**
- Verify PostgreSQL is running: `service postgresql status`
- Verify role exists: `psql -U postgres -c "\du"`
- Check pg_hba.conf authentication

### Issue: Migration fails

**Check:**
```bash
php artisan migrate:status --env=testing
```

**If migrations pending:**
```bash
php artisan migrate --env=testing --force
```

**If specific migration fails:**
- Check error message
- Fix migration file
- Run `migrate:fresh --force`

---

## Success Criteria

- ✅ Syntax error fixed
- ✅ All databases created
- ✅ All migrations successful
- ✅ Pass rate > 50%
- ✅ No infrastructure errors

**Current Progress:**
- [x] PostgreSQL running
- [x] Role 'begin' created
- [x] Test database created
- [x] pgvector extension installed
- [x] Syntax error fixed
- [ ] Migrations completed (pending markets view fix)
- [ ] Parallel databases created (pending)
- [ ] Tests running successfully (pending)

---

**Next Update:** After running fixes (expected: 2025-11-21 EOD)
**Target Pass Rate:** 50-60% (Phase 1), 65-75% (Phase 2), 80%+ (Phase 3-4)

**Contact:** Testing & QA AI Agent
**Reference:** `docs/active/analysis/testing-audit-2025-11-21.md`
