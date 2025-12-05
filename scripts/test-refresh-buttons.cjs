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
    const systemUserConnection = '019aedf2-507e-736a-9156-071c364b2db0';

    // Navigate to System User assets page
    const assetsUrl = `https://cmis-test.kazaaz.com/orgs/${org}/settings/platform-connections/meta/${systemUserConnection}/assets`;
    console.log('\nNavigating to System User assets page...');
    await page.goto(assetsUrl);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Wait for initial load

    // Check for refresh buttons in each section
    console.log('\n=== CHECKING REFRESH BUTTONS ===');

    const sections = [
        { name: 'Business Managers', method: 'refreshBusinesses' },
        { name: 'Facebook Pages', method: 'refreshPages' },
        { name: 'Instagram', method: 'refreshInstagram' },
        { name: 'Threads', method: 'refreshThreads' },
        { name: 'Ad Accounts', method: 'refreshAdAccounts' },
        { name: 'Pixels', method: 'refreshPixels' },
        { name: 'Catalogs', method: 'refreshCatalogs' },
        { name: 'WhatsApp', method: 'refreshWhatsapp' },
        { name: 'Custom Conversions', method: 'refreshCustomConversions' },
        { name: 'Offline Event Sets', method: 'refreshOfflineEventSets' },
    ];

    for (const section of sections) {
        const button = await page.$(`button[\\@click="${section.method}()"]`);
        console.log(`${section.name}: ${button ? '✅ Refresh button found' : '❌ NOT found'}`);
    }

    // Take screenshot
    await page.screenshot({ path: 'test-results/refresh-buttons-test.png', fullPage: true });
    console.log('\nScreenshot saved to test-results/refresh-buttons-test.png');

    // Test one refresh button (Business Managers)
    console.log('\n=== TESTING REFRESH BUTTON (Business Managers) ===');
    const refreshBtn = await page.$('button[\\@click="refreshBusinesses()"]');
    if (refreshBtn) {
        console.log('Clicking refresh button...');
        await refreshBtn.click();
        await page.waitForTimeout(3000); // Wait for refresh to complete

        // Check if the button shows spinner
        const isSpinning = await page.evaluate(() => {
            const btn = document.querySelector('button[\\@click="refreshBusinesses()"]');
            if (btn) {
                const spinner = btn.querySelector('.fa-sync-alt');
                return spinner && spinner.classList.contains('animate-spin');
            }
            return false;
        });
        console.log(`Button animation working: ${isSpinning ? '✅ Yes' : '⏳ Completed'}`);
    }

    // Check browser console for errors
    console.log('\n=== BROWSER CONSOLE ===');
    const logs = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            logs.push(msg.text());
        }
    });
    await page.waitForTimeout(1000);

    if (logs.length > 0) {
        console.log('Console errors:');
        logs.forEach(log => console.log(`  - ${log}`));
    } else {
        console.log('No console errors');
    }

    await browser.close();
    console.log('\n=== TEST COMPLETE ===');
}

main().catch(console.error);
