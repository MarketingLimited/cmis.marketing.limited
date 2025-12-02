const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

async function debugAutocomplete() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 900 }
    });
    const page = await context.newPage();

    // Collect console messages
    const consoleMessages = [];
    const networkErrors = [];

    page.on('console', msg => {
        const text = msg.text();
        consoleMessages.push({ type: msg.type(), text: text });
        if (msg.type() === 'error') {
            console.log('Console Error:', text);
        }
    });

    page.on('requestfailed', request => {
        networkErrors.push({
            url: request.url(),
            failure: request.failure()?.errorText
        });
        console.log('Network Error:', request.url(), request.failure()?.errorText);
    });

    // Track API responses
    const apiResponses = [];
    page.on('response', async response => {
        const url = response.url();
        if (url.includes('search-interests') || url.includes('search-locations') || url.includes('search-work-positions') || url.includes('search-behaviors') || url.includes('meta-audiences')) {
            try {
                const body = await response.text();
                apiResponses.push({
                    url: url,
                    status: response.status(),
                    body: body.substring(0, 500)
                });
                console.log(`API Response: ${url} - Status: ${response.status()}`);
                console.log('Body:', body.substring(0, 200));
            } catch (e) {
                apiResponses.push({ url: url, status: response.status(), body: 'Could not read body' });
            }
        }
    });

    try {
        // Login
        console.log('1. Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');

        // Wait and click the main login button (visible one)
        await page.waitForTimeout(500);
        const loginBtn = await page.$('form button[type="submit"]:visible');
        if (loginBtn) {
            await loginBtn.click();
        } else {
            // Fallback: press Enter
            await page.press('input[name="password"]', 'Enter');
        }
        await page.waitForURL('**/dashboard**', { timeout: 20000 });
        console.log('   Logged in!');

        // Navigate to profiles
        console.log('2. Navigating to profiles...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profiles');
        await page.waitForLoadState('networkidle');

        // Take screenshot
        await page.screenshot({ path: 'test-results/debug-1-profiles.png' });

        // Click on first profile
        console.log('3. Opening profile...');
        const profileLink = await page.$('table tbody tr:first-child a');
        if (profileLink) {
            await profileLink.click();
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'test-results/debug-2-profile-detail.png' });
        }

        // Click Add Boost button
        console.log('4. Opening boost modal...');
        const addBoostBtn = await page.$('button:has-text("Add boost"), button:has-text("إضافة تعزيز"), a:has-text("Add boost")');
        if (addBoostBtn) {
            await addBoostBtn.click();
            await page.waitForTimeout(1000);
        } else {
            // Try clicking on existing boost or add boost link
            const boostLink = await page.$('[x-data*="showBoostModal"] button, [x-on\\:click*="showBoostModal"]');
            if (boostLink) {
                await boostLink.click();
                await page.waitForTimeout(1000);
            }
        }
        await page.screenshot({ path: 'test-results/debug-3-boost-modal.png' });

        // Select an Ad Account if available
        console.log('5. Selecting ad account...');
        const adAccountSelect = await page.$('select[x-model="form.ad_account_id"]');
        if (adAccountSelect) {
            const options = await adAccountSelect.$$('option');
            if (options.length > 1) {
                // Select second option (first real ad account)
                await adAccountSelect.selectOption({ index: 1 });
                console.log('   Selected ad account!');
                await page.waitForTimeout(2000); // Wait for audiences to load
            }
        }
        await page.screenshot({ path: 'test-results/debug-4-ad-account-selected.png' });

        // Expand Detailed Targeting section
        console.log('6. Expanding Detailed Targeting...');
        const detailedTargetingBtn = await page.$('button:has-text("Detailed targeting"), button:has-text("الاستهداف التفصيلي")');
        if (detailedTargetingBtn) {
            await detailedTargetingBtn.click();
            await page.waitForTimeout(500);
        }
        await page.screenshot({ path: 'test-results/debug-5-detailed-targeting.png' });

        // Test interest search
        console.log('7. Testing interest search...');
        const interestInput = await page.$('input[x-model="interestSearch"]');
        if (interestInput) {
            await interestInput.click();
            await interestInput.fill('cars');
            console.log('   Typed "cars" in interest search');
            await page.waitForTimeout(2000); // Wait for API call

            // Check if dropdown appears
            const dropdown = await page.$('[x-show*="showInterestDropdown"]');
            const isVisible = dropdown ? await dropdown.isVisible() : false;
            console.log('   Interest dropdown visible:', isVisible);
        } else {
            console.log('   Could not find interest input!');
        }
        await page.screenshot({ path: 'test-results/debug-6-interest-search.png' });

        // Test location search
        console.log('8. Testing location search...');
        const locationInput = await page.$('input[x-model="locationSearch"]');
        if (locationInput) {
            await locationInput.click();
            await locationInput.fill('dubai');
            console.log('   Typed "dubai" in location search');
            await page.waitForTimeout(2000);
        }
        await page.screenshot({ path: 'test-results/debug-7-location-search.png' });

        // Print summary
        console.log('\n========== DEBUG SUMMARY ==========');
        console.log('Console Errors:', consoleMessages.filter(m => m.type === 'error').length);
        consoleMessages.filter(m => m.type === 'error').forEach(m => console.log('  -', m.text.substring(0, 150)));

        console.log('\nNetwork Errors:', networkErrors.length);
        networkErrors.forEach(e => console.log('  -', e.url, e.failure));

        console.log('\nAPI Responses:', apiResponses.length);
        apiResponses.forEach(r => console.log('  -', r.status, r.url.split('/').pop(), r.body?.substring(0, 100)));

        console.log('\n====================================');
        console.log('Screenshots saved to test-results/debug-*.png');

        // Save debug info
        fs.writeFileSync('test-results/debug-info.json', JSON.stringify({
            consoleMessages,
            networkErrors,
            apiResponses
        }, null, 2));

    } catch (error) {
        console.error('Test error:', error);
        await page.screenshot({ path: 'test-results/debug-error.png' });
    } finally {
        await browser.close();
    }
}

debugAutocomplete().catch(console.error);
