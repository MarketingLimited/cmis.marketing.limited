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
        console.log('🔍 Testing Alpine.js errors on authenticated social page...\n');

        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Logged in\n');

        // Step 2: Navigate to social page
        console.log('📱 Step 2: Loading social page...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log('✅ Social page loaded\n');

        // Step 3: Wait for Alpine.js to initialize
        console.log('⏳ Step 3: Waiting for Alpine.js initialization...');
        await page.waitForTimeout(3000);
        console.log('✅ Alpine.js initialized\n');

        // Step 4: Check results
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
            const relevantErrors = consoleErrors.filter(e =>
                !e.text.includes('cdn.tailwindcss.com') &&
                !e.text.includes('404')
            );

            if (relevantErrors.length > 0) {
                relevantErrors.forEach((e, index) => {
                    console.log(`${index + 1}. [${e.type}] ${e.text}`);
                });
            } else {
                console.log('(Only non-critical warnings found - Tailwind CDN, 404s)');
            }
        }

    } catch (error) {
        console.error('❌ Test error:', error.message);
    } finally {
        await browser.close();
    }
})();
