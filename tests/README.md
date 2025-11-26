# CMIS Test Suite - Fresh Start

**Date:** 2025-11-26
**Reason:** Major architectural refactoring made legacy tests obsolete

## What Happened

The original test suite (251 tests) was archived to `tests.archive/` because of significant changes to:
- Core application architecture
- Business logic flows
- Multi-tenancy implementation
- Database schema and patterns

## Preserved Infrastructure

The following test infrastructure was preserved for reuse:

### Base Test Cases
- `TestCase.php` - Standard Laravel test case
- `ParallelTestCase.php` - For parallel test execution

### Test Helpers
- `TestHelpers/DatabaseHelpers.php` - Database testing utilities

### Test Traits
- `Traits/CreatesTestData.php` - Test data generation
- `Traits/InteractsWithRLS.php` - RLS testing helpers
- `Traits/MocksExternalAPIs.php` - External API mocking
- `Traits/OptimizesTestPerformance.php` - Performance optimization

## Directory Structure

```
tests/
├── Unit/           # Unit tests for services, repositories, models
├── Feature/        # Feature tests for API endpoints, workflows
├── Integration/    # Integration tests for platform connections
├── Traits/         # Reusable test traits
├── TestHelpers/    # Test helper classes
└── README.md       # This file
```

## Getting Started with New Tests

### 1. Example Unit Test

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\InteractsWithRLS;

class CampaignServiceTest extends TestCase
{
    use CreatesTestData, InteractsWithRLS;

    public function test_creates_campaign_with_rls_context()
    {
        $org = $this->createTestOrganization();
        $this->setRLSContext($org->id);

        $service = app(CampaignService::class);
        $campaign = $service->create([
            'name' => 'Test Campaign',
            'status' => 'draft'
        ]);

        $this->assertDatabaseHas('cmis.campaigns', [
            'id' => $campaign->id,
            'org_id' => $org->id
        ]);
    }
}
```

### 2. Example Feature Test

```php
<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class CampaignAPITest extends TestCase
{
    use CreatesTestData;

    public function test_can_list_campaigns()
    {
        $org = $this->createTestOrganization();
        $user = $this->createTestUser($org);

        $this->actingAs($user)
            ->getJson('/api/campaigns')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'status']
                ]
            ]);
    }
}
```

### 3. Example Integration Test

```php
<?php

namespace Tests\Integration\Platform;

use Tests\TestCase;
use Tests\Traits\MocksExternalAPIs;

class MetaIntegrationTest extends TestCase
{
    use MocksExternalAPIs;

    public function test_syncs_campaigns_from_meta()
    {
        $this->mockMetaAPI();

        $service = app(MetaSyncService::class);
        $result = $service->syncCampaigns();

        $this->assertTrue($result->success);
        $this->assertCount(5, $result->campaigns);
    }
}
```

## Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --testsuite=Integration

# Run with coverage
vendor/bin/phpunit --coverage-html build/coverage/html
```

## Reference Old Tests

The archived tests can be found in `tests.archive/` for reference on:
- Test data patterns
- Assertion examples
- Mock setups
- Edge cases covered

## Notes

- All tests should respect RLS (use `InteractsWithRLS` trait)
- Use test traits to reduce duplication
- Mock external APIs (use `MocksExternalAPIs` trait)
- Follow the existing test infrastructure patterns
- Keep tests focused and isolated
