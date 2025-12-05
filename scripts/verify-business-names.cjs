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
    console.log('\nNavigating to Meta Assets page...');
    await page.goto(assetsUrl);
    await page.waitForLoadState('networkidle');

    // Wait for assets to load
    console.log('Waiting for assets to load...');
    await page.waitForTimeout(5000);

    // Take initial screenshot
    await page.screenshot({ path: 'test-results/business-names-initial.png', fullPage: true });
    console.log('Initial screenshot saved');

    // Click refresh on businesses first
    console.log('\nRefreshing businesses...');
    const bizRefreshBtn = await page.$('button:has-text("Refresh"):near(:text("Business Managers"))');
    if (bizRefreshBtn) {
        await bizRefreshBtn.click();
        await page.waitForTimeout(10000);
    }

    // Take screenshot after refresh
    await page.screenshot({ path: 'test-results/business-names-after-refresh.png', fullPage: true });
    console.log('After refresh screenshot saved');

    // Get asset counts from Alpine
    const assetCounts = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];
            return {
                businesses: data.businesses?.length || 0,
                pages: data.pages?.length || 0,
                instagram: data.instagramAccounts?.length || 0,
                adAccounts: data.adAccounts?.length || 0,
                pixels: data.pixels?.length || 0,
                catalogs: data.catalogs?.length || 0,
                whatsapp: data.whatsappAccounts?.length || 0,
            };
        }
        return null;
    });

    console.log('\n=== ASSET COUNTS ===');
    console.log(`Business Managers: ${assetCounts?.businesses || 0}`);
    console.log(`Facebook Pages: ${assetCounts?.pages || 0}`);
    console.log(`Instagram Accounts: ${assetCounts?.instagram || 0}`);
    console.log(`Ad Accounts: ${assetCounts?.adAccounts || 0}`);
    console.log(`Pixels: ${assetCounts?.pixels || 0}`);
    console.log(`Catalogs: ${assetCounts?.catalogs || 0}`);
    console.log(`WhatsApp: ${assetCounts?.whatsapp || 0}`);

    // Check if business_name is visible in Pages section
    const pagesWithBusiness = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const pages = alpineEl._x_dataStack[0].pages || [];
            const withBusiness = pages.filter(p => p.business_name);
            return {
                total: pages.length,
                withBusinessName: withBusiness.length,
                samples: withBusiness.slice(0, 3).map(p => ({ name: p.name, business: p.business_name }))
            };
        }
        return null;
    });

    console.log('\n=== PAGES WITH BUSINESS NAME ===');
    console.log(`Total pages: ${pagesWithBusiness?.total || 0}`);
    console.log(`With business_name: ${pagesWithBusiness?.withBusinessName || 0}`);
    if (pagesWithBusiness?.samples?.length) {
        console.log('Sample pages:');
        pagesWithBusiness.samples.forEach(p => {
            console.log(`  - ${p.name} -> ${p.business}`);
        });
    }

    // Check Instagram
    const instagramWithBusiness = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const accounts = alpineEl._x_dataStack[0].instagramAccounts || [];
            const withBusiness = accounts.filter(a => a.business_name);
            return {
                total: accounts.length,
                withBusinessName: withBusiness.length,
                samples: withBusiness.slice(0, 3).map(a => ({ name: a.username || a.name, business: a.business_name }))
            };
        }
        return null;
    });

    console.log('\n=== INSTAGRAM WITH BUSINESS NAME ===');
    console.log(`Total accounts: ${instagramWithBusiness?.total || 0}`);
    console.log(`With business_name: ${instagramWithBusiness?.withBusinessName || 0}`);
    if (instagramWithBusiness?.samples?.length) {
        console.log('Sample accounts:');
        instagramWithBusiness.samples.forEach(a => {
            console.log(`  - ${a.name} -> ${a.business}`);
        });
    }

    // Check Ad Accounts
    const adAccountsWithBusiness = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const accounts = alpineEl._x_dataStack[0].adAccounts || [];
            const withBusiness = accounts.filter(a => a.business_name);
            return {
                total: accounts.length,
                withBusinessName: withBusiness.length,
                samples: withBusiness.slice(0, 3).map(a => ({ name: a.name, business: a.business_name }))
            };
        }
        return null;
    });

    console.log('\n=== AD ACCOUNTS WITH BUSINESS NAME ===');
    console.log(`Total accounts: ${adAccountsWithBusiness?.total || 0}`);
    console.log(`With business_name: ${adAccountsWithBusiness?.withBusinessName || 0}`);
    if (adAccountsWithBusiness?.samples?.length) {
        console.log('Sample accounts:');
        adAccountsWithBusiness.samples.forEach(a => {
            console.log(`  - ${a.name} -> ${a.business}`);
        });
    }

    // Check if building icon is visible in the UI
    const buildingIcons = await page.$$eval('i.fa-building', icons => icons.length);
    console.log(`\n=== UI VERIFICATION ===`);
    console.log(`Building icons visible: ${buildingIcons}`);

    await browser.close();
    console.log('\nTest completed!');
}

main().catch(console.error);
