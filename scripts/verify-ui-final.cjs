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

    // Test System User connection
    console.log('\n=== SYSTEM USER CONNECTION ===');
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aedf2-507e-736a-9156-071c364b2db0/assets');
    await page.waitForLoadState('networkidle');

    // Click Refresh Assets button to force reload
    const refreshBtn = await page.$('button:has-text("Refresh")');
    if (refreshBtn) {
        console.log('Clicking Refresh Assets button...');
        await refreshBtn.click();
        await page.waitForTimeout(15000); // Wait for all assets to reload
    } else {
        await page.waitForTimeout(10000);
    }

    // Take screenshot
    await page.screenshot({ path: 'test-results/meta-assets-system-user-final.png', fullPage: true });

    // Get counts from UI
    const systemUserCounts = await page.evaluate(() => {
        const text = document.body.innerText;
        const counts = {};

        // Extract counts from the UI text
        const businessMatch = text.match(/(\d+)\s*business manager/i);
        const whatsappMatch = text.match(/(\d+)\s*number.*available|(\d+)\s*WhatsApp/i);
        const catalogMatch = text.match(/(\d+)\s*catalog.*available/i);
        const pagesMatch = text.match(/(\d+)\s*page.*available/i);
        const adAccountsMatch = text.match(/(\d+)\s*account.*available/i);

        if (businessMatch) counts.businesses = parseInt(businessMatch[1]);
        if (whatsappMatch) counts.whatsapp = parseInt(whatsappMatch[1] || whatsappMatch[2]);
        if (catalogMatch) counts.catalogs = parseInt(catalogMatch[1]);
        if (pagesMatch) counts.pages = parseInt(pagesMatch[1]);

        return counts;
    });

    console.log('UI Counts:', JSON.stringify(systemUserCounts, null, 2));

    // Test Regular User connection
    console.log('\n=== REGULAR USER CONNECTION ===');
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aee2d-480e-70f7-b5da-9c01f14288b9/assets');
    await page.waitForLoadState('networkidle');

    // Click Refresh Assets button
    const refreshBtn2 = await page.$('button:has-text("Refresh")');
    if (refreshBtn2) {
        console.log('Clicking Refresh Assets button...');
        await refreshBtn2.click();
        await page.waitForTimeout(15000);
    } else {
        await page.waitForTimeout(10000);
    }

    // Take screenshot
    await page.screenshot({ path: 'test-results/meta-assets-regular-user-final.png', fullPage: true });

    // Get counts from UI
    const regularUserCounts = await page.evaluate(() => {
        const text = document.body.innerText;
        const counts = {};

        const businessMatch = text.match(/(\d+)\s*business manager/i);
        const whatsappMatch = text.match(/(\d+)\s*number.*available/i);
        const catalogMatch = text.match(/(\d+)\s*catalog.*available/i);
        const pagesMatch = text.match(/(\d+)\s*page.*available/i);

        if (businessMatch) counts.businesses = parseInt(businessMatch[1]);
        if (whatsappMatch) counts.whatsapp = parseInt(whatsappMatch[1]);
        if (catalogMatch) counts.catalogs = parseInt(catalogMatch[1]);
        if (pagesMatch) counts.pages = parseInt(pagesMatch[1]);

        return counts;
    });

    console.log('UI Counts:', JSON.stringify(regularUserCounts, null, 2));

    // Summary
    console.log('\n=== SUMMARY ===');
    console.log('System User - WhatsApp:', systemUserCounts.whatsapp || 0, '(Expected: 3)');
    console.log('System User - Catalogs:', systemUserCounts.catalogs || 0, '(Expected: 9)');
    console.log('System User - Businesses:', systemUserCounts.businesses || 0, '(Expected: 50)');

    await browser.close();
}

main().catch(console.error);
