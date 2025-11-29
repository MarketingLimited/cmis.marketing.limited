/**
 * Playwright Screenshot Tool
 *
 * Takes screenshots using Playwright with support for multiple browsers
 *
 * Usage:
 *   node playwright-screenshot.js <url> [output] [browser]
 *
 * Examples:
 *   node playwright-screenshot.js https://cmis-test.kazaaz.com/
 *   node playwright-screenshot.js https://cmis-test.kazaaz.com/ dashboard.png
 *   node playwright-screenshot.js https://cmis-test.kazaaz.com/ test.png firefox
 *   node playwright-screenshot.js https://cmis-test.kazaaz.com/ test.png webkit
 */

const { chromium, firefox, webkit } = require('playwright');

async function screenshot(url, output = 'screenshot.png', options = {}) {
    const {
        browser: browserType = 'chromium',
        viewport = { width: 1920, height: 1080 },
        fullPage = true,
        waitUntil = 'networkidle'
    } = options;

    console.log(`\n🌐 Playwright Screenshot Tool`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);
    console.log(`📍 URL: ${url}`);
    console.log(`🖥️  Browser: ${browserType}`);
    console.log(`📐 Viewport: ${viewport.width}x${viewport.height}`);
    console.log(`📄 Full Page: ${fullPage ? 'Yes' : 'No'}`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

    const browsers = { chromium, firefox, webkit };

    try {
        console.log(`🚀 Launching ${browserType}...`);
        const browser = await browsers[browserType].launch();
        const page = await browser.newPage();

        console.log(`⚙️  Setting viewport size...`);
        await page.setViewportSize(viewport);

        console.log(`🔗 Navigating to ${url}...`);
        const response = await page.goto(url, { waitUntil });

        console.log(`✅ Page loaded - Status: ${response.status()}`);
        console.log(`📸 Capturing screenshot...`);

        await page.screenshot({ path: output, fullPage });

        await browser.close();

        console.log(`\n✅ Screenshot saved: ${output}`);
        console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

        return { success: true, output, url, status: response.status() };
    } catch (error) {
        console.error(`\n❌ Error: ${error.message}\n`);
        return { success: false, error: error.message };
    }
}

// CLI usage
if (require.main === module) {
    const url = process.argv[2] || 'https://cmis-test.kazaaz.com/';
    const output = process.argv[3] || 'screenshot.png';
    const browserType = process.argv[4] || 'chromium';

    screenshot(url, output, { browser: browserType })
        .then(() => process.exit(0))
        .catch(err => {
            console.error(err);
            process.exit(1);
        });
}

module.exports = { screenshot };
