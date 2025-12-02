const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'ar'
    });
    const page = await context.newPage();

    // Collect console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    // Collect page errors
    const pageErrors = [];
    page.on('pageerror', err => {
        pageErrors.push(err.message);
    });

    try {
        // Set locale cookie
        await context.addCookies([{
            name: 'app_locale',
            value: 'ar',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        // Login first
        console.log('Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');

        // Click the primary login button - look for the one with primary styling
        await page.click('button.bg-indigo-600[type="submit"], form button[type="submit"]:visible');
        await page.waitForURL('**/dashboard**', { timeout: 30000 });
        console.log('Logged in successfully');

        // Navigate to meta assets page
        console.log('Navigating to Meta Assets page...');
        const startTime = Date.now();
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/4a5b6c7d-8e9f-0a1b-2c3d-4e5f6a7b8c9d/assets', {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });
        const loadTime = Date.now() - startTime;
        console.log('Page loaded in ' + loadTime + 'ms');

        // Wait for the page to fully render
        await page.waitForTimeout(2000);

        // Take screenshot of initial load
        await page.screenshot({ path: '/tmp/meta-assets-initial.png', fullPage: true });
        console.log('Screenshot saved: /tmp/meta-assets-initial.png');

        // Check if Alpine.js component initialized
        const alpineInitialized = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="metaAssetsPage"]');
            return el !== null;
        });
        console.log('Alpine.js component initialized: ' + alpineInitialized);

        // Check for loading progress bar or skeletons
        const loadingElements = await page.evaluate(() => {
            return {
                progressBar: document.querySelector('.bg-gradient-to-r') !== null,
                skeletons: document.querySelectorAll('.animate-pulse').length,
                loadingText: document.body.textContent.includes('جاري التحميل') || document.body.textContent.includes('Loading')
            };
        });
        console.log('Loading elements: ' + JSON.stringify(loadingElements));

        // Wait for AJAX calls to complete (up to 15 seconds)
        console.log('Waiting for AJAX calls...');
        await page.waitForTimeout(15000);

        // Take screenshot after AJAX loads
        await page.screenshot({ path: '/tmp/meta-assets-loaded.png', fullPage: true });
        console.log('Screenshot saved: /tmp/meta-assets-loaded.png');

        // Check page content
        const pageContent = await page.evaluate(() => {
            return {
                title: document.querySelector('h1, h2, h3')?.textContent?.trim() || 'No title',
                totalCheckboxes: document.querySelectorAll('input[type="checkbox"]').length,
                totalCards: document.querySelectorAll('.rounded-lg.border, .rounded-xl.border').length,
                hasErrorMessages: document.body.textContent.includes('خطأ') || document.body.textContent.includes('Error'),
                hasRefreshButton: document.body.textContent.includes('تحديث') || document.body.textContent.includes('Refresh'),
                bodyText: document.body.textContent.substring(0, 500)
            };
        });
        console.log('Page content: ' + JSON.stringify(pageContent, null, 2));

        // Report errors
        if (consoleErrors.length > 0) {
            console.log('\n=== CONSOLE ERRORS ===');
            consoleErrors.forEach(err => console.log(err));
        } else {
            console.log('\nNo console errors detected');
        }

        if (pageErrors.length > 0) {
            console.log('\n=== PAGE ERRORS ===');
            pageErrors.forEach(err => console.log(err));
        } else {
            console.log('No page errors detected');
        }

        // Summary
        console.log('\n=== SUMMARY ===');
        console.log('Initial page load: ' + loadTime + 'ms (target: < 2000ms)');
        console.log('Fast initial load: ' + (loadTime < 5000 ? 'YES' : 'NO'));
        console.log('Alpine initialized: ' + alpineInitialized);
        console.log('Has refresh button: ' + pageContent.hasRefreshButton);
        console.log('Total checkboxes: ' + pageContent.totalCheckboxes);
        console.log('Error messages: ' + pageContent.hasErrorMessages);

    } catch (error) {
        console.error('Test failed:', error.message);
        await page.screenshot({ path: '/tmp/meta-assets-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
