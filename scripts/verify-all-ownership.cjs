const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        locale: 'en-US'
    });
    const page = await context.newPage();

    try {
        // Set English locale
        await context.addCookies([{
            name: 'app_locale',
            value: 'en',
            domain: 'cmis-test.kazaaz.com',
            path: '/'
        }]);

        // Login
        console.log('Logging in...');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('form[action*="login"] button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 30000 });
        console.log('Logged in successfully');

        // Navigate to Meta Assets page
        const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
        const connectionId = '019aee2d-480e-70f7-b5da-9c01f14288b9';
        const metaAssetsUrl = `${BASE_URL}/orgs/${orgId}/settings/platform-connections/meta/${connectionId}/assets`;

        console.log('Navigating to Meta Assets page...');
        await page.goto(metaAssetsUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        // Wait for initial load
        await page.waitForTimeout(8000);

        // Take initial screenshot
        await page.screenshot({ path: 'test-results/ownership-full-page.png', fullPage: true });
        console.log('Full page screenshot saved');

        // Analyze ownership labels by looking for specific text patterns
        console.log('\n=== ANALYZING OWNERSHIP LABELS BY TEXT CONTENT ===\n');

        const ownershipAnalysis = await page.evaluate(() => {
            const results = {
                ownedBy: [],
                managedBy: [],
                personal: []
            };

            // Get all elements and check for ownership text patterns
            const allElements = document.querySelectorAll('*');
            allElements.forEach(el => {
                const text = el.textContent?.trim() || '';

                // Check for "Owned by:" pattern
                if (text.startsWith('Owned by:') && el.children.length <= 2) {
                    results.ownedBy.push(text.substring(0, 50));
                }

                // Check for "Managed by:" pattern
                if (text.startsWith('Managed by:') && el.children.length <= 2) {
                    results.managedBy.push(text.substring(0, 50));
                }

                // Check for standalone "Personal" label
                if (text === 'Personal' && el.classList.contains('text-gray-400')) {
                    results.personal.push(el.closest('.bg-white')?.querySelector('.font-medium')?.textContent?.trim() || 'Unknown');
                }
            });

            return results;
        });

        console.log(`"Owned by:" labels found: ${ownershipAnalysis.ownedBy.length}`);
        console.log(`"Managed by:" labels found: ${ownershipAnalysis.managedBy.length}`);
        console.log(`"Personal" labels found: ${ownershipAnalysis.personal.length}`);

        // Show samples
        console.log('\n--- Sample "Owned by:" labels ---');
        ownershipAnalysis.ownedBy.slice(0, 5).forEach(label => console.log(`  ${label}`));

        console.log('\n--- Sample "Managed by:" labels ---');
        ownershipAnalysis.managedBy.slice(0, 5).forEach(label => console.log(`  ${label}`));

        console.log('\n--- Sample "Personal" assets ---');
        ownershipAnalysis.personal.slice(0, 5).forEach(name => console.log(`  ${name}`));

        // Count unique patterns
        const ownedBusinesses = [...new Set(ownershipAnalysis.ownedBy.map(l => l.replace('Owned by:', '').trim()))];
        const managedBusinesses = [...new Set(ownershipAnalysis.managedBy.map(l => l.replace('Managed by:', '').trim()))];

        console.log(`\n--- Unique business owners: ${ownedBusinesses.length} ---`);
        ownedBusinesses.slice(0, 10).forEach(b => console.log(`  ${b}`));

        console.log(`\n--- Unique managing businesses: ${managedBusinesses.length} ---`);
        managedBusinesses.slice(0, 10).forEach(b => console.log(`  ${b}`));

        // Final summary
        const totalLabeled = ownershipAnalysis.ownedBy.length + ownershipAnalysis.managedBy.length + ownershipAnalysis.personal.length;
        console.log('\n=== FINAL SUMMARY ===');
        console.log(`Total assets with ownership labels: ${totalLabeled}`);

        const businessOwned = ownershipAnalysis.ownedBy.length + ownershipAnalysis.managedBy.length;
        const personalAssets = ownershipAnalysis.personal.length;

        console.log(`Business-owned/managed: ${businessOwned} (${((businessOwned/totalLabeled)*100).toFixed(1)}%)`);
        console.log(`Personal: ${personalAssets} (${((personalAssets/totalLabeled)*100).toFixed(1)}%)`);

        if (businessOwned > personalAssets) {
            console.log('\n✅ SUCCESS: More assets are business-owned/managed than personal!');
        } else if (businessOwned > 0) {
            console.log('\n⚠️ PARTIAL: Some assets showing business ownership, but more are personal.');
        } else {
            console.log('\n❌ ISSUE: No business ownership labels found.');
        }

    } catch (error) {
        console.error('Error:', error.message);
        await page.screenshot({ path: 'test-results/ownership-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

main().catch(console.error);
