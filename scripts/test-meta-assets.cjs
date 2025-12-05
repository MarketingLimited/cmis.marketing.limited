/**
 * Test Meta Assets page for WhatsApp/Catalogs fix and Business Managers section
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';
const ORG_ID = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

// Two connection types to test
const CONNECTIONS = {
    systemUser: {
        id: '019aedf2-507e-736a-9156-071c364b2db0',
        name: 'System User Connection',
        expectedWhatsApp: true // Should now show WhatsApp accounts
    },
    regularUser: {
        id: '019aee2d-480e-70f7-b5da-9c01f14288b9',
        name: 'Regular User Connection',
        expectedWhatsApp: true
    }
};

async function login(page) {
    await page.goto(`${BASE_URL}/login`);
    await page.waitForLoadState('networkidle');

    await page.fill('#email', 'admin@cmis.test');
    await page.fill('#password', 'password');

    // Use more specific selector - the main login submit button
    await page.click('form button[type="submit"]:visible');

    await page.waitForURL(/.*dashboard.*|.*orgs.*/, { timeout: 15000 });
    console.log('✅ Logged in successfully');
}

async function testConnection(page, connection) {
    const url = `${BASE_URL}/orgs/${ORG_ID}/settings/platform-connections/meta/${connection.id}/assets`;
    console.log(`\n📋 Testing: ${connection.name}`);
    console.log(`   URL: ${url}`);

    await page.goto(url);
    await page.waitForLoadState('networkidle');

    // Wait for assets to load (they load progressively)
    await page.waitForTimeout(5000);

    const results = {
        name: connection.name,
        businesses: { found: false, count: 0 },
        whatsapp: { found: false, count: 0 },
        catalogs: { found: false, count: 0 },
        pages: { found: false, count: 0 },
        adAccounts: { found: false, count: 0 },
        errors: []
    };

    // Check for Business Managers section
    const businessSection = await page.$('text=Business Managers');
    if (businessSection) {
        results.businesses.found = true;
        // Try to get count from badge
        const businessBadge = await page.$('section:has-text("Business Managers") .badge, section:has-text("Business Managers") [x-text*="businesses.length"]');
        if (businessBadge) {
            const badgeText = await businessBadge.textContent();
            results.businesses.count = parseInt(badgeText) || 0;
        }
        // Count items in list
        const businessItems = await page.$$('section:has-text("Business Managers") [x-for*="business"]');
        if (businessItems.length > 0) {
            results.businesses.count = businessItems.length;
        }
    }

    // Check for WhatsApp section and count
    const whatsappSection = await page.$('text=WhatsApp Business Accounts');
    if (whatsappSection) {
        results.whatsapp.found = true;
        // Look for count in nearby badge or text
        const whatsappItems = await page.$$('[x-for*="whatsapp"], [x-for*="Whatsapp"]');
        results.whatsapp.count = whatsappItems.length;
    }

    // Check for Product Catalogs section
    const catalogSection = await page.$('text=Product Catalogs');
    if (catalogSection) {
        results.catalogs.found = true;
        const catalogItems = await page.$$('[x-for*="catalog"]');
        results.catalogs.count = catalogItems.length;
    }

    // Check for errors in console
    page.on('console', msg => {
        if (msg.type() === 'error') {
            results.errors.push(msg.text());
        }
    });

    // Take screenshot
    const screenshotPath = `test-results/meta-assets-${connection.id.substring(0, 8)}.png`;
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log(`   📸 Screenshot saved: ${screenshotPath}`);

    // Get page content for debugging
    const pageContent = await page.content();

    // Check for specific text patterns
    if (pageContent.includes('Business Managers')) {
        results.businesses.found = true;
    }
    if (pageContent.includes('WhatsApp Business Accounts')) {
        results.whatsapp.found = true;
    }

    // Look for actual loaded data
    const businessCountMatch = pageContent.match(/(\d+)\s*business\s*manager/i);
    if (businessCountMatch) {
        results.businesses.count = parseInt(businessCountMatch[1]);
    }

    const whatsappCountMatch = pageContent.match(/(\d+)\s*whatsapp/i);
    if (whatsappCountMatch) {
        results.whatsapp.count = parseInt(whatsappCountMatch[1]);
    }

    // Print results
    console.log(`   Business Managers: ${results.businesses.found ? '✅' : '❌'} (${results.businesses.count} found)`);
    console.log(`   WhatsApp Accounts: ${results.whatsapp.found ? '✅' : '❌'} (${results.whatsapp.count} found)`);
    console.log(`   Product Catalogs:  ${results.catalogs.found ? '✅' : '❌'} (${results.catalogs.count} found)`);

    if (results.errors.length > 0) {
        console.log(`   ⚠️  Console Errors: ${results.errors.length}`);
        results.errors.forEach(err => console.log(`      - ${err}`));
    }

    return results;
}

async function main() {
    console.log('🧪 Testing Meta Assets Implementation');
    console.log('=====================================');
    console.log('Testing: MAX_BUSINESSES=50, Business Managers section');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Capture console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        await login(page);

        // Test System User connection
        const systemUserResults = await testConnection(page, CONNECTIONS.systemUser);

        // Test Regular User connection
        const regularUserResults = await testConnection(page, CONNECTIONS.regularUser);

        // Summary
        console.log('\n📊 SUMMARY');
        console.log('==========');
        console.log(`System User:  Businesses=${systemUserResults.businesses.count}, WhatsApp=${systemUserResults.whatsapp.count}`);
        console.log(`Regular User: Businesses=${regularUserResults.businesses.count}, WhatsApp=${regularUserResults.whatsapp.count}`);

        if (consoleErrors.length > 0) {
            console.log(`\n⚠️  Total Console Errors: ${consoleErrors.length}`);
        } else {
            console.log('\n✅ No console errors detected');
        }

    } catch (error) {
        console.error('❌ Test failed:', error.message);
    } finally {
        await browser.close();
    }
}

main();
