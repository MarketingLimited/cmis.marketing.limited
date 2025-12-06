/**
 * Verify Vite-compiled assets are working in admin layout
 */

const puppeteer = require('puppeteer');

(async () => {
    console.log('Verifying Vite-compiled assets in admin layout...\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();

    // Collect console messages
    const consoleMessages = [];
    page.on('console', msg => {
        consoleMessages.push({ type: msg.type(), text: msg.text() });
    });

    try {
        // Login first
        await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle2' });

        // Fill login form
        await page.waitForSelector('input[name="email"]');
        await page.type('input[name="email"]', 'admin@cmis.test');
        await page.type('input[name="password"]', 'password');

        // Click submit button with proper handling
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }),
            page.evaluate(() => document.querySelector('button[type="submit"]').click())
        ]);

        // Check if we're on dashboard
        const url = page.url();
        console.log('Current URL:', url);

        // Check for Alpine.js and Chart.js availability
        const jsCheck = await page.evaluate(() => {
            return {
                alpineAvailable: typeof window.Alpine !== 'undefined',
                chartAvailable: typeof window.Chart !== 'undefined',
                stylesheetCount: document.styleSheets.length,
                bodyClasses: document.body.className,
                hasTailwindClasses: document.body.classList.contains('bg-gray-50') ||
                                   document.querySelector('.bg-gradient-to-b') !== null
            };
        });

        console.log('\n--- JS Framework Check ---');
        console.log('Alpine.js:', jsCheck.alpineAvailable ? '✓ Available' : '✗ Not loaded');
        console.log('Chart.js:', jsCheck.chartAvailable ? '✓ Available' : '✗ Not loaded');
        console.log('Stylesheets loaded:', jsCheck.stylesheetCount);
        console.log('Tailwind classes:', jsCheck.hasTailwindClasses ? '✓ Working' : '✗ Not detected');
        console.log('Body classes:', jsCheck.bodyClasses || '(none)');

        // Check for errors
        const errors = consoleMessages.filter(m => m.type === 'error');
        if (errors.length > 0) {
            console.log('\n--- Console Errors ---');
            errors.forEach(e => console.log('  - ' + e.text));
        } else {
            console.log('\n✓ No console errors');
        }

        // Summary
        const success = jsCheck.alpineAvailable && jsCheck.chartAvailable && jsCheck.stylesheetCount > 0;
        console.log('\n=== Verification Result ===');
        console.log(success
            ? '✓ SUCCESS - All Vite assets loaded correctly'
            : '✗ FAILED - Some assets not loaded'
        );

        process.exit(success ? 0 : 1);

    } catch (error) {
        console.error('Error during verification:', error.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
