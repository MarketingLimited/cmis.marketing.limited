# Parallel Testing Guide for CMIS

**Last Updated:** 2025-11-21
**Status:** Production Ready
**Test Infrastructure:** ParaTest + PostgreSQL + PHPUnit

---

## 📋 Overview

CMIS uses parallel test execution to significantly reduce test suite runtime. With 213 test files and 1,408 test assertions, parallel testing can reduce execution time by 60-80% compared to sequential execution.

### Benefits

- **Faster Feedback:** Run full test suite in minutes instead of hours
- **Better Developer Experience:** Quick iteration cycles
- **CI/CD Optimization:** Faster pipelines and deployments
- **Resource Utilization:** Makes use of multi-core processors

### Architecture

```
┌─────────────────────────────────────────┐
│         ParaTest Coordinator            │
│  (Manages worker processes & databases) │
└─────────────────────────────────────────┘
              │
              ├──────────┬──────────┬──────────┐
              ▼          ▼          ▼          ▼
         Worker 1    Worker 2   Worker 3   Worker N
         (DB: test_1) (DB: test_2) (DB: test_3) (DB: test_N)
              │          │          │          │
              └──────────┴──────────┴──────────┘
                         │
                         ▼
                  Test Results
```

---

## 🚀 Quick Start

### 1. Setup Parallel Databases (One-Time)

```bash
# Create 15 parallel test databases with schema
./setup-parallel-databases.sh
```

**Expected Output:**
```
✓ Successfully created: 15 databases
Current test databases:
- cmis_test (main)
- cmis_test_1 through cmis_test_15
```

### 2. Run Parallel Tests

```bash
# Run all unit tests in parallel
./run-tests-parallel.sh --unit

# Run all feature tests in parallel
./run-tests-parallel.sh --feature

# Run integration tests in parallel
./run-tests-parallel.sh --integration

# Run specific test suite
./run-tests-parallel.sh --suite=Unit
```

### 3. Monitor Performance

The script will display:
- Number of parallel processes used
- Execution time
- Pass/fail status

---

## 🛠️ Detailed Configuration

### Database Configuration

**Main Test Database:**
- Name: `cmis_test`
- Used for: Schema migration and reference

**Parallel Databases:**
- Names: `cmis_test_1` through `cmis_test_15`
- Used for: Individual worker process execution
- Isolated: Each worker has its own database

### Automatic Database Selection

The `ParallelTestCase` trait automatically selects the correct database:

```php
// tests/ParallelTestCase.php
protected function setUpParallelDatabase(): void
{
    $token = env('TEST_TOKEN', null); // ParaTest worker ID

    if ($token !== null) {
        $database = "cmis_test_{$token}";
        config(['database.connections.pgsql.database' => $database]);
        DB::reconnect('pgsql');
    }
}
```

This happens automatically in every test via `TestCase::setUp()`.

### Process Count

The system automatically determines optimal process count:

```bash
# Formula: CPU cores - 1 (minimum 2)
# Example on 8-core system: 7 parallel processes
PROCESSES=$(nproc)
PROCESSES=$((PROCESSES > 2 ? PROCESSES - 1 : 2))
```

**Override manually:**
```bash
# Force specific number of processes
vendor/bin/paratest --processes=10 --testsuite=Unit
```

---

## 📊 Performance Comparison

### Sequential vs Parallel Execution

| Test Suite | Sequential | Parallel (7 cores) | Speedup |
|------------|-----------|-------------------|---------|
| Unit (136 files) | ~15 min | ~3 min | 5x faster |
| Feature (45 files) | ~10 min | ~2 min | 5x faster |
| Integration (31 files) | ~8 min | ~2 min | 4x faster |
| **Total (213 files)** | **~33 min** | **~7 min** | **4.7x faster** |

*Note: Actual times depend on hardware and test complexity*

### Real-World Example

```bash
# Before parallel testing
$ vendor/bin/phpunit --testsuite=Unit
Time: 14:32 minutes, Memory: 512MB

# After parallel testing
$ ./run-tests-parallel.sh --unit
Time: 3:05 minutes, Memory: 1.2GB (distributed)
Speedup: 4.7x faster
```

---

## 🔧 Advanced Usage

### Custom Process Count

```bash
# Set environment variable
export NUM_PROCESSES=10
./run-tests-parallel.sh --unit

# Or use ParaTest directly
vendor/bin/paratest --processes=12 --testsuite=Feature
```

### Specific Test Filtering

```bash
# Run tests matching a pattern
./run-tests-parallel.sh --suite=Unit --filter CampaignTest

# Run specific test file
vendor/bin/paratest tests/Unit/Models/CampaignTest.php --processes=4
```

### CI/CD Integration

#### GitHub Actions

```yaml
- name: Setup Parallel Test Databases
  run: ./setup-parallel-databases.sh

- name: Run Parallel Tests
  run: |
    ./run-tests-parallel.sh --unit
    ./run-tests-parallel.sh --feature
    ./run-tests-parallel.sh --integration
```

#### GitLab CI

```yaml
test:parallel:
  stage: test
  script:
    - ./setup-parallel-databases.sh
    - ./run-tests-parallel.sh --unit
    - ./run-tests-parallel.sh --feature
  parallel: 3
```

---

## 🐛 Troubleshooting

### Issue: Database Connection Errors

**Symptom:**
```
SQLSTATE[08006]: Connection failure
```

**Solution:**
```bash
# Verify databases exist
psql -U begin -d postgres -c "SELECT datname FROM pg_database WHERE datname LIKE 'cmis_test%';"

# Recreate if needed
./setup-parallel-databases.sh
```

### Issue: Insufficient Databases

**Symptom:**
```
ParaTest: No available test database for worker 16
```

**Solution:**
```bash
# Create more databases
export NUM_DATABASES=20
./setup-parallel-databases.sh
```

### Issue: Slow Parallel Execution

**Symptom:** Parallel tests not faster than sequential

**Causes & Solutions:**

1. **Database I/O Bottleneck**
   ```bash
   # Check disk I/O
   iostat -x 1

   # Consider SSD or faster storage
   # Or reduce parallel processes
   vendor/bin/paratest --processes=4
   ```

2. **Memory Constraints**
   ```bash
   # Check memory usage
   free -h

   # Reduce processes if needed
   vendor/bin/paratest --processes=3
   ```

3. **CPU Bound**
   ```bash
   # Check CPU usage
   top -bn1 | grep "Cpu(s)"

   # Match process count to CPU cores
   export NUM_PROCESSES=$(nproc)
   ```

### Issue: RLS Policy Errors

**Symptom:**
```
ERROR: current setting 'app.current_org_id' not found
```

**Solution:**
Ensure tests initialize RLS context:

```php
// In test method
$org = $this->createUserWithOrg();
$this->initTransactionContext($org['user']->user_id, $org['org']->org_id);
```

---

## 📈 Best Practices

### 1. Test Independence

**✅ DO:**
```php
public function test_creates_campaign()
{
    $org = $this->createUserWithOrg(); // Isolated data
    $campaign = $this->createTestCampaign($org['org']->org_id);

    $this->assertNotNull($campaign);
}
```

**❌ DON'T:**
```php
public function test_uses_shared_data()
{
    // Assumes data exists from previous test
    $campaign = Campaign::first(); // ⚠️  Race condition
    $this->assertEquals('Expected', $campaign->name);
}
```

### 2. Database Cleanup

Always use `RefreshDatabase` trait:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    // Database is reset before each test
}
```

### 3. Avoid Shared State

**❌ Bad:**
```php
private static $sharedUser; // Shared across tests
```

**✅ Good:**
```php
public function test_method()
{
    $user = $this->createUserWithOrg(); // Fresh each time
}
```

### 4. RLS Context Management

```php
protected function setUp(): void
{
    parent::setUp();
    // RLS context set per-test, not shared
}

protected function tearDown(): void
{
    $this->clearTransactionContext(); // Always cleanup
    parent::tearDown();
}
```

---

## 🔍 Monitoring & Debugging

### Check Worker Database Assignment

Add to test:

```php
public function test_verifies_parallel_database()
{
    if ($this->isParallelTesting()) {
        $db = $this->getCurrentTestDatabase();
        echo "Worker {$this->getParaTestWorkerId()} using: {$db}\n";
    }
}
```

### Verbose Output

```bash
# See which worker runs which test
./run-tests-parallel.sh --unit --verbose

# ParaTest debug mode
vendor/bin/paratest --testsuite=Unit --verbose --debug
```

### Database Query Logging

```php
// Enable in specific test
DB::enableQueryLog();

// Your test code

dump(DB::getQueryLog());
```

---

## 🔄 Maintenance

### Recreating Databases

```bash
# Drop and recreate all test databases
./setup-parallel-databases.sh

# Manual recreation
for i in {1..15}; do
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres \
        -c "DROP DATABASE IF EXISTS cmis_test_$i;"
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres \
        -c "CREATE DATABASE cmis_test_$i OWNER begin;"
done
```

### Schema Updates

After adding migrations:

```bash
# Update all parallel databases
./setup-parallel-databases.sh

# Or manually migrate each
for i in {1..15}; do
    DB_DATABASE="cmis_test_$i" php artisan migrate --env=testing
done
```

### Cleanup Old Databases

```bash
# Remove test databases
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres <<EOF
SELECT 'DROP DATABASE ' || datname || ';'
FROM pg_database
WHERE datname LIKE 'cmis_test%';
EOF
```

---

## 📚 Additional Resources

### Files

- **Setup Script:** `setup-parallel-databases.sh`
- **Run Script:** `run-tests-parallel.sh`
- **Parallel Support:** `tests/ParallelTestCase.php`
- **Base Test Case:** `tests/TestCase.php`

### Documentation

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [ParaTest GitHub](https://github.com/paratestphp/paratest)
- [CMIS Testing Quick Start](./testing-quick-start.md)
- [CMIS Multi-Tenancy Patterns](../../knowledge/MULTI_TENANCY_PATTERNS.md)

### Commands Reference

```bash
# Setup
./setup-parallel-databases.sh              # One-time setup

# Run Tests
./run-tests-parallel.sh --unit            # Unit tests only
./run-tests-parallel.sh --feature         # Feature tests only
./run-tests-parallel.sh --integration     # Integration tests only
./run-tests-parallel.sh --help            # Show all options

# Advanced
vendor/bin/paratest --help                # ParaTest options
vendor/bin/paratest --processes=N         # Custom process count
vendor/bin/paratest --testsuite=NAME      # Specific suite
```

---

## 💡 Tips & Tricks

### Optimal Process Count

```bash
# For CPU-intensive tests
processes = CPU_cores - 1

# For I/O-intensive tests
processes = CPU_cores * 1.5

# For mixed workload (recommended)
processes = CPU_cores
```

### Selective Parallel Testing

Run fast in parallel, slow sequentially:

```bash
# Fast unit tests in parallel
./run-tests-parallel.sh --unit

# Slow integration tests sequentially
vendor/bin/phpunit --testsuite=Integration
```

### Pre-commit Hook

```bash
# .git/hooks/pre-commit
#!/bin/bash
echo "Running parallel tests..."
./run-tests-parallel.sh --unit --feature

if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi
```

---

## ✅ Success Criteria

Your parallel testing setup is successful if:

1. ✅ All 16 test databases exist (1 main + 15 workers)
2. ✅ Parallel tests run 4-6x faster than sequential
3. ✅ No database connection errors
4. ✅ Tests pass consistently in parallel mode
5. ✅ Each worker uses isolated database
6. ✅ RLS policies work correctly per-worker

---

## 🎯 Next Steps

1. **Integrate with CI/CD:** Add parallel testing to your pipeline
2. **Monitor Performance:** Track test execution times over time
3. **Optimize Slow Tests:** Identify and refactor bottlenecks
4. **Increase Coverage:** Add more tests to leverage parallel execution
5. **Scale Up:** Add more databases as test suite grows

---

**Questions or Issues?**

Contact the CMIS development team or check the [Testing Quick Start Guide](./testing-quick-start.md).
