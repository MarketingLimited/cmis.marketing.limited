/**
 * Comprehensive Org-Scoped Pages Browser Testing
 * Tests all major platform pages with screenshots in both languages
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    screenshotDir: './test-results/org-pages',
    timeout: 30000,
    testBothLanguages: true
};

// All pages to test
const PAGES = [
    { path: '/orgs', name: 'orgs-list' },
    { path: `/orgs/${CONFIG.orgId}`, name: 'org-home' },
    { path: `/orgs/${CONFIG.orgId}/dashboard`, name: 'dashboard' },
    { path: `/orgs/${CONFIG.orgId}/campaigns`, name: 'campaigns' },
    { path: `/orgs/${CONFIG.orgId}/campaigns/create`, name: 'campaigns-create' },
    { path: `/orgs/${CONFIG.orgId}/analytics`, name: 'analytics' },
    { path: `/orgs/${CONFIG.orgId}/analytics/realtime`, name: 'analytics-realtime' },
    { path: `/orgs/${CONFIG.orgId}/analytics/kpis`, name: 'analytics-kpis' },
    { path: `/orgs/${CONFIG.orgId}/influencer`, name: 'influencer' },
    { path: `/orgs/${CONFIG.orgId}/influencer/create`, name: 'influencer-create' },
    { path: `/orgs/${CONFIG.orgId}/orchestration`, name: 'orchestration' },
    { path: `/orgs/${CONFIG.orgId}/listening`, name: 'listening' },
    { path: `/orgs/${CONFIG.orgId}/creative/assets`, name: 'creative-assets' },
    { path: `/orgs/${CONFIG.orgId}/creative/briefs`, name: 'creative-briefs' },
    { path: `/orgs/${CONFIG.orgId}/creative/briefs/create`, name: 'creative-briefs-create' },
    { path: `/orgs/${CONFIG.orgId}/social`, name: 'social' },
    { path: `/orgs/${CONFIG.orgId}/social/posts`, name: 'social-posts' },
    { path: `/orgs/${CONFIG.orgId}/social/scheduler`, name: 'social-scheduler' },
    { path: `/orgs/${CONFIG.orgId}/social/history`, name: 'social-history' },
    { path: `/orgs/${CONFIG.orgId}/settings/user`, name: 'settings-user' },
    { path: `/orgs/${CONFIG.orgId}/settings/organization`, name: 'settings-organization' },
    { path: `/orgs/${CONFIG.orgId}/settings/platform-connections`, name: 'settings-platforms' },
    { path: `/orgs/${CONFIG.orgId}/settings/profile-groups`, name: 'settings-profile-groups' },
    { path: `/orgs/${CONFIG.orgId}/settings/profile-groups/create`, name: 'settings-profile-groups-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-voices`, name: 'settings-brand-voices' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-voices/create`, name: 'settings-brand-voices-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-safety`, name: 'settings-brand-safety' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-safety/create`, name: 'settings-brand-safety-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/approval-workflows`, name: 'settings-approval-workflows' },
    { path: `/orgs/${CONFIG.orgId}/settings/approval-workflows/create`, name: 'settings-approval-workflows-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/boost-rules`, name: 'settings-boost-rules' },
    { path: `/orgs/${CONFIG.orgId}/settings/boost-rules/create`, name: 'settings-boost-rules-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/ad-accounts`, name: 'settings-ad-accounts' },
    { path: `/orgs/${CONFIG.orgId}/team`, name: 'team' },
    { path: `/orgs/${CONFIG.orgId}/products`, name: 'products' },
    { path: `/orgs/${CONFIG.orgId}/workflows`, name: 'workflows' },
    { path: `/orgs/${CONFIG.orgId}/ai`, name: 'ai' },
    { path: `/orgs/${CONFIG.orgId}/knowledge`, name: 'knowledge' },
    { path: `/orgs/${CONFIG.orgId}/knowledge/create`, name: 'knowledge-create' },
    { path: `/orgs/${CONFIG.orgId}/predictive`, name: 'predictive' },
    { path: `/orgs/${CONFIG.orgId}/experiments`, name: 'experiments' },
    { path: `/orgs/${CONFIG.orgId}/optimization`, name: 'optimization' },
    { path: `/orgs/${CONFIG.orgId}/automation`, name: 'automation' },
    { path: `/orgs/${CONFIG.orgId}/alerts`, name: 'alerts' },
    { path: `/orgs/${CONFIG.orgId}/exports`, name: 'exports' },
    { path: `/orgs/${CONFIG.orgId}/dashboard-builder`, name: 'dashboard-builder' },
    { path: `/orgs/${CONFIG.orgId}/feature-flags`, name: 'feature-flags' },
    { path: `/orgs/${CONFIG.orgId}/inbox`, name: 'inbox' },
    { path: '/profile', name: 'profile' },
];

// Ensure screenshot directory exists
if (!fs.existsSync(CONFIG.screenshotDir)) {
    fs.mkdirSync(CONFIG.screenshotDir, { recursive: true });
}

// Test results
const results = {
    total: 0,
    success: 0,
    failed: 0,
    redirected: 0,
    pages: []
};

async function testPage(browser, page, url, name, lang = 'ar') {
    const startTime = Date.now();
    let result = {
        url,
        name,
        lang,
        status: 'unknown',
        statusCode: null,
        hasArabicText: false,
        hasEnglishText: false,
        hasLanguageSwitcher: false,
        hasNavigation: false,
        loadTime: 0,
        screenshot: null,
        errors: [],
        locale: null,
        direction: null
    };

    try {
        console.log(`\n📍 Testing: ${name} (${lang.toUpperCase()})`);
        console.log(`   URL: ${url}`);

        // Listen for console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                result.errors.push(msg.text());
            }
        });

        // Navigate to page
        const response = await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        result.statusCode = response.status();
        result.loadTime = Date.now() - startTime;

        console.log(`   Status: ${result.statusCode}`);
        console.log(`   Load Time: ${result.loadTime}ms`);

        // Wait for page to settle
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Check if redirected to login
        const currentUrl = page.url();
        if (currentUrl.includes('/login')) {
            result.status = 'redirected_to_login';
            result.statusCode = 302;
            console.log(`   ⚠️  Redirected to login (authentication required)`);
        } else {
            result.status = 'loaded';
        }

        // Get page metadata
        const metadata = await page.evaluate(() => {
            return {
                locale: document.documentElement.lang || 'unknown',
                direction: document.documentElement.dir || 'unknown',
                title: document.title || 'No title',
                bodyText: document.body.innerText.substring(0, 500)
            };
        });

        result.locale = metadata.locale;
        result.direction = metadata.direction;

        console.log(`   Locale: ${result.locale}`);
        console.log(`   Direction: ${result.direction}`);

        // Check for Arabic text (common Arabic words)
        result.hasArabicText = /[\u0600-\u06FF]/.test(metadata.bodyText) ||
                               metadata.bodyText.includes('تسجيل') ||
                               metadata.bodyText.includes('العربية') ||
                               metadata.bodyText.includes('الحملات');

        // Check for English text (common words, excluding email placeholders)
        result.hasEnglishText = /\b(Dashboard|Campaign|Settings|Analytics|Social|Profile)\b/i.test(metadata.bodyText);

        console.log(`   Has Arabic: ${result.hasArabicText ? 'YES ✅' : 'NO ❌'}`);
        console.log(`   Has English: ${result.hasEnglishText ? 'YES ✅' : 'NO ❌'}`);

        // Check for language switcher
        result.hasLanguageSwitcher = await page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button'));
            return buttons.some(btn =>
                btn.textContent.includes('العربية') ||
                btn.textContent.includes('English') ||
                btn.querySelector('svg path[d*="M3 5h12M9 3v2"]')
            );
        });

        console.log(`   Language Switcher: ${result.hasLanguageSwitcher ? 'YES ✅' : 'NO ❌'}`);

        // Check for navigation
        result.hasNavigation = await page.evaluate(() => {
            const nav = document.querySelector('nav') ||
                       document.querySelector('[role="navigation"]') ||
                       document.querySelector('aside') ||
                       document.querySelector('.sidebar');
            return !!nav;
        });

        console.log(`   Has Navigation: ${result.hasNavigation ? 'YES ✅' : 'NO ❌'}`);

        // Take screenshot
        const screenshotPath = path.join(
            CONFIG.screenshotDir,
            `${name}-${lang}.png`
        );
        await page.screenshot({
            path: screenshotPath,
            fullPage: false // Just viewport to keep file size reasonable
        });
        result.screenshot = screenshotPath;
        console.log(`   Screenshot: ${screenshotPath}`);

        if (result.statusCode === 200 || result.status === 'loaded') {
            results.success++;
        } else if (result.status === 'redirected_to_login') {
            results.redirected++;
        } else {
            results.failed++;
        }

    } catch (error) {
        result.status = 'error';
        result.errors.push(error.message);
        results.failed++;
        console.log(`   ❌ Error: ${error.message}`);
    }

    results.total++;
    results.pages.push(result);

    return result;
}

async function runTests() {
    console.log('🌐 Starting Comprehensive Org-Scoped Pages Browser Testing\n');
    console.log(`Total Pages to Test: ${PAGES.length}`);
    console.log(`Languages: ${CONFIG.testBothLanguages ? 'Arabic + English' : 'Arabic only'}`);
    console.log(`Total Tests: ${PAGES.length * (CONFIG.testBothLanguages ? 2 : 1)}\n`);
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

    try {
        for (const pageInfo of PAGES) {
            const page = await browser.newPage();
            await page.setViewport({ width: 1920, height: 1080 });

            const url = `${CONFIG.baseUrl}${pageInfo.path}`;

            // Test in Arabic
            await testPage(browser, page, url, pageInfo.name, 'ar');

            // Test in English (if enabled)
            if (CONFIG.testBothLanguages) {
                // Try to switch language by visiting /language/en first
                // (won't work without auth, but worth trying)
                const urlWithLang = `${url}?lang=en`;
                await testPage(browser, page, urlWithLang, pageInfo.name, 'en');
            }

            await page.close();
        }

        // Generate summary report
        console.log('\n' + '='.repeat(80));
        console.log('📊 TEST SUMMARY');
        console.log('='.repeat(80));
        console.log(`Total Tests: ${results.total}`);
        console.log(`✅ Loaded Successfully: ${results.success}`);
        console.log(`🔄 Redirected to Login: ${results.redirected}`);
        console.log(`❌ Failed: ${results.failed}`);
        console.log(`\nSuccess Rate: ${((results.success / results.total) * 100).toFixed(1)}%`);
        console.log(`Auth Required: ${((results.redirected / results.total) * 100).toFixed(1)}%`);

        // Save JSON report
        const reportPath = path.join(CONFIG.screenshotDir, 'test-report.json');
        fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
        console.log(`\n📄 Full report saved: ${reportPath}`);

        // Generate markdown summary
        generateMarkdownReport();

    } catch (error) {
        console.error('❌ Fatal error:', error);
    } finally {
        await browser.close();
        console.log('\n✅ Testing complete!');
    }
}

function generateMarkdownReport() {
    const reportPath = path.join(CONFIG.screenshotDir, 'TEST-SUMMARY.md');

    let markdown = `# Org-Scoped Pages Browser Testing Report

**Date:** ${new Date().toISOString().split('T')[0]}
**Platform:** ${CONFIG.baseUrl}
**Organization ID:** ${CONFIG.orgId}
**Total Pages Tested:** ${PAGES.length}
**Total Tests:** ${results.total}

---

## Summary

| Metric | Count | Percentage |
|--------|-------|------------|
| ✅ Loaded Successfully | ${results.success} | ${((results.success / results.total) * 100).toFixed(1)}% |
| 🔄 Redirected to Login | ${results.redirected} | ${((results.redirected / results.total) * 100).toFixed(1)}% |
| ❌ Failed | ${results.failed} | ${((results.failed / results.total) * 100).toFixed(1)}% |
| **Total** | ${results.total} | 100% |

---

## Pages by Status

### ✅ Successfully Loaded (No Authentication Required)

`;

    const loaded = results.pages.filter(p => p.status === 'loaded');
    if (loaded.length > 0) {
        loaded.forEach(p => {
            markdown += `- **${p.name}** (${p.lang}): ${p.statusCode} - [Screenshot](${path.basename(p.screenshot)})\n`;
        });
    } else {
        markdown += '*None*\n';
    }

    markdown += `\n### 🔄 Redirected to Login (Authentication Required)\n\n`;

    const redirected = results.pages.filter(p => p.status === 'redirected_to_login');
    if (redirected.length > 0) {
        redirected.forEach(p => {
            markdown += `- **${p.name}** (${p.lang}): Requires authentication\n`;
        });
    } else {
        markdown += '*None*\n';
    }

    markdown += `\n### ❌ Failed/Errors\n\n`;

    const failed = results.pages.filter(p => p.status === 'error');
    if (failed.length > 0) {
        failed.forEach(p => {
            markdown += `- **${p.name}** (${p.lang}): ${p.errors.join(', ')}\n`;
        });
    } else {
        markdown += '*None*\n';
    }

    markdown += `\n---

## Language Analysis

### Pages with Language Switcher

`;

    const withSwitcher = results.pages.filter(p => p.hasLanguageSwitcher);
    markdown += `**Count:** ${withSwitcher.length} / ${results.pages.length}\n\n`;

    if (withSwitcher.length > 0) {
        withSwitcher.forEach(p => {
            markdown += `- ${p.name} (${p.lang})\n`;
        });
    }

    markdown += `\n### Pages with Navigation

`;

    const withNav = results.pages.filter(p => p.hasNavigation);
    markdown += `**Count:** ${withNav.length} / ${results.pages.length}\n\n`;

    markdown += `\n---

## Screenshots

All screenshots saved to: \`${CONFIG.screenshotDir}/\`

**Total Screenshots:** ${results.pages.filter(p => p.screenshot).length}

---

*Report generated: ${new Date().toISOString()}*
`;

    fs.writeFileSync(reportPath, markdown);
    console.log(`📄 Markdown report saved: ${reportPath}`);
}

// Run tests
runTests();
