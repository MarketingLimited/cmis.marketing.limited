const { chromium } = require('playwright');
(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    // Login
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('#email', 'admin@cmis.test');
    await page.fill('#password', 'password');
    await page.click('form button[type="submit"]:visible');
    await page.waitForTimeout(3000);

    // Navigate to Meta Assets
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aee2d-480e-70f7-b5da-9c01f14288b9/assets');
    await page.waitForTimeout(5000);

    // Scroll to find owned assets
    await page.evaluate(() => window.scrollBy(0, 500));
    await page.waitForTimeout(1000);

    await page.screenshot({ path: 'test-results/ownership-scrolled.png', fullPage: false });
    console.log('Scrolled screenshot saved');

    // Check for pages with business_name
    const pageData = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];
            const owned = (data.pages || []).filter(p => p.source === 'owned').slice(0, 3);
            const client = (data.pages || []).filter(p => p.source === 'client').slice(0, 3);
            const personal = (data.pages || []).filter(p => !p.business_name).slice(0, 3);
            return { owned, client, personal };
        }
        return null;
    });

    if (pageData) {
        console.log('\n=== Owned Pages ===');
        pageData.owned.forEach(p => console.log('  -', p.name, '| Owned by:', p.business_name));

        console.log('\n=== Client Pages ===');
        pageData.client.forEach(p => console.log('  -', p.name, '| Managed by:', p.business_name));

        console.log('\n=== Personal Pages ===');
        pageData.personal.forEach(p => console.log('  -', p.name, '| Personal'));
    }

    await browser.close();
})();
