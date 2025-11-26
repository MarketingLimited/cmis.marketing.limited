# دليل الوكلاء - Tests Layer (tests/)

## 1. Purpose

طبقة Tests توفر **Test Coverage**:
- **201 Test Files** (33.4% pass rate - improving)
- **Unit Tests**: Models, Services, Repositories
- **Feature Tests**: API endpoints, Controllers
- **Integration Tests**: Complete flows
- **E2E Tests**: Playwright browser tests

## 2. Owned Scope

```
tests/
├── Unit/                # 50+ unit tests
│   ├── Models/
│   ├── Services/
│   ├── Repositories/
│   └── ...
│
├── Feature/             # 80+ feature tests
│   ├── AI/
│   ├── Campaign/
│   ├── Analytics/
│   ├── Auth/
│   └── ...
│
├── Integration/         # 50+ integration tests
│   ├── Campaign/
│   ├── AdPlatform/
│   ├── Social/
│   └── ...
│
├── E2E/                 # Playwright tests
│   └── *.spec.ts
│
├── TestCase.php         # Base test class
├── ParallelTestCase.php # Parallel testing
└── Traits/              # Reusable test logic
```

## 3. Test Patterns

### Unit Test (Model)
```php
namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Campaign\Campaign;

class CampaignTest extends TestCase
{
    public function test_campaign_has_organization()
    {
        $campaign = Campaign::factory()->create();

        $this->assertInstanceOf(Organization::class, $campaign->org);
    }

    public function test_rls_scope_applied()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Campaign::factory()->create(['org_id' => $org1->id]);
        Campaign::factory()->create(['org_id' => $org2->id]);

        $this->actingAsOrganization($org1);

        $this->assertEquals(1, Campaign::count());
    }
}
```

### Feature Test (API)
```php
namespace Tests\Feature\Campaign;

use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    public function test_can_list_campaigns()
    {
        $user = User::factory()->create();
        Campaign::factory()->count(5)->create(['org_id' => $user->org_id]);

        $response = $this->actingAs($user)
                         ->getJson('/api/campaigns');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data')
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'name', 'status']
                     ]
                 ]);
    }
}
```

### E2E Test (Playwright)
```typescript
// tests/E2E/campaign-creation.spec.ts
import { test, expect } from '@playwright/test';

test('create campaign flow', async ({ page }) => {
    await page.goto('/campaigns');
    await page.click('text=New Campaign');

    await page.fill('[name="name"]', 'Test Campaign');
    await page.selectOption('[name="objective"]', 'awareness');
    await page.click('button[type="submit"]');

    await expect(page.locator('.success-message')).toBeVisible();
});
```

## 4. Running Tests

```bash
# All tests
vendor/bin/phpunit

# Specific suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# Parallel tests (faster)
vendor/bin/paratest

# E2E tests
npm run test:e2e
npm run test:e2e:ui  # With UI

# Coverage
vendor/bin/phpunit --coverage-html build/coverage
```

## 5. Notes

- **Pass Rate**: 33.4% (actively improving)
- **Database**: Uses `cmis_test` database
- **Factories**: Heavily used for test data
- **RefreshDatabase**: Resets DB between tests
- **Multi-tenancy**: Test with multiple orgs
