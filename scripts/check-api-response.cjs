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
    const connection = '019aedf2-507e-736a-9156-071c364b2db0';
    const baseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${connection}/assets/ajax`;

    const endpoints = ['businesses', 'whatsapp', 'catalogs', 'pages', 'ad-accounts'];

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

            console.log(`\n${endpoint.toUpperCase()}:`);
            console.log(`  Status: ${response.status}`);
            if (response.data.success !== undefined) {
                console.log(`  Success: ${response.data.success}`);
            }
            if (response.data.data) {
                if (Array.isArray(response.data.data)) {
                    console.log(`  Count: ${response.data.data.length}`);
                    if (response.data.data.length > 0) {
                        console.log(`  First item: ${JSON.stringify(response.data.data[0]).substring(0, 150)}...`);
                    }
                } else {
                    console.log(`  Data: ${JSON.stringify(response.data.data).substring(0, 200)}`);
                }
            }
            if (response.data.message) {
                console.log(`  Message: ${response.data.message}`);
            }
        } catch (error) {
            console.log(`\n${endpoint.toUpperCase()}: ERROR - ${error.message}`);
        }
    }

    await browser.close();
}

main().catch(console.error);
