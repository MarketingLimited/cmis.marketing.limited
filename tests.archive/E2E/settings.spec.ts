import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth';
import { CMISHelper } from './helpers/cmis';

/**
 * Settings & User Management E2E Tests - Comprehensive Coverage
 *
 * This test suite covers all settings and configuration functionality including:
 * - General settings and preferences
 * - User profile and account management
 * - Organization settings and branding
 * - Team member management
 * - Notification preferences
 * - Billing and subscription management
 * - API keys and webhooks
 * - Security settings and 2FA
 * - Audit logs and compliance
 * - Advanced configuration
 */
test.describe('Settings & User Management', () => {
  let authHelper: AuthHelper;
  let cmisHelper: CMISHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    cmisHelper = new CMISHelper(page);
    await authHelper.login('admin@example.com', 'password');
  });

  test.describe('General Settings', () => {
    test('should display settings page', async ({ page }) => {
      await page.goto('/settings');

      await expect(page.locator('h1')).toContainText(/settings|إعدادات/i);
      await expect(page.locator('[data-testid="settings-menu"]')).toBeVisible();
      await expect(page.locator('[data-testid="settings-tabs"]')).toBeVisible();
    });

    test('should configure language preferences', async ({ page }) => {
      await page.goto('/settings/preferences');

      await page.selectOption('select[name="language"]', 'ar');
      await page.selectOption('select[name="timezone"]', 'Asia/Bahrain');
      await page.selectOption('select[name="date_format"]', 'DD/MM/YYYY');

      await page.click('[data-testid="save-preferences"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  test.describe('User & Profile Management', () => {
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
  });

  test.describe('Organization Settings', () => {
    test('should manage organization settings', async ({ page }) => {
      await page.goto('/settings/organization');

      await page.fill('input[name="organization_name"]', 'Updated Org Name');
      await page.selectOption('[name="currency"]', 'USD');
      await page.selectOption('[name="timezone"]', 'America/New_York');

      await page.click('button[type="submit"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should update organization profile', async ({ page }) => {
      await page.goto('/settings/organization');

      await page.fill('input[name="org_name"]', 'Updated Organization Name');
      await page.fill('input[name="org_email"]', 'info@updated-org.com');
      await page.fill('input[name="org_phone"]', '+973-1234-5678');
      await page.fill('textarea[name="org_description"]', 'مؤسسة رائدة في مجال التسويق الرقمي');

      await page.click('button[type="submit"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
      await expect(page.locator('text=Updated Organization Name')).toBeVisible();
    });

    test('should upload organization logo', async ({ page }) => {
      await page.goto('/settings/organization');

      await page.setInputFiles('input[type="file"][name="logo"]', './test-assets/logo.png');

      await page.click('[data-testid="upload-logo"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
      await expect(page.locator('[data-testid="org-logo"]')).toBeVisible();
    });

    test('should configure brand settings', async ({ page }) => {
      await page.goto('/settings/brand');

      await page.fill('input[name="primary_color"]', '#FF6B35');
      await page.fill('input[name="secondary_color"]', '#004E89');
      await page.fill('input[name="primary_font"]', 'Montserrat');
      await page.fill('input[name="secondary_font"]', 'Open Sans');

      await page.setInputFiles('input[type="file"][name="brand_logo"]', './test-assets/brand-logo.png');

      await page.click('[data-testid="save-brand"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  test.describe('Team Management', () => {
    test('should manage team members', async ({ page }) => {
      await page.goto('/settings/team');

      await page.click('[data-testid="invite-member"]');
      await page.fill('input[name="email"]', 'newmember@example.com');
      await page.selectOption('[name="role"]', 'editor');

      await page.click('button[type="submit"]');

      await expect(page.locator('text=Invitation sent')).toBeVisible();
    });

    test('should invite new team member', async ({ page }) => {
      await page.goto('/settings/users');

      await page.click('[data-testid="invite-user"]');

      await page.fill('input[name="email"]', 'newuser@example.com');
      await page.fill('input[name="name"]', 'أحمد محمد');
      await page.selectOption('select[name="role"]', 'editor');

      await page.check('input[value="campaigns"]');
      await page.check('input[value="content"]');

      await page.click('[data-testid="send-invitation"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
      await expect(page.locator('text=newuser@example.com')).toBeVisible();
    });

    test('should remove team member', async ({ page }) => {
      await page.goto('/settings/users');

      await page.click('[data-testid="remove-user"]:first-child');
      await page.fill('[data-testid="confirm-remove-input"]', 'REMOVE');
      await page.click('[data-testid="confirm-remove-button"]');

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

    test('should edit user permissions', async ({ page }) => {
      await page.goto('/settings/users');

      await page.click('[data-testid="edit-user"]:first-child');

      await page.check('input[value="campaigns.create"]');
      await page.check('input[value="campaigns.edit"]');
      await page.check('input[value="content.publish"]');
      await page.uncheck('input[value="users.delete"]');

      await page.click('[data-testid="save-permissions"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  test.describe('Notification Preferences', () => {
    test('should configure notification preferences', async ({ page }) => {
      await page.goto('/settings/notifications');

      await page.check('[name="notifications"][value="email"]');
      await page.check('[name="notifications"][value="browser"]');

      await page.click('button[type="submit"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should configure detailed notification preferences', async ({ page }) => {
      await page.goto('/settings/notifications');

      await page.check('input[value="campaign_completed"]');
      await page.check('input[value="post_published"]');
      await page.check('input[value="comment_received"]');
      await page.check('input[value="message_received"]');

      await page.selectOption('select[name="notification_method"]', 'both');

      await page.click('[data-testid="save-notifications"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  test.describe('Billing & Subscription', () => {
    test('should update billing information', async ({ page }) => {
      await page.goto('/settings/billing');

      await page.fill('input[name="billing_email"]', 'billing@example.com');
      await page.fill('input[name="company_name"]', 'Tech Solutions LLC');
      await page.fill('input[name="tax_id"]', '123456789');
      await page.fill('textarea[name="billing_address"]', '123 Main St, Manama, Bahrain');

      await page.click('[data-testid="save-billing"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should add payment method', async ({ page }) => {
      await page.goto('/settings/billing/payment-methods');

      await page.click('[data-testid="add-payment-method"]');

      await page.fill('input[name="card_number"]', '4242424242424242');
      await page.fill('input[name="card_expiry"]', '12/25');
      await page.fill('input[name="card_cvc"]', '123');
      await page.fill('input[name="card_name"]', 'Ahmed Mohammed');

      await page.click('[data-testid="save-card"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should view subscription details', async ({ page }) => {
      await page.goto('/settings/subscription');

      await expect(page.locator('[data-testid="current-plan"]')).toBeVisible();
      await expect(page.locator('[data-testid="plan-price"]')).toBeVisible();
      await expect(page.locator('[data-testid="next-billing-date"]')).toBeVisible();
    });

    test('should upgrade subscription plan', async ({ page }) => {
      await page.goto('/settings/subscription');

      await page.click('[data-testid="upgrade-plan"]');

      await page.click('[data-testid="select-plan-premium"]');

      await page.click('[data-testid="confirm-upgrade"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  test.describe('API & Webhooks', () => {
    test('should configure API keys', async ({ page }) => {
      await page.goto('/settings/api');

      await page.click('[data-testid="generate-api-key"]');

      await page.fill('input[name="key_name"]', 'Production API Key');
      await page.check('input[value="read"]');
      await page.check('input[value="write"]');

      await page.click('[data-testid="create-api-key"]');

      await expect(page.locator('[data-testid="api-key-value"]')).toBeVisible();
    });

    test('should revoke API key', async ({ page }) => {
      await page.goto('/settings/api');

      await page.click('[data-testid="revoke-key"]:first-child');
      await page.click('[data-testid="confirm-revoke"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should configure webhook endpoints', async ({ page }) => {
      await page.goto('/settings/webhooks');

      await page.click('[data-testid="add-webhook"]');

      await page.fill('input[name="webhook_url"]', 'https://api.example.com/webhooks');
      await page.fill('input[name="webhook_secret"]', 'secret_key_123');

      await page.check('input[value="campaign.created"]');
      await page.check('input[value="post.published"]');
      await page.check('input[value="message.received"]');

      await page.click('[data-testid="save-webhook"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should test webhook endpoint', async ({ page }) => {
      await page.goto('/settings/webhooks');

      await page.click('[data-testid="test-webhook"]:first-child');

      await expect(page.locator('[data-testid="webhook-test-result"]')).toBeVisible();
      await expect(page.locator('text=200 OK')).toBeVisible();
    });
  });

  test.describe('Brand & Security Settings', () => {
    test('should configure security settings', async ({ page }) => {
      await page.goto('/settings/security');

      await page.check('input[name="require_2fa"]');
      await page.check('input[name="session_timeout"]');
      await page.fill('input[name="timeout_minutes"]', '30');

      await page.click('[data-testid="save-security"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should enable two-factor authentication', async ({ page }) => {
      await page.goto('/settings/security/2fa');

      await page.click('[data-testid="enable-2fa"]');

      await expect(page.locator('[data-testid="qr-code"]')).toBeVisible();

      await page.fill('input[name="verification_code"]', '123456');
      await page.click('[data-testid="verify-2fa"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
  });

  test.describe('Audit Log', () => {
    test('should view audit log', async ({ page }) => {
      await page.goto('/settings/audit-log');

      await expect(page.locator('[data-testid="audit-log-table"]')).toBeVisible();
      await expect(page.locator('[data-testid="audit-entry"]')).toBeVisible();
    });

    test('should filter audit log by action type', async ({ page }) => {
      await page.goto('/settings/audit-log');

      await page.selectOption('[data-testid="action-filter"]', 'user.login');

      await page.waitForTimeout(500);

      const entries = page.locator('[data-testid="audit-entry"]');
      const count = await entries.count();

      for (let i = 0; i < count; i++) {
        const action = await entries.nth(i).getAttribute('data-action');
        expect(action).toBe('user.login');
      }
    });

    test('should export audit log', async ({ page }) => {
      await page.goto('/settings/audit-log');

      const downloadPromise = page.waitForEvent('download');
      await page.click('[data-testid="export-audit-log"]');
      const download = await downloadPromise;

      expect(download.suggestedFilename()).toContain('audit-log');
    });
  });

  test.describe('Advanced Settings', () => {
    test('should configure email templates', async ({ page }) => {
      await page.goto('/settings/email-templates');

      await page.click('[data-testid="edit-template"]:first-child');

      await page.fill('input[name="subject"]', 'مرحباً بك في نظام CMIS');
      await page.fill('textarea[name="body"]', 'مرحباً {{name}}، نحن سعداء بانضمامك إلينا!');

      await page.click('[data-testid="save-template"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should preview email template', async ({ page }) => {
      await page.goto('/settings/email-templates');

      await page.click('[data-testid="preview-template"]:first-child');

      await expect(page.locator('[data-testid="template-preview"]')).toBeVisible();
    });

    test('should configure data retention policy', async ({ page }) => {
      await page.goto('/settings/data-retention');

      await page.fill('input[name="campaigns_retention_days"]', '365');
      await page.fill('input[name="analytics_retention_days"]', '730');
      await page.fill('input[name="logs_retention_days"]', '90');

      await page.click('[data-testid="save-retention-policy"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should export organization data', async ({ page }) => {
      await page.goto('/settings/data-export');

      await page.check('input[value="campaigns"]');
      await page.check('input[value="content"]');
      await page.check('input[value="analytics"]');

      await page.selectOption('select[name="format"]', 'json');

      await page.click('[data-testid="request-export"]');

      await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });

    test('should delete organization account', async ({ page }) => {
      await page.goto('/settings/danger-zone');

      await page.click('[data-testid="delete-organization"]');

      await page.fill('input[name="confirmation"]', 'DELETE');
      await page.fill('input[name="password"]', 'password');

      await page.click('[data-testid="confirm-delete"]');

      // This should redirect to a confirmation page
      await expect(page).toHaveURL(/account-deleted/);
    });
  });
});
