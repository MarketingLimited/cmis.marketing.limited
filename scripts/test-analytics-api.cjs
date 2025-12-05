const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Login first
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button.bg-indigo-600[type="submit"]');
    await page.waitForURL(/dashboard|super-admin/, { timeout: 15000 });
    
    console.log('Testing analytics API endpoints...\n');
    
    // Test each analytics endpoint
    const endpoints = [
        { name: 'Analytics Index (JSON)', url: '/super-admin/analytics?range=24h' },
        { name: 'By Org', url: '/super-admin/analytics/by-org?range=24h&limit=10' },
        { name: 'Endpoints', url: '/super-admin/analytics/endpoints?range=24h' },
        { name: 'Errors', url: '/super-admin/analytics/errors?range=24h' },
    ];
    
    for (const ep of endpoints) {
        const response = await page.evaluate(async (url) => {
            try {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                return { status: res.status, data: data, ok: res.ok };
            } catch (e) {
                return { error: e.message };
            }
        }, 'https://cmis-test.kazaaz.com' + ep.url);
        
        if (response.error) {
            console.log(`❌ ${ep.name}: ERROR - ${response.error}`);
        } else if (response.ok) {
            console.log(`✅ ${ep.name}: OK (${response.status})`);
            // Show sample of data
            if (response.data?.data) {
                const d = response.data.data;
                if (d.overview) {
                    console.log(`   Overview: total_requests=${d.overview.total_requests}, error_rate=${d.overview.error_rate}`);
                }
                if (Array.isArray(d)) {
                    console.log(`   Records: ${d.length}`);
                }
            }
        } else {
            console.log(`❌ ${ep.name}: FAILED (${response.status})`);
            console.log(`   Response:`, JSON.stringify(response.data).substring(0, 200));
        }
    }
    
    await browser.close();
})();
