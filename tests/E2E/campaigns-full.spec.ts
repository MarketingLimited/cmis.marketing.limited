import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Comprehensive Campaign Management E2E Tests
 */
test.describe('Campaign Management - Full Flow', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display campaigns list page', async ({ page }) => {
    await page.goto('/campaigns');

    await expect(page.locator('h1')).toContainText(/campaigns|حملات/i);
    await expect(page.locator('[data-testid="campaigns-table"]')).toBeVisible();
  });

  test('should create new campaign with all details', async ({ page }) => {
    await page.goto('/campaigns/create');

    // Basic Information
    await page.fill('input[name="name"]', 'E2E Test Campaign 2024');
    await page.fill('textarea[name="description"]', 'This is a test campaign created by E2E tests');

    // Objective
    await page.selectOption('select[name="objective"]', 'conversions');

    // Budget
    await page.fill('input[name="budget"]', '5000');
    await page.selectOption('select[name="currency"]', 'BHD');

    // Date Range
    const startDate = new Date();
    startDate.setDate(startDate.getDate() + 7);
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 37);

    await page.fill('input[name="start_date"]', startDate.toISOString().split('T')[0]);
    await page.fill('input[name="end_date"]', endDate.toISOString().split('T')[0]);

    // Target Audience
    await page.fill('textarea[name="target_audience"]', 'Adults 25-45, interested in technology');

    // Submit
    await page.click('button[type="submit"]');

    // Verify redirect to campaign details or campaigns list
    await page.waitForURL(/\/campaigns/);
    await expect(page.locator('text=E2E Test Campaign 2024')).toBeVisible();
  });

  test('should edit existing campaign', async ({ page }) => {
    // First create a campaign
    await page.goto('/campaigns/create');
    await page.fill('input[name="name"]', 'Campaign to Edit');
    await page.fill('input[name="budget"]', '1000');
    await page.click('button[type="submit"]');

    await page.waitForTimeout(1000);

    // Navigate to campaigns list
    await page.goto('/campaigns');

    // Click edit button for the campaign
    await page.click('[data-testid="edit-campaign"]:first-child');

    // Modify campaign details
    await page.fill('input[name="name"]', 'Updated Campaign Name');
    await page.fill('input[name="budget"]', '2000');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Updated Campaign Name')).toBeVisible();
  });

  test('should activate campaign', async ({ page }) => {
    await page.goto('/campaigns');

    // Find a draft campaign and activate it
    const campaignRow = page.locator('[data-status="draft"]').first();
    await campaignRow.locator('[data-testid="activate-button"]').click();

    // Confirm activation
    await page.click('[data-testid="confirm-activate"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should pause active campaign', async ({ page }) => {
    await page.goto('/campaigns');

    const campaignRow = page.locator('[data-status="active"]').first();
    await campaignRow.locator('[data-testid="pause-button"]').click();

    await page.click('[data-testid="confirm-pause"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should view campaign details', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');

    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('[data-testid="campaign-status"]')).toBeVisible();
    await expect(page.locator('[data-testid="campaign-budget"]')).toBeVisible();
    await expect(page.locator('[data-testid="campaign-dates"]')).toBeVisible();
  });

  test('should view campaign analytics', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');
    await page.click('[data-testid="analytics-tab"]');

    await expect(page.locator('[data-testid="impressions-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="clicks-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversions-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="analytics-chart"]')).toBeVisible();
  });

  test('should delete campaign', async ({ page }) => {
    await page.goto('/campaigns');

    const campaignRow = page.locator('[data-testid="campaign-row"]').first();
    await campaignRow.locator('[data-testid="delete-button"]').click();

    // Confirm deletion
    await page.fill('[data-testid="confirm-delete-input"]', 'DELETE');
    await page.click('[data-testid="confirm-delete-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should filter campaigns by status', async ({ page }) => {
    await page.goto('/campaigns');

    await page.selectOption('[data-testid="status-filter"]', 'active');

    await page.waitForTimeout(500);

    const campaigns = page.locator('[data-testid="campaign-row"]');
    const count = await campaigns.count();

    for (let i = 0; i < count; i++) {
      const status = await campaigns.nth(i).getAttribute('data-status');
      expect(status).toBe('active');
    }
  });

  test('should search campaigns by name', async ({ page }) => {
    await page.goto('/campaigns');

    await page.fill('[data-testid="search-input"]', 'Summer');
    await page.click('[data-testid="search-button"]');

    await page.waitForTimeout(500);

    await expect(page.locator('text=Summer')).toBeVisible();
  });

  test('should duplicate campaign', async ({ page }) => {
    await page.goto('/campaigns');

    const campaignRow = page.locator('[data-testid="campaign-row"]').first();
    await campaignRow.locator('[data-testid="duplicate-button"]').click();

    // Modify duplicated campaign name
    await page.fill('input[name="name"]', 'Duplicated Campaign');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Duplicated Campaign')).toBeVisible();
  });

  test('should export campaigns to CSV', async ({ page }) => {
    await page.goto('/campaigns');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-csv"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.csv');
  });

  test('should view campaign performance over time', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');
    await page.click('[data-testid="performance-tab"]');

    await expect(page.locator('[data-testid="performance-chart"]')).toBeVisible();
    await expect(page.locator('[data-testid="roi-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="ctr-metric"]')).toBeVisible();
  });

  test('should associate products with campaign', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');
    await page.click('[data-testid="products-tab"]');

    await page.click('[data-testid="add-product"]');
    await page.selectOption('[data-testid="product-select"]', { index: 0 });
    await page.click('[data-testid="confirm-add"]');

    await expect(page.locator('[data-testid="product-item"]')).toBeVisible();
  });

  test('should view campaign budget utilization', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');
    await page.click('[data-testid="budget-tab"]');

    await expect(page.locator('[data-testid="budget-spent"]')).toBeVisible();
    await expect(page.locator('[data-testid="budget-remaining"]')).toBeVisible();
    await expect(page.locator('[data-testid="budget-progress-bar"]')).toBeVisible();
  });

  test('should set campaign notifications', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');
    await page.click('[data-testid="settings-tab"]');

    await page.check('[data-testid="notify-on-completion"]');
    await page.check('[data-testid="notify-on-budget-alert"]');
    await page.fill('input[name="budget_alert_threshold"]', '80');

    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });
});
