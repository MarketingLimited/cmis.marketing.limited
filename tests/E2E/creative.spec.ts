import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Creative Assets E2E Tests
 */
test.describe('Creative Assets', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display creative assets page', async ({ page }) => {
    await cmisHelper.goToCreativeAssets();

    await expect(page.locator('h1')).toContainText(/creative|assets/i);
    await expect(page.locator('[data-testid="create-asset-button"]')).toBeVisible();
  });

  test('should create creative brief', async ({ page }) => {
    await page.goto('/creative/briefs');
    await page.click('[data-testid="create-brief"]');

    await page.fill('input[name="name"]', `E2E Brief ${Date.now()}`);
    await page.selectOption('[name="objective"]', 'brand_awareness');
    await page.fill('textarea[name="key_message"]', 'Test key message');
    await page.selectOption('[name="tone"]', 'professional');

    await page.click('button[type="submit"]');

    await cmisHelper.waitForNotification('Brief created successfully');
  });

  test('should upload creative asset', async ({ page }) => {
    await cmisHelper.goToCreativeAssets();
    await page.click('[data-testid="upload-asset"]');

    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles({
      name: 'creative-image.jpg',
      mimeType: 'image/jpeg',
      buffer: Buffer.from('fake-image-data'),
    });

    await page.fill('input[name="name"]', 'Test Asset');
    await page.click('button[type="submit"]');

    await cmisHelper.waitForNotification('Asset uploaded successfully');
  });

  test('should generate AI content', async ({ page }) => {
    await page.goto('/creative/ai-generation');

    await page.fill('textarea[name="prompt"]', 'Create a compelling ad copy for summer sale');
    await page.click('[data-testid="generate-content"]');

    await expect(page.locator('[data-testid="generated-content"]')).toBeVisible();
    await expect(page.locator('[data-testid="variation-1"]')).toBeVisible();
  });

  test('should manage content library', async ({ page }) => {
    await page.goto('/creative/library');

    await expect(page.locator('[data-testid="folder-list"]')).toBeVisible();
    await expect(page.locator('[data-testid="asset-grid"]')).toBeVisible();
  });

  test('should approve/reject creative', async ({ page }) => {
    await cmisHelper.goToCreativeAssets();

    await page.click('[data-testid="asset-card"]:first-child');
    await page.click('[data-testid="request-approval"]');

    // Switch to approver view
    await page.goto('/creative/approvals');
    await page.click('[data-testid="pending-approval"]:first-child [data-testid="approve"]');

    await cmisHelper.waitForNotification('Asset approved');
  });
});
