import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * AI Features E2E Tests
 */
test.describe('AI Features', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display AI dashboard', async ({ page }) => {
    await page.goto('/ai');

    await expect(page.locator('h1')).toContainText(/AI|artificial intelligence/i);
    await expect(page.locator('[data-testid="ai-features"]')).toBeVisible();
  });

  test('should perform semantic search', async ({ page }) => {
    await page.goto('/ai/search');

    await page.fill('[data-testid="search-input"]', 'marketing campaigns for summer');
    await page.click('[data-testid="search-button"]');

    await expect(page.locator('[data-testid="search-results"]')).toBeVisible();
    await expect(page.locator('[data-testid="result-item"]')).not.toHaveCount(0);
  });

  test('should generate AI insights', async ({ page }) => {
    await page.goto('/ai/insights');

    await page.click('[data-testid="generate-insights"]');

    await expect(page.locator('[data-testid="insight-card"]')).toBeVisible();
    await expect(page.locator('[data-testid="recommendation"]')).not.toHaveCount(0);
  });

  test('should show AI-powered recommendations', async ({ page }) => {
    await page.goto('/ai/recommendations');

    await expect(page.locator('[data-testid="recommendation-list"]')).toBeVisible();
    await expect(page.locator('[data-testid="confidence-score"]').first()).toBeVisible();
  });

  test('should use AI campaign generator', async ({ page }) => {
    await page.goto('/ai/campaign-generator');

    await page.fill('textarea[name="brief"]', 'Generate a campaign for new product launch');
    await page.selectOption('[name="target_audience"]', 'millennials');
    await page.selectOption('[name="objective"]', 'conversions');

    await page.click('[data-testid="generate-campaign"]');

    await expect(page.locator('[data-testid="generated-campaign"]')).toBeVisible();
    await expect(page.locator('[data-testid="campaign-name"]')).toBeVisible();
  });

  test('should detect anomalies in campaign data', async ({ page }) => {
    await page.goto('/ai/anomaly-detection');

    await page.click('[data-testid="scan-anomalies"]');

    await expect(page.locator('[data-testid="anomaly-list"]')).toBeVisible();
  });

  test('should suggest optimal posting times', async ({ page }) => {
    await page.goto('/ai/best-times');

    await expect(page.locator('[data-testid="time-suggestions"]')).toBeVisible();
    await expect(page.locator('[data-testid="engagement-forecast"]')).toBeVisible();
  });

  test('should provide content suggestions', async ({ page }) => {
    await page.goto('/ai/content-suggestions');

    await page.selectOption('[name="platform"]', 'instagram');
    await page.selectOption('[name="content_type"]', 'post');

    await page.click('[data-testid="get-suggestions"]');

    await expect(page.locator('[data-testid="suggestion-card"]')).not.toHaveCount(0);
  });
});
