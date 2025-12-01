const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    const page = await browser.newPage();

    // Set cookie for locale
    await page.setCookie({
        name: 'app_locale',
        value: 'en',
        domain: 'cmis-test.kazaaz.com',
        path: '/'
    });

    // Login
    console.log('Logging in...');
    await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle0', timeout: 30000 });
    await page.type('input[name="email"]', 'admin@cmis.test');
    await page.type('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 30000 });

    // Navigate to social page
    console.log('Navigating to social page...');
    await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social', { waitUntil: 'networkidle0', timeout: 30000 });

    // Wait for page to load
    await new Promise(resolve => setTimeout(resolve, 3000));

    // Take screenshot
    await page.screenshot({ path: '/tmp/edit-post-test-1.png', fullPage: true });
    console.log('Screenshot 1 saved');

    // Check for JavaScript errors
    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
    });

    // Look for edit buttons using evaluate
    const editButtonCount = await page.evaluate(() => {
        const buttons = document.querySelectorAll('button');
        let count = 0;
        buttons.forEach(btn => {
            if (btn.getAttribute('@click') && btn.getAttribute('@click').includes('editPost')) {
                count++;
            }
        });
        return count;
    });

    console.log('Found ' + editButtonCount + ' edit buttons via attribute check');

    // Try to find edit button by looking at all buttons with fa-edit icon
    const editIconButtons = await page.evaluate(() => {
        const icons = document.querySelectorAll('i.fa-edit');
        return icons.length;
    });
    console.log('Found ' + editIconButtons + ' edit icons');

    // Click on an edit icon button if found
    if (editIconButtons > 0) {
        await page.evaluate(() => {
            const icons = document.querySelectorAll('i.fa-edit');
            if (icons[0] && icons[0].parentElement) {
                icons[0].parentElement.click();
            }
        });

        await new Promise(resolve => setTimeout(resolve, 2000));

        // Take screenshot of the edit modal
        await page.screenshot({ path: '/tmp/edit-post-modal-phase1.png', fullPage: true });
        console.log('Edit modal screenshot saved');

        // Check if modal is visible and has our new progress bar
        const modalCheck = await page.evaluate(() => {
            // Check for modal visibility
            const modal = document.querySelector('[x-show="showEditPostModal"]');
            const progressBar = document.querySelector('.h-1\\.5.bg-gray-200');
            const charText = document.body.innerHTML.includes('characters') || document.body.innerHTML.includes('remaining');

            return {
                modalFound: !!modal,
                progressBarFound: !!progressBar,
                characterTextFound: charText
            };
        });

        console.log('Modal check:', modalCheck);
    }

    console.log('JS Errors:', errors.length > 0 ? errors : 'None');

    await browser.close();
    console.log('Test completed');
})().catch(e => console.error('Error:', e.message));
