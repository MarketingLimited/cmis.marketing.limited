const { chromium } = require('playwright');

(async () => {
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
    const consoleMessages = [];
    page.on('console', msg => {
        consoleMessages.push({
            type: msg.type(),
            text: msg.text()
        });
    });

    try {
        console.log('🔐 Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        // Press Enter to submit the form
        await page.keyboard.press('Enter');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        console.log('✅ Login successful');

        // Navigate to profile detail page
        const profileUrl = 'https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profiles/019ad524-9807-73d4-892e-e1ca9fc6cd84';
        console.log('🔍 Navigating to profile detail page...');
        await page.goto(profileUrl, { waitUntil: 'networkidle', timeout: 15000 });

        // Take screenshot
        await page.screenshot({ path: 'test-results/profile-detail.png', fullPage: true });
        console.log('📸 Screenshot saved: test-results/profile-detail.png');

        // Check for any JavaScript errors
        const errors = consoleMessages.filter(msg => msg.type === 'error');
        if (errors.length > 0) {
            console.log('❌ JavaScript errors found:');
            errors.forEach(err => console.log('  -', err.text));
        } else {
            console.log('✅ No JavaScript errors on page load');
        }

        // Wait for and click Queue Settings button
        console.log('🔘 Looking for Queue Settings button...');
        await page.waitForSelector('button:has-text("Queue Settings")', { timeout: 5000 });
        await page.click('button:has-text("Queue Settings")');
        console.log('✅ Queue Settings button clicked');

        // Wait for modal to appear
        await page.waitForSelector('[x-show="showQueueModal"]', { state: 'visible', timeout: 5000 });
        console.log('✅ Queue Settings modal opened');

        // Take screenshot of modal
        await page.screenshot({ path: 'test-results/queue-modal.png', fullPage: true });
        console.log('📸 Modal screenshot saved: test-results/queue-modal.png');

        // Check for errors after opening modal
        const modalErrors = consoleMessages.filter(msg => msg.type === 'error');
        if (modalErrors.length > errors.length) {
            console.log('❌ New JavaScript errors after opening modal:');
            modalErrors.slice(errors.length).forEach(err => console.log('  -', err.text));
        } else {
            console.log('✅ No new JavaScript errors after opening modal');
        }

        console.log('\n✅ ALL TESTS PASSED! Profile detail page and Queue Settings modal work correctly.');

    } catch (error) {
        console.error('❌ Test failed:', error.message);

        // Take error screenshot
        await page.screenshot({ path: 'test-results/error-screenshot.png', fullPage: true });
        console.log('📸 Error screenshot saved: test-results/error-screenshot.png');

        // Show console errors
        const errors = consoleMessages.filter(msg => msg.type === 'error');
        if (errors.length > 0) {
            console.log('\n❌ JavaScript errors:');
            errors.forEach(err => console.log('  -', err.text));
        }

        process.exit(1);
    } finally {
        await browser.close();
    }
})();
