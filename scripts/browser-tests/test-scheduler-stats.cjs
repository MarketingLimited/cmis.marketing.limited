const puppeteer = require('puppeteer');

async function testSchedulerStats() {
    console.log('Testing Scheduler Stats Display on System Health Page...\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login first
        console.log('1. Logging in as admin...');
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle0' });
        await page.waitForSelector('input[name="email"]', { visible: true });
        await page.type('input[name="email"]', 'admin@cmis.test');
        await page.type('input[name="password"]', 'password');

        await new Promise(r => setTimeout(r, 500));

        await page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button[type="submit"]'));
            const signInBtn = buttons.find(b => b.textContent.includes('Sign In') || b.textContent.includes('تسجيل الدخول'));
            if (signInBtn && signInBtn.offsetParent !== null) {
                signInBtn.click();
            }
        });

        await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 30000 });
        console.log('   ✓ Logged in successfully');

        // Navigate to System Health page
        console.log('\n2. Navigating to System Health page...');
        await page.goto('https://cmis-test.kazaaz.com/super-admin/system/health', { waitUntil: 'networkidle0' });
        console.log('   ✓ Page loaded');

        // Wait for health data to load
        console.log('\n3. Waiting for health data to load...');
        await new Promise(r => setTimeout(r, 3000));
        console.log('   ✓ Data loading wait complete');

        // Test the API directly
        console.log('\n4. Testing API response for scheduler...');
        const apiResponse = await page.evaluate(async () => {
            const resp = await fetch('/super-admin/system/health', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return await resp.json();
        });

        console.log('   API Response scheduler data:');
        const schedulerCheck = apiResponse.data?.checks?.scheduler || apiResponse.checks?.scheduler;
        console.log('   - status:', schedulerCheck?.status);
        console.log('   - last_run:', schedulerCheck?.last_run);
        console.log('   - next_run:', schedulerCheck?.next_run);
        console.log('   - tasks_count:', schedulerCheck?.tasks_count);

        // Check for issues
        const hasLastRun = schedulerCheck?.last_run && schedulerCheck?.last_run !== '-';
        const hasNextRun = schedulerCheck?.next_run && schedulerCheck?.next_run !== '-';
        const hasTasksCount = schedulerCheck?.tasks_count !== undefined;

        console.log('\n5. Verification:');
        console.log(`   - Has tasks_count: ${hasTasksCount ? '✓' : '✗'} (${schedulerCheck?.tasks_count || 'undefined'})`);
        console.log(`   - Has last_run: ${hasLastRun ? '✓ REAL' : '⚠ Default "-"'} (${schedulerCheck?.last_run})`);
        console.log(`   - Has next_run: ${hasNextRun ? '✓ REAL' : '⚠ Default "-"'} (${schedulerCheck?.next_run})`);

        // Get scheduler section from page UI
        console.log('\n6. Checking scheduler section in UI...');
        const schedulerUI = await page.evaluate(() => {
            const elements = document.querySelectorAll('h3');
            for (const el of elements) {
                if (el.textContent.includes('Scheduler') || el.textContent.includes('المجدول')) {
                    const parent = el.closest('.bg-white, .dark\\:bg-gray-800');
                    if (parent) {
                        // Get all text content
                        return {
                            found: true,
                            text: parent.textContent.replace(/\s+/g, ' ').trim().substring(0, 400)
                        };
                    }
                }
            }
            return { found: false };
        });

        if (schedulerUI.found) {
            console.log('   ✓ Found scheduler section');
            console.log('   Content:', schedulerUI.text);
        } else {
            console.log('   ✗ Scheduler section not found');
        }

        // Take screenshot of scheduler section
        await page.screenshot({ path: '/tmp/scheduler-stats-test.png', fullPage: true });
        console.log('\n   Screenshot saved to /tmp/scheduler-stats-test.png');

        // Summary
        console.log('\n' + '='.repeat(50));
        console.log('SUMMARY:');
        if (hasTasksCount && schedulerCheck.tasks_count > 0) {
            console.log(`   ✓ Scheduler shows ${schedulerCheck.tasks_count} scheduled tasks`);
        } else {
            console.log('   ⚠ No scheduled tasks configured');
        }

        if (hasNextRun) {
            console.log(`   ✓ Next run shows real time: ${schedulerCheck.next_run}`);
        } else {
            console.log('   ⚠ Next run shows placeholder "-"');
        }

        console.log('\n✓ Test completed');

    } catch (e) {
        console.error('Error:', e.message);
        await page.screenshot({ path: '/tmp/scheduler-stats-error.png', fullPage: true });
    }

    await browser.close();
}

testSchedulerStats().catch(console.error);
