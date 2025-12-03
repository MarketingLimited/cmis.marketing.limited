const { chromium } = require('playwright');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
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
    
    // Click the visible submit button
    await page.click('form button[type="submit"]:visible');
    
    try {
        await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: CONFIG.timeout });
        return !page.url().includes('/login');
    } catch (e) {
        return false;
    }
}

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Login
    const loginSuccess = await login(page);
    if (!loginSuccess) {
        console.log('❌ Login failed');
        await browser.close();
        process.exit(1);
    }
    console.log('✅ Logged in successfully');

    // Navigate to platform connections
    await page.goto(`${CONFIG.baseUrl}/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections`, {
        waitUntil: 'networkidle',
        timeout: CONFIG.timeout
    });
    await page.waitForTimeout(2000);

    console.log('Current URL:', page.url());

    // Take a screenshot of the full page
    await page.screenshot({ 
        path: '/tmp/platform-connections-full.png',
        fullPage: true 
    });
    console.log('Full page screenshot saved to /tmp/platform-connections-full.png');

    // Look for TikTok section
    const tiktokHeading = await page.locator('h3:has-text("TikTok")').first();
    if (await tiktokHeading.isVisible()) {
        console.log('✅ TikTok heading found');
        
        // Scroll into view
        await tiktokHeading.scrollIntoViewIfNeeded();
        await page.waitForTimeout(500);
        
        // Take a focused screenshot
        await page.screenshot({ 
            path: '/tmp/tiktok-section.png',
            fullPage: false 
        });
        console.log('TikTok section screenshot saved to /tmp/tiktok-section.png');
    } else {
        console.log('❌ TikTok heading not visible - checking page content...');
        const content = await page.content();
        console.log('Page contains "TikTok":', content.includes('TikTok'));
    }

    // Check for the two TikTok buttons by looking for tiktok-ads route
    const allLinks = await page.locator('a[href*="tiktok"]').all();
    console.log(`Found ${allLinks.length} TikTok-related links:`);
    for (const link of allLinks) {
        const href = await link.getAttribute('href');
        const text = await link.textContent();
        console.log(`  - "${text.trim()}" -> ${href}`);
    }

    // Check for "Connect Account" and "Connect Ads" buttons
    const connectAccountBtn = await page.locator('a[href*="tiktok/authorize"]').first();
    const connectAdsBtn = await page.locator('a[href*="tiktok-ads/authorize"]').first();
    
    if (await connectAccountBtn.count() > 0) {
        console.log('✅ Connect TikTok Account button found');
    } else {
        console.log('❌ Connect TikTok Account button not found');
    }
    
    if (await connectAdsBtn.count() > 0) {
        console.log('✅ Connect TikTok Ads button found');
    } else {
        console.log('❌ Connect TikTok Ads button not found');
    }

    await browser.close();
    console.log('\n✅ Test completed');
})();
