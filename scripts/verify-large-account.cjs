const { chromium } = require('playwright');

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
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

    // Test Regular User connection (large account)
    const regularUserConnection = '019aee2d-480e-70f7-b5da-9c01f14288b9';
    const baseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${regularUserConnection}/assets/ajax`;

    console.log('\n=== REGULAR USER (LARGE ACCOUNT) ===');
    console.log('Testing with MAX_BUSINESSES=200...\n');

    const endpoints = ['businesses', 'whatsapp', 'catalogs', 'pages', 'instagram', 'ad-accounts'];

    for (const endpoint of endpoints) {
        try {
            const url = `${baseUrl}/${endpoint}?refresh=true`;
            const response = await page.evaluate(async (url) => {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                return {
                    status: res.status,
                    data: await res.json()
                };
            }, url);

            const count = response.data.data?.length || 0;
            console.log(`${endpoint.toUpperCase()}: ${count}`);
        } catch (error) {
            console.log(`${endpoint.toUpperCase()}: ERROR - ${error.message}`);
        }
    }

    // Also test System User connection
    const systemUserConnection = '019aedf2-507e-736a-9156-071c364b2db0';
    const systemBaseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${systemUserConnection}/assets/ajax`;

    console.log('\n=== SYSTEM USER ===');

    for (const endpoint of ['businesses', 'whatsapp', 'catalogs']) {
        try {
            const url = `${systemBaseUrl}/${endpoint}?refresh=true`;
            const response = await page.evaluate(async (url) => {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                return {
                    status: res.status,
                    data: await res.json()
                };
            }, url);

            const count = response.data.data?.length || 0;
            console.log(`${endpoint.toUpperCase()}: ${count}`);
        } catch (error) {
            console.log(`${endpoint.toUpperCase()}: ERROR - ${error.message}`);
        }
    }

    console.log('\n=== VERIFICATION COMPLETE ===');

    await browser.close();
}

main().catch(console.error);
