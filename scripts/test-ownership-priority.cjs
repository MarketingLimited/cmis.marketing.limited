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

        // Navigate to Meta Assets page directly with correct URL pattern
        const orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
        const connectionId = '019aee2d-480e-70f7-b5da-9c01f14288b9';
        const metaAssetsUrl = `${BASE_URL}/orgs/${orgId}/settings/platform-connections/meta/${connectionId}/assets`;

        console.log('Navigating to Meta Assets page...');
        console.log('URL:', metaAssetsUrl);
        await page.goto(metaAssetsUrl, {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        // Wait for page to load
        await page.waitForTimeout(5000);

        // Take screenshot of initial state
        await page.screenshot({ path: 'test-results/ownership-priority-loading.png', fullPage: true });
        console.log('Screenshot saved: ownership-priority-loading.png');

        // Wait for assets to load (up to 120 seconds)
        console.log('Waiting for assets to load...');
        const maxWait = 120000;
        const startTime = Date.now();

        let assetsLoaded = false;
        while (Date.now() - startTime < maxWait) {
            // Check if Business Managers section has content
            const businessCount = await page.locator('[x-show*="businessManagers"] .grid > div').count();
            if (businessCount > 0) {
                assetsLoaded = true;
                console.log(`Assets loaded! Found ${businessCount} business managers`);
                break;
            }
            await page.waitForTimeout(3000);
        }

        // Take screenshot after loading
        await page.screenshot({ path: 'test-results/ownership-priority-loaded.png', fullPage: true });
        console.log('Screenshot saved: ownership-priority-loaded.png');

        // Scroll to Ad Accounts section
        console.log('Scrolling to Ad Accounts section...');
        await page.evaluate(() => {
            const adAccountsSection = document.querySelector('[x-show*="adAccounts"]');
            if (adAccountsSection) {
                adAccountsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        await page.waitForTimeout(2000);

        // Take screenshot of Ad Accounts section
        await page.screenshot({ path: 'test-results/ownership-priority-adaccounts.png', fullPage: true });
        console.log('Screenshot saved: ownership-priority-adaccounts.png');

        // Count ownership labels
        console.log('\n=== OWNERSHIP LABEL ANALYSIS ===\n');

        // Count "Owned by:" labels
        const ownedByCount = await page.evaluate(() => {
            return document.querySelectorAll('.text-indigo-600').length;
        });
        console.log(`"Owned by:" labels (indigo): ${ownedByCount}`);

        // Count "Managed by:" labels
        const managedByCount = await page.evaluate(() => {
            return document.querySelectorAll('.text-teal-600').length;
        });
        console.log(`"Managed by:" labels (teal): ${managedByCount}`);

        // Count "Personal" labels
        const personalCount = await page.evaluate(() => {
            return document.querySelectorAll('.text-gray-400').length;
        });
        console.log(`"Personal" labels (gray): ${personalCount}`);

        // Get actual text content of ownership labels
        console.log('\n=== SAMPLE OWNERSHIP LABELS ===\n');

        const ownershipSamples = await page.evaluate(() => {
            const samples = [];

            // Get "Owned by:" samples
            const ownedLabels = document.querySelectorAll('.text-indigo-600');
            ownedLabels.forEach((el, i) => {
                if (i < 3 && el.textContent.includes('Owned')) {
                    samples.push({ type: 'owned', text: el.textContent.trim() });
                }
            });

            // Get "Managed by:" samples
            const managedLabels = document.querySelectorAll('.text-teal-600');
            managedLabels.forEach((el, i) => {
                if (i < 3 && el.textContent.includes('Managed')) {
                    samples.push({ type: 'managed', text: el.textContent.trim() });
                }
            });

            // Get "Personal" samples
            const personalLabels = document.querySelectorAll('.text-gray-400');
            personalLabels.forEach((el, i) => {
                if (i < 3 && el.textContent.includes('Personal')) {
                    samples.push({ type: 'personal', text: el.textContent.trim() });
                }
            });

            return samples;
        });

        ownershipSamples.forEach(sample => {
            console.log(`[${sample.type.toUpperCase()}] ${sample.text}`);
        });

        // Check Ad Accounts specifically
        console.log('\n=== AD ACCOUNTS OWNERSHIP CHECK ===\n');

        const adAccountsOwnership = await page.evaluate(() => {
            const results = {
                owned: 0,
                managed: 0,
                personal: 0,
                samples: []
            };

            // Find the Ad Accounts section
            const adAccountsSection = document.querySelector('[x-show*="adAccounts"]');
            if (!adAccountsSection) return results;

            // Count ownership types in Ad Accounts section
            const ownedInAdAccounts = adAccountsSection.querySelectorAll('.text-indigo-600');
            const managedInAdAccounts = adAccountsSection.querySelectorAll('.text-teal-600');
            const personalInAdAccounts = adAccountsSection.querySelectorAll('.text-gray-400');

            results.owned = Array.from(ownedInAdAccounts).filter(el => el.textContent.includes('Owned')).length;
            results.managed = Array.from(managedInAdAccounts).filter(el => el.textContent.includes('Managed')).length;
            results.personal = Array.from(personalInAdAccounts).filter(el => el.textContent.includes('Personal')).length;

            // Get sample ad account names with their ownership
            const adAccountCards = adAccountsSection.querySelectorAll('.bg-white.border');
            adAccountCards.forEach((card, i) => {
                if (i < 5) {
                    const name = card.querySelector('.font-medium')?.textContent?.trim() || 'Unknown';
                    const ownershipLabel = card.querySelector('.text-indigo-600, .text-teal-600, .text-gray-400')?.textContent?.trim() || 'None';
                    results.samples.push({ name, ownership: ownershipLabel });
                }
            });

            return results;
        });

        console.log(`Ad Accounts - Owned by business: ${adAccountsOwnership.owned}`);
        console.log(`Ad Accounts - Managed (client): ${adAccountsOwnership.managed}`);
        console.log(`Ad Accounts - Personal: ${adAccountsOwnership.personal}`);

        console.log('\nSample Ad Accounts:');
        adAccountsOwnership.samples.forEach(sample => {
            console.log(`  - ${sample.name}: ${sample.ownership}`);
        });

        // Final summary
        console.log('\n=== SUMMARY ===\n');
        const totalOwnership = ownedByCount + managedByCount + personalCount;
        console.log(`Total ownership labels: ${totalOwnership}`);
        console.log(`  - Owned by (business): ${ownedByCount} (${((ownedByCount/totalOwnership)*100).toFixed(1)}%)`);
        console.log(`  - Managed by (client): ${managedByCount} (${((managedByCount/totalOwnership)*100).toFixed(1)}%)`);
        console.log(`  - Personal: ${personalCount} (${((personalCount/totalOwnership)*100).toFixed(1)}%)`);

        if (ownedByCount > personalCount) {
            console.log('\n SUCCESS: More assets are showing business ownership than personal!');
        } else {
            console.log('\n WARNING: More assets showing as Personal than business-owned. May need further investigation.');
        }

    } catch (error) {
        console.error('Error:', error.message);
        await page.screenshot({ path: 'test-results/ownership-priority-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

main().catch(console.error);
