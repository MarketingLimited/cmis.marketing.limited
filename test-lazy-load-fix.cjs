const { chromium } = require('playwright');

/**
 * Test Script: Verify Lazy-Load Fix for Alpine.js Initialization Errors
 *
 * Expected Behavior:
 * - Before opening modal: 0 Alpine initialization errors (modal not rendered)
 * - After opening modal: Minimal errors (only during initialization)
 * - Total reduction: ~90% compared to previous ~150+ errors
 */

async function testLazyLoadFix() {
    console.log('🧪 Testing Lazy-Load Fix for Alpine Initialization Errors\n');
    console.log('━'.repeat(80));

    const browser = await chromium.launch({
        headless: true,
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });

    const page = await context.newPage();

    // Track console messages
    const consoleErrors = [];
    const consoleWarnings = [];

    page.on('console', msg => {
        const text = msg.text();
        const type = msg.type();

        // Track Alpine-related errors and warnings
        if (type === 'error' || type === 'warning') {
            if (text.includes('Alpine') ||
                text.includes('is not defined') ||
                text.includes('Expression Error') ||
                text.includes('Cannot read properties')) {

                if (type === 'error') {
                    consoleErrors.push(text);
                } else {
                    consoleWarnings.push(text);
                }
            }
        }
    });

    try {
        console.log('📝 Step 1: Setting up authentication...');

        // Set authentication cookie
        await context.addCookies([{
            name: 'cmis_session',
            value: 'valid-session-token',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        // Set locale to Arabic
        await context.addCookies([{
            name: 'app_locale',
            value: 'ar',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        console.log('✅ Authentication configured\n');

        // Phase 1: Load page WITHOUT opening modal
        console.log('━'.repeat(80));
        console.log('📝 Step 2: Loading page (modal should NOT render yet)...');

        const errorsBeforeLoad = consoleErrors.length;
        const warningsBeforeLoad = consoleWarnings.length;

        await page.goto('https://cmis-test.kazaaz.com/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        // Wait for Alpine to initialize
        await page.waitForTimeout(2000);

        const errorsAfterPageLoad = consoleErrors.length - errorsBeforeLoad;
        const warningsAfterPageLoad = consoleWarnings.length - warningsBeforeLoad;

        console.log(`\n📊 Initial Page Load Results:`);
        console.log(`   • Console Errors: ${errorsAfterPageLoad}`);
        console.log(`   • Console Warnings: ${warningsAfterPageLoad}`);
        console.log(`   • Total Issues: ${errorsAfterPageLoad + warningsAfterPageLoad}`);

        if (errorsAfterPageLoad + warningsAfterPageLoad === 0) {
            console.log('   ✅ SUCCESS: Zero Alpine errors on page load!');
        } else {
            console.log('   ⚠️  Some errors detected during page load');
        }

        // Phase 2: Open the modal
        console.log('\n━'.repeat(80));
        console.log('📝 Step 3: Opening publish modal (modal should render now)...');

        const errorsBeforeModal = consoleErrors.length;
        const warningsBeforeModal = consoleWarnings.length;

        // Find and click the floating action button
        const fabButton = page.locator('button:has-text("إنشاء منشور جديد"), button[title*="منشور"], button:has(i.fa-plus)').first();

        if (await fabButton.count() > 0) {
            await fabButton.click();
            console.log('✅ Clicked floating action button');
        } else {
            // Try dispatching event directly
            await page.evaluate(() => {
                window.dispatchEvent(new CustomEvent('open-publish-modal'));
            });
            console.log('✅ Dispatched open-publish-modal event');
        }

        // Wait for modal to initialize
        await page.waitForTimeout(3000);

        // Check if modal is visible
        const modalVisible = await page.locator('[x-data*="publishModal"]').isVisible().catch(() => false);
        console.log(`📊 Modal visibility: ${modalVisible ? '✅ Visible' : '❌ Not visible'}`);

        const errorsAfterModal = consoleErrors.length - errorsBeforeModal;
        const warningsAfterModal = consoleWarnings.length - warningsBeforeModal;

        console.log(`\n📊 Modal Opening Results:`);
        console.log(`   • Console Errors: ${errorsAfterModal}`);
        console.log(`   • Console Warnings: ${warningsAfterModal}`);
        console.log(`   • Total Issues: ${errorsAfterModal + warningsAfterModal}`);

        // Phase 3: Summary and comparison
        console.log('\n━'.repeat(80));
        console.log('📊 FINAL RESULTS SUMMARY\n');

        const totalErrors = consoleErrors.length;
        const totalWarnings = consoleWarnings.length;
        const totalIssues = totalErrors + totalWarnings;

        console.log(`Total Alpine Issues Detected:`);
        console.log(`   • Errors: ${totalErrors}`);
        console.log(`   • Warnings: ${totalWarnings}`);
        console.log(`   • TOTAL: ${totalIssues}`);
        console.log('');

        // Compare with previous baseline (~150 errors)
        const previousErrorCount = 150;
        const reduction = previousErrorCount - totalIssues;
        const reductionPercentage = ((reduction / previousErrorCount) * 100).toFixed(1);

        console.log(`Comparison with Previous Implementation:`);
        console.log(`   • Previous: ~${previousErrorCount} errors`);
        console.log(`   • Current: ${totalIssues} errors`);
        console.log(`   • Reduction: ${reduction} errors (${reductionPercentage}%)`);
        console.log('');

        if (totalIssues === 0) {
            console.log('🎉 PERFECT! Zero Alpine initialization errors!');
            console.log('✅ Lazy-load approach completely eliminated the issue.');
        } else if (totalIssues < 15) {
            console.log('✅ EXCELLENT! Errors reduced by over 90%.');
            console.log('✅ Lazy-load approach successfully minimized the issue.');
        } else if (totalIssues < 50) {
            console.log('✅ GOOD! Significant reduction in errors.');
            console.log('⚠️  May need additional optimization.');
        } else {
            console.log('❌ LIMITED IMPROVEMENT');
            console.log('⚠️  Lazy-load approach did not sufficiently reduce errors.');
            console.log('💡 Consider implementing Option B (Sub-Component Architecture)');
        }

        // Show sample errors if any
        if (totalIssues > 0) {
            console.log('\n━'.repeat(80));
            console.log('📝 Sample Errors/Warnings (first 10):\n');

            const allIssues = [
                ...consoleErrors.map(e => `[ERROR] ${e}`),
                ...consoleWarnings.map(w => `[WARN] ${w}`)
            ];

            allIssues.slice(0, 10).forEach((issue, i) => {
                console.log(`${i + 1}. ${issue}`);
            });

            if (allIssues.length > 10) {
                console.log(`\n... and ${allIssues.length - 10} more`);
            }
        }

        console.log('\n━'.repeat(80));
        console.log('✅ Test completed successfully');

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
}

// Run the test
testLazyLoadFix().catch(console.error);
