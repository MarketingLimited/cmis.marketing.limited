const { chromium } = require('playwright');

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    // Login
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.waitForLoadState('networkidle');
    await page.fill('#email', 'admin@cmis.test');
    await page.fill('#password', 'password');
    await page.click('form button[type="submit"]:visible');
    await page.waitForURL(/.*dashboard.*|.*orgs.*/, { timeout: 15000 });
    console.log('Logged in');

    // Go to System User connection
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platform-connections/meta/019aedf2-507e-736a-9156-071c364b2db0/assets');
    await page.waitForLoadState('networkidle');

    // Wait for assets to load
    console.log('Waiting for assets to load...');
    await page.waitForTimeout(12000);

    // Scroll to WhatsApp section
    const whatsappSection = await page.$('text=WhatsApp Business Accounts');
    if (whatsappSection) {
        await whatsappSection.scrollIntoViewIfNeeded();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/meta-assets-whatsapp-section.png' });
        console.log('WhatsApp section screenshot saved');
    } else {
        console.log('WhatsApp section not found yet, scrolling down...');
        await page.evaluate(() => window.scrollTo(0, 3000));
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/meta-assets-whatsapp-section.png' });
    }

    // Scroll to Catalogs section
    const catalogsSection = await page.$('text=Product Catalogs');
    if (catalogsSection) {
        await catalogsSection.scrollIntoViewIfNeeded();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/meta-assets-catalogs-section.png' });
        console.log('Catalogs section screenshot saved');
    } else {
        console.log('Catalogs section not found, scrolling more...');
        await page.evaluate(() => window.scrollTo(0, 5000));
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/meta-assets-catalogs-section.png' });
    }

    // Get all section counts
    const counts = await page.evaluate(() => {
        const sections = {};
        const text = document.body.innerText;

        // Extract all counts
        const patterns = [
            { name: 'businesses', regex: /(\d+)\s*business manager/i },
            { name: 'pages', regex: /page\(s\) available (\d+)/i },
            { name: 'instagram', regex: /account\(s\) available (\d+)/i },
            { name: 'adAccounts', regex: /(\d+)\s*ad account/i },
            { name: 'whatsapp', regex: /(\d+)\s*WhatsApp/i },
            { name: 'catalogs', regex: /(\d+)\s*catalog/i },
            { name: 'pixels', regex: /(\d+)\s*pixel/i },
        ];

        patterns.forEach(p => {
            const match = text.match(p.regex);
            if (match) sections[p.name] = parseInt(match[1]);
        });

        return sections;
    });

    console.log('\nAll Asset Counts:');
    console.log(JSON.stringify(counts, null, 2));

    // Check loading states
    const loadingStates = await page.evaluate(() => {
        const loading = [];
        document.querySelectorAll('[x-show*="loading"]').forEach(el => {
            if (el.offsetParent !== null) {
                loading.push(el.textContent.trim().substring(0, 50));
            }
        });
        return loading;
    });

    if (loadingStates.length > 0) {
        console.log('\nSections still loading:', loadingStates);
    }

    // Check for errors
    const errors = await page.evaluate(() => {
        const errors = [];
        document.querySelectorAll('[class*="error"], [class*="red"]').forEach(el => {
            const text = el.textContent.trim();
            if (text.includes('Failed') || text.includes('Error')) {
                errors.push(text.substring(0, 100));
            }
        });
        return errors;
    });

    if (errors.length > 0) {
        console.log('\nErrors found:', errors);
    }

    await browser.close();
}

main().catch(console.error);
