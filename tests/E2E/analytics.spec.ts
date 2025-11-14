import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Analytics & Reporting E2E Tests
 */
test.describe('Analytics & Reporting', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display analytics dashboard', async ({ page }) => {
    await page.goto('/analytics');

    await expect(page.locator('h1')).toContainText(/analytics|dashboard/i);
    await expect(page.locator('[data-testid="overview-metrics"]')).toBeVisible();
  });

  test('should show campaign performance metrics', async ({ page }) => {
    await page.goto('/analytics/campaigns');

    await expect(page.locator('[data-testid="impressions-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="clicks-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversions-chart"]')).toBeVisible();
  });

  test('should filter analytics by date range', async ({ page }) => {
    await page.goto('/analytics');

    await page.click('[data-testid="date-range-picker"]');
    await page.click('[data-testid="last-7-days"]');

    await page.waitForLoadState('networkidle');
    await expect(page.locator('[data-testid="date-range-label"]')).toContainText('Last 7 days');
  });

  test('should export analytics report to PDF', async ({ page }) => {
    await page.goto('/analytics');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-pdf"]');

    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/analytics.*\.pdf/);
  });

  test('should show social media analytics', async ({ page }) => {
    await page.goto('/analytics/social');

    await expect(page.locator('[data-testid="platform-performance"]')).toBeVisible();
    await expect(page.locator('[data-testid="engagement-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="follower-growth"]')).toBeVisible();
  });

  test('should display KPI targets', async ({ page }) => {
    await page.goto('/analytics/kpis');

    await expect(page.locator('[data-testid="kpi-list"]')).toBeVisible();
    await expect(page.locator('[data-testid="kpi-progress"]')).not.toHaveCount(0);
  });

  test('should create custom report', async ({ page }) => {
    await page.goto('/analytics/reports');
    await page.click('[data-testid="create-report"]');

    await page.fill('input[name="report_name"]', 'Custom Performance Report');
    await page.check('[name="metrics"][value="impressions"]');
    await page.check('[name="metrics"][value="clicks"]');
    await page.selectOption('[name="groupBy"]', 'campaign');

    await page.click('button[type="submit"]');

    await cmisHelper.waitForNotification('Report created successfully');
  });
});
