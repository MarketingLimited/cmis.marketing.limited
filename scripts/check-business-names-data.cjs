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

    // Fetch pages with refresh
    console.log('\nFetching pages...');
    const pagesResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        return { status: res.status, data: await res.json() };
    }, `${baseUrl}/pages?refresh=true`);

    const pages = pagesResponse.data?.data || [];
    console.log(`Total pages: ${pages.length}`);

    // Check how many have business_name
    const withBusinessName = pages.filter(p => p.business_name);
    console.log(`With business_name: ${withBusinessName.length}`);

    // Show sample pages with business_name
    console.log('\n=== Sample pages WITH business_name ===');
    withBusinessName.slice(0, 5).forEach(p => {
        console.log(`  - ${p.name} -> Business: ${p.business_name}`);
    });

    // Show sample pages without business_name
    const withoutBusinessName = pages.filter(p => !p.business_name);
    console.log(`\n=== Sample pages WITHOUT business_name ===`);
    withoutBusinessName.slice(0, 5).forEach(p => {
        console.log(`  - ${p.name} (${p.source || 'unknown source'})`);
    });

    // Fetch Instagram
    console.log('\n\nFetching Instagram...');
    const igResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        return { status: res.status, data: await res.json() };
    }, `${baseUrl}/instagram?refresh=true`);

    const instagram = igResponse.data?.data || [];
    console.log(`Total Instagram: ${instagram.length}`);

    const igWithBusiness = instagram.filter(ig => ig.business_name);
    console.log(`With business_name: ${igWithBusiness.length}`);

    console.log('\n=== Sample Instagram WITH business_name ===');
    igWithBusiness.slice(0, 5).forEach(ig => {
        console.log(`  - ${ig.username || ig.name} -> Business: ${ig.business_name}`);
    });

    // Fetch Ad Accounts
    console.log('\n\nFetching Ad Accounts...');
    const adResponse = await page.evaluate(async (url) => {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        return { status: res.status, data: await res.json() };
    }, `${baseUrl}/ad-accounts?refresh=true`);

    const adAccounts = adResponse.data?.data || [];
    console.log(`Total Ad Accounts: ${adAccounts.length}`);

    const adWithBusiness = adAccounts.filter(a => a.business_name);
    console.log(`With business_name: ${adWithBusiness.length}`);

    console.log('\n=== Sample Ad Accounts WITH business_name ===');
    adWithBusiness.slice(0, 5).forEach(a => {
        console.log(`  - ${a.name} -> Business: ${a.business_name}`);
    });

    await browser.close();
    console.log('\nDone!');
}

main().catch(console.error);
