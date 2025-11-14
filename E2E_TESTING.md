# End-to-End Testing with Playwright

## Overview

The CMIS platform includes comprehensive End-to-End (E2E) testing using Playwright, covering all user-facing pages and API routes. E2E tests validate complete user workflows from the browser's perspective.

## Setup

### Installation

```bash
# Install Node dependencies
npm install

# Install Playwright browsers
npm run playwright:install
```

### Prerequisites

1. **Application Running**: Laravel dev server must be running
   ```bash
   php artisan serve
   ```

2. **Database Setup**: Test database should be configured and migrated
   ```bash
   php artisan migrate:fresh --seed --env=testing
   ```

3. **Environment**: Set `APP_URL=http://localhost:8000` in `.env`

## Running E2E Tests

### Basic Commands

```bash
# Run all E2E tests
npm run test:e2e

# Run with UI mode (interactive)
npm run test:e2e:ui

# Run in headed mode (see browser)
npm run test:e2e:headed

# Debug mode (step through tests)
npm run test:e2e:debug

# Show test report
npm run test:e2e:report
```

### Running Specific Tests

```bash
# Run specific test file
npx playwright test tests/E2E/campaigns.spec.ts

# Run specific test by name
npx playwright test --grep "should create a new campaign"

# Run tests for specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit
```

### Run All Tests (Unit + Integration + E2E)

```bash
npm run test:all
```

## Test Coverage

### Page Routes Covered

**Authentication** (`tests/E2E/auth.spec.ts`):
- ✅ `/login` - Login page
- ✅ `/register` - Registration page
- ✅ `/forgot-password` - Password reset
- ✅ `/verify-email` - Email verification

**Dashboard** (`tests/E2E/campaigns.spec.ts`):
- ✅ `/dashboard` - Main dashboard
- ✅ `/campaigns` - Campaign listing
- ✅ `/campaigns/{id}` - Campaign details
- ✅ `/campaigns/create` - Create campaign
- ✅ `/campaigns/{id}/edit` - Edit campaign

**Analytics** (`tests/E2E/analytics.spec.ts`):
- ✅ `/analytics` - Analytics dashboard
- ✅ `/analytics/campaigns` - Campaign analytics
- ✅ `/analytics/social` - Social media analytics
- ✅ `/analytics/kpis` - KPI tracking
- ✅ `/analytics/reports` - Custom reports

**Creative** (`tests/E2E/creative.spec.ts`):
- ✅ `/creative` - Creative assets overview
- ✅ `/creative/briefs` - Creative briefs
- ✅ `/creative/library` - Content library
- ✅ `/creative/ai-generation` - AI content generation
- ✅ `/creative/approvals` - Approval workflow

**Publishing** (`tests/E2E/publishing.spec.ts`):
- ✅ `/publishing` - Publishing queue
- ✅ `/publishing/schedule` - Schedule posts
- ✅ `/publishing/history` - Publishing history
- ✅ `/publishing/queue-config` - Queue configuration

**Integrations** (`tests/E2E/integrations.spec.ts`):
- ✅ `/integrations` - Platform integrations
- ✅ `/integrations/webhooks` - Webhook management
- ✅ `/integrations/api-keys` - API key management
- ✅ `/integrations/sync-history` - Sync logs

**AI Features** (`tests/E2E/ai-features.spec.ts`):
- ✅ `/ai` - AI dashboard
- ✅ `/ai/search` - Semantic search
- ✅ `/ai/insights` - AI insights
- ✅ `/ai/recommendations` - Recommendations
- ✅ `/ai/campaign-generator` - AI campaign generator
- ✅ `/ai/anomaly-detection` - Anomaly detection
- ✅ `/ai/best-times` - Optimal posting times
- ✅ `/ai/content-suggestions` - Content suggestions

**Settings** (`tests/E2E/settings.spec.ts`):
- ✅ `/settings` - Settings overview
- ✅ `/settings/profile` - User profile
- ✅ `/settings/security` - Security settings
- ✅ `/settings/team` - Team management
- ✅ `/settings/notifications` - Notification preferences
- ✅ `/settings/organization` - Organization settings
- ✅ `/settings/permissions` - Permission management

### API Routes Covered

All API endpoints are indirectly tested through UI interactions:

**Campaign APIs**:
- `GET /api/orgs/{org_id}/campaigns`
- `POST /api/orgs/{org_id}/campaigns`
- `GET /api/orgs/{org_id}/campaigns/{id}`
- `PUT /api/orgs/{org_id}/campaigns/{id}`
- `DELETE /api/orgs/{org_id}/campaigns/{id}`

**Publishing APIs**:
- `POST /api/orgs/{org_id}/publishing/schedule`
- `GET /api/orgs/{org_id}/publishing/queue`
- `PUT /api/orgs/{org_id}/publishing/{id}`

**Integration APIs**:
- `POST /api/orgs/{org_id}/integrations`
- `POST /api/orgs/{org_id}/integrations/sync`
- `GET /api/orgs/{org_id}/integrations/history`

**Analytics APIs**:
- `GET /api/orgs/{org_id}/analytics/overview`
- `GET /api/orgs/{org_id}/analytics/campaigns`
- `POST /api/orgs/{org_id}/analytics/export`

**AI APIs**:
- `POST /api/orgs/{org_id}/ai/search`
- `POST /api/orgs/{org_id}/ai/generate`
- `GET /api/orgs/{org_id}/ai/insights`

## Test Structure

### Helper Classes

**AuthHelper** (`tests/E2E/helpers/auth.ts`):
- `login(email, password)` - Login user
- `logout()` - Logout user
- `createTestUser(userData)` - Create test user via API
- `getAuthToken(email, password)` - Get API token

**CMISHelper** (`tests/E2E/helpers/cmis.ts`):
- `selectOrganization(orgName)` - Switch organization
- `goToCampaigns()` - Navigate to campaigns
- `createCampaign(data)` - Create campaign via UI
- `searchCampaigns(query)` - Search campaigns
- `schedulePost(data)` - Schedule social post
- `waitForNotification(message)` - Wait for notification
- `takeScreenshot(name)` - Capture screenshot

### Test Patterns

**Basic Test Structure**:
```typescript
test('should perform action', async ({ page }) => {
  // Arrange
  await page.goto('/some-page');

  // Act
  await page.click('[data-testid="button"]');

  // Assert
  await expect(page.locator('[data-testid="result"]')).toBeVisible();
});
```

**Using Helpers**:
```typescript
test('should create campaign', async ({ page }) => {
  const authHelper = new AuthHelper(page);
  const cmisHelper = new CMISHelper(page);

  await authHelper.login('user@example.com', 'password');
  await cmisHelper.createCampaign({ name: 'Test Campaign' });

  await expect(page.locator('text=Campaign created')).toBeVisible();
});
```

## Browser Coverage

Tests run across multiple browsers and viewports:

**Desktop**:
- Chromium (Chrome/Edge)
- Firefox
- WebKit (Safari)

**Mobile**:
- Mobile Chrome (Pixel 5)
- Mobile Safari (iPhone 12)

**Tablet**:
- iPad Pro

## CI/CD Integration

### GitHub Actions

Add to `.github/workflows/e2e-tests.yml`:

```yaml
name: E2E Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  e2e:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: cmis_test
          POSTGRES_USER: begin
          POSTGRES_PASSWORD: 123@Marketing@321
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '20'

      - name: Install PHP Dependencies
        run: composer install

      - name: Install Node Dependencies
        run: npm ci

      - name: Install Playwright Browsers
        run: npx playwright install --with-deps

      - name: Run Migrations
        run: php artisan migrate:fresh --seed --env=testing

      - name: Start Laravel Server
        run: php artisan serve &

      - name: Run E2E Tests
        run: npm run test:e2e

      - name: Upload Test Report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/
```

## Debugging Tests

### Visual Debugging

```bash
# UI Mode (best for debugging)
npm run test:e2e:ui

# Debug Mode (step through)
npm run test:e2e:debug

# Headed Mode (see browser)
npm run test:e2e:headed
```

### Code Generation

Generate test code by interacting with the app:

```bash
npm run test:e2e:codegen
```

### Screenshots & Videos

Tests automatically capture:
- Screenshots on failure
- Videos on failure
- Traces on first retry

Access in `playwright-report/` directory.

### Test Selectors

Use `data-testid` attributes for reliable selectors:

```html
<!-- Good -->
<button data-testid="create-campaign-button">Create</button>

<!-- Avoid -->
<button class="btn btn-primary">Create</button>
```

## Best Practices

1. **Use Test IDs**: Always prefer `data-testid` over CSS selectors
2. **Wait for Actions**: Use `waitForLoadState('networkidle')` after navigation
3. **Cleanup Data**: Use unique names with timestamps for test data
4. **Helper Methods**: Use helpers for common actions
5. **Assertions**: Use built-in Playwright assertions
6. **Screenshots**: Take screenshots for visual verification
7. **Parallel Execution**: Tests should be independent
8. **Error Messages**: Use descriptive error messages
9. **Page Objects**: Extract common page logic into helpers
10. **Network Mocking**: Mock external APIs when needed

## Test Data Management

### Creating Test Data

Use API or UI helpers to create test data:

```typescript
// Via API
const userData = await authHelper.createTestUser({
  name: 'Test User',
  email: `test-${Date.now()}@example.com`,
  password: 'Password123!',
});

// Via UI
await cmisHelper.createCampaign({
  name: `E2E Campaign ${Date.now()}`,
  objective: 'awareness',
});
```

### Unique Naming

Always use timestamps for unique names:

```typescript
const campaignName = `Test Campaign ${Date.now()}`;
```

## Troubleshooting

### Common Issues

**Issue**: Tests fail with timeout
```
Solution: Increase timeout in test or use waitFor helpers
test.setTimeout(60000); // 60 seconds
```

**Issue**: Server not running
```bash
Solution: Start Laravel server before tests
php artisan serve
```

**Issue**: Database connection errors
```bash
Solution: Ensure test database exists
php artisan migrate:fresh --env=testing
```

**Issue**: Flaky tests
```
Solution: Use proper wait conditions
await page.waitForLoadState('networkidle');
await page.waitForSelector('[data-testid="element"]');
```

### Debug Output

```typescript
// Console log
console.log(await page.locator('[data-testid="element"]').textContent());

// Take screenshot
await page.screenshot({ path: 'debug.png' });

// Pause execution
await page.pause();
```

## Performance

### Parallel Execution

Tests run in parallel by default:

```bash
# Control worker count
npx playwright test --workers=4
```

### Headless Mode

Headless mode is faster:

```bash
npx playwright test # headless (default)
npx playwright test --headed # headed
```

## Reports

### HTML Report

```bash
# Generate report
npm run test:e2e

# View report
npm run test:e2e:report
```

### JUnit Report

Located at `playwright-report/junit.xml` for CI/CD integration.

### JSON Report

Located at `playwright-report/results.json` for custom processing.

---

**Last Updated**: 2025-01-13
**Playwright Version**: 1.40.0
**Coverage**: 50+ E2E tests across all major workflows
