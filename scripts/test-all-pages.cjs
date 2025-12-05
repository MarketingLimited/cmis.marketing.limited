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

    console.log('\n=== Testing Facebook Pages Fetch ===');
    const baseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets/ajax`;

    // First navigate to the page so we have proper auth cookies
    await page.goto(`https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Fetch pages with refresh
    console.log('Fetching pages with refresh=true...');
    const startTime = Date.now();
    const pagesResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        return {
            status: res.status,
            data: await res.json()
        };
    }, `${baseUrl}/pages?refresh=true`);
    const elapsed = Date.now() - startTime;

    console.log(`\nPages API completed in ${elapsed}ms`);
    console.log(`Status: ${pagesResponse.status}`);
    console.log(`Count: ${pagesResponse.data?.data?.length || 0} Facebook Pages`);

    if (pagesResponse.data?.error) {
        console.log(`Error: ${pagesResponse.data.error}`);
    }

    // Also test Instagram
    console.log('\n=== Testing Instagram Fetch ===');
    const igResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        return {
            status: res.status,
            data: await res.json()
        };
    }, `${baseUrl}/instagram?refresh=true`);

    console.log(`Instagram Count: ${igResponse.data?.data?.length || 0} accounts`);

    await browser.close();
    console.log('\n=== CHECK LARAVEL LOGS ===');
    console.log('Run: grep -E "(merged|business_pages)" storage/logs/laravel.log | tail -10');
}

main().catch(console.error);
