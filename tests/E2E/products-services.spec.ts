import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Products & Services E2E Tests
 */
test.describe('Products & Services Management', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test('should display products and services list', async ({ page }) => {
    await page.goto('/offerings');

    await expect(page.locator('h1')).toContainText(/products|services|منتجات|خدمات/i);
    await expect(page.locator('[data-testid="offerings-grid"]')).toBeVisible();
  });

  test('should create new product with all details', async ({ page }) => {
    await page.goto('/offerings/create');

    // Basic Info
    await page.selectOption('select[name="type"]', 'product');
    await page.fill('input[name="name"]', 'Premium Cotton T-Shirt');
    await page.fill('textarea[name="description"]', 'High-quality cotton t-shirt perfect for summer');

    // Pricing
    await page.fill('input[name="price"]', '25.00');
    await page.selectOption('select[name="currency"]', 'BHD');

    // Features
    await page.fill('textarea[name="features"]', '100% premium cotton\nModern comfortable design\nAvailable in multiple colors');

    // Benefits
    await page.fill('textarea[name="benefits"]', 'Keeps you cool all day\nEasy to wash and maintain');

    // Transformational Benefits
    await page.fill('textarea[name="transformational_benefits"]', 'Boost your confidence\nProfessional appearance');

    // USPs (Unique Selling Points)
    await page.fill('textarea[name="usps"]', 'Best price in market\nQuality guarantee\nFast shipping');

    // Images
    await page.setInputFiles('input[type="file"][name="images"]', [
      './test-assets/product-1.jpg',
      './test-assets/product-2.jpg',
    ]);

    // Categories
    await page.check('input[value="fashion"]');
    await page.check('input[value="summer"]');

    // Inventory
    await page.fill('input[name="stock_quantity"]', '100');
    await page.fill('input[name="sku"]', 'SHIRT-SUMMER-001');

    // Submit
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Premium Cotton T-Shirt')).toBeVisible();
  });

  test('should create new service', async ({ page }) => {
    await page.goto('/offerings/create');

    await page.selectOption('select[name="type"]', 'service');
    await page.fill('input[name="name"]', 'Marketing Consultation');
    await page.fill('textarea[name="description"]', 'Professional marketing consultation for your business');

    await page.fill('input[name="price"]', '150.00');
    await page.selectOption('select[name="currency"]', 'BHD');

    await page.fill('textarea[name="features"]', 'One-on-one consultation\nCustomized strategy\nAction plan included');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Marketing Consultation')).toBeVisible();
  });

  test('should edit existing product', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="edit-offering"]:first-child');

    await page.fill('input[name="name"]', 'Updated Product Name');
    await page.fill('input[name="price"]', '30.00');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Updated Product Name')).toBeVisible();
  });

  test('should delete product', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="delete-offering"]:first-child');
    await page.fill('[data-testid="confirm-delete-input"]', 'DELETE');
    await page.click('[data-testid="confirm-delete-button"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should filter offerings by type', async ({ page }) => {
    await page.goto('/offerings');

    await page.selectOption('[data-testid="type-filter"]', 'product');

    await page.waitForTimeout(500);

    const offerings = page.locator('[data-testid="offering-card"]');
    const count = await offerings.count();

    for (let i = 0; i < count; i++) {
      const type = await offerings.nth(i).getAttribute('data-type');
      expect(type).toBe('product');
    }
  });

  test('should search offerings', async ({ page }) => {
    await page.goto('/offerings');

    await page.fill('[data-testid="search-input"]', 'T-Shirt');
    await page.click('[data-testid="search-button"]');

    await expect(page.locator('text=T-Shirt')).toBeVisible();
  });

  test('should view offering details', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');

    await expect(page.locator('[data-testid="offering-name"]')).toBeVisible();
    await expect(page.locator('[data-testid="offering-price"]')).toBeVisible();
    await expect(page.locator('[data-testid="offering-description"]')).toBeVisible();
    await expect(page.locator('[data-testid="features-section"]')).toBeVisible();
    await expect(page.locator('[data-testid="benefits-section"]')).toBeVisible();
  });

  test('should add product to campaign', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');
    await page.click('[data-testid="add-to-campaign"]');

    await page.selectOption('[data-testid="campaign-select"]', { index: 0 });
    await page.click('[data-testid="confirm-add"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should duplicate offering', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="duplicate-offering"]:first-child');

    await page.fill('input[name="name"]', 'Duplicated Product');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Duplicated Product')).toBeVisible();
  });

  test('should export offerings to CSV', async ({ page }) => {
    await page.goto('/offerings');

    const downloadPromise = page.waitForEvent('download');
    await page.click('[data-testid="export-csv"]');
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toContain('.csv');
  });

  test('should import offerings from CSV', async ({ page }) => {
    await page.goto('/offerings/import');

    await page.setInputFiles('input[type="file"]', './test-assets/offerings.csv');

    await page.click('[data-testid="upload-csv"]');

    await expect(page.locator('[data-testid="import-preview"]')).toBeVisible();

    await page.click('[data-testid="confirm-import"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should manage product variants', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');
    await page.click('[data-testid="manage-variants"]');

    await page.click('[data-testid="add-variant"]');

    await page.fill('input[name="variant_name"]', 'Size: Large');
    await page.fill('input[name="variant_price"]', '28.00');
    await page.fill('input[name="variant_sku"]', 'SHIRT-L');

    await page.click('[data-testid="save-variant"]');

    await expect(page.locator('[data-testid="variant-item"]')).toBeVisible();
  });

  test('should update inventory', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');
    await page.click('[data-testid="update-inventory"]');

    await page.fill('input[name="stock_quantity"]', '200');
    await page.click('[data-testid="save-inventory"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should set promotional pricing', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');
    await page.click('[data-testid="set-promotion"]');

    await page.fill('input[name="promotional_price"]', '20.00');
    await page.fill('input[name="promotion_start"]', '2024-06-01');
    await page.fill('input[name="promotion_end"]', '2024-06-30');

    await page.click('[data-testid="save-promotion"]');

    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="promotional-badge"]')).toBeVisible();
  });

  test('should view offering analytics', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');
    await page.click('[data-testid="analytics-tab"]');

    await expect(page.locator('[data-testid="views-count"]')).toBeVisible();
    await expect(page.locator('[data-testid="conversions-count"]')).toBeVisible();
    await expect(page.locator('[data-testid="revenue-total"]')).toBeVisible();
  });

  test('should add offering reviews', async ({ page }) => {
    await page.goto('/offerings');

    await page.click('[data-testid="view-offering"]:first-child');
    await page.click('[data-testid="reviews-tab"]');

    await expect(page.locator('[data-testid="reviews-list"]')).toBeVisible();
    await expect(page.locator('[data-testid="average-rating"]')).toBeVisible();
  });
});
