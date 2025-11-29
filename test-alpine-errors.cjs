const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext();
    const page = await context.newPage();

    // Collect console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error' || msg.type() === 'warning') {
            consoleErrors.push({
                type: msg.type(),
                text: msg.text()
            });
        }
    });

    // Set locale cookie
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    try {
        console.log('Loading login page...');
        await page.goto('https://cmis-test.kazaaz.com/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        console.log('Logging in...');
        await page.fill('input[type="email"]', 'admin@cmis.test');
        await page.fill('input[type="password"]', 'password');

        // Find the visible submit button in the login form
        await page.locator('form button[type="submit"]').filter({ hasText: /Sign in|تسجيل الدخول/ }).first().click();

        console.log('Waiting for dashboard...');
        await page.waitForURL('**/orgs/**', { timeout: 30000 });

        // Extract org ID from URL
        const currentUrl = page.url();
        const orgMatch = currentUrl.match(/\/orgs\/([^\/]+)/);
        const orgId = orgMatch ? orgMatch[1] : null;

        if (!orgId) {
            console.error('Could not extract org ID from URL:', currentUrl);
            await browser.close();
            return;
        }

        console.log(`Using org ID: ${orgId}`);
        console.log('Loading social page...');

        // Clear console errors collected during login
        consoleErrors.length = 0;

        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        // Wait a bit for Alpine.js to initialize
        await page.waitForTimeout(3000);

        console.log('\n=== Console Errors/Warnings ===');
        if (consoleErrors.length === 0) {
            console.log('✅ No console errors or warnings found!');
        } else {
            const alpineErrors = consoleErrors.filter(e =>
                e.text.includes('is not defined') ||
                e.text.includes('Alpine')
            );

            if (alpineErrors.length === 0) {
                console.log('✅ No Alpine.js "is not defined" errors found!');
                console.log(`\nOther errors/warnings (${consoleErrors.length}):`);
                consoleErrors.forEach(e => {
                    console.log(`  [${e.type}] ${e.text}`);
                });
            } else {
                console.log(`❌ Found ${alpineErrors.length} Alpine.js errors:`);
                alpineErrors.forEach(e => {
                    console.log(`  [${e.type}] ${e.text}`);
                });

                if (consoleErrors.length > alpineErrors.length) {
                    console.log(`\nOther errors/warnings (${consoleErrors.length - alpineErrors.length}):`);
                    consoleErrors.filter(e => !alpineErrors.includes(e)).forEach(e => {
                        console.log(`  [${e.type}] ${e.text}`);
                    });
                }
            }
        }

    } catch (error) {
        console.error('Test failed:', error.message);

        // Still show any console errors we collected
        if (consoleErrors.length > 0) {
            console.log('\n=== Console Errors (before test failure) ===');
            consoleErrors.forEach(e => {
                console.log(`  [${e.type}] ${e.text}`);
            });
        }
    } finally {
        await browser.close();
    }
})();
