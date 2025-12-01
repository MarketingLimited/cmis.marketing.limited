const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();
    const errors = [];
    
    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
    });

    try {
        // Login
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        // Click the main login button (has specific class and text)
        await page.click('button[type="submit"]:has-text("Sign in"), button[type="submit"]:has-text("تسجيل الدخول")');
        await page.waitForURL('**/dashboard**', { timeout: 15000 });
        console.log('✓ Logged in successfully');

        // Go to profile page
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profiles/019ad524-9807-73d4-892e-e1ca9fc6cd84');
        await page.waitForLoadState('networkidle');
        console.log('✓ Profile page loaded');

        // Check for key elements in page
        const pageContent = await page.content();
        console.log('✓ Boost Settings exists:', pageContent.includes('BOOST SETTINGS') || pageContent.includes('إعدادات التعزيز'));

        // Look for the add boost button and click it
        const addBoostBtn = await page.$('button:has-text("Add boost")') || await page.$('button:has-text("إضافة تعزيز")');
        
        if (addBoostBtn) {
            await addBoostBtn.click();
            await page.waitForTimeout(1000);
            console.log('✓ Clicked Add boost button');
            
            // Check modal content
            const modalContent = await page.content();
            console.log('✓ Ad Account dropdown:', modalContent.includes('x-model="form.ad_account_id"'));
            console.log('✓ Campaign Objective:', modalContent.includes('form.objective'));
            console.log('✓ Advantage+ toggle:', modalContent.includes('advantage_plus_enabled'));
            console.log('✓ Audience targeting section:', modalContent.includes('showAudienceSection'));
            console.log('✓ Budget validation:', modalContent.includes('validateBudget'));
            
            // Take a screenshot
            await page.screenshot({ path: 'test-results/boost-modal.png', fullPage: false });
            console.log('✓ Screenshot saved to test-results/boost-modal.png');
        } else {
            console.log('⚠ Could not find Add boost button, checking page content...');
            console.log('Page has boost section:', pageContent.includes('boost'));
        }

        // Final error check
        await page.waitForTimeout(500);
        const jsErrors = errors.filter(e => !e.includes('favicon'));
        if (jsErrors.length > 0) {
            console.log('⚠ Console errors:', jsErrors.slice(0, 3).join('\n  '));
        } else {
            console.log('✓ No JavaScript console errors');
        }

        console.log('\n✓ Verification complete!');

    } catch (error) {
        console.error('✗ Error:', error.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
