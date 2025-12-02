const { chromium } = require('playwright');
const fs = require('fs');

async function testBoostComplete() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1400, height: 1000 }
    });
    const page = await context.newPage();

    const errors = [];
    const apiResponses = [];
    const issues = [];

    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
        }
    });

    page.on('response', async response => {
        const url = response.url();
        if (url.includes('/settings/profiles/') && (
            url.includes('search-') ||
            url.includes('meta-audiences') ||
            url.includes('validate-budget')
        )) {
            try {
                const body = await response.text();
                apiResponses.push({
                    url: url.split('/').pop(),
                    status: response.status(),
                    body: body.substring(0, 300)
                });
            } catch (e) {}
        }
    });

    try {
        // Login
        console.log('1. Logging in...');
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.press('input[name="password"]', 'Enter');
        await page.waitForURL('**/dashboard**', { timeout: 20000 });
        console.log('   Logged in!');

        // Navigate to profile
        console.log('2. Navigating to profile...');
        await page.goto('https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profiles/019ad524-9807-73d4-892e-e1ca9fc6cd84');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Click Add Boost button
        console.log('3. Opening boost modal...');
        const addBoostBtn = await page.$('button:has-text("إضافة تعزيز"), button:has-text("Add boost")');
        if (addBoostBtn) {
            await addBoostBtn.click();
            await page.waitForTimeout(1500);
        }
        await page.screenshot({ path: 'test-results/boost-01-modal-opened.png', fullPage: false });

        // Select Ad Account - Main Account | Marketing Dot Limited
        console.log('4. Selecting ad account (Main Account | Marketing Dot Limited)...');
        const adAccountSelect = await page.$('select[x-model="form.ad_account_id"]');
        if (adAccountSelect) {
            const options = await adAccountSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent }))
            );
            console.log('   Available ad accounts:', options.map(o => o.text).join(', '));

            // Find Main Account
            const mainAccount = options.find(o => o.text.includes('Marketing Dot Limited') || o.text.includes('3048183365459787'));
            if (mainAccount) {
                await adAccountSelect.selectOption(mainAccount.value);
                console.log('   Selected:', mainAccount.text);
            } else {
                issues.push('Main Account | Marketing Dot Limited not found in dropdown');
                // Select first real account
                if (options.length > 1) {
                    await adAccountSelect.selectOption({ index: 1 });
                }
            }
            await page.waitForTimeout(2000);
        }
        await page.screenshot({ path: 'test-results/boost-02-account-selected.png', fullPage: false });

        // Test Objective Selection
        console.log('5. Testing objective selection...');
        const objectiveSelect = await page.$('select[x-model="form.objective"]');
        if (objectiveSelect) {
            const objectives = await objectiveSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent, disabled: o.disabled }))
            );
            console.log('   Available objectives:', objectives.filter(o => !o.disabled).map(o => `${o.text} (${o.value})`).join(', '));

            // Select Lead Generation (OUTCOME_LEADS)
            const leadGenObj = objectives.find(o =>
                o.value.includes('LEAD') || o.text.toLowerCase().includes('lead')
            );
            if (leadGenObj) {
                await objectiveSelect.selectOption(leadGenObj.value);
                console.log('   Selected objective:', leadGenObj.text);
            } else {
                issues.push('Lead Generation objective not found');
            }
            await page.waitForTimeout(1000);
        } else {
            issues.push('Objective select not found');
        }
        await page.screenshot({ path: 'test-results/boost-03-objective-selected.png', fullPage: false });

        // Test Destination Selection (after objective)
        console.log('6. Testing destination selection...');
        const destinationSelect = await page.$('select[x-model="form.destination_type"]');
        if (destinationSelect) {
            const isVisible = await destinationSelect.isVisible();
            const isDisabled = await destinationSelect.isDisabled();
            console.log('   Destination select visible:', isVisible, 'disabled:', isDisabled);

            const destinations = await destinationSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent, disabled: o.disabled }))
            );
            console.log('   Available destinations:', destinations.map(o => `${o.text} (${o.value})${o.disabled ? ' [disabled]' : ''}`).join(', '));

            if (destinations.length <= 1) {
                issues.push('No destination options available after selecting objective');
            }

            // Try to select messaging apps
            const messagingDest = destinations.find(o =>
                o.value.includes('MESSAGING') || o.text.toLowerCase().includes('messaging') || o.text.toLowerCase().includes('رسائل')
            );
            if (messagingDest && !messagingDest.disabled) {
                await destinationSelect.selectOption(messagingDest.value);
                console.log('   Selected destination:', messagingDest.text);
            } else {
                issues.push('Messaging apps destination not available or disabled');
            }
            await page.waitForTimeout(1000);
        } else {
            issues.push('Destination select not found - may be hidden or not rendered');
        }
        await page.screenshot({ path: 'test-results/boost-04-destination-selected.png', fullPage: false });

        // Test WhatsApp Selection
        console.log('7. Testing WhatsApp number selection...');
        const whatsappSelect = await page.$('select[x-model="form.whatsapp_number"], select[x-model="form.whatsapp_id"]');
        if (whatsappSelect) {
            const isVisible = await whatsappSelect.isVisible();
            console.log('   WhatsApp select visible:', isVisible);

            const whatsappOptions = await whatsappSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent }))
            );
            console.log('   WhatsApp options:', whatsappOptions.map(o => o.text).join(', '));

            if (whatsappOptions.length > 1) {
                await whatsappSelect.selectOption({ index: 1 });
            } else {
                issues.push('No WhatsApp numbers available');
            }
        } else {
            console.log('   WhatsApp select not found (may require messaging destination)');
        }
        await page.screenshot({ path: 'test-results/boost-05-whatsapp.png', fullPage: false });

        // Set Budget $5
        console.log('8. Setting budget to $5...');
        const budgetInput = await page.$('input[x-model="form.budget_amount"]');
        if (budgetInput) {
            await budgetInput.fill('5');
            console.log('   Budget set to $5');
        }

        // Set Duration 5 days
        console.log('9. Setting duration to 5 days...');
        const durationInput = await page.$('input[x-model="form.duration_days"]');
        if (durationInput) {
            await durationInput.fill('5');
            console.log('   Duration set to 5 days');
        }

        // Set Boost Delay 4 hours
        console.log('10. Setting boost delay to 4 hours...');
        const delayInput = await page.$('input[x-model="form.delay_hours"], input[x-model="form.boost_delay"]');
        if (delayInput) {
            await delayInput.fill('4');
            console.log('   Delay set to 4 hours');
        } else {
            // Try the combined delay field
            const delayValueInput = await page.$('input[x-model="form.delay_value"]');
            const delayUnitSelect = await page.$('select[x-model="form.delay_unit"]');
            if (delayValueInput && delayUnitSelect) {
                await delayValueInput.fill('4');
                await delayUnitSelect.selectOption('hours');
                console.log('   Delay set to 4 hours');
            }
        }
        await page.screenshot({ path: 'test-results/boost-06-budget-duration.png', fullPage: false });

        // Expand Audience Targeting section
        console.log('11. Expanding Audience Targeting section...');
        const audienceBtn = await page.$('button:has-text("استهداف الجمهور"), button:has-text("Audience targeting")');
        if (audienceBtn) {
            await audienceBtn.click();
            await page.waitForTimeout(500);
        }
        await page.screenshot({ path: 'test-results/boost-07-audience-section.png', fullPage: false });

        // Test Custom Audiences
        console.log('12. Testing Custom Audiences...');
        const customAudienceSelect = await page.$('select[x-model="form.custom_audiences"]');
        if (customAudienceSelect) {
            const customOptions = await customAudienceSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent }))
            );
            console.log('   Custom audiences available:', customOptions.length);
            customOptions.forEach(o => console.log('     -', o.text));

            if (customOptions.length === 0) {
                issues.push('No custom audiences loaded from Meta API');
            }
        }

        // Test Lookalike Audiences
        console.log('13. Testing Lookalike Audiences...');
        const lookalikeSelect = await page.$('select[x-model="form.lookalike_audiences"]');
        if (lookalikeSelect) {
            const lookalikeOptions = await lookalikeSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent }))
            );
            console.log('   Lookalike audiences available:', lookalikeOptions.length);
            lookalikeOptions.forEach(o => console.log('     -', o.text));

            if (lookalikeOptions.length === 0) {
                issues.push('No lookalike audiences loaded from Meta API');
            }
        }

        // Test Excluded Audiences
        console.log('14. Testing Excluded Audiences...');
        const excludedSelect = await page.$('select[x-model="form.excluded_audiences"]');
        if (excludedSelect) {
            const excludedOptions = await excludedSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent }))
            );
            console.log('   Excluded audience options:', excludedOptions.length);
        }
        await page.screenshot({ path: 'test-results/boost-08-audiences.png', fullPage: false });

        // Expand Detailed Targeting section
        console.log('15. Expanding Detailed Targeting section...');
        const detailedBtn = await page.$('button:has-text("الاستهداف التفصيلي"), button:has-text("Detailed targeting")');
        if (detailedBtn) {
            await detailedBtn.click();
            await page.waitForTimeout(500);
        }

        // Test Location Search (Bahrain)
        console.log('16. Testing Location search (Bahrain)...');
        const locationInput = await page.$('input[x-model="locationSearch"]');
        if (locationInput) {
            await locationInput.click();
            await locationInput.fill('bahrain');
            await page.waitForTimeout(2000);

            const locationDropdown = await page.$('[x-show*="showLocationDropdown"]');
            if (locationDropdown) {
                const isVisible = await locationDropdown.isVisible();
                console.log('   Location dropdown visible:', isVisible);

                if (isVisible) {
                    // Try to click the first result
                    const firstResult = await page.$('[x-show*="showLocationDropdown"] button:first-child');
                    if (firstResult) {
                        await firstResult.click();
                        console.log('   Selected Bahrain location');
                    }
                }
            }
        } else {
            issues.push('Location search input not found');
        }
        await page.screenshot({ path: 'test-results/boost-09-location.png', fullPage: false });

        // Test Interest Search (marketing)
        console.log('17. Testing Interest search (marketing)...');
        const interestInput = await page.$('input[x-model="interestSearch"]');
        if (interestInput) {
            await interestInput.click();
            await interestInput.fill('marketing');
            await page.waitForTimeout(2000);

            const interestDropdown = await page.$('[x-show*="showInterestDropdown"]');
            if (interestDropdown) {
                const isVisible = await interestDropdown.isVisible();
                console.log('   Interest dropdown visible:', isVisible);

                // Count results
                const results = await page.$$('[x-show*="showInterestDropdown"] button');
                console.log('   Interest results count:', results.length);

                if (results.length > 0) {
                    // Click first result
                    await results[0].click();
                    await page.waitForTimeout(500);
                    console.log('   Selected marketing interest');
                }
            }
        }
        await page.screenshot({ path: 'test-results/boost-10-interests.png', fullPage: false });

        // Test Gender Selection (Male only)
        console.log('18. Testing Gender selection (Male only)...');
        const genderSelect = await page.$('select[x-model="form.genders"]');
        if (genderSelect) {
            const genderOptions = await genderSelect.$$eval('option', opts =>
                opts.map(o => ({ value: o.value, text: o.textContent }))
            );
            console.log('   Gender options:', genderOptions.map(o => o.text).join(', '));

            // Find male option
            const maleOption = genderOptions.find(o =>
                o.value === '1' || o.text.toLowerCase().includes('male') || o.text.includes('ذكر')
            );
            if (maleOption) {
                await genderSelect.selectOption(maleOption.value);
                console.log('   Selected:', maleOption.text);
            }
        } else {
            issues.push('Gender select not found');
        }

        // Test Age Range (25-45)
        console.log('19. Testing Age range (25-45)...');
        const minAgeInput = await page.$('input[x-model="form.age_min"]');
        const maxAgeInput = await page.$('input[x-model="form.age_max"]');
        if (minAgeInput && maxAgeInput) {
            await minAgeInput.fill('25');
            await maxAgeInput.fill('45');
            console.log('   Age range set to 25-45');
        } else {
            issues.push('Age inputs not found');
        }
        await page.screenshot({ path: 'test-results/boost-11-demographics.png', fullPage: false });

        // Final screenshot of complete form
        await page.screenshot({ path: 'test-results/boost-12-complete-form.png', fullPage: true });

        // Summary
        console.log('\n========== TEST SUMMARY ==========');
        console.log('Issues found:', issues.length);
        issues.forEach((issue, i) => console.log(`  ${i + 1}. ${issue}`));

        console.log('\nAPI Responses:', apiResponses.length);
        apiResponses.forEach(r => console.log(`  - ${r.status} ${r.url}`));

        console.log('\nConsole Errors:', errors.filter(e => !e.includes('404')).length);
        errors.filter(e => !e.includes('404')).forEach(e => console.log(`  - ${e.substring(0, 100)}`));

        console.log('\n===================================');
        console.log('Screenshots saved to test-results/boost-*.png');

        // Save detailed report
        fs.writeFileSync('test-results/boost-test-report.json', JSON.stringify({
            issues,
            apiResponses,
            consoleErrors: errors,
            timestamp: new Date().toISOString()
        }, null, 2));

    } catch (error) {
        console.error('Test error:', error.message);
        await page.screenshot({ path: 'test-results/boost-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

testBoostComplete().catch(console.error);
