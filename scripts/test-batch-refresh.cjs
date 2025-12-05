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
    const systemUserConnection = '019aedf2-507e-736a-9156-071c364b2db0';

    // Navigate to assets page first
    const assetsUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${systemUserConnection}/assets`;
    console.log('\nNavigating to System User assets page...');
    await page.goto(assetsUrl);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Force refresh by calling the businesses API with refresh=true
    console.log('\nForcing refresh via AJAX with refresh=true...');
    const baseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${systemUserConnection}/assets/ajax`;

    const startTime = Date.now();

    try {
        // Call businesses endpoint with refresh=true - this triggers the batch API
        const businessResponse = await page.evaluate(async (url) => {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            return {
                status: res.status,
                data: await res.json()
            };
        }, `${baseUrl}/businesses?refresh=true`);

        const elapsed = Date.now() - startTime;
        console.log(`\nBusinesses API completed in ${elapsed}ms`);
        console.log(`Status: ${businessResponse.status}`);
        console.log(`Count: ${businessResponse.data?.data?.length || 0} businesses`);

        if (businessResponse.data?.error) {
            console.log(`Error: ${businessResponse.data.error}`);
        }
    } catch (error) {
        console.log(`Error: ${error.message}`);
    }

    console.log('\n=== CHECK LARAVEL LOGS FOR BATCH API ===');
    console.log('Run: grep -E "(Batch API|batch|fallback)" storage/logs/laravel.log | tail -30');

    await browser.close();
}

main().catch(console.error);
