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
    await page.waitForTimeout(4000);

    // Take screenshot
    await page.screenshot({ path: 'test-results/ownership-display-test.png', fullPage: true });
    console.log('Screenshot saved to test-results/ownership-display-test.png');

    // Check for "Owned by:" text
    const ownedByText = await page.evaluate(() => {
        const text = document.body.innerText;
        return (text.match(/Owned by:/g) || []).length;
    });
    console.log(`\n=== Ownership Display Check ===`);
    console.log(`"Owned by:" occurrences: ${ownedByText}`);

    // Check for "Managed by:" text
    const managedByText = await page.evaluate(() => {
        const text = document.body.innerText;
        return (text.match(/Managed by:/g) || []).length;
    });
    console.log(`"Managed by:" occurrences: ${managedByText}`);

    // Check for "Personal" text
    const personalText = await page.evaluate(() => {
        const text = document.body.innerText;
        return (text.match(/Personal/g) || []).length;
    });
    console.log(`"Personal" occurrences: ${personalText}`);

    // Check icons
    const buildingIcons = await page.evaluate(() => document.querySelectorAll('i.fa-building').length);
    const handshakeIcons = await page.evaluate(() => document.querySelectorAll('i.fa-handshake').length);
    const userIcons = await page.evaluate(() => document.querySelectorAll('i.fa-user').length);

    console.log(`\n=== Icon Count ===`);
    console.log(`Building icons (Owned): ${buildingIcons}`);
    console.log(`Handshake icons (Managed): ${handshakeIcons}`);
    console.log(`User icons (Personal): ${userIcons}`);

    // Get sample data
    const pageData = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];
            return (data.pages || []).slice(0, 3).map(p => ({
                name: p.name,
                source: p.source,
                business_name: p.business_name
            }));
        }
        return [];
    });

    console.log('\n=== Sample Page Data ===');
    pageData.forEach(p => {
        const ownership = p.source === 'client' ? 'Managed by' : (p.business_name ? 'Owned by' : 'Personal');
        console.log(`  - ${p.name} | source: ${p.source || 'N/A'} | ${ownership}: ${p.business_name || 'Personal'}`);
    });

    await browser.close();
    console.log('\n✅ Test completed!');
}

main().catch(console.error);
