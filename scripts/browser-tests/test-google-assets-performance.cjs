/**
 * Google Assets Page Performance Test
 *
 * Tests that the page loads in < 1 second (initial render)
 * and assets load progressively via AJAX.
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';
const TEST_ORG = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

async function runTest() {
    console.log('🚀 Starting Google Assets Performance Test...\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        locale: 'ar'
    });

    const page = await context.newPage();

    // Set Arabic locale
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    // Collect console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    // Collect network requests to AJAX endpoints
    const ajaxRequests = [];
    page.on('request', request => {
        const url = request.url();
        if (url.includes('/assets/ajax/')) {
            ajaxRequests.push({
                url: url,
                method: request.method(),
                startTime: Date.now()
            });
        }
    });

    page.on('response', response => {
        const url = response.url();
        if (url.includes('/assets/ajax/')) {
            const request = ajaxRequests.find(r => r.url === url && !r.endTime);
            if (request) {
                request.endTime = Date.now();
                request.status = response.status();
                request.duration = request.endTime - request.startTime;
            }
        }
    });

    try {
        // Step 1: Login
        console.log('📝 Logging in...');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        // Use text-based selector for login button
        await page.click('button:has-text("تسجيل الدخول"), button:has-text("Log in")');
        await page.waitForURL('**/dashboard**', { timeout: 15000 });
        console.log('✅ Logged in successfully\n');

        // Step 2: Navigate to Google assets page
        console.log('🔍 Finding Google connection...');

        // First, let's get a Google connection
        const connectionsUrl = `${BASE_URL}/orgs/${TEST_ORG}/settings/platform-connections`;
        await page.goto(connectionsUrl, { waitUntil: 'networkidle' });

        // Find a Google connection link
        const googleLink = await page.locator('a[href*="/google/"][href*="/assets"]').first();
        const hasGoogleConnection = await googleLink.count() > 0;

        if (!hasGoogleConnection) {
            console.log('⚠️  No Google connection found. Creating test for page structure...\n');

            // Take a screenshot of connections page
            await page.screenshot({
                path: 'test-results/google-assets-connections-page.png',
                fullPage: true
            });

            console.log('📸 Screenshot saved: test-results/google-assets-connections-page.png');
            console.log('\n✅ Test completed - no Google connection available to test\n');
            await browser.close();
            return;
        }

        const googleAssetsUrl = await googleLink.getAttribute('href');
        console.log(`📍 Found Google assets URL: ${googleAssetsUrl}\n`);

        // Step 3: Measure initial page load time
        console.log('⏱️  Measuring initial page load...');
        const startTime = Date.now();

        // URL might be relative or absolute
        const fullUrl = googleAssetsUrl.startsWith('http') ? googleAssetsUrl : `${BASE_URL}${googleAssetsUrl}`;
        await page.goto(fullUrl, { waitUntil: 'domcontentloaded' });

        const domContentLoaded = Date.now() - startTime;
        console.log(`   DOM Content Loaded: ${domContentLoaded}ms`);

        // Wait for initial render (skeleton UI)
        await page.waitForSelector('[x-data]', { timeout: 5000 });
        const initialRender = Date.now() - startTime;
        console.log(`   Initial Render: ${initialRender}ms`);

        // Step 4: Wait for AJAX loading to complete
        console.log('\n📡 Waiting for AJAX requests...');

        // Wait for loading to finish (progress bar gone or all sections loaded)
        try {
            await page.waitForFunction(() => {
                const progressBar = document.querySelector('[x-show="isInitialLoading"]');
                return !progressBar || window.getComputedStyle(progressBar).display === 'none';
            }, { timeout: 30000 });
        } catch (e) {
            console.log('   ⚠️  Timeout waiting for loading to complete');
        }

        const totalTime = Date.now() - startTime;

        // Step 5: Take screenshot
        await page.screenshot({
            path: 'test-results/google-assets-loaded.png',
            fullPage: true
        });
        console.log('\n📸 Screenshot saved: test-results/google-assets-loaded.png');

        // Step 6: Report results
        console.log('\n📊 Performance Results:');
        console.log('━'.repeat(50));
        console.log(`   DOM Content Loaded: ${domContentLoaded}ms ${domContentLoaded < 1000 ? '✅' : '❌'}`);
        console.log(`   Initial Render: ${initialRender}ms ${initialRender < 1000 ? '✅' : '❌'}`);
        console.log(`   Total Load Time: ${totalTime}ms`);
        console.log('━'.repeat(50));

        // AJAX requests summary
        console.log('\n📡 AJAX Requests:');
        if (ajaxRequests.length > 0) {
            ajaxRequests.forEach(req => {
                const endpoint = req.url.split('/ajax/')[1] || req.url;
                const status = req.status === 200 ? '✅' : (req.status === 404 ? '⚠️' : '❌');
                console.log(`   ${status} ${endpoint}: ${req.duration || 'pending'}ms (${req.status || 'pending'})`);
            });
        } else {
            console.log('   (No AJAX requests captured)');
        }

        // Console errors
        console.log('\n🔍 Console Errors:');
        if (consoleErrors.length > 0) {
            consoleErrors.forEach(err => console.log(`   ❌ ${err}`));
        } else {
            console.log('   ✅ No console errors');
        }

        // Final verdict
        console.log('\n' + '═'.repeat(50));
        if (initialRender < 1000 && consoleErrors.length === 0) {
            console.log('🎉 SUCCESS: Page loads in < 1 second with no errors!');
        } else if (initialRender < 1000) {
            console.log('⚠️  WARNING: Page loads quickly but has console errors');
        } else {
            console.log('❌ FAILED: Initial render > 1 second');
        }
        console.log('═'.repeat(50) + '\n');

    } catch (error) {
        console.error('❌ Test failed:', error.message);

        // Take error screenshot
        await page.screenshot({
            path: 'test-results/google-assets-error.png',
            fullPage: true
        });
        console.log('📸 Error screenshot saved: test-results/google-assets-error.png');
    }

    await browser.close();
}

// Ensure test-results directory exists
const fs = require('fs');
if (!fs.existsSync('test-results')) {
    fs.mkdirSync('test-results', { recursive: true });
}

runTest().catch(console.error);
