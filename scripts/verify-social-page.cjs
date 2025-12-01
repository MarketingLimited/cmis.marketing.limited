const { chromium } = require('playwright');

(async () => {
    console.log('🔍 CMIS Social Page Verification');
    console.log('='.repeat(50));

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        locale: 'en',
        timezoneId: 'America/New_York'
    });
    const page = await context.newPage();

    // Set locale cookie to English
    await page.context().addCookies([{
        name: 'app_locale',
        value: 'en',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    // Collect console messages
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        console.log('\n📝 Step 1: Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });

        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.keyboard.press('Enter');

        console.log('✅ Login submitted');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        console.log('✅ Redirected to dashboard');

        console.log('\n📝 Step 2: Navigating to Social page...');
        const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`, { waitUntil: 'networkidle' });
        console.log('✅ Social page loaded');

        console.log('\n📝 Step 3: Checking for Queue Settings button (should NOT exist)...');
        const queueButton = await page.locator('button:has-text("Queue Settings")').count();
        if (queueButton === 0) {
            console.log('✅ Queue Settings button successfully removed');
        } else {
            console.log('❌ Queue Settings button still exists!');
        }

        console.log('\n📝 Step 4: Checking for New Post button (should exist)...');
        const newPostButton = await page.locator('button:has-text("New Post")').count();
        if (newPostButton > 0) {
            console.log('✅ New Post button exists');
        } else {
            console.log('❌ New Post button not found!');
        }

        console.log('\n📝 Step 5: Taking screenshot...');
        await page.screenshot({ path: 'test-results/social-page-no-queue.png', fullPage: true });
        console.log('✅ Screenshot saved to test-results/social-page-no-queue.png');

        console.log('\n📝 Step 6: Checking for console errors...');
        if (consoleErrors.length === 0) {
            console.log('✅ No console errors detected');
        } else {
            console.log(`❌ Found ${consoleErrors.length} console errors:`);
            consoleErrors.forEach((err, idx) => {
                console.log(`   ${idx + 1}. ${err}`);
            });
        }

        console.log('\n' + '='.repeat(50));
        console.log('✅ Verification Complete!');
        console.log('='.repeat(50));

    } catch (error) {
        console.error('\n❌ Verification failed:', error.message);
        await page.screenshot({ path: 'test-results/social-page-error.png', fullPage: true });
        console.log('📸 Error screenshot saved to test-results/social-page-error.png');
    } finally {
        await browser.close();
    }
})();
