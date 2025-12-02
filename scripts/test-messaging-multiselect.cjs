/**
 * Test script for multi-select messaging apps in boost modal
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';
const LOGIN_EMAIL = 'admin@cmis.test';
const LOGIN_PASSWORD = 'password';
const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

async function run() {
    console.log('Starting multi-select messaging test...\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1400, height: 900 }
    });
    const page = await context.newPage();

    // Enable console logging
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log(`Browser Error: ${msg.text()}`);
        }
    });

    try {
        // Step 1: Login
        console.log('1. Logging in...');
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', LOGIN_EMAIL);
        await page.fill('input[name="password"]', LOGIN_PASSWORD);
        // Click the visible login button
        await page.locator('button[type="submit"]:has-text("تسجيل الدخول")').click();
        await page.waitForLoadState('networkidle', { timeout: 15000 });

        // Check if we need to select organization
        if (await page.url().includes('select-organization') || await page.locator('text=اختيار المؤسسة').count() > 0) {
            console.log('   Selecting organization...');
            // Navigate directly to the org dashboard
            await page.goto(`${BASE_URL}/orgs/${ORG_ID}/dashboard`);
            await page.waitForLoadState('networkidle');
        }
        console.log('   ✓ Login successful\n');

        // Step 2: Navigate to profile page
        console.log('2. Navigating to profile page...');
        // First get the profile ID
        await page.goto(`${BASE_URL}/orgs/${ORG_ID}/settings/profiles`);
        await page.waitForLoadState('networkidle');

        // Click on first profile link
        const profileLink = await page.locator('a[href*="/settings/profiles/"]').first();
        if (await profileLink.count() > 0) {
            const href = await profileLink.getAttribute('href');
            // Handle both absolute and relative URLs
            const profileUrl = href.startsWith('http') ? href : `${BASE_URL}${href}`;
            await page.goto(profileUrl);
            await page.waitForLoadState('networkidle');
            console.log('   ✓ Profile page loaded\n');
        } else {
            console.log('   ✗ No profile found\n');
            throw new Error('No profile found');
        }

        // Step 3: Click Add Boost button
        console.log('3. Opening boost modal...');
        const addBoostBtn = page.locator('button:has-text("إضافة تعزيز"), button:has-text("Add boost")').first();
        await addBoostBtn.click();
        await page.waitForTimeout(1000);
        console.log('   ✓ Boost modal opened\n');

        // Take screenshot of initial modal
        await page.screenshot({ path: 'test-results/messaging-01-initial.png', fullPage: false });
        console.log('   Screenshot: test-results/messaging-01-initial.png\n');

        // Step 4: Select ad account
        console.log('4. Selecting ad account...');
        const adAccountSelect = page.locator('select[x-model="form.ad_account_id"]');
        await adAccountSelect.waitFor({ state: 'visible' });

        // Get first ad account with "Marketing Dot" or first available
        const options = await adAccountSelect.locator('option').all();
        let selectedValue = '';
        for (const opt of options) {
            const text = await opt.textContent();
            const value = await opt.getAttribute('value');
            if (text.includes('Marketing Dot') || text.includes('3048183365459787')) {
                selectedValue = value;
                console.log(`   Found: ${text}`);
                break;
            }
            if (value && !selectedValue) {
                selectedValue = value;
            }
        }

        if (selectedValue) {
            await adAccountSelect.selectOption(selectedValue);
            await page.waitForTimeout(1500);
            console.log('   ✓ Ad account selected\n');
        }

        // Step 5: Select Lead Generation objective
        console.log('5. Selecting Lead Generation objective...');
        const objectiveSelect = page.locator('select[x-model="form.objective"]');
        await objectiveSelect.waitFor({ state: 'visible' });
        await objectiveSelect.selectOption('OUTCOME_LEADS');
        await page.waitForTimeout(1500);
        console.log('   ✓ Lead Generation selected\n');

        // Take screenshot showing destination types
        await page.screenshot({ path: 'test-results/messaging-02-destinations.png', fullPage: false });
        console.log('   Screenshot: test-results/messaging-02-destinations.png\n');

        // Step 6: Check for multi-select messaging section
        console.log('6. Checking for multi-select messaging section...');

        // Look for the multi-select label
        const multiSelectLabel = page.locator('text=يمكنك اختيار عدة, text=you can select multiple');
        const hasMultiSelect = await multiSelectLabel.count() > 0;
        console.log(`   Multi-select messaging section: ${hasMultiSelect ? '✓ FOUND' : '✗ NOT FOUND'}\n`);

        // Check for messaging destination buttons (checkbox style)
        const messengerBtn = page.locator('button:has-text("ماسنجر"), button:has-text("Messenger")').first();
        const whatsappBtn = page.locator('button:has-text("واتساب"), button:has-text("WhatsApp")').first();
        const instagramBtn = page.locator('button:has-text("رسائل انستغرام"), button:has-text("Instagram Direct")').first();

        const hasMessenger = await messengerBtn.count() > 0;
        const hasWhatsapp = await whatsappBtn.count() > 0;
        const hasInstagram = await instagramBtn.count() > 0;

        console.log(`   Messenger button: ${hasMessenger ? '✓ FOUND' : '✗ NOT FOUND'}`);
        console.log(`   WhatsApp button: ${hasWhatsapp ? '✓ FOUND' : '✗ NOT FOUND'}`);
        console.log(`   Instagram Direct button: ${hasInstagram ? '✓ FOUND' : '✗ NOT FOUND'}\n`);

        // Step 7: Test multi-select by clicking multiple messaging destinations
        console.log('7. Testing multi-select functionality...');

        if (hasMessenger) {
            await messengerBtn.click();
            await page.waitForTimeout(500);
            console.log('   ✓ Clicked Messenger');
        }

        if (hasWhatsapp) {
            await whatsappBtn.click();
            await page.waitForTimeout(500);
            console.log('   ✓ Clicked WhatsApp');
        }

        if (hasInstagram) {
            await instagramBtn.click();
            await page.waitForTimeout(500);
            console.log('   ✓ Clicked Instagram Direct');
        }

        console.log('');

        // Take screenshot showing multi-selection
        await page.screenshot({ path: 'test-results/messaging-03-multiselect.png', fullPage: false });
        console.log('   Screenshot: test-results/messaging-03-multiselect.png\n');

        // Step 8: Check for messaging fields showing
        console.log('8. Checking for messaging account fields...');

        // Check for green box with selected messaging apps
        const selectedAppsBox = page.locator('.bg-green-50');
        const hasSelectedAppsBox = await selectedAppsBox.count() > 0;
        console.log(`   Selected apps box: ${hasSelectedAppsBox ? '✓ FOUND' : '✗ NOT FOUND'}`);

        // Check for WhatsApp dropdown
        const whatsappSelect = page.locator('select[x-model="form.whatsapp_number_id"]');
        const hasWhatsappSelect = await whatsappSelect.isVisible().catch(() => false);
        console.log(`   WhatsApp number dropdown: ${hasWhatsappSelect ? '✓ VISIBLE' : '✗ NOT VISIBLE'}`);

        // Check for Messenger page dropdown
        const messengerSelect = page.locator('select[x-model="form.page_id"]');
        const hasMessengerSelect = await messengerSelect.isVisible().catch(() => false);
        console.log(`   Messenger page dropdown: ${hasMessengerSelect ? '✓ VISIBLE' : '✗ NOT VISIBLE'}`);

        // Check for Instagram dropdown
        const instagramSelect = page.locator('select[x-model="form.instagram_account_id"]');
        const hasInstagramSelect = await instagramSelect.isVisible().catch(() => false);
        console.log(`   Instagram account dropdown: ${hasInstagramSelect ? '✓ VISIBLE' : '✗ NOT VISIBLE'}\n`);

        // Take final screenshot
        await page.screenshot({ path: 'test-results/messaging-04-fields.png', fullPage: false });
        console.log('   Screenshot: test-results/messaging-04-fields.png\n');

        // Step 9: Check messaging accounts data
        console.log('9. Checking messaging accounts data...');

        // Get counts from dropdowns
        if (hasWhatsappSelect) {
            const waOptions = await whatsappSelect.locator('option').count();
            console.log(`   WhatsApp options: ${waOptions - 1} numbers`);
        }

        if (hasMessengerSelect) {
            const msgOptions = await messengerSelect.locator('option').count();
            console.log(`   Messenger options: ${msgOptions - 1} pages`);
        }

        if (hasInstagramSelect) {
            const igOptions = await instagramSelect.locator('option').count();
            console.log(`   Instagram options: ${igOptions - 1} accounts`);
        }

        console.log('\n========================================');
        console.log('Multi-Select Messaging Test Complete!');
        console.log('========================================\n');

        // Summary
        console.log('Summary:');
        console.log(`- Multi-select section: ${hasMultiSelect ? '✓' : '✗'}`);
        console.log(`- Messenger button: ${hasMessenger ? '✓' : '✗'}`);
        console.log(`- WhatsApp button: ${hasWhatsapp ? '✓' : '✗'}`);
        console.log(`- Instagram Direct button: ${hasInstagram ? '✓' : '✗'}`);
        console.log(`- Selected apps box (green): ${hasSelectedAppsBox ? '✓' : '✗'}`);
        console.log(`- WhatsApp dropdown: ${hasWhatsappSelect ? '✓' : '✗'}`);
        console.log(`- Messenger dropdown: ${hasMessengerSelect ? '✓' : '✗'}`);
        console.log(`- Instagram dropdown: ${hasInstagramSelect ? '✓' : '✗'}`);

    } catch (error) {
        console.error('Test error:', error.message);
        await page.screenshot({ path: 'test-results/messaging-error.png', fullPage: true });
        console.log('Error screenshot saved: test-results/messaging-error.png');
    } finally {
        await browser.close();
    }
}

run().catch(console.error);
