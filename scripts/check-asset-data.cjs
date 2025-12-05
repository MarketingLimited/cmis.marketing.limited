const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('#email', 'admin@cmis.test');
    await page.fill('#password', 'password');
    await page.click('form button[type="submit"]:visible');
    await page.waitForTimeout(3000);

    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aee2d-480e-70f7-b5da-9c01f14288b9/assets');
    await page.waitForTimeout(8000);

    // Check asset data
    const assetData = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];

            // Get samples of each type
            const igSample = (data.instagramAccounts || []).slice(0, 3).map(ig => ({
                name: ig.username || ig.name,
                id: ig.id,
                business_name: ig.business_name,
                source: ig.source,
                connected_page_name: ig.connected_page_name
            }));

            const adSample = (data.adAccounts || []).slice(0, 3).map(a => ({
                name: a.name,
                id: a.account_id,
                business_name: a.business_name,
                source: a.source,
                business_id: a.business_id
            }));

            const pixelSample = (data.pixels || []).slice(0, 3).map(p => ({
                name: p.name,
                id: p.id,
                business_name: p.business_name,
                source: p.source,
                ad_account_name: p.ad_account_name
            }));

            return { igSample, adSample, pixelSample };
        }
        return null;
    });

    if (assetData) {
        console.log('\n=== Instagram Accounts Data ===');
        assetData.igSample.forEach(ig => {
            console.log(`  Name: ${ig.name}`);
            console.log(`    ID: ${ig.id}`);
            console.log(`    business_name: ${ig.business_name || '(not set)'}`);
            console.log(`    source: ${ig.source || '(not set)'}`);
            console.log(`    connected_page_name: ${ig.connected_page_name || '(not set)'}`);
            console.log('');
        });

        console.log('\n=== Ad Accounts Data ===');
        assetData.adSample.forEach(a => {
            console.log(`  Name: ${a.name}`);
            console.log(`    ID: ${a.id}`);
            console.log(`    business_name: ${a.business_name || '(not set)'}`);
            console.log(`    source: ${a.source || '(not set)'}`);
            console.log(`    business_id: ${a.business_id || '(not set)'}`);
            console.log('');
        });

        console.log('\n=== Pixels Data ===');
        assetData.pixelSample.forEach(p => {
            console.log(`  Name: ${p.name}`);
            console.log(`    ID: ${p.id}`);
            console.log(`    business_name: ${p.business_name || '(not set)'}`);
            console.log(`    source: ${p.source || '(not set)'}`);
            console.log(`    ad_account_name: ${p.ad_account_name || '(not set)'}`);
            console.log('');
        });
    } else {
        console.log('Could not access Alpine data');
    }

    await browser.close();
})();
