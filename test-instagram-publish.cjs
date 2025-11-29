const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    const metaConnectionId = '4a5b6c7d-8e9f-0a1b-2c3d-4e5f6a7b8c9d';

    try {
        console.log('=== META PUBLISHING FLOW TEST ===\n');

        // Step 1: Login
        console.log('Step 1: Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[type="email"]', 'admin@cmis.test');
        await page.fill('input[type="password"]', 'password');
        await page.locator('form button[type="submit"]').first().click();
        await page.waitForTimeout(3000);
        console.log('✅ Logged in\n');

        // Step 2: Check accounts API
        console.log('Step 2: Fetching connected accounts from API...');
        const accountsResponse = await page.request.get(
            'https://cmis-test.kazaaz.com/orgs/' + orgId + '/social/accounts',
            { ignoreHTTPSErrors: true }
        );

        const accountsData = await accountsResponse.json();
        console.log('Accounts API Response:', JSON.stringify(accountsData, null, 2));
        fs.writeFileSync('test-results/accounts-from-api.json', JSON.stringify(accountsData, null, 2));

        // Parse accounts
        let instagramAccounts = [];
        let facebookAccounts = [];

        if (accountsData.success && accountsData.data && accountsData.data.accounts) {
            accountsData.data.accounts.forEach(account => {
                if (account.platform === 'instagram') {
                    instagramAccounts.push(account);
                } else if (account.platform === 'facebook') {
                    facebookAccounts.push(account);
                }
            });
        }

        console.log('\nFound ' + instagramAccounts.length + ' Instagram account(s)');
        console.log('Found ' + facebookAccounts.length + ' Facebook account(s)\n');

        if (instagramAccounts.length === 0 && facebookAccounts.length === 0) {
            console.log('❌ NO ACCOUNTS FOUND!');
            console.log('\nThis means:');
            console.log('1. No Meta accounts are connected in the database');
            console.log('2. Need to connect Instagram/Facebook accounts first');
            console.log('\nCannot proceed with publishing test without connected accounts.\n');
            return;
        }

        // Step 3: Navigate to social page
        console.log('Step 3: Loading social publishing page...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/' + orgId + '/social');
        await page.waitForTimeout(3000);
        await page.screenshot({ path: 'test-results/social-page.png', fullPage: true });
        console.log('✅ Social page loaded\n');

        // Step 4: Get CSRF token
        const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        console.log('CSRF Token extracted:', csrfToken ? 'Yes' : 'No');

        // Step 5: Test publish API directly
        console.log('\nStep 5: Testing publish API directly...');

        const testProfiles = [];
        if (instagramAccounts.length > 0) {
            testProfiles.push(instagramAccounts[0].integration_id);
        }
        if (facebookAccounts.length > 0) {
            testProfiles.push(facebookAccounts[0].integration_id);
        }

        console.log('Publishing to profiles:', testProfiles);

        const publishPayload = {
            profile_ids: testProfiles,
            content: {
                global: {
                    text: 'Test post from automated test - ' + new Date().toISOString(),
                    media: [],
                    link: '',
                    labels: []
                },
                platforms: {}
            },
            is_draft: false
        };

        console.log('Payload:', JSON.stringify(publishPayload, null, 2));

        const publishResponse = await page.request.post(
            'https://cmis-test.kazaaz.com/orgs/' + orgId + '/social/publish-modal/create',
            {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                data: publishPayload,
                ignoreHTTPSErrors: true
            }
        );

        const publishResult = await publishResponse.json();

        console.log('\n=== PUBLISH API RESPONSE ===');
        console.log('Status:', publishResponse.status());
        console.log('Response:', JSON.stringify(publishResult, null, 2));
        console.log('===========================\n');

        fs.writeFileSync('test-results/publish-result.json', JSON.stringify(publishResult, null, 2));

        if (publishResult.success) {
            console.log('✅ PUBLISH SUCCESSFUL!');
            if (publishResult.data) {
                console.log('  Success count: ' + publishResult.data.success_count);
                console.log('  Failed count: ' + publishResult.data.failed_count);
                if (publishResult.data.posts) {
                    publishResult.data.posts.forEach(post => {
                        console.log('  - ' + post.platform + ': ' + post.status + ' ' + (post.error_message || ''));
                    });
                }
            }
        } else {
            console.log('❌ PUBLISH FAILED!');
            console.log('Error:', publishResult.message);
            if (publishResult.errors) {
                console.log('Validation errors:', publishResult.errors);
            }
        }

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        console.error(error.stack);
        await page.screenshot({ path: 'test-results/error-screenshot.png', fullPage: true });
    } finally {
        await context.close();
        await browser.close();
    }
})();
