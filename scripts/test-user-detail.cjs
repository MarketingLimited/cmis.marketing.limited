const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button.bg-indigo-600[type="submit"]');
    await page.waitForURL(/dashboard|super-admin/, { timeout: 15000 });

    // Go to user detail page
    console.log('Navigating to user detail page...');
    const response = await page.goto('https://cmis-test.kazaaz.com/super-admin/users/2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e');

    console.log('Status:', response.status());

    if (response.status() === 500) {
        const content = await page.content();
        console.log('\nERROR PAGE CONTENT:');

        // Extract key error info
        const sqlMatch = content.match(/SQLSTATE\[[\d\w]+\][^<]+/);
        if (sqlMatch) {
            console.log('\nSQL Error:', sqlMatch[0]);
        }

        const errorMessageMatch = content.match(/Error:.*?(?=<|$)/);
        if (errorMessageMatch) {
            console.log('\nError:', errorMessageMatch[0]);
        }

        // Get full error section
        const exceptionMatch = content.match(/class="exception-message"[^>]*>([^<]+)/);
        if (exceptionMatch) {
            console.log('\nException Message:', exceptionMatch[1]);
        }

        // Get stack trace line
        const traceMatch = content.match(/SuperAdminUserController\.php:\d+/);
        if (traceMatch) {
            console.log('\nError Location:', traceMatch[0]);
        }

        // Save full HTML for debugging
        const fs = require('fs');
        fs.writeFileSync('/tmp/error-page.html', content);
        console.log('\nFull error page saved to /tmp/error-page.html');
    } else if (response.status() === 200) {
        console.log('SUCCESS - Page loaded');
        await page.screenshot({ path: '/tmp/user-detail.png' });
        console.log('Screenshot saved to /tmp/user-detail.png');
    }

    await browser.close();
})();
