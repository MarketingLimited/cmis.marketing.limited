const puppeteer = require('puppeteer');

async function testActionLogging() {
    console.log('Testing Admin Action Logging...\n');

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
        await page.type('input[name="email"]', 'admin@cmis.test');
        await page.type('input[name="password"]', 'password');
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0' })
        ]);
        console.log('   ✓ Logged in successfully');

        // Get CSRF token
        const csrfToken = await page.evaluate(() => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : null;
        });
        console.log('   ✓ CSRF token obtained');

        // Get a plan to toggle
        console.log('\n2. Fetching plans...');
        const plansResponse = await page.evaluate(async () => {
            const resp = await fetch('/super-admin/plans', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return await resp.json();
        });
        
        const plans = plansResponse.data || plansResponse.plans || [];
        console.log(`   Found ${plans.length} plans`);
        
        if (plans.length > 0) {
            const plan = plans[0];
            console.log(`   Using plan: ${plan.name} (ID: ${plan.plan_id})`);
            
            // Toggle the plan's active status - this should log an action
            console.log('\n3. Toggling plan status (should log action)...');
            const toggleResponse = await page.evaluate(async (planId, csrf) => {
                try {
                    const resp = await fetch(`/super-admin/plans/${planId}/toggle-active`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    return { status: resp.status, data: await resp.json() };
                } catch (e) {
                    return { error: e.message };
                }
            }, plan.plan_id, csrfToken);
            
            console.log('   Response:', JSON.stringify(toggleResponse, null, 2));
            
            // Toggle back to restore original state
            console.log('\n4. Toggling back to restore original state...');
            await page.evaluate(async (planId, csrf) => {
                await fetch(`/super-admin/plans/${planId}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            }, plan.plan_id, csrfToken);
            console.log('   ✓ Restored original state');
        }
        
        console.log('\n✓ Test completed');
    } catch (e) {
        console.error('Error:', e.message);
    }

    await browser.close();
}

testActionLogging().catch(console.error);
