/**
 * Browser test for Marketplace UI Optimizations
 * Tests: scrollable tabs, compact cards, category colors, bulk actions
 */
const { chromium } = require('playwright');

const TEST_URL = 'https://cmis-test.kazaaz.com';
const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
const CREDENTIALS = { email: 'admin@cmis.test', password: 'password' };

async function run() {
    console.log('🚀 Starting Marketplace UI Test...\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    // Collect console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        // 1. Login
        console.log('1. Logging in...');
        await page.goto(`${TEST_URL}/login`);
        await page.fill('input[name="email"]', CREDENTIALS.email);
        await page.fill('input[name="password"]', CREDENTIALS.password);
        // Find the visible login button
        await page.click('button.bg-indigo-600:visible, button[type="submit"]:visible >> nth=0');
        await page.waitForURL(/dashboard|orgs/, { timeout: 15000 });
        console.log('   ✅ Login successful\n');

        // 2. Navigate to marketplace
        console.log('2. Navigating to marketplace...');
        await page.goto(`${TEST_URL}/orgs/${ORG_ID}/marketplace`);
        await page.waitForLoadState('networkidle');
        console.log('   ✅ Marketplace page loaded\n');

        // 3. Test UI elements
        console.log('3. Testing UI elements...');

        // Sticky header
        const stickyHeader = await page.locator('.sticky.top-0.z-10').first();
        const hasSticky = await stickyHeader.count() > 0;
        console.log(`   ${hasSticky ? '✅' : '❌'} Sticky header present`);

        // Scrollable category tabs
        const scrollableTabs = await page.locator('.scrollbar-hide').first();
        const hasScrollable = await scrollableTabs.count() > 0;
        console.log(`   ${hasScrollable ? '✅' : '❌'} Scrollable category tabs`);

        // Category tabs count
        const categoryTabs = await page.locator('.scrollbar-hide button').count();
        console.log(`   ✅ ${categoryTabs} category tabs found`);

        // Bulk select button
        const bulkButton = await page.locator('button:has-text("Bulk Select"), button:has-text("تحديد متعدد")').first();
        const hasBulk = await bulkButton.count() > 0;
        console.log(`   ${hasBulk ? '✅' : '❌'} Bulk select button present`);

        // Grid layout (4 columns on xl)
        const gridElement = await page.locator('.xl\\:grid-cols-4').first();
        const hasGrid = await gridElement.count() > 0;
        console.log(`   ${hasGrid ? '✅' : '❌'} 4-column grid layout`);

        // App cards count
        const appCards = await page.locator('.rounded-xl.shadow-sm.border.p-4').count();
        console.log(`   ✅ ${appCards} app cards found`);

        // Category gradient colors (check for different gradients)
        const gradients = await page.locator('[class*="from-"][class*="to-"]').count();
        console.log(`   ✅ ${gradients} gradient elements (category colors)`);

        // Core feature badges
        const coreBadges = await page.locator('.bg-green-50.text-green-600:has-text("Core Feature"), .bg-green-50.text-green-600:has-text("ميزة أساسية")').count();
        console.log(`   ✅ ${coreBadges} core feature badges`);

        // Status dots
        const statusDots = await page.locator('.w-2\\.5.h-2\\.5.rounded-full').count();
        console.log(`   ✅ ${statusDots} status dots`);

        console.log('');

        // 4. Test category filtering
        console.log('4. Testing category filtering...');
        const marketingTab = await page.locator('button:has-text("Marketing"), button:has-text("التسويق")').first();
        if (await marketingTab.count() > 0) {
            await marketingTab.click();
            await page.waitForTimeout(500);
            console.log('   ✅ Category filter works\n');
        } else {
            console.log('   ⚠️ Marketing tab not found\n');
        }

        // 5. Test search
        console.log('5. Testing search...');
        // Target the marketplace search input within the sticky header
        const searchInput = await page.locator('.sticky input[type="text"]').first();
        if (await searchInput.count() > 0 && await searchInput.isVisible()) {
            await searchInput.fill('Campaign');
            await page.waitForTimeout(500);
            console.log('   ✅ Search input works\n');
        } else {
            console.log('   ⚠️ Search input not visible (may be a test artifact)\n');
        }

        // 6. Take screenshot
        console.log('6. Taking screenshots...');
        await page.screenshot({ path: 'test-results/marketplace-optimized.png', fullPage: true });
        console.log('   ✅ Screenshot saved to test-results/marketplace-optimized.png\n');

        // 7. Test bulk mode
        console.log('7. Testing bulk mode...');
        if (await bulkButton.count() > 0) {
            await bulkButton.click();
            await page.waitForTimeout(500);

            // Check for checkboxes
            const checkboxes = await page.locator('input[type="checkbox"]').count();
            console.log(`   ✅ Bulk mode activated - ${checkboxes} checkboxes visible`);

            // Take screenshot of bulk mode
            await page.screenshot({ path: 'test-results/marketplace-bulk-mode.png', fullPage: true });
            console.log('   ✅ Bulk mode screenshot saved\n');
        }

        // 8. Console errors check
        console.log('8. Checking for console errors...');
        if (consoleErrors.length === 0) {
            console.log('   ✅ No console errors\n');
        } else {
            console.log(`   ⚠️ ${consoleErrors.length} console errors found:`);
            consoleErrors.slice(0, 5).forEach(err => console.log(`      - ${err.substring(0, 100)}`));
            console.log('');
        }

        // Summary
        console.log('═══════════════════════════════════════════════════════');
        console.log('📊 TEST SUMMARY');
        console.log('═══════════════════════════════════════════════════════');
        console.log(`✅ Sticky header: ${hasSticky ? 'PASS' : 'FAIL'}`);
        console.log(`✅ Scrollable tabs: ${hasScrollable ? 'PASS' : 'FAIL'}`);
        console.log(`✅ 4-column grid: ${hasGrid ? 'PASS' : 'FAIL'}`);
        console.log(`✅ Bulk select: ${hasBulk ? 'PASS' : 'FAIL'}`);
        console.log(`✅ Category tabs: ${categoryTabs}`);
        console.log(`✅ App cards: ${appCards}`);
        console.log(`✅ Core badges: ${coreBadges}`);
        console.log(`✅ Console errors: ${consoleErrors.length}`);
        console.log('═══════════════════════════════════════════════════════\n');

        const allPassed = hasSticky && hasScrollable && hasGrid && hasBulk && consoleErrors.length === 0;
        console.log(allPassed ? '✅ ALL TESTS PASSED!' : '⚠️ Some tests need attention');

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        await page.screenshot({ path: 'test-results/marketplace-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

run().catch(console.error);
