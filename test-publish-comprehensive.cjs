const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const browser = await chromium.launch({
        headless: true, // Run in headless mode (no display server)
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        recordVideo: {
            dir: 'test-results/videos/',
            size: { width: 1280, height: 720 }
        }
    });

    const page = await context.newPage();

    // Collect console messages and network requests
    const logs = {
        console: [],
        network: [],
        errors: []
    };

    page.on('console', msg => {
        const text = msg.text();
        logs.console.push({ type: msg.type(), text });
        console.log(`[BROWSER ${msg.type().toUpperCase()}]`, text);
    });

    page.on('response', async response => {
        const url = response.url();
        if (url.includes('/api/') || url.includes('/social/')) {
            try {
                const body = await response.text();
                logs.network.push({
                    url,
                    status: response.status(),
                    body: body.substring(0, 500)
                });
                console.log(`[NETWORK] ${response.status()} ${url}`);
            } catch (e) {
                // Response body already consumed
            }
        }
    });

    page.on('pageerror', error => {
        logs.errors.push(error.message);
        console.error(`[PAGE ERROR]`, error.message);
    });

    try {
        console.log('=== META PUBLISHING TEST ===\n');

        // Step 1: Login
        console.log('Step 1: Logging in...');
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
        console.log('✅ Logged in\n');

        // Step 2: Navigate to social page
        const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
        console.log('Step 2: Loading social page...');
        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/01-social-page.png', fullPage: true });
        console.log('✅ Social page loaded\n');

        // Step 3: Click "New Post" button
        console.log('Step 3: Opening create post modal...');
        const createPostButton = page.locator('button:has-text("New Post"), button:has-text("منشور جديد")').first();
        await createPostButton.click();
        await page.waitForTimeout(1000);
        await page.screenshot({ path: 'test-results/02-modal-opened.png', fullPage: true });
        console.log('✅ Create post modal opened\n');

        // Step 4: Wait for platforms to load
        console.log('Step 4: Waiting for platform accounts to load...');
        await page.waitForTimeout(2000);

        // Check if platforms are loaded
        const platformsVisible = await page.locator('[x-show="loadingPlatforms"]').isVisible().catch(() => false);
        if (platformsVisible) {
            console.log('Platforms still loading, waiting...');
            await page.waitForTimeout(3000);
        }

        // Step 5: Select account groups and profiles
        console.log('Step 5: Selecting account groups...');

        // Take screenshot to see available groups
        await page.screenshot({ path: 'test-results/03-groups-loaded.png', fullPage: true });

        // Click "Select All Groups" button
        const selectAllGroupsBtn = page.locator('button:has-text("Select All"), button:has-text("تحديد الكل")').first();
        try {
            await selectAllGroupsBtn.click({ timeout: 5000 });
            console.log('✅ All groups selected');
            await page.waitForTimeout(1000);
        } catch (e) {
            console.log('⚠️  Select All button not found, trying to select first group...');
            // Try selecting the first group checkbox
            const firstGroup = page.locator('input[type="checkbox"]').first();
            await firstGroup.check({ timeout: 5000 });
        }

        await page.screenshot({ path: 'test-results/04-groups-selected.png', fullPage: true });

        // Step 5b: Select Instagram and Facebook profiles
        console.log('Step 5b: Selecting Instagram and Facebook accounts...');

        // Wait for profiles to load
        await page.waitForTimeout(1000);

        // Find and click Instagram profile
        const instagramProfile = page.locator('label:has-text("instagram"), label:has(img) >> xpath=//following-sibling::div[contains(text(), "instagram")]').first();
        try {
            await instagramProfile.click({ timeout: 3000 });
            console.log('✅ Instagram account selected');
        } catch (e) {
            console.log('⚠️  Trying alternative Instagram selector...');
            // Try to find any profile with Instagram icon
            await page.locator('.fa-instagram').locator('xpath=ancestor::label').first().click({ timeout: 3000 }).catch(() => {
                console.log('⚠️  Could not select Instagram account');
            });
        }

        await page.waitForTimeout(500);

        // Find and click Facebook profile
        const facebookProfile = page.locator('label:has-text("facebook"), label:has(img) >> xpath=//following-sibling::div[contains(text(), "facebook")]').first();
        try {
            await facebookProfile.click({ timeout: 3000 });
            console.log('✅ Facebook account selected');
        } catch (e) {
            console.log('⚠️  Trying alternative Facebook selector...');
            // Try to find any profile with Facebook icon
            await page.locator('.fa-facebook').locator('xpath=ancestor::label').first().click({ timeout: 3000 }).catch(() => {
                console.log('⚠️  Could not select Facebook account');
            });
        }

        await page.screenshot({ path: 'test-results/05-accounts-selected.png', fullPage: true });

        // Step 6: Fill in post content
        console.log('\nStep 6: Filling post content...');
        const contentTextarea = page.locator('textarea[placeholder*="What"], textarea[placeholder*="ماذا"], textarea[x-model="newPost.content"]').first();
        await contentTextarea.fill('Test post from automated testing - ' + new Date().toISOString());
        console.log('✅ Content filled\n');

        // Step 7: Upload test image
        console.log('\nStep 7: Uploading test image...');

        // Create a simple test image if it doesn't exist
        const testImagePath = path.join(__dirname, 'test-image.jpg');
        if (!fs.existsSync(testImagePath)) {
            // Copy the error screenshot if it exists, or create a placeholder
            const errorScreenshot = path.join(__dirname, 'test-error-screenshot.png');
            if (fs.existsSync(errorScreenshot)) {
                fs.copyFileSync(errorScreenshot, testImagePath);
            } else {
                console.log('⚠️  No test image found, test will proceed without media');
            }
        }

        if (fs.existsSync(testImagePath)) {
            // Find file input
            const fileInput = page.locator('input[type="file"]').first();
            await fileInput.setInputFiles(testImagePath);
            await page.waitForTimeout(2000);
            console.log('✅ Image uploaded\n');
            await page.screenshot({ path: 'test-results/06-image-uploaded.png', fullPage: true });
        }

        // Step 8: Select "Publish Now"
        console.log('Step 8: Setting publish type to "Publish Now"...');
        const publishNowOption = page.locator('input[value="now"], label:has-text("Publish Now"), label:has-text("نشر الآن")').first();
        try {
            await publishNowOption.click({ timeout: 5000 });
            console.log('✅ Publish Now selected\n');
        } catch (e) {
            console.log('⚠️  "Publish Now" might already be selected\n');
        }

        await page.screenshot({ path: 'test-results/07-ready-to-publish.png', fullPage: true });

        // Step 9: Click Publish button
        console.log('Step 9: Clicking Publish button...');
        console.log('⏳ Waiting for API response...\n');

        const publishButton = page.locator('button:has-text("Publish"), button:has-text("نشر")').last();

        // Wait for network response
        const responsePromise = page.waitForResponse(
            response => response.url().includes('/social/posts') && response.request().method() === 'POST',
            { timeout: 60000 }
        );

        await publishButton.click();

        // Wait for response
        const response = await responsePromise;
        const responseBody = await response.json();

        console.log('=== PUBLISH API RESPONSE ===');
        console.log('Status:', response.status());
        console.log('Response:', JSON.stringify(responseBody, null, 2));
        console.log('===========================\n');

        // Take screenshot after publish
        await page.waitForTimeout(3000);
        await page.screenshot({ path: 'test-results/08-after-publish.png', fullPage: true });

        // Check response
        if (responseBody.success) {
            console.log('✅ PUBLISH SUCCESSFUL!');
            console.log('Message:', responseBody.message);
            if (responseBody.data) {
                console.log('Post Data:', responseBody.data);
            }
        } else {
            console.log('❌ PUBLISH FAILED!');
            console.log('Error:', responseBody.message || responseBody.error);
            if (responseBody.errors) {
                console.log('Validation Errors:', responseBody.errors);
            }
            if (responseBody.data) {
                console.log('Additional Data:', responseBody.data);
            }
        }

        // Step 10: Check for any notifications or errors on page
        await page.waitForTimeout(2000);
        const notifications = await page.locator('.notification, .alert, .toast, [role="alert"]').allTextContents();
        if (notifications.length > 0) {
            console.log('\nPage Notifications:');
            notifications.forEach(n => console.log(' -', n));
        }

        // Save logs to file
        fs.writeFileSync('test-results/publish-test-logs.json', JSON.stringify(logs, null, 2));
        console.log('\n✅ Test complete. Logs saved to test-results/publish-test-logs.json');

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        await page.screenshot({ path: 'test-results/error-screenshot.png', fullPage: true });

        // Save error logs
        logs.errors.push(error.message);
        fs.writeFileSync('test-results/error-logs.json', JSON.stringify(logs, null, 2));
    } finally {
        await context.close();
        await browser.close();
    }
})();
