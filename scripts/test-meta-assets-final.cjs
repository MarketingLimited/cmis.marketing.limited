const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'en'
    });
    const page = await context.newPage();

    try {
        await context.addCookies([{
            name: 'app_locale',
            value: 'en',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        // Login
        console.log('🔐 Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('button.bg-indigo-600[type="submit"], form button[type="submit"]:visible');
        await page.waitForURL('**/dashboard**', { timeout: 30000 });
        console.log('✓ Logged in');

        // Navigate to meta assets page
        console.log('\n📄 Loading Meta Assets page...');
        const startTime = Date.now();

        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/4a5b6c7d-8e9f-0a1b-2c3d-4e5f6a7b8c9d/assets', {
            waitUntil: 'load',
            timeout: 60000
        });

        // Wait for AJAX loading to complete
        console.log('⏳ Waiting for assets to load...');

        // Monitor AJAX progress by watching for loading indicators to disappear
        const loadingSelectors = [
            '[x-show="loading"]',
            '.animate-spin',
            '[x-show="loadingPages"]',
            '[x-show="loadingInstagram"]',
            '[x-show="loadingThreads"]',
            '[x-show="loadingAdAccounts"]',
            '[x-show="loadingPixels"]',
            '[x-show="loadingCatalogs"]',
            '[x-show="loadingWhatsApp"]'
        ];

        // Wait for all loading indicators to be hidden (max 30 seconds)
        let loaded = false;
        let attempts = 0;
        const maxAttempts = 60; // 30 seconds (500ms per check)

        while (!loaded && attempts < maxAttempts) {
            await page.waitForTimeout(500);
            attempts++;

            // Check if any loading indicator is visible
            let anyLoading = false;
            for (const selector of loadingSelectors) {
                try {
                    const isVisible = await page.isVisible(selector);
                    if (isVisible) {
                        anyLoading = true;
                        break;
                    }
                } catch (e) {
                    // Selector not found, continue
                }
            }

            if (!anyLoading) {
                loaded = true;
            }

            // Progress indicator
            if (attempts % 4 === 0) {
                process.stdout.write('.');
            }
        }
        console.log('');

        const endTime = Date.now();
        const duration = (endTime - startTime) / 1000;

        console.log('\n📊 RESULTS:');
        console.log(`   Total load time: ${duration.toFixed(2)} seconds`);

        // Check for specific sections
        const sections = [
            { name: 'Pages', selector: '[x-data*="metaPages"]' },
            { name: 'Instagram', selector: '[x-data*="metaInstagram"]' },
            { name: 'Threads', selector: '[x-data*="metaThreads"]' },
            { name: 'Ad Accounts', selector: '[x-data*="metaAdAccounts"]' },
            { name: 'Pixels', selector: '[x-data*="metaPixels"]' },
            { name: 'Catalogs', selector: '[x-data*="metaCatalogs"]' },
            { name: 'WhatsApp', selector: '[x-data*="metaWhatsApp"]' }
        ];

        console.log('\n   Section Status:');
        for (const section of sections) {
            try {
                const exists = await page.locator(section.selector).count() > 0;
                console.log(`   - ${section.name}: ${exists ? '✓' : '✗'}`);
            } catch (e) {
                console.log(`   - ${section.name}: ✗ (error)`);
            }
        }

        // Performance assessment
        console.log('\n📈 ASSESSMENT:');
        if (duration < 10) {
            console.log('   ✅ EXCELLENT - Loading completed quickly!');
        } else if (duration < 20) {
            console.log('   ⚠️  ACCEPTABLE - Could be faster');
        } else {
            console.log('   ❌ SLOW - Fix may not be working');
        }

        // Take screenshot
        await page.screenshot({ path: '/tmp/meta-assets-loaded.png', fullPage: true });
        console.log('\n   Screenshot saved to /tmp/meta-assets-loaded.png');

    } catch (error) {
        console.error('❌ Error:', error.message);
        await page.screenshot({ path: '/tmp/meta-assets-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
