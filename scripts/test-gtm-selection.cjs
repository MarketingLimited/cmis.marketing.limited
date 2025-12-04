const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Set locale cookie
    await page.context().addCookies([{
        name: 'app_locale',
        value: 'ar',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    // Collect console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    console.log('Navigating to login...');
    await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle' });

    // Login
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    const submitBtn = page.locator('button:has-text("تسجيل الدخول"), button:has-text("Log in")').first();
    await submitBtn.click({ timeout: 10000 });
    await page.waitForURL('**/dashboard', { timeout: 30000 });

    console.log('Logged in, navigating to Google assets page...');

    // Navigate to assets page
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/google/019adfdc-5f7a-7125-9b74-8a4cac310de2/assets', { waitUntil: 'networkidle', timeout: 60000 });

    // Wait for Tag Manager containers to load (poll until available)
    console.log('Waiting for Tag Manager containers to load...');
    let containers = [];
    for (let i = 0; i < 20; i++) {
        await page.waitForTimeout(1000);
        containers = await page.evaluate(() => {
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
            return alpineData && alpineData.tagManagerContainers ? alpineData.tagManagerContainers : [];
        });
        if (containers.length > 0) {
            console.log('Containers loaded: ' + containers.length);
            break;
        }
    }

    // Scroll to Tag Manager section
    const tagManagerHeading = page.locator('h3:has-text("Google Tag Manager")');
    if (await tagManagerHeading.count() > 0) {
        await tagManagerHeading.scrollIntoViewIfNeeded();
        await page.waitForTimeout(1000);
    }

    // Check Tag Manager data
    const tagManagerSection = await page.evaluate(() => {
        const alpineData = Alpine.$data(document.querySelector('[x-data]'));
        if (alpineData) {
            return {
                containers: alpineData.tagManagerContainers ? alpineData.tagManagerContainers.slice(0, 3) : [],
                containerCount: alpineData.tagManagerContainers ? alpineData.tagManagerContainers.length : 0,
                selected: alpineData.selectedTagManager || [],
                loading: alpineData.loading ? alpineData.loading.tagManager : false,
                error: alpineData.errors ? alpineData.errors.tagManager : null
            };
        }
        return null;
    });

    console.log('\nTag Manager Status:');
    console.log('  Containers: ' + (tagManagerSection ? tagManagerSection.containerCount : 0));
    console.log('  Currently selected: ' + (tagManagerSection && tagManagerSection.selected ? tagManagerSection.selected.length : 0));
    console.log('  Loading: ' + (tagManagerSection ? tagManagerSection.loading : 'unknown'));
    console.log('  Error: ' + (tagManagerSection && tagManagerSection.error ? tagManagerSection.error : 'none'));
    console.log('  First 3 containers:', JSON.stringify(tagManagerSection ? tagManagerSection.containers : [], null, 2));

    // Test checkbox selection
    if (tagManagerSection && tagManagerSection.containerCount > 0) {
        console.log('\n=== Testing Checkbox Selection ===');

        // Get total checkbox count
        const totalCheckboxes = await page.locator('input[name="tag_manager[]"]').count();
        console.log('Total Tag Manager checkboxes found: ' + totalCheckboxes);

        // Take screenshot before click
        await page.screenshot({ path: 'test-results/gtm-before-click.png', fullPage: true });

        // Get selected count before click
        const selectedBefore = await page.evaluate(() => {
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
            return alpineData.selectedTagManager.length;
        });
        console.log('Selected before click: ' + selectedBefore);

        // Click the FIRST Tag Manager checkbox
        const firstCheckbox = page.locator('input[name="tag_manager[]"]').first();
        await firstCheckbox.scrollIntoViewIfNeeded();
        await page.waitForTimeout(500);
        await firstCheckbox.click();
        await page.waitForTimeout(1000);

        // Check selected count after click
        const selectedAfter = await page.evaluate(() => {
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
            return {
                count: alpineData.selectedTagManager.length,
                values: alpineData.selectedTagManager
            };
        });
        console.log('Selected after click: ' + selectedAfter.count);
        console.log('Selected values: ' + JSON.stringify(selectedAfter.values));

        // Count checked checkboxes visually
        const checkedCount = await page.locator('input[name="tag_manager[]"]:checked').count();
        console.log('Visually checked checkboxes: ' + checkedCount + ' of ' + totalCheckboxes);

        // Take screenshot after click
        await page.screenshot({ path: 'test-results/gtm-after-click.png', fullPage: true });

        // Verify the fix
        if (checkedCount === 1 && selectedAfter.count === 1) {
            console.log('\nSUCCESS: Fix verified! Only 1 checkbox selected as expected.');
        } else if (checkedCount > 1 || selectedAfter.count > 1) {
            console.log('\nBUG STILL EXISTS: Multiple checkboxes selected unexpectedly!');
        } else if (checkedCount === 0 || selectedAfter.count === 0) {
            console.log('\nNo checkboxes selected (might have been pre-selected and we unselected)');
        }

        // Additional verification: click a different checkbox
        if (totalCheckboxes > 1) {
            console.log('\n--- Clicking second checkbox ---');
            const secondCheckbox = page.locator('input[name="tag_manager[]"]').nth(1);
            await secondCheckbox.scrollIntoViewIfNeeded();
            await secondCheckbox.click();
            await page.waitForTimeout(500);

            const afterSecondClick = await page.evaluate(() => {
                const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                return alpineData.selectedTagManager.length;
            });
            const checkedAfterSecond = await page.locator('input[name="tag_manager[]"]:checked').count();

            console.log('After second click - selected: ' + afterSecondClick + ', visually checked: ' + checkedAfterSecond);

            if (afterSecondClick === 2 && checkedAfterSecond === 2) {
                console.log('SUCCESS: Both checkboxes work independently!');
            }

            await page.screenshot({ path: 'test-results/gtm-after-second-click.png', fullPage: true });
        }
    } else {
        console.log('\nNo Tag Manager containers found for testing');
    }

    if (consoleErrors.length > 0) {
        console.log('\n=== Console Errors ===');
        consoleErrors.forEach(e => console.log('  -', e));
    } else {
        console.log('\nNo console errors detected');
    }

    await browser.close();
    console.log('\n=== Test Complete ===');
})();
