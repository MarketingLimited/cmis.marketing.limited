import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Comprehensive Campaign Management E2E Tests
 *
 * Consolidated test suite covering:
 * - Basic CRUD operations
 * - Campaign lifecycle (create, activate, pause, delete)
 * - Filtering and search
 * - Analytics and performance tracking
 * - Product associations and budget management
 * - Multi-org isolation and permissions
 */
test.describe('Campaign Management', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  // ============================================================================
  // BASIC OPERATIONS
  // ============================================================================

  test.describe('Basic Operations', () => {
    test('should display campaigns page', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      await expect(page.locator('h1')).toContainText(/campaigns/i);
      await expect(page.locator('[data-testid="create-campaign-button"]')).toBeVisible();
    });

    test('should create a new campaign with basic details', async ({ page }) => {
      const campaignName = `E2E Test Campaign ${Date.now()}`;

      await cmisHelper.createCampaign({
        name: campaignName,
        objective: 'awareness',
        budget: 5000,
        startDate: '2025-02-01',
        endDate: '2025-02-28',
      });

      await expect(page.locator(`text=${campaignName}`)).toBeVisible();
      await cmisHelper.waitForNotification('Campaign created successfully');
    });

    test('should create campaign with comprehensive details', async ({ page }) => {
      await page.goto('/campaigns/create');

      // Basic Information
      const campaignName = `Comprehensive Campaign ${Date.now()}`;
      await page.fill('input[name="name"]', campaignName);
      await page.fill('textarea[name="description"]', 'Full campaign with all details');

      // Objective and Budget
      await page.selectOption('select[name="objective"]', 'conversions');
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

      await page.waitForURL(/\/campaigns/);
      await expect(page.locator(`text=${campaignName}`)).toBeVisible();
    });

    test('should validate campaign form fields', async ({ page }) => {
      await cmisHelper.goToCampaigns();
      await page.click('[data-testid="create-campaign-button"]');

      // Submit without required fields
      await page.click('button[type="submit"]');

      // Should show validation errors
      await expect(page.locator('text=/name is required/i')).toBeVisible();
    });

    test('should edit existing campaign', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      // Click first campaign's edit button
      await page.click('[data-testid="campaign-row"]:first-child [data-testid="edit-button"]');

      // Update campaign name
      const newName = `Updated Campaign ${Date.now()}`;
      await page.fill('input[name="name"]', newName);
      await page.click('button[type="submit"]');

      // Verify update
      await cmisHelper.waitForNotification('Campaign updated successfully');
      await expect(page.locator(`text=${newName}`)).toBeVisible();
    });

    test('should duplicate campaign', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      const campaignRow = page.locator('[data-testid="campaign-row"]').first();
      await campaignRow.locator('[data-testid="duplicate-button"]').click();

      // Modify duplicated campaign name
      const duplicatedName = `Duplicated Campaign ${Date.now()}`;
      await page.fill('input[name="name"]', duplicatedName);
      await page.click('button[type="submit"]');

      await expect(page.locator(`text=${duplicatedName}`)).toBeVisible();
    });

    test('should delete campaign', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      // Get initial campaign count
      const initialCount = await page.locator('[data-testid="campaign-row"]').count();

      // Delete first campaign
      await page.click('[data-testid="campaign-row"]:first-child [data-testid="delete-button"]');

      // Confirm deletion
      await page.click('[data-testid="confirm-delete"]');

      // Verify deletion
      await cmisHelper.waitForNotification('Campaign deleted successfully');

      const newCount = await page.locator('[data-testid="campaign-row"]').count();
      expect(newCount).toBe(initialCount - 1);
    });
  });

  // ============================================================================
  // CAMPAIGN LIFECYCLE
  // ============================================================================

  test.describe('Campaign Lifecycle', () => {
    test('should activate draft campaign', async ({ page }) => {
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
  });

  // ============================================================================
  // FILTERING AND SEARCH
  // ============================================================================

  test.describe('Filtering and Search', () => {
    test('should filter campaigns by status', async ({ page }) => {
      await cmisHelper.filterCampaignsByStatus('active');

      // All visible campaigns should have 'active' status
      const statusBadges = page.locator('[data-testid="campaign-status"]');
      const count = await statusBadges.count();

      for (let i = 0; i < count; i++) {
        await expect(statusBadges.nth(i)).toHaveText('active');
      }
    });

    test('should search campaigns by name', async ({ page }) => {
      const searchQuery = 'Summer';
      await cmisHelper.searchCampaigns(searchQuery);

      // All visible campaigns should contain search query in name
      const campaignNames = page.locator('[data-testid="campaign-name"]');
      const count = await campaignNames.count();

      for (let i = 0; i < count; i++) {
        const text = await campaignNames.nth(i).textContent();
        expect(text?.toLowerCase()).toContain(searchQuery.toLowerCase());
      }
    });

    test('should paginate campaign list', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      // Check if pagination exists
      const pagination = page.locator('[data-testid="pagination"]');

      if (await pagination.isVisible()) {
        // Click next page
        await page.click('[data-testid="next-page"]');

        // URL should update with page parameter
        await expect(page).toHaveURL(/.*page=2/);

        // Different campaigns should be visible
        await expect(page.locator('[data-testid="campaign-row"]')).not.toHaveCount(0);
      }
    });
  });

  // ============================================================================
  // CAMPAIGN DETAILS AND VIEWS
  // ============================================================================

  test.describe('Campaign Details', () => {
    test('should view campaign details', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      // Click first campaign
      await page.click('[data-testid="campaign-row"]:first-child [data-testid="view-button"]');

      // Should navigate to campaign detail page
      await expect(page).toHaveURL(/.*\/campaigns\/[a-f0-9-]+/);

      // Should show campaign information
      await expect(page.locator('[data-testid="campaign-name"]')).toBeVisible();
      await expect(page.locator('[data-testid="campaign-objective"]')).toBeVisible();
      await expect(page.locator('[data-testid="campaign-budget"]')).toBeVisible();
    });

    test('should display campaign analytics', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      await page.click('[data-testid="campaign-row"]:first-child [data-testid="view-button"]');

      // Navigate to analytics tab
      await page.click('[data-testid="analytics-tab"]');

      // Should show performance metrics
      await expect(page.locator('[data-testid="impressions-metric"]')).toBeVisible();
      await expect(page.locator('[data-testid="clicks-metric"]')).toBeVisible();
      await expect(page.locator('[data-testid="spend-metric"]')).toBeVisible();
      await expect(page.locator('[data-testid="analytics-chart"]')).toBeVisible();
    });

    test('should view campaign performance over time', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      await page.click('[data-testid="view-campaign"]:first-child');
      await page.click('[data-testid="performance-tab"]');

      await expect(page.locator('[data-testid="performance-chart"]')).toBeVisible();
      await expect(page.locator('[data-testid="roi-metric"]')).toBeVisible();
      await expect(page.locator('[data-testid="ctr-metric"]')).toBeVisible();
    });
  });

  // ============================================================================
  // PRODUCT AND BUDGET MANAGEMENT
  // ============================================================================

  test.describe('Product and Budget Management', () => {
    test('should associate products with campaign', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      await page.click('[data-testid="view-campaign"]:first-child');
      await page.click('[data-testid="products-tab"]');

      await page.click('[data-testid="add-product"]');
      await page.selectOption('[data-testid="product-select"]', { index: 0 });
      await page.click('[data-testid="confirm-add"]');

      await expect(page.locator('[data-testid="product-item"]')).toBeVisible();
    });

    test('should view campaign budget utilization', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      await page.click('[data-testid="view-campaign"]:first-child');
      await page.click('[data-testid="budget-tab"]');

      await expect(page.locator('[data-testid="budget-spent"]')).toBeVisible();
      await expect(page.locator('[data-testid="budget-remaining"]')).toBeVisible();
      await expect(page.locator('[data-testid="budget-progress-bar"]')).toBeVisible();
    });
  });

  // ============================================================================
  // SETTINGS AND NOTIFICATIONS
  // ============================================================================

  test.describe('Settings and Notifications', () => {
    test('should configure campaign notifications', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      await page.click('[data-testid="view-campaign"]:first-child');
      await page.click('[data-testid="settings-tab"]');

      await page.check('[data-testid="notify-on-completion"]');
      await page.check('[data-testid="notify-on-budget-alert"]');
      await page.fill('input[name="budget_alert_threshold"]', '80');

      await page.click('button[type="submit"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  // ============================================================================
  // MULTI-TENANCY AND PERMISSIONS
  // ============================================================================

  test.describe('Multi-tenancy and Permissions', () => {
    test('should enforce organization isolation', async ({ page, context }) => {
      // Create campaign in first org
      const campaignName = `Org1 Campaign ${Date.now()}`;
      await cmisHelper.createCampaign({ name: campaignName });

      // Switch to different organization
      await cmisHelper.selectOrganization('Test Organization 2');

      // Campaign from org1 should not be visible
      await cmisHelper.goToCampaigns();
      await expect(page.locator(`text=${campaignName}`)).not.toBeVisible();
    });
  });

  // ============================================================================
  // DATA EXPORT
  // ============================================================================

  test.describe('Data Export', () => {
    test('should export campaigns to CSV', async ({ page }) => {
      await cmisHelper.goToCampaigns();

      // Start waiting for download
      const downloadPromise = page.waitForEvent('download');

      // Click export button
      await page.click('[data-testid="export-campaigns"]');

      // Wait for download
      const download = await downloadPromise;

      // Verify filename
      expect(download.suggestedFilename()).toMatch(/campaigns.*\.csv/);
    });
  });
});
