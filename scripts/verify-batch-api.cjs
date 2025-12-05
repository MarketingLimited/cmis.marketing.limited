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

    // Navigate to System User connection assets page (triggers API call)
    const systemUrl = 'https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aedf2-507e-736a-9156-071c364b2db0/assets';
    console.log('\nNavigating to System User assets page...');
    await page.goto(systemUrl);
    await page.waitForLoadState('networkidle');

    // Wait for the page to fully load and trigger AJAX calls
    console.log('Waiting for all AJAX calls to complete...');
    await page.waitForTimeout(15000); // Wait 15 seconds for all AJAX to complete

    // Take screenshot
    await page.screenshot({ path: 'test-results/batch-api-test.png', fullPage: true });
    console.log('Screenshot saved to test-results/batch-api-test.png');

    // Check what data was loaded by reading Alpine.js state
    const alpineData = await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            const data = alpineEl._x_dataStack[0];
            return {
                businesses: data.businesses?.length || 0,
                pages: data.pages?.length || 0,
                instagram: data.instagramAccounts?.length || 0,
                adAccounts: data.adAccounts?.length || 0,
                whatsapp: data.whatsappAccounts?.length || 0,
                catalogs: data.catalogs?.length || 0,
            };
        }
        return null;
    });

    console.log('\n=== LOADED DATA ===');
    if (alpineData) {
        console.log(`Business Managers: ${alpineData.businesses}`);
        console.log(`Facebook Pages: ${alpineData.pages}`);
        console.log(`Instagram Accounts: ${alpineData.instagram}`);
        console.log(`Ad Accounts: ${alpineData.adAccounts}`);
        console.log(`WhatsApp Accounts: ${alpineData.whatsapp}`);
        console.log(`Product Catalogs: ${alpineData.catalogs}`);
    } else {
        console.log('Could not read Alpine.js data');
    }

    console.log('\n=== CHECK LARAVEL LOGS FOR BATCH API MESSAGES ===');
    console.log('Run: grep -i "batch\\|fallback" storage/logs/laravel.log | tail -20');

    await browser.close();
}

main().catch(console.error);
