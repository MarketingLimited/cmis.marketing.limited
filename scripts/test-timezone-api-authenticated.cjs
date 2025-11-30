#!/usr/bin/env node

/**
 * Test Timezone API Endpoint with Authentication
 * Uses Playwright to authenticate and test the timezone inheritance
 */

const { chromium } = require('playwright');

const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
const BASE_URL = 'https://cmis-test.kazaaz.com';
const LOGIN_EMAIL = 'admin@cmis.test';
const LOGIN_PASSWORD = 'password';

// Test cases
const testCases = [
    {
        name: 'Instagram with Profile Group timezone (Asia/Dubai)',
        integration_ids: ['019ad524-9807-73d4-892e-e1ca9fc6cd84'],
        expected_timezone: 'Asia/Dubai',
        expected_source: 'profile_group'
    },
    {
        name: 'Meta without Profile Group (should inherit from org → UTC)',
        integration_ids: ['c9274137-3ff7-9d6f-0db5-39c4b07260ed'],
        expected_timezone: 'UTC',
        expected_source: 'organization'
    }
];

async function runTests() {
    console.log('🧪 Testing Timezone API Endpoint with Inheritance Hierarchy\n');
    console.log('=' .repeat(80));

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();

    try {
        // Login first
        console.log('🔐 Logging in...');
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', LOGIN_EMAIL);
        await page.fill('input[name="password"]', LOGIN_PASSWORD);
        await page.click('button:has-text("Sign in")');
        await page.waitForURL(/\/orgs\//, { timeout: 10000 });
        console.log('✅ Logged in successfully\n');

        let passCount = 0;
        let failCount = 0;

        for (const testCase of testCases) {
            console.log(`📋 Test: ${testCase.name}`);
            console.log(`   Integration IDs: ${testCase.integration_ids.join(', ')}`);

            try {
                // Make API request with authentication
                const response = await page.request.post(
                    `${BASE_URL}/api/orgs/${ORG_ID}/social/timezone`,
                    {
                        data: {
                            integration_ids: testCase.integration_ids
                        }
                    }
                );

                const status = response.status();
                console.log(`   Status: ${status}`);

                if (status === 200) {
                    const data = await response.json();

                    if (data.success) {
                        const result = data.data;
                        console.log(`   🌍 Timezone: ${result.timezone}`);
                        console.log(`   📍 Source: ${result.timezone_source}`);
                        console.log(`   👥 Profile Group: ${result.profile_group_name || 'N/A'}`);

                        if (result.inheritance_info) {
                            console.log(`   🔗 Inheritance Chain:`);
                            console.log(`      - Social Account: ${result.inheritance_info.social_account || 'NULL'}`);
                            console.log(`      - Profile Group: ${result.inheritance_info.profile_group || 'NULL'}`);
                            console.log(`      - Organization: ${result.inheritance_info.organization || 'NULL'}`);
                            console.log(`      - Final (used): ${result.inheritance_info.final}`);
                            console.log(`      - Source level: ${result.inheritance_info.source}`);
                        }

                        if (result.warning) {
                            console.log(`   ⚠️  Warning: ${result.warning}`);
                        }

                        // Validate expectations
                        const timezoneMatch = result.timezone === testCase.expected_timezone;
                        const sourceMatch = result.timezone_source === testCase.expected_source;

                        if (!timezoneMatch) {
                            console.log(`   ❌ FAIL: Expected timezone "${testCase.expected_timezone}", got "${result.timezone}"`);
                            failCount++;
                        } else if (!sourceMatch) {
                            console.log(`   ❌ FAIL: Expected source "${testCase.expected_source}", got "${result.timezone_source}"`);
                            failCount++;
                        } else {
                            console.log(`   ✅ PASS: Correct timezone and source`);
                            passCount++;
                        }
                    } else {
                        console.log(`   ❌ FAIL: API returned success=false`);
                        console.log(`   Message: ${data.message}`);
                        failCount++;
                    }
                } else {
                    console.log(`   ❌ FAIL: HTTP ${status}`);
                    const text = await response.text();
                    console.log(`   Response: ${text.substring(0, 200)}`);
                    failCount++;
                }

            } catch (error) {
                console.log(`   ❌ FAIL: ${error.message}`);
                failCount++;
            }

            console.log('');
        }

        console.log('='.repeat(80));
        console.log(`\n📊 Test Results: ${passCount} passed, ${failCount} failed`);

        if (failCount === 0) {
            console.log('✅ All tests passed! Timezone inheritance is working correctly.\n');
            await browser.close();
            process.exit(0);
        } else {
            console.log('❌ Some tests failed. Please review the output above.\n');
            await browser.close();
            process.exit(1);
        }

    } catch (error) {
        console.error('❌ Fatal error:', error);
        await browser.close();
        process.exit(1);
    }
}

runTests();
