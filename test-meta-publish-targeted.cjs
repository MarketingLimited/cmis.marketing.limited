const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        bypassCSP: true,
        // Disable cache to ensure fresh JavaScript files
        javaScriptEnabled: true
    });

    const page = await context.newPage();

    const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    const testResultsDir = path.join(__dirname, 'test-results');

    if (!fs.existsSync(testResultsDir)) {
        fs.mkdirSync(testResultsDir, { recursive: true });
    }

    // Capture all network requests
    const networkLogs = [];
    page.on('request', request => {
        if (request.url().includes('/social/') || request.url().includes('/media/')) {
            networkLogs.push({
                type: 'request',
                url: request.url(),
                method: request.method(),
                postData: request.postData()
            });
        }
    });

    page.on('response', async response => {
        if (response.url().includes('/social/') || response.url().includes('/media/')) {
            try {
                const body = await response.text();
                networkLogs.push({
                    type: 'response',
                    url: response.url(),
                    status: response.status(),
                    body: body.substring(0, 1000) // First 1000 chars
                });
            } catch (e) {
                // Ignore errors reading response body
            }
        }
    });

    // Capture console logs
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('[Upload]') || text.includes('[Publishing]') || text.includes('media')) {
            console.log('[BROWSER LOG]', text);
        }
    });

    try {
        console.log('=== META PUBLISHING TEST (Community & Support Group) ===\n');

        // Step 1: Login
        console.log('Step 1: Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForTimeout(1000);
        await page.fill('input[type="email"]', 'admin@cmis.test');
        await page.fill('input[type="password"]', 'password');
        await page.locator('form button[type="submit"]:has-text("Login"), form button[type="submit"]:has-text("تسجيل الدخول")').click();
        await page.waitForTimeout(5000); // Wait for redirect
        console.log('✅ Logged in\n');

        // Step 2: Navigate to social page
        console.log('Step 2: Loading social page...');
        await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/social`);
        await page.waitForTimeout(3000);
        console.log('✅ Social page loaded\n');

        // Step 3: Open publish modal
        console.log('Step 3: Opening publish modal...');
        const newPostBtn = page.locator('button:has-text("New Post"), button:has-text("منشور جديد")').first();
        await newPostBtn.click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/targeted-01-modal-opened.png', fullPage: true });
        console.log('✅ Modal opened\n');

        // Step 4: Select "Community & Support" group specifically
        console.log('Step 4: Selecting "Community & Support" account group...');
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/targeted-02-groups-available.png', fullPage: true });

        // Try to find and click the Community & Support group
        const communityGroupCheckbox = page.locator('label:has-text("Community & Support"), label:has-text("المجتمع والدعم")').locator('input[type="checkbox"]').first();
        try {
            await communityGroupCheckbox.check({ timeout: 5000 });
            console.log('✅ "Community & Support" group selected');
        } catch (e) {
            console.log('⚠️  Could not find "Community & Support" group, selecting first available group...');
            const firstGroupCheckbox = page.locator('input[type="checkbox"][x-model*="selectedGroups"]').first();
            await firstGroupCheckbox.check({ timeout: 5000 });
        }

        await page.waitForTimeout(1000);
        await page.screenshot({ path: 'test-results/targeted-03-group-selected.png', fullPage: true });

        // Step 5: Select all accounts in the group
        console.log('\nStep 5: Selecting all accounts in the group...');
        await page.waitForTimeout(1000);

        // Click "Select All" button if available
        const selectAllBtn = page.locator('button:has-text("Select All"), button:has-text("تحديد الكل")').first();
        try {
            await selectAllBtn.click({ timeout: 3000 });
            console.log('✅ All accounts selected');
        } catch (e) {
            console.log('⚠️  "Select All" not found, manually selecting accounts...');

            // Manually select Instagram and Facebook accounts
            const instagramCheckbox = page.locator('input[type="checkbox"][value*="instagram"]').first();
            const facebookCheckbox = page.locator('input[type="checkbox"][value*="facebook"]').first();

            try {
                await instagramCheckbox.check({ timeout: 2000 });
                console.log('✅ Instagram account selected');
            } catch (e) {
                console.log('⚠️  Instagram account not found');
            }

            try {
                await facebookCheckbox.check({ timeout: 2000 });
                console.log('✅ Facebook account selected');
            } catch (e) {
                console.log('⚠️  Facebook account not found');
            }
        }

        await page.screenshot({ path: 'test-results/targeted-04-accounts-selected.png', fullPage: true });

        // Step 6: Fill post content
        console.log('\nStep 6: Filling post content...');
        const contentTextarea = page.locator('textarea[placeholder*="What"], textarea[placeholder*="ماذا"], textarea[x-model="newPost.content"]').first();
        const testContent = 'Test post from Community & Support group - ' + new Date().toISOString();
        await contentTextarea.fill(testContent);
        console.log('✅ Content filled');

        // Step 7: Upload test image
        console.log('\nStep 7: Uploading test image...');
        const testImagePath = path.join(__dirname, 'test-image.jpg');
        if (!fs.existsSync(testImagePath)) {
            const errorScreenshot = path.join(__dirname, 'test-error-screenshot.png');
            if (fs.existsSync(errorScreenshot)) {
                fs.copyFileSync(errorScreenshot, testImagePath);
            }
        }

        if (fs.existsSync(testImagePath)) {
            const fileInput = page.locator('input[type="file"]').first();
            await fileInput.setInputFiles(testImagePath);
            await page.waitForTimeout(3000); // Wait for upload to complete
            console.log('✅ Image uploaded');
            await page.screenshot({ path: 'test-results/targeted-05-image-uploaded.png', fullPage: true });
        } else {
            console.log('⚠️  No test image available');
        }

        // Step 8: Select "Publish Now"
        console.log('\nStep 8: Setting publish type to "Publish Now"...');
        const publishNowRadio = page.locator('input[value="now"], label:has-text("Publish Now"), label:has-text("نشر الآن")').first();
        try {
            await publishNowRadio.click({ timeout: 3000 });
            console.log('✅ Publish Now selected');
        } catch (e) {
            console.log('⚠️  "Publish Now" might already be selected');
        }

        await page.screenshot({ path: 'test-results/targeted-06-ready-to-publish.png', fullPage: true });

        // Step 9: Click Publish and capture response
        console.log('\nStep 9: Publishing...');
        const publishBtn = page.locator('button:has-text("Publish"), button:has-text("نشر")').last();

        // Wait for the publish API call
        const responsePromise = page.waitForResponse(
            response => response.url().includes('publish-modal/create'),
            { timeout: 60000 }
        );

        await publishBtn.click();

        try {
            const response = await responsePromise;
            const responseBody = await response.json();

            console.log('\n=== PUBLISH API RESPONSE ===');
            console.log('Status:', response.status());
            console.log('Response:', JSON.stringify(responseBody, null, 2));
            console.log('===========================\n');

            // Save response
            fs.writeFileSync(
                'test-results/targeted-publish-response.json',
                JSON.stringify(responseBody, null, 2)
            );

            // Check results
            if (responseBody.success) {
                console.log('✅ API call successful');

                if (responseBody.data) {
                    console.log('  Success count:', responseBody.data.success_count);
                    console.log('  Failed count:', responseBody.data.failed_count);

                    if (responseBody.data.posts) {
                        console.log('\n  Post Details:');
                        responseBody.data.posts.forEach((post, idx) => {
                            console.log(`  ${idx + 1}. ${post.platform}: ${post.status}`);
                            if (post.error_message) {
                                console.log(`     Error: ${post.error_message}`);
                            }
                            if (post.permalink) {
                                console.log(`     Link: ${post.permalink}`);
                            }
                        });
                    }
                }
            } else {
                console.log('❌ API call failed:', responseBody.message);
            }

        } catch (e) {
            console.log('❌ Failed to get publish response:', e.message);
        }

        await page.screenshot({ path: 'test-results/targeted-07-after-publish.png', fullPage: true });

        // Save network logs
        fs.writeFileSync(
            'test-results/targeted-network-logs.json',
            JSON.stringify(networkLogs, null, 2)
        );

        console.log('\n✅ Test completed');
        console.log('📁 Results saved to test-results/');

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        await page.screenshot({ path: 'test-results/targeted-error.png', fullPage: true });

        // Save network logs even on error
        fs.writeFileSync(
            'test-results/targeted-network-logs.json',
            JSON.stringify(networkLogs, null, 2)
        );
    } finally {
        await context.close();
        await browser.close();
    }
})();
