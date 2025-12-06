const { chromium } = require('playwright');

const BASE_URL = 'https://cmis-test.kazaaz.com';

const pages = [
    { url: '/', name: 'Homepage' },
    { url: '/features', name: 'Features' },
    { url: '/pricing', name: 'Pricing' },
    { url: '/about', name: 'About' },
    { url: '/faq', name: 'FAQ' },
    { url: '/blog', name: 'Blog' },
    { url: '/case-studies', name: 'Case Studies' },
    { url: '/contact', name: 'Contact' },
    { url: '/demo', name: 'Demo' },
];

async function testBilingual() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });

    const page = await context.newPage();

    let passed = 0;
    let failed = 0;

    console.log('\n=== Bilingual Marketing Website Test ===\n');

    // Test English
    console.log('--- Testing English (LTR) ---\n');
    await context.addCookies([{
        name: 'app_locale',
        value: 'en',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    for (const { url, name } of pages) {
        try {
            const response = await page.goto(`${BASE_URL}${url}`, {
                waitUntil: 'domcontentloaded',
                timeout: 30000
            });

            const status = response?.status() || 0;
            const dir = await page.getAttribute('html', 'dir');
            const lang = await page.getAttribute('html', 'lang');

            // Check for hero section
            const hasHero = await page.$('section.relative.bg-gradient-to-br');

            // Check for badge
            const hasBadge = await page.$('span.inline-block.px-4');

            if (status === 200) {
                console.log(`  [EN] ${name}: ${status} (dir=${dir || 'ltr'}) ${hasHero ? 'Hero OK' : ''} ${hasBadge ? 'Badge OK' : ''}`);
                passed++;
            } else {
                console.log(`  [EN] ${name}: ${status}`);
                failed++;
            }
        } catch (error) {
            console.log(`  [EN] ${name}: Error - ${error.message.substring(0, 40)}`);
            failed++;
        }
    }

    // Test Arabic
    console.log('\n--- Testing Arabic (RTL) ---\n');
    await context.clearCookies();
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    for (const { url, name } of pages) {
        try {
            const response = await page.goto(`${BASE_URL}${url}`, {
                waitUntil: 'domcontentloaded',
                timeout: 30000
            });

            const status = response?.status() || 0;
            const dir = await page.getAttribute('html', 'dir');

            // Check for hero section
            const hasHero = await page.$('section.relative.bg-gradient-to-br');

            // Check for badge
            const hasBadge = await page.$('span.inline-block.px-4');

            if (status === 200) {
                console.log(`  [AR] ${name}: ${status} (dir=${dir || 'ltr'}) ${hasHero ? 'Hero OK' : ''} ${hasBadge ? 'Badge OK' : ''}`);
                passed++;
            } else {
                console.log(`  [AR] ${name}: ${status}`);
                failed++;
            }
        } catch (error) {
            console.log(`  [AR] ${name}: Error - ${error.message.substring(0, 40)}`);
            failed++;
        }
    }

    // Take screenshots of key pages in both languages
    console.log('\n=== Taking Screenshots ===\n');

    // English screenshots
    await context.clearCookies();
    await context.addCookies([{
        name: 'app_locale',
        value: 'en',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    await page.goto(`${BASE_URL}/`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.screenshot({ path: 'test-results/marketing-home-en.png', fullPage: false });
    console.log('  Screenshot: marketing-home-en.png');

    await page.goto(`${BASE_URL}/features`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.screenshot({ path: 'test-results/marketing-features-en.png', fullPage: false });
    console.log('  Screenshot: marketing-features-en.png');

    // Arabic screenshots
    await context.clearCookies();
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    }]);

    await page.goto(`${BASE_URL}/`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.screenshot({ path: 'test-results/marketing-home-ar.png', fullPage: false });
    console.log('  Screenshot: marketing-home-ar.png');

    await page.goto(`${BASE_URL}/features`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.screenshot({ path: 'test-results/marketing-features-ar.png', fullPage: false });
    console.log('  Screenshot: marketing-features-ar.png');

    await browser.close();

    console.log('\n=== Summary ===');
    console.log(`Passed: ${passed}/${pages.length * 2}`);
    console.log(`Failed: ${failed}/${pages.length * 2}`);

    return { passed, failed };
}

// Ensure test-results directory exists
const fs = require('fs');
if (!fs.existsSync('test-results')) {
    fs.mkdirSync('test-results', { recursive: true });
}

testBilingual()
    .then(({ passed, failed }) => {
        process.exit(failed > 0 ? 1 : 0);
    })
    .catch(console.error);
