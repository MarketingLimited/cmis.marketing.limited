import { Page } from '@playwright/test';

/**
 * CMIS-specific helper functions for E2E tests
 */
export class CMISHelper {
  private page: Page;

  constructor(page: Page) {
    this.page = page;
  }

  /**
   * Select organization from dropdown
   */
  async selectOrganization(orgName: string): Promise<void> {
    await this.page.click('[data-testid="org-selector"]');
    await this.page.click(`[data-org-name="${orgName}"]`);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Navigate to campaigns page
   */
  async goToCampaigns(): Promise<void> {
    await this.page.click('[data-testid="nav-campaigns"]');
    await this.page.waitForURL(/.*\/campaigns/);
  }

  /**
   * Create a new campaign via UI
   */
  async createCampaign(campaignData: {
    name: string;
    objective?: string;
    budget?: number;
    startDate?: string;
    endDate?: string;
  }): Promise<void> {
    await this.goToCampaigns();
    await this.page.click('[data-testid="create-campaign-button"]');

    // Fill campaign form
    await this.page.fill('input[name="name"]', campaignData.name);

    if (campaignData.objective) {
      await this.page.selectOption('select[name="objective"]', campaignData.objective);
    }

    if (campaignData.budget) {
      await this.page.fill('input[name="budget"]', campaignData.budget.toString());
    }

    if (campaignData.startDate) {
      await this.page.fill('input[name="start_date"]', campaignData.startDate);
    }

    if (campaignData.endDate) {
      await this.page.fill('input[name="end_date"]', campaignData.endDate);
    }

    // Submit form
    await this.page.click('button[type="submit"]');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Search for campaigns
   */
  async searchCampaigns(query: string): Promise<void> {
    await this.goToCampaigns();
    await this.page.fill('[data-testid="campaign-search"]', query);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Filter campaigns by status
   */
  async filterCampaignsByStatus(status: string): Promise<void> {
    await this.goToCampaigns();
    await this.page.selectOption('[data-testid="status-filter"]', status);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Navigate to creative assets
   */
  async goToCreativeAssets(): Promise<void> {
    await this.page.click('[data-testid="nav-creative"]');
    await this.page.waitForURL(/.*\/creative/);
  }

  /**
   * Navigate to publishing queue
   */
  async goToPublishingQueue(): Promise<void> {
    await this.page.click('[data-testid="nav-publishing"]');
    await this.page.waitForURL(/.*\/publishing/);
  }

  /**
   * Connect social media account
   */
  async connectSocialAccount(platform: string): Promise<void> {
    await this.page.click('[data-testid="nav-integrations"]');
    await this.page.click(`[data-testid="connect-${platform}"]`);
    // Handle OAuth flow (would need platform-specific implementation)
  }

  /**
   * Schedule a post
   */
  async schedulePost(postData: {
    content: string;
    platforms: string[];
    scheduledTime: string;
  }): Promise<void> {
    await this.goToPublishingQueue();
    await this.page.click('[data-testid="schedule-post-button"]');

    await this.page.fill('textarea[name="content"]', postData.content);

    for (const platform of postData.platforms) {
      await this.page.check(`input[name="platforms"][value="${platform}"]`);
    }

    await this.page.fill('input[name="scheduled_at"]', postData.scheduledTime);

    await this.page.click('button[type="submit"]');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Wait for notification
   */
  async waitForNotification(message: string): Promise<void> {
    await this.page.waitForSelector(`[data-testid="notification"]:has-text("${message}")`);
  }

  /**
   * Get campaign count from dashboard
   */
  async getCampaignCount(): Promise<number> {
    await this.page.goto('/dashboard');
    const countText = await this.page.textContent('[data-testid="campaign-count"]');
    return parseInt(countText || '0', 10);
  }

  /**
   * Take screenshot with timestamp
   */
  async takeScreenshot(name: string): Promise<void> {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    await this.page.screenshot({
      path: `screenshots/${name}-${timestamp}.png`,
      fullPage: true,
    });
  }
}
