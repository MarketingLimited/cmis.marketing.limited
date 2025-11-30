#!/usr/bin/env node

/**
 * Test publish modal performance with detailed timing logs
 */

const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Listen to console logs to capture [PERF] messages
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('[PERF]') || text.includes('[AutoUpload]') || text.includes('[Publishing]')) {
            console.log(`[BROWSER CONSOLE] ${text}`);
        }
    });

    try {
        console.log('1. Navigating to login page...');
        await page.goto('https://cmis-test.kazaaz.com/login');

        // Set locale to English
        await page.evaluate(() => {
            document.cookie = 'app_locale=en; path=/; domain=cmis-test.kazaaz.com';
        });

        console.log('2. Logging in...');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.locator('button[type="submit"]:visible').first().click();
        await page.waitForLoadState('networkidle');

        console.log('3. Navigating to Social Posts page...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/posts');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        console.log('4. Clicking "Create New Post" button...');
        const createButton = await page.locator('button:has-text("Create New Post")').first();
        await createButton.click();
        await page.waitForTimeout(2000);

        console.log('5. Waiting for modal to open...');
        await page.waitForSelector('[x-data*="publishModal"]', { timeout: 5000 });
        await page.waitForTimeout(1000);

        console.log('6. Selecting a profile...');
        const profileCheckbox = await page.locator('input[type="checkbox"][x-model*="selectedProfiles"]').first();
        await profileCheckbox.click();
        await page.waitForTimeout(500);

        console.log('7. Entering post text...');
        const textarea = await page.locator('textarea[x-model="content.global.text"]').first();
        await textarea.fill('Performance test post - ' + new Date().toISOString());
        await page.waitForTimeout(500);

        console.log('8. Clicking "Publish Now" button...');
        console.log('===== STARTING PERFORMANCE MEASUREMENT =====');
        const publishButton = await page.locator('button:has-text("Publish Now")').first();
        await publishButton.click();

        // Wait for publish to complete (modal should close)
        console.log('9. Waiting for modal to close...');
        await page.waitForSelector('[x-data*="publishModal"]', { state: 'hidden', timeout: 30000 });

        console.log('===== PERFORMANCE MEASUREMENT COMPLETE =====');
        console.log('\n10. SUCCESS: Modal closed after publishing');

        // Wait a bit to ensure all logs are captured
        await page.waitForTimeout(2000);

        console.log('\n11. Check Laravel logs for backend performance:');
        console.log('    tail -100 storage/logs/laravel.log | grep "\\[PERF\\]"');

    } catch (error) {
        console.error('ERROR:', error.message);
    } finally {
        console.log('\nClosing browser...');
        await browser.close();
    }
})();
