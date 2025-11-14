import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Platform Integrations E2E Tests
 */
test.describe('Platform Integrations', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display integrations page', async ({ page }) => {
    await page.goto('/integrations');

    await expect(page.locator('h1')).toContainText(/integrations/i);
    await expect(page.locator('[data-testid="available-platforms"]')).toBeVisible();
  });

  test('should show Facebook integration option', async ({ page }) => {
    await page.goto('/integrations');

    await expect(page.locator('[data-testid="facebook-integration"]')).toBeVisible();
    await expect(page.locator('[data-testid="connect-facebook"]')).toBeVisible();
  });

  test('should display connected accounts', async ({ page }) => {
    await page.goto('/integrations');

    await expect(page.locator('[data-testid="connected-accounts"]')).toBeVisible();
  });

  test('should disconnect integration', async ({ page }) => {
    await page.goto('/integrations');

    const connectedAccounts = page.locator('[data-testid="connected-account"]');

    if ((await connectedAccounts.count()) > 0) {
      await connectedAccounts.first().locator('[data-testid="disconnect"]').click();
      await page.click('[data-testid="confirm-disconnect"]');

      await cmisHelper.waitForNotification('Integration disconnected');
    }
  });

  test('should sync platform data', async ({ page }) => {
    await page.goto('/integrations');

    await page.click('[data-testid="connected-account"]:first-child [data-testid="sync-now"]');

    await cmisHelper.waitForNotification('Sync started');
    await expect(page.locator('[data-testid="sync-status"]')).toContainText(/syncing|in progress/i);
  });

  test('should view sync history', async ({ page }) => {
    await page.goto('/integrations');

    await page.click('[data-testid="connected-account"]:first-child [data-testid="view-history"]');

    await expect(page.locator('[data-testid="sync-log"]')).not.toHaveCount(0);
    await expect(page.locator('[data-testid="sync-timestamp"]').first()).toBeVisible();
  });

  test('should configure webhook', async ({ page }) => {
    await page.goto('/integrations/webhooks');

    await page.click('[data-testid="create-webhook"]');
    await page.fill('input[name="url"]', 'https://example.com/webhook');
    await page.check('[name="events"][value="campaign.created"]');
    await page.check('[name="events"][value="post.published"]');

    await page.click('button[type="submit"]');

    await cmisHelper.waitForNotification('Webhook created');
  });

  test('should manage API keys', async ({ page }) => {
    await page.goto('/integrations/api-keys');

    await page.click('[data-testid="generate-api-key"]');
    await page.fill('input[name="name"]', 'Test API Key');

    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="api-key-value"]')).toBeVisible();
  });
});
