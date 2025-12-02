const { chromium } = require('playwright');
(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button.bg-indigo-600:visible, button[type="submit"]:visible >> nth=0');
    await page.waitForURL(/dashboard|orgs/, { timeout: 15000 });
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/marketplace');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await page.screenshot({ path: 'test-results/marketplace-clean.png', fullPage: true });
    await browser.close();
    console.log('Done');
})();
