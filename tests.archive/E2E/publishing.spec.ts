import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Publishing Workflow E2E Tests
 *
 * Tests the complete publishing workflow:
 * - Queue configuration
 * - Post scheduling
 * - Multi-platform publishing
 * - Publishing status tracking
 */
test.describe('Publishing Workflow', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);

    await authHelper.login('admin@example.com', 'password');
  });

  test('should display publishing queue', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();

    await expect(page.locator('h1')).toContainText(/publishing|schedule/i);
    await expect(page.locator('[data-testid="schedule-post-button"]')).toBeVisible();
  });

  test('should schedule a post', async ({ page }) => {
    const postContent = `E2E Test Post ${Date.now()}`;
    const scheduledTime = new Date();
    scheduledTime.setHours(scheduledTime.getHours() + 2);

    await cmisHelper.schedulePost({
      content: postContent,
      platforms: ['facebook', 'instagram'],
      scheduledTime: scheduledTime.toISOString().slice(0, 16),
    });

    // Verify post appears in queue
    await expect(page.locator(`text=${postContent}`)).toBeVisible();

    // Verify notification
    await cmisHelper.waitForNotification('Post scheduled successfully');
  });

  test('should validate post content', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();
    await page.click('[data-testid="schedule-post-button"]');

    // Try to submit without content
    await page.click('button[type="submit"]');

    // Should show validation error
    await expect(page.locator('text=/content is required/i')).toBeVisible();
  });

  test('should select multiple platforms', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();
    await page.click('[data-testid="schedule-post-button"]');

    // Select platforms
    await page.check('input[name="platforms"][value="facebook"]');
    await page.check('input[name="platforms"][value="instagram"]');
    await page.check('input[name="platforms"][value="twitter"]');

    // All three should be checked
    await expect(page.locator('input[name="platforms"][value="facebook"]')).toBeChecked();
    await expect(page.locator('input[name="platforms"][value="instagram"]')).toBeChecked();
    await expect(page.locator('input[name="platforms"][value="twitter"]')).toBeChecked();
  });

  test('should edit scheduled post', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();

    // Click edit on first scheduled post
    await page.click('[data-testid="scheduled-post"]:first-child [data-testid="edit-button"]');

    // Update content
    const newContent = `Updated Post ${Date.now()}`;
    await page.fill('textarea[name="content"]', newContent);
    await page.click('button[type="submit"]');

    // Verify update
    await cmisHelper.waitForNotification('Post updated successfully');
    await expect(page.locator(`text=${newContent}`)).toBeVisible();
  });

  test('should delete scheduled post', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();

    const initialCount = await page.locator('[data-testid="scheduled-post"]').count();

    // Delete first post
    await page.click('[data-testid="scheduled-post"]:first-child [data-testid="delete-button"]');
    await page.click('[data-testid="confirm-delete"]');

    // Verify deletion
    await cmisHelper.waitForNotification('Post deleted successfully');

    const newCount = await page.locator('[data-testid="scheduled-post"]').count();
    expect(newCount).toBe(initialCount - 1);
  });

  test('should filter posts by status', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();

    // Filter by published
    await page.selectOption('[data-testid="status-filter"]', 'published');

    // All visible posts should have 'published' status
    const statusBadges = page.locator('[data-testid="post-status"]');
    const count = await statusBadges.count();

    for (let i = 0; i < count; i++) {
      await expect(statusBadges.nth(i)).toHaveText('published');
    }
  });

  test('should display publishing history', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();

    // Navigate to history tab
    await page.click('[data-testid="history-tab"]');

    // Should show published posts
    await expect(page.locator('[data-testid="published-post"]')).not.toHaveCount(0);

    // Should show timestamps
    await expect(page.locator('[data-testid="published-at"]').first()).toBeVisible();
  });

  test('should configure publishing queue', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();

    // Open queue configuration
    await page.click('[data-testid="configure-queue"]');

    // Set weekdays
    await page.check('input[name="weekdays"][value="monday"]');
    await page.check('input[name="weekdays"][value="wednesday"]');
    await page.check('input[name="weekdays"][value="friday"]');

    // Add time slots
    await page.click('[data-testid="add-time-slot"]');
    await page.fill('[data-testid="time-slot-0"]', '09:00');

    await page.click('[data-testid="add-time-slot"]');
    await page.fill('[data-testid="time-slot-1"]', '14:00');

    // Save configuration
    await page.click('button[type="submit"]');

    // Verify notification
    await cmisHelper.waitForNotification('Queue configured successfully');
  });

  test('should preview post before scheduling', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();
    await page.click('[data-testid="schedule-post-button"]');

    const content = 'Test post with #hashtags and @mentions';
    await page.fill('textarea[name="content"]', content);

    // Click preview button
    await page.click('[data-testid="preview-button"]');

    // Should show preview modal
    await expect(page.locator('[data-testid="preview-modal"]')).toBeVisible();
    await expect(page.locator('[data-testid="preview-content"]')).toContainText(content);

    // Should show platform-specific previews
    await expect(page.locator('[data-testid="facebook-preview"]')).toBeVisible();
    await expect(page.locator('[data-testid="instagram-preview"]')).toBeVisible();
  });

  test('should upload media with post', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();
    await page.click('[data-testid="schedule-post-button"]');

    // Upload image
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles({
      name: 'test-image.jpg',
      mimeType: 'image/jpeg',
      buffer: Buffer.from('fake-image-data'),
    });

    // Should show image preview
    await expect(page.locator('[data-testid="media-preview"]')).toBeVisible();

    // Should allow removal
    await page.click('[data-testid="remove-media"]');
    await expect(page.locator('[data-testid="media-preview"]')).not.toBeVisible();
  });

  test('should show character count for different platforms', async ({ page }) => {
    await cmisHelper.goToPublishingQueue();
    await page.click('[data-testid="schedule-post-button"]');

    // Select platforms
    await page.check('input[name="platforms"][value="twitter"]');
    await page.check('input[name="platforms"][value="facebook"]');

    // Type content
    const content = 'This is a test post with some content';
    await page.fill('textarea[name="content"]', content);

    // Should show character counts
    await expect(page.locator('[data-testid="twitter-char-count"]')).toBeVisible();
    await expect(page.locator('[data-testid="facebook-char-count"]')).toBeVisible();

    // Twitter should show limit warning if exceeded
    const longContent = 'a'.repeat(300);
    await page.fill('textarea[name="content"]', longContent);

    await expect(page.locator('[data-testid="twitter-char-warning"]')).toBeVisible();
  });
});
