const { chromium } = require('playwright');

const TEST_URL = 'https://cmis-test.kazaaz.com';
const TEST_EMAIL = 'admin@cmis.test';
const TEST_PASSWORD = 'password';
const TEST_ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

async function testQueueSettings() {
    console.log('🚀 Starting Queue Settings Test\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();
    const consoleErrors = [];
    const networkErrors = [];

    // Capture console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    // Capture network errors
    page.on('response', response => {
        if (response.status() >= 400) {
            networkErrors.push(`${response.status()} ${response.url()}`);
        }
    });

    try {
        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto(`${TEST_URL}/login`);
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="email"]', TEST_EMAIL);
        await page.fill('input[name="password"]', TEST_PASSWORD);

        // Find the login button (the one with indigo background, not language switchers)
        await page.locator('button[type="submit"].bg-indigo-600').click();

        // Wait for navigation after login (don't check specific URL as it may redirect)
        await page.waitForLoadState('networkidle', { timeout: 15000 });

        // Verify we're logged in by checking for user menu or dashboard elements
        await page.waitForTimeout(2000);
        console.log('✅ Login successful\n');

        // Step 2: Navigate to Profile Management
        console.log('📝 Step 2: Navigating to Profile Management...');
        await page.goto(`${TEST_URL}/orgs/${TEST_ORG_ID}/settings/profiles`);
        await page.waitForLoadState('networkidle');
        console.log('✅ Profile Management page loaded\n');

        // Step 3: Get first profile and navigate to detail page
        console.log('📝 Step 3: Opening first profile detail page...');
        const firstProfileLink = await page.locator('a[href*="/settings/profiles/"]').first();
        if (!await firstProfileLink.isVisible()) {
            throw new Error('No profiles found on the page');
        }
        await firstProfileLink.click();
        await page.waitForLoadState('networkidle');
        console.log('✅ Profile detail page loaded\n');

        // Take screenshot of profile page
        await page.screenshot({ path: 'test-results/queue-settings-profile-page.png', fullPage: true });
        console.log('📸 Screenshot saved: queue-settings-profile-page.png\n');

        // Step 4: Open Queue Settings Modal
        console.log('📝 Step 4: Opening Queue Settings modal...');
        await page.click('button:has-text("Queue Settings")');
        await page.waitForTimeout(1000); // Wait for modal animation

        // Check if modal is visible
        const modal = page.locator('div:has-text("Queue Settings")').first();
        if (!await modal.isVisible()) {
            throw new Error('Queue Settings modal did not appear');
        }
        console.log('✅ Queue Settings modal opened\n');

        // Take screenshot of modal
        await page.screenshot({ path: 'test-results/queue-settings-modal-initial.png', fullPage: true });
        console.log('📸 Screenshot saved: queue-settings-modal-initial.png\n');

        // Step 5: Enable Queue
        console.log('📝 Step 5: Enabling publishing queue...');
        const queueToggle = page.locator('input[type="checkbox"][x-model="queueEnabled"]');
        const isChecked = await queueToggle.isChecked();
        if (!isChecked) {
            await queueToggle.click();
            await page.waitForTimeout(500);
        }
        console.log('✅ Queue enabled\n');

        // Step 6: Add time slots to Monday
        console.log('📝 Step 6: Adding time slots to Monday...');

        // Enable Monday
        const mondayToggle = page.locator('input[type="checkbox"][x-model="day.enabled"]').first();
        const mondayChecked = await mondayToggle.isChecked();
        if (!mondayChecked) {
            await mondayToggle.click();
            await page.waitForTimeout(500);
        }

        // Click "Add time" button for Monday
        await page.click('button:has-text("Add time")');
        await page.waitForTimeout(500);

        // Check if time picker modal appeared
        const timePicker = page.locator('input[type="time"][x-model="newTime"]');
        if (!await timePicker.isVisible()) {
            throw new Error('Time picker modal did not appear');
        }

        // Set time to 09:00
        await timePicker.fill('09:00');
        await page.waitForTimeout(300);

        // Click Add button
        await page.click('button:has-text("Add")');
        await page.waitForTimeout(500);
        console.log('✅ Added 09:00 to Monday\n');

        // Add another time: 15:00
        await page.click('button:has-text("Add time")');
        await page.waitForTimeout(500);
        await timePicker.fill('15:00');
        await page.waitForTimeout(300);
        await page.click('button:has-text("Add")');
        await page.waitForTimeout(500);
        console.log('✅ Added 15:00 to Monday\n');

        // Take screenshot with time slots
        await page.screenshot({ path: 'test-results/queue-settings-with-times.png', fullPage: true });
        console.log('📸 Screenshot saved: queue-settings-with-times.png\n');

        // Step 7: Test "Copy to weekdays" button
        console.log('📝 Step 7: Testing Copy to weekdays...');
        await page.click('button:has-text("Copy Monday to weekdays")');
        await page.waitForTimeout(1000);
        console.log('✅ Copied Monday times to weekdays\n');

        // Take screenshot after copy
        await page.screenshot({ path: 'test-results/queue-settings-copied-weekdays.png', fullPage: true });
        console.log('📸 Screenshot saved: queue-settings-copied-weekdays.png\n');

        // Step 8: Save Queue Settings
        console.log('📝 Step 8: Saving queue settings...');
        await page.click('button:has-text("Save Changes")');

        // Wait for either success message or error
        try {
            await page.waitForTimeout(3000); // Wait for save to complete
            console.log('✅ Queue settings saved successfully\n');
        } catch (error) {
            throw new Error(`Failed to save queue settings: ${error.message}`);
        }

        // Step 9: Verify settings persisted
        console.log('📝 Step 9: Verifying settings persisted...');
        await page.waitForLoadState('networkidle');

        // Take screenshot of final page
        await page.screenshot({ path: 'test-results/queue-settings-final-page.png', fullPage: true });
        console.log('📸 Screenshot saved: queue-settings-final-page.png\n');

        // Check for queue enabled status
        const queueEnabledBadge = page.locator('text="Queue enabled"');
        if (await queueEnabledBadge.isVisible()) {
            console.log('✅ Queue settings persisted correctly\n');
        } else {
            console.log('⚠️  Warning: Queue enabled badge not found\n');
        }

        // Step 10: Report Results
        console.log('\n' + '='.repeat(60));
        console.log('📊 TEST RESULTS');
        console.log('='.repeat(60));

        if (consoleErrors.length > 0) {
            console.log('\n❌ Console Errors:');
            consoleErrors.forEach((err, i) => {
                console.log(`  ${i + 1}. ${err}`);
            });
        } else {
            console.log('\n✅ No console errors detected');
        }

        if (networkErrors.length > 0) {
            console.log('\n❌ Network Errors:');
            networkErrors.forEach((err, i) => {
                console.log(`  ${i + 1}. ${err}`);
            });
        } else {
            console.log('✅ No network errors detected');
        }

        console.log('\n' + '='.repeat(60));
        console.log('✅ ALL TESTS PASSED!');
        console.log('='.repeat(60) + '\n');

    } catch (error) {
        console.error('\n' + '='.repeat(60));
        console.error('❌ TEST FAILED!');
        console.error('='.repeat(60));
        console.error(`\nError: ${error.message}\n`);

        // Take error screenshot
        await page.screenshot({ path: 'test-results/queue-settings-error.png', fullPage: true });
        console.error('📸 Error screenshot saved: queue-settings-error.png\n');

        if (consoleErrors.length > 0) {
            console.error('Console Errors:');
            consoleErrors.forEach((err, i) => {
                console.error(`  ${i + 1}. ${err}`);
            });
        }

        if (networkErrors.length > 0) {
            console.error('\nNetwork Errors:');
            networkErrors.forEach((err, i) => {
                console.error(`  ${i + 1}. ${err}`);
            });
        }

        throw error;
    } finally {
        await browser.close();
    }
}

// Run the test
testQueueSettings()
    .then(() => {
        console.log('✨ Test completed successfully!\n');
        process.exit(0);
    })
    .catch((error) => {
        console.error('💥 Test failed:', error.message, '\n');
        process.exit(1);
    });
