/**
 * CMIS Platform Comprehensive Testing Script
 * Tests all features and generates detailed report
 */

const puppeteer = require('puppeteer');
const fs = require('fs').promises;
const path = require('path');

// Configuration
const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    adminEmail: 'admin@cmis.test',
    adminPassword: 'password', // Common default Laravel password
    screenshotDir: './test-results/screenshots',
    reportFile: './test-results/comprehensive-test-report.json',
    timeout: 30000,
    viewport: {
        width: 1920,
        height: 1080
    }
};

// Test results storage
const testResults = {
    timestamp: new Date().toISOString(),
    summary: {
        total: 0,
        passed: 0,
        failed: 0,
        warnings: 0
    },
    tests: [],
    issues: [],
    screenshots: []
};

// Utility functions
async function takeScreenshot(page, name) {
    const timestamp = Date.now();
    const filename = `${name}-${timestamp}.png`;
    const filepath = path.join(CONFIG.screenshotDir, filename);
    await page.screenshot({ path: filepath, fullPage: true });
    testResults.screenshots.push({ name, filepath, timestamp });
    return filepath;
}

function logTest(category, name, status, details = {}) {
    const test = {
        category,
        name,
        status, // 'pass', 'fail', 'warning'
        details,
        timestamp: new Date().toISOString()
    };
    testResults.tests.push(test);
    testResults.summary.total++;
    testResults.summary[status === 'pass' ? 'passed' : status === 'fail' ? 'failed' : 'warnings']++;
    console.log(`[${status.toUpperCase()}] ${category} > ${name}`);
    if (details.message) console.log(`  ${details.message}`);
}

function logIssue(severity, category, description, recommendation) {
    const issue = {
        severity, // 'critical', 'high', 'medium', 'low'
        category,
        description,
        recommendation,
        timestamp: new Date().toISOString()
    };
    testResults.issues.push(issue);
    console.log(`[ISSUE-${severity.toUpperCase()}] ${category}: ${description}`);
}

async function waitForPageLoad(page, timeout = CONFIG.timeout) {
    try {
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout });
        return true;
    } catch (error) {
        return false;
    }
}

// Test Suites
async function testAuthentication(browser) {
    console.log('\n=== Testing Authentication ===');
    const page = await browser.newPage();
    await page.setViewport(CONFIG.viewport);

    try {
        // Test 1: Login page loads
        await page.goto(`${CONFIG.baseUrl}/login`, { waitUntil: 'networkidle2' });
        await takeScreenshot(page, 'auth-login-page');

        const loginFormExists = await page.$('form') !== null;
        if (loginFormExists) {
            logTest('Authentication', 'Login page loads', 'pass');
        } else {
            logTest('Authentication', 'Login page loads', 'fail', { message: 'Login form not found' });
            logIssue('critical', 'Authentication', 'Login form missing on login page', 'Check login blade template');
        }

        // Test 2: Login with admin credentials
        const emailInput = await page.$('input[name="email"], input[type="email"]');
        const passwordInput = await page.$('input[name="password"], input[type="password"]');

        if (emailInput && passwordInput) {
            await emailInput.type(CONFIG.adminEmail);
            await passwordInput.type(CONFIG.adminPassword);
            await takeScreenshot(page, 'auth-credentials-entered');

            // Click login button
            const loginButton = await page.$('button[type="submit"]');
            if (loginButton) {
                await loginButton.click();
                await new Promise(resolve => setTimeout(resolve, 3000));
                await takeScreenshot(page, 'auth-after-login-attempt');

                const currentUrl = page.url();
                if (currentUrl.includes('/dashboard') || currentUrl.includes('/home')) {
                    logTest('Authentication', 'Admin login successful', 'pass');
                    return { page, success: true };
                } else if (currentUrl.includes('/login')) {
                    // Check for error messages
                    const errorMessage = await page.$eval('body', el => el.textContent).catch(() => '');
                    logTest('Authentication', 'Admin login', 'fail', {
                        message: `Login failed - still on login page. Error: ${errorMessage.substring(0, 200)}`
                    });
                    logIssue('critical', 'Authentication',
                        'Admin login failed with default credentials',
                        'Verify admin user exists and password is correct. Check authentication logic.');
                    return { page, success: false };
                }
            } else {
                logTest('Authentication', 'Login button found', 'fail', { message: 'Login button not found' });
                logIssue('critical', 'Authentication', 'Login button missing', 'Add submit button to login form');
            }
        } else {
            logTest('Authentication', 'Login form inputs', 'fail', { message: 'Email or password input not found' });
            logIssue('critical', 'Authentication', 'Login form inputs missing', 'Check form input fields');
        }

    } catch (error) {
        logTest('Authentication', 'Authentication flow', 'fail', { message: error.message });
        logIssue('critical', 'Authentication', `Authentication test error: ${error.message}`, 'Debug authentication flow');
        return { page, success: false };
    }
}

async function testDashboard(page) {
    console.log('\n=== Testing Dashboard ===');

    try {
        await page.goto(`${CONFIG.baseUrl}/dashboard`, { waitUntil: 'networkidle2', timeout: CONFIG.timeout });
        await takeScreenshot(page, 'dashboard-main');

        // Test dashboard elements
        const title = await page.title();
        logTest('Dashboard', 'Dashboard page loads', title ? 'pass' : 'fail', { title });

        // Check for common dashboard elements
        const elements = {
            navigation: await page.$('nav, [role="navigation"]'),
            header: await page.$('header'),
            sidebar: await page.$('aside, .sidebar'),
            mainContent: await page.$('main, .main-content')
        };

        Object.entries(elements).forEach(([name, element]) => {
            if (element) {
                logTest('Dashboard', `${name} present`, 'pass');
            } else {
                logTest('Dashboard', `${name} present`, 'warning', { message: `${name} not found` });
                logIssue('medium', 'Dashboard', `${name} element missing`, `Add ${name} to dashboard layout`);
            }
        });

        // Check for metrics/widgets
        const widgets = await page.$$('[class*="widget"], [class*="card"], [class*="metric"]');
        logTest('Dashboard', 'Dashboard widgets', widgets.length > 0 ? 'pass' : 'warning',
            { count: widgets.length, message: `Found ${widgets.length} widgets` });

        if (widgets.length === 0) {
            logIssue('medium', 'Dashboard', 'No dashboard widgets found', 'Add dashboard widgets for key metrics');
        }

    } catch (error) {
        logTest('Dashboard', 'Dashboard test', 'fail', { message: error.message });
        logIssue('high', 'Dashboard', `Dashboard error: ${error.message}`, 'Debug dashboard loading');
    }
}

async function testNavigation(page) {
    console.log('\n=== Testing Navigation ===');

    const menuItems = [
        { name: 'Dashboard', url: '/dashboard' },
        { name: 'Campaigns', url: '/campaigns' },
        { name: 'Users', url: '/users' },
        { name: 'Social', url: '/social' },
        { name: 'Analytics', url: '/analytics' },
        { name: 'Reports', url: '/reports' },
        { name: 'Settings', url: '/settings' },
    ];

    for (const item of menuItems) {
        try {
            const fullUrl = `${CONFIG.baseUrl}${item.url}`;
            const response = await page.goto(fullUrl, { waitUntil: 'networkidle2', timeout: 10000 });
            const status = response.status();

            if (status === 200) {
                await takeScreenshot(page, `nav-${item.name.toLowerCase()}`);
                logTest('Navigation', `${item.name} page accessible`, 'pass', { url: item.url, status });
            } else if (status === 404) {
                logTest('Navigation', `${item.name} page accessible`, 'fail', { url: item.url, status });
                logIssue('high', 'Navigation', `${item.name} page returns 404`, `Implement ${item.name} page`);
            } else if (status === 403) {
                logTest('Navigation', `${item.name} page accessible`, 'warning', { url: item.url, status, message: 'Forbidden - may require permissions' });
            } else {
                logTest('Navigation', `${item.name} page accessible`, 'warning', { url: item.url, status });
            }

            // Check for console errors
            const consoleErrors = [];
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    consoleErrors.push(msg.text());
                }
            });

            await new Promise(resolve => setTimeout(resolve, 1000));

            if (consoleErrors.length > 0) {
                logIssue('medium', 'Navigation', `JavaScript errors on ${item.name} page`, `Fix console errors: ${consoleErrors.join(', ')}`);
            }

        } catch (error) {
            logTest('Navigation', `${item.name} page accessible`, 'fail', { url: item.url, message: error.message });
            logIssue('high', 'Navigation', `Failed to load ${item.name} page: ${error.message}`, `Debug ${item.name} page routing and rendering`);
        }
    }
}

async function testI18n(page) {
    console.log('\n=== Testing Internationalization (i18n) ===');

    try {
        // Test Arabic (RTL)
        await page.goto(`${CONFIG.baseUrl}/dashboard?lang=ar`, { waitUntil: 'networkidle2' });
        await takeScreenshot(page, 'i18n-arabic-rtl');

        const htmlDir = await page.$eval('html', el => el.getAttribute('dir'));
        const htmlLang = await page.$eval('html', el => el.getAttribute('lang'));

        if (htmlDir === 'rtl' && htmlLang === 'ar') {
            logTest('i18n', 'Arabic RTL support', 'pass', { dir: htmlDir, lang: htmlLang });
        } else {
            logTest('i18n', 'Arabic RTL support', 'fail', { dir: htmlDir, lang: htmlLang });
            logIssue('high', 'i18n', 'Arabic RTL not properly configured', 'Set html dir="rtl" and lang="ar" for Arabic');
        }

        // Test English (LTR)
        await page.goto(`${CONFIG.baseUrl}/dashboard?lang=en`, { waitUntil: 'networkidle2' });
        await takeScreenshot(page, 'i18n-english-ltr');

        const htmlDirEn = await page.$eval('html', el => el.getAttribute('dir'));
        const htmlLangEn = await page.$eval('html', el => el.getAttribute('lang'));

        if (htmlDirEn === 'ltr' && htmlLangEn === 'en') {
            logTest('i18n', 'English LTR support', 'pass', { dir: htmlDirEn, lang: htmlLangEn });
        } else {
            logTest('i18n', 'English LTR support', 'fail', { dir: htmlDirEn, lang: htmlLangEn });
            logIssue('high', 'i18n', 'English LTR not properly configured', 'Set html dir="ltr" and lang="en" for English');
        }

        // Test language switcher
        const languageSwitcher = await page.$('[id*="language"], [class*="language"], [data-lang]');
        if (languageSwitcher) {
            logTest('i18n', 'Language switcher present', 'pass');
        } else {
            logTest('i18n', 'Language switcher present', 'warning');
            logIssue('medium', 'i18n', 'Language switcher not found', 'Add language switcher component');
        }

    } catch (error) {
        logTest('i18n', 'i18n test', 'fail', { message: error.message });
        logIssue('high', 'i18n', `i18n test error: ${error.message}`, 'Debug i18n implementation');
    }
}

async function testResponsiveDesign(page) {
    console.log('\n=== Testing Responsive Design ===');

    const viewports = [
        { name: 'Mobile', width: 375, height: 667 },
        { name: 'Tablet', width: 768, height: 1024 },
        { name: 'Desktop', width: 1920, height: 1080 }
    ];

    for (const viewport of viewports) {
        try {
            await page.setViewport(viewport);
            await page.goto(`${CONFIG.baseUrl}/dashboard`, { waitUntil: 'networkidle2' });
            await takeScreenshot(page, `responsive-${viewport.name.toLowerCase()}`);
            logTest('Responsive', `${viewport.name} layout (${viewport.width}x${viewport.height})`, 'pass');
        } catch (error) {
            logTest('Responsive', `${viewport.name} layout`, 'fail', { message: error.message });
            logIssue('medium', 'Responsive', `${viewport.name} layout error`, 'Test responsive design');
        }
    }
}

async function testUserManagement(page) {
    console.log('\n=== Testing User Management ===');

    try {
        await page.goto(`${CONFIG.baseUrl}/users`, { waitUntil: 'networkidle2', timeout: CONFIG.timeout });
        await takeScreenshot(page, 'users-index');

        // Check for user list/table
        const userTable = await page.$('table, [role="table"], .user-list');
        if (userTable) {
            logTest('Users', 'User list displays', 'pass');
        } else {
            logTest('Users', 'User list displays', 'warning');
            logIssue('medium', 'Users', 'User list/table not found', 'Implement user list view');
        }

        // Check for create user button
        const createButton = await page.$('[data-testid*="create"], [data-testid*="add"], a[href*="create"], button[type="button"].bg-blue-600, button[type="button"].bg-green-600');
        if (createButton) {
            logTest('Users', 'Create user button present', 'pass');
        } else {
            logTest('Users', 'Create user button present', 'warning');
            logIssue('low', 'Users', 'Create user button not found', 'Add create user button');
        }

    } catch (error) {
        logTest('Users', 'User management test', 'fail', { message: error.message });
        logIssue('medium', 'Users', `User management error: ${error.message}`, 'Debug user management');
    }
}

async function testCampaigns(page) {
    console.log('\n=== Testing Campaign Management ===');

    try {
        await page.goto(`${CONFIG.baseUrl}/campaigns`, { waitUntil: 'networkidle2', timeout: CONFIG.timeout });
        await takeScreenshot(page, 'campaigns-index');

        const pageTitle = await page.title();
        logTest('Campaigns', 'Campaigns page loads', 'pass', { title: pageTitle });

        // Check for campaign list
        const campaignList = await page.$('table, [role="table"], .campaign-list, [data-campaigns]');
        if (campaignList) {
            logTest('Campaigns', 'Campaign list present', 'pass');
        } else {
            logTest('Campaigns', 'Campaign list present', 'warning');
            logIssue('high', 'Campaigns', 'Campaign list not found', 'Implement campaign list view');
        }

        // Try to access create campaign
        try {
            await page.goto(`${CONFIG.baseUrl}/campaigns/create`, { waitUntil: 'networkidle2', timeout: 10000 });
            await takeScreenshot(page, 'campaigns-create');
            logTest('Campaigns', 'Create campaign page accessible', 'pass');
        } catch (error) {
            logTest('Campaigns', 'Create campaign page accessible', 'fail', { message: error.message });
            logIssue('high', 'Campaigns', 'Create campaign page not accessible', 'Implement campaign creation');
        }

    } catch (error) {
        logTest('Campaigns', 'Campaign management test', 'fail', { message: error.message });
        logIssue('high', 'Campaigns', `Campaign test error: ${error.message}`, 'Debug campaign management');
    }
}

async function testSocialMedia(page) {
    console.log('\n=== Testing Social Media Features ===');

    try {
        await page.goto(`${CONFIG.baseUrl}/social`, { waitUntil: 'networkidle2', timeout: CONFIG.timeout });
        await takeScreenshot(page, 'social-main');

        logTest('Social', 'Social page accessible', 'pass');

        // Test social platforms
        const platforms = ['posts', 'schedule', 'analytics', 'library'];
        for (const platform of platforms) {
            try {
                await page.goto(`${CONFIG.baseUrl}/social/${platform}`, { waitUntil: 'networkidle2', timeout: 10000 });
                await takeScreenshot(page, `social-${platform}`);
                logTest('Social', `Social ${platform} page`, 'pass');
            } catch (error) {
                logTest('Social', `Social ${platform} page`, 'warning', { message: error.message });
                logIssue('medium', 'Social', `Social ${platform} not implemented`, `Implement social ${platform} feature`);
            }
        }

    } catch (error) {
        logTest('Social', 'Social media test', 'fail', { message: error.message });
        logIssue('medium', 'Social', `Social media error: ${error.message}`, 'Debug social media features');
    }
}

async function testPlatformIntegrations(page) {
    console.log('\n=== Testing Platform Integrations ===');

    const platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];

    for (const platform of platforms) {
        try {
            await page.goto(`${CONFIG.baseUrl}/integrations/${platform}`, { waitUntil: 'networkidle2', timeout: 10000 });
            await takeScreenshot(page, `integration-${platform}`);

            const response = await page.goto(`${CONFIG.baseUrl}/integrations/${platform}`, { waitUntil: 'networkidle2' });
            if (response.status() === 200) {
                logTest('Integrations', `${platform} integration page`, 'pass');
            } else {
                logTest('Integrations', `${platform} integration page`, 'warning', { status: response.status() });
                logIssue('medium', 'Integrations', `${platform} integration not accessible`, `Implement ${platform} integration UI`);
            }
        } catch (error) {
            logTest('Integrations', `${platform} integration page`, 'warning', { message: error.message });
            logIssue('medium', 'Integrations', `${platform} integration not implemented`, `Implement ${platform} integration`);
        }
    }
}

async function testAnalytics(page) {
    console.log('\n=== Testing Analytics & Reporting ===');

    try {
        await page.goto(`${CONFIG.baseUrl}/analytics`, { waitUntil: 'networkidle2', timeout: CONFIG.timeout });
        await takeScreenshot(page, 'analytics-main');

        logTest('Analytics', 'Analytics page accessible', 'pass');

        // Check for charts
        const charts = await page.$$('canvas, svg[class*="chart"], [data-chart]');
        if (charts.length > 0) {
            logTest('Analytics', 'Charts present', 'pass', { count: charts.length });
        } else {
            logTest('Analytics', 'Charts present', 'warning');
            logIssue('high', 'Analytics', 'No charts found on analytics page', 'Implement analytics charts using Chart.js');
        }

    } catch (error) {
        logTest('Analytics', 'Analytics test', 'fail', { message: error.message });
        logIssue('medium', 'Analytics', `Analytics error: ${error.message}`, 'Debug analytics features');
    }
}

// Main test runner
async function runTests() {
    console.log('='.repeat(60));
    console.log('CMIS Platform Comprehensive Testing');
    console.log('='.repeat(60));

    // Create output directories
    await fs.mkdir(CONFIG.screenshotDir, { recursive: true });
    await fs.mkdir(path.dirname(CONFIG.reportFile), { recursive: true });

    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security',
            '--ignore-certificate-errors'
        ]
    });

    try {
        // Run authentication first
        const { page, success } = await testAuthentication(browser);

        if (success) {
            // Run all other tests
            await testDashboard(page);
            await testNavigation(page);
            await testI18n(page);
            await testResponsiveDesign(page);
            await testUserManagement(page);
            await testCampaigns(page);
            await testSocialMedia(page);
            await testPlatformIntegrations(page);
            await testAnalytics(page);
        } else {
            console.log('\n⚠️  Authentication failed. Running limited tests without authentication...');

            // Run tests that don't require auth
            const publicPage = await browser.newPage();
            await publicPage.setViewport(CONFIG.viewport);
            await testI18n(publicPage);
            await testResponsiveDesign(publicPage);
        }

    } catch (error) {
        console.error('Fatal error during testing:', error);
        logIssue('critical', 'System', `Fatal test error: ${error.message}`, 'Debug testing infrastructure');
    } finally {
        await browser.close();
    }

    // Save results
    await fs.writeFile(CONFIG.reportFile, JSON.stringify(testResults, null, 2));

    // Print summary
    console.log('\n' + '='.repeat(60));
    console.log('TEST SUMMARY');
    console.log('='.repeat(60));
    console.log(`Total Tests: ${testResults.summary.total}`);
    console.log(`✅ Passed: ${testResults.summary.passed}`);
    console.log(`❌ Failed: ${testResults.summary.failed}`);
    console.log(`⚠️  Warnings: ${testResults.summary.warnings}`);
    console.log(`🐛 Issues Found: ${testResults.issues.length}`);
    console.log(`📸 Screenshots: ${testResults.screenshots.length}`);
    console.log(`\nDetailed report: ${CONFIG.reportFile}`);
    console.log(`Screenshots: ${CONFIG.screenshotDir}`);
    console.log('='.repeat(60));
}

// Run tests
runTests().catch(console.error);
