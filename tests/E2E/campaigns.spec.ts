import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Campaign Management E2E Tests
 *
 * Tests the complete campaign lifecycle:
 * - Campaign creation
 * - Campaign listing and filtering
 * - Campaign editing
 * - Campaign deletion
 * - Multi-org isolation
 */
test.describe('Campaign Management', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);

    // Login before each test
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display campaigns page', async ({ page }) => {
    await cmisHelper.goToCampaigns();

    await expect(page.locator('h1')).toContainText(/campaigns/i);
    await expect(page.locator('[data-testid="create-campaign-button"]')).toBeVisible();
  });

  test('should create a new campaign', async ({ page }) => {
    const campaignName = `E2E Test Campaign ${Date.now()}`;

    await cmisHelper.createCampaign({
      name: campaignName,
      objective: 'awareness',
      budget: 5000,
      startDate: '2025-02-01',
      endDate: '2025-02-28',
    });

    // Verify campaign appears in list
    await expect(page.locator(`text=${campaignName}`)).toBeVisible();

    // Verify notification
    await cmisHelper.waitForNotification('Campaign created successfully');
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

  test('should filter campaigns by status', async ({ page }) => {
    await cmisHelper.filterCampaignsByStatus('active');

    // All visible campaigns should have 'active' status
    const statusBadges = page.locator('[data-testid="campaign-status"]');
    const count = await statusBadges.count();

    for (let i = 0; i < count; i++) {
      await expect(statusBadges.nth(i)).toHaveText('active');
    }
  });

  test('should search campaigns', async ({ page }) => {
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

    // Click first campaign
    await page.click('[data-testid="campaign-row"]:first-child [data-testid="view-button"]');

    // Navigate to analytics tab
    await page.click('[data-testid="analytics-tab"]');

    // Should show performance metrics
    await expect(page.locator('[data-testid="impressions-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="clicks-metric"]')).toBeVisible();
    await expect(page.locator('[data-testid="spend-metric"]')).toBeVisible();
  });

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
