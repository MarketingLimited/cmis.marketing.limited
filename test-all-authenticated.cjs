/**
 * Complete Authenticated Pages Testing - All Org-Scoped Routes
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    screenshotDir: './test-results/all-authenticated-pages',
    timeout: 30000,
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    }
};

// ALL pages to test
const PAGES = [
    { path: '/orgs', name: '01-orgs-list' },
    { path: `/orgs/${CONFIG.orgId}`, name: '02-org-home' },
    { path: `/orgs/${CONFIG.orgId}/dashboard`, name: '03-dashboard' },
    { path: `/orgs/${CONFIG.orgId}/campaigns`, name: '04-campaigns' },
    { path: `/orgs/${CONFIG.orgId}/campaigns/create`, name: '05-campaigns-create' },
    { path: `/orgs/${CONFIG.orgId}/analytics`, name: '06-analytics' },
    { path: `/orgs/${CONFIG.orgId}/analytics/realtime`, name: '07-analytics-realtime' },
    { path: `/orgs/${CONFIG.orgId}/analytics/kpis`, name: '08-analytics-kpis' },
    { path: `/orgs/${CONFIG.orgId}/influencer`, name: '09-influencer' },
    { path: `/orgs/${CONFIG.orgId}/influencer/create`, name: '10-influencer-create' },
    { path: `/orgs/${CONFIG.orgId}/orchestration`, name: '11-orchestration' },
    { path: `/orgs/${CONFIG.orgId}/listening`, name: '12-listening' },
    { path: `/orgs/${CONFIG.orgId}/creative/assets`, name: '13-creative-assets' },
    { path: `/orgs/${CONFIG.orgId}/creative/briefs`, name: '14-creative-briefs' },
    { path: `/orgs/${CONFIG.orgId}/creative/briefs/create`, name: '15-creative-briefs-create' },
    { path: `/orgs/${CONFIG.orgId}/social`, name: '16-social' },
    { path: `/orgs/${CONFIG.orgId}/social/posts`, name: '17-social-posts' },
    { path: `/orgs/${CONFIG.orgId}/social/scheduler`, name: '18-social-scheduler' },
    { path: `/orgs/${CONFIG.orgId}/social/history`, name: '19-social-history' },
    { path: `/orgs/${CONFIG.orgId}/settings/user`, name: '20-settings-user' },
    { path: `/orgs/${CONFIG.orgId}/settings/organization`, name: '21-settings-organization' },
    { path: `/orgs/${CONFIG.orgId}/settings/platform-connections`, name: '22-settings-platforms' },
    { path: `/orgs/${CONFIG.orgId}/settings/profile-groups`, name: '23-settings-profile-groups' },
    { path: `/orgs/${CONFIG.orgId}/settings/profile-groups/create`, name: '24-settings-profile-groups-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-voices`, name: '25-settings-brand-voices' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-voices/create`, name: '26-settings-brand-voices-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-safety`, name: '27-settings-brand-safety' },
    { path: `/orgs/${CONFIG.orgId}/settings/brand-safety/create`, name: '28-settings-brand-safety-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/approval-workflows`, name: '29-settings-approval-workflows' },
    { path: `/orgs/${CONFIG.orgId}/settings/approval-workflows/create`, name: '30-settings-approval-workflows-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/boost-rules`, name: '31-settings-boost-rules' },
    { path: `/orgs/${CONFIG.orgId}/settings/boost-rules/create`, name: '32-settings-boost-rules-create' },
    { path: `/orgs/${CONFIG.orgId}/settings/ad-accounts`, name: '33-settings-ad-accounts' },
    { path: `/orgs/${CONFIG.orgId}/team`, name: '34-team' },
    { path: `/orgs/${CONFIG.orgId}/products`, name: '35-products' },
    { path: `/orgs/${CONFIG.orgId}/workflows`, name: '36-workflows' },
    { path: `/orgs/${CONFIG.orgId}/ai`, name: '37-ai' },
    { path: `/orgs/${CONFIG.orgId}/knowledge`, name: '38-knowledge' },
    { path: `/orgs/${CONFIG.orgId}/knowledge/create`, name: '39-knowledge-create' },
    { path: `/orgs/${CONFIG.orgId}/predictive`, name: '40-predictive' },
    { path: `/orgs/${CONFIG.orgId}/experiments`, name: '41-experiments' },
    { path: `/orgs/${CONFIG.orgId}/optimization`, name: '42-optimization' },
    { path: `/orgs/${CONFIG.orgId}/automation`, name: '43-automation' },
    { path: `/orgs/${CONFIG.orgId}/alerts`, name: '44-alerts' },
    { path: `/orgs/${CONFIG.orgId}/exports`, name: '45-exports' },
    { path: `/orgs/${CONFIG.orgId}/dashboard-builder`, name: '46-dashboard-builder' },
    { path: `/orgs/${CONFIG.orgId}/feature-flags`, name: '47-feature-flags' },
    { path: `/orgs/${CONFIG.orgId}/inbox`, name: '48-inbox' },
    { path: '/profile', name: '49-profile' },
];

if (!fs.existsSync(CONFIG.screenshotDir)) {
    fs.mkdirSync(CONFIG.screenshotDir, { recursive: true });
}

const results = [];

async function login(page) {
    console.log('\n🔐 Logging in...');
    await page.goto(`${CONFIG.baseUrl}/login`, { waitUntil: 'networkidle2' });
    await page.type('input[name="email"]', CONFIG.credentials.email);
    await page.type('input[name="password"]', CONFIG.credentials.password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }),
        page.click('button[type="submit"]')
    ]);
    const success = !page.url().includes('/login');
    console.log(success ? '   ✅ Login successful' : '   ❌ Login failed');
    return success;
}

async function testPage(page, pageInfo, index, total) {
    const url = `${CONFIG.baseUrl}${pageInfo.path}`;

    console.log(`\n[${index}/${total}] Testing: ${pageInfo.name}`);
    console.log(`   URL: ${url}`);

    try {
        const response = await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        await new Promise(resolve => setTimeout(resolve, 1500));

        const statusCode = response.status();
        const finalUrl = page.url();

        // Get metadata
        const metadata = await page.evaluate(() => ({
            locale: document.documentElement.lang,
            dir: document.documentElement.dir,
            title: document.title,
            hasContent: document.body.innerText.length > 100
        }));

        // Take screenshot
        const screenshotPath = path.join(CONFIG.screenshotDir, `${pageInfo.name}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: false });

        const result = {
            name: pageInfo.name,
            url,
            statusCode,
            finalUrl,
            locale: metadata.locale,
            direction: metadata.dir,
            title: metadata.title,
            screenshot: screenshotPath,
            status: finalUrl.includes('/login') ? 'redirected' : 'success'
        };

        console.log(`   Status: ${statusCode} | Locale: ${metadata.locale} | Dir: ${metadata.dir}`);
        console.log(`   Screenshot: ${screenshotPath}`);

        results.push(result);
        return result;

    } catch (error) {
        console.log(`   ❌ Error: ${error.message}`);
        results.push({
            name: pageInfo.name,
            url,
            status: 'error',
            error: error.message
        });
    }
}

async function runTests() {
    console.log('🌐 Complete Authenticated Pages Testing');
    console.log('=' .repeat(80));
    console.log(`Total Pages: ${PAGES.length}`);
    console.log(`Organization ID: ${CONFIG.orgId}\n`);

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login
        if (!await login(page)) {
            console.log('\n❌ Login failed - cannot proceed');
            await browser.close();
            return;
        }

        console.log('\n' + '='.repeat(80));
        console.log('📋 Testing All Pages');
        console.log('='.repeat(80));

        // Test each page
        for (let i = 0; i < PAGES.length; i++) {
            await testPage(page, PAGES[i], i + 1, PAGES.length);
        }

        // Summary
        console.log('\n' + '='.repeat(80));
        console.log('📊 SUMMARY');
        console.log('='.repeat(80));

        const successful = results.filter(r => r.status === 'success').length;
        const redirected = results.filter(r => r.status === 'redirected').length;
        const failed = results.filter(r => r.status === 'error').length;

        console.log(`Total: ${PAGES.length}`);
        console.log(`✅ Success: ${successful}`);
        console.log(`🔄 Redirected: ${redirected}`);
        console.log(`❌ Failed: ${failed}`);
        console.log(`\n📁 Screenshots: ${CONFIG.screenshotDir}/`);

        // Save report
        const reportPath = path.join(CONFIG.screenshotDir, 'test-report.json');
        fs.writeFileSync(reportPath, JSON.stringify({
            timestamp: new Date().toISOString(),
            total: PAGES.length,
            summary: { successful, redirected, failed },
            results
        }, null, 2));
        console.log(`📄 Report: ${reportPath}`);

        // Create markdown summary
        let markdown = `# All Authenticated Pages Test Report\n\n`;
        markdown += `**Date:** ${new Date().toISOString().split('T')[0]}\n`;
        markdown += `**Total Pages:** ${PAGES.length}\n\n`;
        markdown += `## Summary\n\n`;
        markdown += `- ✅ Success: ${successful}\n`;
        markdown += `- 🔄 Redirected: ${redirected}\n`;
        markdown += `- ❌ Failed: ${failed}\n\n`;
        markdown += `## Pages\n\n`;

        results.forEach(r => {
            const icon = r.status === 'success' ? '✅' : r.status === 'redirected' ? '🔄' : '❌';
            markdown += `${icon} **${r.name}** - ${r.title || 'No title'}\n`;
        });

        fs.writeFileSync(path.join(CONFIG.screenshotDir, 'SUMMARY.md'), markdown);

    } finally {
        await browser.close();
        console.log('\n✅ Testing complete!');
    }
}

runTests();
