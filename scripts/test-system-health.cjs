const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const errors = [];
    const networkErrors = [];
    
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
        }
    });
    
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

    // Go to system health page
    console.log('Navigating to system health page...');
    const response = await page.goto('https://cmis-test.kazaaz.com/super-admin/system/health');
    console.log('Page status:', response.status());
    
    if (response.status() !== 200) {
        const content = await page.content();
        console.log('Error content:', content.substring(0, 500));
    }
    
    // Wait for page to load
    await page.waitForTimeout(2000);
    
    // Take screenshot
    await page.screenshot({ path: '/tmp/system-health.png', fullPage: true });
    console.log('Screenshot saved to /tmp/system-health.png');
    
    // Report errors
    console.log('\n=== CONSOLE ERRORS ===');
    if (errors.length === 0) {
        console.log('No JavaScript errors');
    } else {
        errors.forEach(e => console.log('ERROR:', e.substring(0, 200)));
    }
    
    console.log('\n=== NETWORK ERRORS ===');
    if (networkErrors.length === 0) {
        console.log('No network failures');
    } else {
        networkErrors.forEach(e => console.log('FAILED:', e.url, '-', e.error));
    }
    
    await browser.close();
})();
