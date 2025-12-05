const { chromium } = require('playwright');
async function main() {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'domcontentloaded' });
    await page.screenshot({ path: 'test-results/login-page.png', fullPage: true });
    
    // Get all button elements
    const buttons = await page.evaluate(() => {
        const btns = document.querySelectorAll('button[type="submit"]');
        return Array.from(btns).map((b, i) => ({
            index: i,
            classes: b.className,
            visible: b.offsetParent !== null,
            text: b.textContent.trim().substring(0, 50)
        }));
    });
    console.log('Submit buttons found:', JSON.stringify(buttons, null, 2));
    
    // Get form info
    const forms = await page.evaluate(() => {
        const formEls = document.querySelectorAll('form');
        return Array.from(formEls).map(f => ({
            action: f.action,
            method: f.method,
            hasSubmit: f.querySelector('button[type="submit"]') !== null
        }));
    });
    console.log('Forms found:', JSON.stringify(forms, null, 2));
    
    await browser.close();
}
main().catch(console.error);
