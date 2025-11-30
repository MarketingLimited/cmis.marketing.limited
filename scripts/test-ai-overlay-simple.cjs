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

    console.log('Logging in...');
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    const loginButton = await page.locator('form button[type="submit"]').filter({ hasText: /تسجيل الدخول|دخول|login|sign in/i }).first();
    await loginButton.click();
    await page.waitForLoadState('networkidle');

    console.log('Opening publish modal...');
    const testOrgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    await page.goto(`https://cmis-test.kazaaz.com/orgs/${testOrgId}/social`);
    await page.waitForTimeout(2000);

    const publishButton = await page.locator('button').filter({ hasText: /نشر|إنشاء|publish|create|جديد/i }).first();
    if (await publishButton.isVisible()) {
        await publishButton.click();
        await page.waitForTimeout(1000);
    }

    console.log('\nChecking AI Assistant overlay DOM location...');
    const domInfo = await page.evaluate(() => {
        const overlay = document.querySelector('[x-show="showAIAssistant"]');
        if (!overlay) return { error: 'Overlay not found' };

        // Get path from overlay to root
        const path = [];
        let current = overlay;
        while (current) {
            const info = {
                tag: current.tagName,
                id: current.id,
                hasXData: current.hasAttribute('x-data'),
                xDataValue: current.getAttribute('x-data'),
                classes: current.className?.substring(0, 60)
            };
            path.push(info);
            current = current.parentElement;
        }

        return { path };
    });

    console.log('\nDOM Path from AI Assistant to root:');
    if (domInfo.error) {
        console.log('  Error:', domInfo.error);
    } else {
        domInfo.path.forEach((node, idx) => {
            console.log(`  [${idx}] ${node.tag}${node.id ? '#' + node.id : ''} ${node.hasXData ? `(x-data="${node.xDataValue}")` : ''}`);
            if (node.classes) console.log(`      class: ${node.classes}`);
        });
    }

    await browser.close();
})();
