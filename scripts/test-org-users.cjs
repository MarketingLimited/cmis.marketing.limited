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

    // Go to specific org detail page
    await page.goto('https://cmis-test.kazaaz.com/super-admin/organizations/8b6f1a2d-4e5f-5a6b-9c8d-2e3f4a5b6c7d');
    
    // Click on Users tab
    await page.click('button:has-text("المستخدمون")');
    await page.waitForTimeout(500);
    
    // Take screenshot
    await page.screenshot({ path: '/tmp/org-users.png', fullPage: true });
    console.log('Screenshot saved to /tmp/org-users.png');
    
    // Get all user names in the table
    const userNames = await page.$$eval('table tbody tr td:first-child', els => els.map(el => el.textContent.trim()));
    console.log('Users found:', userNames);
    console.log('Count:', userNames.length);
    
    // Check for duplicates
    const counts = {};
    userNames.forEach(name => {
        counts[name] = (counts[name] || 0) + 1;
    });
    console.log('User counts:', counts);
    
    await browser.close();
})();
