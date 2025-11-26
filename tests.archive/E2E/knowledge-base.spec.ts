import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Knowledge Base & AI Features E2E Tests
 */
test.describe('Knowledge Base & AI Features', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display knowledge base dashboard', async ({ page }) => {
    await page.goto('/knowledge');

    await expect(page.locator('h1')).toContainText(/knowledge|معرفة|قاعدة المعرفة/i);
    await expect(page.locator('[data-testid="knowledge-grid"]')).toBeVisible();
  });

  test('should create knowledge entry', async ({ page }) => {
    await page.goto('/knowledge/create');

    await page.fill('input[name="title"]', 'استراتيجيات التسويق الرقمي');
    await page.fill('textarea[name="content"]', 'دليل شامل لأفضل استراتيجيات التسويق الرقمي في 2024');

    await page.selectOption('select[name="category"]', 'marketing_strategy');

    await page.fill('input[name="tags"]', 'تسويق, استراتيجية, رقمي');

    await page.click('[data-testid="save-entry"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should perform semantic search', async ({ page }) => {
    await page.goto('/knowledge');

    await page.fill('[data-testid="semantic-search"]', 'كيفية زيادة التفاعل على إنستقرام');
    await page.click('[data-testid="search-button"]');

    await expect(page.locator('[data-testid="search-results"]')).toBeVisible();
    await expect(page.locator('[data-testid="similarity-score"]')).toBeVisible();
  });

  test('should generate AI content suggestions', async ({ page }) => {
    await page.goto('/knowledge/ai-assistant');

    await page.fill('textarea[name="prompt"]', 'اقترح أفكار لحملة تسويقية لمنتج جديد');
    await page.click('[data-testid="generate-suggestions"]');

    await expect(page.locator('[data-testid="ai-suggestions"]')).toBeVisible();
    await expect(page.locator('[data-testid="suggestion-item"]')).toBeVisible();
  });

  test('should generate campaign brief with AI', async ({ page }) => {
    await page.goto('/creative-briefs/create');

    await page.click('[data-testid="ai-generate-brief"]');

    await page.fill('textarea[name="product_description"]', 'قميص صيفي قطني عالي الجودة');
    await page.fill('input[name="target_audience"]', 'شباب 18-35');
    await page.fill('input[name="campaign_goal"]', 'زيادة المبيعات');

    await page.click('[data-testid="generate-brief"]');

    await expect(page.locator('[data-testid="generated-brief"]')).toBeVisible();
    await expect(page.locator('input[name="marketing_objective"]')).not.toBeEmpty();
  });

  test('should generate visual description with AI', async ({ page }) => {
    await page.goto('/creative-briefs/create');

    await page.fill('textarea[name="product_info"]', 'قميص أزرق، قطني، عصري');

    await page.click('[data-testid="ai-generate-visual"]');

    await expect(page.locator('textarea[name="art_direction_description"]')).not.toBeEmpty();
  });

  test('should extract keywords from content', async ({ page }) => {
    await page.goto('/knowledge/ai-assistant');

    await page.fill('textarea[name="content"]', 'التسويق الرقمي هو أحد أهم أدوات النمو في العصر الحديث. يشمل السوشيال ميديا والإعلانات المدفوعة');

    await page.click('[data-testid="extract-keywords"]');

    await expect(page.locator('[data-testid="extracted-keywords"]')).toBeVisible();
    await expect(page.locator('[data-testid="keyword-item"]')).toBeVisible();
  });

  test('should generate hashtags with AI', async ({ page }) => {
    await page.goto('/content/create');

    await page.fill('textarea[name="caption"]', 'خصومات الصيف على جميع المنتجات!');

    await page.click('[data-testid="generate-hashtags"]');

    await expect(page.locator('[data-testid="suggested-hashtags"]')).toBeVisible();
    await expect(page.locator('text=#صيف')).toBeVisible();
  });

  test('should analyze sentiment of content', async ({ page }) => {
    await page.goto('/knowledge/ai-assistant');

    await page.fill('textarea[name="text"]', 'منتج رائع! أنا سعيد جداً بالشراء');

    await page.click('[data-testid="analyze-sentiment"]');

    await expect(page.locator('[data-testid="sentiment-result"]')).toBeVisible();
    await expect(page.locator('text=positive')).toBeVisible();
  });

  test('should translate content', async ({ page }) => {
    await page.goto('/knowledge/ai-assistant');

    await page.fill('textarea[name="source_text"]', 'Summer sale up to 50% off');
    await page.selectOption('select[name="target_language"]', 'ar');

    await page.click('[data-testid="translate"]');

    await expect(page.locator('[data-testid="translated-text"]')).toBeVisible();
    await expect(page.locator('[data-testid="translated-text"]')).toContainText(/خصم|تخفيض/i);
  });

  test('should generate similar content variations', async ({ page }) => {
    await page.goto('/content/create');

    await page.fill('textarea[name="original_content"]', 'خصومات الصيف - تسوق الآن!');

    await page.click('[data-testid="generate-variations"]');

    await expect(page.locator('[data-testid="content-variations"]')).toBeVisible();
    await expect(page.locator('[data-testid="variation-item"]')).toHaveCount(5);
  });

  test('should find similar campaigns', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');

    await page.click('[data-testid="find-similar"]');

    await expect(page.locator('[data-testid="similar-campaigns"]')).toBeVisible();
    await expect(page.locator('[data-testid="similarity-score"]')).toBeVisible();
  });

  test('should get AI recommendations for optimization', async ({ page }) => {
    await page.goto('/campaigns');

    await page.click('[data-testid="view-campaign"]:first-child');

    await page.click('[data-testid="get-ai-recommendations"]');

    await expect(page.locator('[data-testid="recommendations-panel"]')).toBeVisible();
    await expect(page.locator('[data-testid="recommendation-item"]')).toBeVisible();
  });

  test('should generate content calendar with AI', async ({ page }) => {
    await page.goto('/content/calendar');

    await page.click('[data-testid="ai-generate-calendar"]');

    await page.fill('input[name="campaign_name"]', 'حملة الصيف');
    await page.fill('input[name="start_date"]', '2024-06-01');
    await page.fill('input[name="end_date"]', '2024-08-31');
    await page.fill('input[name="posts_per_week"]', '7');

    await page.click('[data-testid="generate-calendar"]');

    await expect(page.locator('[data-testid="generated-calendar"]')).toBeVisible();
  });

  test('should categorize content automatically', async ({ page }) => {
    await page.goto('/knowledge/create');

    await page.fill('textarea[name="content"]', 'نصائح لزيادة التفاعل على فيسبوك وإنستقرام');

    await page.click('[data-testid="auto-categorize"]');

    await expect(page.locator('select[name="category"]')).toHaveValue(/social_media/i);
  });

  test('should generate meta description with AI', async ({ page }) => {
    await page.goto('/content/create');

    await page.fill('textarea[name="content"]', 'دليل شامل للتسويق عبر السوشيال ميديا في 2024');

    await page.click('[data-testid="generate-meta-description"]');

    await expect(page.locator('textarea[name="meta_description"]')).not.toBeEmpty();
  });

  test('should suggest improvements to content', async ({ page }) => {
    await page.goto('/content/create');

    await page.fill('textarea[name="content"]', 'منتج جيد بسعر مناسب');

    await page.click('[data-testid="suggest-improvements"]');

    await expect(page.locator('[data-testid="improvement-suggestions"]')).toBeVisible();
    await expect(page.locator('[data-testid="suggestion-item"]')).toBeVisible();
  });

  test('should check content for plagiarism', async ({ page }) => {
    await page.goto('/knowledge/ai-assistant');

    await page.fill('textarea[name="content"]', 'محتوى للفحص...');

    await page.click('[data-testid="check-plagiarism"]');

    await expect(page.locator('[data-testid="plagiarism-result"]')).toBeVisible();
  });

  test('should generate topic clusters', async ({ page }) => {
    await page.goto('/knowledge/topic-clusters');

    await page.fill('input[name="main_topic"]', 'التسويق الرقمي');

    await page.click('[data-testid="generate-clusters"]');

    await expect(page.locator('[data-testid="topic-cluster-map"]')).toBeVisible();
    await expect(page.locator('[data-testid="cluster-node"]')).toBeVisible();
  });

  test('should export knowledge base', async ({ page }) => {
    await page.goto('/knowledge');

    await page.click('[data-testid="export-knowledge-base"]');

    await page.selectOption('select[name="format"]', 'pdf');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="confirm-export"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.pdf');
  });

  test('should import knowledge from external source', async ({ page }) => {
    await page.goto('/knowledge/import');

    await page.setInputFiles('input[type="file"]', './test-assets/knowledge.json');

    await page.click('[data-testid="upload-file"]');

    await expect(page.locator('[data-testid="import-preview"]')).toBeVisible();

    await page.click('[data-testid="confirm-import"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should create knowledge collection', async ({ page }) => {
    await page.goto('/knowledge/collections');

    await page.click('[data-testid="create-collection"]');

    await page.fill('input[name="name"]', 'استراتيجيات السوشيال ميديا');
    await page.fill('textarea[name="description"]', 'مجموعة شاملة عن استراتيجيات السوشيال ميديا');

    await page.click('[data-testid="save-collection"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should add entry to collection', async ({ page }) => {
    await page.goto('/knowledge');

    await page.click('[data-testid="knowledge-item"]:first-child');

    await page.click('[data-testid="add-to-collection"]');

    await page.selectOption('select[name="collection"]', { index: 0 });

    await page.click('[data-testid="confirm-add"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should generate knowledge graph', async ({ page }) => {
    await page.goto('/knowledge/graph');

    await expect(page.locator('[data-testid="knowledge-graph"]')).toBeVisible();
    await expect(page.locator('[data-testid="graph-node"]')).toBeVisible();
    await expect(page.locator('[data-testid="graph-edge"]')).toBeVisible();
  });

  test('should filter knowledge by date', async ({ page }) => {
    await page.goto('/knowledge');

    await page.fill('input[name="date_from"]', '2024-01-01');
    await page.fill('input[name="date_to"]', '2024-06-30');

    await page.click('[data-testid="apply-filters"]');

    await expect(page.locator('[data-testid="knowledge-item"]')).toBeVisible();
  });

  test('should view knowledge analytics', async ({ page }) => {
    await page.goto('/knowledge/analytics');

    await expect(page.locator('[data-testid="total-entries"]')).toBeVisible();
    await expect(page.locator('[data-testid="most-viewed"]')).toBeVisible();
    await expect(page.locator('[data-testid="most-searched"]')).toBeVisible();
  });
});
