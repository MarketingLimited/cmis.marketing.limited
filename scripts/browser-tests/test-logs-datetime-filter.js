const puppeteer = require('puppeteer');

async function testLogsDatetimeFilter() {
    console.log('Testing Logs Custom DateTime Range Filter...\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // Login first
    console.log('1. Logging in...');
    await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@cmis.test');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ waitUntil: 'networkidle0' })
    ]);
    console.log('   ✓ Logged in successfully');

    // Navigate to logs page
    console.log('\n2. Navigating to logs page...');
    await page.goto('https://cmis-test.kazaaz.com/super-admin/system/logs', { waitUntil: 'networkidle0' });
    await page.waitForTimeout(2000);

    // Take screenshot of initial state
    await page.screenshot({ path: '/tmp/logs-initial.png', fullPage: true });
    console.log('   ✓ Screenshot saved: /tmp/logs-initial.png');

    // Check if date filter dropdown exists with all options
    console.log('\n3. Checking date filter dropdown...');
    const dateOptions = await page.evaluate(() => {
        const select = document.querySelector('select[x-model="filters.date"]');
        if (!select) return null;
        return Array.from(select.options).map(opt => ({ value: opt.value, text: opt.textContent }));
    });

    console.log('   Date filter options found:');
    if (dateOptions) {
        dateOptions.forEach(opt => console.log(`   - ${opt.value}: "${opt.text}"`));
    } else {
        console.log('   ERROR: Date filter dropdown not found!');
    }

    // Check for custom date range option
    const hasCustomOption = dateOptions && dateOptions.some(opt => opt.value === 'custom');
    const hasMonthOption = dateOptions && dateOptions.some(opt => opt.value === 'month');
    console.log(`\n   Has "month" option (Last 30 Days): ${hasMonthOption ? '✓' : '✗'}`);
    console.log(`   Has "custom" option: ${hasCustomOption ? '✓' : '✗'}`);

    // Select custom date range
    console.log('\n4. Testing custom date range selection...');
    await page.select('select[x-model="filters.date"]', 'custom');
    await page.waitForTimeout(500);

    // Check if custom date inputs appeared
    const customInputsVisible = await page.evaluate(() => {
        const fromInput = document.querySelector('input[x-model="filters.date_from"]');
        const toInput = document.querySelector('input[x-model="filters.date_to"]');
        const applyBtn = document.querySelector('button[class*="bg-red"]');
        return {
            fromInputExists: !!fromInput,
            fromInputVisible: fromInput ? fromInput.offsetParent !== null : false,
            fromInputValue: fromInput?.value || '',
            toInputExists: !!toInput,
            toInputVisible: toInput ? toInput.offsetParent !== null : false,
            toInputValue: toInput?.value || '',
            applyButtonExists: !!applyBtn
        };
    });

    console.log('   From Date input:');
    console.log(`     - Exists: ${customInputsVisible.fromInputExists ? '✓' : '✗'}`);
    console.log(`     - Visible: ${customInputsVisible.fromInputVisible ? '✓' : '✗'}`);
    console.log(`     - Default value: ${customInputsVisible.fromInputValue || '(empty)'}`);

    console.log('   To Date input:');
    console.log(`     - Exists: ${customInputsVisible.toInputExists ? '✓' : '✗'}`);
    console.log(`     - Visible: ${customInputsVisible.toInputVisible ? '✓' : '✗'}`);
    console.log(`     - Default value: ${customInputsVisible.toInputValue || '(empty)'}`);

    console.log(`   Apply Filter button: ${customInputsVisible.applyButtonExists ? '✓' : '✗'}`);

    // Take screenshot with custom range visible
    await page.screenshot({ path: '/tmp/logs-custom-range.png', fullPage: true });
    console.log('\n   ✓ Screenshot saved: /tmp/logs-custom-range.png');

    // Test the API with custom date range
    console.log('\n5. Testing API with custom date range...');
    const apiResponse = await page.evaluate(async () => {
        const now = new Date();
        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);

        const formatDateTime = (d) => {
            return d.toISOString().slice(0, 16).replace('T', 'T');
        };

        const params = new URLSearchParams({
            date: 'custom',
            date_from: formatDateTime(weekAgo),
            date_to: formatDateTime(now)
        });

        try {
            const resp = await fetch(`/super-admin/system/logs?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return await resp.json();
        } catch (e) {
            return { error: e.message };
        }
    });

    if (apiResponse.error) {
        console.log(`   ERROR: ${apiResponse.error}`);
    } else {
        console.log(`   ✓ API returned ${apiResponse.logs?.length || 0} logs`);
        console.log(`   ✓ Pagination: page ${apiResponse.pagination?.current_page}/${apiResponse.pagination?.last_page}, total ${apiResponse.pagination?.total}`);
    }

    // Check for console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });
    await page.waitForTimeout(1000);

    if (consoleErrors.length > 0) {
        console.log('\n6. Console errors found:');
        consoleErrors.forEach(err => console.log(`   - ${err}`));
    } else {
        console.log('\n6. No console errors ✓');
    }

    await browser.close();

    // Summary
    console.log('\n' + '='.repeat(50));
    console.log('SUMMARY:');
    console.log('='.repeat(50));
    const allPassed = hasMonthOption && hasCustomOption &&
                      customInputsVisible.fromInputExists &&
                      customInputsVisible.toInputExists;

    if (allPassed) {
        console.log('✓ All checks passed!');
        console.log('✓ Custom datetime range filter is working correctly');
    } else {
        console.log('✗ Some checks failed');
    }
}

testLogsDatetimeFilter().catch(console.error);
