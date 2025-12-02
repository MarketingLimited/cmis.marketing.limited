/**
 * Test marketplace enable/disable functionality
 */
const { chromium } = require('playwright');

async function testMarketplace() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    // Collect console messages
    const consoleMessages = [];
    page.on('console', msg => {
        consoleMessages.push({ type: msg.type(), text: msg.text() });
    });

    try {
        console.log('1. Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForLoadState('networkidle');

        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');

        await page.locator('button:has-text("تسجيل الدخول"), button:has-text("Login")').first().click();
        await page.waitForLoadState('networkidle', { timeout: 15000 });

        const currentUrl = page.url();
        console.log('   Current URL after login:', currentUrl);

        console.log('\n2. Going to marketplace...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/marketplace');
        await page.waitForLoadState('networkidle');

        // Take screenshot
        await page.screenshot({ path: 'test-results/marketplace-page.png', fullPage: true });
        console.log('   Screenshot saved: marketplace-page.png');

        // Look for app cards
        const appCards = await page.locator('.bg-white.rounded-2xl.shadow-sm').count();
        console.log('   App cards found:', appCards);

        // Find toggle buttons using Arabic text
        console.log('\n3. Looking for app toggle buttons...');
        const enableButtons = await page.locator('button:has-text("تفعيل")').all();
        const disableButtons = await page.locator('button:has-text("تعطيل")').all();

        console.log('   Enable buttons found:', enableButtons.length);
        console.log('   Disable buttons found:', disableButtons.length);

        // Try to click a button
        if (enableButtons.length > 0) {
            console.log('\n4. Testing enable button...');

            // Setup response listener before clicking
            const responsePromise = page.waitForResponse(
                resp => resp.url().includes('/marketplace/apps/') && resp.request().method() === 'POST',
                { timeout: 15000 }
            );

            console.log('   Clicking first enable button...');
            await enableButtons[0].click();

            try {
                const response = await responsePromise;
                console.log('   API Response status:', response.status());
                const responseBody = await response.json();
                console.log('   API Response:', JSON.stringify(responseBody, null, 2));

                if (responseBody.success) {
                    console.log('\n   ✅ Enable SUCCESS!');
                } else {
                    console.log('\n   ❌ Enable FAILED:', responseBody.message);
                }
            } catch (e) {
                console.log('   No API response received:', e.message);
            }

            await page.waitForTimeout(1500);
            await page.screenshot({ path: 'test-results/marketplace-after-click.png' });
            console.log('   Screenshot saved: marketplace-after-click.png');

        } else if (disableButtons.length > 0) {
            console.log('\n4. Testing disable button...');

            const responsePromise = page.waitForResponse(
                resp => resp.url().includes('/marketplace/apps/') && resp.request().method() === 'POST',
                { timeout: 15000 }
            );

            console.log('   Clicking first disable button...');
            await disableButtons[0].click();

            try {
                const response = await responsePromise;
                console.log('   API Response status:', response.status());
                const responseBody = await response.json();
                console.log('   API Response:', JSON.stringify(responseBody, null, 2));

                if (responseBody.success) {
                    console.log('\n   ✅ Disable SUCCESS!');
                } else {
                    console.log('\n   ❌ Disable FAILED:', responseBody.message);
                }
            } catch (e) {
                console.log('   No API response received:', e.message);
            }

            await page.waitForTimeout(1500);
            await page.screenshot({ path: 'test-results/marketplace-after-click.png' });

        } else {
            console.log('   No toggle buttons found! Check the screenshot.');
        }

        // Check for JavaScript errors
        console.log('\n5. Console errors:');
        const errors = consoleMessages.filter(m => m.type === 'error');
        if (errors.length > 0) {
            errors.forEach(e => console.log('   ERROR:', e.text));
        } else {
            console.log('   None');
        }

        console.log('\n========================================');
        console.log('Marketplace Test COMPLETED');
        console.log('========================================');

    } catch (error) {
        console.error('\nTest failed:', error.message);
        await page.screenshot({ path: 'test-results/marketplace-error.png' });
        console.log('Error screenshot saved: marketplace-error.png');
    } finally {
        await browser.close();
    }
}

// Ensure test-results directory exists
const fs = require('fs');
if (!fs.existsSync('test-results')) {
    fs.mkdirSync('test-results', { recursive: true });
}

testMarketplace();
