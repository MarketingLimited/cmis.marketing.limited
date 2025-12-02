const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'ar'
    });
    const page = await context.newPage();

    // Capture console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        await context.addCookies([{
            name: 'app_locale',
            value: 'ar',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        // Login
        console.log('Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('button.bg-indigo-600[type="submit"], form button[type="submit"]:visible');
        await page.waitForURL('**/dashboard**', { timeout: 30000 });
        console.log('✓ Logged in');

        // Navigate to meta assets page
        console.log('Loading Meta Assets page...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/4a5b6c7d-8e9f-0a1b-2c3d-4e5f6a7b8c9d/assets', {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });

        // Wait for assets to load
        console.log('Waiting for assets to load...');
        await page.waitForTimeout(15000);

        // Take initial screenshot
        await page.screenshot({ path: '/tmp/meta-assets-search-initial.png', fullPage: true });
        console.log('✓ Initial screenshot saved');

        // Check for search inputs
        const searchInputs = await page.evaluate(() => {
            const inputs = document.querySelectorAll('input[type="text"][x-model*="Search"]');
            return Array.from(inputs).map(input => ({
                model: input.getAttribute('x-model'),
                placeholder: input.getAttribute('placeholder')
            }));
        });

        console.log('\n📋 Search inputs found:');
        searchInputs.forEach(input => {
            console.log(`  - ${input.model}: "${input.placeholder}"`);
        });

        // Check for all 7 search inputs (2 existing + 5 new)
        const expectedSearches = [
            'pagesSearch',
            'instagramSearch',
            'threadsSearch',
            'adAccountsSearch',
            'pixelsSearch',
            'catalogsSearch',
            'whatsappSearch'
        ];

        const foundModels = searchInputs.map(s => s.model);
        const missingSearches = expectedSearches.filter(s => !foundModels.includes(s));

        if (missingSearches.length === 0) {
            console.log('\n✓ All 7 search inputs are present');
        } else {
            console.log('\n✗ Missing search inputs:', missingSearches);
        }

        // Test search functionality on Ad Accounts section
        console.log('\n🔍 Testing search functionality...');

        // Find ad accounts search input
        const adAccountsInput = await page.$('input[x-model="adAccountsSearch"]');
        if (adAccountsInput) {
            // Count items before search
            const beforeCount = await page.evaluate(() => {
                const section = document.querySelector('[x-show="filteredAdAccounts.length > 0"]');
                if (section) {
                    return document.querySelectorAll('[x-for*="filteredAdAccounts"] .rounded-lg, [x-for="account in filteredAdAccounts"] > div').length;
                }
                return -1;
            });
            console.log(`  Before search: ${beforeCount >= 0 ? beforeCount + ' items' : 'could not count'}`);

            // Type a search term
            await adAccountsInput.fill('test');
            await page.waitForTimeout(500); // Wait for debounce

            // Screenshot after search
            await page.screenshot({ path: '/tmp/meta-assets-search-filtered.png', fullPage: true });
            console.log('  ✓ Search filtering screenshot saved');
        }

        // Check console errors
        if (consoleErrors.length > 0) {
            console.log('\n⚠️ Console errors found:');
            consoleErrors.forEach(err => console.log(`  - ${err}`));
        } else {
            console.log('\n✓ No JavaScript console errors');
        }

        console.log('\n✅ Search functionality test completed');
        console.log('Screenshots saved:');
        console.log('  - /tmp/meta-assets-search-initial.png');
        console.log('  - /tmp/meta-assets-search-filtered.png');

    } catch (error) {
        console.error('❌ Error:', error.message);
        await page.screenshot({ path: '/tmp/meta-assets-search-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
