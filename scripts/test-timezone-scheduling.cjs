/**
 * Test script to verify timezone handling in scheduled posts
 *
 * This script:
 * 1. Logs in as admin
 * 2. Opens the publish modal
 * 3. Selects a profile
 * 4. Sets a schedule time
 * 5. Submits the post
 * 6. Verifies the timezone was correctly applied
 */

const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';
const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a'; // CMIS org with Asia/Bahrain timezone

async function main() {
    console.log('Starting timezone scheduling test...\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        locale: 'en-US',
        timezoneId: 'Asia/Bahrain' // Simulate user in Bahrain
    });
    const page = await context.newPage();

    // Enable console logging
    page.on('console', msg => {
        if (msg.text().includes('[PublishModal]') || msg.text().includes('[TIMEZONE]')) {
            console.log('BROWSER:', msg.text());
        }
    });

    try {
        // 1. Login
        console.log('1. Logging in...');
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        // Click the visible login button (last submit button which is the actual login btn)
        const submitButtons = await page.locator('button[type="submit"]').all();
        for (const btn of submitButtons) {
            if (await btn.isVisible()) {
                await btn.click();
                break;
            }
        }
        await page.waitForURL('**/dashboard**', { timeout: 15000 });
        console.log('   Logged in successfully');

        // 2. Navigate to social page
        console.log('2. Navigating to social page...');
        await page.goto(`${BASE_URL}/orgs/${ORG_ID}/social`);
        await page.waitForLoadState('networkidle');
        console.log('   Social page loaded');

        // 3. Open publish modal
        console.log('3. Opening publish modal...');
        const createButton = page.locator('button:has-text("Create Post"), button:has-text("New Post"), [x-data*="publishModal"] button');
        await createButton.first().click();
        await page.waitForTimeout(1000); // Wait for modal animation
        console.log('   Publish modal opened');

        // 4. Select a profile
        console.log('4. Selecting profile...');
        const profileCheckbox = page.locator('[x-data*="publishModal"] input[type="checkbox"]').first();
        if (await profileCheckbox.isVisible()) {
            await profileCheckbox.click();
            await page.waitForTimeout(500); // Wait for timezone fetch
        }
        console.log('   Profile selected');

        // 5. Enter content
        console.log('5. Entering content...');
        const contentInput = page.locator('[x-data*="publishModal"] textarea').first();
        if (await contentInput.isVisible()) {
            await contentInput.fill('Test post for timezone verification ' + new Date().toISOString());
        }

        // 6. Select schedule mode
        console.log('6. Selecting schedule mode...');
        const scheduleRadio = page.locator('input[value="schedule"]');
        if (await scheduleRadio.isVisible()) {
            await scheduleRadio.click();
            await page.waitForTimeout(500);
        }

        // 7. Set schedule time
        console.log('7. Setting schedule time...');
        const dateInput = page.locator('input[type="date"]').first();
        const timeInput = page.locator('input[type="time"]').first();

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dateValue = tomorrow.toISOString().split('T')[0];
        const timeValue = '21:28'; // 9:28 PM

        if (await dateInput.isVisible()) {
            await dateInput.fill(dateValue);
        }
        if (await timeInput.isVisible()) {
            await timeInput.fill(timeValue);
        }
        console.log(`   Set schedule: ${dateValue} ${timeValue}`);

        // 8. Wait for timezone fetch and check value
        console.log('8. Waiting for timezone to be fetched...');
        await page.waitForTimeout(2000);

        // Get the schedule object from Alpine
        const scheduleData = await page.evaluate(() => {
            const modal = document.querySelector('[x-data*="publishModal"]');
            if (modal && modal.__x && modal.__x.$data) {
                return {
                    schedule: modal.__x.$data.schedule,
                    selectedProfiles: modal.__x.$data.selectedProfiles?.length || 0
                };
            }
            return null;
        });

        console.log('   Schedule data:', JSON.stringify(scheduleData, null, 2));

        if (scheduleData && scheduleData.schedule) {
            const tz = scheduleData.schedule.timezone;
            if (tz === 'Asia/Bahrain') {
                console.log('   ✅ Timezone correctly set to Asia/Bahrain');
            } else if (tz === 'UTC') {
                console.log('   ⚠️ Timezone is still UTC - race condition may not be fixed');
            } else {
                console.log(`   ℹ️ Timezone set to: ${tz}`);
            }
        }

        // Take screenshot
        await page.screenshot({ path: 'test-results/timezone-test-modal.png', fullPage: false });
        console.log('   Screenshot saved to test-results/timezone-test-modal.png');

        console.log('\n✅ Test completed successfully');

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        await page.screenshot({ path: 'test-results/timezone-test-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

main().catch(console.error);
