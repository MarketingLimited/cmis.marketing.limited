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
    const systemUrl = 'https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aedf2-507e-736a-9156-071c364b2db0/assets';
    await page.goto(systemUrl);
    await page.waitForLoadState('networkidle');

    // Wait for loading to complete - check for spinner to disappear
    console.log('Waiting for all assets to load...');
    await page.waitForTimeout(5000);

    // Wait until no more loading spinners are visible
    let attempts = 0;
    while (attempts < 30) {
        const loadingSpinners = await page.$$('.fa-spin:visible, .fa-spinner:visible');
        const visibleSpinners = await Promise.all(loadingSpinners.map(el => el.isVisible()));
        const activeSpinners = visibleSpinners.filter(v => v).length;

        if (activeSpinners === 0) {
            console.log('All assets loaded!');
            break;
        }
        console.log(`Still loading... (${activeSpinners} spinners active)`);
        await page.waitForTimeout(2000);
        attempts++;
    }

    // Get all counts from Alpine.js data
    const systemUserData = await page.evaluate(() => {
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
                pixels: data.pixels?.length || 0,
                customConversions: data.customConversions?.length || 0,
                offlineEventSets: data.offlineEventSets?.length || 0,
            };
        }
        return null;
    });

    console.log('\nAlpine.js Data Counts:');
    console.log(JSON.stringify(systemUserData, null, 2));

    // Take screenshot
    await page.screenshot({ path: 'test-results/final-system-user.png', fullPage: true });

    // Summary
    console.log('\n=== VERIFICATION RESULTS ===');
    if (systemUserData) {
        const whatsappOk = systemUserData.whatsapp >= 3;
        const catalogsOk = systemUserData.catalogs >= 9;
        const businessesOk = systemUserData.businesses >= 50;

        console.log(`Business Managers: ${systemUserData.businesses} ${businessesOk ? '✅' : '❌'} (expected >= 50)`);
        console.log(`WhatsApp Accounts: ${systemUserData.whatsapp} ${whatsappOk ? '✅' : '❌'} (expected >= 3)`);
        console.log(`Product Catalogs: ${systemUserData.catalogs} ${catalogsOk ? '✅' : '❌'} (expected >= 9)`);
        console.log(`Facebook Pages: ${systemUserData.pages}`);
        console.log(`Instagram Accounts: ${systemUserData.instagram}`);
        console.log(`Ad Accounts: ${systemUserData.adAccounts}`);
        console.log(`Pixels: ${systemUserData.pixels}`);

        if (whatsappOk && catalogsOk && businessesOk) {
            console.log('\n✅ ALL TESTS PASSED! WhatsApp and Catalogs fix verified.');
        } else {
            console.log('\n⚠️ Some counts are lower than expected.');
        }
    }

    await browser.close();
}

main().catch(console.error);
