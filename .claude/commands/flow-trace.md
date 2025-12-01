---
description: Trace complete execution flow for a feature - identify all nodes from frontend to database
---

Trace the complete execution flow for a CMIS feature:

## Step 1: Identify Feature Entry Point

Ask user for feature details:
- What is the feature name or URL?
- What user action triggers it? (button click, form submit, page load)
- What is the expected outcome?

## Step 2: Frontend Flow Discovery

```bash
echo "=== FRONTEND FLOW DISCOVERY ==="

# Search for the feature in Blade templates
FEATURE="$1"
echo "Searching for: $FEATURE"

echo ""
echo "📄 Blade Templates:"
grep -rn "$FEATURE" resources/views/ --include="*.blade.php" 2>/dev/null | head -20

echo ""
echo "🔘 Click Handlers (@click):"
grep -rn "@click.*$FEATURE\|$FEATURE.*@click" resources/views/ --include="*.blade.php" 2>/dev/null | head -10

echo ""
echo "📊 Alpine.js Components (x-data):"
grep -rn "x-data.*$FEATURE" resources/views/ --include="*.blade.php" 2>/dev/null | head -10

echo ""
echo "🌐 Fetch/Axios Calls:"
grep -rn "fetch\|axios" resources/views/ --include="*.blade.php" 2>/dev/null | grep -i "$FEATURE" | head -10

echo ""
echo "📁 JavaScript Files:"
grep -rn "$FEATURE" resources/js/ --include="*.js" 2>/dev/null | head -10
```

## Step 3: Route Discovery

```bash
echo ""
echo "=== ROUTE DISCOVERY ==="

# Check web routes
echo "🌐 Web Routes:"
grep -n "$FEATURE" routes/web.php 2>/dev/null | head -10

# Check API routes
echo ""
echo "🔌 API Routes:"
grep -n "$FEATURE" routes/api.php 2>/dev/null | head -10

# Full route list
echo ""
echo "📋 All Matching Routes:"
php artisan route:list | grep -i "$FEATURE" 2>/dev/null | head -15
```

## Step 4: Controller Discovery

```bash
echo ""
echo "=== CONTROLLER DISCOVERY ==="

# Find controllers
echo "🎮 Controllers:"
grep -rln "$FEATURE" app/Http/Controllers/ --include="*.php" 2>/dev/null

# Find specific methods
echo ""
echo "📍 Controller Methods:"
grep -rn "function.*$FEATURE\|$FEATURE" app/Http/Controllers/ --include="*.php" 2>/dev/null | head -15
```

## Step 5: Service Layer Discovery

```bash
echo ""
echo "=== SERVICE LAYER DISCOVERY ==="

# Find services
echo "⚙️ Services:"
grep -rln "$FEATURE" app/Services/ --include="*.php" 2>/dev/null

# Find specific methods
echo ""
echo "📍 Service Methods:"
grep -rn "function.*$FEATURE\|$FEATURE" app/Services/ --include="*.php" 2>/dev/null | head -15
```

## Step 6: Repository/Model Discovery

```bash
echo ""
echo "=== MODEL/REPOSITORY DISCOVERY ==="

# Find models
echo "📦 Models:"
grep -rln "$FEATURE" app/Models/ --include="*.php" 2>/dev/null

# Find repositories
echo ""
echo "🗄️ Repositories:"
grep -rln "$FEATURE" app/Repositories/ --include="*.php" 2>/dev/null

# Find database calls
echo ""
echo "💾 Database Queries:"
grep -rn "DB::\|->where\|->find\|->create\|->update\|->delete" app/ --include="*.php" | grep -i "$FEATURE" 2>/dev/null | head -10
```

## Step 7: Database Table Discovery

```bash
echo ""
echo "=== DATABASE DISCOVERY ==="

# Get database info
DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2)
DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)
DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)
DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)

# Find related tables
echo "📊 Related Tables:"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT schemaname || '.' || tablename as table_name
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  AND tablename ILIKE '%${FEATURE}%'
ORDER BY tablename;
" 2>/dev/null

# Check RLS policies on related tables
echo ""
echo "🔒 RLS Policies:"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT schemaname, tablename, policyname
FROM pg_policies
WHERE schemaname LIKE 'cmis%'
  AND tablename ILIKE '%${FEATURE}%';
" 2>/dev/null
```

## Step 8: Generate Flow Diagram

Based on the discovery, generate a text-based flow diagram:

```
╔══════════════════════════════════════════════════════════════╗
║                    FEATURE FLOW: $FEATURE                     ║
╠══════════════════════════════════════════════════════════════╣
║                                                               ║
║  USER ACTION                                                  ║
║      │                                                        ║
║      ▼                                                        ║
║  ┌─────────────────────────────────────────────────────────┐ ║
║  │ FRONTEND                                                 │ ║
║  │   File: [blade file path]                               │ ║
║  │   Component: [x-data name]                              │ ║
║  │   Handler: [function name]                              │ ║
║  │   Line: [line number]                                   │ ║
║  └─────────────────────────┬───────────────────────────────┘ ║
║                            │                                  ║
║                            ▼ HTTP [METHOD] /api/endpoint      ║
║  ┌─────────────────────────────────────────────────────────┐ ║
║  │ ROUTE                                                    │ ║
║  │   File: routes/[web|api].php                            │ ║
║  │   Line: [line number]                                   │ ║
║  │   Controller: [ControllerName@method]                   │ ║
║  └─────────────────────────┬───────────────────────────────┘ ║
║                            │                                  ║
║                            ▼                                  ║
║  ┌─────────────────────────────────────────────────────────┐ ║
║  │ CONTROLLER                                               │ ║
║  │   File: app/Http/Controllers/[path]                     │ ║
║  │   Method: [method name]                                 │ ║
║  │   Line: [line number]                                   │ ║
║  │   Calls: [service method]                               │ ║
║  └─────────────────────────┬───────────────────────────────┘ ║
║                            │                                  ║
║                            ▼                                  ║
║  ┌─────────────────────────────────────────────────────────┐ ║
║  │ SERVICE                                                  │ ║
║  │   File: app/Services/[path]                             │ ║
║  │   Method: [method name]                                 │ ║
║  │   Line: [line number]                                   │ ║
║  │   Calls: [repository/model method]                      │ ║
║  └─────────────────────────┬───────────────────────────────┘ ║
║                            │                                  ║
║                            ▼                                  ║
║  ┌─────────────────────────────────────────────────────────┐ ║
║  │ MODEL/REPOSITORY                                         │ ║
║  │   File: app/Models/[path] or app/Repositories/[path]    │ ║
║  │   Method: [method name]                                 │ ║
║  │   Line: [line number]                                   │ ║
║  └─────────────────────────┬───────────────────────────────┘ ║
║                            │                                  ║
║                            ▼                                  ║
║  ┌─────────────────────────────────────────────────────────┐ ║
║  │ DATABASE                                                 │ ║
║  │   Table: cmis.[table_name]                              │ ║
║  │   RLS Policy: [policy_name]                             │ ║
║  │   Query Type: [SELECT|INSERT|UPDATE|DELETE]             │ ║
║  └─────────────────────────────────────────────────────────┘ ║
║                                                               ║
╚══════════════════════════════════════════════════════════════╝
```

## Step 9: Debug Point Template

Generate debug points for each node:

**Frontend Debug:**
```javascript
// Add to Alpine component
x-init="console.log('[DEBUG:FRONTEND] Component init:', $data)"
@click="console.log('[DEBUG:FRONTEND] Action triggered'); yourMethod()"
```

**Controller Debug:**
```php
\Log::debug('[DEBUG:CTRL] Entry', ['request' => $request->all()]);
// ... code ...
\Log::debug('[DEBUG:CTRL] Exit', ['result' => $result]);
```

**Service Debug:**
```php
\Log::debug('[DEBUG:SVC] Entry', ['params' => $params]);
// ... code ...
\Log::debug('[DEBUG:SVC] Exit', ['result' => $result]);
```

**Model Debug:**
```php
\Log::debug('[DEBUG:MODEL] Query', ['sql' => DB::getQueryLog()]);
```

## Step 10: Create Debug Script

Create a custom debug script for this flow:

```bash
cat > scripts/troubleshoot/debug-$FEATURE.cjs << 'EOF'
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await (await browser.newContext()).newPage();

    // Capture console for DEBUG messages
    page.on('console', msg => {
        if (msg.text().includes('[DEBUG:')) {
            console.log(msg.text());
        }
    });

    // Login
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.fill('input[name="email"]', 'admin@cmis.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Navigate to feature
    await page.goto('https://cmis-test.kazaaz.com/YOUR_FEATURE_URL');

    // Trigger the action
    // await page.click('#your-trigger');

    // Wait for result
    await page.waitForTimeout(2000);

    // Screenshot
    await page.screenshot({ path: 'test-results/debug-$FEATURE.png', fullPage: true });

    await browser.close();
})();
EOF

echo "Debug script created: scripts/troubleshoot/debug-$FEATURE.cjs"
```

## Usage

Run with feature name:
```
/flow-trace campaign
/flow-trace social-post
/flow-trace ai-assistant
```

This will discover and document the complete flow from user action to database.
