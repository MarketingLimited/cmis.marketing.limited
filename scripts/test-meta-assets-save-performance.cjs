const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'en'
    });
    const page = await context.newPage();

    try {
        await context.addCookies([{
            name: 'app_locale',
            value: 'en',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        // Login
        console.log('🔐 Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('button.bg-indigo-600[type="submit"], form button[type="submit"]:visible');
        await page.waitForURL('**/dashboard**', { timeout: 30000 });
        console.log('✓ Logged in');

        // Navigate to meta assets page
        console.log('\n📄 Loading Meta Assets page...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/4a5b6c7d-8e9f-0a1b-2c3d-4e5f6a7b8c9d/assets', {
            waitUntil: 'load',
            timeout: 60000
        });

        // Wait for page to be interactive
        console.log('⏳ Waiting for page to be ready...');
        await page.waitForTimeout(3000);

        // Wait for form to exist
        await page.waitForSelector('form', { timeout: 10000 });

        // Take a screenshot before save
        await page.screenshot({ path: '/tmp/meta-assets-before-save.png', fullPage: true });

        // Find the submit button more reliably
        const submitButton = await page.locator('button[type="submit"]').first();
        const buttonText = await submitButton.textContent();
        console.log(`   Found button: "${buttonText.trim()}"`);

        // Record start time
        const startTime = Date.now();
        console.log('\n💾 Clicking save button...');

        // Click and wait for navigation or response
        await Promise.all([
            page.waitForNavigation({ timeout: 120000 }).catch(() => {}),
            submitButton.click()
        ]);

        const endTime = Date.now();
        const duration = (endTime - startTime) / 1000;

        console.log('\n📊 RESULTS:');
        console.log(`   Duration: ${duration.toFixed(2)} seconds`);

        if (duration < 3) {
            console.log('   ✅ PASS - Save completed quickly!');
        } else if (duration < 15) {
            console.log('   ⚠️  IMPROVED - But could be faster');
        } else {
            console.log('   ❌ STILL SLOW - Fix may not be working');
        }

        // Take screenshot of result
        await page.waitForTimeout(1000);
        await page.screenshot({ path: '/tmp/meta-assets-after-save.png', fullPage: true });

        // Check current URL to see if we redirected
        const currentUrl = page.url();
        console.log(`   Final URL: ${currentUrl}`);

    } catch (error) {
        console.error('❌ Error:', error.message);
        await page.screenshot({ path: '/tmp/meta-assets-save-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
