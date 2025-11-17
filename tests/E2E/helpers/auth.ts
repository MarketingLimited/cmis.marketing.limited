import { Page } from '@playwright/test';

/**
 * Authentication helper for E2E tests
 */
export class AuthHelper {
  private page: Page;

  constructor(page: Page) {
    this.page = page;
  }

  /**
   * Login with credentials
   */
  async login(email: string, password: string): Promise<void> {
    await this.page.goto('/login');
    await this.page.fill('input[name="email"]', email);
    await this.page.fill('input[name="password"]', password);
    await this.page.click('button[type="submit"]');
    await this.page.waitForSelector('[data-testid="user-menu"]');
  }

  /**
   * Logout
   */
  async logout(): Promise<void> {
    await this.page.click('[data-testid="user-menu"]');
    await this.page.click('[data-testid="logout-button"]');
    await this.page.waitForSelector('form[action="/login"]');
  }

  /**
   * Create test user via API
   */
  async createTestUser(userData: {
    name: string;
    email: string;
    password: string;
  }): Promise<any> {
    const response = await this.page.request.post('/api/register', {
      data: userData,
    });

    return await response.json();
  }

  /**
   * Get authentication token for API requests
   */
  async getAuthToken(email: string, password: string): Promise<string> {
    const response = await this.page.request.post('/api/login', {
      data: { email, password },
    });

    const data = await response.json();
    return data.token;
  }
}
