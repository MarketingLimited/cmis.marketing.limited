import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Content Planning E2E Tests
 */
test.describe('Content Planning & Scheduling', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display content calendar', async ({ page }) => {
    await page.goto('/content/calendar');

    await expect(page.locator('h1')).toContainText(/content calendar|تقويم المحتوى/i);
    await expect(page.locator('[data-testid="calendar-view"]')).toBeVisible();
  });

  test('should create content plan', async ({ page }) => {
    await page.goto('/content/plans/create');

    await page.fill('input[name="name"]', 'Q2 2024 Content Plan');
    await page.selectOption('select[name="campaign_id"]', { index: 0 });

    const startDate = new Date('2024-04-01');
    const endDate = new Date('2024-06-30');

    await page.fill('input[name="start_date"]', startDate.toISOString().split('T')[0]);
    await page.fill('input[name="end_date"]', endDate.toISOString().split('T')[0]);

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Q2 2024 Content Plan')).toBeVisible();
  });

  test('should create content item for social media', async ({ page }) => {
    await page.goto('/content/items/create');

    // Basic info
    await page.fill('input[name="title"]', 'Summer Product Launch Post');
    await page.selectOption('select[name="plan_id"]', { index: 0 });

    // Channel & Format
    await page.selectOption('select[name="channel"]', 'instagram');
    await page.selectOption('select[name="format"]', 'image');

    // Content
    await page.fill('textarea[name="caption"]', 'Introducing our new summer collection! 🌞 #Summer2024');

    // Media
    await page.setInputFiles('input[type="file"]', './test-assets/sample-image.jpg');

    // Scheduling
    const scheduledDate = new Date();
    scheduledDate.setDate(scheduledDate.getDate() + 3);
    scheduledDate.setHours(10, 0, 0, 0);

    await page.fill('input[name="scheduled_at"]', scheduledDate.toISOString().slice(0, 16));

    // Submit
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Summer Product Launch Post')).toBeVisible();
  });

  test('should schedule multiple posts across platforms', async ({ page }) => {
    await page.goto('/content/bulk-schedule');

    // Select platforms
    await page.check('input[value="facebook"]');
    await page.check('input[value="instagram"]');
    await page.check('input[value="twitter"]');

    // Content
    await page.fill('textarea[name="content"]', 'Check out our amazing new products!');

    // Upload media
    await page.setInputFiles('input[type="file"]', './test-assets/sample-image.jpg');

    // Schedule times
    await page.fill('input[name="facebook_time"]', '10:00');
    await page.fill('input[name="instagram_time"]', '11:00');
    await page.fill('input[name="twitter_time"]', '12:00');

    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('text=3 posts scheduled')).toBeVisible();
  });

  test('should view content calendar in month view', async ({ page }) => {
    await page.goto('/content/calendar');

    await page.click('[data-testid="month-view"]');

    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
    await expect(page.locator('[data-testid="scheduled-content"]')).toBeVisible();
  });

  test('should view content calendar in week view', async ({ page }) => {
    await page.goto('/content/calendar');

    await page.click('[data-testid="week-view"]');

    await expect(page.locator('[data-testid="week-grid"]')).toBeVisible();
  });

  test('should drag and drop to reschedule content', async ({ page }) => {
    await page.goto('/content/calendar');

    const contentItem = page.locator('[data-testid="content-item"]').first();
    const newSlot = page.locator('[data-date="2024-06-15"][data-hour="14"]');

    await contentItem.dragTo(newSlot);

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should generate AI-powered content suggestions', async ({ page }) => {
    await page.goto('/content/items/create');

    await page.fill('textarea[name="brief"]', 'Create engaging content about our summer fashion collection');

    await page.click('[data-testid="generate-ai-content"]');

    await page.waitForSelector('[data-testid="ai-suggestions"]', { timeout: 10000 });

    await expect(page.locator('[data-testid="ai-suggestions"]')).toBeVisible();
    await expect(page.locator('[data-testid="suggestion-item"]')).toHaveCount(3);
  });

  test('should preview content before publishing', async ({ page }) => {
    await page.goto('/content/items/create');

    await page.fill('textarea[name="caption"]', 'Amazing summer sale! #Sale');
    await page.setInputFiles('input[type="file"]', './test-assets/sample-image.jpg');

    await page.click('[data-testid="preview-button"]');

    await expect(page.locator('[data-testid="preview-modal"]')).toBeVisible();
    await expect(page.locator('[data-testid="instagram-preview"]')).toBeVisible();
    await expect(page.locator('[data-testid="facebook-preview"]')).toBeVisible();
  });

  test('should approve content workflow', async ({ page }) => {
    await page.goto('/content/approvals');

    const pendingItem = page.locator('[data-status="pending_approval"]').first();
    await pendingItem.locator('[data-testid="review-button"]').click();

    await page.fill('textarea[name="feedback"]', 'Looks great! Approved.');
    await page.click('[data-testid="approve-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should reject and request revisions', async ({ page }) => {
    await page.goto('/content/approvals');

    const pendingItem = page.locator('[data-status="pending_approval"]').first();
    await pendingItem.locator('[data-testid="review-button"]').click();

    await page.fill('textarea[name="feedback"]', 'Please update the image and adjust caption tone');
    await page.click('[data-testid="request-revisions-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should publish approved content immediately', async ({ page }) => {
    await page.goto('/content/items');

    const approvedItem = page.locator('[data-status="approved"]').first();
    await approvedItem.locator('[data-testid="publish-now"]').click();

    await page.click('[data-testid="confirm-publish"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('text=Published successfully')).toBeVisible();
  });

  test('should view content performance analytics', async ({ page }) => {
    await page.goto('/content/analytics');

    await expect(page.locator('[data-testid="total-impressions"]')).toBeVisible();
    await expect(page.locator('[data-testid="engagement-rate"]')).toBeVisible();
    await expect(page.locator('[data-testid="best-performing-content"]')).toBeVisible();
    await expect(page.locator('[data-testid="performance-chart"]')).toBeVisible();
  });

  test('should filter content by platform', async ({ page }) => {
    await page.goto('/content/items');

    await page.selectOption('[data-testid="platform-filter"]', 'instagram');

    await page.waitForTimeout(500);

    const items = page.locator('[data-testid="content-item"]');
    const count = await items.count();

    for (let i = 0; i < count; i++) {
      const platform = await items.nth(i).getAttribute('data-platform');
      expect(platform).toContain('instagram');
    }
  });

  test('should bulk edit content items', async ({ page }) => {
    await page.goto('/content/items');

    // Select multiple items
    await page.check('[data-testid="select-item"]:nth-child(1)');
    await page.check('[data-testid="select-item"]:nth-child(2)');
    await page.check('[data-testid="select-item"]:nth-child(3)');

    await page.click('[data-testid="bulk-actions"]');
    await page.click('[data-testid="bulk-edit"]');

    // Change status
    await page.selectOption('select[name="status"]', 'scheduled');

    await page.click('[data-testid="apply-changes"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should delete scheduled content', async ({ page }) => {
    await page.goto('/content/items');

    const item = page.locator('[data-testid="content-item"]').first();
    await item.locator('[data-testid="delete-button"]').click();

    await page.fill('[data-testid="confirm-delete-input"]', 'DELETE');
    await page.click('[data-testid="confirm-delete-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should export content calendar to PDF', async ({ page }) => {
    await page.goto('/content/calendar');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-pdf"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.pdf');
  });
});
