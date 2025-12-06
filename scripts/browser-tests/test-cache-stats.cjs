const puppeteer = require('puppeteer');

async function testCacheStats() {
    console.log('Testing Cache Stats Display on System Health Page...\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login first
        console.log('1. Logging in as admin...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle0' });
        await page.waitForSelector('input[name="email"]', { visible: true });
        await page.type('input[name="email"]', 'admin@cmis.test');
        await page.type('input[name="password"]', 'password');

        // Wait for form to be ready
        await new Promise(r => setTimeout(r, 500));

        // Use page.evaluate to find and click the Sign In button
        await page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button[type="submit"]'));
            const signInBtn = buttons.find(b => b.textContent.includes('Sign In') || b.textContent.includes('تسجيل الدخول'));
            if (signInBtn && signInBtn.offsetParent !== null) {
                signInBtn.click();
            }
        });

        await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 30000 });
        console.log('   ✓ Logged in successfully');

        // Navigate to System Health page
        console.log('\n2. Navigating to System Health page...');
        await page.goto('https://cmis-test.kazaaz.com/super-admin/system/health', { waitUntil: 'networkidle0' });
        console.log('   ✓ Page loaded');

        // Wait for health data to load
        console.log('\n3. Waiting for health data to load...');
        await new Promise(r => setTimeout(r, 3000)); // Wait for Alpine.js to load data
        console.log('   ✓ Data loading wait complete');

        // Test the API directly
        console.log('\n4. Testing API response...');
        const apiResponse = await page.evaluate(async () => {
            const resp = await fetch('/super-admin/system/health', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return await resp.json();
        });

        console.log('   API Response cache data:');
        const cacheCheck = apiResponse.data?.checks?.cache || apiResponse.checks?.cache;
        console.log('   - driver:', cacheCheck?.driver);
        console.log('   - driver_label:', cacheCheck?.driver_label);
        console.log('   - status:', cacheCheck?.status);
        console.log('   - supports_stats:', cacheCheck?.supports_stats);
        console.log('   - keys:', cacheCheck?.keys);
        console.log('   - hit_rate:', cacheCheck?.hit_rate);
        console.log('   - memory_used:', cacheCheck?.memory_used);

        // Get cache section content from page
        console.log('\n5. Checking cache section in page...');
        const cacheSection = await page.evaluate(() => {
            const sections = Array.from(document.querySelectorAll('.bg-white, .dark\\:bg-gray-800'));
            for (const section of sections) {
                if (section.textContent.includes('Cache') || section.textContent.includes('التخزين')) {
                    return section.textContent.substring(0, 300);
                }
            }
            return 'Not found';
        });
        console.log('   Cache section:', cacheSection);

        // Verify no "-%"" display
        const pageContent = await page.content();
        if (pageContent.includes('">-%</span>')) {
            console.log('\n   ✗ FAIL: Still showing "-%"" display');
        } else {
            console.log('\n   ✓ PASS: No bad "-%"" display found');
        }

        // Take screenshot
        await page.screenshot({ path: '/tmp/cache-stats-test.png', fullPage: true });
        console.log('\n   Screenshot saved to /tmp/cache-stats-test.png');

        console.log('\n✓ Test completed successfully');
    } catch (e) {
        console.error('Error:', e.message);
        await page.screenshot({ path: '/tmp/cache-stats-error.png', fullPage: true });
    }

    await browser.close();
}

testCacheStats().catch(console.error);
