const { chromium } = require('playwright');

async function testMetaPlatformConnectionFix() {
    console.log('Testing Meta Platform Connection Fix');
    console.log('='.repeat(50));

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        // 1. Login
        console.log('\nStep 1: Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');

        // Use a more specific selector for the main login button (with indigo background)
        const loginButton = page.locator('button[type="submit"].bg-indigo-600');
        await loginButton.click();

        // Wait for navigation to dashboard
        await page.waitForURL(/dashboard|orgs/, { timeout: 15000 });
        console.log('OK - Login successful');

        // 2. Navigate to Platform Connections page
        console.log('\nStep 2: Navigating to Platform Connections...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'test-results/platform-connections-list.png', fullPage: true });
        console.log('OK - Platform Connections page loaded');

        // 3. Navigate to Meta Add form
        console.log('\nStep 3: Navigating to Meta Add form...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/add');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'test-results/meta-add-form-auth.png', fullPage: true });

        // Check for 500 error
        const pageContent = await page.content();
        if (pageContent.includes('500') && pageContent.includes('Internal Server Error')) {
            console.log('FAIL - Page shows 500 Internal Server Error');
            return false;
        }
        if (pageContent.includes('UniqueConstraintViolationException')) {
            console.log('FAIL - Page shows UniqueConstraintViolationException');
            return false;
        }
        console.log('OK - Meta Add form loaded (no 500 error)');

        // 4. Check for form elements
        console.log('\nStep 4: Checking form elements...');
        const hasAccessTokenField = await page.$('input[name="access_token"], textarea[name="access_token"]');
        const hasAccountNameField = await page.$('input[name="account_name"]');
        const hasSubmitButton = await page.$('form button[type="submit"]');

        console.log('  Access token field: ' + (hasAccessTokenField ? 'present' : 'NOT FOUND'));
        console.log('  Account name field: ' + (hasAccountNameField ? 'present' : 'NOT FOUND'));
        console.log('  Submit button: ' + (hasSubmitButton ? 'present' : 'NOT FOUND'));

        // 5. Check console errors
        console.log('\nStep 5: Checking console errors...');
        if (consoleErrors.length > 0) {
            console.log('  WARNING: ' + consoleErrors.length + ' console errors found');
        } else {
            console.log('  OK - No JavaScript console errors');
        }

        console.log('\n' + '='.repeat(50));
        console.log('TEST PASSED: Page loads without 500 error');
        console.log('Screenshots saved to test-results/');
        return true;

    } catch (error) {
        console.log('\nTEST ERROR: ' + error.message);
        await page.screenshot({ path: 'test-results/meta-error.png', fullPage: true });
        return false;
    } finally {
        await browser.close();
    }
}

testMetaPlatformConnectionFix()
    .then(success => process.exit(success ? 0 : 1))
    .catch(e => { console.error(e); process.exit(1); });
