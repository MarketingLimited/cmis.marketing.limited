const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const errors = [];
    const networkErrors = [];
    
    // Capture console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
        }
    });
    
    // Capture network failures
    page.on('requestfailed', request => {
        networkErrors.push({
            url: request.url(),
            error: request.failure()?.errorText
        });
    });

    // Login
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button.bg-indigo-600[type="submit"]');
    await page.waitForURL(/dashboard|super-admin/, { timeout: 15000 });

    // Go to analytics page
    console.log('Navigating to analytics page...');
    const response = await page.goto('https://cmis-test.kazaaz.com/super-admin/analytics');
    console.log('Page status:', response.status());
    
    // Wait for AJAX calls to complete
    await page.waitForTimeout(3000);
    
    // Take screenshot
    await page.screenshot({ path: '/tmp/analytics-test.png', fullPage: true });
    console.log('Screenshot saved to /tmp/analytics-test.png');
    
    // Report errors
    console.log('\n=== CONSOLE ERRORS ===');
    if (errors.length === 0) {
        console.log('No JavaScript errors');
    } else {
        errors.forEach(e => console.log('ERROR:', e));
    }
    
    console.log('\n=== NETWORK ERRORS ===');
    if (networkErrors.length === 0) {
        console.log('No network failures');
    } else {
        networkErrors.forEach(e => console.log('FAILED:', e.url, '-', e.error));
    }
    
    // Check if data was loaded (look for "0" values in stats)
    const statsText = await page.locator('.text-2xl.font-bold').allTextContents();
    console.log('\n=== STATS VALUES ===');
    console.log('Stats:', statsText);
    
    await browser.close();
})();
