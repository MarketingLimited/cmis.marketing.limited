import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';

/**
 * Settings & User Management E2E Tests
 */
test.describe('Settings & User Management', () => {
  let authHelper: AuthHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display settings page', async ({ page }) => {
    await page.goto('/settings');

    await expect(page.locator('h1')).toContainText(/settings/i);
    await expect(page.locator('[data-testid="settings-menu"]')).toBeVisible();
  });

  test('should update user profile', async ({ page }) => {
    await page.goto('/settings/profile');

    await page.fill('input[name="name"]', 'Updated Name');
    await page.fill('input[name="email"]', 'updated@example.com');

    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should change password', async ({ page }) => {
    await page.goto('/settings/security');

    await page.fill('input[name="current_password"]', 'password');
    await page.fill('input[name="new_password"]', 'NewPassword123!');
    await page.fill('input[name="new_password_confirmation"]', 'NewPassword123!');

    await page.click('[data-testid="change-password-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should manage team members', async ({ page }) => {
    await page.goto('/settings/team');

    await page.click('[data-testid="invite-member"]');
    await page.fill('input[name="email"]', 'newmember@example.com');
    await page.selectOption('[name="role"]', 'editor');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Invitation sent')).toBeVisible();
  });

  test('should configure notification preferences', async ({ page }) => {
    await page.goto('/settings/notifications');

    await page.check('[name="notifications"][value="email"]');
    await page.check('[name="notifications"][value="browser"]');

    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should manage organization settings', async ({ page }) => {
    await page.goto('/settings/organization');

    await page.fill('input[name="organization_name"]', 'Updated Org Name');
    await page.selectOption('[name="currency"]', 'USD');
    await page.selectOption('[name="timezone"]', 'America/New_York');

    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should manage user permissions', async ({ page }) => {
    await page.goto('/settings/permissions');

    await page.click('[data-testid="user-row"]:first-child [data-testid="edit-permissions"]');

    await page.check('[name="permissions"][value="campaigns.create"]');
    await page.check('[name="permissions"][value="campaigns.edit"]');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Permissions updated')).toBeVisible();
  });
});
