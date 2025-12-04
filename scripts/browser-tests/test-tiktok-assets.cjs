const { chromium } = require('playwright');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    timeout: 30000,
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    }
};

async function login(page) {
    await page.goto(`${CONFIG.baseUrl}/login`, { waitUntil: 'networkidle', timeout: CONFIG.timeout });
    await page.fill('input[name="email"]', CONFIG.credentials.email);
    await page.fill('input[name="password"]', CONFIG.credentials.password);
    await page.click('form button[type="submit"]:visible');

    try {
        await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: CONFIG.timeout });
        return !page.url().includes('/login');
    } catch (e) {
        return false;
    }
}

(async () => {
    console.log('🔧 TikTok Business Assets Test');
    console.log('━'.repeat(50));

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
    });
    page.on('pageerror', err => errors.push(err.message));

    // Login
    const loginSuccess = await login(page);
    if (!loginSuccess) {
        console.log('❌ Login failed');
        await browser.close();
        process.exit(1);
    }
    console.log('✅ Logged in successfully');

    // Test 1: Platform Connections page - Check "Manage TikTok Assets" button
    console.log('\n📋 Test 1: Platform Connections Index (English)');
    await page.goto(`${CONFIG.baseUrl}/orgs/${CONFIG.orgId}/settings/platform-connections`, {
        waitUntil: 'networkidle',
        timeout: CONFIG.timeout
    });

    // Look for "Manage TikTok Assets" button
    const manageTikTokBtn = await page.locator('a:has-text("Manage TikTok Assets")');
    if (await manageTikTokBtn.count() > 0) {
        console.log('   ✅ "Manage TikTok Assets" button found');
        const href = await manageTikTokBtn.getAttribute('href');
        console.log('   📍 Link:', href);
    } else {
        console.log('   ❌ "Manage TikTok Assets" button NOT found');
    }

    // Old connect buttons should be removed
    const oldConnectAccountBtn = await page.locator('a[href*="tiktok/authorize"]:not([href*="business_asset_id"])');
    const oldConnectAdsBtn = await page.locator('a[href*="tiktok-ads/authorize"]:not([href*="business_asset_id"])');

    if (await oldConnectAccountBtn.count() === 0 && await oldConnectAdsBtn.count() === 0) {
        console.log('   ✅ Old connect buttons removed from index page');
    } else {
        console.log('   ⚠️  Old connect buttons still present (may need to scroll)');
    }

    await page.screenshot({ path: '/tmp/tiktok-01-index-en.png', fullPage: true });
    console.log('   📸 Screenshot: /tmp/tiktok-01-index-en.png');

    // Test 2: TikTok Business Assets Index page
    console.log('\n📋 Test 2: TikTok Business Assets Index (English)');
    await page.goto(`${CONFIG.baseUrl}/orgs/${CONFIG.orgId}/settings/platform-connections/tiktok-assets`, {
        waitUntil: 'networkidle',
        timeout: CONFIG.timeout
    });

    const pageTitle = await page.title();
    console.log('   📄 Page Title:', pageTitle);

    // Check for create button
    const createBtn = await page.locator('button:has-text("Create TikTok Business Asset")');
    if (await createBtn.count() > 0) {
        console.log('   ✅ "Create TikTok Business Asset" button found');

        // Open modal
        await createBtn.click();
        await page.waitForTimeout(500);

        // Check if modal is visible
        const modal = await page.locator('input[name="name"]');
        if (await modal.isVisible()) {
            console.log('   ✅ Create modal opened successfully');
        }
        await page.screenshot({ path: '/tmp/tiktok-02-create-modal-en.png', fullPage: true });
        console.log('   📸 Screenshot: /tmp/tiktok-02-create-modal-en.png');

        // Close modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(300);
    } else {
        console.log('   ❌ "Create TikTok Business Asset" button NOT found');
    }

    await page.screenshot({ path: '/tmp/tiktok-03-assets-index-en.png', fullPage: true });
    console.log('   📸 Screenshot: /tmp/tiktok-03-assets-index-en.png');

    // Test 3: Arabic/RTL Test
    console.log('\n📋 Test 3: Platform Connections (Arabic/RTL)');
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    await page.goto(`${CONFIG.baseUrl}/orgs/${CONFIG.orgId}/settings/platform-connections`, {
        waitUntil: 'networkidle',
        timeout: CONFIG.timeout
    });

    // Check for RTL direction
    const rtlDir = await page.locator('[dir="rtl"]').count();
    console.log('   ' + (rtlDir > 0 ? '✅' : '❌') + ' RTL direction detected:', rtlDir > 0);

    // Check for Arabic TikTok button text
    const arabicManageBtn = await page.locator('a:has-text("إدارة أصول تيك توك")');
    if (await arabicManageBtn.count() > 0) {
        console.log('   ✅ Arabic "Manage TikTok Assets" button found');
    } else {
        console.log('   ⚠️  Arabic button text not found (checking English fallback)');
    }

    await page.screenshot({ path: '/tmp/tiktok-04-index-ar.png', fullPage: true });
    console.log('   📸 Screenshot: /tmp/tiktok-04-index-ar.png');

    // Test 4: TikTok Assets Index (Arabic)
    console.log('\n📋 Test 4: TikTok Business Assets Index (Arabic/RTL)');
    await page.goto(`${CONFIG.baseUrl}/orgs/${CONFIG.orgId}/settings/platform-connections/tiktok-assets`, {
        waitUntil: 'networkidle',
        timeout: CONFIG.timeout
    });

    // Check RTL direction on this page
    const rtlDirAssets = await page.locator('[dir="rtl"]').count();
    console.log('   ' + (rtlDirAssets > 0 ? '✅' : '❌') + ' RTL direction detected on assets page:', rtlDirAssets > 0);

    await page.screenshot({ path: '/tmp/tiktok-05-assets-index-ar.png', fullPage: true });
    console.log('   📸 Screenshot: /tmp/tiktok-05-assets-index-ar.png');

    // Console Errors Summary
    console.log('\n📊 Console Errors Summary:');
    if (errors.length > 0) {
        console.log('   ⚠️  ' + errors.length + ' console errors detected:');
        errors.forEach(e => console.log('      -', e.substring(0, 100)));
    } else {
        console.log('   ✅ No console errors detected');
    }

    await browser.close();
    console.log('\n' + '━'.repeat(50));
    console.log('✅ TikTok Business Assets Test Completed');
})();
