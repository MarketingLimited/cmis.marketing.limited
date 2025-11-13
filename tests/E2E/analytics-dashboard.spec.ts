import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Analytics Dashboard E2E Tests - Complete Coverage
 */
test.describe('Analytics & Reporting', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display main analytics dashboard', async ({ page }) => {
    await page.goto('/analytics');

    await expect(page.locator('h1')).toContainText(/analytics|تحليلات/i);
    await expect(page.locator('[data-testid="kpi-cards"]')).toBeVisible();
    await expect(page.locator('[data-testid="main-chart"]')).toBeVisible();
  });

  test('should show campaign performance metrics', async ({ page }) => {
    await page.goto('/analytics/campaigns');

    await expect(page.locator('[data-testid="total-campaigns"]')).toBeVisible();
    await expect(page.locator('[data-testid="active-campaigns"]')).toBeVisible();
    await expect(page.locator('[data-testid="total-impressions"]')).toBeVisible();
    await expect(page.locator('[data-testid="total-clicks"]')).toBeVisible();
    await expect(page.locator('[data-testid="total-conversions"]')).toBeVisible();
    await expect(page.locator('[data-testid="average-ctr"]')).toBeVisible();
    await expect(page.locator('[data-testid="average-roi"]')).toBeVisible();
  });

  test('should display social media analytics', async ({ page }) => {
    await page.goto('/analytics/social');

    await expect(page.locator('[data-testid="facebook-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="instagram-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="twitter-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="linkedin-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="tiktok-metrics"]')).toBeVisible();
  });

  test('should show engagement metrics over time', async ({ page }) => {
    await page.goto('/analytics/engagement');

    await expect(page.locator('[data-testid="engagement-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="likes-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="comments-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="shares-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="engagement-rate"]')).toBeVisible();
  });

  test('should filter analytics by date range', async ({ page }) => {
    await page.goto('/analytics');

    await page.click('[data-testid="date-range-picker"]');
    await page.click('[data-testid="last-30-days"]');

    await page.waitForTimeout(1000);

    await expect(page.locator('[data-testid="date-range-label"]')).toContainText('Last 30 days');
  });

  test('should filter analytics by custom date range', async ({ page }) => {
    await page.goto('/analytics');

    await page.click('[data-testid="date-range-picker"]');
    await page.click('[data-testid="custom-range"]');

    const startDate = new Date('2024-01-01');
    const endDate = new Date('2024-03-31');

    await page.fill('[data-testid="start-date"]', startDate.toISOString().split('T')[0]);
    await page.fill('[data-testid="end-date"]', endDate.toISOString().split('T')[0]);

    await page.click('[data-testid="apply-filter"]');

    await page.waitForTimeout(1000);

    await expect(page.locator('[data-testid="main-chart"]')).toBeVisible();
  });

  test('should compare analytics across campaigns', async ({ page }) => {
    await page.goto('/analytics/compare');

    await page.selectOption('[data-testid="campaign-1"]', { index: 0 });
    await page.selectOption('[data-testid="campaign-2"]', { index: 1 });

    await page.click('[data-testid="compare-button"]');

    await expect(page.locator('[data-testid="comparison-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="campaign-1-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="campaign-2-metrics"]')).toBeVisible();
  });

  test('should export analytics report to PDF', async ({ page }) => {
    await page.goto('/analytics');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-pdf"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.pdf');
  });

  test('should export analytics report to Excel', async ({ page }) => {
    await page.goto('/analytics');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-excel"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toMatch(/\.xlsx?$/);
  });

  test('should display audience demographics', async ({ page }) => {
    await page.goto('/analytics/audience');

    await expect(page.locator('[data-testid="age-distribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="gender-distribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="location-map"]')).toBeVisible();
    await expect(page.locator('[data-testid="interests-chart"]')).toBeVisible();
  });

  test('should show content performance rankings', async ({ page }) => {
    await page.goto('/analytics/content-performance');

    await expect(page.locator('[data-testid="top-posts"]')).toBeVisible();
    await expect(page.locator('[data-testid="worst-posts"]')).toBeVisible();
    await expect(page.locator('[data-testid="performance-table"]')).toBeVisible();
  });

  test('should display funnel analytics', async ({ page }) => {
    await page.goto('/analytics/funnel');

    await expect(page.locator('[data-testid="funnel-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="awareness-stage"]')).toBeVisible();
    await expect(page.locator('[data-testid="consideration-stage"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversion-stage"]')).toBeVisible();
    await expect(page.locator('[data-testid="retention-stage"]')).toBeVisible();
  });

  test('should show ROI calculator', async ({ page }) => {
    await page.goto('/analytics/roi');

    await page.fill('input[name="total_spend"]', '5000');
    await page.fill('input[name="total_revenue"]', '15000');

    await page.click('[data-testid="calculate-roi"]');

    await expect(page.locator('[data-testid="roi-result"]')).toBeVisible();
    await expect(page.locator('[data-testid="roi-percentage"]')).toContainText('200%');
  });

  test('should display conversion tracking', async ({ page }) => {
    await page.goto('/analytics/conversions');

    await expect(page.locator('[data-testid="total-conversions"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversion-rate"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversion-value"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversion-chart"]')).toBeVisible();
  });

  test('should show real-time analytics', async ({ page }) => {
    await page.goto('/analytics/realtime');

    await expect(page.locator('[data-testid="active-users"]')).toBeVisible();
    await expect(page.locator('[data-testid="live-events"]')).toBeVisible();
    await expect(page.locator('[data-testid="realtime-chart"]')).toBeVisible();

    // Wait and check if data updates
    await page.waitForTimeout(5000);

    await expect(page.locator('[data-testid="last-updated"]')).toBeVisible();
  });

  test('should filter analytics by platform', async ({ page }) => {
    await page.goto('/analytics/social');

    await page.selectOption('[data-testid="platform-filter"]', 'instagram');

    await page.waitForTimeout(500);

    await expect(page.locator('[data-testid="instagram-metrics"]')).toBeVisible();
  });

  test('should display attribution analytics', async ({ page }) => {
    await page.goto('/analytics/attribution');

    await expect(page.locator('[data-testid="first-touch-attribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="last-touch-attribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="multi-touch-attribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="attribution-chart"]')).toBeVisible();
  });

  test('should show budget utilization report', async ({ page }) => {
    await page.goto('/analytics/budget');

    await expect(page.locator('[data-testid="total-budget"]')).toBeVisible();
    await expect(page.locator('[data-testid="spent-budget"]')).toBeVisible();
    await expect(page.locator('[data-testid="remaining-budget"]')).toBeVisible();
    await expect(page.locator('[data-testid="budget-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="burn-rate"]')).toBeVisible();
  });

  test('should create custom analytics dashboard', async ({ page }) => {
    await page.goto('/analytics/custom');

    await page.click('[data-testid="add-widget"]');
    await page.selectOption('[data-testid="widget-type"]', 'chart');
    await page.selectOption('[data-testid="metric"]', 'impressions');
    await page.click('[data-testid="save-widget"]');

    await expect(page.locator('[data-testid="custom-widget"]')).toBeVisible();
  });

  test('should set up analytics alerts', async ({ page }) => {
    await page.goto('/analytics/alerts');

    await page.click('[data-testid="create-alert"]');

    await page.selectOption('[data-testid="metric"]', 'conversions');
    await page.selectOption('[data-testid="condition"]', 'less_than');
    await page.fill('input[name="threshold"]', '10');
    await page.fill('input[name="email"]', 'admin@example.com');

    await page.click('[data-testid="save-alert"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should display predictive analytics', async ({ page }) => {
    await page.goto('/analytics/predictions');

    await expect(page.locator('[data-testid="forecast-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="predicted-conversions"]')).toBeVisible();
    await expect(page.locator('[data-testid="confidence-interval"]')).toBeVisible();
  });
});
