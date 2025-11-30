#!/usr/bin/env node

/**
 * Test Timezone API Endpoint with Inheritance Hierarchy
 *
 * Tests the 3-level inheritance: Social Account → Profile Group → Organization
 */

const https = require('https');

const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
const BASE_URL = 'https://cmis-test.kazaaz.com';

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
    },
    {
        name: 'Multiple integrations with same timezone',
        integration_ids: ['019ad524-9807-73d4-892e-e1ca9fc6cd84', '019ad524-a408-71e1-b180-e70d5c64958c'],
        // This will test if they have the same timezone or different
        expected_timezone: null // Don't know yet
    }
];

async function callTimezoneAPI(integrationIds) {
    return new Promise((resolve, reject) => {
        const postData = JSON.stringify({
            integration_ids: integrationIds
        });

        const options = {
            hostname: 'cmis-test.kazaaz.com',
            port: 443,
            path: `/api/orgs/${ORG_ID}/social/timezone`,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData),
                'Accept': 'application/json'
            }
        };

        const req = https.request(options, (res) => {
            let data = '';

            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                try {
                    const parsed = JSON.parse(data);
                    resolve({ status: res.statusCode, data: parsed });
                } catch (e) {
                    reject(new Error(`Failed to parse response: ${e.message}\nData: ${data}`));
                }
            });
        });

        req.on('error', (e) => {
            reject(e);
        });

        req.write(postData);
        req.end();
    });
}

async function runTests() {
    console.log('🧪 Testing Timezone API Endpoint with Inheritance Hierarchy\n');
    console.log('=' .repeat(80));

    let passCount = 0;
    let failCount = 0;

    for (const testCase of testCases) {
        console.log(`\n📋 Test: ${testCase.name}`);
        console.log(`   Integration IDs: ${testCase.integration_ids.join(', ')}`);

        try {
            const response = await callTimezoneAPI(testCase.integration_ids);

            console.log(`   ✅ Status: ${response.status}`);

            if (response.data.success) {
                const data = response.data.data;
                console.log(`   🌍 Timezone: ${data.timezone}`);
                console.log(`   📍 Source: ${data.timezone_source}`);
                console.log(`   👥 Profile Group: ${data.profile_group_name || 'N/A'}`);

                if (data.inheritance_info) {
                    console.log(`   🔗 Inheritance Chain:`);
                    console.log(`      - Social Account: ${data.inheritance_info.social_account || 'NULL'}`);
                    console.log(`      - Profile Group: ${data.inheritance_info.profile_group || 'NULL'}`);
                    console.log(`      - Organization: ${data.inheritance_info.organization || 'NULL'}`);
                    console.log(`      - Final (used): ${data.inheritance_info.final}`);
                    console.log(`      - Source level: ${data.inheritance_info.source}`);
                }

                if (data.warning) {
                    console.log(`   ⚠️  Warning: ${data.warning}`);
                }

                // Validate expectations
                if (testCase.expected_timezone && data.timezone !== testCase.expected_timezone) {
                    console.log(`   ❌ FAIL: Expected timezone "${testCase.expected_timezone}", got "${data.timezone}"`);
                    failCount++;
                } else if (testCase.expected_source && data.timezone_source !== testCase.expected_source) {
                    console.log(`   ❌ FAIL: Expected source "${testCase.expected_source}", got "${data.timezone_source}"`);
                    failCount++;
                } else {
                    console.log(`   ✅ PASS: Correct timezone and source`);
                    passCount++;
                }
            } else {
                console.log(`   ❌ API returned success=false`);
                console.log(`   Message: ${response.data.message}`);
                failCount++;
            }

        } catch (error) {
            console.log(`   ❌ FAIL: ${error.message}`);
            failCount++;
        }
    }

    console.log('\n' + '='.repeat(80));
    console.log(`\n📊 Test Results: ${passCount} passed, ${failCount} failed`);

    if (failCount === 0) {
        console.log('✅ All tests passed! Timezone inheritance is working correctly.\n');
        process.exit(0);
    } else {
        console.log('❌ Some tests failed. Please review the output above.\n');
        process.exit(1);
    }
}

runTests().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
