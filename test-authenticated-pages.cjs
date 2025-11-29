/**
 * Authenticated Pages Browser Testing
 * Logs in first, then tests all org-scoped pages
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    screenshotDir: './test-results/authenticated-pages',
    timeout: 30000,

    // Login credentials (will try common defaults)
    credentials: {
        email: 'admin@cmis.test',
        password: 'password' // Common default - may need adjustment
    }
};

// Test pages
const PAGES = [
    { path: `/orgs/${CONFIG.orgId}/dashboard`, name: 'dashboard' },
    { path: `/orgs/${CONFIG.orgId}/campaigns`, name: 'campaigns' },
    { path: `/orgs/${CONFIG.orgId}/social`, name: 'social' },
    { path: `/orgs/${CONFIG.orgId}/analytics`, name: 'analytics' },
    { path: `/orgs/${CONFIG.orgId}/settings/platform-connections`, name: 'settings-platforms' },
    { path: `/orgs/${CONFIG.orgId}/team`, name: 'team' },
    { path: '/profile', name: 'profile' },
];

if (!fs.existsSync(CONFIG.screenshotDir)) {
    fs.mkdirSync(CONFIG.screenshotDir, { recursive: true });
}

async function login(page) {
    console.log('\n🔐 Attempting to login...');
    console.log(`   Email: ${CONFIG.credentials.email}`);
    console.log(`   URL: ${CONFIG.baseUrl}/login`);

    try {
        await page.goto(`${CONFIG.baseUrl}/login`, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        // Fill login form
        await page.type('input[name="email"]', CONFIG.credentials.email);
        await page.type('input[name="password"]', CONFIG.credentials.password);

        // Submit form
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);

        // Check if login succeeded
        const currentUrl = page.url();
        const loginFailed = currentUrl.includes('/login');

        if (loginFailed) {
            console.log('   ❌ Login FAILED - still on login page');
            console.log('   Current URL:', currentUrl);

            // Check for error messages
            const errorMsg = await page.evaluate(() => {
                const error = document.querySelector('.bg-red-50, .text-red-700, .error');
                return error ? error.innerText : null;
            });

            if (errorMsg) {
                console.log('   Error message:', errorMsg);
            }

            return false;
        }

        console.log('   ✅ Login SUCCESSFUL');
        console.log('   Redirected to:', currentUrl);
        return true;

    } catch (error) {
        console.log('   ❌ Login error:', error.message);
        return false;
    }
}

async function testAuthenticatedPage(page, pagePath, pageName) {
    console.log(`\n📍 Testing: ${pageName}`);

    const url = `${CONFIG.baseUrl}${pagePath}`;
    console.log(`   URL: ${url}`);

    try {
        const response = await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        await new Promise(resolve => setTimeout(resolve, 2000));

        const statusCode = response.status();
        const currentUrl = page.url();

        console.log(`   Status: ${statusCode}`);
        console.log(`   Final URL: ${currentUrl}`);

        // Check if redirected to login (session expired)
        if (currentUrl.includes('/login')) {
            console.log('   ⚠️  Redirected to login - session may have expired');
            return { name: pageName, status: 'session_expired', statusCode };
        }

        // Get page metadata
        const metadata = await page.evaluate(() => {
            return {
                locale: document.documentElement.lang || 'unknown',
                direction: document.documentElement.dir || 'unknown',
                title: document.title || 'No title',
                hasArabic: /[\u0600-\u06FF]/.test(document.body.innerText),
                hasEnglish: /\b(Dashboard|Campaign|Settings|Analytics)\b/i.test(document.body.innerText),
                bodyText: document.body.innerText.substring(0, 300)
            };
        });

        console.log(`   Locale: ${metadata.locale}`);
        console.log(`   Direction: ${metadata.direction}`);
        console.log(`   Has Arabic: ${metadata.hasArabic ? 'YES ✅' : 'NO ❌'}`);
        console.log(`   Has English: ${metadata.hasEnglish ? 'YES ✅' : 'NO ❌'}`);

        // Check for language switcher
        const hasLanguageSwitcher = await page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button'));
            return buttons.some(btn =>
                btn.textContent.includes('العربية') ||
                btn.textContent.includes('English')
            );
        });

        console.log(`   Language Switcher: ${hasLanguageSwitcher ? 'YES ✅' : 'NO ❌'}`);

        // Screenshot in Arabic
        const screenshotAr = path.join(CONFIG.screenshotDir, `${pageName}-ar.png`);
        await page.screenshot({ path: screenshotAr, fullPage: true });
        console.log(`   Screenshot (AR): ${screenshotAr}`);

        // Try to switch to English
        let screenshotEn = null;
        if (hasLanguageSwitcher) {
            try {
                console.log(`   🔄 Switching to English...`);

                // Click language switcher
                await page.evaluate(() => {
                    const buttons = Array.from(document.querySelectorAll('button'));
                    const switcherBtn = buttons.find(btn =>
                        btn.textContent.includes('العربية') ||
                        btn.textContent.includes('English')
                    );
                    if (switcherBtn) switcherBtn.click();
                });

                await new Promise(resolve => setTimeout(resolve, 500));

                // Click English option
                await page.evaluate(() => {
                    const buttons = Array.from(document.querySelectorAll('button[type="submit"]'));
                    const englishBtn = buttons.find(btn => btn.textContent.includes('English'));
                    if (englishBtn) englishBtn.click();
                });

                await new Promise(resolve => setTimeout(resolve, 3000));

                // Take English screenshot
                screenshotEn = path.join(CONFIG.screenshotDir, `${pageName}-en.png`);
                await page.screenshot({ path: screenshotEn, fullPage: true });
                console.log(`   Screenshot (EN): ${screenshotEn}`);

            } catch (error) {
                console.log(`   ⚠️  Could not switch language: ${error.message}`);
            }
        }

        return {
            name: pageName,
            url,
            status: 'success',
            statusCode,
            locale: metadata.locale,
            direction: metadata.direction,
            hasLanguageSwitcher,
            screenshots: {
                arabic: screenshotAr,
                english: screenshotEn
            }
        };

    } catch (error) {
        console.log(`   ❌ Error: ${error.message}`);
        return {
            name: pageName,
            status: 'error',
            error: error.message
        };
    }
}

async function runTests() {
    console.log('🌐 Starting Authenticated Pages Browser Testing\n');
    console.log('=' .repeat(80));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    const results = [];

    try {
        // Step 1: Login
        const loginSuccess = await login(page);

        if (!loginSuccess) {
            console.log('\n❌ Cannot proceed without successful login');
            console.log('💡 Please verify credentials or reset admin password');
            await browser.close();
            return;
        }

        // Step 2: Test each page
        console.log('\n' + '='.repeat(80));
        console.log('📋 Testing Pages While Authenticated');
        console.log('='.repeat(80));

        for (const pageInfo of PAGES) {
            const result = await testAuthenticatedPage(page, pageInfo.path, pageInfo.name);
            results.push(result);
        }

        // Summary
        console.log('\n' + '='.repeat(80));
        console.log('📊 TEST SUMMARY');
        console.log('='.repeat(80));

        const successful = results.filter(r => r.status === 'success').length;
        const failed = results.filter(r => r.status !== 'success').length;

        console.log(`Total Pages Tested: ${results.length}`);
        console.log(`✅ Successful: ${successful}`);
        console.log(`❌ Failed: ${failed}`);

        // Save report
        const reportPath = path.join(CONFIG.screenshotDir, 'test-report.json');
        fs.writeFileSync(reportPath, JSON.stringify({ results, summary: { successful, failed } }, null, 2));
        console.log(`\n📄 Report saved: ${reportPath}`);

    } catch (error) {
        console.error('❌ Fatal error:', error);
    } finally {
        await browser.close();
        console.log('\n✅ Testing complete!');
    }
}

runTests();
