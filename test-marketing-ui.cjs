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
    { url: '/terms', name: 'Terms' },
    { url: '/privacy', name: 'Privacy' },
    { url: '/cookies', name: 'Cookies' },
];

async function testPages() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });

    const page = await context.newPage();

    const results = [];
    let passed = 0;
    let failed = 0;

    console.log('\n=== Marketing Website UI Test ===\n');

    for (const { url, name } of pages) {
        try {
            const response = await page.goto(`${BASE_URL}${url}`, {
                waitUntil: 'domcontentloaded',
                timeout: 30000
            });

            const status = response?.status() || 0;

            if (status === 200) {
                // Check for hero section with animated background
                const hasHero = await page.$('section.relative.bg-gradient-to-br');
                const hasAnimatedOrbs = await page.$('.animate-pulse');

                console.log(`✅ ${name}: ${status} ${hasHero ? '(Hero OK)' : ''} ${hasAnimatedOrbs ? '(Animations OK)' : ''}`);
                passed++;
                results.push({ name, status: 'PASS', code: status });
            } else {
                console.log(`❌ ${name}: ${status}`);
                failed++;
                results.push({ name, status: 'FAIL', code: status });
            }
        } catch (error) {
            console.log(`❌ ${name}: Error - ${error.message.substring(0, 50)}`);
            failed++;
            results.push({ name, status: 'ERROR', error: error.message });
        }
    }

    // Take screenshots of key pages
    console.log('\n=== Taking Screenshots ===\n');

    const screenshotPages = ['/', '/features', '/pricing'];
    for (const url of screenshotPages) {
        try {
            await page.goto(`${BASE_URL}${url}`, { waitUntil: 'networkidle', timeout: 30000 });
            await page.screenshot({
                path: `test-results/marketing-${url === '/' ? 'home' : url.replace('/', '')}.png`,
                fullPage: false
            });
            console.log(`📸 Screenshot saved for ${url}`);
        } catch (e) {
            console.log(`⚠️  Could not take screenshot for ${url}`);
        }
    }

    await browser.close();

    console.log('\n=== Summary ===');
    console.log(`Passed: ${passed}/${pages.length}`);
    console.log(`Failed: ${failed}/${pages.length}`);

    return { passed, failed, results };
}

// Ensure test-results directory exists
const fs = require('fs');
if (!fs.existsSync('test-results')) {
    fs.mkdirSync('test-results', { recursive: true });
}

testPages()
    .then(({ passed, failed }) => {
        process.exit(failed > 0 ? 1 : 0);
    })
    .catch(console.error);
