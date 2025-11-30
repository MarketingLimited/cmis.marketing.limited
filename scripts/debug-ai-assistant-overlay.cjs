const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        locale: 'ar',
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Capture console messages
    const consoleMessages = [];
    page.on('console', msg => {
        const text = msg.text();
        consoleMessages.push({ type: msg.type(), text });
        if (msg.type() === 'error' || text.includes('AI') || text.includes('showAIAssistant')) {
            console.log(`   [Browser ${msg.type()}]:`, text);
        }
    });

    // Set locale cookie
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        url: 'https://cmis-test.kazaaz.com'
    }]);

    console.log('1. Navigating to CMIS login page...');
    await page.goto('https://cmis-test.kazaaz.com/login');

    console.log('2. Logging in...');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');

    // Find the visible submit button in the login form
    const loginButton = await page.locator('form button[type="submit"]').filter({ hasText: /تسجيل الدخول|دخول|login|sign in/i }).first();
    await loginButton.click();
    await page.waitForLoadState('networkidle');

    console.log('3. Navigating to Social Publishing page...');
    // Use the test org ID from CLAUDE.md
    const testOrgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    await page.goto(`https://cmis-test.kazaaz.com/orgs/${testOrgId}/social`);
    await page.waitForTimeout(3000);

    console.log('   Current URL:', page.url());

    // Save page HTML for debugging
    const html = await page.content();
    require('fs').writeFileSync('test-results/page-content.html', html);
    console.log('   Page HTML saved to test-results/page-content.html');

    // Take a screenshot
    await page.screenshot({ path: 'test-results/social-publish-page.png', fullPage: true });

    // Try to find and click the publish/create post button
    const publishButton = await page.locator('button').filter({ hasText: /نشر|إنشاء|publish|create|جديد/i }).first();
    if (await publishButton.count() > 0 && await publishButton.isVisible()) {
        console.log('   Found publish button, clicking...');
        await publishButton.click();
        await page.waitForTimeout(1000);
    } else {
        console.log('   Publish button not found. Looking for modal trigger...');
        // Check if modal is already open or can be triggered
        const modalTriggers = await page.locator('[x-data*="publishModal"], [x-on\\:click*="publishModal"], button[onclick*="modal"]').all();
        console.log('   Found', modalTriggers.length, 'potential modal triggers');
        if (modalTriggers.length > 0) {
            await modalTriggers[0].click().catch(() => {});
            await page.waitForTimeout(1000);
        }
    }

    console.log('4. Looking for AI Assistant button...');
    await page.waitForTimeout(1000);

    // Find the AI Assistant button (magic wand icon)
    const aiButton = await page.locator('button[title*="AI"]').or(
        page.locator('button:has(i.fa-magic)')
    ).first();

    if (await aiButton.isVisible()) {
        console.log('5. AI Assistant button found. Clicking...');

        // Take screenshot before click
        await page.screenshot({ path: 'test-results/ai-assistant-before-click.png', fullPage: true });

        // Click the button
        await aiButton.click();

        // Wait longer for Alpine transitions to complete
        console.log('   Waiting for Alpine to process state change...');
        await page.waitForTimeout(2000);

        console.log('6. Inspecting AI Assistant overlay...');

        // Check how many AI Assistant overlays exist
        const overlayCount = await page.locator('[x-show="showAIAssistant"]').count();
        console.log(`   Found ${overlayCount} AI Assistant overlay(s) in the DOM`);

        if (overlayCount > 1) {
            console.log('   ⚠️  WARNING: Multiple AI Assistant overlays detected!');

            // Check each one
            for (let i = 0; i < overlayCount; i++) {
                const dims = await page.locator('[x-show="showAIAssistant"]').nth(i).evaluate((el) => ({
                    index: i,
                    offsetWidth: el.offsetWidth,
                    offsetHeight: el.offsetHeight,
                    computedDisplay: window.getComputedStyle(el).display,
                    isConnected: el.isConnected
                }));
                console.log(`      Overlay ${i}:`, dims);
            }
        }

        // Find the overlay element
        const overlay = await page.locator('[x-show="showAIAssistant"]').last(); // Use last() instead of first()

        if (await overlay.count() > 0) {
            console.log('   ✓ Overlay element exists in DOM');

            // Get computed styles and dimensions
            const computedStyles = await overlay.evaluate((el) => {
                const styles = window.getComputedStyle(el);
                const rect = el.getBoundingClientRect();
                return {
                    // Computed CSS
                    display: styles.display,
                    visibility: styles.visibility,
                    opacity: styles.opacity,
                    zIndex: styles.zIndex,
                    position: styles.position,
                    width: styles.width,
                    height: styles.height,
                    top: styles.top,
                    left: styles.left,
                    transform: styles.transform,
                    // Element dimensions
                    offsetWidth: el.offsetWidth,
                    offsetHeight: el.offsetHeight,
                    clientWidth: el.clientWidth,
                    clientHeight: el.clientHeight,
                    scrollWidth: el.scrollWidth,
                    scrollHeight: el.scrollHeight,
                    // Bounding rect
                    rectWidth: rect.width,
                    rectHeight: rect.height,
                    rectTop: rect.top,
                    rectLeft: rect.left,
                    // Attributes
                    hasXCloak: el.hasAttribute('x-cloak'),
                    hasXShow: el.hasAttribute('x-show'),
                    xShowValue: el.getAttribute('x-show'),
                    // Inline style
                    inlineDisplay: el.style.display,
                };
            });

            console.log('   Computed Styles & Dimensions:', JSON.stringify(computedStyles, null, 2));

            // Check Alpine data
            const alpineData = await page.evaluate(() => {
                const modalEl = document.querySelector('[x-data*="publishModal"]');
                if (modalEl && modalEl._x_dataStack) {
                    return modalEl._x_dataStack[0];
                }
                return null;
            });

            console.log('   Alpine Data (showAIAssistant):', alpineData?.showAIAssistant);

            // Get the actual HTML of the overlay element
            const overlayHTML = await overlay.evaluate((el) => {
                // Get ALL properties including data attributes and Alpine internals
                const allProps = {};
                for (const key in el) {
                    if (key.startsWith('_x') || key.startsWith('__x')) {
                        if (key === '_x_isShown') {
                            allProps[key] = el[key]; // Get actual value, not just type
                        } else {
                            allProps[key] = typeof el[key];
                        }
                    }
                }

                return {
                    outerHTML: el.outerHTML.substring(0, 500), // First 500 chars
                    style: el.getAttribute('style'),
                    inlineStyle: el.style.cssText,
                    computedDisplay: window.getComputedStyle(el).display,
                    allAttributes: Array.from(el.attributes).map(attr => ({
                        name: attr.name,
                        value: attr.value
                    })),
                    alpineProperties: allProps,
                    // Check if element is actually in DOM
                    isConnected: el.isConnected,
                    parentElement: el.parentElement ? el.parentElement.tagName : null
                };
            });

            console.log('   Overlay isConnected:', overlayHTML.isConnected);
            console.log('   Overlay parentElement:', overlayHTML.parentElement);
            console.log('   Overlay inline style (cssText):', overlayHTML.inlineStyle);
            console.log('   Overlay computedDisplay:', overlayHTML.computedDisplay);
            console.log('   Overlay Alpine _x_isShown:', overlayHTML.alpineProperties._x_isShown);

            // Check parent outerHTML
            const parentHTML = await overlay.evaluate((el) => {
                const parent = el.parentElement;
                if (parent) {
                    return {
                        tagName: parent.tagName,
                        className: parent.className,
                        outerHTML: parent.outerHTML.substring(0, 800)
                    };
                }
                return null;
            });
            console.log('   Parent element HTML:', parentHTML?.outerHTML);

            // Try manually calling Alpine's show function
            console.log('\n   Attempting to manually call Alpine _x_doShow()...');
            const manualShowResult = await overlay.evaluate((el) => {
                if (el._x_doShow && typeof el._x_doShow === 'function') {
                    try {
                        el._x_doShow();
                        return { success: true, message: 'Called _x_doShow()' };
                    } catch (e) {
                        return { success: false, error: e.message };
                    }
                }
                return { success: false, message: '_x_doShow not found' };
            });
            console.log('   Manual show result:', manualShowResult);

            // Check dimensions again after manual show
            await page.waitForTimeout(500);
            const dimsAfter = await overlay.evaluate((el) => ({
                offsetWidth: el.offsetWidth,
                offsetHeight: el.offsetHeight,
                computedDisplay: window.getComputedStyle(el).display
            }));
            console.log('   Dimensions after manual show:', dimsAfter);

            // Check child elements
            console.log('\n   Checking child elements...');
            const childInfo = await overlay.evaluate((el) => {
                const children = Array.from(el.children);
                return children.map((child, idx) => ({
                    index: idx,
                    tagName: child.tagName,
                    className: child.className.substring(0, 100),
                    offsetWidth: child.offsetWidth,
                    offsetHeight: child.offsetHeight,
                    computedDisplay: window.getComputedStyle(child).display,
                    hasXShow: child.hasAttribute('x-show'),
                    hasXIf: child.hasAttribute('x-if')
                }));
            });
            console.log('   Child elements:', JSON.stringify(childInfo, null, 2));

            // Check grandchildren (content of the modal box)
            console.log('\n   Checking grandchildren (modal content)...');
            const grandchildInfo = await overlay.evaluate((el) => {
                const modalBox = el.querySelector('.bg-white.rounded-2xl');
                if (!modalBox) return { error: 'Modal box not found' };

                const grandchildren = Array.from(modalBox.children);
                return {
                    totalGrandchildren: grandchildren.length,
                    grandchildren: grandchildren.map((gc, idx) => ({
                        index: idx,
                        tagName: gc.tagName,
                        className: gc.className.substring(0, 80),
                        offsetWidth: gc.offsetWidth,
                        offsetHeight: gc.offsetHeight,
                        computedDisplay: window.getComputedStyle(gc).display,
                        textContent: gc.textContent?.substring(0, 50)
                    }))
                };
            });
            console.log('   Grandchildren:', JSON.stringify(grandchildInfo, null, 2));

            // Check parent and ancestor dimensions
            console.log('\n   Checking parent and ancestors...');
            const ancestorInfo = await overlay.evaluate((el) => {
                const ancestors = [];
                let current = el.parentElement;
                let depth = 0;

                while (current && depth < 5) {
                    const styles = window.getComputedStyle(current);
                    ancestors.push({
                        depth,
                        tagName: current.tagName,
                        id: current.id,
                        className: current.className?.substring(0, 100),
                        offsetWidth: current.offsetWidth,
                        offsetHeight: current.offsetHeight,
                        computedDisplay: styles.display,
                        position: styles.position,
                        hasXData: current.hasAttribute('x-data')
                    });

                    current = current.parentElement;
                    depth++;
                }

                return ancestors;
            });
            console.log('   Ancestors:');
            ancestorInfo.forEach(a => {
                console.log(`      [${a.depth}] ${a.tagName} (${a.offsetWidth}x${a.offsetHeight}) display:${a.computedDisplay} ${a.hasXData ? '(has x-data)' : ''}`);
                if (a.className) console.log(`          class: ${a.className}`);
            });

            // Get all CSS rules applied to this element
            const cssRules = await overlay.evaluate((el) => {
                const rules = [];
                const sheets = Array.from(document.styleSheets);

                for (const sheet of sheets) {
                    try {
                        const cssRules = Array.from(sheet.cssRules || []);
                        for (const rule of cssRules) {
                            if (rule.selectorText && el.matches(rule.selectorText)) {
                                rules.push({
                                    selector: rule.selectorText,
                                    cssText: rule.style.cssText.substring(0, 200)
                                });
                            }
                        }
                    } catch (e) {
                        // Skip CORS sheets
                    }
                }

                return rules;
            });

            console.log('   Matched CSS Rules:');
            cssRules.slice(0, 10).forEach(rule => {
                console.log(`      ${rule.selector}: ${rule.cssText}`);
            });

            // Check for overlapping elements
            const boundingBox = await overlay.boundingBox();
            console.log('   Bounding Box:', boundingBox);

            // Check if visible
            const isVisible = await overlay.isVisible();
            console.log('   Is Visible (Playwright):', isVisible);

            // Get all elements at the center of where overlay should be
            if (boundingBox) {
                const centerX = boundingBox.x + boundingBox.width / 2;
                const centerY = boundingBox.y + boundingBox.height / 2;

                const elementAtPoint = await page.evaluate(({ x, y }) => {
                    const el = document.elementFromPoint(x, y);
                    if (el) {
                        return {
                            tagName: el.tagName,
                            className: el.className,
                            id: el.id,
                            innerHTML: el.innerHTML?.substring(0, 100)
                        };
                    }
                    return null;
                }, { x: centerX, y: centerY });

                console.log('   Element at overlay center point:', elementAtPoint);
            }

            // Check for x-cloak
            const hasCloak = await overlay.evaluate((el) => el.hasAttribute('x-cloak'));
            console.log('   Has x-cloak attribute:', hasCloak);

            // Check parent visibility
            const parentVisible = await overlay.evaluate((el) => {
                let parent = el.parentElement;
                while (parent) {
                    const styles = window.getComputedStyle(parent);
                    if (styles.display === 'none' || styles.visibility === 'hidden') {
                        return {
                            visible: false,
                            hiddenBy: parent.className || parent.tagName,
                            display: styles.display,
                            visibility: styles.visibility
                        };
                    }
                    parent = parent.parentElement;
                }
                return { visible: true };
            });

            console.log('   Parent visibility chain:', parentVisible);

        } else {
            console.log('   ✗ Overlay element NOT found in DOM');
        }

        // Take screenshot after click
        await page.screenshot({ path: 'test-results/ai-assistant-after-click.png', fullPage: true });

        // Get console logs
        page.on('console', msg => console.log('   Browser Console:', msg.text()));

        // Wait a bit to see if anything changes
        console.log('7. Waiting 3 seconds to observe any changes...');
        await page.waitForTimeout(3000);

        // Final screenshot
        await page.screenshot({ path: 'test-results/ai-assistant-final.png', fullPage: true });

    } else {
        console.log('   ✗ AI Assistant button NOT found');
        await page.screenshot({ path: 'test-results/ai-assistant-button-not-found.png', fullPage: true });
    }

    console.log('\n8. Screenshots saved to test-results/');
    console.log('   - ai-assistant-before-click.png');
    console.log('   - ai-assistant-after-click.png');
    console.log('   - ai-assistant-final.png');

    await page.waitForTimeout(2000);
    await browser.close();
})();
