const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        locale: 'ar',
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        url: 'https://cmis-test.kazaaz.com'
    }]);

    console.log('1. Logging in...');
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    const loginButton = await page.locator('form button[type="submit"]').filter({ hasText: /تسجيل الدخول|دخول|login|sign in/i }).first();
    await loginButton.click();
    await page.waitForLoadState('networkidle');

    console.log('2. Opening publish modal...');
    const testOrgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    await page.goto(`https://cmis-test.kazaaz.com/orgs/${testOrgId}/social`);
    await page.waitForTimeout(2000);

    const publishButton = await page.locator('button').filter({ hasText: /نشر|إنشاء|publish|create|جديد/i }).first();
    if (await publishButton.isVisible()) {
        await publishButton.click();
        await page.waitForTimeout(1000);
    }

    console.log('3. Clicking AI Assistant button...');
    const aiButton = await page.locator('button:has(i.fa-magic)').first();
    await page.screenshot({ path: 'test-results/before-ai-click.png', fullPage: true });

    await aiButton.click();
    await page.waitForTimeout(1000);

    console.log('4. Checking if AI Assistant overlay is visible...');
    const overlay = await page.locator('[x-show="showAIAssistant"]').first();
    const isVisible = await overlay.isVisible();
    const dimensions = await overlay.evaluate((el) => ({
        offsetWidth: el.offsetWidth,
        offsetHeight: el.offsetHeight,
        isVisible: el.offsetWidth > 0 && el.offsetHeight > 0
    }));

    console.log(`\n✅ RESULT:`);
    console.log(`   Overlay visible (Playwright): ${isVisible}`);
    console.log(`   Overlay dimensions: ${dimensions.offsetWidth}x${dimensions.offsetHeight}`);
    console.log(`   Overlay has dimensions: ${dimensions.isVisible}`);

    await page.screenshot({ path: 'test-results/after-ai-click.png', fullPage: true });

    if (isVisible && dimensions.isVisible) {
        console.log(`\n🎉 SUCCESS! AI Assistant overlay is displaying correctly!`);
    } else {
        console.log(`\n❌ FAILED: AI Assistant overlay is still not visible.`);
    }

    console.log(`\nScreenshots saved:`);
    console.log(`   - test-results/before-ai-click.png`);
    console.log(`   - test-results/after-ai-click.png`);

    await page.waitForTimeout(2000);
    await browser.close();
})();
