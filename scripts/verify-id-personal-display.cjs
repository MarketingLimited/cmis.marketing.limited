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

    // Navigate to Meta Assets page
    const assetsUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${userTokenConnection}/assets`;
    console.log('\nNavigating to Meta Assets page...');
    await page.goto(assetsUrl);
    await page.waitForLoadState('networkidle');

    // Wait for assets to load
    console.log('Waiting for assets to load...');
    await page.waitForTimeout(3000);

    // Take screenshot
    await page.screenshot({ path: 'test-results/id-personal-display-test.png', fullPage: true });
    console.log('Screenshot saved to test-results/id-personal-display-test.png');

    // Check for ID displays
    const idDisplays = await page.$$eval('span:has-text("ID:")', elements => elements.length);
    console.log(`\n=== ID Display Check ===`);
    console.log(`ID displays found: ${idDisplays}`);

    // Check for Personal displays
    const personalDisplays = await page.evaluate(() => {
        const elements = document.querySelectorAll('i.fa-user');
        return elements.length;
    });
    console.log(`Personal icons found: ${personalDisplays}`);

    // Check for Business displays
    const businessDisplays = await page.evaluate(() => {
        const elements = document.querySelectorAll('i.fa-building');
        return elements.length;
    });
    console.log(`Business icons found: ${businessDisplays}`);

    // Get asset data from Alpine
    const assetData = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];
            return {
                pages: (data.pages || []).slice(0, 2).map(p => ({
                    name: p.name,
                    id: p.id,
                    business_name: p.business_name
                })),
                instagram: (data.instagramAccounts || []).slice(0, 2).map(ig => ({
                    name: ig.username || ig.name,
                    id: ig.id,
                    business_name: ig.business_name
                })),
                adAccounts: (data.adAccounts || []).slice(0, 2).map(a => ({
                    name: a.name,
                    id: a.account_id,
                    business_name: a.business_name
                })),
                pixels: (data.pixels || []).slice(0, 2).map(p => ({
                    name: p.name,
                    id: p.id,
                    business_name: p.business_name
                })),
                catalogs: (data.catalogs || []).slice(0, 2).map(c => ({
                    name: c.name,
                    id: c.id,
                    business_name: c.business_name
                })),
            };
        }
        return null;
    });

    console.log('\n=== Sample Asset Data ===');
    if (assetData) {
        console.log('\nFacebook Pages:');
        assetData.pages.forEach(p => {
            console.log(`  - ${p.name} | ID: ${p.id} | Business: ${p.business_name || 'Personal'}`);
        });

        console.log('\nInstagram Accounts:');
        assetData.instagram.forEach(ig => {
            console.log(`  - ${ig.name} | ID: ${ig.id} | Business: ${ig.business_name || 'Personal'}`);
        });

        console.log('\nAd Accounts:');
        assetData.adAccounts.forEach(a => {
            console.log(`  - ${a.name} | ID: ${a.id} | Business: ${a.business_name || 'Personal'}`);
        });

        console.log('\nPixels:');
        assetData.pixels.forEach(p => {
            console.log(`  - ${p.name} | ID: ${p.id} | Business: ${p.business_name || 'Personal'}`);
        });

        console.log('\nCatalogs:');
        assetData.catalogs.forEach(c => {
            console.log(`  - ${c.name} | ID: ${c.id} | Business: ${c.business_name || 'Personal'}`);
        });
    }

    await browser.close();
    console.log('\n✅ Test completed!');
}

main().catch(console.error);
