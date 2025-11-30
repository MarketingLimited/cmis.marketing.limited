# CMIS Troubleshooting Methodology & Tools

**Last Updated:** 2025-11-30
**Status:** Active Reference Guide
**Purpose:** Comprehensive guide for diagnosing and fixing issues in CMIS

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Available Tools](#available-tools)
3. [Frontend/UI Troubleshooting](#frontendui-troubleshooting)
4. [Backend/Laravel Troubleshooting](#backendlaravel-troubleshooting)
5. [Database Troubleshooting](#database-troubleshooting)
6. [Case Study: AI Assistant Overlay Fix](#case-study-ai-assistant-overlay-fix)
7. [Post-Implementation Verification & Testing](#post-implementation-verification--testing)
8. [Automatic Git Commits](#automatic-git-commits-mandatory)
9. [Best Practices](#best-practices)

---

## 🎯 Overview

When troubleshooting issues in CMIS, you have access to a **complete diagnostic toolkit** including:
- Browser automation (Playwright)
- Server-side debugging
- Database inspection
- Log analysis
- Network monitoring
- DOM/CSS inspection

**Golden Rule:** **ALWAYS use automated tools to inspect the actual running application** rather than guessing from code inspection alone.

---

## 🛠️ Available Tools

### Browser Automation & Testing

#### **Playwright (Primary Tool)**
```bash
# Installed at: /home/cmis-test/public_html/node_modules/.bin/playwright
# Version: Latest (already installed)

# Basic usage
node scripts/your-test.cjs

# Headless browser (no X server needed)
const browser = await chromium.launch({ headless: true });

# Take screenshots
await page.screenshot({ path: 'test-results/screenshot.png', fullPage: true });

# Get computed styles
const styles = await element.evaluate((el) => {
    return window.getComputedStyle(el).display;
});

# Execute JavaScript in browser context
const result = await page.evaluate(() => {
    return document.querySelector('.selector').offsetWidth;
});
```

#### **Capabilities:**
- ✅ Execute JavaScript in browser context
- ✅ Inspect DOM structure and hierarchy
- ✅ Get computed CSS styles
- ✅ Capture console logs (errors, warnings, info)
- ✅ Monitor network requests
- ✅ Take screenshots (full page or specific elements)
- ✅ Simulate user interactions (click, type, scroll)
- ✅ Access browser developer tools programmatically
- ✅ Test across multiple viewports/devices
- ✅ Handle authentication and cookies

#### **Example: Comprehensive DOM Inspection**
```javascript
const diagnostics = await page.evaluate(() => {
    const el = document.querySelector('.problematic-element');
    const styles = window.getComputedStyle(el);

    return {
        // Computed styles
        display: styles.display,
        visibility: styles.visibility,
        opacity: styles.opacity,
        position: styles.position,
        zIndex: styles.zIndex,

        // Dimensions
        offsetWidth: el.offsetWidth,
        offsetHeight: el.offsetHeight,
        clientWidth: el.clientWidth,
        clientHeight: el.clientHeight,

        // Attributes
        attributes: Array.from(el.attributes).map(attr => ({
            name: attr.name,
            value: attr.value
        })),

        // Parent chain
        parentTag: el.parentElement?.tagName,

        // Alpine.js data (if applicable)
        alpineData: el._x_dataStack?.[0],

        // Inline styles
        inlineStyle: el.style.cssText
    };
});

console.log('Diagnostics:', JSON.stringify(diagnostics, null, 2));
```

### Server-Side Tools

#### **Laravel Artisan**
```bash
# Clear caches (ALWAYS do this after view changes)
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# Check routes
php artisan route:list

# Run migrations
php artisan migrate

# Interactive shell
php artisan tinker
```

#### **Laravel Logs**
```bash
# Tail live logs
tail -f storage/logs/laravel.log

# Search for errors
grep -i "error\|exception" storage/logs/laravel.log

# Check specific date
cat storage/logs/laravel-2025-11-30.log
```

#### **Add Debug Code**

**Backend (Laravel):**
```php
// Log to Laravel log
\Log::debug('Variable value:', ['var' => $variable]);
\Log::info('Checkpoint reached');
\Log::error('Error occurred', ['context' => $data]);

// Dump and die
dd($variable);

// Dump without dying
dump($variable);

// Ray debugging (if installed)
ray($variable)->label('Debug Point');
```

**Frontend (Blade/Alpine.js):**
```html
<!-- Console logging -->
<div x-data="{ value: 'test' }"
     x-init="console.log('[DEBUG] Component initialized:', $data)">
    <button @click="console.log('[CLICK] Button clicked, value:', value)">
        Click Me
    </button>
</div>

<!-- Temporary debug display -->
<div class="fixed top-0 right-0 bg-red-500 text-white p-4 z-[9999]">
    <pre x-text="JSON.stringify($data, null, 2)"></pre>
</div>
```

### Database Tools

#### **PostgreSQL CLI**
```bash
# Connect to database (use .env values)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)"

# Check RLS policies
SELECT * FROM pg_policies WHERE schemaname = 'cmis';

# Check table structure
\d cmis.campaigns

# Test RLS context
SET app.current_org_id = 'org-uuid-here';
SELECT * FROM cmis.campaigns;
```

#### **Laravel Tinker**
```php
php artisan tinker

>>> DB::connection()->getDatabaseName()
>>> User::find(1)
>>> Campaign::with('org')->first()
```

### Network & HTTP Tools

#### **cURL**
```bash
# Test API endpoint
curl -X GET "https://cmis-test.kazaaz.com/api/endpoint" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"

# Test with verbose output
curl -v "https://cmis-test.kazaaz.com/api/endpoint"

# Save response to file
curl "https://cmis-test.kazaaz.com/api/endpoint" > response.json
```

#### **Network Monitoring (Playwright)**
```javascript
// Capture all network requests
page.on('request', request => {
    console.log('Request:', request.method(), request.url());
});

page.on('response', response => {
    console.log('Response:', response.status(), response.url());
});

// Wait for specific API call
await page.waitForResponse(response =>
    response.url().includes('/api/campaigns') && response.status() === 200
);
```

### File System Tools

#### **grep (Search in Files)**
```bash
# Find all files containing text
grep -r "searchTerm" resources/views/

# Find with line numbers
grep -rn "x-show" resources/views/

# Find in specific file types
grep -r --include="*.blade.php" "Alpine" resources/

# Count occurrences
grep -r "x-data" resources/views/ | wc -l
```

#### **Git**
```bash
# Check recent changes
git log --oneline -10
git diff

# Find when a line was added
git log -S "showAIAssistant" --source --all

# Check file history
git log -p resources/views/components/publish-modal.blade.php
```

### HTML/CSS Validation

#### **Check Div Balance**
```bash
# Count opening vs closing divs
opening=$(grep -o '<div' file.blade.php | wc -l)
closing=$(grep -o '</div>' file.blade.php | wc -l)
echo "Difference: $((opening - closing))"

# Find unclosed divs
grep -n "^[[:space:]]*<div" file.blade.php
grep -n "^[[:space:]]*</div>" file.blade.php
```

---

## 🎨 Frontend/UI Troubleshooting

### Diagnostic Workflow

1. **Reproduce the Issue**
   ```javascript
   // Use Playwright to automate reproduction
   await page.goto('https://cmis-test.kazaaz.com/...');
   await page.click('button.trigger');
   await page.waitForTimeout(1000);
   ```

2. **Take Screenshots**
   ```javascript
   await page.screenshot({
       path: 'test-results/before.png',
       fullPage: true
   });
   ```

3. **Inspect DOM Structure**
   ```javascript
   const domPath = await page.evaluate(() => {
       const el = document.querySelector('.target');
       const path = [];
       let current = el;

       while (current) {
           path.push({
               tag: current.tagName,
               id: current.id,
               classes: current.className,
               hasXData: current.hasAttribute('x-data')
           });
           current = current.parentElement;
       }

       return path;
   });

   console.log('DOM Path:', JSON.stringify(domPath, null, 2));
   ```

4. **Check Computed Styles**
   ```javascript
   const styles = await page.evaluate(() => {
       const el = document.querySelector('.target');
       const computed = window.getComputedStyle(el);

       return {
           display: computed.display,
           visibility: computed.visibility,
           opacity: computed.opacity,
           width: computed.width,
           height: computed.height,
           position: computed.position,
           zIndex: computed.zIndex,
           // Element dimensions
           offsetWidth: el.offsetWidth,
           offsetHeight: el.offsetHeight
       };
   });
   ```

5. **Capture Console Logs**
   ```javascript
   const errors = [];
   page.on('console', msg => {
       if (msg.type() === 'error') {
           errors.push(msg.text());
       }
   });

   page.on('pageerror', error => {
       console.log('Page Error:', error.message);
   });
   ```

6. **Check Alpine.js State**
   ```javascript
   const alpineData = await page.evaluate(() => {
       const component = document.querySelector('[x-data*="publishModal"]');
       return component?._x_dataStack?.[0];
   });

   console.log('Alpine Data:', alpineData);
   ```

### Common UI Issues & Solutions

#### **Issue: Element Has Zero Dimensions**

**Diagnosis:**
```javascript
const diagnosis = await page.evaluate(() => {
    const el = document.querySelector('.element');

    // Check element dimensions
    const dims = {
        offsetWidth: el.offsetWidth,
        offsetHeight: el.offsetHeight,
        computedDisplay: window.getComputedStyle(el).display
    };

    // Check parent dimensions
    const parent = el.parentElement;
    const parentDims = {
        offsetWidth: parent?.offsetWidth,
        offsetHeight: parent?.offsetHeight,
        computedDisplay: window.getComputedStyle(parent).display
    };

    // Check if element is in correct scope
    let current = el;
    const hasXDataParent = [];
    while (current) {
        if (current.hasAttribute('x-data')) {
            hasXDataParent.push({
                tag: current.tagName,
                xData: current.getAttribute('x-data')
            });
        }
        current = current.parentElement;
    }

    return { dims, parentDims, hasXDataParent };
});
```

**Common Causes:**
1. Parent element has `display: none`
2. Element is outside Alpine.js scope
3. Unclosed/extra div tags in templates
4. CSS `!important` rules blocking Alpine directives

#### **Issue: Alpine.js Directive Not Working**

**Diagnosis:**
```javascript
// Check if element is in correct Alpine scope
const scopeInfo = await page.evaluate(() => {
    const el = document.querySelector('[x-show="variable"]');

    // Find parent with x-data
    let parent = el;
    while (parent && !parent.hasAttribute('x-data')) {
        parent = parent.parentElement;
    }

    return {
        hasParentScope: !!parent,
        parentXData: parent?.getAttribute('x-data'),
        elementXShow: el.getAttribute('x-show'),
        alpineInitialized: !!el._x_dataStack,
        dataStackValue: el._x_dataStack?.[0]
    };
});
```

#### **Issue: Element Not Visible Despite Correct Styles**

**Diagnosis:**
```javascript
const visibilityCheck = await page.evaluate(() => {
    const el = document.querySelector('.element');

    // Check z-index stacking
    const zIndex = window.getComputedStyle(el).zIndex;

    // Check for overlapping elements
    const rect = el.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    const topElement = document.elementFromPoint(centerX, centerY);

    return {
        zIndex,
        boundingRect: rect,
        elementAtCenter: {
            tag: topElement?.tagName,
            classes: topElement?.className
        },
        isSameElement: topElement === el
    };
});
```

---

## ⚙️ Backend/Laravel Troubleshooting

### Laravel Log Analysis

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log | grep -i "error\|exception\|warning"

# Find specific errors
grep -A 10 "SQLSTATE" storage/logs/laravel.log

# Find authentication issues
grep -i "auth\|login\|token" storage/logs/laravel.log
```

### Add Strategic Debug Points

```php
// Controller
public function store(Request $request)
{
    \Log::info('[STORE] Request data:', $request->all());

    $validated = $request->validated();
    \Log::info('[STORE] Validated data:', $validated);

    $campaign = Campaign::create($validated);
    \Log::info('[STORE] Created campaign:', ['id' => $campaign->id]);

    return $this->success($campaign);
}

// Service
public function processCampaign($data)
{
    \Log::debug('[SERVICE] Processing campaign', ['data' => $data]);

    try {
        $result = $this->externalApi->call();
        \Log::debug('[SERVICE] API response', ['result' => $result]);
    } catch (\Exception $e) {
        \Log::error('[SERVICE] API failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}
```

### Performance Profiling

```php
// Add timing
$start = microtime(true);

// ... operation ...

$duration = microtime(true) - $start;
\Log::info('[PERF] Operation took ' . $duration . 's');

// Query debugging
DB::enableQueryLog();

// ... queries ...

$queries = DB::getQueryLog();
\Log::debug('[QUERIES]', $queries);
```

---

## 🗄️ Database Troubleshooting

### Check RLS Context

```sql
-- Show current org context
SELECT current_setting('app.current_org_id', true);

-- Test RLS with specific org
SET app.current_org_id = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
SELECT * FROM cmis.campaigns;

-- Should only return campaigns for that org
```

### Verify RLS Policies

```sql
-- List all RLS policies
SELECT
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual
FROM pg_policies
WHERE schemaname = 'cmis'
ORDER BY tablename, policyname;

-- Check if RLS is enabled on table
SELECT
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables
WHERE schemaname = 'cmis';
```

### Migration Issues

```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback --step=1

# Fresh migration (CAUTION: destroys data)
php artisan migrate:fresh

# Specific migration
php artisan migrate --path=/database/migrations/2025_11_30_create_table.php
```

---

## 📚 Case Study: AI Assistant Overlay Fix

**Problem:** Clicking AI Assistant button does nothing - overlay doesn't display.

### Step 1: Initial Investigation (Manual Code Review)

```bash
# Check if overlay file exists
ls -lh resources/views/components/publish-modal/overlays/ai-assistant.blade.php

# Check button click handler
grep -n "showAIAssistant" resources/views/components/publish-modal/
```

**Finding:** File exists, button has `@click="showAIAssistant = true"`

### Step 2: Browser Automation (Playwright)

```javascript
// Create diagnostic script
const page = await context.newPage();

// Navigate and click button
await page.goto('https://cmis-test.kazaaz.com/orgs/{org}/social');
await page.click('button:has(i.fa-magic)');

// Inspect overlay
const overlayInfo = await page.evaluate(() => {
    const overlay = document.querySelector('[x-show="showAIAssistant"]');
    return {
        exists: !!overlay,
        offsetWidth: overlay?.offsetWidth,
        offsetHeight: overlay?.offsetHeight,
        computedDisplay: window.getComputedStyle(overlay).display,
        alpineData: overlay?._x_isShown
    };
});
```

**Finding:** Overlay exists but has zero dimensions, Alpine `_x_isShown: true` but element not visible.

### Step 3: DOM Structure Inspection

```javascript
// Trace parent hierarchy
const domPath = await page.evaluate(() => {
    const overlay = document.querySelector('[x-show="showAIAssistant"]');
    const path = [];
    let current = overlay;

    while (current && path.length < 10) {
        path.push({
            tag: current.tagName,
            id: current.id,
            className: current.className?.substring(0, 60),
            hasXData: current.hasAttribute('x-data'),
            xDataValue: current.getAttribute('x-data'),
            display: window.getComputedStyle(current).display,
            dimensions: `${current.offsetWidth}x${current.offsetHeight}`
        });
        current = current.parentElement;
    }

    return path;
});
```

**Finding:** Overlay parent has same classes as overlay itself AND `display: none` - suggests nested structure.

### Step 4: Check Parent Element

```javascript
const parentHTML = await page.evaluate(() => {
    const overlay = document.querySelector('[x-show="showAIAssistant"]');
    const parent = overlay.parentElement;

    return {
        tag: parent.tagName,
        xShow: parent.getAttribute('x-show'),
        outerHTML: parent.outerHTML.substring(0, 300)
    };
});
```

**Finding:** Parent is `<div x-show="showBestTimes" ... style="display: none;">` - overlay is INSIDE another overlay!

### Step 5: Find Root Cause

```bash
# Check div balance in overlay files
for file in hashtag-manager mention-picker calendar best-times media-source-picker media-library; do
    opening=$(grep -o '<div' "resources/views/components/publish-modal/overlays/$file.blade.php" | wc -l)
    closing=$(grep -o '</div>' "resources/views/components/publish-modal/overlays/$file.blade.php" | wc -l)
    echo "$file: Opening=$opening, Closing=$closing, Diff=$((opening - closing))"
done
```

**Finding:**
- `best-times.blade.php`: 11 opening, 9 closing (missing 2)
- `hashtag-manager.blade.php`: 20 opening, 21 closing (1 extra)
- `media-library.blade.php`: 14 opening, 15 closing (1 extra)

### Step 6: Fix & Verify

```bash
# Fix best-times.blade.php (add 2 closing divs)
# Fix hashtag-manager.blade.php (remove 1 closing div)
# Fix media-library.blade.php (remove 1 closing div)

# Clear caches
php artisan view:clear && php artisan cache:clear

# Verify fix
node scripts/test-ai-overlay-simple.cjs
```

**Result:**
```
DOM Path from AI Assistant to root:
  [0] DIV (AI Assistant overlay)
  [1] DIV (modal panel)
  [2] DIV (x-data="publishModal()") ← CORRECT SCOPE!
  [3] BODY
  [4] HTML
```

### Step 7: Final Verification

```javascript
// Test actual display
await page.click('button:has(i.fa-magic)');
const isVisible = await page.locator('[x-show="showAIAssistant"]').isVisible();
const dimensions = await page.evaluate(() => {
    const el = document.querySelector('[x-show="showAIAssistant"]');
    return {
        width: el.offsetWidth,
        height: el.offsetHeight
    };
});

console.log('Visible:', isVisible); // true
console.log('Dimensions:', dimensions); // { width: 1920, height: 1080 }
```

**Result:** ✅ Overlay displays correctly with full viewport dimensions!

---

## ✅ Best Practices

### 1. **Always Use Automated Inspection First**

❌ **Don't:**
```javascript
// Guess from code
"The overlay should work because x-show is set correctly"
```

✅ **Do:**
```javascript
// Verify in browser
const actual = await page.evaluate(() => {
    const el = document.querySelector('[x-show="showAIAssistant"]');
    return {
        display: window.getComputedStyle(el).display,
        dimensions: { w: el.offsetWidth, h: el.offsetHeight },
        alpineState: el._x_isShown
    };
});
```

### 2. **Clear Caches After Every View Change**

```bash
# ALWAYS run after modifying .blade.php files
php artisan view:clear && php artisan cache:clear
```

### 3. **Use Screenshots Liberally**

```javascript
// Before action
await page.screenshot({ path: 'before.png' });

// Perform action
await page.click('button');

// After action
await page.screenshot({ path: 'after.png' });
```

### 4. **Check Multiple Levels**

When debugging, inspect:
1. ✅ Element itself (styles, dimensions, attributes)
2. ✅ Parent elements (scope, visibility)
3. ✅ Child elements (content, dimensions)
4. ✅ Alpine.js state (data, directives)
5. ✅ Console logs (errors, warnings)
6. ✅ Network requests (API calls, assets)

### 5. **Document Your Findings**

```javascript
// Add clear logging
console.log('=== AI ASSISTANT DIAGNOSTIC ===');
console.log('Overlay exists:', !!overlay);
console.log('Computed display:', computedDisplay);
console.log('Dimensions:', { width, height });
console.log('Alpine state:', alpineData);
console.log('Parent element:', parentInfo);
console.log('==============================');
```

### 6. **Test After Fixes**

```javascript
// Don't assume - verify!
const finalCheck = await page.evaluate(() => {
    const el = document.querySelector('[x-show="showAIAssistant"]');
    return el.offsetWidth > 0 && el.offsetHeight > 0;
});

if (finalCheck) {
    console.log('✅ FIX VERIFIED');
} else {
    console.log('❌ ISSUE PERSISTS');
}
```

---

## ✅ Post-Implementation Verification & Testing

**CRITICAL:** After updating, upgrading, enhancing any feature, or creating new code, you MUST verify your changes work correctly. **DO NOT assume success** - always verify with automated tools.

### 🎯 Verification Checklist

After making ANY code changes, verify in this order:

#### 1. **Browser Console Logs** ✅

```javascript
// Use Playwright to capture console messages
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Capture ALL console messages
    const consoleMessages = [];
    page.on('console', msg => {
        consoleMessages.push({
            type: msg.type(),
            text: msg.text(),
            location: msg.location()
        });
        console.log(`[${msg.type().toUpperCase()}] ${msg.text()}`);
    });

    // Capture JavaScript errors
    const errors = [];
    page.on('pageerror', error => {
        errors.push(error.message);
        console.error('❌ PAGE ERROR:', error.message);
    });

    // Navigate and test
    await page.goto('https://cmis-test.kazaaz.com/your-page');
    await page.waitForLoadState('networkidle');

    // Test your feature
    await page.click('#your-button');
    await page.waitForTimeout(2000);

    // Report results
    console.log('\n=== CONSOLE VERIFICATION ===');
    console.log(`Total messages: ${consoleMessages.length}`);
    console.log(`Errors: ${errors.length}`);

    if (errors.length > 0) {
        console.log('❌ FAILED - JavaScript errors detected');
        errors.forEach(err => console.log('  -', err));
    } else {
        console.log('✅ PASSED - No JavaScript errors');
    }

    await browser.close();
})();
```

#### 2. **Laravel Logs** ✅

```bash
# Check Laravel logs after testing
tail -n 100 /home/cmis-test/public_html/storage/logs/laravel.log

# Look for errors (should return nothing if clean)
grep -i "error\|exception\|failed" storage/logs/laravel.log | tail -20

# Monitor logs in real-time while testing
tail -f storage/logs/laravel.log
```

**In your code, add verification logging:**

```php
// Before your change
\Log::info('[VERIFY] Before feature execution', [
    'user_id' => auth()->id(),
    'input' => $request->all()
]);

// After your change
\Log::info('[VERIFY] After feature execution', [
    'result' => $result,
    'status' => 'success'
]);
```

#### 3. **Screenshots** ✅

```javascript
// Take screenshots at each step to verify UI
await page.screenshot({
    path: 'test-results/verification/01-initial-state.png',
    fullPage: true
});

// Click your new button
await page.click('#new-feature-button');
await page.waitForTimeout(1000);

await page.screenshot({
    path: 'test-results/verification/02-after-click.png',
    fullPage: true
});

// Verify modal/overlay appears
const modalVisible = await page.isVisible('#your-modal');
console.log(modalVisible ? '✅ Modal visible' : '❌ Modal not visible');

await page.screenshot({
    path: 'test-results/verification/03-modal-open.png',
    fullPage: true
});
```

#### 4. **Functional Testing with Playwright** ✅

```javascript
// Complete verification test
const { chromium } = require('playwright');
const { expect } = require('@playwright/test');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    try {
        // Login
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        console.log('✅ Login successful');

        // Navigate to your feature
        await page.goto('https://cmis-test.kazaaz.com/your-feature');
        await page.waitForLoadState('networkidle');

        // TEST 1: Element exists
        const elementExists = await page.isVisible('#your-element');
        console.log(elementExists ? '✅ Element visible' : '❌ Element not found');

        // TEST 2: Click interaction works
        await page.click('#your-button');
        await page.waitForTimeout(1000);

        // TEST 3: Expected result appears
        const resultVisible = await page.isVisible('#expected-result');
        console.log(resultVisible ? '✅ Result displayed' : '❌ Result not displayed');

        // TEST 4: Data is correct
        const resultText = await page.textContent('#result-text');
        console.log(`Result: "${resultText}"`);
        console.log(resultText.includes('Expected') ? '✅ Correct data' : '❌ Wrong data');

        // TEST 5: No console errors (checked via page.on('console') above)

        console.log('\n✅ ALL VERIFICATION TESTS PASSED');

    } catch (error) {
        console.error('❌ VERIFICATION FAILED:', error.message);
        await page.screenshot({ path: 'test-results/verification-error.png' });
    } finally {
        await browser.close();
    }
})();
```

#### 5. **Network Requests Verification** ✅

```javascript
// Monitor network requests
const requests = [];
page.on('request', request => {
    requests.push({
        url: request.url(),
        method: request.method(),
        headers: request.headers()
    });
});

const responses = [];
page.on('response', response => {
    responses.push({
        url: response.url(),
        status: response.status(),
        ok: response.ok()
    });

    if (!response.ok()) {
        console.error(`❌ Failed request: ${response.url()} (${response.status()})`);
    }
});

// After your test
console.log(`\n=== NETWORK VERIFICATION ===`);
console.log(`Total requests: ${requests.length}`);
console.log(`Failed requests: ${responses.filter(r => !r.ok).length}`);
```

---

### 🧪 Creating Automated Test Files (MANDATORY)

**If everything works fine**, you MUST create permanent automated test files for future regression testing.

#### Laravel Feature Test Template

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $org;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->org = Organization::factory()->create();

        // Create test user
        $this->user = User::factory()->create([
            'org_id' => $this->org->id
        ]);

        // Set RLS context
        \DB::statement("SELECT init_transaction_context('{$this->org->id}')");
    }

    /** @test */
    public function it_can_perform_your_feature()
    {
        // Arrange
        $this->actingAs($this->user);
        $data = ['key' => 'value'];

        // Act
        $response = $this->postJson('/api/your-endpoint', $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Expected message'
        ]);

        // Verify database
        $this->assertDatabaseHas('cmis.your_table', [
            'org_id' => $this->org->id,
            'key' => 'value'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/your-endpoint', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key']);
    }

    /** @test */
    public function it_respects_multi_tenancy()
    {
        // Create another organization
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['org_id' => $otherOrg->id]);

        // Create data for this org
        $this->actingAs($this->user);
        $this->postJson('/api/your-endpoint', ['key' => 'value']);

        // Switch to other user - should NOT see first org's data
        $this->actingAs($otherUser);
        $response = $this->getJson('/api/your-endpoint');

        $response->assertStatus(200);
        $response->assertJsonMissing(['key' => 'value']);
    }
}
```

**Save as:** `tests/Feature/YourFeatureTest.php`

**Run test:**
```bash
vendor/bin/phpunit tests/Feature/YourFeatureTest.php
```

#### Browser Test Template (Playwright)

```javascript
// tests/browser/your-feature.spec.js
const { test, expect } = require('@playwright/test');

test.describe('Your Feature', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('https://cmis-test.kazaaz.com/login');
        await page.fill('input[name="email"]', 'admin@cmis.test');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
    });

    test('should display the feature correctly', async ({ page }) => {
        await page.goto('https://cmis-test.kazaaz.com/your-page');

        // Verify element exists
        await expect(page.locator('#your-element')).toBeVisible();

        // Verify text content
        const text = await page.textContent('#your-element');
        expect(text).toContain('Expected Text');
    });

    test('should handle user interaction', async ({ page }) => {
        await page.goto('https://cmis-test.kazaaz.com/your-page');

        // Click button
        await page.click('#your-button');

        // Wait for result
        await page.waitForSelector('#result', { state: 'visible' });

        // Verify result
        await expect(page.locator('#result')).toBeVisible();
        await expect(page.locator('#result')).toHaveText('Success');
    });

    test('should not have console errors', async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));

        await page.goto('https://cmis-test.kazaaz.com/your-page');
        await page.click('#your-button');
        await page.waitForTimeout(2000);

        expect(errors).toHaveLength(0);
    });

    test('should work in both languages', async ({ page, context }) => {
        // Test Arabic (RTL)
        await context.addCookies([{
            name: 'app_locale',
            value: 'ar',
            url: 'https://cmis-test.kazaaz.com'
        }]);
        await page.goto('https://cmis-test.kazaaz.com/your-page');
        await expect(page.locator('#your-element')).toBeVisible();

        // Test English (LTR)
        await context.addCookies([{
            name: 'app_locale',
            value: 'en',
            url: 'https://cmis-test.kazaaz.com'
        }]);
        await page.goto('https://cmis-test.kazaaz.com/your-page');
        await expect(page.locator('#your-element')).toBeVisible();
    });
});
```

**Save as:** `scripts/browser-tests/your-feature.spec.js`

**Run test:**
```bash
npx playwright test scripts/browser-tests/your-feature.spec.js
```

---

### 🔄 Updating Previous Related Tests

**CRITICAL:** Some updates change behavior in previous features, functions, or modules. You MUST update existing tests when:

#### When to Update Existing Tests:

1. **API Response Format Changed**
   - Updated: Controller return format
   - Impact: All tests checking that endpoint
   - Action: Update `assertJson()` expectations

2. **Validation Rules Changed**
   - Updated: Request validation rules
   - Impact: Tests checking validation
   - Action: Update `assertJsonValidationErrors()` assertions

3. **Database Schema Changed**
   - Updated: Migration added/removed columns
   - Impact: All tests using that model
   - Action: Update factory definitions and `assertDatabaseHas()` checks

4. **Feature Behavior Changed**
   - Updated: Business logic in service
   - Impact: Integration tests
   - Action: Update test scenarios and expectations

5. **UI Structure Changed**
   - Updated: Blade template structure
   - Impact: Browser tests using selectors
   - Action: Update selectors in Playwright tests

#### Example: Updating Tests After API Change

**Scenario:** You changed the campaign API response from:
```json
{ "data": {...} }
```
to:
```json
{ "success": true, "data": {...}, "message": "..." }
```

**Find affected tests:**
```bash
# Find all tests using this endpoint
grep -r "campaigns" tests/Feature/ | grep postJson

# Or search for specific patterns
grep -r "assertJson" tests/Feature/Campaign*
```

**Update the tests:**

```php
// OLD TEST (needs update)
public function test_create_campaign()
{
    $response = $this->postJson('/api/campaigns', $data);
    $response->assertJson(['data' => [...]]);  // ❌ Will fail with new format
}

// NEW TEST (updated)
public function test_create_campaign()
{
    $response = $this->postJson('/api/campaigns', $data);
    $response->assertJson([
        'success' => true,          // ✅ Added
        'message' => 'Campaign created successfully',  // ✅ Added
        'data' => [...]             // ✅ Kept
    ]);
}
```

#### Find and Update Related Tests Script

```bash
#!/bin/bash
# scripts/find-related-tests.sh

FEATURE_NAME="$1"

echo "=== Finding tests related to: $FEATURE_NAME ==="

# Search PHP tests
echo -e "\n📝 Feature Tests:"
grep -r "$FEATURE_NAME" tests/Feature/ --include="*.php" -l

# Search browser tests
echo -e "\n🌐 Browser Tests:"
grep -r "$FEATURE_NAME" scripts/browser-tests/ --include="*.js" -l

# Search for specific API endpoints
echo -e "\n🔌 API Endpoint Tests:"
grep -r "/api/$FEATURE_NAME" tests/ --include="*.php" -l

echo -e "\n✅ Review these files and update assertions/expectations as needed"
```

**Usage:**
```bash
bash scripts/find-related-tests.sh campaigns
```

---

### 📊 Verification Report Template

After verification, document your findings:

```markdown
# Verification Report: [Feature Name]

**Date:** 2025-11-30
**Developer:** Claude Code Agent
**Changes Made:** [Brief description]

## ✅ Verification Results

### Browser Console
- ✅ No JavaScript errors
- ✅ No warnings
- 📊 Console messages: 12 (all informational)

### Laravel Logs
- ✅ No errors
- ✅ No exceptions
- 📊 Debug logs: 5 entries (all expected)

### Screenshots
- ✅ Initial state: `test-results/01-initial.png`
- ✅ After interaction: `test-results/02-after-click.png`
- ✅ Final state: `test-results/03-success.png`

### Functional Tests
- ✅ Element visibility: PASSED
- ✅ Click interaction: PASSED
- ✅ Data display: PASSED
- ✅ Multi-language: PASSED (AR/EN)
- ✅ Network requests: All 200 OK

### Automated Tests Created
- ✅ `tests/Feature/YourFeatureTest.php` (5 tests)
- ✅ `scripts/browser-tests/your-feature.spec.js` (4 tests)

### Tests Updated
- ✅ `tests/Feature/RelatedFeatureTest.php` (updated API assertions)
- ✅ `scripts/browser-tests/integration.spec.js` (updated selectors)

## 🎯 Overall Status: ✅ SUCCESS

All verification checks passed. Feature is ready for production.
```

---

### 🚨 Common Verification Failures

| Symptom | Cause | Fix |
|---------|-------|-----|
| `TypeError: Cannot read property 'X' of undefined` | Missing null check | Add `if (obj?.property)` |
| `404 Not Found` on API call | Route not registered | Check `routes/api.php` |
| Element not visible | CSS `display: none` or `opacity: 0` | Check computed styles with Playwright |
| Test fails: "Expected 200, got 500" | Server error | Check `storage/logs/laravel.log` |
| Test fails: "Element not found" | Selector changed | Update Playwright selector |
| RLS violation | Missing `init_transaction_context()` | Add to controller/test |
| CSRF token mismatch | Missing CSRF in test | Use `postJson()` instead of `post()` |

---

### 💡 Best Practices for Verification

1. **Automate Everything** - Never manually check; write scripts
2. **Test Both Languages** - Always verify Arabic (RTL) and English (LTR)
3. **Test Multi-Tenancy** - Verify data isolation between orgs
4. **Check Logs FIRST** - Before debugging, check logs
5. **Take Screenshots** - Visual proof of success/failure
6. **Create Tests Immediately** - Don't defer test creation
7. **Update Related Tests** - Search and update affected tests
8. **Document Findings** - Keep verification reports
9. **Test Edge Cases** - Empty data, invalid input, permissions
10. **Monitor Performance** - Check response times, N+1 queries
11. **Commit Automatically** - After verification passes, commit to git immediately

---

## 🔄 Automatic Git Commits (MANDATORY)

**CRITICAL:** After successful verification, you MUST automatically commit your changes to git. **DO NOT wait for user to ask.**

### When to Auto-Commit

✅ **Commit immediately after:**
1. All verification checks pass (console, logs, screenshots, tests)
2. Automated tests are created and passing
3. Related tests are updated (if needed)
4. No errors detected in browser console or Laravel logs

❌ **DO NOT commit if:**
- Verification failed
- Tests are failing
- Console errors detected
- Laravel logs show exceptions
- Feature doesn't work as expected

### Auto-Commit Workflow

```bash
# STEP 1: Verify all checks passed
echo "=== VERIFICATION SUMMARY ==="
echo "✅ Browser console: No errors"
echo "✅ Laravel logs: Clean"
echo "✅ Screenshots: Feature works"
echo "✅ Tests: All passing"
echo "✅ Related tests: Updated"

# STEP 2: Check git status
git status

# STEP 3: Add all changed files
git add .

# STEP 4: Create descriptive commit with Claude attribution
git commit -m "$(cat <<'EOF'
feat: Add AI Assistant overlay to publish modal

- Implemented AI Assistant overlay with Alpine.js
- Added translation keys for AR/EN support
- Created automated tests (Feature + Browser)
- Fixed div nesting in related overlay files
- Verified: Browser console clean, Laravel logs clean
- Tests: 5 passing (YourFeatureTest.php)

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"

# STEP 5: Verify commit was created
git log -1 --oneline
```

### Commit Message Format (REQUIRED)

**Use conventional commits format:**

```
<type>: <short description>

- <detailed change 1>
- <detailed change 2>
- <detailed change 3>
- Verified: Browser console clean, Laravel logs clean
- Tests: <number> passing (<test file names>)

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Commit types:**
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring
- `test:` - Adding/updating tests
- `docs:` - Documentation changes
- `style:` - Code style/formatting
- `perf:` - Performance improvements
- `chore:` - Build/tooling changes

### Complete Auto-Commit Script Template

```bash
#!/bin/bash
# scripts/auto-commit.sh

FEATURE_NAME="$1"
COMMIT_TYPE="${2:-feat}" # Default to 'feat'

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== AUTO-COMMIT WORKFLOW ===${NC}\n"

# Step 1: Verify git is initialized
if [ ! -d .git ]; then
    echo -e "${RED}❌ Error: Not a git repository${NC}"
    exit 1
fi

# Step 2: Check for changes
if git diff --quiet && git diff --cached --quiet; then
    echo -e "${RED}❌ No changes to commit${NC}"
    exit 0
fi

# Step 3: Show what will be committed
echo -e "${GREEN}📝 Files to be committed:${NC}"
git status --short

# Step 4: Get list of changed files
CHANGED_FILES=$(git diff --name-only --cached 2>/dev/null || git diff --name-only 2>/dev/null)

# Step 5: Determine what changed (for commit message)
BACKEND_CHANGES=$(echo "$CHANGED_FILES" | grep -E '\.(php)$' | wc -l)
FRONTEND_CHANGES=$(echo "$CHANGED_FILES" | grep -E '\.(blade\.php|js|css)$' | wc -l)
TEST_CHANGES=$(echo "$CHANGED_FILES" | grep -E 'tests/' | wc -l)
MIGRATION_CHANGES=$(echo "$CHANGED_FILES" | grep -E 'database/migrations/' | wc -l)

# Step 6: Build detailed change list
CHANGE_DETAILS=""
[ $BACKEND_CHANGES -gt 0 ] && CHANGE_DETAILS="${CHANGE_DETAILS}- Updated $BACKEND_CHANGES backend file(s)\n"
[ $FRONTEND_CHANGES -gt 0 ] && CHANGE_DETAILS="${CHANGE_DETAILS}- Updated $FRONTEND_CHANGES frontend file(s)\n"
[ $TEST_CHANGES -gt 0 ] && CHANGE_DETAILS="${CHANGE_DETAILS}- Added/updated $TEST_CHANGES test file(s)\n"
[ $MIGRATION_CHANGES -gt 0 ] && CHANGE_DETAILS="${CHANGE_DETAILS}- Added/updated $MIGRATION_CHANGES migration(s)\n"

# Step 7: Add all changes
git add .

# Step 8: Create commit
git commit -m "$(cat <<EOF
${COMMIT_TYPE}: ${FEATURE_NAME}

${CHANGE_DETAILS}- Verified: Browser console clean, Laravel logs clean
- All automated tests passing

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"

# Step 9: Verify commit
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✅ Commit successful!${NC}"
    echo -e "${GREEN}Latest commit:${NC}"
    git log -1 --oneline --decorate
else
    echo -e "\n${RED}❌ Commit failed!${NC}"
    exit 1
fi
```

**Usage:**
```bash
# After verification passes
bash scripts/auto-commit.sh "Add AI Assistant overlay" feat

# For bug fixes
bash scripts/auto-commit.sh "Fix overlay nesting issue" fix

# For refactoring
bash scripts/auto-commit.sh "Extract overlay component" refactor
```

### Inline Auto-Commit (Use in Verification Scripts)

```javascript
// At the end of your Playwright verification script
const { execSync } = require('child_process');

(async () => {
    // ... your verification code ...

    if (allChecksPassed) {
        console.log('\n✅ ALL VERIFICATION PASSED - Creating git commit...\n');

        try {
            // Add all changes
            execSync('git add .', { stdio: 'inherit' });

            // Create commit with heredoc
            const commitMessage = `feat: Add new feature

- Implemented feature X with Alpine.js
- Added bilingual support (AR/EN)
- Created automated tests
- Verified: Browser console clean, Laravel logs clean
- Tests: 5 passing

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>`;

            execSync(`git commit -m "${commitMessage}"`, { stdio: 'inherit' });

            console.log('✅ Git commit created successfully!');

            // Show latest commit
            execSync('git log -1 --oneline', { stdio: 'inherit' });

        } catch (error) {
            console.error('❌ Git commit failed:', error.message);
        }
    } else {
        console.log('\n❌ VERIFICATION FAILED - NOT committing to git');
    }
})();
```

### Git Safety Checklist (ALWAYS Follow)

✅ **DO:**
- Commit after successful verification
- Use descriptive commit messages
- Include verification status in commit message
- Add Claude Code attribution
- Check git status before committing
- Verify commit was created successfully

❌ **DO NOT:**
- Commit without verification
- Use `--no-verify` flag
- Use `git commit --amend` (unless explicitly requested)
- Force push (`git push --force`)
- Push to main/master directly
- Commit secrets or .env files
- Skip Claude Code attribution

### Example Commit Messages

**Feature Addition:**
```
feat: Add campaign analytics dashboard

- Created dashboard component with Chart.js
- Implemented real-time metrics API endpoint
- Added bilingual support (Arabic/English)
- Created automated tests (5 Feature + 3 Browser tests)
- Verified: Browser console clean, Laravel logs clean
- Tests: 8/8 passing

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Bug Fix:**
```
fix: Resolve AI Assistant overlay nesting issue

- Fixed unclosed div tags in best-times.blade.php
- Fixed extra closing divs in hashtag-manager.blade.php
- Fixed extra closing divs in media-library.blade.php
- Verified overlay now displays correctly
- Verified: Browser console clean, no DOM errors
- Tests: Updated overlay integration tests

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Refactoring:**
```
refactor: Extract publish modal overlay components

- Split monolithic modal into reusable components
- Applied DRY principle to reduce 200 lines of duplicate code
- Maintained backward compatibility
- All existing tests passing (12/12)
- Verified: No regressions, all features work

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Test Addition:**
```
test: Add comprehensive tests for publish modal

- Added 5 Feature tests for modal interactions
- Added 3 Browser tests with Playwright
- Tests cover: Arabic/English, multi-tenancy, validation
- All tests passing (8/8)
- Coverage increased by 12%

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

---

## 🎯 Quick Reference Commands

### Playwright Test Template

```javascript
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Set auth cookie
    await context.addCookies([{
        name: 'app_locale',
        value: 'ar',
        url: 'https://cmis-test.kazaaz.com'
    }]);

    // Navigate
    await page.goto('https://cmis-test.kazaaz.com/login');

    // Login
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Your diagnostic code here

    await browser.close();
})();
```

### Laravel Debug Snippet

```php
// Add to controller/service
\Log::channel('single')->info('[DEBUG]', [
    'checkpoint' => 'function_name',
    'data' => $variable,
    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
]);
```

### Quick HTML Validation

```bash
# Check div balance
opening=$(grep -o '<div' file.blade.php | wc -l)
closing=$(grep -o '</div>' file.blade.php | wc -l)
echo "Difference: $((opening - closing))"
```

---

## 📞 Support Resources

- **Playwright Docs:** https://playwright.dev/docs/intro
- **Laravel Debugging:** https://laravel.com/docs/logging
- **Alpine.js Devtools:** Browser extension for inspecting Alpine state
- **PostgreSQL Docs:** https://www.postgresql.org/docs/current/

---

**Remember:** When in doubt, **automate the inspection** using Playwright or similar tools. Visual inspection of code is helpful, but **runtime inspection tells the truth**.
