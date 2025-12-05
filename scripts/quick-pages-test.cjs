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
    const baseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets/ajax`;

    // First, trigger a refresh of businesses to get fresh data with pages
    console.log('\nRefreshing businesses first (to get fresh data with pages)...');
    const bizResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        return { status: res.status, data: await res.json() };
    }, `${baseUrl}/businesses?refresh=true`);
    console.log(`Businesses: ${bizResponse.data?.data?.length || 0}`);

    // Now fetch pages
    console.log('\nFetching pages with refresh=true...');
    const pagesResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        return { status: res.status, data: await res.json() };
    }, `${baseUrl}/pages?refresh=true`);

    console.log(`Pages: ${pagesResponse.data?.data?.length || 0} Facebook Pages`);

    await browser.close();
}

main().catch(console.error);
