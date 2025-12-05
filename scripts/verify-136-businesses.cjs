const { chromium } = require('playwright');

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    // Login
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.waitForLoadState('networkidle');
    await page.fill('#email', 'admin@cmis.test');
    await page.fill('#password', 'password');
    await page.click('form button[type="submit"]:visible');
    await page.waitForURL(/.*dashboard.*|.*orgs.*/, { timeout: 15000 });
    console.log('Logged in');

    const org = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    const userTokenConnection = '019aee2d-480e-70f7-b5da-9c01f14288b9';

    // Navigate to User Token assets page
    const assetsUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets`;
    console.log('\nNavigating to User Token assets page...');
    await page.goto(assetsUrl);
    await page.waitForLoadState('networkidle');

    // Wait for businesses to load
    console.log('Waiting for businesses to load...');
    await page.waitForTimeout(10000);

    // First, click the refresh button to force refresh
    console.log('Clicking Business Managers refresh button...');
    const refreshBtn = await page.$('button[\\@click="refreshBusinesses()"]');
    if (refreshBtn) {
        await refreshBtn.click();
        console.log('Refresh button clicked, waiting for data...');

        // Wait for loading to complete
        await page.waitForFunction(() => {
            const alpineEl = document.querySelector('[x-data]');
            if (alpineEl && alpineEl._x_dataStack) {
                return !alpineEl._x_dataStack[0].loading.businesses && !alpineEl._x_dataStack[0].refreshing.businesses;
            }
            return false;
        }, { timeout: 60000 });

        console.log('Refresh complete!');
    }

    // Wait a bit more for UI to update
    await page.waitForTimeout(2000);

    // Get the business count from Alpine
    const businessCount = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            return alpineEl._x_dataStack[0].businesses?.length || 0;
        }
        return 0;
    });

    console.log(`\n=== RESULT: ${businessCount} Business Managers ===`);

    if (businessCount === 136) {
        console.log('✅ SUCCESS: Correct count of 136 businesses!');
    } else if (businessCount === 100) {
        console.log('❌ FAIL: Still showing 100 (old cached data or fallback)');
    } else {
        console.log(`⚠️ WARNING: Unexpected count: ${businessCount}`);
    }

    // Take screenshot
    await page.screenshot({ path: 'test-results/verify-136-businesses.png', fullPage: true });
    console.log('\nScreenshot saved');

    await browser.close();
}

main().catch(console.error);
