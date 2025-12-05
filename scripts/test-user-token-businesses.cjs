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
    const userTokenConnection = '019aee2d-480e-70f7-b5da-9c01f14288b9';  // User Token

    // Navigate to User Token assets page
    const assetsUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets`;
    console.log('\nNavigating to User Token assets page...');
    await page.goto(assetsUrl);
    await page.waitForLoadState('networkidle');

    // Force refresh businesses
    console.log('\nForcing refresh of businesses with refresh=true...');
    const baseUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets/ajax`;

    const startTime = Date.now();
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

    // Wait and take screenshot
    await page.waitForTimeout(3000);
    await page.screenshot({ path: 'test-results/user-token-businesses.png', fullPage: true });
    console.log('\nScreenshot saved to test-results/user-token-businesses.png');

    // Check Alpine.js data
    const alpineData = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];
            return {
                businesses: data.businesses?.length || 0,
            };
        }
        return null;
    });

    if (alpineData) {
        console.log(`\nAlpine.js shows: ${alpineData.businesses} businesses`);
    }

    await browser.close();
}

main().catch(console.error);
