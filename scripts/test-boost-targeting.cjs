const { chromium } = require('playwright');

async function testBoostTargeting() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 900 }
    });
    const page = await context.newPage();

    // Collect console errors
    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
    });

    try {
        // Login
        console.log('1. Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(500);
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.waitForTimeout(300);

        // Try multiple selectors for login button
        try {
            await page.click('button[type="submit"]', { timeout: 5000 });
        } catch {
            try {
                await page.click('button:has-text("Log in")', { timeout: 3000 });
            } catch {
                await page.click('button:has-text("تسجيل الدخول")', { timeout: 3000 });
            }
        }
        await page.waitForURL('**/dashboard**', { timeout: 20000 });
        console.log('   ✓ Logged in');

        // Navigate to profiles
        console.log('2. Navigating to profiles...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profiles');
        await page.waitForLoadState('networkidle');
        console.log('   ✓ On profiles page');

        // Click on a profile
        console.log('3. Opening profile detail...');
        const profileLink = await page.$('table tbody tr:first-child a');
        if (profileLink) {
            await profileLink.click();
            await page.waitForLoadState('networkidle');
            console.log('   ✓ Profile detail loaded');
        } else {
            console.log('   ⚠ No profiles found');
            throw new Error('No profiles to test with');
        }

        // Take screenshot of profile page
        await page.screenshot({ path: 'test-results/profile-before-boost.png', fullPage: true });

        // Find and click Add Boost button
        console.log('4. Opening boost modal...');
        const addBoostBtn = await page.$('button:has-text("Add boost"), button:has-text("إضافة تعزيز")');
        if (addBoostBtn) {
            await addBoostBtn.click();
            await page.waitForTimeout(800);
            console.log('   ✓ Boost modal opened');
        } else {
            console.log('   ⚠ Add boost button not found');
            throw new Error('Add boost button not found');
        }

        // Screenshot of boost modal
        await page.screenshot({ path: 'test-results/boost-modal-opened.png', fullPage: true });

        // Check if modal has the new autocomplete fields
        console.log('5. Verifying autocomplete fields...');

        // Check for Detailed Targeting section button
        const detailedTargetingBtn = await page.$('button:has-text("Detailed Targeting"), button:has-text("الاستهداف التفصيلي")');
        if (detailedTargetingBtn) {
            await detailedTargetingBtn.click();
            await page.waitForTimeout(300);
            console.log('   ✓ Detailed Targeting section expanded');
        }

        // Screenshot showing the autocomplete fields
        await page.screenshot({ path: 'test-results/boost-detailed-targeting.png', fullPage: true });

        // Check for Interests input with autocomplete
        const interestInput = await page.$('input[placeholder*="interest"], input[placeholder*="الاهتمامات"]');
        if (interestInput) {
            console.log('   ✓ Interest autocomplete input found');
        } else {
            console.log('   ⚠ Interest autocomplete input not found');
        }

        // Check for Locations input
        const locationInput = await page.$('input[placeholder*="cities"], input[placeholder*="المدن"]');
        if (locationInput) {
            console.log('   ✓ Location autocomplete input found');
        } else {
            console.log('   ⚠ Location autocomplete input not found');
        }

        // Check for Work Positions input
        const workPositionInput = await page.$('input[placeholder*="work"], input[placeholder*="الوظيفية"]');
        if (workPositionInput) {
            console.log('   ✓ Work positions autocomplete input found');
        } else {
            console.log('   ⚠ Work positions autocomplete input not found');
        }

        // Check for Behaviors section
        const behaviorsSection = await page.$('text=Behaviors, text=السلوكيات');
        if (behaviorsSection) {
            console.log('   ✓ Behaviors section found');
        } else {
            console.log('   ⚠ Behaviors section not found');
        }

        // Report console errors
        console.log('\n6. Console errors check...');
        if (errors.length > 0) {
            console.log(`   ⚠ ${errors.length} console error(s):`);
            errors.slice(0, 5).forEach(e => console.log(`     - ${e.substring(0, 150)}`));
        } else {
            console.log('   ✓ No console errors');
        }

        console.log('\n✅ Test completed successfully');
        console.log('\nScreenshots saved to test-results/');

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        await page.screenshot({ path: 'test-results/error-boost-targeting.png' });
    } finally {
        await browser.close();
    }
}

testBoostTargeting();
