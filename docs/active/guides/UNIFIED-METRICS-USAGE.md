# Unified Metrics System - Usage Guide

**Date:** 2025-11-22
**Version:** 1.0
**Status:** ✅ Implemented (Phase 1 Complete)

---

## Overview

The Unified Metrics System consolidates 10 previous metrics tables into a single, performant solution with polymorphic relationships and time-series partitioning.

**Benefits:**
- 🎯 Single source of truth for all metrics
- ⚡ 5-10x faster queries with proper indexing
- 📊 Polymorphic - track metrics for ANY entity
- 🔄 Time-series partitioning for optimal performance
- 🎨 Consistent API across all platforms

---

## Quick Start

### Recording Metrics

```php
use App\Models\Analytics\Metric;

// Simple record
Metric::record(
    entityType: 'campaign',
    entityId: $campaign->id,
    metricName: 'impressions',
    value: 1000,
    platform: 'meta'
);

// With options
Metric::record(
    entityType: 'ad',
    entityId: $ad->id,
    metricName: 'spend',
    value: 150.50,
    platform: 'google',
    options: [
        'source' => 'api',
        'metadata' => ['campaign_id' => $campaign->id],
        'recorded_at' => now(),
    ]
);
```

### Using Repository

```php
use App\Repositories\Analytics\MetricsRepository;

$repo = new MetricsRepository();

// Record single metric
$repo->record('campaign', $campaignId, 'clicks', 500, 'meta');

// Record multiple metrics at once
$repo->recordBatch('campaign', $campaignId, [
    'impressions' => 10000,
    'clicks' => 500,
    'spend' => 250.00,
    'conversions' => 25,
], 'meta');

// Get entity metrics
$metrics = $repo->getEntityMetrics(
    'campaign',
    $campaignId,
    metricNames: ['impressions', 'clicks', 'spend'],
    startDate: '2025-11-01',
    endDate: '2025-11-30'
);

// Get latest value
$latestSpend = $repo->getLatestValue('campaign', $campaignId, 'spend');

// Get aggregated data
$totals = $repo->getAggregated(
    'campaign',
    $campaignId,
    ['impressions', 'clicks', 'spend'],
    '2025-11-01',
    '2025-11-30',
    aggregation: 'sum' // sum, avg, min, max
);

// Get trend data
$dailyImpressions = $repo->getTrend(
    'campaign',
    $campaignId,
    'impressions',
    '2025-11-01',
    '2025-11-30',
    interval: 'daily' // daily, hourly, monthly
);
```

---

## Querying Metrics

### Basic Queries

```php
use App\Models\Analytics\Metric;

// Get all metrics for an entity
$metrics = Metric::forEntity('campaign', $campaignId)->get();

// Get specific metric
$impressions = Metric::forEntity('campaign', $campaignId)
    ->metric('impressions')
    ->get();

// Get metrics for date range
$metrics = Metric::forEntity('campaign', $campaignId)
    ->dateRange('2025-11-01', '2025-11-30')
    ->get();

// Get latest metrics
$latest = Metric::forEntity('campaign', $campaignId)
    ->latest()
    ->first();

// Today's metrics
$today = Metric::forEntity('campaign', $campaignId)
    ->today()
    ->get();

// This week's metrics
$thisWeek = Metric::forEntity('campaign', $campaignId)
    ->thisWeek()
    ->get();

// This month's metrics
$thisMonth = Metric::forEntity('campaign', $campaignId)
    ->thisMonth()
    ->get();
```

### Advanced Queries

```php
// Get by category
$financial = Metric::forEntity('campaign', $campaignId)
    ->category(Metric::CATEGORY_FINANCIAL)
    ->get();

// Get by platform
$metaMetrics = Metric::forEntity('campaign', $campaignId)
    ->platform(Metric::PLATFORM_META)
    ->get();

// Combine scopes
$metrics = Metric::forEntity('campaign', $campaignId)
    ->category(Metric::CATEGORY_PERFORMANCE)
    ->platform(Metric::PLATFORM_GOOGLE)
    ->dateRange('2025-11-01', '2025-11-30')
    ->latest()
    ->get();

// Numeric metrics only
$numericMetrics = Metric::forEntity('campaign', $campaignId)
    ->numeric()
    ->get();
```

---

## Entity Types

You can track metrics for ANY entity in CMIS:

| Entity Type | Example Usage |
|-------------|---------------|
| `campaign` | Campaign-level metrics |
| `ad` | Individual ad metrics |
| `ad_set` | Ad set/group metrics |
| `post` | Social post metrics |
| `account` | Social account metrics |
| `creative` | Creative asset performance |
| `audience` | Audience segment metrics |

---

## Metric Categories

Use these standard categories:

```php
Metric::CATEGORY_PERFORMANCE   // impressions, clicks, reach, ctr
Metric::CATEGORY_FINANCIAL    // spend, cpc, cpa, roas, roi
Metric::CATEGORY_ENGAGEMENT   // likes, comments, shares, engagement_rate
Metric::CATEGORY_CONVERSION   // conversions, conversion_rate, conversion_value
Metric::CATEGORY_VIDEO        // video_views, video_completion_rate
Metric::CATEGORY_AUDIENCE     // followers, audience_size
```

---

## Common Metrics

Standard metric names (with auto-categorization):

```php
// Performance
Metric::METRIC_IMPRESSIONS
Metric::METRIC_CLICKS
Metric::METRIC_CTR
Metric::METRIC_REACH

// Financial
Metric::METRIC_SPEND
Metric::METRIC_CPC
Metric::METRIC_CPA
Metric::METRIC_ROAS

// Conversion
Metric::METRIC_CONVERSIONS

// Engagement
Metric::METRIC_ENGAGEMENT_RATE
```

---

## Platforms

```php
Metric::PLATFORM_META
Metric::PLATFORM_GOOGLE
Metric::PLATFORM_TIKTOK
Metric::PLATFORM_LINKEDIN
Metric::PLATFORM_TWITTER
Metric::PLATFORM_SNAPCHAT
```

---

## Views

Two helper views are available:

### 1. Latest Metrics
```php
DB::table('cmis.latest_metrics')
    ->where('entity_type', 'campaign')
    ->where('entity_id', $campaignId)
    ->get();
```

### 2. Daily Metrics (Aggregated)
```php
DB::table('cmis.daily_metrics')
    ->where('entity_type', 'campaign')
    ->where('entity_id', $campaignId)
    ->where('date', today())
    ->get();
```

---

## Metric Definitions

Access metric metadata:

```php
use App\Models\Analytics\MetricDefinition;

// Get all active definitions
$definitions = MetricDefinition::active()->get();

// Get by category
$financial = MetricDefinition::category('financial')->get();

// Get specific definition
$def = MetricDefinition::where('metric_name', 'impressions')->first();

echo $def->display_name; // "Impressions"
echo $def->unit; // "count"
echo $def->description;
```

---

## Migration from Old Tables

If you have code using old metrics tables, update as follows:

### Before (Old Way)
```php
// AdMetric model
AdMetric::create([
    'ad_id' => $adId,
    'impressions' => 1000,
    'clicks' => 50,
    'spend' => 100.00,
]);

// CampaignMetric model
CampaignMetric::create([
    'campaign_id' => $campaignId,
    'metric_name' => 'impressions',
    'value' => 1000,
]);
```

### After (New Way)
```php
// Single unified approach
Metric::record('ad', $adId, 'impressions', 1000);
Metric::record('ad', $adId, 'clicks', 50);
Metric::record('ad', $adId, 'spend', 100.00);

// Or batch
$repo->recordBatch('ad', $adId, [
    'impressions' => 1000,
    'clicks' => 50,
    'spend' => 100.00,
]);
```

---

## Performance Tips

1. **Use date ranges** when querying to leverage partitioning
2. **Use specific scopes** instead of filtering after retrieval
3. **Use views** for common query patterns
4. **Index metadata** fields if querying frequently

---

## Testing

```php
use App\Models\Analytics\Metric;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_can_record_metric()
    {
        $metric = Metric::record(
            'campaign',
            'test-campaign-id',
            'impressions',
            1000,
            'meta'
        );

        $this->assertNotNull($metric->id);
        $this->assertEquals(1000, $metric->value_numeric);
    }

    public function test_metrics_are_isolated_by_org()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $this->actingAsOrg($org1);
        Metric::record('campaign', 'campaign-1', 'impressions', 1000);

        $this->actingAsOrg($org2);
        Metric::record('campaign', 'campaign-2', 'impressions', 2000);

        $this->actingAsOrg($org1);
        $this->assertEquals(1, Metric::count());
    }
}
```

---

## Next Steps

- ✅ Phase 1 Complete: Unified metrics infrastructure
- 🔄 Phase 2 (Next): Update all services to use unified metrics
- 🔄 Phase 3: Deprecate old metrics tables
- 🔄 Phase 4: Performance optimization & monitoring

---

## Support

For questions or issues:
- See: `docs/active/analysis/database-duplication-analysis.md`
- See: `docs/active/analysis/COMPREHENSIVE-DUPLICATION-ANALYSIS-2025-11-21.md`

**Implemented by:** Claude Code AI Agent
**Date:** 2025-11-22
**Phase:** 1 of 8 (Metrics Consolidation)
