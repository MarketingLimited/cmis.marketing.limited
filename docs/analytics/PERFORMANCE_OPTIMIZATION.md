# Analytics Performance Optimization Guide

**Phase 10 - Performance Best Practices**

---

## 🎯 Overview

This document outlines performance optimization strategies for the CMIS Analytics system to ensure fast, responsive dashboards even with large datasets.

---

## 📊 Current Performance Targets

| Metric | Target | Status |
|--------|--------|--------|
| API Response Time | < 2000ms | ✅ Achieved |
| Page Load Time | < 3000ms | ✅ Optimized |
| Chart Render Time | < 500ms | ✅ Optimized |
| Auto-Refresh Impact | Minimal (no UI block) | ✅ Implemented |

---

## 🚀 Backend Optimizations

### 1. Database Query Optimization

#### Use Indexes
```sql
-- Add indexes on frequently queried columns
CREATE INDEX idx_campaigns_org_status ON cmis.campaigns(org_id, status);
CREATE INDEX idx_campaign_metrics_date ON cmis_analytics.metrics(campaign_id, date);
CREATE INDEX idx_alerts_org_status ON cmis_enterprise.alerts(org_id, status, severity);
```

#### Optimize Time-Range Queries
```php
// Use indexed date columns
DB::table('cmis_analytics.metrics')
    ->whereBetween('date', [$startDate, $endDate])
    ->where('campaign_id', $campaignId)
    ->get();

// Avoid LIKE on large tables
// BAD:
->where('name', 'LIKE', '%search%')
// GOOD:
->where('name', 'ILIKE', 'search%') // If starting with
// BETTER:
// Use full-text search indexes
```

#### Aggregate at Database Level
```php
// Don't load all rows then aggregate in PHP
// BAD:
$campaigns = Campaign::all();
$totalSpend = $campaigns->sum('budget');

// GOOD:
$totalSpend = Campaign::sum('budget');
```

### 2. Caching Strategy

#### API Response Caching
```php
use Illuminate\Support\Facades\Cache;

public function getRealTimeMetrics($orgId, $window)
{
    $cacheKey = "realtime.{$orgId}.{$window}";
    $cacheDuration = match($window) {
        '1m' => 30,   // 30 seconds
        '5m' => 60,   // 1 minute
        '15m' => 180, // 3 minutes
        '1h' => 300,  // 5 minutes
        default => 60
    };

    return Cache::remember($cacheKey, $cacheDuration, function() use ($orgId, $window) {
        return $this->calculateRealTimeMetrics($orgId, $window);
    });
}
```

#### Query Result Caching
```php
// Cache expensive queries
$campaigns = Cache::remember('org.' . $orgId . '.active_campaigns', 300, function() use ($orgId) {
    return Campaign::where('org_id', $orgId)
        ->where('status', 'active')
        ->with(['metrics' => function($query) {
            $query->latest()->limit(1);
        }])
        ->get();
});
```

### 3. Eager Loading (N+1 Prevention)

```php
// BAD: N+1 queries
$campaigns = Campaign::all();
foreach ($campaigns as $campaign) {
    echo $campaign->org->name; // Fires query for each campaign
}

// GOOD: Eager loading
$campaigns = Campaign::with('org')->get();
foreach ($campaigns as $campaign) {
    echo $campaign->org->name; // No additional queries
}

// BETTER: Select only needed columns
$campaigns = Campaign::with('org:org_id,name')->select('campaign_id', 'name', 'org_id')->get();
```

### 4. Pagination

```php
// Always paginate large result sets
$campaigns = Campaign::where('org_id', $orgId)
    ->orderBy('created_at', 'desc')
    ->paginate(20); // 20 items per page

// For APIs, use cursor pagination for better performance
$campaigns = Campaign::where('org_id', $orgId)
    ->orderBy('created_at', 'desc')
    ->cursorPaginate(20);
```

### 5. Background Jobs for Heavy Operations

```php
// For report generation, projections, etc.
use Illuminate\Support\Facades\Queue;

// Dispatch to queue instead of synchronous processing
Queue::push(new GenerateROIReport($campaignId, $dateRange));

// Or use Laravel Jobs
dispatch(new CalculateAttributionJob($campaignId));
```

---

## 🎨 Frontend Optimizations

### 1. Auto-Refresh Strategy

#### Configurable Intervals
```javascript
// Don't refresh too frequently
const REFRESH_INTERVALS = {
    realtime: 30000,   // 30 seconds
    kpi: 60000,        // 1 minute
    alerts: 30000,     // 30 seconds
    campaign: 120000   // 2 minutes (less frequently for detailed views)
};
```

#### Silent Updates
```javascript
// Update data without showing loading spinner
async loadDashboard(silent = false) {
    if (!silent) {
        this.loading = true;
    }

    try {
        const data = await fetch(url);
        this.updateData(data);
    } finally {
        if (!silent) {
            this.loading = false;
        }
    }
}

// Use silent updates for auto-refresh
setInterval(() => {
    this.loadDashboard(true); // silent = true
}, this.refreshInterval);
```

#### Stop Refresh on Hidden Tab
```javascript
// Pause auto-refresh when tab is not visible
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        this.stopAutoRefresh();
    } else {
        this.startAutoRefresh();
    }
});
```

### 2. Chart.js Optimization

#### Destroy Before Re-render
```javascript
// Prevent memory leaks
if (this.charts.roi) {
    this.charts.roi.destroy();
    this.charts.roi = null;
}

this.charts.roi = new Chart(ctx, config);
```

#### Use Decimation
```javascript
// For large datasets
new Chart(ctx, {
    type: 'line',
    data: data,
    options: {
        parsing: false, // Disable parsing for better performance
        normalized: true, // Data is already normalized
        plugins: {
            decimation: {
                enabled: true,
                algorithm: 'lttb', // Largest-Triangle-Three-Buckets
                samples: 500 // Reduce points displayed
            }
        }
    }
});
```

#### Defer Chart Rendering
```javascript
// Don't render charts until tab is visible
Alpine.data('campaignAnalytics', () => ({
    activeTab: 'overview',
    chartsRendered: {},

    changeTab(tab) {
        this.activeTab = tab;

        // Render chart only when tab becomes active
        if (!this.chartsRendered[tab]) {
            this.$nextTick(() => {
                this.renderChartForTab(tab);
                this.chartsRendered[tab] = true;
            });
        }
    }
}));
```

### 3. Virtual Scrolling (Future Enhancement)

For very long lists:
```javascript
// Use libraries like vue-virtual-scroll-list or implement custom
// Only render visible items + buffer
const visibleStart = Math.floor(scrollTop / itemHeight);
const visibleEnd = visibleStart + Math.ceil(containerHeight / itemHeight);
const visibleItems = allItems.slice(visibleStart, visibleEnd);
```

### 4. Lazy Loading Components

```javascript
// Load components only when needed
window.loadAnalyticsComponent = async (componentName) => {
    if (!window.Alpine.data(componentName)) {
        const module = await import(`./components/${componentName}.js`);
        window.Alpine.data(componentName, module.default);
    }
};
```

---

## 🔍 Database Optimizations

### 1. Materialized Views

For frequently accessed aggregated data:

```sql
-- Create materialized view for campaign summary
CREATE MATERIALIZED VIEW cmis_analytics.campaign_summary AS
SELECT
    c.campaign_id,
    c.name,
    c.org_id,
    COUNT(m.metric_id) as metric_count,
    SUM(m.impressions) as total_impressions,
    SUM(m.clicks) as total_clicks,
    SUM(m.spend) as total_spend
FROM cmis.campaigns c
LEFT JOIN cmis_analytics.metrics m ON c.campaign_id = m.campaign_id
GROUP BY c.campaign_id, c.name, c.org_id;

-- Create index on materialized view
CREATE INDEX idx_campaign_summary_org ON cmis_analytics.campaign_summary(org_id);

-- Refresh strategy
-- Option 1: Manual refresh after data updates
REFRESH MATERIALIZED VIEW cmis_analytics.campaign_summary;

-- Option 2: Scheduled refresh (via Laravel scheduler)
// In app/Console/Kernel.php
$schedule->call(function () {
    DB::statement('REFRESH MATERIALIZED VIEW cmis_analytics.campaign_summary');
})->hourly();
```

### 2. Partitioning Large Tables

For metrics tables with time-series data:

```sql
-- Partition by month
CREATE TABLE cmis_analytics.metrics (
    metric_id UUID PRIMARY KEY,
    campaign_id UUID NOT NULL,
    date DATE NOT NULL,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    -- other columns
) PARTITION BY RANGE (date);

-- Create partitions
CREATE TABLE cmis_analytics.metrics_2025_01
    PARTITION OF cmis_analytics.metrics
    FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');

CREATE TABLE cmis_analytics.metrics_2025_02
    PARTITION OF cmis_analytics.metrics
    FOR VALUES FROM ('2025-02-01') TO ('2025-03-01');

-- Queries automatically use correct partition
SELECT * FROM cmis_analytics.metrics
WHERE date BETWEEN '2025-01-15' AND '2025-01-31';
-- Only scans metrics_2025_01 partition
```

### 3. Query Optimization Tips

```sql
-- Use EXPLAIN ANALYZE to identify slow queries
EXPLAIN ANALYZE
SELECT * FROM cmis.campaigns
WHERE org_id = 'uuid' AND status = 'active';

-- Optimize based on results:
-- - Add indexes on filtered columns
-- - Rewrite complex joins
-- - Use CTEs for readability and optimization
-- - Limit result sets

-- Example optimization
-- BEFORE: Nested subqueries
SELECT * FROM campaigns
WHERE campaign_id IN (
    SELECT campaign_id FROM metrics
    WHERE spend > (SELECT AVG(spend) FROM metrics)
);

-- AFTER: CTE (Common Table Expression)
WITH avg_spend AS (
    SELECT AVG(spend) as avg FROM metrics
)
SELECT c.* FROM campaigns c
JOIN metrics m ON c.campaign_id = m.campaign_id
CROSS JOIN avg_spend
WHERE m.spend > avg_spend.avg;
```

---

## 📦 Asset Optimization

### 1. CDN Usage

```html
<!-- Use CDN for external libraries -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.js"></script>

<!-- Benefits: -->
<!-- - Faster load times (CDN edge servers) -->
<!-- - Browser caching across sites -->
<!-- - Reduced server load -->
```

### 2. Vite Build Optimization

```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                    'chart': ['chart.js'],
                    'vendor': ['axios']
                }
            }
        },
        chunkSizeWarningLimit: 1000,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true // Remove console.logs in production
            }
        }
    }
});
```

---

## 🔬 Monitoring & Profiling

### 1. Laravel Telescope

Install for development:
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Monitor:
- Slow database queries
- API request/response times
- Cache hit/miss ratios
- Queue job performance

### 2. Application Performance Monitoring (APM)

Consider using:
- **Laravel Debugbar** (development)
- **New Relic** (production)
- **Scout APM** (production)
- **Blackfire.io** (profiling)

### 3. Custom Performance Logging

```php
// Log slow queries
DB::listen(function ($query) {
    if ($query->time > 1000) { // > 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms'
        ]);
    }
});

// Log API response times
$startTime = microtime(true);
// ... API logic ...
$endTime = microtime(true);
$responseTime = ($endTime - $startTime) * 1000;

if ($responseTime > 2000) {
    Log::warning('Slow API response', [
        'endpoint' => $request->path(),
        'time' => $responseTime . 'ms'
    ]);
}
```

---

## ✅ Performance Checklist

### Backend
- [ ] Database indexes on frequently queried columns
- [ ] Eager loading for relationships (prevent N+1)
- [ ] Query result caching (appropriate TTL)
- [ ] Pagination for large result sets
- [ ] API response caching
- [ ] Background jobs for heavy operations
- [ ] Materialized views for complex aggregations

### Frontend
- [ ] Configurable auto-refresh intervals
- [ ] Silent background updates
- [ ] Chart.js instance cleanup
- [ ] Defer chart rendering until visible
- [ ] Lazy load components when possible
- [ ] Pause refresh on hidden tabs
- [ ] Minimize DOM manipulations

### Database
- [ ] Regular VACUUM ANALYZE on PostgreSQL
- [ ] Monitor table bloat
- [ ] Optimize slow queries (EXPLAIN ANALYZE)
- [ ] Consider partitioning for large tables
- [ ] Review and optimize RLS policies

### Infrastructure
- [ ] Enable Redis caching
- [ ] Configure OPcache for PHP
- [ ] Use CDN for static assets
- [ ] Enable GZIP compression
- [ ] Optimize server resources

---

## 🎯 Continuous Optimization

1. **Monitor**: Track performance metrics regularly
2. **Identify**: Find bottlenecks using profiling tools
3. **Optimize**: Apply appropriate optimization techniques
4. **Test**: Verify improvements with load testing
5. **Document**: Keep this guide updated with new optimizations

---

**Last Updated**: 2025-11-21
**Phase**: 10
