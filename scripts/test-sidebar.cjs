/**
 * Test sidebar dynamic navigation
 */
const { chromium } = require('playwright');

async function testSidebar() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    // Collect console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        console.log('1. Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        // Click the login button using more specific selector
        await page.locator('button[type="submit"]:visible').first().click();
        await page.waitForURL('**/orgs/**', { timeout: 15000 });
        console.log('   Logged in successfully');

        // Wait for page to fully load
        await page.waitForLoadState('networkidle');

        console.log('\n2. Checking sidebar navigation...');

        // Take screenshot of the sidebar
        await page.screenshot({ path: 'test-results/sidebar-test.png', fullPage: true });
        console.log('   Screenshot saved to test-results/sidebar-test.png');

        // Check if the sidebar contains expected elements
        const dashboardLink = await page.locator('a[href*="/dashboard"]').first();
        const socialMediaLink = await page.locator('a[href*="/social"]').first();
        const profileGroupsLink = await page.locator('a[href*="/profile-groups"]').first();
        const inboxLink = await page.locator('a[href*="/inbox"]').first();
        const settingsButton = await page.locator('button:has-text("Settings"), a:has-text("Settings")').first();
        const marketplaceLink = await page.locator('a[href*="/marketplace"]').first();

        console.log('\n3. Core Features Check:');
        console.log('   - Dashboard:', await dashboardLink.isVisible() ? 'VISIBLE' : 'MISSING');
        console.log('   - Social Media:', await socialMediaLink.isVisible() ? 'VISIBLE' : 'MISSING');
        console.log('   - Profile Groups:', await profileGroupsLink.isVisible() ? 'VISIBLE' : 'MISSING');
        console.log('   - Inbox:', await inboxLink.isVisible() ? 'VISIBLE' : 'MISSING');
        console.log('   - Settings:', await settingsButton.isVisible() ? 'VISIBLE' : 'MISSING');
        console.log('   - Marketplace:', await marketplaceLink.isVisible() ? 'VISIBLE' : 'MISSING');

        // Check for enabled optional apps
        console.log('\n4. Optional Apps (should show based on org settings):');
        const campaignsLink = await page.locator('button:has-text("Campaigns"), a:has-text("Campaigns")').first();
        const analyticsLink = await page.locator('a[href*="/analytics"]').first();
        const audiencesLink = await page.locator('button:has-text("Audiences"), a:has-text("Audiences")').first();

        console.log('   - Campaigns:', await campaignsLink.isVisible() ? 'VISIBLE' : 'HIDDEN');
        console.log('   - Analytics:', await analyticsLink.isVisible() ? 'VISIBLE' : 'HIDDEN');
        console.log('   - Audiences:', await audiencesLink.isVisible() ? 'VISIBLE' : 'HIDDEN');

        // Count total nav items in sidebar
        const navItems = await page.locator('aside nav a, aside nav button').count();
        console.log('\n5. Total navigation items in sidebar:', navItems);

        // Check for console errors
        console.log('\n6. Console Errors:', consoleErrors.length > 0 ? consoleErrors.join('\n   ') : 'NONE');

        console.log('\n========================================');
        console.log('Sidebar Test COMPLETED');
        console.log('========================================');

    } catch (error) {
        console.error('Test failed:', error.message);
        await page.screenshot({ path: 'test-results/sidebar-error.png' });
    } finally {
        await browser.close();
    }
}

// Ensure test-results directory exists
const fs = require('fs');
if (!fs.existsSync('test-results')) {
    fs.mkdirSync('test-results', { recursive: true });
}

testSidebar();
