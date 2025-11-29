const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({
        headless: true
    });

    const page = await browser.newPage();

    try {
        console.log('=== SIMPLE META PUBLISHING TEST ===\n');

        const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

        // Step 1: Login
        console.log('Step 1: Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('form button[type="submit"]:has-text("Login"), form button[type="submit"]:has-text("تسجيل الدخول")');
        await page.waitForURL(/dashboard|social/, { timeout: 10000 });
        console.log('✅ Logged in\n');

        // Step 2: Navigate to social page
        console.log('Step 2: Navigating to social page...');
        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`);
        await page.waitForTimeout(3000);
        console.log('✅ Social page loaded\n');

        // Step 3: Open publish modal
        console.log('Step 3: Opening publish modal...');
        await page.click('button:has-text("Create Post"), button:has-text("إنشاء منشور")');
        await page.waitForTimeout(2000);
        console.log('✅ Publish modal opened\n');

        // Step 4: Select INDIVIDUAL profiles (not groups)
        console.log('Step 4: Selecting individual profiles...');

        // Wait for profiles to load
        await page.waitForTimeout(2000);

        // Find and click individual profile checkboxes
        // The checkboxes should have x-model that includes "selectedProfiles"
        const profileCheckboxes = page.locator('input[type="checkbox"]').filter({
            hasText: /@|facebook|instagram/i
        });

        const count = await profileCheckboxes.count();
        console.log(`Found ${count} potential profile checkboxes`);

        // Click the first 2 profile checkboxes
        if (count >= 2) {
            await profileCheckboxes.nth(0).click();
            console.log('✅ Selected first profile');
            await page.waitForTimeout(500);

            await profileCheckboxes.nth(1).click();
            console.log('✅ Selected second profile');
            await page.waitForTimeout(500);
        }

        // Step 5: Fill content
        console.log('\nStep 5: Filling post content...');
        const textarea = page.locator('textarea').first();
        await textarea.fill('TEST: Publishing with corrected media upload - ' + new Date().toISOString());
        console.log('✅ Content filled\n');

        // Step 6: Upload image
        console.log('Step 6: Uploading test image...');
        const testImagePath = path.join(__dirname, 'test-image.jpg');
        if (fs.existsSync(testImagePath)) {
            const fileInput = page.locator('input[type="file"]').first();
            await fileInput.setInputFiles(testImagePath);
            await page.waitForTimeout(3000);
            console.log('✅ Image uploaded\n');
        }

        // Step 7: Check if publish button is enabled
        console.log('Step 7: Checking publish button state...');
        const publishBtn = page.locator('button:has-text("Publish"), button:has-text("نشر")').last();
        const isDisabled = await publishBtn.getAttribute('disabled');
        console.log(`Publish button disabled: ${isDisabled !== null}\n`);

        if (isDisabled) {
            console.log('❌ Publish button is still disabled. Taking screenshot...');
            await page.screenshot({ path: 'test-results/debug-button-disabled.png', fullPage: true });
        } else {
            console.log('Step 8: Publishing...');

            // Listen for console logs
            page.on('console', msg => {
                const text = msg.text();
                if (text.includes('[Upload]') || text.includes('[Publishing]')) {
                    console.log(`[BROWSER LOG] ${text}`);
                }
            });

            // Click publish
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/social/publish-modal/create'),
                { timeout: 60000 }
            );

            await publishBtn.click();
            console.log('⏳ Waiting for response...\n');

            const response = await responsePromise;
            const responseBody = await response.json();

            console.log('=== API RESPONSE ===');
            console.log('Status:', response.status());
            console.log('Body:', JSON.stringify(responseBody, null, 2));

            if (response.status() === 201 || response.ok) {
                console.log('\n✅ TEST PASSED!');
            } else {
                console.log('\n❌ TEST FAILED: Unexpected response status');
            }
        }

        // Wait before closing
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        await page.screenshot({ path: 'test-results/error-screenshot.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
