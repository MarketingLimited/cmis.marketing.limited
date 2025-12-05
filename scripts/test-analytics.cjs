const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Capture console logs
    page.on('console', msg => console.log('Browser:', msg.text()));

    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button.bg-indigo-600[type="submit"]');
    await page.waitForURL(/dashboard|super-admin/, { timeout: 15000 });

    // Go to analytics page
    const response = await page.goto('https://cmis-test.kazaaz.com/super-admin/analytics');
    console.log('Status:', response.status());
    
    await page.waitForTimeout(1000);
    
    // Take screenshot
    await page.screenshot({ path: '/tmp/analytics-page.png', fullPage: true });
    console.log('Screenshot saved to /tmp/analytics-page.png');
    
    // Check for error messages or empty states
    const content = await page.content();
    if (content.includes('لا توجد بيانات') || content.includes('No data')) {
        console.log('Found "No data" message');
    }
    
    await browser.close();
})();
