import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Team Collaboration E2E Tests
 */
test.describe('Team Collaboration', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display team dashboard', async ({ page }) => {
    await page.goto('/team');

    await expect(page.locator('h1')).toContainText(/team|فريق/i);
    await expect(page.locator('[data-testid="team-members-grid"]')).toBeVisible();
  });

  test('should view team member profile', async ({ page }) => {
    await page.goto('/team');

    await page.click('[data-testid="view-member"]:first-child');

    await expect(page.locator('[data-testid="member-name"]')).toBeVisible();
    await expect(page.locator('[data-testid="member-role"]')).toBeVisible();
    await expect(page.locator('[data-testid="member-activity"]')).toBeVisible();
  });

  test('should assign task to team member', async ({ page }) => {
    await page.goto('/team/tasks');

    await page.click('[data-testid="create-task"]');

    await page.fill('input[name="title"]', 'إنشاء محتوى لحملة الصيف');
    await page.fill('textarea[name="description"]', 'نحتاج إلى 10 منشورات للحملة الصيفية');
    await page.selectOption('select[name="assignee"]', { index: 0 });
    await page.fill('input[name="due_date"]', '2024-07-01');
    await page.selectOption('select[name="priority"]', 'high');

    await page.click('[data-testid="create-task-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('text=إنشاء محتوى لحملة الصيف')).toBeVisible();
  });

  test('should update task status', async ({ page }) => {
    await page.goto('/team/tasks');

    await page.click('[data-testid="task-item"]:first-child');

    await page.selectOption('select[name="status"]', 'in_progress');

    await page.click('[data-testid="save-task"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should add comment to task', async ({ page }) => {
    await page.goto('/team/tasks');

    await page.click('[data-testid="task-item"]:first-child');

    await page.fill('textarea[name="comment"]', 'تم إكمال 5 منشورات حتى الآن');
    await page.click('[data-testid="add-comment"]');

    await expect(page.locator('[data-testid="task-comment"]')).toBeVisible();
    await expect(page.locator('text=تم إكمال 5 منشورات حتى الآن')).toBeVisible();
  });

  test('should create team project', async ({ page }) => {
    await page.goto('/team/projects');

    await page.click('[data-testid="create-project"]');

    await page.fill('input[name="name"]', 'حملة العودة إلى المدارس');
    await page.fill('textarea[name="description"]', 'مشروع شامل لحملة العودة إلى المدارس');
    await page.fill('input[name="start_date"]', '2024-08-01');
    await page.fill('input[name="end_date"]', '2024-09-15');

    // Assign team members
    await page.check('input[value="member_1"]');
    await page.check('input[value="member_2"]');
    await page.check('input[value="member_3"]');

    await page.click('[data-testid="create-project-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should create shared workspace', async ({ page }) => {
    await page.goto('/team/workspaces');

    await page.click('[data-testid="create-workspace"]');

    await page.fill('input[name="name"]', 'مساحة عمل التسويق');
    await page.fill('textarea[name="description"]', 'مساحة مشتركة لفريق التسويق');

    await page.click('[data-testid="create-workspace-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should share file with team', async ({ page }) => {
    await page.goto('/team/files');

    await page.setInputFiles('input[type="file"]', './test-assets/document.pdf');

    await page.click('[data-testid="upload-file"]');

    await page.click('[data-testid="share-file"]:first-child');

    await page.check('input[value="member_1"]');
    await page.check('input[value="member_2"]');

    await page.selectOption('select[name="permission"]', 'edit');

    await page.click('[data-testid="confirm-share"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should create team announcement', async ({ page }) => {
    await page.goto('/team/announcements');

    await page.click('[data-testid="create-announcement"]');

    await page.fill('input[name="title"]', 'تحديث مهم على النظام');
    await page.fill('textarea[name="message"]', 'سيتم إجراء صيانة للنظام يوم الجمعة');
    await page.selectOption('select[name="priority"]', 'high');

    await page.click('[data-testid="post-announcement"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should schedule team meeting', async ({ page }) => {
    await page.goto('/team/calendar');

    await page.click('[data-testid="schedule-meeting"]');

    await page.fill('input[name="title"]', 'اجتماع مراجعة الحملات');
    await page.fill('input[name="date"]', '2024-07-15');
    await page.fill('input[name="time"]', '10:00');
    await page.fill('input[name="duration"]', '60');

    await page.check('input[value="member_1"]');
    await page.check('input[value="member_2"]');
    await page.check('input[value="member_3"]');

    await page.fill('input[name="meeting_link"]', 'https://meet.example.com/abc123');

    await page.click('[data-testid="schedule-meeting-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should view team calendar', async ({ page }) => {
    await page.goto('/team/calendar');

    await expect(page.locator('[data-testid="calendar-view"]')).toBeVisible();
    await expect(page.locator('[data-testid="calendar-event"]')).toBeVisible();
  });

  test('should filter tasks by assignee', async ({ page }) => {
    await page.goto('/team/tasks');

    await page.selectOption('[data-testid="assignee-filter"]', 'member_1');

    await page.waitForTimeout(500);

    const tasks = page.locator('[data-testid="task-item"]');
    const count = await tasks.count();

    for (let i = 0; i < count; i++) {
      const assignee = await tasks.nth(i).getAttribute('data-assignee');
      expect(assignee).toBe('member_1');
    }
  });

  test('should mention team member in comment', async ({ page }) => {
    await page.goto('/team/tasks');

    await page.click('[data-testid="task-item"]:first-child');

    await page.fill('textarea[name="comment"]', '@ahmed محمد هل يمكنك المساعدة في هذا؟');
    await page.click('[data-testid="add-comment"]');

    await expect(page.locator('[data-testid="mention"]')).toBeVisible();
  });

  test('should view team activity feed', async ({ page }) => {
    await page.goto('/team/activity');

    await expect(page.locator('[data-testid="activity-feed"]')).toBeVisible();
    await expect(page.locator('[data-testid="activity-item"]')).toBeVisible();
  });

  test('should filter activity by type', async ({ page }) => {
    await page.goto('/team/activity');

    await page.selectOption('[data-testid="activity-type-filter"]', 'task_created');

    await page.waitForTimeout(500);

    const activities = page.locator('[data-testid="activity-item"]');
    const count = await activities.count();

    for (let i = 0; i < count; i++) {
      const type = await activities.nth(i).getAttribute('data-type');
      expect(type).toBe('task_created');
    }
  });

  test('should create team goal', async ({ page }) => {
    await page.goto('/team/goals');

    await page.click('[data-testid="create-goal"]');

    await page.fill('input[name="title"]', 'زيادة التفاعل بنسبة 25%');
    await page.fill('textarea[name="description"]', 'زيادة التفاعل على جميع المنصات');
    await page.fill('input[name="target_date"]', '2024-12-31');
    await page.fill('input[name="target_value"]', '25');

    await page.click('[data-testid="create-goal-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should track goal progress', async ({ page }) => {
    await page.goto('/team/goals');

    await page.click('[data-testid="update-progress"]:first-child');

    await page.fill('input[name="current_value"]', '15');
    await page.fill('textarea[name="notes"]', 'تقدم جيد حتى الآن');

    await page.click('[data-testid="save-progress"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="progress-bar"]')).toBeVisible();
  });

  test('should create knowledge base article', async ({ page }) => {
    await page.goto('/team/knowledge-base');

    await page.click('[data-testid="create-article"]');

    await page.fill('input[name="title"]', 'كيفية إنشاء حملة إعلانية ناجحة');
    await page.fill('textarea[name="content"]', 'دليل شامل لإنشاء حملات إعلانية فعالة...');

    await page.check('input[value="marketing"]');
    await page.check('input[value="campaigns"]');

    await page.click('[data-testid="publish-article"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should search knowledge base', async ({ page }) => {
    await page.goto('/team/knowledge-base');

    await page.fill('[data-testid="search-input"]', 'حملة إعلانية');
    await page.click('[data-testid="search-button"]');

    await expect(page.locator('[data-testid="article-result"]')).toBeVisible();
  });

  test('should add bookmark to knowledge article', async ({ page }) => {
    await page.goto('/team/knowledge-base');

    await page.click('[data-testid="article-item"]:first-child');

    await page.click('[data-testid="bookmark-article"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should view team performance metrics', async ({ page }) => {
    await page.goto('/team/performance');

    await expect(page.locator('[data-testid="performance-dashboard"]')).toBeVisible();
    await expect(page.locator('[data-testid="tasks-completed"]')).toBeVisible();
    await expect(page.locator('[data-testid="average-completion-time"]')).toBeVisible();
    await expect(page.locator('[data-testid="team-productivity"]')).toBeVisible();
  });

  test('should export team report', async ({ page }) => {
    await page.goto('/team/performance');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-report"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('team-report');
  });

  test('should configure team notifications', async ({ page }) => {
    await page.goto('/team/settings');

    await page.check('input[value="task_assigned"]');
    await page.check('input[value="task_completed"]');
    await page.check('input[value="mention_received"]');
    await page.check('input[value="meeting_scheduled"]');

    await page.click('[data-testid="save-team-settings"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should create team template', async ({ page }) => {
    await page.goto('/team/templates');

    await page.click('[data-testid="create-template"]');

    await page.fill('input[name="name"]', 'قالب حملة السوشيال ميديا');
    await page.fill('textarea[name="description"]', 'قالب قياسي لحملات السوشيال ميديا');

    await page.click('[data-testid="save-template"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should use team template', async ({ page }) => {
    await page.goto('/team/templates');

    await page.click('[data-testid="use-template"]:first-child');

    await expect(page).toHaveURL(/\/campaigns\/create/);
  });
});
