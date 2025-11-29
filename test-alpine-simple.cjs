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
    const alpineErrors = [];

    page.on('console', msg => {
        const text = msg.text();
        const type = msg.type();

        if (type === 'error' || type === 'warning') {
            consoleErrors.push({ type, text });

            // Check for Alpine.js "is not defined" errors
            if (text.includes('is not defined')) {
                alpineErrors.push(text);
            }
        }
    });

    try {
        console.log('Testing Alpine.js errors on social page...\n');

        // Just load the page to check for JavaScript errors
        // (The page may redirect to login, but we'll catch console errors before that)
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social', {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });

        // Wait a bit for Alpine.js to try to initialize
        await page.waitForTimeout(3000);

        console.log('=== TEST RESULTS ===\n');

        if (alpineErrors.length === 0) {
            console.log('✅ SUCCESS: No Alpine.js "is not defined" errors found!\n');
        } else {
            console.log(`❌ FAILED: Found ${alpineErrors.length} Alpine.js "is not defined" errors:\n`);
            alpineErrors.forEach((error, index) => {
                console.log(`${index + 1}. ${error}`);
            });
            console.log();
        }

        if (consoleErrors.length > 0) {
            console.log('All console errors/warnings:');
            consoleErrors.forEach((e, index) => {
                console.log(`${index + 1}. [${e.type}] ${e.text}`);
            });
        }

    } catch (error) {
        console.error('Test error:', error.message);
    } finally {
        await browser.close();
    }
})();
