const { chromium } = require('playwright');

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 3000 } });
    const page = await context.newPage();

    // Login
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.waitForLoadState('networkidle');
    await page.fill('#email', 'admin@cmis.test');
    await page.fill('#password', 'password');
    await page.click('form button[type="submit"]:visible');
    await page.waitForURL(/.*dashboard.*|.*orgs.*/, { timeout: 15000 });
    console.log('Logged in');

    // Go to System User connection
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aedf2-507e-736a-9156-071c364b2db0/assets');
    await page.waitForLoadState('networkidle');

    // Wait for assets to load
    await page.waitForTimeout(10000);

    // Scroll to bottom
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2000);

    // Take full page screenshot
    await page.screenshot({ path: 'test-results/meta-assets-full-system-user.png', fullPage: true });
    console.log('Screenshot saved: test-results/meta-assets-full-system-user.png');

    // Get text content
    const pageText = await page.evaluate(() => document.body.innerText);

    // Check for specific sections and counts
    console.log('\nSection Check:');
    console.log('Business Managers:', pageText.includes('Business Managers') ? 'FOUND' : 'NOT FOUND');
    console.log('WhatsApp Accounts:', pageText.includes('WhatsApp Business Accounts') ? 'FOUND' : 'NOT FOUND');
    console.log('Product Catalogs:', pageText.includes('Product Catalogs') ? 'FOUND' : 'NOT FOUND');

    // Extract specific counts
    const businessMatch = pageText.match(/(\d+)\s*business manager/i);
    const whatsappMatch = pageText.match(/(\d+)\s*WhatsApp.*available/i);
    const catalogMatch = pageText.match(/(\d+)\s*catalog.*available/i);

    console.log('\nAsset Counts:');
    console.log('Business Managers:', businessMatch ? businessMatch[1] : 'N/A');
    console.log('WhatsApp Accounts:', whatsappMatch ? whatsappMatch[1] : 'N/A');
    console.log('Product Catalogs:', catalogMatch ? catalogMatch[1] : 'N/A');

    // Check for errors
    const hasError = pageText.includes('Failed to load') || pageText.includes('Error loading');
    if (hasError) {
        console.log('\nWARNING: Error messages found on page');
    }

    await browser.close();
}

main().catch(console.error);
