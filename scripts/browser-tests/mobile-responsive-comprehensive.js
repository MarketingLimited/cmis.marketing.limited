/**
 * Comprehensive Mobile Responsive Testing Suite
 *
 * Tests CMIS pages across multiple device sizes and both locales (AR/EN)
 * Checks for common mobile issues:
 * - Horizontal overflow
 * - Touch target sizes
 * - Font sizes
 * - RTL/LTR layout rendering
 *
 * Usage:
 *   node mobile-responsive-comprehensive.js
 *   node mobile-responsive-comprehensive.js --quick     # Test only 5 key pages
 *   node mobile-responsive-comprehensive.js --pages 10  # Test first 10 pages
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    outputDir: './test-results/mobile-responsive',
    timeout: 30000,
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    }
};

// Device configurations
const DEVICES = {
    iPhoneSE: { width: 375, height: 667, name: 'iPhone SE', type: 'mobile' },
    iPhone14: { width: 390, height: 844, name: 'iPhone 14', type: 'mobile' },
    iPhone14ProMax: { width: 430, height: 932, name: 'iPhone 14 Pro Max', type: 'mobile' },
    Pixel7: { width: 412, height: 915, name: 'Pixel 7', type: 'mobile' },
    GalaxyS21: { width: 360, height: 800, name: 'Galaxy S21', type: 'mobile' },
    iPadMini: { width: 768, height: 1024, name: 'iPad Mini', type: 'tablet' },
    iPadPro: { width: 1024, height: 1366, name: 'iPad Pro 12.9"', type: 'tablet' }
};

// Key pages to test
const PAGES = [
    { path: '/login', name: 'Login', requiresAuth: false },
    { path: '/register', name: 'Register', requiresAuth: false },
    { path: '/orgs', name: 'Organizations List', requiresAuth: true },
    { path: '/profile', name: 'User Profile', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/dashboard', name: 'Dashboard', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns', name: 'Campaigns', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns/create', name: 'Create Campaign', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics', name: 'Analytics', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social', name: 'Social Media', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/assets', name: 'Creative Assets', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/automation', name: 'Automation', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms', name: 'Platform Settings', requiresAuth: true }
];

// Test results
const results = {
    timestamp: new Date().toISOString(),
    summary: {
        totalTests: 0,
        passed: 0,
        failed: 0,
        issues: []
    },
    pages: []
};

/**
 * Set locale cookie
 */
async function setLocale(context, locale) {
    await context.addCookies([{
        name: 'app_locale',
        value: locale,
        domain: new URL(CONFIG.baseUrl).hostname,
        path: '/'
    }]);
}

/**
 * Login to the application
 */
async function login(page) {
    console.log('   🔐 Logging in...');

    await page.goto(`${CONFIG.baseUrl}/login`, { waitUntil: 'networkidle', timeout: CONFIG.timeout });
    await page.fill('input[name="email"]', CONFIG.credentials.email);
    await page.fill('input[name="password"]', CONFIG.credentials.password);
    await page.click('button[type="submit"]');

    await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: CONFIG.timeout });

    const success = !page.url().includes('/login');
    console.log(success ? '   ✅ Login successful' : '   ❌ Login failed');
    return success;
}

/**
 * Check for mobile-specific issues
 */
async function checkMobileIssues(page, deviceName) {
    return await page.evaluate((device) => {
        const issues = [];

        // Check for horizontal overflow
        if (document.documentElement.scrollWidth > document.documentElement.clientWidth) {
            const overflow = document.documentElement.scrollWidth - document.documentElement.clientWidth;
            issues.push({
                type: 'horizontal-overflow',
                severity: 'high',
                message: `Horizontal overflow detected: ${overflow}px extra width`,
                device
            });
        }

        // Check touch targets (buttons and links)
        const interactives = document.querySelectorAll('a, button, [role="button"], input[type="submit"]');
        let smallTouchTargets = 0;

        interactives.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                if (rect.width < 44 || rect.height < 44) {
                    smallTouchTargets++;
                }
            }
        });

        if (smallTouchTargets > 0) {
            issues.push({
                type: 'small-touch-targets',
                severity: 'medium',
                message: `${smallTouchTargets} interactive elements have touch targets smaller than 44x44px`,
                device
            });
        }

        // Check font sizes
        const textElements = document.querySelectorAll('p, span, a, li, td, th, label');
        let smallFonts = 0;

        textElements.forEach(el => {
            const fontSize = parseFloat(window.getComputedStyle(el).fontSize);
            if (fontSize < 12) {
                smallFonts++;
            }
        });

        if (smallFonts > 10) {
            issues.push({
                type: 'small-fonts',
                severity: 'medium',
                message: `${smallFonts} text elements have font size smaller than 12px`,
                device
            });
        }

        // Check RTL/LTR consistency
        const htmlDir = document.documentElement.dir;
        const locale = document.documentElement.lang;
        const expectedDir = locale === 'ar' ? 'rtl' : 'ltr';

        if (htmlDir !== expectedDir) {
            issues.push({
                type: 'direction-mismatch',
                severity: 'high',
                message: `Direction mismatch: lang="${locale}" but dir="${htmlDir}"`,
                device
            });
        }

        // Check for viewport meta tag
        const viewportMeta = document.querySelector('meta[name="viewport"]');
        if (!viewportMeta) {
            issues.push({
                type: 'missing-viewport',
                severity: 'high',
                message: 'Missing viewport meta tag',
                device
            });
        } else {
            const content = viewportMeta.getAttribute('content') || '';
            if (!content.includes('width=device-width')) {
                issues.push({
                    type: 'improper-viewport',
                    severity: 'medium',
                    message: 'Viewport meta tag missing "width=device-width"',
                    device
                });
            }
        }

        return {
            issues,
            metrics: {
                scrollWidth: document.documentElement.scrollWidth,
                clientWidth: document.documentElement.clientWidth,
                interactiveElements: interactives.length,
                smallTouchTargets,
                smallFonts,
                htmlDir,
                locale
            }
        };
    }, deviceName);
}

/**
 * Test a single page on a specific device and locale
 */
async function testPageOnDevice(context, pageInfo, device, locale) {
    const page = await context.newPage();
    const { path: pagePath, name: pageName } = pageInfo;
    const fullUrl = `${CONFIG.baseUrl}${pagePath}`;

    const testResult = {
        page: pageName,
        path: pagePath,
        device: device.name,
        deviceType: device.type,
        locale,
        viewport: `${device.width}x${device.height}`,
        status: 'pending',
        issues: [],
        screenshot: null
    };

    try {
        await setLocale(context, locale);
        await page.setViewportSize({ width: device.width, height: device.height });

        const response = await page.goto(fullUrl, { waitUntil: 'networkidle', timeout: CONFIG.timeout });
        testResult.statusCode = response?.status() || 0;

        // Wait for content to stabilize
        await page.waitForTimeout(500);

        // Check for mobile issues
        const checkResult = await checkMobileIssues(page, device.name);
        testResult.issues = checkResult.issues;
        testResult.metrics = checkResult.metrics;

        // Take screenshot
        const screenshotDir = path.join(CONFIG.outputDir, 'screenshots');
        if (!fs.existsSync(screenshotDir)) {
            fs.mkdirSync(screenshotDir, { recursive: true });
        }

        const screenshotName = `${pageName.toLowerCase().replace(/\s+/g, '-')}-${device.name.toLowerCase().replace(/\s+/g, '-')}-${locale}.png`;
        const screenshotPath = path.join(screenshotDir, screenshotName);

        await page.screenshot({ path: screenshotPath, fullPage: true });
        testResult.screenshot = screenshotPath;

        // Determine pass/fail
        const highSeverityIssues = testResult.issues.filter(i => i.severity === 'high');
        testResult.status = highSeverityIssues.length === 0 ? 'pass' : 'fail';

    } catch (error) {
        testResult.status = 'error';
        testResult.error = error.message;
    }

    await page.close();
    return testResult;
}

/**
 * Main test runner
 */
async function runTests(options = {}) {
    const { quick = false, pages: pageLimit = null } = options;

    console.log('\n📱 CMIS Mobile Responsive Testing Suite');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`🌐 Base URL: ${CONFIG.baseUrl}`);
    console.log(`📁 Output: ${CONFIG.outputDir}`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

    // Create output directory
    if (!fs.existsSync(CONFIG.outputDir)) {
        fs.mkdirSync(CONFIG.outputDir, { recursive: true });
    }

    // Determine which pages to test
    let pagesToTest = PAGES;
    if (quick) {
        pagesToTest = PAGES.slice(0, 5);
    } else if (pageLimit) {
        pagesToTest = PAGES.slice(0, pageLimit);
    }

    // Determine which devices to test (fewer for quick mode)
    const devicesToTest = quick
        ? { iPhoneSE: DEVICES.iPhoneSE, iPadMini: DEVICES.iPadMini }
        : DEVICES;

    const locales = ['ar', 'en'];

    const totalTests = pagesToTest.length * Object.keys(devicesToTest).length * locales.length;
    console.log(`📊 Tests to run: ${totalTests}`);
    console.log(`   Pages: ${pagesToTest.length}`);
    console.log(`   Devices: ${Object.keys(devicesToTest).length}`);
    console.log(`   Locales: ${locales.length}\n`);

    // Launch browser
    const browser = await chromium.launch({
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext();

    // Login once
    const loginPage = await context.newPage();
    await setLocale(context, 'en');
    const loginSuccess = await login(loginPage);
    await loginPage.close();

    if (!loginSuccess) {
        console.log('❌ Login failed - testing only guest pages');
    }

    // Run tests
    let testNumber = 0;
    for (const pageInfo of pagesToTest) {
        if (pageInfo.requiresAuth && !loginSuccess) {
            console.log(`⏭️  Skipping ${pageInfo.name} (requires auth)`);
            continue;
        }

        console.log(`\n📄 Testing: ${pageInfo.name}`);

        for (const [deviceKey, device] of Object.entries(devicesToTest)) {
            for (const locale of locales) {
                testNumber++;
                const progress = `[${testNumber}/${totalTests}]`;
                process.stdout.write(`   ${progress} ${device.name} (${locale})... `);

                const testResult = await testPageOnDevice(context, pageInfo, device, locale);
                results.pages.push(testResult);
                results.summary.totalTests++;

                if (testResult.status === 'pass') {
                    results.summary.passed++;
                    console.log('✅');
                } else if (testResult.status === 'fail') {
                    results.summary.failed++;
                    console.log(`❌ (${testResult.issues.length} issues)`);
                    testResult.issues.forEach(issue => {
                        console.log(`      ⚠️  ${issue.message}`);
                    });
                } else {
                    results.summary.failed++;
                    console.log(`💥 Error: ${testResult.error}`);
                }
            }
        }
    }

    await browser.close();

    // Aggregate all issues
    results.pages.forEach(p => {
        p.issues.forEach(issue => {
            if (!results.summary.issues.some(i =>
                i.type === issue.type && i.page === p.page && i.device === p.device
            )) {
                results.summary.issues.push({
                    ...issue,
                    page: p.page,
                    path: p.path,
                    locale: p.locale
                });
            }
        });
    });

    // Save results
    const jsonPath = path.join(CONFIG.outputDir, 'results.json');
    fs.writeFileSync(jsonPath, JSON.stringify(results, null, 2));

    // Generate report
    generateReport();

    // Print summary
    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('📊 Test Summary');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`   Total Tests: ${results.summary.totalTests}`);
    console.log(`   ✅ Passed: ${results.summary.passed}`);
    console.log(`   ❌ Failed: ${results.summary.failed}`);
    console.log(`   📈 Pass Rate: ${((results.summary.passed / results.summary.totalTests) * 100).toFixed(1)}%`);
    console.log(`\n   📝 Report: ${path.join(CONFIG.outputDir, 'REPORT.md')}`);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

    return results;
}

/**
 * Generate markdown report
 */
function generateReport() {
    let report = `# Mobile Responsive Testing Report\n\n`;
    report += `**Generated:** ${results.timestamp}\n`;
    report += `**Base URL:** ${CONFIG.baseUrl}\n\n`;

    // Summary
    report += `## Summary\n\n`;
    report += `| Metric | Value |\n`;
    report += `|--------|-------|\n`;
    report += `| Total Tests | ${results.summary.totalTests} |\n`;
    report += `| Passed | ${results.summary.passed} |\n`;
    report += `| Failed | ${results.summary.failed} |\n`;
    report += `| Pass Rate | ${((results.summary.passed / results.summary.totalTests) * 100).toFixed(1)}% |\n\n`;

    // Issues by type
    if (results.summary.issues.length > 0) {
        report += `## Issues Found\n\n`;

        const issuesByType = {};
        results.summary.issues.forEach(issue => {
            if (!issuesByType[issue.type]) {
                issuesByType[issue.type] = [];
            }
            issuesByType[issue.type].push(issue);
        });

        for (const [type, issues] of Object.entries(issuesByType)) {
            report += `### ${type.replace(/-/g, ' ').toUpperCase()} (${issues.length})\n\n`;
            issues.forEach(issue => {
                report += `- **${issue.page}** (${issue.device}, ${issue.locale}): ${issue.message}\n`;
            });
            report += `\n`;
        }
    }

    // Results by page
    report += `## Results by Page\n\n`;

    const pageGroups = {};
    results.pages.forEach(p => {
        if (!pageGroups[p.page]) {
            pageGroups[p.page] = [];
        }
        pageGroups[p.page].push(p);
    });

    for (const [page, pageResults] of Object.entries(pageGroups)) {
        const passed = pageResults.filter(r => r.status === 'pass').length;
        const total = pageResults.length;
        const icon = passed === total ? '✅' : '⚠️';

        report += `### ${icon} ${page}\n\n`;
        report += `| Device | Type | Locale | Status | Issues |\n`;
        report += `|--------|------|--------|--------|--------|\n`;

        pageResults.forEach(r => {
            const status = r.status === 'pass' ? '✅' : r.status === 'fail' ? '❌' : '💥';
            const issues = r.issues.length > 0 ? r.issues.map(i => i.type).join(', ') : '-';
            report += `| ${r.device} | ${r.deviceType} | ${r.locale} | ${status} | ${issues} |\n`;
        });

        report += `\n`;
    }

    const reportPath = path.join(CONFIG.outputDir, 'REPORT.md');
    fs.writeFileSync(reportPath, report);
}

// CLI
if (require.main === module) {
    const args = process.argv.slice(2);
    const options = {
        quick: args.includes('--quick'),
        pages: args.includes('--pages') ? parseInt(args[args.indexOf('--pages') + 1]) : null
    };

    runTests(options)
        .then(() => process.exit(0))
        .catch(err => {
            console.error(err);
            process.exit(1);
        });
}

module.exports = { runTests, CONFIG, DEVICES, PAGES };
