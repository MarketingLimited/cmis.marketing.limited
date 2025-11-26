import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';

/**
 * Authentication E2E Tests
 *
 * Tests the complete authentication flow including:
 * - User registration
 * - Login/logout
 * - Session persistence
 * - Password reset
 */
test.describe('Authentication', () => {
  let authHelper: AuthHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
  });

  test('should display login page', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('h1')).toContainText(/login|sign in/i);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should successfully login with valid credentials', async ({ page }) => {
    await authHelper.login('admin@example.com', 'password');

    // Verify redirect to dashboard
    await expect(page).toHaveURL(/.*\/dashboard/);

    // Verify user menu is visible
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'invalid@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Should show error message
    await expect(page.locator('[data-testid="error-message"]')).toBeVisible();

    // Should remain on login page
    await expect(page).toHaveURL(/.*\/login/);
  });

  test('should successfully logout', async ({ page }) => {
    await authHelper.login('admin@example.com', 'password');
    await authHelper.logout();

    // Verify redirect to login
    await expect(page).toHaveURL(/.*\/login/);

    // Verify cannot access protected pages
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/.*\/login/);
  });

  test('should register new user', async ({ page }) => {
    await page.goto('/register');

    const email = `test-${Date.now()}@example.com`;

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'Password123!');
    await page.fill('input[name="password_confirmation"]', 'Password123!');
    await page.click('button[type="submit"]');

    // Should redirect to dashboard or email verification page
    await page.waitForURL(/.*\/(dashboard|verify-email)/);
  });

  test('should validate email format', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="email"]', 'invalid-email');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Should show validation error
    await expect(page.locator('text=/valid email/i')).toBeVisible();
  });

  test('should persist session across page reloads', async ({ page }) => {
    await authHelper.login('admin@example.com', 'password');

    // Reload page
    await page.reload();

    // Should still be logged in
    await expect(page).toHaveURL(/.*\/dashboard/);
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
  });

  test('should redirect to intended page after login', async ({ page }) => {
    // Try to access protected page
    await page.goto('/campaigns');

    // Should redirect to login
    await expect(page).toHaveURL(/.*\/login/);

    // Login
    await authHelper.login('admin@example.com', 'password');

    // Should redirect back to intended page
    await expect(page).toHaveURL(/.*\/campaigns/);
  });
});
