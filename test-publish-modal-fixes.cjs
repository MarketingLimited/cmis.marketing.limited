#!/usr/bin/env node

const { chromium } = require('playwright');

/**
 * Test script for Publish Modal Touch Target Fixes
 * Tests all critical fixes from Sub-Phase 1A
 */

async function testPublishModalFixes() {
    console.log('🚀 Starting Publish Modal Touch Target Tests...\n');

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const context = await browser.newContext({
        viewport: { width: 375, height: 667 }, // iPhone SE
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();

    const results = {
        passed: [],
        failed: [],
        warnings: []
    };

    try {
        // Login
        console.log('📱 Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[type="email"]', 'admin@cmis.test');
        await page.fill('input[type="password"]', 'password');
        await page.locator('form button[type="submit"]').first().click();
        await page.waitForTimeout(3000);
        console.log('✅ Login successful\n');

        // Test in both locales
        for (const locale of ['ar', 'en']) {
            console.log(`\n${'='.repeat(50)}`);
            console.log(`Testing in ${locale.toUpperCase()} locale`);
            console.log('='.repeat(50));

            // Set locale cookie
            await page.context().addCookies([{
                name: 'app_locale',
                value: locale,
                domain: 'cmis-test.kazaaz.com',
                path: '/'
            }]);
            await page.reload({ waitUntil: 'networkidle' });
            await page.waitForTimeout(1000);

            // Open publish modal
            console.log('\n📂 Opening publish modal...');
            const newPostButton = page.locator('button:has-text("New Post"), button:has-text("منشور جديد")').first();
            const newPostBox = await newPostButton.boundingBox();

            if (newPostBox) {
                console.log(`   "New Post" button: ${newPostBox.width.toFixed(1)}px × ${newPostBox.height.toFixed(1)}px`);
                if (newPostBox.height >= 44) {
                    results.passed.push(`[${locale}] "New Post" button touch target (${newPostBox.height.toFixed(1)}px)`);
                } else {
                    results.failed.push(`[${locale}] "New Post" button too small (${newPostBox.height.toFixed(1)}px < 44px)`);
                }
            }

            await newPostButton.click();
            await page.waitForTimeout(2000);

            // Test 1: Check RTL/LTR direction
            console.log('\n🔍 Test 1: RTL/LTR Direction');
            const modalContainer = page.locator('[x-data*="publishModal"]').first();
            const dir = await modalContainer.getAttribute('dir');
            const expectedDir = locale === 'ar' ? 'rtl' : 'ltr';

            if (dir === expectedDir) {
                console.log(`   ✅ Modal direction is ${dir} (correct for ${locale})`);
                results.passed.push(`[${locale}] Modal direction: ${dir}`);
            } else {
                console.log(`   ❌ Modal direction is ${dir} (expected ${expectedDir} for ${locale})`);
                results.failed.push(`[${locale}] Wrong modal direction: ${dir} (expected ${expectedDir})`);
            }

            // Test 2: Header button touch targets
            console.log('\n🔍 Test 2: Header Button Touch Targets');

            // Close button
            const closeButton = page.locator('button:has(i.fa-times)').first();
            const closeBox = await closeButton.boundingBox();
            if (closeBox) {
                console.log(`   Close (X) button: ${closeBox.width.toFixed(1)}px × ${closeBox.height.toFixed(1)}px`);
                if (closeBox.width >= 44 && closeBox.height >= 44) {
                    results.passed.push(`[${locale}] Close button touch target (${closeBox.width.toFixed(1)}×${closeBox.height.toFixed(1)}px)`);
                } else {
                    results.failed.push(`[${locale}] Close button too small (${closeBox.width.toFixed(1)}×${closeBox.height.toFixed(1)}px)`);
                }
            }

            // Save Draft button
            const saveDraftButton = page.locator('button:has-text("Save Draft"), button:has-text("حفظ كمسودة")').first();
            const saveDraftBox = await saveDraftButton.boundingBox();
            if (saveDraftBox) {
                console.log(`   Save Draft button: ${saveDraftBox.width.toFixed(1)}px × ${saveDraftBox.height.toFixed(1)}px`);
                if (saveDraftBox.height >= 44) {
                    results.passed.push(`[${locale}] Save Draft button touch target (${saveDraftBox.height.toFixed(1)}px)`);
                } else {
                    results.failed.push(`[${locale}] Save Draft button too small (${saveDraftBox.height.toFixed(1)}px)`);
                }
            }

            // Test 3: Toolbar button touch targets
            console.log('\n🔍 Test 3: Toolbar Button Touch Targets');

            const toolbarButtons = [
                { name: 'Bold', selector: 'button:has(i.fa-bold)' },
                { name: 'Italic', selector: 'button:has(i.fa-italic)' },
                { name: 'Underline', selector: 'button:has(i.fa-underline)' },
                { name: 'Strikethrough', selector: 'button:has(i.fa-strikethrough)' },
                { name: 'Emoji', selector: 'button:has(i.fa-smile)' },
                { name: 'Hashtag', selector: 'button:has(i.fa-hashtag)' },
                { name: 'Mention', selector: 'button:has(i.fa-at)' },
                { name: 'AI Assistant', selector: 'button:has(i.fa-magic)' }
            ];

            for (const btn of toolbarButtons) {
                const button = page.locator(btn.selector).first();
                const box = await button.boundingBox();
                if (box) {
                    const status = (box.width >= 44 && box.height >= 44) ? '✅' : '❌';
                    console.log(`   ${status} ${btn.name}: ${box.width.toFixed(1)}px × ${box.height.toFixed(1)}px`);

                    if (box.width >= 44 && box.height >= 44) {
                        results.passed.push(`[${locale}] ${btn.name} button (${box.width.toFixed(1)}×${box.height.toFixed(1)}px)`);
                    } else {
                        results.failed.push(`[${locale}] ${btn.name} button too small (${box.width.toFixed(1)}×${box.height.toFixed(1)}px)`);
                    }
                }
            }

            // Test 4: Platform tabs touch targets
            console.log('\n🔍 Test 4: Platform Tab Touch Targets');

            const globalTab = page.locator('button:has-text("Global Content"), button:has-text("المحتوى العام")').first();
            const tabBox = await globalTab.boundingBox();
            if (tabBox) {
                console.log(`   Global tab: ${tabBox.width.toFixed(1)}px × ${tabBox.height.toFixed(1)}px`);
                if (tabBox.height >= 44) {
                    results.passed.push(`[${locale}] Global tab touch target (${tabBox.height.toFixed(1)}px)`);
                } else {
                    results.failed.push(`[${locale}] Global tab too small (${tabBox.height.toFixed(1)}px)`);
                }
            }

            // Test 5: Unsaved changes warning
            console.log('\n🔍 Test 5: Unsaved Changes Warning');

            // Type some text
            const textarea = page.locator('textarea[x-model*="content.global.text"]').first();
            await textarea.fill('Test content to trigger unsaved changes warning');

            // Try to close (should show confirm dialog)
            page.on('dialog', async dialog => {
                const message = dialog.message();
                console.log(`   Confirm dialog appeared: "${message}"`);

                const expectedKeywords = locale === 'ar'
                    ? ['تغييرات', 'محفوظة', 'إغلاق']
                    : ['unsaved', 'changes', 'close'];

                const hasKeywords = expectedKeywords.some(keyword =>
                    message.toLowerCase().includes(keyword.toLowerCase())
                );

                if (hasKeywords) {
                    results.passed.push(`[${locale}] Unsaved changes warning shown`);
                } else {
                    results.warnings.push(`[${locale}] Unsaved changes warning text may need review`);
                }

                await dialog.dismiss();
            });

            await closeButton.click();
            await page.waitForTimeout(1000);

            // Clear the textarea for next test
            await textarea.fill('');

            // Close modal successfully
            await closeButton.click();
            await page.waitForTimeout(1000);
        }

        // Print results summary
        console.log('\n' + '='.repeat(50));
        console.log('📊 TEST RESULTS SUMMARY');
        console.log('='.repeat(50));

        console.log(`\n✅ PASSED (${results.passed.length}):`);
        results.passed.forEach(test => console.log(`   ✓ ${test}`));

        if (results.warnings.length > 0) {
            console.log(`\n⚠️  WARNINGS (${results.warnings.length}):`);
            results.warnings.forEach(test => console.log(`   ⚠ ${test}`));
        }

        if (results.failed.length > 0) {
            console.log(`\n❌ FAILED (${results.failed.length}):`);
            results.failed.forEach(test => console.log(`   ✗ ${test}`));
        }

        const totalTests = results.passed.length + results.failed.length;
        const passRate = ((results.passed.length / totalTests) * 100).toFixed(1);

        console.log('\n' + '='.repeat(50));
        console.log(`Pass Rate: ${passRate}% (${results.passed.length}/${totalTests} tests)`);
        console.log('='.repeat(50));

    } catch (error) {
        console.error('\n❌ Test failed with error:', error.message);
        results.failed.push(`Fatal error: ${error.message}`);
    } finally {
        await browser.close();
    }

    // Return exit code based on results
    return results.failed.length === 0 ? 0 : 1;
}

// Run tests
testPublishModalFixes()
    .then(exitCode => process.exit(exitCode))
    .catch(err => {
        console.error('Fatal error:', err);
        process.exit(1);
    });
