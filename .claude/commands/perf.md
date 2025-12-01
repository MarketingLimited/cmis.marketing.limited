---
description: Profile CMIS application performance and identify bottlenecks
---

Profile CMIS application performance:

## Step 1: Database Performance

```bash
echo "=== Database Performance Analysis ==="

# Get database connection info from .env
DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2)
DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)
DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)
DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)

# Largest tables
echo "=== Largest Tables ==="
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT schemaname || '.' || tablename AS table_name,
       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS total_size,
       pg_size_pretty(pg_relation_size(schemaname||'.'||tablename)) AS data_size,
       pg_size_pretty(pg_indexes_size(schemaname||'.'||tablename::regclass)) AS index_size
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 15;
"
```

## Step 2: Unused Indexes

```bash
echo "=== Unused Indexes (Never Scanned) ==="
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT schemaname || '.' || relname AS table,
       indexrelname AS index,
       pg_size_pretty(pg_relation_size(indexrelid)) AS size,
       idx_scan AS scans
FROM pg_stat_user_indexes
WHERE schemaname LIKE 'cmis%'
  AND idx_scan = 0
  AND pg_relation_size(indexrelid) > 8192
ORDER BY pg_relation_size(indexrelid) DESC
LIMIT 15;
"
```

## Step 3: Missing Indexes

```bash
echo "=== Potential Missing Indexes ==="
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT schemaname || '.' || relname AS table,
       seq_scan AS sequential_scans,
       seq_tup_read AS rows_scanned,
       idx_scan AS index_scans,
       CASE WHEN seq_scan > 0
            THEN round(seq_tup_read::numeric / seq_scan)
            ELSE 0 END AS avg_rows_per_scan
FROM pg_stat_user_tables
WHERE schemaname LIKE 'cmis%'
  AND seq_scan > 100
  AND seq_tup_read > 10000
ORDER BY seq_tup_read DESC
LIMIT 15;
"
```

## Step 4: Slow Queries (if pg_stat_statements enabled)

```bash
echo "=== Slow Queries ==="
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
SELECT
    round(total_exec_time::numeric, 2) AS total_time_ms,
    calls,
    round(mean_exec_time::numeric, 2) AS avg_time_ms,
    LEFT(query, 80) AS query_preview
FROM pg_stat_statements
WHERE dbid = (SELECT oid FROM pg_database WHERE datname = current_database())
ORDER BY total_exec_time DESC
LIMIT 10;
" 2>/dev/null || echo "pg_stat_statements extension not enabled"
```

## Step 5: Laravel Query Analysis

```bash
echo "=== Laravel Query Patterns ==="

# Check for N+1 query patterns in code
echo "Potential N+1 queries (loops with DB calls):"
grep -r -n "foreach.*\$.*->.*\$.*->" app/ --include="*.php" | grep -v "Collection" | head -10

echo ""
echo "Missing eager loading opportunities:"
grep -r -n "::find\|::first\|::get" app/Http/Controllers/ --include="*.php" | grep -v "with(" | head -10
```

## Step 6: Cache Analysis

```bash
echo "=== Cache Configuration ==="
CACHE_DRIVER=$(grep '^CACHE_DRIVER=' .env | cut -d'=' -f2)
echo "Cache Driver: $CACHE_DRIVER"

# Check cache status
php artisan cache:clear --quiet 2>/dev/null
echo "Cache cleared for fresh analysis"

# Check config cache
test -f bootstrap/cache/config.php && echo "✅ Config cached" || echo "⚠️ Config not cached (run: php artisan config:cache)"
test -f bootstrap/cache/routes-v7.php && echo "✅ Routes cached" || echo "⚠️ Routes not cached (run: php artisan route:cache)"
```

## Step 7: Memory & Load

```bash
echo "=== System Resources ==="
echo "Memory Usage:"
free -h | head -2

echo ""
echo "PHP Memory Limit:"
php -r "echo 'PHP memory_limit: ' . ini_get('memory_limit') . PHP_EOL;"

echo ""
echo "Disk I/O (current):"
iostat -x 1 1 2>/dev/null | tail -5 || echo "iostat not available"
```

## Step 8: Application Metrics

```bash
echo "=== Application Metrics ==="
php artisan tinker --execute="
echo 'Total Models: ' . count(glob(app_path('Models/**/*.php')));
echo 'Total Controllers: ' . count(glob(app_path('Http/Controllers/**/*.php')));
echo 'Total Routes: ' . count(Route::getRoutes());
" 2>/dev/null
```

## Performance Report

```
╔══════════════════════════════════════════════════════════════╗
║                    PERFORMANCE REPORT                         ║
╠══════════════════════════════════════════════════════════════╣
║ DATABASE                                                      ║
║   Largest Table:      [table_name] ([size])                  ║
║   Unused Indexes:     [X] (wasting [Y] MB)                   ║
║   Missing Indexes:    [X tables need attention]              ║
║   Slow Queries:       [X queries > 100ms avg]                ║
╠══════════════════════════════════════════════════════════════╣
║ APPLICATION                                                   ║
║   N+1 Patterns:       [X potential issues]                   ║
║   Cache Status:       [Configured/Not configured]            ║
║   Config Cached:      [Yes/No]                               ║
║   Routes Cached:      [Yes/No]                               ║
╠══════════════════════════════════════════════════════════════╣
║ SYSTEM                                                        ║
║   Memory Available:   [X GB]                                  ║
║   PHP Memory Limit:   [X MB]                                  ║
║   Disk Usage:         [X%]                                    ║
╠══════════════════════════════════════════════════════════════╣
║ RECOMMENDATIONS                                               ║
║   High Priority:                                              ║
║     1. [Most impactful optimization]                          ║
║     2. [Second priority]                                      ║
║   Medium Priority:                                            ║
║     3. [Optimization suggestion]                              ║
╚══════════════════════════════════════════════════════════════╝
```

## Quick Optimizations

Based on findings, suggest:

1. **Add missing indexes:**
   ```sql
   CREATE INDEX idx_table_column ON cmis.table_name(column_name);
   ```

2. **Remove unused indexes:**
   ```sql
   DROP INDEX IF EXISTS cmis.unused_index_name;
   ```

3. **Enable query caching:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

4. **Fix N+1 queries:**
   ```php
   // Before
   $campaigns = Campaign::all();
   foreach ($campaigns as $c) { $c->org->name; }

   // After
   $campaigns = Campaign::with('org')->get();
   ```
