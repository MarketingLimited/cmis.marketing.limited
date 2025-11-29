/**
 * Responsive Testing Tool
 *
 * Captures screenshots at different viewport sizes (mobile, tablet, desktop)
 *
 * Usage:
 *   node responsive-test.js <url> [outputPrefix]
 *
 * Examples:
 *   node responsive-test.js https://cmis-test.kazaaz.com/
 *   node responsive-test.js https://cmis-test.kazaaz.com/ dashboard
 */

const { chromium } = require('playwright');

const viewports = {
    mobile: {
        width: 375,
        height: 667,
        name: 'mobile',
        description: 'iPhone SE / iPhone 8'
    },
    tablet: {
        width: 768,
        height: 1024,
        name: 'tablet',
        description: 'iPad Mini / iPad'
    },
    desktop: {
        width: 1920,
        height: 1080,
        name: 'desktop',
        description: 'Full HD Desktop'
    },
    widescreen: {
        width: 2560,
        height: 1440,
        name: 'widescreen',
        description: '2K Monitor'
    }
};

async function responsiveTest(url, outputPrefix = 'responsive') {
    console.log(`\n📱 Responsive Testing Tool`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);
    console.log(`📍 URL: ${url}`);
    console.log(`📸 Output prefix: ${outputPrefix}`);
    console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

    try {
        console.log(`🚀 Launching browser...`);
        const browser = await chromium.launch();

        const results = [];

        for (const [key, config] of Object.entries(viewports)) {
            console.log(`\n📐 Testing ${config.name} (${config.description})`);
            console.log(`   Size: ${config.width}x${config.height}`);

            const page = await browser.newPage();
            await page.setViewportSize({
                width: config.width,
                height: config.height
            });

            console.log(`   🔗 Loading page...`);
            await page.goto(url, { waitUntil: 'networkidle' });

            const filename = `${outputPrefix}-${config.name}.png`;
            console.log(`   📸 Capturing screenshot: ${filename}`);

            await page.screenshot({
                path: filename,
                fullPage: true
            });

            results.push({
                device: config.name,
                description: config.description,
                viewport: `${config.width}x${config.height}`,
                filename
            });

            console.log(`   ✅ ${config.name}: ${filename}`);

            await page.close();
        }

        await browser.close();

        console.log(`\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);
        console.log(`✅ Responsive testing complete!`);
        console.log(`\n📊 Screenshots captured:`);
        results.forEach(r => {
            console.log(`   📱 ${r.device.padEnd(12)} ${r.viewport.padEnd(12)} → ${r.filename}`);
        });
        console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`);

        return results;
    } catch (error) {
        console.error(`\n❌ Error: ${error.message}\n`);
        throw error;
    }
}

// CLI usage
if (require.main === module) {
    const url = process.argv[2] || 'https://cmis-test.kazaaz.com/';
    const outputPrefix = process.argv[3] || 'responsive';

    responsiveTest(url, outputPrefix)
        .then(() => process.exit(0))
        .catch(err => {
            console.error(err);
            process.exit(1);
        });
}

module.exports = { responsiveTest, viewports };
