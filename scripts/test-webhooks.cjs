/**
 * Test Webhook Configuration Feature
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';

async function runTest() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        locale: 'en',
        viewport: { width: 1280, height: 720 }
    });

    // Set locale cookie
    await context.addCookies([{
        name: 'app_locale',
        value: 'en',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    const page = await context.newPage();
    const errors = [];

    // Collect console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(`Console Error: ${msg.text()}`);
        }
    });

    page.on('pageerror', error => {
        errors.push(`Page Error: ${error.message}`);
    });

    try {
        // 1. Login
        console.log('1. Logging in...');
        await page.goto(`${BASE_URL}/login`);
        await page.waitForLoadState('networkidle');

        // Take screenshot of login page
        await page.screenshot({ path: '/home/cmis-test/public_html/test-results/webhook-login.png' });

        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');

        // Click the Sign In button by text
        await page.click('button:has-text("Sign In")');
        await page.waitForTimeout(3000);

        // 2. Navigate to Settings > Webhooks
        console.log('2. Navigating to Webhook Configuration...');

        // Get current URL
        const url = page.url();
        console.log('   Current URL:', url);

        // Check if super-admin, switch to an org first
        if (url.includes('super-admin')) {
            console.log('   Super-admin detected. Looking for organization switch...');
            await page.screenshot({ path: '/home/cmis-test/public_html/test-results/webhook-superadmin.png' });

            // Try to find any org link or just go directly to a known org
            // Using the CMIS test org ID from database
            const testOrgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
            const testOrgUrl = `${BASE_URL}/orgs/${testOrgId}/settings/webhooks`;
            console.log('   Navigating to CMIS org webhooks:', testOrgUrl);
            await page.goto(testOrgUrl);
            await page.waitForTimeout(2000);
        } else {
            // Extract org from URL pattern /orgs/{org}/...
            const orgMatch = url.match(/\/orgs\/([^\/]+)/);
            const orgSlug = orgMatch ? orgMatch[1] : '';
            console.log('   Org ID:', orgSlug || 'Not found');

            // Navigate to webhooks settings page
            if (orgSlug) {
                const webhooksUrl = `${BASE_URL}/orgs/${orgSlug}/settings/webhooks`;
                console.log('   Navigating to:', webhooksUrl);
                await page.goto(webhooksUrl);
                await page.waitForTimeout(2000);
            } else {
                console.log('   ERROR: Could not determine org slug');
                await page.screenshot({ path: '/home/cmis-test/public_html/test-results/webhook-test-no-org.png' });
            }
        }

        // 3. Check page content
        console.log('3. Checking page content...');
        const pageTitle = await page.title();
        console.log('   Page title:', pageTitle);

        // Take screenshot
        await page.screenshot({ path: '/home/cmis-test/public_html/test-results/webhook-index.png', fullPage: true });

        // 4. Check for empty state or webhooks list
        const hasEmptyState = await page.$('.text-center.py-12') !== null;
        const hasWebhookCards = await page.$$eval('.grid.gap-6 > div', elements => elements.length);

        console.log('   Empty state visible:', hasEmptyState);
        console.log('   Webhook cards found:', hasWebhookCards);

        // 5. Try to navigate to create page
        console.log('4. Navigating to Create Webhook page...');
        const createButton = await page.$('a[href*="webhooks/create"]');
        if (createButton) {
            await createButton.click();
            await page.waitForTimeout(2000);

            // Take screenshot of create page
            await page.screenshot({ path: '/home/cmis-test/public_html/test-results/webhook-create.png', fullPage: true });

            // Check for form fields
            const hasNameField = await page.$('input[name="name"]') !== null;
            const hasCallbackField = await page.$('input[name="callback_url"], input#callback_url') !== null;
            console.log('   Name field found:', hasNameField);
            console.log('   Callback URL field found:', hasCallbackField);
        } else {
            console.log('   Create button not found');
        }

        // 6. Report errors
        if (errors.length > 0) {
            console.log('\n--- ERRORS DETECTED ---');
            errors.forEach(e => console.log(e));
        } else {
            console.log('\n--- NO ERRORS DETECTED ---');
        }

        console.log('\nTest completed!');
        console.log('Screenshots saved to: test-results/');

    } catch (error) {
        console.error('Test failed:', error);
        await page.screenshot({ path: '/home/cmis-test/public_html/test-results/webhook-error.png' });
    } finally {
        await browser.close();
    }
}

runTest().catch(console.error);
