const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    screenshotDir: './test-results/bilingual-web',
    timeout: 30000,
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    },
    languages: ['ar', 'en']
};

// All testable web pages categorized
const PAGES = [
    // Category 1: Guest Routes (4 pages) - No auth required
    { path: '/login', name: 'guest-login', category: 'guest', requiresAuth: false },
    { path: '/register', name: 'guest-register', category: 'guest', requiresAuth: false },
    { path: '/invitation/accept/dummy-token', name: 'guest-invitation-accept', category: 'guest', requiresAuth: false, expectedStatus: 404 },
    { path: '/invitation/decline/dummy-token', name: 'guest-invitation-decline', category: 'guest', requiresAuth: false, expectedStatus: 404 },

    // Category 2: Authenticated Non-Org Routes (16 pages)
    { path: '/orgs', name: 'auth-orgs-list', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/home', name: 'auth-home', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/onboarding', name: 'auth-onboarding', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/onboarding/industry', name: 'auth-onboarding-industry', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/onboarding/goals', name: 'auth-onboarding-goals', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/onboarding/complete', name: 'auth-onboarding-complete', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/profile', name: 'auth-profile', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/profile/edit', name: 'auth-profile-edit', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/settings', name: 'auth-settings', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/offerings', name: 'auth-offerings', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/products', name: 'auth-products', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/services', name: 'auth-services', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/organizations/create', name: 'auth-organizations-create', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/subscriptions', name: 'auth-subscriptions', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/subscriptions/manage', name: 'auth-subscriptions-manage', category: 'authenticated-non-org', requiresAuth: true },
    { path: '/subscriptions/payment', name: 'auth-subscriptions-payment', category: 'authenticated-non-org', requiresAuth: true },

    // Category 3: Org-Scoped Core Routes (49 pages)
    { path: `/orgs/${CONFIG.orgId}`, name: 'org-home', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/dashboard`, name: 'org-dashboard', category: 'org-core', requiresAuth: true },

    // Campaigns
    { path: `/orgs/${CONFIG.orgId}/campaigns`, name: 'org-campaigns', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/campaigns/create`, name: 'org-campaigns-create', category: 'org-core', requiresAuth: true },

    // Analytics
    { path: `/orgs/${CONFIG.orgId}/analytics`, name: 'org-analytics', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/analytics/realtime`, name: 'org-analytics-realtime', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/analytics/kpis`, name: 'org-analytics-kpis', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/analytics/reports`, name: 'org-analytics-reports', category: 'org-core', requiresAuth: true },

    // Creative
    { path: `/orgs/${CONFIG.orgId}/creative/assets`, name: 'org-creative-assets', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/creative/briefs`, name: 'org-creative-briefs', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/creative/briefs/create`, name: 'org-creative-briefs-create', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/creative/ads`, name: 'org-creative-ads', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/creative/templates`, name: 'org-creative-templates', category: 'org-core', requiresAuth: true },

    // Social Media
    { path: `/orgs/${CONFIG.orgId}/social`, name: 'org-social', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/social/posts`, name: 'org-social-posts', category: 'org-core', requiresAuth: true, knownIssue: '500 error - undefined $currentOrg' },
    { path: `/orgs/${CONFIG.orgId}/social/scheduler`, name: 'org-social-scheduler', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/social/history`, name: 'org-social-history', category: 'org-core', requiresAuth: true },

    // Influencer Marketing
    { path: `/orgs/${CONFIG.orgId}/influencer`, name: 'org-influencer', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/influencer/create`, name: 'org-influencer-create', category: 'org-core', requiresAuth: true },

    // Campaign Orchestration
    { path: `/orgs/${CONFIG.orgId}/orchestration`, name: 'org-orchestration', category: 'org-core', requiresAuth: true },

    // Social Listening
    { path: `/orgs/${CONFIG.orgId}/listening`, name: 'org-listening', category: 'org-core', requiresAuth: true },

    // AI Center
    { path: `/orgs/${CONFIG.orgId}/ai`, name: 'org-ai', category: 'org-core', requiresAuth: true },

    // Knowledge Base
    { path: `/orgs/${CONFIG.orgId}/knowledge`, name: 'org-knowledge', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/knowledge/create`, name: 'org-knowledge-create', category: 'org-core', requiresAuth: true },

    // Predictive Analytics
    { path: `/orgs/${CONFIG.orgId}/predictive`, name: 'org-predictive', category: 'org-core', requiresAuth: true },

    // A/B Testing & Experiments
    { path: `/orgs/${CONFIG.orgId}/experiments`, name: 'org-experiments', category: 'org-core', requiresAuth: true },

    // Optimization Engine
    { path: `/orgs/${CONFIG.orgId}/optimization`, name: 'org-optimization', category: 'org-core', requiresAuth: true },

    // Automation & Workflows
    { path: `/orgs/${CONFIG.orgId}/automation`, name: 'org-automation', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/workflows`, name: 'org-workflows', category: 'org-core', requiresAuth: true },

    // Team & Products
    { path: `/orgs/${CONFIG.orgId}/team`, name: 'org-team', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/products`, name: 'org-products', category: 'org-core', requiresAuth: true },

    // Unified Inbox
    { path: `/orgs/${CONFIG.orgId}/inbox`, name: 'org-inbox', category: 'org-core', requiresAuth: true },

    // System Features
    { path: `/orgs/${CONFIG.orgId}/alerts`, name: 'org-alerts', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/exports`, name: 'org-exports', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/dashboard-builder`, name: 'org-dashboard-builder', category: 'org-core', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/feature-flags`, name: 'org-feature-flags', category: 'org-core', requiresAuth: true },

    // Category 4: Org Settings Routes (30+ pages)
    { path: `/orgs/${CONFIG.orgId}/settings/user`, name: 'org-settings-user', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/organization`, name: 'org-settings-organization', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/platforms`, name: 'org-settings-platforms', category: 'org-settings', requiresAuth: true },

    // Platform Connections
    { path: `/orgs/${CONFIG.orgId}/settings/platforms/meta`, name: 'org-settings-platforms-meta', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/platforms/google`, name: 'org-settings-platforms-google', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/platforms/tiktok`, name: 'org-settings-platforms-tiktok', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/platforms/linkedin`, name: 'org-settings-platforms-linkedin', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/platforms/twitter`, name: 'org-settings-platforms-twitter', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/platforms/snapchat`, name: 'org-settings-platforms-snapchat', category: 'org-settings', requiresAuth: true },

    // Profile Groups
    { path: `/orgs/${CONFIG.orgId}/settings/profile-groups`, name: 'org-settings-profile-groups', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/profile-groups/create`, name: 'org-settings-profile-groups-create', category: 'org-settings', requiresAuth: true },

    // Brand Voices
    { path: `/orgs/${CONFIG.orgId}/settings/brand-voices`, name: 'org-settings-brand-voices', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-voices/create`, name: 'org-settings-brand-voices-create', category: 'org-settings', requiresAuth: true },

    // Brand Safety
    { path: `/orgs/${CONFIG.orgId}/settings/brand-safety`, name: 'org-settings-brand-safety', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-safety/create`, name: 'org-settings-brand-safety-create', category: 'org-settings', requiresAuth: true },

    // Approval Workflows
    { path: `/orgs/${CONFIG.orgId}/settings/approval-workflows`, name: 'org-settings-approval-workflows', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/approval-workflows/create`, name: 'org-settings-approval-workflows-create', category: 'org-settings', requiresAuth: true },

    // Boost Rules
    { path: `/orgs/${CONFIG.orgId}/settings/boost-rules`, name: 'org-settings-boost-rules', category: 'org-settings', requiresAuth: true },
    { path: `/orgs/${CONFIG.orgId}/settings/boost-rules/create`, name: 'org-settings-boost-rules-create', category: 'org-settings', requiresAuth: true },

    // Ad Accounts
    { path: `/orgs/${CONFIG.orgId}/settings/ad-accounts`, name: 'org-settings-ad-accounts', category: 'org-settings', requiresAuth: true },
];

// Test results storage
const testResults = {
    startTime: new Date().toISOString(),
    config: CONFIG,
    summary: {
        totalPages: PAGES.length,
        totalTests: PAGES.length * 2, // ar + en for each page
        testedPages: 0,
        successfulPages: 0,
        failedPages: 0,
        categories: {}
    },
    pages: []
};

/**
 * Login to the application
 */
async function login(page) {
    console.log('\n🔐 Logging in...');
    try {
        // Set initial locale cookie before login
        await page.setCookie({
            name: 'app_locale',
            value: 'ar',
            domain: new URL(CONFIG.baseUrl).hostname,
            path: '/',
            httpOnly: false,
            secure: true,
            sameSite: 'Lax'
        });

        await page.goto(`${CONFIG.baseUrl}/login`, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        // Fill login form
        await page.waitForSelector('input[name="email"]', { timeout: 5000 });
        await page.type('input[name="email"]', CONFIG.credentials.email);
        await page.type('input[name="password"]', CONFIG.credentials.password);

        // Submit and wait for navigation
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: CONFIG.timeout }),
            page.click('button[type="submit"]')
        ]);

        const currentUrl = page.url();
        const success = !currentUrl.includes('/login');

        if (success) {
            console.log('   ✅ Login successful');
        } else {
            console.log('   ❌ Login failed - still on login page');
        }

        return success;
    } catch (error) {
        console.log(`   ❌ Login error: ${error.message}`);
        return false;
    }
}

/**
 * Set the locale cookie before navigating
 * This is the proper way to test locale switching - set cookie BEFORE navigation
 */
async function setLocaleCookie(page, locale) {
    try {
        await page.setCookie({
            name: 'app_locale',
            value: locale,
            domain: new URL(CONFIG.baseUrl).hostname,
            path: '/',
            httpOnly: false,
            secure: true,
            sameSite: 'Lax'
        });
        return true;
    } catch (error) {
        console.log(`      ⚠️  Could not set locale cookie to ${locale}: ${error.message}`);
        return false;
    }
}

/**
 * Switch language on the page (legacy method - kept for reference)
 */
async function switchLanguage(page, targetLang) {
    try {
        // Try to find and click the language switcher
        const switched = await page.evaluate((lang) => {
            // Look for language switcher buttons
            const buttons = Array.from(document.querySelectorAll('button, a'));

            // Find English or Arabic button based on target language
            const targetText = lang === 'en' ? 'English' : 'العربية';
            const langButton = buttons.find(btn =>
                btn.textContent.includes(targetText) ||
                btn.getAttribute('data-lang') === lang
            );

            if (langButton) {
                langButton.click();
                return true;
            }
            return false;
        }, targetLang);

        if (switched) {
            // Wait for page to update after language switch
            await new Promise(resolve => setTimeout(resolve, 2000));
            return true;
        }

        return false;
    } catch (error) {
        console.log(`      ⚠️  Could not switch to ${targetLang}: ${error.message}`);
        return false;
    }
}

/**
 * Extract page metadata
 */
async function getPageMetadata(page) {
    return await page.evaluate(() => {
        const hasLanguageSwitcher = !!(
            document.querySelector('[data-lang]') ||
            document.querySelector('button[onclick*="language"]') ||
            Array.from(document.querySelectorAll('button, a'))
                .some(el => el.textContent.includes('English') || el.textContent.includes('العربية'))
        );

        return {
            locale: document.documentElement.lang || 'unknown',
            direction: document.documentElement.dir || 'unknown',
            title: document.title || 'No title',
            hasArabic: /[\u0600-\u06FF]/.test(document.body.innerText),
            hasEnglish: /\b(Dashboard|Campaign|Settings|Analytics|Login|Register)\b/i.test(document.body.innerText),
            hasLanguageSwitcher,
            bodyClasses: document.body.className,
            htmlClasses: document.documentElement.className
        };
    });
}

/**
 * Check for i18n compliance issues
 */
async function checkI18nCompliance(page, locale) {
    return await page.evaluate((expectedLocale) => {
        const issues = [];

        // Check for hardcoded text patterns
        const bodyText = document.body.innerText;

        // Common hardcoded English words (not comprehensive, just examples)
        const hardcodedPatterns = [
            /\bSave\b/,
            /\bDelete\b/,
            /\bCancel\b/,
            /\bSubmit\b/,
            /\bEdit\b/,
            /\bCreate\b/,
            /\bUpdate\b/,
        ];

        // Check for directional CSS issues
        const allElements = document.querySelectorAll('*');
        let directionalCssCount = 0;

        allElements.forEach(el => {
            const classes = el.className;
            if (typeof classes === 'string') {
                // Check for non-logical directional classes
                if (classes.match(/\b(ml-|mr-|pl-|pr-|text-left|text-right)\b/)) {
                    directionalCssCount++;
                }
            }
        });

        if (directionalCssCount > 0) {
            issues.push(`Found ${directionalCssCount} elements with directional CSS (ml-, mr-, text-left, etc.)`);
        }

        // Check locale attribute
        const htmlLang = document.documentElement.lang;
        if (htmlLang !== expectedLocale) {
            issues.push(`Expected locale "${expectedLocale}" but found "${htmlLang}"`);
        }

        // Check direction attribute
        const htmlDir = document.documentElement.dir;
        const expectedDir = expectedLocale === 'ar' ? 'rtl' : 'ltr';
        if (htmlDir !== expectedDir) {
            issues.push(`Expected direction "${expectedDir}" but found "${htmlDir}"`);
        }

        return {
            compliant: issues.length === 0,
            issues
        };
    }, locale);
}

/**
 * Test a single page in both languages
 * Uses cookie-based locale switching for reliable testing
 */
async function testPage(page, pageInfo) {
    const { path, name, category, requiresAuth, expectedStatus = 200, knownIssue } = pageInfo;
    const fullUrl = `${CONFIG.baseUrl}${path}`;

    console.log(`\n📄 Testing: ${name}`);
    console.log(`   URL: ${fullUrl}`);
    console.log(`   Category: ${category}`);

    if (knownIssue) {
        console.log(`   ⚠️  Known Issue: ${knownIssue}`);
    }

    const pageResult = {
        name,
        path,
        category,
        url: fullUrl,
        requiresAuth,
        knownIssue: knownIssue || null,
        languages: {}
    };

    try {
        // ============================================
        // Test Arabic version - set cookie FIRST
        // ============================================
        console.log('   🌍 Testing Arabic (ar)...');
        await setLocaleCookie(page, 'ar');

        const arResponse = await page.goto(fullUrl, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        const arStatusCode = arResponse ? arResponse.status() : 0;
        const arFinalUrl = page.url();

        console.log(`   Status: ${arStatusCode}`);
        console.log(`   Final URL: ${arFinalUrl}`);

        await new Promise(resolve => setTimeout(resolve, 500));

        const arMetadata = await getPageMetadata(page);
        const arCompliance = await checkI18nCompliance(page, 'ar');
        const arScreenshot = `${CONFIG.screenshotDir}/screenshots/${name}-ar.png`;

        await page.screenshot({
            path: arScreenshot,
            fullPage: true
        });

        pageResult.languages.ar = {
            statusCode: arStatusCode,
            finalUrl: arFinalUrl,
            locale: arMetadata.locale,
            direction: arMetadata.direction,
            title: arMetadata.title,
            hasArabic: arMetadata.hasArabic,
            hasEnglish: arMetadata.hasEnglish,
            hasLanguageSwitcher: arMetadata.hasLanguageSwitcher,
            i18nCompliance: arCompliance,
            screenshot: arScreenshot
        };

        console.log(`      Locale: ${arMetadata.locale}, Dir: ${arMetadata.direction}`);
        console.log(`      Language Switcher: ${arMetadata.hasLanguageSwitcher ? 'Yes' : 'No'}`);
        console.log(`      i18n Compliant: ${arCompliance.compliant ? 'Yes' : 'No'}`);
        if (!arCompliance.compliant) {
            arCompliance.issues.forEach(issue => console.log(`      ⚠️  ${issue}`));
        }

        // ============================================
        // Test English version - set cookie and reload
        // ============================================
        console.log('   🌍 Testing English (en)...');
        await setLocaleCookie(page, 'en');

        const enResponse = await page.goto(fullUrl, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        const enStatusCode = enResponse ? enResponse.status() : 0;
        const enFinalUrl = page.url();

        await new Promise(resolve => setTimeout(resolve, 500));

        const enMetadata = await getPageMetadata(page);
        const enCompliance = await checkI18nCompliance(page, 'en');
        const enScreenshot = `${CONFIG.screenshotDir}/screenshots/${name}-en.png`;

        await page.screenshot({
            path: enScreenshot,
            fullPage: true
        });

        pageResult.languages.en = {
            statusCode: enStatusCode,
            finalUrl: enFinalUrl,
            locale: enMetadata.locale,
            direction: enMetadata.direction,
            title: enMetadata.title,
            hasArabic: enMetadata.hasArabic,
            hasEnglish: enMetadata.hasEnglish,
            hasLanguageSwitcher: enMetadata.hasLanguageSwitcher,
            i18nCompliance: enCompliance,
            screenshot: enScreenshot
        };

        console.log(`      Locale: ${enMetadata.locale}, Dir: ${enMetadata.direction}`);
        console.log(`      i18n Compliant: ${enCompliance.compliant ? 'Yes' : 'No'}`);
        if (!enCompliance.compliant) {
            enCompliance.issues.forEach(issue => console.log(`      ⚠️  ${issue}`));
        }

        // Determine if test was successful
        // Success requires both languages to work correctly
        const arSuccess = (
            arStatusCode === expectedStatus &&
            arMetadata.locale === 'ar' &&
            arMetadata.direction === 'rtl'
        );

        const enSuccess = (
            enStatusCode === expectedStatus &&
            enMetadata.locale === 'en' &&
            enMetadata.direction === 'ltr'
        );

        const isSuccess = arSuccess && enSuccess;

        pageResult.status = isSuccess ? 'success' : 'failed';
        pageResult.arSuccess = arSuccess;
        pageResult.enSuccess = enSuccess;

        if (isSuccess) {
            testResults.summary.successfulPages++;
            console.log('   ✅ Test passed (both languages work correctly)');
        } else {
            testResults.summary.failedPages++;
            console.log(`   ❌ Test failed - AR: ${arSuccess ? '✅' : '❌'}, EN: ${enSuccess ? '✅' : '❌'}`);
        }

    } catch (error) {
        console.log(`   ❌ Error: ${error.message}`);
        pageResult.status = 'error';
        pageResult.error = error.message;
        testResults.summary.failedPages++;
    }

    testResults.summary.testedPages++;
    testResults.pages.push(pageResult);

    // Update category summary
    if (!testResults.summary.categories[category]) {
        testResults.summary.categories[category] = {
            total: 0,
            tested: 0,
            successful: 0,
            failed: 0
        };
    }
    testResults.summary.categories[category].total++;
    testResults.summary.categories[category].tested++;
    if (pageResult.status === 'success') {
        testResults.summary.categories[category].successful++;
    } else {
        testResults.summary.categories[category].failed++;
    }
}

/**
 * Generate test reports
 */
function generateReports() {
    console.log('\n📊 Generating reports...');

    // Save JSON report
    const jsonReport = path.join(CONFIG.screenshotDir, 'test-report.json');
    fs.writeFileSync(jsonReport, JSON.stringify(testResults, null, 2));
    console.log(`   ✅ JSON report: ${jsonReport}`);

    // Generate Markdown summary
    const mdReport = path.join(CONFIG.screenshotDir, 'SUMMARY.md');
    let mdContent = `# Bilingual Web Testing Report\n\n`;
    mdContent += `**Date:** ${testResults.startTime}\n`;
    mdContent += `**Total Pages:** ${testResults.summary.totalPages}\n`;
    mdContent += `**Total Tests:** ${testResults.summary.totalTests} (${testResults.summary.totalPages} pages × 2 languages)\n\n`;

    mdContent += `## Summary\n\n`;
    mdContent += `- ✅ Successful: ${testResults.summary.successfulPages}\n`;
    mdContent += `- ❌ Failed: ${testResults.summary.failedPages}\n`;
    mdContent += `- 📊 Success Rate: ${((testResults.summary.successfulPages / testResults.summary.testedPages) * 100).toFixed(1)}%\n\n`;

    mdContent += `## By Category\n\n`;
    mdContent += `| Category | Total | Tested | Success | Failed | Success Rate |\n`;
    mdContent += `|----------|-------|--------|---------|--------|-------------|\n`;

    Object.entries(testResults.summary.categories).forEach(([category, stats]) => {
        const successRate = stats.tested > 0 ? ((stats.successful / stats.tested) * 100).toFixed(1) : '0.0';
        mdContent += `| ${category} | ${stats.total} | ${stats.tested} | ${stats.successful} | ${stats.failed} | ${successRate}% |\n`;
    });

    mdContent += `\n## Pages Tested\n\n`;

    testResults.pages.forEach(page => {
        const icon = page.status === 'success' ? '✅' : '❌';
        mdContent += `${icon} **${page.name}** - ${page.category}\n`;
        mdContent += `   - Path: \`${page.path}\`\n`;

        if (page.languages.ar) {
            mdContent += `   - Arabic: locale=${page.languages.ar.locale}, dir=${page.languages.ar.direction}, i18n=${page.languages.ar.i18nCompliance?.compliant ? 'Yes' : 'No'}\n`;
        }
        if (page.languages.en) {
            if (page.languages.en.error) {
                mdContent += `   - English: ${page.languages.en.error}\n`;
            } else {
                mdContent += `   - English: locale=${page.languages.en.locale}, dir=${page.languages.en.direction}, i18n=${page.languages.en.i18nCompliance?.compliant ? 'Yes' : 'No'}\n`;
            }
        }

        if (page.knownIssue) {
            mdContent += `   - ⚠️  Known Issue: ${page.knownIssue}\n`;
        }

        mdContent += `\n`;
    });

    fs.writeFileSync(mdReport, mdContent);
    console.log(`   ✅ Markdown summary: ${mdReport}`);
}

/**
 * Main test execution
 */
async function main() {
    console.log('🚀 Starting Comprehensive Bilingual Web Testing');
    console.log(`📍 Base URL: ${CONFIG.baseUrl}`);
    console.log(`🏢 Organization: ${CONFIG.orgId}`);
    console.log(`📦 Total Pages: ${PAGES.length}`);
    console.log(`🌍 Languages: ${CONFIG.languages.join(', ')}`);
    console.log(`📊 Total Tests: ${PAGES.length * CONFIG.languages.length}`);

    // Create screenshot directory
    const screenshotPath = path.join(CONFIG.screenshotDir, 'screenshots');
    if (!fs.existsSync(screenshotPath)) {
        fs.mkdirSync(screenshotPath, { recursive: true });
    }

    // Launch browser
    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login first for authenticated pages
        const loginSuccess = await login(page);

        if (!loginSuccess) {
            console.log('\n❌ Login failed - cannot test authenticated pages');
            console.log('   Will only test guest pages');
        }

        // Test all pages
        for (const pageInfo of PAGES) {
            // Skip authenticated pages if login failed
            if (pageInfo.requiresAuth && !loginSuccess) {
                console.log(`\n⏭️  Skipping ${pageInfo.name} (requires authentication)`);
                testResults.summary.testedPages++;
                testResults.summary.failedPages++;
                testResults.pages.push({
                    ...pageInfo,
                    status: 'skipped',
                    error: 'Authentication required but login failed'
                });
                continue;
            }

            await testPage(page, pageInfo);

            // Small delay between pages
            await new Promise(resolve => setTimeout(resolve, 500));
        }

    } catch (error) {
        console.error('\n❌ Fatal error:', error);
    } finally {
        await browser.close();

        // Calculate end time
        testResults.endTime = new Date().toISOString();

        // Generate reports
        generateReports();

        console.log('\n✅ Testing complete!');
        console.log(`\n📊 Final Summary:`);
        console.log(`   Total Pages: ${testResults.summary.totalPages}`);
        console.log(`   Tested: ${testResults.summary.testedPages}`);
        console.log(`   Successful: ${testResults.summary.successfulPages}`);
        console.log(`   Failed: ${testResults.summary.failedPages}`);
        console.log(`   Success Rate: ${((testResults.summary.successfulPages / testResults.summary.testedPages) * 100).toFixed(1)}%`);
    }
}

// Run the tests
main().catch(console.error);
