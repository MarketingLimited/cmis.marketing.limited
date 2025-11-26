import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Reports & Export E2E Tests
 */
test.describe('Reports & Export', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display reports dashboard', async ({ page }) => {
    await page.goto('/reports');

    await expect(page.locator('h1')).toContainText(/reports|تقارير/i);
    await expect(page.locator('[data-testid="report-types-grid"]')).toBeVisible();
  });

  test('should generate campaign performance report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'campaign_performance');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-06-30');

    await page.check('input[value="campaign_1"]');
    await page.check('input[value="campaign_2"]');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="report-preview"]')).toBeVisible();
    await expect(page.locator('[data-testid="metrics-table"]')).toBeVisible();
  });

  test('should export report to PDF', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="view-report"]:first-child');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-pdf"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.pdf');
  });

  test('should export report to Excel', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="view-report"]:first-child');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-excel"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toMatch(/\.(xlsx|xls)$/);
  });

  test('should generate social media performance report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'social_media');

    await page.check('input[value="instagram"]');
    await page.check('input[value="facebook"]');
    await page.check('input[value="twitter"]');

    await page.fill('input[name="date_from"]', '2024-05-01');
    await page.fill('input[name="date_to"]', '2024-05-31');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="platform-metrics"]')).toBeVisible();
    await expect(page.locator('[data-testid="engagement-chart"]')).toBeVisible();
  });

  test('should generate ROI report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'roi');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-06-30');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="total-spend"]')).toBeVisible();
    await expect(page.locator('[data-testid="total-revenue"]')).toBeVisible();
    await expect(page.locator('[data-testid="roi-percentage"]')).toBeVisible();
  });

  test('should generate audience demographics report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'demographics');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="age-distribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="gender-distribution"]')).toBeVisible();
    await expect(page.locator('[data-testid="location-map"]')).toBeVisible();
  });

  test('should generate content performance report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'content_performance');

    await page.selectOption('select[name="platform"]', 'instagram');

    await page.fill('input[name="date_from"]', '2024-05-01');
    await page.fill('input[name="date_to"]', '2024-05-31');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="top-posts"]')).toBeVisible();
    await expect(page.locator('[data-testid="engagement-metrics"]')).toBeVisible();
  });

  test('should generate ad spend report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'ad_spend');

    await page.check('input[value="meta_ads"]');
    await page.check('input[value="google_ads"]');
    await page.check('input[value="tiktok_ads"]');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-06-30');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="spend-by-platform"]')).toBeVisible();
    await expect(page.locator('[data-testid="cost-per-result"]')).toBeVisible();
  });

  test('should schedule automated report', async ({ page }) => {
    await page.goto('/reports/schedule');

    await page.click('[data-testid="schedule-report"]');

    await page.fill('input[name="report_name"]', 'تقرير أداء شهري');
    await page.selectOption('select[name="report_type"]', 'campaign_performance');
    await page.selectOption('select[name="frequency"]', 'monthly');
    await page.selectOption('select[name="day_of_month"]', '1');

    await page.fill('input[name="recipients"]', 'manager@example.com, team@example.com');

    await page.click('[data-testid="save-schedule"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should view scheduled reports', async ({ page }) => {
    await page.goto('/reports/schedule');

    await expect(page.locator('[data-testid="scheduled-reports-list"]')).toBeVisible();
    await expect(page.locator('[data-testid="scheduled-report-item"]')).toBeVisible();
  });

  test('should edit scheduled report', async ({ page }) => {
    await page.goto('/reports/schedule');

    await page.click('[data-testid="edit-schedule"]:first-child');

    await page.selectOption('select[name="frequency"]', 'weekly');

    await page.click('[data-testid="save-schedule"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should delete scheduled report', async ({ page }) => {
    await page.goto('/reports/schedule');

    await page.click('[data-testid="delete-schedule"]:first-child');
    await page.click('[data-testid="confirm-delete"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should generate custom report', async ({ page }) => {
    await page.goto('/reports/custom');

    await page.fill('input[name="report_name"]', 'تقرير مخصص للربع الأول');

    await page.check('input[value="impressions"]');
    await page.check('input[value="clicks"]');
    await page.check('input[value="conversions"]');
    await page.check('input[value="spend"]');
    await page.check('input[value="revenue"]');

    await page.selectOption('select[name="group_by"]', 'campaign');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-03-31');

    await page.click('[data-testid="generate-custom-report"]');

    await expect(page.locator('[data-testid="custom-report-preview"]')).toBeVisible();
  });

  test('should save custom report template', async ({ page }) => {
    await page.goto('/reports/custom');

    // Configure report
    await page.fill('input[name="report_name"]', 'قالب التقرير الشهري');
    await page.check('input[value="impressions"]');
    await page.check('input[value="clicks"]');

    await page.click('[data-testid="save-template"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should use saved report template', async ({ page }) => {
    await page.goto('/reports/templates');

    await page.click('[data-testid="use-template"]:first-child');

    await expect(page).toHaveURL(/\/reports\/custom/);
    await expect(page.locator('input[name="report_name"]')).not.toBeEmpty();
  });

  test('should compare campaigns in report', async ({ page }) => {
    await page.goto('/reports/compare');

    await page.selectOption('[data-testid="campaign-1"]', 'campaign_summer');
    await page.selectOption('[data-testid="campaign-2"]', 'campaign_winter');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-06-30');

    await page.click('[data-testid="compare-campaigns"]');

    await expect(page.locator('[data-testid="comparison-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="side-by-side-metrics"]')).toBeVisible();
  });

  test('should generate conversion funnel report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'conversion_funnel');

    await page.selectOption('select[name="campaign"]', { index: 0 });

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="funnel-visualization"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversion-rates"]')).toBeVisible();
  });

  test('should generate attribution report', async ({ page }) => {
    await page.goto('/reports/create');

    await page.selectOption('select[name="report_type"]', 'attribution');

    await page.selectOption('select[name="attribution_model"]', 'last_click');

    await page.click('[data-testid="generate-report"]');

    await expect(page.locator('[data-testid="channel-attribution"]')).toBeVisible();
  });

  test('should filter report by segment', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="view-report"]:first-child');

    await page.selectOption('[data-testid="segment-filter"]', 'age_18_24');

    await page.click('[data-testid="apply-filter"]');

    await expect(page.locator('[data-testid="filtered-results"]')).toBeVisible();
  });

  test('should add report to favorites', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="favorite-report"]:first-child');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should share report with team', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="view-report"]:first-child');

    await page.click('[data-testid="share-report"]');

    await page.check('input[value="member_1"]');
    await page.check('input[value="member_2"]');

    await page.click('[data-testid="confirm-share"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should generate executive summary', async ({ page }) => {
    await page.goto('/reports/executive-summary');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-06-30');

    await page.click('[data-testid="generate-summary"]');

    await expect(page.locator('[data-testid="key-highlights"]')).toBeVisible();
    await expect(page.locator('[data-testid="performance-overview"]')).toBeVisible();
    await expect(page.locator('[data-testid="recommendations"]')).toBeVisible();
  });

  test('should view report history', async ({ page }) => {
    await page.goto('/reports/history');

    await expect(page.locator('[data-testid="report-history-list"]')).toBeVisible();
    await expect(page.locator('[data-testid="history-item"]')).toBeVisible();
  });

  test('should filter reports by date range', async ({ page }) => {
    await page.goto('/reports');

    await page.fill('input[name="filter_from"]', '2024-01-01');
    await page.fill('input[name="filter_to"]', '2024-06-30');

    await page.click('[data-testid="apply-date-filter"]');

    await expect(page.locator('[data-testid="filtered-reports"]')).toBeVisible();
  });

  test('should send report via email', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="view-report"]:first-child');

    await page.click('[data-testid="email-report"]');

    await page.fill('input[name="recipients"]', 'manager@example.com');
    await page.fill('textarea[name="message"]', 'إليك التقرير الشهري');

    await page.click('[data-testid="send-email"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should print report', async ({ page }) => {
    await page.goto('/reports');

    await page.click('[data-testid="view-report"]:first-child');

    await page.click('[data-testid="print-report"]');

    // Verify print dialog or print preview appears
    await expect(page.locator('[data-testid="print-preview"]')).toBeVisible();
  });
});
