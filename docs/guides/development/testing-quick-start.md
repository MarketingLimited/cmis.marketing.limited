# CMIS Testing Quick Start Guide

**Last Updated:** 2025-11-20
**For:** Development Team
**Purpose:** Get started with CMIS testing immediately

---

## Quick Start (5 Minutes)

### 1. Verify Test Environment

```bash
# Check PostgreSQL is running
service postgresql status

# Check database connection
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test -c "SELECT version();"

# Check composer dependencies
vendor/bin/phpunit --version
```

**Expected Output:**
```
PHPUnit 11.5.42 by Sebastian Bergmann and contributors.
```

### 2. Run Your First Test

```bash
# Run a single unit test
vendor/bin/phpunit tests/Unit/Models/Campaign/CampaignTest.php

# Run all unit tests (will take a few minutes)
vendor/bin/phpunit --testsuite=Unit

# Run feature tests
vendor/bin/phpunit --testsuite=Feature
```

### 3. Run Tests in Parallel (Fast!)

```bash
# First time: Setup parallel databases
for i in {1..15}; do
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres \
        -c "CREATE DATABASE IF NOT EXISTS cmis_test_$i;"
done

# Run tests in parallel (3-5x faster!)
./run-tests-parallel.sh --unit
```

---

## Writing Your First Test

### Step 1: Choose Test Type

| Test Type | Purpose | Location |
|-----------|---------|----------|
| **Unit** | Test single class/method | `tests/Unit/` |
| **Feature** | Test API endpoints | `tests/Feature/` |
| **Integration** | Test multi-component flows | `tests/Integration/` |

### Step 2: Copy a Template

#### Unit Test Template
```php
<?php

namespace Tests\Unit\Models\YourDomain;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\YourDomain\YourModel;

class YourModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_model()
    {
        // Setup: Create user with org
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        // Act: Create model
        $model = YourModel::create([
            'org_id' => $org->org_id,
            'name' => 'Test Model',
        ]);

        // Assert: Verify creation
        $this->assertNotNull($model);
        $this->assertEquals('Test Model', $model->name);
        $this->assertEquals($org->org_id, $model->org_id);
    }
}
```

#### Feature Test Template (API)
```php
<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\YourModel;

class YourAPITest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_resources()
    {
        // Setup: Create user with org and RLS context
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create test data
        $resource = YourModel::create([
            'org_id' => $org->org_id,
            'name' => 'Test Resource',
        ]);

        // Act: Make API request
        $response = $this->getJson("/api/your-resource/{$resource->id}");

        // Assert: Verify response
        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'name' => 'Test Resource',
                     ],
                 ]);
    }

    /** @test */
    public function it_enforces_org_isolation()
    {
        // Setup: Create two separate orgs
        $org1Setup = $this->createUserWithOrg();
        $org2Setup = $this->createUserWithOrg();

        // Create resource in org2
        $resource = YourModel::create([
            'org_id' => $org2Setup['org']->org_id,
            'name' => 'Org 2 Resource',
        ]);

        // Act: Try to access from org1
        $this->actingAsUserInOrg($org1Setup['user'], $org1Setup['org']);
        $response = $this->getJson("/api/your-resource/{$resource->id}");

        // Assert: Should be forbidden
        $this->assertContains($response->status(), [403, 404]);
    }
}
```

---

## Multi-Tenancy Testing (CRITICAL!)

### Always Use RLS Context in Tests

```php
// ❌ WRONG - No RLS context
public function it_creates_campaign()
{
    $campaign = Campaign::create(['name' => 'Test']);
    // This will fail or violate RLS policies!
}

// ✅ CORRECT - With RLS context
public function it_creates_campaign()
{
    $setup = $this->createUserWithOrg();
    $this->actingAsUserInOrg($setup['user'], $setup['org']);

    $campaign = Campaign::create([
        'org_id' => $setup['org']->org_id,
        'name' => 'Test',
    ]);

    $this->assertEquals($setup['org']->org_id, $campaign->org_id);
}
```

### Test Cross-Org Isolation

**ALWAYS add this test to API endpoint tests:**
```php
/** @test */
public function it_enforces_org_isolation()
{
    $org1Setup = $this->createUserWithOrg();
    $org2Setup = $this->createUserWithOrg();

    $org2Resource = ResourceModel::create([
        'org_id' => $org2Setup['org']->org_id,
        'name' => 'Org 2 Resource',
    ]);

    $this->actingAsUserInOrg($org1Setup['user'], $org1Setup['org']);

    $response = $this->getJson("/api/resource/{$org2Resource->id}");

    // MUST return 403 or 404
    $this->assertContains($response->status(), [403, 404]);
}
```

---

## Platform Integration Testing

### Always Mock External APIs

```php
use Illuminate\Support\Facades\Http;

/** @test */
public function it_syncs_meta_campaigns()
{
    // Setup: Mock Meta API
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                ['id' => '123', 'name' => 'Test Campaign'],
            ],
        ], 200),
    ]);

    $setup = $this->createUserWithOrg();
    $this->actingAsUserInOrg($setup['user'], $setup['org']);

    // Act: Trigger sync
    $service = app(MetaSyncService::class);
    $result = $service->syncCampaigns($setup['org']->org_id);

    // Assert: Verify sync worked
    $this->assertTrue($result->wasSuccessful());
    $this->assertCount(1, $result->campaigns);
}
```

### Test Webhook Signature Validation

```php
/** @test */
public function it_validates_webhook_signature()
{
    $setup = $this->createUserWithOrg();

    $payload = ['event' => 'campaign_updated'];
    $validSignature = hash_hmac('sha256', json_encode($payload), config('services.meta.app_secret'));

    $response = $this->postJson('/webhooks/meta', $payload, [
        'X-Hub-Signature-256' => 'sha256=' . $validSignature,
    ]);

    $response->assertStatus(200);
}

/** @test */
public function it_rejects_invalid_webhook_signature()
{
    $payload = ['event' => 'campaign_updated'];
    $invalidSignature = 'invalid_signature';

    $response = $this->postJson('/webhooks/meta', $payload, [
        'X-Hub-Signature-256' => 'sha256=' . $invalidSignature,
    ]);

    $response->assertStatus(403);
}
```

---

## Common Testing Patterns

### Testing JSON Responses

```php
// Basic structure
$response->assertStatus(200)
         ->assertJsonStructure([
             'data' => [
                 'id',
                 'name',
                 'status',
             ],
         ]);

// Exact values
$response->assertJson([
    'data' => [
        'name' => 'Test Campaign',
        'status' => 'active',
    ],
]);

// Specific path
$response->assertJsonPath('data.name', 'Test Campaign');

// Count items
$response->assertJsonCount(5, 'data');
```

### Testing Database State

```php
// Assert record exists
$this->assertDatabaseHas('cmis.campaigns', [
    'name' => 'Test Campaign',
    'org_id' => $org->org_id,
]);

// Assert record missing
$this->assertDatabaseMissing('cmis.campaigns', [
    'name' => 'Deleted Campaign',
]);

// Assert soft deleted
$this->assertSoftDeleted('cmis.campaigns', [
    'id' => $campaign->id,
]);
```

### Testing Validation

```php
/** @test */
public function it_requires_name_field()
{
    $setup = $this->createUserWithOrg();
    $this->actingAsUserInOrg($setup['user'], $setup['org']);

    $response = $this->postJson('/api/campaigns', [
        // Missing 'name' field
        'status' => 'active',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
}
```

---

## Debugging Failed Tests

### View Detailed Error Output

```bash
# Run with verbose output
vendor/bin/phpunit --testdox-text build/testdox.txt

# View failed test details
vendor/bin/phpunit --stop-on-failure

# Run single test for debugging
vendor/bin/phpunit --filter=it_can_create_campaign
```

### Common Issues & Solutions

#### Issue: "Target class [env] does not exist"
**Solution:** Configuration file using `app()` during config load
```php
// ❌ WRONG
'value' => env('KEY', app()->someMethod()),

// ✅ CORRECT
'value' => env('KEY', env('OTHER_KEY')),
```

#### Issue: "Column 'org_id' cannot be null"
**Solution:** Missing RLS context
```php
// Add before creating models
$setup = $this->createUserWithOrg();
$this->actingAsUserInOrg($setup['user'], $setup['org']);
```

#### Issue: "Class 'Database\Factories\YourModelFactory' not found"
**Solution:** Factory doesn't exist yet
```bash
# Create factory
php artisan make:factory YourModelFactory --model=YourModel
```

#### Issue: Test passes locally but fails in CI
**Solution:** Check database state isolation
```php
// Always use RefreshDatabase
class YourTest extends TestCase
{
    use RefreshDatabase; // This is critical!
}
```

---

## Running Tests in CI/CD

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: pgvector/pgvector:pg16
        env:
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_pgsql, pgsql, pcov

      - name: Install Dependencies
        run: composer install --no-interaction

      - name: Create Test Databases
        run: |
          for i in {1..15}; do
            psql -h localhost -U postgres -c "CREATE DATABASE cmis_test_$i;"
          done

      - name: Run Tests
        run: ./run-tests-parallel.sh
```

---

## Performance Tips

### Use Parallel Testing
```bash
# Sequential (slow): ~7 minutes
vendor/bin/phpunit

# Parallel (fast): ~1.7 minutes
./run-tests-parallel.sh

# 4x speed improvement!
```

### Optimize Database Operations
```php
// ❌ SLOW - N+1 queries
$campaigns = Campaign::all();
foreach ($campaigns as $campaign) {
    echo $campaign->org->name;
}

// ✅ FAST - Eager loading
$campaigns = Campaign::with('org')->get();
foreach ($campaigns as $campaign) {
    echo $campaign->org->name;
}
```

### Use Factories for Test Data
```php
// ❌ SLOW - Manual creation
$user = User::create([
    'user_id' => Str::uuid(),
    'name' => 'Test User',
    'email' => 'test@example.com',
    // ... 10 more fields
]);

// ✅ FAST - Factory
$user = User::factory()->create();
```

---

## Test Coverage Goals

### Current Status
- Total Tests: 1,408 test assertions
- Pass Rate: ~33.4% (improving continuously)
- RLS Coverage: 13% (needs improvement)

### Target Goals
- Pass Rate: 90%+
- RLS Coverage: 75%+
- Code Coverage: 70%+
- Execution Time: <2 minutes (parallel)

---

## Resources

- **Full Assessment:** `docs/active/analysis/testing-assessment-2025-11-20.md`
- **PHPUnit Docs:** https://phpunit.de/manual/11.5/en/
- **Laravel Testing:** https://laravel.com/docs/11.x/testing
- **Test Base Class:** `tests/TestCase.php`
- **Parallel Script:** `./run-tests-parallel.sh`

---

## Getting Help

### Internal Resources
1. Read the full testing assessment
2. Check existing test files for patterns
3. Review `tests/TestCase.php` for helper methods

### Common Questions

**Q: How do I test multi-tenancy?**
A: Always use `actingAsUserInOrg($user, $org)` and test cross-org isolation.

**Q: How do I mock external APIs?**
A: Use `Http::fake([...])` before making requests.

**Q: Why are my tests slow?**
A: Use parallel testing with `./run-tests-parallel.sh`

**Q: How do I debug a failing test?**
A: Run with `--filter=testName` and `--stop-on-failure`

---

**Happy Testing! 🧪**
