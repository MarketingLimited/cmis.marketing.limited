const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Collect all console messages
    const consoleMessages = {
        errors: [],
        warnings: [],
        alpineErrors: [],
        syntaxErrors: []
    };

    page.on('console', msg => {
        const text = msg.text();
        const type = msg.type();

        if (type === 'error') {
            consoleMessages.errors.push(text);
            if (text.includes('is not defined') && text.includes('Alpine')) {
                consoleMessages.alpineErrors.push(text);
            }
            if (text.includes('SyntaxError') || text.includes('Unexpected token')) {
                consoleMessages.syntaxErrors.push(text);
            }
        } else if (type === 'warning') {
            consoleMessages.warnings.push(text);
            if (text.includes('is not defined') && text.includes('Alpine')) {
                consoleMessages.alpineErrors.push(text);
            }
        }
    });

    try {
        console.log('=== CMIS Social Page - Comprehensive Test ===\n');
        console.log('Step 1: Loading login page...');

        await page.goto('https://cmis-test.kazaaz.com/login', {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });

        console.log('Step 2: Filling login credentials...');
        await page.fill('input[type="email"]', 'admin@cmis.test');
        await page.fill('input[type="password"]', 'password');

        console.log('Step 3: Submitting login form...');
        // Click the login button - try multiple selectors
        try {
            await page.click('button:has-text("Sign in"), button:has-text("تسجيل الدخول")', { timeout: 5000 });
        } catch (e) {
            // If text-based selector fails, try form submit button
            await page.locator('form button[type="submit"]').first().click();
        }

        console.log('Step 4: Waiting for redirect to dashboard...');
        await page.waitForURL('**/orgs/**', { timeout: 30000 }).catch(() => {
            console.log('Warning: Did not redirect to org page, checking current URL...');
        });

        const currentUrl = page.url();
        console.log(`Current URL: ${currentUrl}`);

        // Extract org ID
        const orgMatch = currentUrl.match(/\/orgs\/([^\/]+)/);
        const orgId = orgMatch ? orgMatch[1] : '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

        console.log(`Step 5: Loading social page for org: ${orgId}...\n`);

        // Clear console messages before loading social page
        consoleMessages.errors.length = 0;
        consoleMessages.warnings.length = 0;
        consoleMessages.alpineErrors.length = 0;
        consoleMessages.syntaxErrors.length = 0;

        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        // Wait for Alpine.js to initialize
        console.log('Step 6: Waiting for Alpine.js initialization...');
        await page.waitForTimeout(3000);

        // Take a screenshot
        await page.screenshot({ path: 'test-social-screenshot.png', fullPage: true });
        console.log('Screenshot saved: test-social-screenshot.png\n');

        // Print results
        console.log('=== TEST RESULTS ===\n');

        // Alpine.js "is not defined" errors
        if (consoleMessages.alpineErrors.length === 0) {
            console.log('✅ Alpine.js Properties: NO "is not defined" errors');
        } else {
            console.log(`❌ Alpine.js Properties: ${consoleMessages.alpineErrors.length} "is not defined" errors:`);
            consoleMessages.alpineErrors.forEach((err, i) => {
                console.log(`   ${i + 1}. ${err.substring(0, 100)}...`);
            });
        }

        // Syntax errors
        if (consoleMessages.syntaxErrors.length === 0) {
            console.log('✅ Syntax Errors: NONE');
        } else {
            console.log(`❌ Syntax Errors: ${consoleMessages.syntaxErrors.length} found:`);
            consoleMessages.syntaxErrors.forEach((err, i) => {
                console.log(`   ${i + 1}. ${err.substring(0, 100)}...`);
            });
        }

        // Other errors (excluding Tailwind CDN warning)
        const otherErrors = consoleMessages.errors.filter(e =>
            !e.includes('cdn.tailwindcss.com') &&
            !consoleMessages.alpineErrors.includes(e) &&
            !consoleMessages.syntaxErrors.includes(e)
        );

        if (otherErrors.length === 0) {
            console.log('✅ Other Errors: NONE');
        } else {
            console.log(`⚠️  Other Errors: ${otherErrors.length} found:`);
            otherErrors.forEach((err, i) => {
                console.log(`   ${i + 1}. ${err.substring(0, 100)}...`);
            });
        }

        console.log('\n=== SUMMARY ===');
        const totalIssues = consoleMessages.alpineErrors.length + consoleMessages.syntaxErrors.length + otherErrors.length;
        if (totalIssues === 0) {
            console.log('✅ ALL TESTS PASSED - No critical errors found!');
        } else {
            console.log(`❌ TESTS FAILED - ${totalIssues} issues found`);
        }

    } catch (error) {
        console.error('\n❌ Test execution failed:', error.message);

        // Still show collected errors
        if (consoleMessages.errors.length > 0 || consoleMessages.warnings.length > 0) {
            console.log('\n=== Console Messages (before failure) ===');
            console.log(`Errors: ${consoleMessages.errors.length}`);
            console.log(`Warnings: ${consoleMessages.warnings.length}`);
            console.log(`Alpine.js errors: ${consoleMessages.alpineErrors.length}`);
            console.log(`Syntax errors: ${consoleMessages.syntaxErrors.length}`);
        }
    } finally {
        await browser.close();
    }
})();
