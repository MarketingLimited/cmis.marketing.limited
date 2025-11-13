import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Platform Integrations E2E Tests - All Platforms
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

    await expect(page.locator('h1')).toContainText(/integrations|التكاملات/i);
    await expect(page.locator('[data-testid="integrations-grid"]')).toBeVisible();
  });

  test('should connect Facebook account', async ({ page }) => {
    await page.goto('/integrations/facebook/connect');

    // Mock OAuth flow
    await page.fill('input[name="access_token"]', 'test_facebook_token');
    await page.click('[data-testid="connect-facebook"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('text=Facebook connected')).toBeVisible();
  });

  test('should connect Instagram Business account', async ({ page }) => {
    await page.goto('/integrations/instagram/connect');

    await page.fill('input[name="access_token"]', 'test_instagram_token');
    await page.fill('input[name="instagram_business_id"]', 'ig_business_123');
    await page.click('[data-testid="connect-instagram"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect TikTok account', async ({ page }) => {
    await page.goto('/integrations/tiktok/connect');

    await page.fill('input[name="access_token"]', 'test_tiktok_token');
    await page.fill('input[name="advertiser_id"]', 'tiktok_adv_123');
    await page.click('[data-testid="connect-tiktok"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect Twitter/X account', async ({ page }) => {
    await page.goto('/integrations/twitter/connect');

    await page.fill('input[name="access_token"]', 'test_twitter_token');
    await page.fill('input[name="access_token_secret"]', 'test_twitter_secret');
    await page.click('[data-testid="connect-twitter"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect LinkedIn account', async ({ page }) => {
    await page.goto('/integrations/linkedin/connect');

    await page.fill('input[name="access_token"]', 'test_linkedin_token');
    await page.click('[data-testid="connect-linkedin"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect YouTube account', async ({ page }) => {
    await page.goto('/integrations/youtube/connect');

    await page.fill('input[name="access_token"]', 'test_youtube_token');
    await page.fill('input[name="channel_id"]', 'youtube_channel_123');
    await page.click('[data-testid="connect-youtube"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect Snapchat account', async ({ page }) => {
    await page.goto('/integrations/snapchat/connect');

    await page.fill('input[name="access_token"]', 'test_snapchat_token');
    await page.fill('input[name="organization_id"]', 'snap_org_123');
    await page.click('[data-testid="connect-snapchat"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect WhatsApp Business API', async ({ page }) => {
    await page.goto('/integrations/whatsapp/connect');

    await page.fill('input[name="access_token"]', 'test_whatsapp_token');
    await page.fill('input[name="phone_number_id"]', 'whatsapp_phone_123');
    await page.fill('input[name="business_account_id"]', 'whatsapp_business_123');
    await page.click('[data-testid="connect-whatsapp"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect Google Business Profile', async ({ page }) => {
    await page.goto('/integrations/google-business/connect');

    await page.fill('input[name="access_token"]', 'test_google_token');
    await page.fill('input[name="location_id"]', 'google_location_123');
    await page.click('[data-testid="connect-google-business"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect Google Analytics', async ({ page }) => {
    await page.goto('/integrations/google-analytics/connect');

    await page.fill('input[name="access_token"]', 'test_ga_token');
    await page.fill('input[name="property_id"]', 'ga_property_123');
    await page.click('[data-testid="connect-google-analytics"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect Google Ads account', async ({ page }) => {
    await page.goto('/integrations/google-ads/connect');

    await page.fill('input[name="access_token"]', 'test_googleads_token');
    await page.fill('input[name="customer_id"]', '123-456-7890');
    await page.click('[data-testid="connect-google-ads"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should connect Meta Ads account', async ({ page }) => {
    await page.goto('/integrations/meta-ads/connect');

    await page.fill('input[name="access_token"]', 'test_meta_token');
    await page.fill('input[name="ad_account_id"]', 'act_123456');
    await page.click('[data-testid="connect-meta-ads"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should view connected accounts', async ({ page }) => {
    await page.goto('/integrations');

    await expect(page.locator('[data-testid="connected-account"]')).toBeVisible();
    await expect(page.locator('[data-testid="account-status"]')).toContainText(/connected|متصل/i);
  });

  test('should refresh integration token', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="refresh-token"]').click();

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('text=Token refreshed')).toBeVisible();
  });

  test('should disconnect integration', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="disconnect-button"]').click();

    await page.click('[data-testid="confirm-disconnect"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('text=Disconnected')).toBeVisible();
  });

  test('should test integration connection', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="test-connection"]').click();

    await expect(page.locator('[data-testid="connection-status"]')).toBeVisible();
    await expect(page.locator('text=Connection successful')).toBeVisible();
  });

  test('should view integration permissions', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="view-permissions"]').click();

    await expect(page.locator('[data-testid="permissions-modal"]')).toBeVisible();
    await expect(page.locator('[data-testid="permission-item"]')).toBeVisible();
  });

  test('should configure webhook settings', async ({ page }) => {
    await page.goto('/integrations/webhooks');

    await page.click('[data-testid="configure-webhooks"]');

    await page.fill('input[name="webhook_url"]', 'https://example.com/webhooks');
    await page.fill('input[name="webhook_secret"]', 'secret_key_123');

    await page.check('input[value="messages"]');
    await page.check('input[value="comments"]');
    await page.check('input[value="posts"]');

    await page.click('[data-testid="save-webhook"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should view integration activity log', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="view-activity"]').click();

    await expect(page.locator('[data-testid="activity-log"]')).toBeVisible();
    await expect(page.locator('[data-testid="activity-item"]')).toBeVisible();
  });

  test('should handle token expiration error', async ({ page }) => {
    await page.goto('/integrations');

    const expiredIntegration = page.locator('[data-status="expired"]').first();

    await expect(expiredIntegration.locator('[data-testid="expired-badge"]')).toBeVisible();
    await expect(expiredIntegration.locator('[data-testid="reconnect-button"]')).toBeVisible();
  });

  test('should filter integrations by status', async ({ page }) => {
    await page.goto('/integrations');

    await page.selectOption('[data-testid="status-filter"]', 'connected');

    await page.waitForTimeout(500);

    const integrations = page.locator('[data-testid="integration-card"]');
    const count = await integrations.count();

    for (let i = 0; i < count; i++) {
      const status = await integrations.nth(i).getAttribute('data-status');
      expect(status).toBe('connected');
    }
  });

  test('should search integrations', async ({ page }) => {
    await page.goto('/integrations');

    await page.fill('[data-testid="search-input"]', 'Facebook');
    await page.click('[data-testid="search-button"]');

    await expect(page.locator('text=Facebook')).toBeVisible();
  });

  test('should sync data from integration', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="sync-data"]').click();

    await expect(page.locator('[data-testid="sync-progress"]')).toBeVisible();
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible({ timeout: 10000 });
  });

  test('should schedule automatic data sync', async ({ page }) => {
    await page.goto('/integrations');

    const integration = page.locator('[data-testid="integration-card"]').first();
    await integration.locator('[data-testid="settings"]').click();

    await page.check('[data-testid="enable-auto-sync"]');
    await page.selectOption('[data-testid="sync-frequency"]', 'hourly');

    await page.click('[data-testid="save-settings"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });
});
