/**
 * Cross-Browser Testing Suite
 *
 * Tests CMIS pages across multiple browsers (Chrome, Firefox, WebKit)
 * Compares rendering consistency and detects browser-specific issues
 *
 * Usage:
 *   node cross-browser-test.js
 *   node cross-browser-test.js --quick          # Test only key pages
 *   node cross-browser-test.js --browser chrome # Test single browser
 */

const { chromium, firefox, webkit } = require('playwright');
const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    outputDir: './test-results/cross-browser',
    timeout: 30000,
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    }
};

// Browser configurations
const BROWSERS = {
    chrome: {
        name: 'Chrome',
        launcher: chromium,
        options: {
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        }
    },
    firefox: {
        name: 'Firefox',
        launcher: firefox,
        options: {}
    },
    webkit: {
        name: 'Safari (WebKit)',
        launcher: webkit,
        options: {}
    }
};

// Key pages to test
const PAGES = [
    { path: '/login', name: 'Login', requiresAuth: false },
    { path: '/register', name: 'Register', requiresAuth: false },
    { path: '/orgs', name: 'Organizations', requiresAuth: true },
    { path: '/profile', name: 'Profile', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/dashboard', name: 'Dashboard', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns', name: 'Campaigns', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics', name: 'Analytics', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social', name: 'Social', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/automation', name: 'Automation', requiresAuth: true },
    { path: '/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms', name: 'Settings', requiresAuth: true }
];

// Test results
const results = {
    timestamp: new Date().toISOString(),
    browsers: {},
    summary: {
        totalTests: 0,
        passed: 0,
        failed: 0,
        browserIssues: []
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
    await page.goto(`${CONFIG.baseUrl}/login`, { waitUntil: 'networkidle', timeout: CONFIG.timeout });
    await page.fill('input[name="email"]', CONFIG.credentials.email);
    await page.fill('input[name="password"]', CONFIG.credentials.password);
    await page.click('button[type="submit"]');

    try {
        await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: CONFIG.timeout });
        return !page.url().includes('/login');
    } catch {
        return false;
    }
}

/**
 * Check for browser-specific issues
 */
async function checkBrowserIssues(page, browserName) {
    return await page.evaluate((browser) => {
        const issues = [];

        // Check for JavaScript errors (would be caught by error listener)
        // Check CSS feature support
        const cssFeatures = {
            'flexbox': CSS.supports('display', 'flex'),
            'grid': CSS.supports('display', 'grid'),
            'custom-properties': CSS.supports('color', 'var(--test)'),
            'scroll-snap': CSS.supports('scroll-snap-type', 'x mandatory'),
            'backdrop-filter': CSS.supports('backdrop-filter', 'blur(10px)'),
            'logical-properties': CSS.supports('margin-inline-start', '1px')
        };

        Object.entries(cssFeatures).forEach(([feature, supported]) => {
            if (!supported) {
                issues.push({
                    type: 'unsupported-css',
                    severity: 'medium',
                    message: `CSS feature "${feature}" not supported`,
                    browser
                });
            }
        });

        // Check for broken images
        const images = document.querySelectorAll('img');
        let brokenImages = 0;
        images.forEach(img => {
            if (!img.complete || img.naturalWidth === 0) {
                brokenImages++;
            }
        });

        if (brokenImages > 0) {
            issues.push({
                type: 'broken-images',
                severity: 'medium',
                message: `${brokenImages} broken image(s) detected`,
                browser
            });
        }

        // Check for console errors (this is a proxy check)
        // Check for layout issues
        const layoutMetrics = {
            docWidth: document.documentElement.scrollWidth,
            viewportWidth: document.documentElement.clientWidth,
            docHeight: document.documentElement.scrollHeight,
            viewportHeight: document.documentElement.clientHeight
        };

        // Check for RTL rendering
        const htmlDir = document.documentElement.dir;
        const locale = document.documentElement.lang;

        // Check for SVG rendering
        const svgs = document.querySelectorAll('svg');
        let brokenSvgs = 0;
        svgs.forEach(svg => {
            const bbox = svg.getBBox?.() || { width: 0, height: 0 };
            if (bbox.width === 0 && bbox.height === 0 && svg.innerHTML.trim()) {
                brokenSvgs++;
            }
        });

        if (brokenSvgs > 0) {
            issues.push({
                type: 'svg-rendering',
                severity: 'low',
                message: `${brokenSvgs} SVG(s) may not be rendering correctly`,
                browser
            });
        }

        return {
            issues,
            metrics: {
                layoutMetrics,
                htmlDir,
                locale,
                imagesTotal: images.length,
                brokenImages,
                svgsTotal: svgs.length,
                brokenSvgs,
                cssSupport: cssFeatures
            }
        };
    }, browserName);
}

/**
 * Test a page on a specific browser
 */
async function testPageOnBrowser(browser, browserConfig, pageInfo, locale, isLoggedIn) {
    const { path: pagePath, name: pageName } = pageInfo;
    const fullUrl = `${CONFIG.baseUrl}${pagePath}`;

    const testResult = {
        page: pageName,
        path: pagePath,
        browser: browserConfig.name,
        locale,
        status: 'pending',
        issues: [],
        metrics: {},
        screenshot: null,
        loadTime: 0
    };

    const context = await browser.newContext();
    const page = await context.newPage();

    // Capture console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    // Capture page errors
    page.on('pageerror', error => {
        consoleErrors.push(error.message);
    });

    try {
        await setLocale(context, locale);
        await page.setViewportSize({ width: 1920, height: 1080 });

        // Login if needed
        if (pageInfo.requiresAuth && !isLoggedIn) {
            const loginSuccess = await login(page);
            if (!loginSuccess) {
                testResult.status = 'skipped';
                testResult.error = 'Login required but failed';
                await context.close();
                return testResult;
            }
            await setLocale(context, locale);
        }

        const startTime = Date.now();
        const response = await page.goto(fullUrl, { waitUntil: 'networkidle', timeout: CONFIG.timeout });
        testResult.loadTime = Date.now() - startTime;
        testResult.statusCode = response?.status() || 0;

        // Wait for content
        await page.waitForTimeout(500);

        // Check browser issues
        const checkResult = await checkBrowserIssues(page, browserConfig.name);
        testResult.issues = checkResult.issues;
        testResult.metrics = checkResult.metrics;

        // Add console errors as issues
        if (consoleErrors.length > 0) {
            testResult.issues.push({
                type: 'console-errors',
                severity: 'high',
                message: `${consoleErrors.length} JavaScript error(s)`,
                details: consoleErrors.slice(0, 5) // First 5 errors
            });
        }

        // Take screenshot
        const screenshotDir = path.join(CONFIG.outputDir, 'screenshots', browserConfig.name.toLowerCase().replace(/[^a-z0-9]/g, '-'));
        if (!fs.existsSync(screenshotDir)) {
            fs.mkdirSync(screenshotDir, { recursive: true });
        }

        const screenshotName = `${pageName.toLowerCase().replace(/\s+/g, '-')}-${locale}.png`;
        testResult.screenshot = path.join(screenshotDir, screenshotName);
        await page.screenshot({ path: testResult.screenshot, fullPage: true });

        // Determine pass/fail
        const highSeverityIssues = testResult.issues.filter(i => i.severity === 'high');
        testResult.status = highSeverityIssues.length === 0 ? 'pass' : 'fail';

    } catch (error) {
        testResult.status = 'error';
        testResult.error = error.message;
    }

    await context.close();
    return testResult;
}

/**
 * Main test runner
 */
async function runTests(options = {}) {
    const { quick = false, browser: specificBrowser = null } = options;

    console.log('\n🌐 CMIS Cross-Browser Testing Suite');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`🌐 Base URL: ${CONFIG.baseUrl}`);
    console.log(`📁 Output: ${CONFIG.outputDir}`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

    // Create output directory
    if (!fs.existsSync(CONFIG.outputDir)) {
        fs.mkdirSync(CONFIG.outputDir, { recursive: true });
    }

    // Determine browsers to test
    let browsersToTest = BROWSERS;
    if (specificBrowser) {
        browsersToTest = { [specificBrowser]: BROWSERS[specificBrowser] };
    }

    // Determine pages to test
    let pagesToTest = quick ? PAGES.slice(0, 5) : PAGES;

    const locales = ['ar', 'en'];
    const totalTests = pagesToTest.length * Object.keys(browsersToTest).length * locales.length;

    console.log(`📊 Tests to run: ${totalTests}`);
    console.log(`   Browsers: ${Object.values(browsersToTest).map(b => b.name).join(', ')}`);
    console.log(`   Pages: ${pagesToTest.length}`);
    console.log(`   Locales: ${locales.join(', ')}\n`);

    let testNumber = 0;

    // Test each browser
    for (const [browserKey, browserConfig] of Object.entries(browsersToTest)) {
        console.log(`\n🔷 Testing on ${browserConfig.name}`);
        console.log('─'.repeat(40));

        results.browsers[browserKey] = {
            name: browserConfig.name,
            tests: 0,
            passed: 0,
            failed: 0
        };

        let browser;
        try {
            browser = await browserConfig.launcher.launch(browserConfig.options);
        } catch (error) {
            console.log(`   ❌ Failed to launch ${browserConfig.name}: ${error.message}`);
            continue;
        }

        // Login once per browser
        let isLoggedIn = false;
        try {
            const context = await browser.newContext();
            const page = await context.newPage();
            await setLocale(context, 'en');
            isLoggedIn = await login(page);
            await context.close();
            console.log(isLoggedIn ? '   🔐 Login successful' : '   ⚠️  Login failed - will skip auth pages');
        } catch (error) {
            console.log(`   ⚠️  Login error: ${error.message}`);
        }

        for (const pageInfo of pagesToTest) {
            if (pageInfo.requiresAuth && !isLoggedIn) {
                continue;
            }

            for (const locale of locales) {
                testNumber++;
                const progress = `[${testNumber}/${totalTests}]`;
                process.stdout.write(`   ${progress} ${pageInfo.name} (${locale})... `);

                const testResult = await testPageOnBrowser(browser, browserConfig, pageInfo, locale, isLoggedIn);
                results.pages.push(testResult);
                results.summary.totalTests++;
                results.browsers[browserKey].tests++;

                if (testResult.status === 'pass') {
                    results.summary.passed++;
                    results.browsers[browserKey].passed++;
                    console.log(`✅ (${testResult.loadTime}ms)`);
                } else if (testResult.status === 'fail') {
                    results.summary.failed++;
                    results.browsers[browserKey].failed++;
                    console.log(`❌ (${testResult.issues.length} issues)`);
                } else if (testResult.status === 'skipped') {
                    console.log(`⏭️  ${testResult.error}`);
                } else {
                    results.summary.failed++;
                    results.browsers[browserKey].failed++;
                    console.log(`💥 ${testResult.error}`);
                }
            }
        }

        await browser.close();
    }

    // Aggregate browser-specific issues
    results.pages.forEach(p => {
        p.issues.forEach(issue => {
            results.summary.browserIssues.push({
                ...issue,
                page: p.page,
                path: p.path,
                browser: p.browser,
                locale: p.locale
            });
        });
    });

    // Save results
    const jsonPath = path.join(CONFIG.outputDir, 'results.json');
    fs.writeFileSync(jsonPath, JSON.stringify(results, null, 2));

    // Generate report
    generateReport();

    // Print summary
    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('📊 Cross-Browser Test Summary');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`   Total Tests: ${results.summary.totalTests}`);
    console.log(`   ✅ Passed: ${results.summary.passed}`);
    console.log(`   ❌ Failed: ${results.summary.failed}`);
    console.log(`   📈 Pass Rate: ${((results.summary.passed / results.summary.totalTests) * 100).toFixed(1)}%\n`);

    console.log('   By Browser:');
    Object.values(results.browsers).forEach(b => {
        const rate = b.tests > 0 ? ((b.passed / b.tests) * 100).toFixed(1) : 0;
        console.log(`   - ${b.name}: ${b.passed}/${b.tests} (${rate}%)`);
    });

    console.log(`\n   📝 Report: ${path.join(CONFIG.outputDir, 'REPORT.md')}`);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

    return results;
}

/**
 * Generate markdown report
 */
function generateReport() {
    let report = `# Cross-Browser Testing Report\n\n`;
    report += `**Generated:** ${results.timestamp}\n`;
    report += `**Base URL:** ${CONFIG.baseUrl}\n\n`;

    // Summary
    report += `## Summary\n\n`;
    report += `| Browser | Tests | Passed | Failed | Pass Rate |\n`;
    report += `|---------|-------|--------|--------|----------|\n`;

    Object.values(results.browsers).forEach(b => {
        const rate = b.tests > 0 ? ((b.passed / b.tests) * 100).toFixed(1) : 0;
        const icon = b.failed === 0 ? '✅' : '⚠️';
        report += `| ${icon} ${b.name} | ${b.tests} | ${b.passed} | ${b.failed} | ${rate}% |\n`;
    });

    report += `\n**Total:** ${results.summary.totalTests} tests, ${results.summary.passed} passed, ${results.summary.failed} failed\n\n`;

    // Browser-specific issues
    if (results.summary.browserIssues.length > 0) {
        report += `## Issues by Browser\n\n`;

        const issuesByBrowser = {};
        results.summary.browserIssues.forEach(issue => {
            if (!issuesByBrowser[issue.browser]) {
                issuesByBrowser[issue.browser] = [];
            }
            issuesByBrowser[issue.browser].push(issue);
        });

        for (const [browser, issues] of Object.entries(issuesByBrowser)) {
            report += `### ${browser} (${issues.length} issues)\n\n`;

            const issuesByType = {};
            issues.forEach(issue => {
                if (!issuesByType[issue.type]) {
                    issuesByType[issue.type] = [];
                }
                issuesByType[issue.type].push(issue);
            });

            for (const [type, typeIssues] of Object.entries(issuesByType)) {
                report += `**${type}:** ${typeIssues.length} occurrences\n`;
                typeIssues.slice(0, 3).forEach(issue => {
                    report += `- ${issue.page} (${issue.locale}): ${issue.message}\n`;
                });
                if (typeIssues.length > 3) {
                    report += `- ... and ${typeIssues.length - 3} more\n`;
                }
                report += `\n`;
            }
        }
    }

    // Page results
    report += `## Results by Page\n\n`;

    const pageGroups = {};
    results.pages.forEach(p => {
        const key = `${p.page}-${p.locale}`;
        if (!pageGroups[key]) {
            pageGroups[key] = { page: p.page, locale: p.locale, browsers: {} };
        }
        pageGroups[key].browsers[p.browser] = p;
    });

    report += `| Page | Locale | Chrome | Firefox | Safari |\n`;
    report += `|------|--------|--------|---------|--------|\n`;

    Object.values(pageGroups).forEach(group => {
        const chrome = group.browsers['Chrome'];
        const firefox = group.browsers['Firefox'];
        const safari = group.browsers['Safari (WebKit)'];

        const getStatus = (r) => {
            if (!r) return '-';
            if (r.status === 'pass') return '✅';
            if (r.status === 'fail') return '❌';
            return '💥';
        };

        report += `| ${group.page} | ${group.locale} | ${getStatus(chrome)} | ${getStatus(firefox)} | ${getStatus(safari)} |\n`;
    });

    report += `\n`;

    const reportPath = path.join(CONFIG.outputDir, 'REPORT.md');
    fs.writeFileSync(reportPath, report);
}

// CLI
if (require.main === module) {
    const args = process.argv.slice(2);
    const options = {
        quick: args.includes('--quick'),
        browser: args.includes('--browser') ? args[args.indexOf('--browser') + 1] : null
    };

    runTests(options)
        .then(() => process.exit(0))
        .catch(err => {
            console.error(err);
            process.exit(1);
        });
}

module.exports = { runTests, BROWSERS, PAGES };
