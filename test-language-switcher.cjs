/**
 * Language Switcher Testing Script
 * Tests the actual language switching functionality by clicking the component
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    screenshotDir: './test-results/language-tests',
    timeout: 30000
};

// Ensure screenshot directory exists
if (!fs.existsSync(CONFIG.screenshotDir)) {
    fs.mkdirSync(CONFIG.screenshotDir, { recursive: true });
}

async function testLanguageSwitcher() {
    console.log('🌐 Starting Language Switcher Test...\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // Enable console logging from the page
    page.on('console', msg => {
        if (msg.text().includes('[LANGUAGE SWITCHER]')) {
            console.log('🖥️  Browser Console:', msg.text());
        }
    });

    try {
        console.log('📍 Step 1: Load login page (should be in Arabic by default)');
        await page.goto(`${CONFIG.baseUrl}/login`, {
            waitUntil: 'networkidle2',
            timeout: CONFIG.timeout
        });

        // Wait for Alpine.js to initialize
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Take screenshot of Arabic version
        const arabicScreenshot = path.join(CONFIG.screenshotDir, '01-login-arabic-default.png');
        await page.screenshot({ path: arabicScreenshot, fullPage: true });
        console.log(`✅ Screenshot saved: ${arabicScreenshot}\n`);

        // Check current locale
        const arabicLocale = await page.evaluate(() => {
            return document.documentElement.lang || 'unknown';
        });
        console.log(`📝 Current locale attribute: ${arabicLocale}`);

        // Check for Arabic text
        const hasArabicText = await page.evaluate(() => {
            const bodyText = document.body.innerText;
            return bodyText.includes('تسجيل الدخول') || bodyText.includes('العربية') || bodyText.includes('الذكي');
        });
        console.log(`🔍 Has Arabic text: ${hasArabicText ? 'YES ✅' : 'NO ❌'}\n`);

        console.log('📍 Step 2: Look for language switcher component');

        // Check if language switcher exists
        const hasSwitcher = await page.evaluate(() => {
            // Look for the button with language icon
            const buttons = Array.from(document.querySelectorAll('button'));
            return buttons.some(btn =>
                btn.textContent.includes('العربية') ||
                btn.textContent.includes('English') ||
                btn.querySelector('svg path[d*="M3 5h12M9 3v2"]') // Language icon SVG path
            );
        });

        if (!hasSwitcher) {
            console.log('⚠️  WARNING: Language switcher not found on page!\n');
        } else {
            console.log('✅ Language switcher found\n');

            console.log('📍 Step 3: Click language switcher to open dropdown');

            // Find and click the language switcher button
            await page.evaluate(() => {
                const buttons = Array.from(document.querySelectorAll('button'));
                const switcherButton = buttons.find(btn =>
                    btn.textContent.includes('العربية') ||
                    btn.textContent.includes('English')
                );
                if (switcherButton) {
                    console.log('[TEST] Clicking language switcher button');
                    switcherButton.click();
                }
            });

            // Wait for dropdown to open
            await new Promise(resolve => setTimeout(resolve, 500));

            // Take screenshot of opened dropdown
            const dropdownScreenshot = path.join(CONFIG.screenshotDir, '02-dropdown-opened.png');
            await page.screenshot({ path: dropdownScreenshot, fullPage: true });
            console.log(`✅ Screenshot saved: ${dropdownScreenshot}\n`);

            console.log('📍 Step 4: Click English option');

            // Find and click English option
            await page.evaluate(() => {
                const buttons = Array.from(document.querySelectorAll('button[type="submit"]'));
                const englishButton = buttons.find(btn =>
                    btn.textContent.includes('English') ||
                    btn.querySelector('span')?.textContent === '🇬🇧'
                );
                if (englishButton) {
                    console.log('[TEST] Clicking English button');
                    englishButton.click();
                } else {
                    console.log('[TEST] English button not found!');
                }
            });

            // Wait for navigation/reload
            console.log('⏳ Waiting for page to reload...');
            await new Promise(resolve => setTimeout(resolve, 3000));

            // Take screenshot of English version
            const englishScreenshot = path.join(CONFIG.screenshotDir, '03-login-english-switched.png');
            await page.screenshot({ path: englishScreenshot, fullPage: true });
            console.log(`✅ Screenshot saved: ${englishScreenshot}\n`);

            // Check if language actually changed
            const englishLocale = await page.evaluate(() => {
                return document.documentElement.lang || 'unknown';
            });
            console.log(`📝 Locale after switch: ${englishLocale}`);

            // Check for English text
            const hasEnglishText = await page.evaluate(() => {
                const bodyText = document.body.innerText;
                return bodyText.includes('Sign in') ||
                       bodyText.includes('Email') ||
                       bodyText.includes('Password') ||
                       (bodyText.includes('Login') && !bodyText.includes('تسجيل'));
            });
            console.log(`🔍 Has English text: ${hasEnglishText ? 'YES ✅' : 'NO ❌'}\n`);

            // Check cookies
            const cookies = await page.cookies();
            const localeCookie = cookies.find(c => c.name === 'app_locale');
            console.log(`🍪 app_locale cookie: ${localeCookie ? localeCookie.value : 'NOT SET'}\n`);

            // Summary
            console.log('📊 TEST RESULTS:');
            console.log('================');
            console.log(`Arabic (default): ${hasArabicText ? 'WORKING ✅' : 'FAILED ❌'}`);
            console.log(`English (switched): ${hasEnglishText ? 'WORKING ✅' : 'FAILED ❌'}`);
            console.log(`Cookie persistence: ${localeCookie ? 'WORKING ✅' : 'FAILED ❌'}`);
            console.log(`Locale attribute change: ${arabicLocale !== englishLocale ? 'WORKING ✅' : 'FAILED ❌'}`);
        }

    } catch (error) {
        console.error('❌ Error during test:', error.message);
    } finally {
        await browser.close();
        console.log('\n✅ Test complete!');
    }
}

// Run the test
testLanguageSwitcher();
