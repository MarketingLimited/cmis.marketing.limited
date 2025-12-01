const { chromium } = require('playwright');

async function testDestinationTypes() {
    const browser = await chromium.launch();
    const context = await browser.newContext({ 
        viewport: { width: 1280, height: 800 }
    });
    const page = await context.newPage();
    
    // Collect console errors
    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
    });
    
    try {
        // Login
        console.log('Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        
        // Use the primary login button
        const loginBtn = await page.$('form button[type="submit"]');
        if (loginBtn) {
            await loginBtn.click();
        } else {
            // Fallback to button with text
            await page.click('button:has-text("Log in"), button:has-text("تسجيل الدخول")');
        }
        await page.waitForURL('**/dashboard**', { timeout: 15000 });
        console.log('✓ Logged in');
        
        // Navigate to profile settings
        console.log('Navigating to profile settings...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profiles');
        await page.waitForLoadState('networkidle');
        
        // Take screenshot of profiles page
        await page.screenshot({ path: 'test-results/profiles-page.png', fullPage: true });
        console.log('✓ Screenshot saved: profiles-page.png');
        
        // Click on first profile (if exists)
        const profileLink = await page.$('table tbody tr:first-child a');
        if (profileLink) {
            await profileLink.click();
            await page.waitForLoadState('networkidle');
            console.log('✓ Navigated to profile detail page');
            
            // Take screenshot
            await page.screenshot({ path: 'test-results/profile-detail.png', fullPage: true });
            console.log('✓ Screenshot saved: profile-detail.png');
            
            // Look for boost settings section or button
            const addBoostBtn = await page.$('[x-on\\:click*="showBoostModal"], button:has-text("Add boost"), button:has-text("إضافة تعزيز")');
            if (addBoostBtn) {
                await addBoostBtn.click();
                await page.waitForTimeout(800);
                console.log('✓ Add boost modal opened');
                
                // Take screenshot
                await page.screenshot({ path: 'test-results/boost-modal.png', fullPage: true });
                console.log('✓ Screenshot saved: boost-modal.png');
            } else {
                console.log('! Add boost button not found on page');
            }
        } else {
            console.log('! No profiles found in table');
        }
        
        // Report console errors
        if (errors.length > 0) {
            console.log('\n⚠ Console errors:', errors.length);
            errors.slice(0, 5).forEach(e => console.log('  -', e.substring(0, 150)));
        } else {
            console.log('✓ No console errors');
        }
        
        console.log('\n✅ Test completed successfully');
        
    } catch (error) {
        console.error('Test failed:', error.message);
        await page.screenshot({ path: 'test-results/error.png' });
    } finally {
        await browser.close();
    }
}

testDestinationTypes();
