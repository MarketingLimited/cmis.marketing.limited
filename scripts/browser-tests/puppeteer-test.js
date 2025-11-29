/**
 * Puppeteer Test Tool
 *
 * Full page testing with Puppeteer including status codes and page title
 *
 * Usage:
 *   node puppeteer-test.js <url> [output]
 *
 * Examples:
 *   node puppeteer-test.js https://cmis-test.kazaaz.com/
 *   node puppeteer-test.js https://cmis-test.kazaaz.com/ test-result.png
 */

const puppeteer = require('puppeteer');

async function testPage(url, options = {}) {
    const {
        output = 'test-result.png',
        viewport = { width: 1920, height: 1080 }
    } = options;

    console.log(`\n🌐 Puppeteer Test Tool`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);
    console.log(`📍 URL: ${url}`);
    console.log(`📐 Viewport: ${viewport.width}x${viewport.height}`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

    try {
        console.log(`🚀 Launching Chromium...`);
        const browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });

        const page = await browser.newPage();

        console.log(`⚙️  Setting viewport size...`);
        await page.setViewport(viewport);

        console.log(`🔗 Navigating to ${url}...`);
        const response = await page.goto(url, { waitUntil: 'networkidle0' });

        const result = {
            url,
            status: response.status(),
            title: await page.title(),
            screenshot: output
        };

        console.log(`\n📊 Page Information:`);
        console.log(`   Status Code: ${result.status}`);
        console.log(`   Page Title: ${result.title}`);
        console.log(`   Screenshot: ${output}\n`);

        console.log(`📸 Capturing full-page screenshot...`);
        await page.screenshot({ path: output, fullPage: true });

        await browser.close();

        console.log(`\n✅ Test completed successfully!`);
        console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

        return result;
    } catch (error) {
        console.error(`\n❌ Error: ${error.message}\n`);
        throw error;
    }
}

// CLI usage
if (require.main === module) {
    const url = process.argv[2] || 'https://cmis-test.kazaaz.com/';
    const output = process.argv[3] || 'test-result.png';

    testPage(url, { output })
        .then(result => {
            console.log('Test result:', JSON.stringify(result, null, 2));
            process.exit(0);
        })
        .catch(err => {
            console.error('Test failed:', err.message);
            process.exit(1);
        });
}

module.exports = { testPage };
