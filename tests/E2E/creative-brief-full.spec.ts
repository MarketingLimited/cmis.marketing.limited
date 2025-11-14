import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Creative Brief E2E Tests - Complete Workflow
 */
test.describe('Creative Brief - Full Flow', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display creative briefs list', async ({ page }) => {
    await page.goto('/creative-briefs');

    await expect(page.locator('h1')).toContainText(/creative brief|ملخص إبداعي/i);
    await expect(page.locator('[data-testid="briefs-list"]')).toBeVisible();
  });

  test('should create complete creative brief with all fields', async ({ page }) => {
    await page.goto('/creative-briefs/create');

    // Basic Info
    await page.fill('input[name="name"]', 'Summer 2024 Campaign Brief');
    await page.fill('textarea[name="description"]', 'Complete creative brief for summer campaign');

    // Marketing Objectives
    await page.selectOption('select[name="marketing_objective"]', 'drive_sales');
    await page.selectOption('select[name="emotional_trigger"]', 'desire');

    // Hooks
    await page.fill('textarea[name="hooks"]', 'Summer Sale!\n50% Off Everything!');

    // Channels
    await page.check('input[value="facebook"]');
    await page.check('input[value="instagram"]');
    await page.check('input[value="tiktok"]');

    // Segments
    await page.fill('textarea[name="segments"]', 'Young adults 18-35\nFashion enthusiasts\nOnline shoppers');

    // Pains
    await page.fill('textarea[name="pains"]', 'High prices\nLimited product availability');

    // Marketing Framework
    await page.selectOption('select[name="marketing_framework"]', 'aida');

    // Marketing Strategies
    await page.check('input[value="content_marketing"]');
    await page.check('input[value="social_media"]');

    // Awareness & Funnel Stage
    await page.selectOption('select[name="awareness_stage"]', 'solution_aware');
    await page.selectOption('select[name="funnel_stage"]', 'consideration');

    // Tone & Style
    await page.selectOption('select[name="tone"]', 'friendly');
    await page.fill('input[name="style"]', 'Modern, clean, colorful');

    // Message Map
    await page.fill('input[name="message_map_primary"]', 'Quality at affordable prices');
    await page.fill('input[name="message_map_secondary"]', 'Wide variety of products');
    await page.fill('input[name="message_map_cta"]', 'Shop Now and Save!');

    // Proofs
    await page.fill('textarea[name="proofs"]', 'Customer ratings 4.8/5\n10,000+ happy customers');

    // Brand
    await page.fill('input[name="brand_name"]', 'Summer Style');
    await page.fill('textarea[name="brand_values"]', 'Quality\nAuthenticity\nInnovation');

    // Guardrails
    await page.fill('textarea[name="guardrails"]', 'No exaggerated images\nAvoid unrealistic promises');

    // Seasonality & Offer
    await page.fill('input[name="seasonality"]', 'Summer 2024');
    await page.fill('textarea[name="offer"]', '50% off summer collection + free shipping');
    await page.fill('input[name="pricing"]', '15-75 BHD');
    await page.fill('input[name="cta"]', 'Shop Now');

    // Content Formats
    await page.check('input[value="image"]');
    await page.check('input[value="video"]');
    await page.check('input[value="carousel"]');

    // Art Direction - Click to expand section
    await page.click('[data-testid="art-direction-section"]');

    // Mood & Visual Message
    await page.fill('input[name="art_direction_mood"]', 'Energetic, summery, fresh');
    await page.fill('textarea[name="art_direction_visual_message"]', 'Bright images reflecting summer atmosphere');
    await page.fill('input[name="art_direction_look_feel"]', 'Clean, bright, modern, simple');

    // Color Palette
    await page.fill('input[name="color_palette_primary"]', '#FF6B35');
    await page.fill('input[name="color_palette_secondary"]', '#F7F7F7');
    await page.fill('input[name="color_palette_accent"]', '#004E89');

    // Typography
    await page.fill('input[name="typography_primary_font"]', 'Montserrat');
    await page.fill('input[name="typography_secondary_font"]', 'Open Sans');

    // Imagery & Graphics
    await page.fill('textarea[name="imagery"]', 'Real product photos, people wearing clothes');
    await page.fill('input[name="icons_symbols"]', 'Simple icons, summer symbols');

    // Composition
    await page.fill('textarea[name="composition"]', 'Balanced design with white spaces');

    // Amplify
    await page.fill('textarea[name="amplify"]', 'Quality\nGreat prices\nWide variety');

    // Story/Solution
    await page.fill('textarea[name="story"]', 'We provide the perfect solution for modern summer fashion');

    // Design Elements
    await page.fill('textarea[name="design_description"]', 'Vibrant design reflecting summer spirit');
    await page.fill('input[name="background"]', 'Clean white or light summer colors');
    await page.fill('input[name="lighting"]', 'Bright natural lighting');
    await page.fill('input[name="highlight"]', 'Main products and special offers');
    await page.fill('input[name="de_emphasize"]', 'Secondary elements and background');

    // Element Positions
    await page.selectOption('select[name="element_position_logo"]', 'top-left');
    await page.selectOption('select[name="element_position_product"]', 'center');
    await page.selectOption('select[name="element_position_cta"]', 'bottom-right');
    await page.selectOption('select[name="element_position_price"]', 'near-product');

    // Ratio & Motion
    await page.selectOption('select[name="ratio"]', '1:1');
    await page.fill('input[name="motion"]', 'Smooth slow movements, gradual zoom');

    // Submit
    await page.click('button[type="submit"]');

    await page.waitForURL(/\/creative-briefs/);
    await expect(page.locator('text=Summer 2024 Campaign Brief')).toBeVisible();
  });

  test('should edit creative brief', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="edit-brief"]:first-child');

    await page.fill('input[name="name"]', 'Updated Brief Name');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Updated Brief Name')).toBeVisible();
  });

  test('should view creative brief details with all art direction', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="view-brief"]:first-child');

    // Verify all sections are displayed
    await expect(page.locator('[data-testid="marketing-objective"]')).toBeVisible();
    await expect(page.locator('[data-testid="emotional-trigger"]')).toBeVisible();
    await expect(page.locator('[data-testid="hooks-section"]')).toBeVisible();
    await expect(page.locator('[data-testid="channels-section"]')).toBeVisible();

    // Expand art direction
    await page.click('[data-testid="art-direction-toggle"]');

    await expect(page.locator('[data-testid="color-palette"]')).toBeVisible();
    await expect(page.locator('[data-testid="typography"]')).toBeVisible();
    await expect(page.locator('[data-testid="element-positions"]')).toBeVisible();
  });

  test('should clone creative brief', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="clone-brief"]:first-child');

    await page.fill('input[name="name"]', 'Cloned Brief');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Cloned Brief')).toBeVisible();
  });

  test('should export creative brief to PDF', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="view-brief"]:first-child');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-pdf"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.pdf');
  });

  test('should delete creative brief', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="delete-brief"]:first-child');
    await page.fill('[data-testid="confirm-delete-input"]', 'DELETE');
    await page.click('[data-testid="confirm-delete-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should link creative brief to campaign', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="view-brief"]:first-child');
    await page.click('[data-testid="link-campaign"]');

    await page.selectOption('[data-testid="campaign-select"]', { index: 0 });
    await page.click('[data-testid="confirm-link"]');

    await expect(page.locator('[data-testid="linked-campaign"]')).toBeVisible();
  });

  test('should preview creative brief design', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.click('[data-testid="view-brief"]:first-child');
    await page.click('[data-testid="preview-design"]');

    await expect(page.locator('[data-testid="design-preview"]')).toBeVisible();
    await expect(page.locator('[data-testid="color-swatches"]')).toBeVisible();
    await expect(page.locator('[data-testid="typography-sample"]')).toBeVisible();
  });

  test('should filter briefs by marketing objective', async ({ page }) => {
    await page.goto('/creative-briefs');

    await page.selectOption('[data-testid="objective-filter"]', 'drive_sales');

    await page.waitForTimeout(500);

    const briefs = page.locator('[data-testid="brief-card"]');
    const count = await briefs.count();

    for (let i = 0; i < count; i++) {
      const objective = await briefs.nth(i).getAttribute('data-objective');
      expect(objective).toBe('drive_sales');
    }
  });

  test('should validate required fields', async ({ page }) => {
    await page.goto('/creative-briefs/create');

    // Try to submit without filling required fields
    await page.click('button[type="submit"]');

    await expect(page.locator('[data-testid="error-name"]')).toBeVisible();
    await expect(page.locator('[data-testid="error-objective"]')).toBeVisible();
  });
});
