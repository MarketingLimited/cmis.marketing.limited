const { chromium } = require('playwright');

async function testWizard() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    console.log('Testing wizard dashboard...');

    // Set locale cookie
    await context.addCookies([{
        name: 'app_locale',
        value: 'en',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    // Login first
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');

    // Use form submit button with explicit selector
    await page.click('form button[type="submit"]:visible');
    await page.waitForURL('**/dashboard**', { timeout: 30000 });

    // Navigate to wizard dashboard
    const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    await page.goto(`https://cmis-test.kazaaz.com/orgs/${orgId}/settings/platform-connections/wizard`);
    await page.waitForLoadState('networkidle');

    // Check page title
    const title = await page.title();
    console.log('Page title:', title);

    // Check for platform cards
    const platformCards = await page.locator('.grid.grid-cols-2 a').count();
    console.log('Platform cards found:', platformCards);

    // Check page content
    const content = await page.content();
    console.log('Has platform grid:', content.includes('grid-cols-2'));
    console.log('Has Meta platform:', content.includes('Meta') || content.includes('ميتا'));
    console.log('Has Google platform:', content.includes('Google') || content.includes('جوجل'));

    // Take screenshot
    await page.screenshot({ path: 'test-results/wizard-dashboard.png', fullPage: true });
    console.log('Screenshot saved to test-results/wizard-dashboard.png');

    // Navigate to start wizard for Meta
    const metaLink = page.locator('a[href*="/wizard/meta"]').first();
    if (await metaLink.count() > 0) {
        await metaLink.click();
        await page.waitForLoadState('networkidle');

        const modeTitle = await page.title();
        console.log('Step 1 (Mode) title:', modeTitle);

        // Check for step 1 content
        const step1Content = await page.content();
        console.log('Has step indicator:', step1Content.includes('wizard.steps.connect') || step1Content.includes('الاتصال') || step1Content.includes('Connect'));
        console.log('Has OAuth button:', step1Content.includes('wizard.mode.direct.button') || step1Content.includes('الاتصال بـ') || step1Content.includes('Connect with'));

        // Take screenshot of step 1
        await page.screenshot({ path: 'test-results/wizard-step1-meta.png', fullPage: true });
        console.log('Screenshot saved to test-results/wizard-step1-meta.png');
    } else {
        console.log('Meta link not found');
    }

    await browser.close();
    console.log('Test completed successfully!');
}

testWizard().catch(console.error);
