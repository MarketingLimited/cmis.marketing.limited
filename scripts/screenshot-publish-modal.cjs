#!/usr/bin/env node

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'https://cmis-test.kazaaz.com';
const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

const TEST_CREDENTIALS = {
    email: 'admin@cmis.test',
    password: 'password'
};

const SCREENSHOTS_DIR = path.join(__dirname, '../test-results/tmp');

if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

async function main() {
    console.log('Taking screenshot of publish modal...');

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    // Login
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', TEST_CREDENTIALS.email);
    await page.fill('input[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Go to social manager
    await page.goto(`${BASE_URL}/orgs/${ORG_ID}/social`);
    await page.waitForLoadState('networkidle');
    await sleep(2000);

    // Click create post button
    const createButton = await page.locator('button:has-text("Create"), button:has-text("إنشاء"), [x-on\\:click*="showCreateModal"]').first();
    if (await createButton.count() > 0) {
        await createButton.click();
        await sleep(1500);
    }

    // Screenshot the modal
    await page.screenshot({
        path: path.join(SCREENSHOTS_DIR, 'publish-modal-full.png'),
        fullPage: true
    });

    console.log('Screenshot saved to test-results/tmp/publish-modal-full.png');

    // Try to select "Schedule" option to see timezone display
    const scheduleOption = await page.locator('text=Schedule, text=جدولة, [x-on\\:click*="scheduled"]').first();
    if (await scheduleOption.count() > 0) {
        await scheduleOption.click();
        await sleep(500);
        await page.screenshot({
            path: path.join(SCREENSHOTS_DIR, 'publish-modal-scheduled.png'),
            fullPage: true
        });
        console.log('Screenshot saved to test-results/tmp/publish-modal-scheduled.png');
    }

    await browser.close();
    console.log('Done!');
}

main().catch(console.error);
