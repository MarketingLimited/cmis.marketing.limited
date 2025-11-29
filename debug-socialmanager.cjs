const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    try {
        console.log('Loading page and authenticating...\n');

        await page.goto('https://cmis-test.kazaaz.com/login', {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });

        await page.fill('input[type="email"]', 'admin@cmis.test');
        await page.fill('input[type="password"]', 'password');

        try {
            await page.click('button:has-text("Sign in"), button:has-text("تسجيل الدخول")', { timeout: 5000 });
        } catch (e) {
            await page.locator('form button[type="submit"]').first().click();
        }

        await page.waitForTimeout(3000);

        const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

        console.log(`Loading social page for org: ${orgId}...\n`);

        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.waitForTimeout(2000);

        // Extract the socialManager function source from the page
        const socialManagerSource = await page.evaluate(() => {
            if (typeof socialManager === 'function') {
                return socialManager.toString();
            }
            return 'socialManager function not found';
        });

        // Save to file
        fs.writeFileSync('/tmp/socialmanager-function.js', socialManagerSource);
        console.log('Social Manager function source saved to: /tmp/socialmanager-function.js');
        console.log(`Source length: ${socialManagerSource.length} characters\n`);

        // Check for specific properties
        const hasShowHashtagManager = socialManagerSource.includes('showHashtagManager');
        const hasHashtagSets = socialManagerSource.includes('hashtagSets');
        const hasDaysOfWeek = socialManagerSource.includes('daysOfWeek');

        console.log('Property Check:');
        console.log(`- showHashtagManager: ${hasShowHashtagManager ? '✅ FOUND' : '❌ NOT FOUND'}`);
        console.log(`- hashtagSets: ${hasHashtagSets ? '✅ FOUND' : '❌ NOT FOUND'}`);
        console.log(`- daysOfWeek: ${hasDaysOfWeek ? '✅ FOUND' : '❌ NOT FOUND'}`);

        // Count properties in return object
        const returnMatch = socialManagerSource.match(/return\s*{([\s\S]*?)};?\s*}$/);
        if (returnMatch) {
            const returnBody = returnMatch[1];
            const propertyMatches = returnBody.match(/^\s*\w+:/gm);
            console.log(`\nTotal properties in return object: ${propertyMatches ? propertyMatches.length : 0}`);
        }

    } catch (error) {
        console.error('Error:', error.message);
    } finally {
        await browser.close();
    }
})();
