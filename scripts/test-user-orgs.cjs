const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button.bg-indigo-600[type="submit"]');
    await page.waitForURL(/dashboard|super-admin/, { timeout: 15000 });

    // Go to specific user detail page
    await page.goto('https://cmis-test.kazaaz.com/super-admin/users/4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a');
    
    // Click on Organizations tab
    await page.click('button:has-text("المنظمات")');
    await page.waitForTimeout(500);
    
    // Take screenshot
    await page.screenshot({ path: '/tmp/user-orgs-duplicate.png', fullPage: true });
    console.log('Screenshot saved to /tmp/user-orgs-duplicate.png');
    
    // Get all org names in the table
    const orgNames = await page.$$eval('table tbody tr td:first-child a', els => els.map(el => el.textContent.trim()));
    console.log('Organizations found:', orgNames);
    console.log('Count:', orgNames.length);
    
    // Check for duplicates
    const counts = {};
    orgNames.forEach(name => {
        counts[name] = (counts[name] || 0) + 1;
    });
    console.log('Organization counts:', counts);
    
    await browser.close();
})();
