---
description: All-in-one debugging - Laravel logs, browser console, API tests, screenshots, flow tracing
---

Perform comprehensive all-in-one debugging for CMIS:

## Step 1: Pre-Diagnostic Setup

```bash
echo "=== CMIS ALL-IN-ONE DEBUGGER ==="
echo "Starting diagnostic at $(date)"

# Create test results directory
mkdir -p test-results/debug

# Clear Laravel logs for fresh diagnostic
cp storage/logs/laravel.log test-results/debug/laravel-backup-$(date +%Y%m%d%H%M%S).log
echo "" > storage/logs/laravel.log
echo "✅ Laravel logs cleared for fresh diagnostic"
```

## Step 2: Laravel Backend Check

```bash
echo ""
echo "=== BACKEND HEALTH CHECK ==="

# Test database connection
php artisan tinker --execute="
try {
    \$db = DB::connection()->getDatabaseName();
    echo '✅ Database: ' . \$db . PHP_EOL;
} catch (\Exception \$e) {
    echo '❌ Database Error: ' . \$e->getMessage() . PHP_EOL;
}
"

# Check for recent exceptions
echo ""
echo "Recent Laravel Errors (last 24h):"
find storage/logs -name "*.log" -mtime -1 -exec grep -l "Exception\|Error" {} \; 2>/dev/null | head -5

# Check queue status
echo ""
echo "Queue Status:"
php artisan queue:failed --limit=5 2>/dev/null || echo "No failed jobs"

# Check cache status
echo ""
echo "Cache Status:"
php artisan cache:clear --quiet && echo "✅ Cache operational"
```

## Step 3: Browser Console & Screenshots

Create and run a Playwright diagnostic script:

```bash
cat > test-results/debug/browser-diagnostic.cjs << 'EOF'
const { chromium } = require('playwright');
const fs = require('fs');

const TEST_URL = process.argv[2] || 'https://cmis-test.kazaaz.com/login';

(async () => {
    console.log('=== BROWSER DIAGNOSTIC ===');
    console.log('URL:', TEST_URL);

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Capture console logs
    const consoleLogs = [];
    page.on('console', msg => {
        consoleLogs.push({
            type: msg.type(),
            text: msg.text(),
            location: msg.location()
        });
        if (msg.type() === 'error') {
            console.log('❌ Console Error:', msg.text());
        }
    });

    // Capture page errors
    const pageErrors = [];
    page.on('pageerror', error => {
        pageErrors.push(error.message);
        console.log('❌ Page Error:', error.message);
    });

    // Capture network failures
    const networkErrors = [];
    page.on('response', response => {
        if (!response.ok() && response.status() !== 304) {
            networkErrors.push({
                url: response.url(),
                status: response.status()
            });
            console.log(`❌ Network ${response.status()}:`, response.url().substring(0, 80));
        }
    });

    try {
        // Navigate
        await page.goto(TEST_URL, { waitUntil: 'networkidle', timeout: 30000 });
        console.log('✅ Page loaded');

        // Take screenshot
        await page.screenshot({
            path: 'test-results/debug/screenshot-initial.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved: screenshot-initial.png');

        // Get page info
        const pageInfo = await page.evaluate(() => {
            return {
                title: document.title,
                url: window.location.href,
                lang: document.documentElement.lang,
                dir: document.documentElement.dir,
                bodyClasses: document.body.className,
                viewportWidth: window.innerWidth,
                viewportHeight: window.innerHeight,
                documentWidth: document.documentElement.scrollWidth,
                documentHeight: document.documentElement.scrollHeight,
                hasAlpine: typeof Alpine !== 'undefined',
                alpineComponents: document.querySelectorAll('[x-data]').length
            };
        });

        console.log('\n=== PAGE INFO ===');
        console.log('Title:', pageInfo.title);
        console.log('Language:', pageInfo.lang, '| Direction:', pageInfo.dir);
        console.log('Viewport:', pageInfo.viewportWidth, 'x', pageInfo.viewportHeight);
        console.log('Document:', pageInfo.documentWidth, 'x', pageInfo.documentHeight);
        console.log('Alpine.js:', pageInfo.hasAlpine ? `Yes (${pageInfo.alpineComponents} components)` : 'No');

        // Check for horizontal overflow
        if (pageInfo.documentWidth > pageInfo.viewportWidth) {
            console.log('⚠️ HORIZONTAL OVERFLOW:', pageInfo.documentWidth - pageInfo.viewportWidth, 'px');
        }

    } catch (error) {
        console.log('❌ Navigation Error:', error.message);
        await page.screenshot({ path: 'test-results/debug/screenshot-error.png' });
    }

    // Summary
    console.log('\n=== DIAGNOSTIC SUMMARY ===');
    console.log('Console Errors:', pageErrors.length + consoleLogs.filter(l => l.type === 'error').length);
    console.log('Network Errors:', networkErrors.length);
    console.log('Page Errors:', pageErrors.length);

    // Save full report
    const report = {
        url: TEST_URL,
        timestamp: new Date().toISOString(),
        consoleLogs,
        pageErrors,
        networkErrors
    };
    fs.writeFileSync('test-results/debug/browser-report.json', JSON.stringify(report, null, 2));
    console.log('✅ Full report saved: browser-report.json');

    await browser.close();

    // Exit with error code if issues found
    const hasErrors = pageErrors.length > 0 || networkErrors.length > 0 ||
                      consoleLogs.filter(l => l.type === 'error').length > 0;
    process.exit(hasErrors ? 1 : 0);
})();
EOF

echo ""
echo "=== RUNNING BROWSER DIAGNOSTIC ==="
node test-results/debug/browser-diagnostic.cjs "$1" 2>&1
```

## Step 4: API Endpoint Testing

```bash
echo ""
echo "=== API ENDPOINT TESTS ==="

# Test health endpoint
echo -n "Health Check: "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://cmis-test.kazaaz.com/ 2>/dev/null)
[ "$HTTP_CODE" = "200" ] && echo "✅ $HTTP_CODE" || echo "❌ $HTTP_CODE"

# Test login page
echo -n "Login Page: "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://cmis-test.kazaaz.com/login 2>/dev/null)
[ "$HTTP_CODE" = "200" ] && echo "✅ $HTTP_CODE" || echo "❌ $HTTP_CODE"

# Get page source for analysis
echo ""
echo "Saving page source..."
curl -s https://cmis-test.kazaaz.com/login > test-results/debug/page-source.html
echo "✅ Page source saved: page-source.html"

# Check for common issues in page source
echo ""
echo "Page Source Analysis:"
grep -c "<!DOCTYPE" test-results/debug/page-source.html > /dev/null && echo "✅ Valid HTML doctype" || echo "❌ Missing doctype"
grep -c 'lang="' test-results/debug/page-source.html > /dev/null && echo "✅ Language attribute set" || echo "⚠️ Missing lang attribute"
grep -c 'dir="' test-results/debug/page-source.html > /dev/null && echo "✅ Direction attribute set" || echo "⚠️ Missing dir attribute"
grep -c "csrf-token" test-results/debug/page-source.html > /dev/null && echo "✅ CSRF token present" || echo "❌ Missing CSRF token"
```

## Step 5: Check Laravel Logs After Test

```bash
echo ""
echo "=== LARAVEL LOG ANALYSIS ==="

# Check for any errors generated during test
if [ -s storage/logs/laravel.log ]; then
    ERROR_COUNT=$(grep -c -i "error\|exception" storage/logs/laravel.log 2>/dev/null || echo "0")
    WARNING_COUNT=$(grep -c -i "warning" storage/logs/laravel.log 2>/dev/null || echo "0")

    echo "Errors: $ERROR_COUNT"
    echo "Warnings: $WARNING_COUNT"

    if [ "$ERROR_COUNT" -gt 0 ]; then
        echo ""
        echo "Recent Errors:"
        grep -i "error\|exception" storage/logs/laravel.log | tail -10
    fi
else
    echo "✅ No errors logged during diagnostic"
fi
```

## Step 6: Database Quick Check

```bash
echo ""
echo "=== DATABASE QUICK CHECK ==="

# Get database info from .env
DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2)
DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)
DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)
DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)

# Test connection and RLS
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT
    'Connection' as check,
    'OK' as status
UNION ALL
SELECT
    'RLS Tables',
    COUNT(*)::text || ' tables'
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
UNION ALL
SELECT
    'RLS Policies',
    COUNT(*)::text || ' policies'
FROM pg_policies
WHERE schemaname LIKE 'cmis%';
" 2>/dev/null || echo "❌ Database connection failed"
```

## Step 7: Generate Summary Report

```bash
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    DEBUG SUMMARY REPORT                       ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║ Timestamp: $(date)                          ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║ Backend:                                                      ║"
echo "║   Database: [Check above]                                    ║"
echo "║   Laravel Logs: $ERROR_COUNT errors, $WARNING_COUNT warnings              ║"
echo "║   Cache: [Operational]                                        ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║ Frontend:                                                     ║"
echo "║   Console Errors: [Check browser-report.json]                ║"
echo "║   Screenshots: test-results/debug/                           ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║ Files Generated:                                              ║"
echo "║   - test-results/debug/screenshot-initial.png                ║"
echo "║   - test-results/debug/browser-report.json                   ║"
echo "║   - test-results/debug/page-source.html                      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
```

## Usage Notes

- Run with URL argument: `/debug https://cmis-test.kazaaz.com/social`
- Default URL is login page
- Check `test-results/debug/` for all generated files
- If errors found, use `cmis-troubleshooting` agent for deep analysis

## Next Steps If Issues Found

1. **Console Errors:** Check `browser-report.json` for stack traces
2. **Laravel Errors:** Review full log with `tail -100 storage/logs/laravel.log`
3. **API Failures:** Use curl with `-v` flag for verbose output
4. **Visual Issues:** Review screenshots in `test-results/debug/`

For comprehensive troubleshooting, use the `cmis-troubleshooting` agent.
