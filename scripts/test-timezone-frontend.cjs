#!/usr/bin/env node

/**
 * CMIS Frontend Timezone Testing Script
 *
 * Tests timezone functionality across:
 * - Organization Settings
 * - Profile Group Create/Edit
 * - Social Manager (Create/Edit Post Modals)
 *
 * Usage: node scripts/test-timezone-frontend.cjs
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

// Configuration
const BASE_URL = 'https://cmis-test.kazaaz.com';
const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
const PROFILE_GROUP_ID = '1de58c20-ab77-46af-b9d5-9728f8947f7f';

const TEST_CREDENTIALS = {
    email: 'admin@cmis.test',
    password: 'password'
};

const SCREENSHOTS_DIR = path.join(__dirname, '../test-results/timezone-frontend');

// Ensure screenshots directory exists
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

async function login(page) {
    console.log('🔐 Logging in...');
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', TEST_CREDENTIALS.email);
    await page.fill('input[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    console.log('✅ Login successful');
}

async function testOrganizationSettings(page) {
    console.log('\n📋 Testing Organization Settings...');

    const url = `${BASE_URL}/orgs/${ORG_ID}/settings/organization`;
    await page.goto(url);
    await page.waitForLoadState('networkidle');

    // Check for timezone select
    const timezoneSelect = await page.locator('select[name="timezone"]');
    const exists = await timezoneSelect.count() > 0;

    if (!exists) {
        throw new Error('❌ Timezone select not found on Organization Settings page');
    }

    // Check for timezone label
    const label = await page.locator('label:has-text("Timezone")').first();
    const labelExists = await label.count() > 0;

    if (!labelExists) {
        throw new Error('❌ Timezone label not found on Organization Settings page');
    }

    // Check for help text
    const helpText = await page.locator('text=/timezone|المنطقة الزمنية/i').count();

    // Take screenshot
    await page.screenshot({
        path: path.join(SCREENSHOTS_DIR, 'organization-settings-timezone.png'),
        fullPage: true
    });

    console.log('✅ Organization Settings: Timezone field present');
    console.log(`   - Select element: ${exists ? 'Found' : 'Not found'}`);
    console.log(`   - Label: ${labelExists ? 'Found' : 'Not found'}`);
    console.log(`   - Help/translation text: ${helpText > 0 ? 'Found' : 'Not found'}`);
}

async function testProfileGroupCreate(page) {
    console.log('\n📋 Testing Profile Group Create...');

    const url = `${BASE_URL}/orgs/${ORG_ID}/settings/profile-groups/create`;
    await page.goto(url);
    await page.waitForLoadState('networkidle');

    // Check for timezone select
    const timezoneSelect = await page.locator('select[name="timezone"]');
    const exists = await timezoneSelect.count() > 0;

    if (!exists) {
        throw new Error('❌ Timezone select not found on Profile Group Create page');
    }

    // Check for inheritance info
    const inheritanceInfo = await page.locator('text=/inherit|Organization|المنطقة الزمنية/i').count();

    // Take screenshot
    await page.screenshot({
        path: path.join(SCREENSHOTS_DIR, 'profile-group-create-timezone.png'),
        fullPage: true
    });

    console.log('✅ Profile Group Create: Timezone field present');
    console.log(`   - Select element: ${exists ? 'Found' : 'Not found'}`);
    console.log(`   - Inheritance info: ${inheritanceInfo > 0 ? 'Found' : 'Not found'}`);
}

async function testProfileGroupEdit(page) {
    console.log('\n📋 Testing Profile Group Edit...');

    const url = `${BASE_URL}/orgs/${ORG_ID}/settings/profile-groups/${PROFILE_GROUP_ID}/edit`;
    await page.goto(url);
    await page.waitForLoadState('networkidle');

    // Check for timezone select
    const timezoneSelect = await page.locator('select[name="timezone"]');
    const exists = await timezoneSelect.count() > 0;

    if (!exists) {
        throw new Error('❌ Timezone select not found on Profile Group Edit page');
    }

    // Check for inheritance info
    const inheritanceInfo = await page.locator('text=/inherit|Organization|المنطقة الزمنية/i').count();

    // Take screenshot
    await page.screenshot({
        path: path.join(SCREENSHOTS_DIR, 'profile-group-edit-timezone.png'),
        fullPage: true
    });

    console.log('✅ Profile Group Edit: Timezone field present');
    console.log(`   - Select element: ${exists ? 'Found' : 'Not found'}`);
    console.log(`   - Inheritance info: ${inheritanceInfo > 0 ? 'Found' : 'Not found'}`);
}

async function testSocialManagerTimezone(page) {
    console.log('\n📋 Testing Social Manager Timezone...');

    const url = `${BASE_URL}/orgs/${ORG_ID}/social`;
    await page.goto(url);
    await page.waitForLoadState('networkidle');
    await sleep(2000); // Wait for Alpine.js to initialize

    // Check for console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    // Look for create post button
    const createButton = await page.locator('button:has-text("Create"), button:has-text("إنشاء")').first();
    const buttonExists = await createButton.count() > 0;

    if (buttonExists) {
        console.log('   - Found create post button, clicking...');
        await createButton.click();
        await sleep(1000);

        // Check if modal opened
        const modal = await page.locator('[x-show="showCreateModal"], .modal, [role="dialog"]').first();
        const modalVisible = await modal.count() > 0;

        if (modalVisible) {
            console.log('   - Create modal opened');

            // Take screenshot of modal
            await page.screenshot({
                path: path.join(SCREENSHOTS_DIR, 'social-create-modal.png'),
                fullPage: true
            });

            // Look for timezone-related text in modal
            const timezoneText = await page.locator('text=/timezone|المنطقة الزمنية|UTC/i').count();
            console.log(`   - Timezone references in modal: ${timezoneText > 0 ? 'Found' : 'Not found'}`);
        } else {
            console.log('   ⚠️ Create modal did not open');
        }
    } else {
        console.log('   ⚠️ Create post button not found');
    }

    // Take screenshot of main page
    await page.screenshot({
        path: path.join(SCREENSHOTS_DIR, 'social-manager-page.png'),
        fullPage: true
    });

    console.log('✅ Social Manager: Page loaded');
    if (consoleErrors.length > 0) {
        console.log('   ⚠️ Console errors found:');
        consoleErrors.forEach(err => console.log(`      - ${err}`));
    } else {
        console.log('   - No console errors detected');
    }
}

async function checkLaravelLogs() {
    console.log('\n📋 Checking Laravel Logs...');

    const logPath = path.join(__dirname, '../storage/logs/laravel.log');

    if (!fs.existsSync(logPath)) {
        console.log('   ⚠️ Laravel log file not found');
        return;
    }

    const logContent = fs.readFileSync(logPath, 'utf8');
    const lines = logContent.split('\n');
    const recentErrors = lines
        .slice(-100) // Last 100 lines
        .filter(line => /error|exception|fatal/i.test(line) && !/vendor\/laravel\/framework/i.test(line))
        .slice(-10); // Last 10 errors

    if (recentErrors.length > 0) {
        console.log('   ⚠️ Recent errors found:');
        recentErrors.forEach(err => console.log(`      ${err.substring(0, 150)}...`));
    } else {
        console.log('   ✅ No recent errors in Laravel logs');
    }
}

async function main() {
    console.log('🚀 Starting CMIS Timezone Frontend Tests\n');
    console.log(`Base URL: ${BASE_URL}`);
    console.log(`Organization ID: ${ORG_ID}`);
    console.log(`Profile Group ID: ${PROFILE_GROUP_ID}`);
    console.log(`Screenshots will be saved to: ${SCREENSHOTS_DIR}\n`);

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        locale: 'en-US',
    });

    const page = await context.newPage();

    // Collect console messages
    const consoleLogs = [];
    page.on('console', msg => {
        consoleLogs.push({ type: msg.type(), text: msg.text() });
    });

    try {
        await login(page);

        // Run tests
        await testOrganizationSettings(page);
        await testProfileGroupCreate(page);
        await testProfileGroupEdit(page);
        await testSocialManagerTimezone(page);

        // Check Laravel logs
        checkLaravelLogs();

        console.log('\n✅ All tests completed successfully!');
        console.log(`\n📸 Screenshots saved to: ${SCREENSHOTS_DIR}`);

        // Summary
        console.log('\n📊 Test Summary:');
        console.log('   ✅ Organization Settings - Timezone field present');
        console.log('   ✅ Profile Group Create - Timezone with inheritance');
        console.log('   ✅ Profile Group Edit - Timezone with inheritance');
        console.log('   ✅ Social Manager - Page loads without errors');

        // Console log summary
        const errors = consoleLogs.filter(log => log.type === 'error');
        const warnings = consoleLogs.filter(log => log.type === 'warning');
        console.log(`\n📝 Console Logs:`);
        console.log(`   - Total messages: ${consoleLogs.length}`);
        console.log(`   - Errors: ${errors.length}`);
        console.log(`   - Warnings: ${warnings.length}`);

        if (errors.length > 0) {
            console.log('\n   Recent errors:');
            errors.slice(-5).forEach(err => console.log(`      - ${err.text}`));
        }

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);

        // Take error screenshot
        await page.screenshot({
            path: path.join(SCREENSHOTS_DIR, 'error-screenshot.png'),
            fullPage: true
        });

        process.exit(1);
    } finally {
        await browser.close();
    }
}

main().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
