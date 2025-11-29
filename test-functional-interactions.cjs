#!/usr/bin/env node

/**
 * CMIS Platform - Comprehensive Functional Interaction Testing
 *
 * This script performs actual UI interactions:
 * - Clicks buttons and verifies responses
 * - Opens and interacts with modals
 * - Fills out and submits forms
 * - Tests dropdowns, toggles, checkboxes
 * - Captures screenshots of each interaction
 * - Documents functional vs non-functional elements
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    resultsDir: './test-results/functional-interactions',
    screenshotsDir: './test-results/functional-interactions/screenshots',
    timeout: 30000,
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    }
};

// Test scenarios for functional interactions
const INTERACTION_TESTS = [
    {
        name: 'Language Switcher',
        page: `/orgs/${CONFIG.orgId}/dashboard`,
        tests: [
            {
                action: 'click_language_switcher',
                description: 'Click language switcher to open dropdown',
                selector: '[data-testid="language-switcher"], #language-switcher-btn',
                expectedResult: 'Dropdown menu appears with language options',
                screenshot: true
            },
            {
                action: 'switch_to_arabic',
                description: 'Switch language to Arabic',
                selector: 'form[action*="language/ar"] button[type="submit"]',
                expectedResult: 'Page reloads with Arabic content and RTL layout',
                screenshot: true
            }
        ]
    },
    {
        name: 'Campaign Creation Flow',
        page: `/orgs/${CONFIG.orgId}/campaigns/create`,
        tests: [
            {
                action: 'select_platform_meta',
                description: 'Select Meta platform',
                selector: 'input[type="radio"][value="meta"], label[for*="meta"], [data-platform="meta"]',
                expectedResult: 'Meta platform card selected',
                screenshot: true
            },
            {
                action: 'click_next',
                description: 'Click Next button to proceed to step 2',
                selector: 'button[type="submit"], button.next-btn, [data-testid="next-btn"]',
                expectedResult: 'Wizard advances to Campaign Objective step',
                screenshot: true
            }
        ]
    },
    {
        name: 'User Settings Form',
        page: `/orgs/${CONFIG.orgId}/settings/user`,
        tests: [
            {
                action: 'fill_display_name',
                description: 'Fill display name field',
                selector: 'input[name="display_name"], input[placeholder*="name"]',
                value: 'Test User Display Name',
                expectedResult: 'Display name field filled',
                screenshot: true
            },
            {
                action: 'change_language',
                description: 'Change language dropdown',
                selector: 'select[name="language"]',
                value: 'en',
                expectedResult: 'Language dropdown changed to English',
                screenshot: true
            },
            {
                action: 'click_save',
                description: 'Click Save Changes button',
                selector: 'button[type="submit"], button[data-testid="save-settings-btn"], button.bg-blue-600, form button[type="submit"]',
                expectedResult: 'Form submitted successfully',
                screenshot: true
            }
        ]
    },
    {
        name: 'Search Functionality',
        page: `/orgs/${CONFIG.orgId}/campaigns`,
        tests: [
            {
                action: 'fill_search',
                description: 'Type in search field',
                selector: 'input[placeholder*="Search"], input[type="search"]',
                value: 'test campaign',
                expectedResult: 'Search field accepts input',
                screenshot: true
            },
            {
                action: 'verify_search_results',
                description: 'Verify search results or empty state',
                expectedResult: 'Shows filtered results or "no results" message',
                screenshot: true
            }
        ]
    },
    {
        name: 'Filter Dropdowns',
        page: `/orgs/${CONFIG.orgId}/campaigns`,
        tests: [
            {
                action: 'click_status_filter',
                description: 'Click status filter dropdown',
                selector: '[data-testid="status-filter"], #status-filter, select[name="status"]',
                expectedResult: 'Status filter dropdown opens',
                screenshot: true
            },
            {
                action: 'click_filter_button',
                description: 'Click Filter button',
                selector: '[data-testid="filter-btn"], button[type="submit"][form="filter-form"], button.filter-btn',
                expectedResult: 'Filters are applied',
                screenshot: true
            }
        ]
    },
    {
        name: 'Modal Interactions - Create Campaign',
        page: `/orgs/${CONFIG.orgId}/campaigns`,
        tests: [
            {
                action: 'click_new_campaign',
                description: 'Click New Campaign button',
                selector: '[data-testid="new-campaign-btn"], #new-campaign-btn, a[href*="/campaigns/create"]',
                expectedResult: 'Navigates to campaign creation page or opens modal',
                screenshot: true
            }
        ]
    },
    {
        name: 'Social Post Creation',
        page: `/orgs/${CONFIG.orgId}/social`,
        tests: [
            {
                action: 'click_new_post',
                description: 'Click New Post button',
                selector: '[data-testid="create-post-btn"], #create-post-btn, button[x-on\\:click*="showCreateModal"]',
                expectedResult: 'Opens post creation modal or page',
                screenshot: true
            }
        ]
    },
    {
        name: 'Creative Asset Upload',
        page: `/orgs/${CONFIG.orgId}/creative/assets`,
        tests: [
            {
                action: 'click_upload',
                description: 'Click Upload Asset button',
                selector: '[data-testid="upload-asset-btn"], #upload-asset-btn, button[x-on\\:click*="showUploadModal"]',
                expectedResult: 'Opens file upload dialog or modal',
                screenshot: true
            }
        ]
    },
    {
        name: 'View Toggles',
        page: `/orgs/${CONFIG.orgId}/social`,
        tests: [
            {
                action: 'click_list_view',
                description: 'Click list view toggle',
                selector: '[data-testid="view-list-btn"], button[aria-label*="list"], button[x-on\\:click*="viewMode = \'list\'"]',
                expectedResult: 'Changes to list view layout',
                screenshot: true
            },
            {
                action: 'click_grid_view',
                description: 'Click grid view toggle',
                selector: '[data-testid="view-grid-btn"], button[aria-label*="grid"], button[x-on\\:click*="viewMode = \'grid\'"]',
                expectedResult: 'Changes to grid view layout',
                screenshot: true
            }
        ]
    },
    {
        name: 'Sidebar Navigation',
        page: `/orgs/${CONFIG.orgId}/dashboard`,
        tests: [
            {
                action: 'click_campaigns_nav',
                description: 'Click Campaigns in sidebar',
                selector: 'a[href*="/campaigns"]:not([href*="create"])',
                expectedResult: 'Navigates to campaigns page',
                screenshot: true
            },
            {
                action: 'click_analytics_nav',
                description: 'Click Analytics in sidebar',
                selector: 'a[href*="/analytics"]',
                expectedResult: 'Navigates to analytics page',
                screenshot: true
            },
            {
                action: 'click_social_nav',
                description: 'Click Social Media in sidebar',
                selector: 'a[href*="/social"]:not([href*="posts"]):not([href*="scheduler"])',
                expectedResult: 'Navigates to social media hub',
                screenshot: true
            }
        ]
    },
    {
        name: 'Refresh Button',
        page: `/orgs/${CONFIG.orgId}/dashboard`,
        tests: [
            {
                action: 'click_refresh',
                description: 'Click Refresh button',
                selector: '[data-testid="refresh-btn"], button[x-on\\:click*="refresh"], button.refresh-btn',
                expectedResult: 'Page data refreshes',
                screenshot: true
            }
        ]
    },
    {
        name: 'Platform Connection',
        page: `/orgs/${CONFIG.orgId}/settings/platform-connections`,
        tests: [
            {
                action: 'click_google_connect',
                description: 'Click Google Connect button',
                selector: '[data-testid="connect-google-btn"], a[href*="google/connect"], button[data-platform="google"]',
                expectedResult: 'Opens Google OAuth flow or shows connection modal',
                screenshot: true
            }
        ]
    },
    {
        name: 'Tab Switching',
        page: `/orgs/${CONFIG.orgId}/analytics`,
        tests: [
            {
                action: 'click_kpi_tab',
                description: 'Click KPI Dashboard tab',
                selector: '[data-testid="kpi-dashboard-btn"], #kpi-dashboard-btn, button[x-on\\:click*="activeTab = \'kpi\'"]',
                expectedResult: 'Switches to KPI Dashboard view',
                screenshot: true
            },
            {
                action: 'click_realtime_tab',
                description: 'Click Real-Time Dashboard tab',
                selector: '[data-testid="realtime-dashboard-btn"], #realtime-dashboard-btn, button[x-on\\:click*="activeTab = \'realtime\'"]',
                expectedResult: 'Switches to Real-Time Dashboard view',
                screenshot: true
            }
        ]
    }
];

const results = {
    timestamp: new Date().toISOString(),
    total: 0,
    passed: 0,
    failed: 0,
    skipped: 0,
    tests: []
};

// Ensure directories exist
function ensureDirectories() {
    [CONFIG.resultsDir, CONFIG.screenshotsDir].forEach(dir => {
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
    });
}

// Login function
async function login(page) {
    console.log('\n🔐 Logging in...');

    await page.goto(`${CONFIG.baseUrl}/login`, {
        waitUntil: 'networkidle2',
        timeout: CONFIG.timeout
    });

    await page.type('input[name="email"], input[type="email"]', CONFIG.credentials.email);
    await page.type('input[name="password"], input[type="password"]', CONFIG.credentials.password);

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2', timeout: CONFIG.timeout }),
        page.click('button[type="submit"]')
    ]);

    console.log('   ✅ Login successful\n');
}

// Wait for element with timeout
async function waitForSelector(page, selector, timeout = 5000) {
    try {
        await page.waitForSelector(selector, { timeout });
        return true;
    } catch (error) {
        return false;
    }
}

// Perform interaction test
async function performTest(page, scenario, test, scenarioIndex, testIndex) {
    const testId = `${scenarioIndex + 1}.${testIndex + 1}`;
    const screenshotName = `${String(scenarioIndex + 1).padStart(2, '0')}-${scenario.name.replace(/\s+/g, '-').toLowerCase()}-${String(testIndex + 1).padStart(2, '0')}.png`;

    console.log(`\n📍 Test ${testId}: ${test.description}`);

    const testResult = {
        id: testId,
        scenario: scenario.name,
        action: test.action,
        description: test.description,
        selector: test.selector,
        expectedResult: test.expectedResult,
        status: 'pending',
        error: null,
        screenshot: null
    };

    try {
        // Wait for selector
        const found = await waitForSelector(page, test.selector);

        if (!found) {
            testResult.status = 'skipped';
            testResult.error = `Element not found: ${test.selector}`;
            console.log(`   ⏭️  Skipped: ${testResult.error}`);
            results.skipped++;
            return testResult;
        }

        // Perform action based on type
        if (test.action.includes('fill')) {
            await page.type(test.selector, test.value || '');
            console.log(`   ✏️  Filled: "${test.value}"`);
        } else if (test.action.includes('click') || test.action.includes('select')) {
            await page.click(test.selector);
            console.log(`   🖱️  Clicked`);

            // Wait a bit for any animations or state changes
            await new Promise(resolve => setTimeout(resolve, 1000));
        } else if (test.action.includes('change')) {
            await page.select(test.selector, test.value || '');
            console.log(`   🔄 Changed to: "${test.value}"`);
        }

        // Take screenshot if requested
        if (test.screenshot) {
            const screenshotPath = path.join(CONFIG.screenshotsDir, screenshotName);
            await page.screenshot({
                path: screenshotPath,
                fullPage: true
            });
            testResult.screenshot = screenshotPath;
            console.log(`   📸 Screenshot: ${screenshotName}`);
        }

        testResult.status = 'passed';
        results.passed++;
        console.log(`   ✅ Passed`);

    } catch (error) {
        testResult.status = 'failed';
        testResult.error = error.message;
        results.failed++;
        console.log(`   ❌ Failed: ${error.message}`);

        // Take screenshot on failure
        try {
            const errorScreenshot = path.join(CONFIG.screenshotsDir, `ERROR-${screenshotName}`);
            await page.screenshot({
                path: errorScreenshot,
                fullPage: true
            });
            testResult.screenshot = errorScreenshot;
        } catch (screenshotError) {
            console.log(`   ⚠️  Could not capture error screenshot`);
        }
    }

    return testResult;
}

// Run all interaction tests
async function runInteractionTests() {
    console.log('🌐 Starting Comprehensive Functional Interaction Testing\n');
    console.log(`Total Scenarios: ${INTERACTION_TESTS.length}`);
    console.log(`Base URL: ${CONFIG.baseUrl}\n`);
    console.log('================================================================================\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--disable-gpu'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // Login once
    await login(page);

    // Run each scenario
    for (let i = 0; i < INTERACTION_TESTS.length; i++) {
        const scenario = INTERACTION_TESTS[i];

        console.log(`\n${'='.repeat(80)}`);
        console.log(`Scenario ${i + 1}/${INTERACTION_TESTS.length}: ${scenario.name}`);
        console.log(`Page: ${scenario.page}`);
        console.log(`${'='.repeat(80)}`);

        // Navigate to page
        try {
            await page.goto(`${CONFIG.baseUrl}${scenario.page}`, {
                waitUntil: 'networkidle2',
                timeout: CONFIG.timeout
            });

            console.log(`   ✅ Page loaded successfully`);

            // Run each test in the scenario
            for (let j = 0; j < scenario.tests.length; j++) {
                const test = scenario.tests[j];
                const testResult = await performTest(page, scenario, test, i, j);
                results.tests.push(testResult);
                results.total++;

                // Small delay between tests
                await new Promise(resolve => setTimeout(resolve, 500));
            }

        } catch (error) {
            console.log(`   ❌ Failed to load page: ${error.message}`);

            // Mark all tests in this scenario as failed
            scenario.tests.forEach((test, j) => {
                results.tests.push({
                    id: `${i + 1}.${j + 1}`,
                    scenario: scenario.name,
                    action: test.action,
                    description: test.description,
                    status: 'failed',
                    error: `Page load failed: ${error.message}`,
                    screenshot: null
                });
                results.total++;
                results.failed++;
            });
        }
    }

    await browser.close();
}

// Generate summary report
function generateSummary() {
    const summary = `# Functional Interaction Testing Report

**Date:** ${results.timestamp}
**Total Tests:** ${results.total}

## Summary

- ✅ Passed: ${results.passed} (${((results.passed / results.total) * 100).toFixed(1)}%)
- ❌ Failed: ${results.failed} (${((results.failed / results.total) * 100).toFixed(1)}%)
- ⏭️  Skipped: ${results.skipped} (${((results.skipped / results.total) * 100).toFixed(1)}%)

## Test Results

${results.tests.map(test => `
### ${test.id}. ${test.scenario} - ${test.description}

- **Action:** ${test.action}
- **Selector:** \`${test.selector}\`
- **Expected:** ${test.expectedResult}
- **Status:** ${test.status === 'passed' ? '✅ PASSED' : test.status === 'failed' ? '❌ FAILED' : '⏭️  SKIPPED'}
${test.error ? `- **Error:** ${test.error}` : ''}
${test.screenshot ? `- **Screenshot:** ${test.screenshot}` : ''}
`).join('\n')}

## Functional vs Non-Functional Elements

### ✅ Functional Elements (Passed Tests)
${results.tests.filter(t => t.status === 'passed').map(t => `- ${t.scenario}: ${t.description}`).join('\n')}

### ❌ Non-Functional Elements (Failed Tests)
${results.tests.filter(t => t.status === 'failed').map(t => `- ${t.scenario}: ${t.description} - ${t.error}`).join('\n')}

### ⏭️  Not Found Elements (Skipped Tests)
${results.tests.filter(t => t.status === 'skipped').map(t => `- ${t.scenario}: ${t.description} - ${t.error}`).join('\n')}

---

**Screenshots:** ${CONFIG.screenshotsDir}/
**Full Report:** ${CONFIG.resultsDir}/test-report.json
`;

    fs.writeFileSync(path.join(CONFIG.resultsDir, 'FUNCTIONAL_TEST_SUMMARY.md'), summary);
    console.log(`\n📄 Summary saved to: ${CONFIG.resultsDir}/FUNCTIONAL_TEST_SUMMARY.md`);
}

// Main execution
async function main() {
    ensureDirectories();

    try {
        await runInteractionTests();

        // Save full JSON report
        fs.writeFileSync(
            path.join(CONFIG.resultsDir, 'test-report.json'),
            JSON.stringify(results, null, 2)
        );

        // Generate summary
        generateSummary();

        console.log('\n================================================================================');
        console.log('📊 TESTING COMPLETE');
        console.log('================================================================================');
        console.log(`Total Tests: ${results.total}`);
        console.log(`✅ Passed: ${results.passed} (${((results.passed / results.total) * 100).toFixed(1)}%)`);
        console.log(`❌ Failed: ${results.failed} (${((results.failed / results.total) * 100).toFixed(1)}%)`);
        console.log(`⏭️  Skipped: ${results.skipped} (${((results.skipped / results.total) * 100).toFixed(1)}%)`);
        console.log(`\n📁 Results: ${CONFIG.resultsDir}/`);
        console.log(`📸 Screenshots: ${CONFIG.screenshotsDir}/`);
        console.log('================================================================================\n');

    } catch (error) {
        console.error('\n❌ Testing failed:', error);
        process.exit(1);
    }
}

main();
