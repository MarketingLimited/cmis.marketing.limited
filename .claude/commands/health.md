---
description: Check CMIS system health and service status
---

Perform comprehensive health check for CMIS platform:

## Step 1: Environment Check

```bash
# Read environment configuration
echo "=== Environment ==="
echo "APP_ENV: $(grep '^APP_ENV=' .env | cut -d'=' -f2)"
echo "APP_DEBUG: $(grep '^APP_DEBUG=' .env | cut -d'=' -f2)"
echo "APP_URL: $(grep '^APP_URL=' .env | cut -d'=' -f2)"
```

## Step 2: Database Health

```bash
# PostgreSQL connection
echo "=== Database ==="
DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2)
DB_PORT=$(grep '^DB_PORT=' .env | cut -d'=' -f2)
DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)

# Test connection
pg_isready -h "$DB_HOST" -p "$DB_PORT" && echo "✅ PostgreSQL: Connected" || echo "❌ PostgreSQL: Not responding"

# Check database exists
PGPASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)" psql -h "$DB_HOST" -U "$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)" -d "$DB_DATABASE" -c "SELECT 1;" >/dev/null 2>&1 && echo "✅ Database '$DB_DATABASE': Accessible" || echo "❌ Database: Not accessible"
```

## Step 3: Required Extensions

```bash
echo "=== PostgreSQL Extensions ==="
PGPASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)" psql -h "$(grep '^DB_HOST=' .env | cut -d'=' -f2)" -U "$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)" -d "$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)" -c "
SELECT extname, extversion FROM pg_extension WHERE extname IN ('uuid-ossp', 'vector', 'pgcrypto');
" 2>/dev/null || echo "Could not check extensions"
```

## Step 4: Laravel Application

```bash
echo "=== Laravel Application ==="

# Check artisan works
php artisan --version && echo "✅ Artisan: Working" || echo "❌ Artisan: Failed"

# Check configuration cache
test -f bootstrap/cache/config.php && echo "✅ Config: Cached" || echo "⚠️ Config: Not cached"

# Check routes cache
test -f bootstrap/cache/routes-v7.php && echo "✅ Routes: Cached" || echo "⚠️ Routes: Not cached"

# Check storage permissions
test -w storage/logs && echo "✅ Storage: Writable" || echo "❌ Storage: Not writable"
```

## Step 5: Cache & Queue

```bash
echo "=== Cache & Queue ==="

# Check Redis (if configured)
REDIS_HOST=$(grep '^REDIS_HOST=' .env | cut -d'=' -f2)
if [ -n "$REDIS_HOST" ]; then
    redis-cli -h "$REDIS_HOST" ping 2>/dev/null && echo "✅ Redis: Connected" || echo "❌ Redis: Not responding"
else
    echo "⚠️ Redis: Not configured"
fi

# Check queue connection
QUEUE_CONNECTION=$(grep '^QUEUE_CONNECTION=' .env | cut -d'=' -f2)
echo "Queue Driver: $QUEUE_CONNECTION"
```

## Step 6: Storage & Logs

```bash
echo "=== Storage ==="

# Check log file
if [ -f storage/logs/laravel.log ]; then
    LOG_SIZE=$(du -h storage/logs/laravel.log | cut -f1)
    echo "✅ Log file: $LOG_SIZE"

    # Check for recent errors
    RECENT_ERRORS=$(tail -100 storage/logs/laravel.log | grep -c "ERROR\|Exception" || echo "0")
    if [ "$RECENT_ERRORS" -gt 0 ]; then
        echo "⚠️ Recent errors in log: $RECENT_ERRORS"
    else
        echo "✅ No recent errors in log"
    fi
else
    echo "⚠️ Log file: Not found"
fi

# Check disk space
echo "Disk Usage: $(df -h . | tail -1 | awk '{print $5 " used"}')"
```

## Step 7: RLS Context

```bash
echo "=== Multi-Tenancy (RLS) ==="
php artisan tinker --execute="
try {
    DB::statement(\"SET app.current_org_id = '00000000-0000-0000-0000-000000000000'\");
    echo '✅ RLS context: Can be set';
} catch (\Exception \$e) {
    echo '❌ RLS context: Failed - ' . \$e->getMessage();
}
" 2>/dev/null || echo "Could not verify RLS"
```

## Step 8: External Services (Optional)

```bash
echo "=== External Services ==="

# Check if platform credentials exist
php artisan tinker --execute="
\$count = \App\Models\Core\Integration::where('is_active', true)->count();
echo 'Active integrations: ' . \$count;
" 2>/dev/null || echo "Could not check integrations"
```

## Summary Report

Generate a summary:

```
╔══════════════════════════════════════════════════════════════╗
║                    CMIS HEALTH CHECK                          ║
╠══════════════════════════════════════════════════════════════╣
║ Environment:    [local/staging/production]                    ║
║ Status:         [🟢 Healthy / 🟡 Warning / 🔴 Critical]       ║
╠══════════════════════════════════════════════════════════════╣
║ PostgreSQL:     [✅ OK / ❌ Failed]                            ║
║ Extensions:     [✅ All installed / ⚠️ Missing: X]            ║
║ Laravel:        [✅ OK / ❌ Failed]                            ║
║ Cache:          [✅ Configured / ⚠️ Not cached]               ║
║ Queue:          [✅ Running / ⚠️ Not running]                 ║
║ Storage:        [✅ Writable / ❌ Permission denied]          ║
║ RLS:            [✅ Working / ❌ Failed]                       ║
╠══════════════════════════════════════════════════════════════╣
║ Recommendations:                                              ║
║   [List any issues that need attention]                       ║
╚══════════════════════════════════════════════════════════════╝
```

## Quick Fixes

If issues found, suggest quick fixes:
- PostgreSQL not running: `sudo service postgresql start`
- Config not cached: `php artisan config:cache`
- Storage not writable: `chmod -R 775 storage`
- Missing extensions: `CREATE EXTENSION IF NOT EXISTS "uuid-ossp";`
