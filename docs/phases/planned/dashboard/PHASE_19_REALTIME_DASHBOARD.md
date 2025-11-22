# Phase 19: Real-Time Analytics Dashboard & Reporting Hub

**Implementation Date:** 2025-11-21
**Status:** ✅ Foundation Complete
**Dependencies:** Phases 0-18

---

## Overview

Phase 19 creates a comprehensive real-time analytics dashboard and reporting hub that unifies all CMIS capabilities into actionable insights. It leverages data from predictive analytics (Phase 16), automation rules (Phase 17), and platform integrations (Phase 18) to provide a complete view of marketing performance.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│         Real-Time Analytics Dashboard & Reporting Hub        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌────────────────┐  ┌───────────────┐  │
│  │  Dashboard   │  │    Widget      │  │   Real-Time   │  │
│  │  Templates   │  │    Engine      │  │  Metrics Cache│  │
│  └──────────────┘  └────────────────┘  └───────────────┘  │
│          │                 │                    │          │
│          └─────────────────┴────────────────────┘          │
│                           │                                │
│                  ┌────────▼────────┐                       │
│                  │ Data Aggregator  │                      │
│                  └─────────────────┘                       │
│                           │                                │
├───────────────────────────┴────────────────────────────────┤
│                  Data Sources                               │
├─────────────────────────────────────────────────────────────┤
│ Phase 16  │ Phase 17  │ Phase 18  │ Campaigns │ Analytics │
│ Forecasts │ Automation│ Platforms │  Data     │   Data    │
│ Anomalies │   Rules   │ Connections│          │           │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema (6 Tables)

### 1. dashboard_templates
Pre-configured dashboard layouts
- Global and org-specific templates
- Grid layout configuration
- Widget composition
- Default filters and refresh intervals
- Usage tracking

### 2. dashboard_widgets
Configurable widget instances
- Widget types: KPI cards, charts, tables, maps
- Data sources: All Phase 8-18 data
- Position and size configuration
- Auto-refresh capabilities
- Real-time data binding

### 3. dashboard_snapshots
Historical dashboard states
- Point-in-time data capture
- Scheduled and manual snapshots
- Multiple export formats (JSON, PDF, Excel)
- Comparison capabilities

### 4. report_schedules
Automated report distribution
- Daily, weekly, monthly, quarterly schedules
- Multi-recipient support
- Format selection (PDF, Excel, CSV)
- Timezone-aware scheduling

### 5. dashboard_alerts
Proactive monitoring and notifications
- Threshold-based alerts
- Anomaly detection integration (Phase 16)
- Multi-channel notifications (Email, Slack, webhook)
- Alert history tracking

### 6. realtime_metrics_cache
Performance-optimized metric storage
- Real-time, hourly, daily, weekly aggregations
- Entity-specific caching (campaigns, accounts, platforms)
- TTL-based expiration
- Fast dashboard rendering

All tables include RLS policies for multi-tenancy.

---

## Dashboard Template Categories

### 1. Executive Dashboard
**Purpose:** C-level overview of marketing performance

**Widgets:**
- Total Revenue (KPI card)
- ROAS Trend (line chart)
- Top Performing Campaigns (table)
- Budget Utilization (gauge chart)
- Platform Performance Comparison (bar chart)
- Forecasted Revenue (Phase 16 forecast chart)
- Active Automation Rules (Phase 17 status)
- Platform Connection Health (Phase 18 status)

**Refresh:** Every 15 minutes

### 2. Performance Dashboard
**Purpose:** Detailed campaign performance analysis

**Widgets:**
- Campaign Performance Table (sortable, filterable)
- Conversion Rate Trend (line chart)
- CPA by Campaign (bar chart)
- Geographic Performance (map)
- Hour-of-Day Analysis (heatmap)
- Anomaly Alerts (Phase 16 integration)
- Budget Pacing (gauge chart)

**Refresh:** Every 5 minutes

### 3. Automation Dashboard
**Purpose:** Monitor automation rule performance

**Widgets:**
- Active Rules Count (KPI card)
- Rule Execution History (timeline)
- Success Rate by Rule (bar chart)
- Recent Automation Actions (table)
- Budget Adjustments Tracking (line chart)
- Paused Campaigns (list)
- Automation Impact on ROI (comparison chart)

**Refresh:** Real-time

### 4. Platform Health Dashboard
**Purpose:** Monitor platform integration status

**Widgets:**
- Connection Status (status cards)
- API Call Volume (line chart)
- Rate Limit Usage (gauge charts)
- Sync Status (timeline)
- Error Log (table)
- Token Expiration Warnings (alert list)
- Platform Performance Comparison (bar chart)

**Refresh:** Every 1 minute

### 5. Forecasting Dashboard
**Purpose:** Predictive analytics and trend analysis

**Widgets:**
- 30-Day Revenue Forecast (line chart with confidence intervals)
- Detected Anomalies (alert cards)
- Trend Analysis (multi-line chart)
- Recommendation Queue (actionable list)
- Forecast Accuracy (gauge chart)
- Budget Optimization Suggestions (cards)

**Refresh:** Every 1 hour (forecasts updated daily)

---

## Widget Types

### KPI Cards
- Single metric display
- Trend indicator (up/down/neutral)
- Comparison to previous period
- Sparkline visualization
- Color-coded status

**Example:**
```json
{
  "widget_type": "kpi_card",
  "data_source": "campaigns",
  "config": {
    "metric": "total_revenue",
    "comparison_period": "previous_30_days",
    "show_sparkline": true,
    "goal": 100000,
    "format": "currency"
  }
}
```

### Line Charts
- Time-series data
- Multiple series support
- Forecast integration (Phase 16)
- Drill-down capabilities

### Bar Charts
- Comparison across entities
- Horizontal and vertical
- Stacked variants
- Top N filtering

### Pie/Donut Charts
- Distribution visualization
- Percentage display
- Interactive legends

### Tables
- Sortable columns
- Filterable rows
- Pagination
- Export to CSV/Excel
- Inline actions (pause, edit, etc.)

### Maps
- Geographic performance
- Heatmap visualization
- Click-through to details

### Gauges
- Progress indicators
- Threshold markers
- Budget pacing
- Rate limit usage

---

## Data Sources

### Phase 16 Integration (Predictive Analytics)
```json
{
  "data_source": "forecasts",
  "query_params": {
    "entity_type": "campaign",
    "metric": "revenue",
    "days": 30,
    "forecast_type": "linear_regression"
  }
}
```

**Available Metrics:**
- Forecasts (all forecast types)
- Detected Anomalies (severity filtered)
- Trend Analyses (trend type filtered)
- Recommendations (priority filtered)

### Phase 17 Integration (Automation)
```json
{
  "data_source": "automation_rules",
  "query_params": {
    "status": "active",
    "rule_type": "campaign_performance"
  }
}
```

**Available Metrics:**
- Active/Paused Rules Count
- Execution Success Rate
- Recent Executions
- Actions Taken (pause, budget adjust, etc.)

### Phase 18 Integration (Platform Connections)
```json
{
  "data_source": "platform_connections",
  "query_params": {
    "platform": "meta",
    "status": "active"
  }
}
```

**Available Metrics:**
- Connection Status
- Sync Health
- API Call Volume
- Rate Limit Usage
- Error Rates

### Campaign Data
- Revenue, Spend, Conversions
- CTR, CPC, CPA, ROAS
- Impressions, Clicks
- Budget Utilization

---

## Real-Time Metrics Cache

### Purpose
Optimize dashboard performance by caching frequently accessed metrics.

### Aggregation Periods
- **Realtime:** Updated every minute
- **Hourly:** Updated every hour
- **Daily:** Updated daily at midnight
- **Weekly:** Updated weekly on Sunday

### Cache Structure
```json
{
  "metric_key": "org_123_revenue_realtime",
  "metric_type": "revenue",
  "metric_data": {
    "current": 45230.50,
    "previous_period": 42100.00,
    "change_percent": 7.4,
    "trend": "up"
  },
  "aggregation_period": "realtime",
  "cached_at": "2025-11-21T10:30:00Z",
  "expires_at": "2025-11-21T10:35:00Z"
}
```

### Cache Invalidation
- TTL-based expiration
- Event-driven invalidation (campaign updates)
- Manual refresh capability

---

## Dashboard Alerts

### Alert Types

**1. Threshold Alerts**
```json
{
  "alert_type": "threshold",
  "condition": {
    "metric": "cpa",
    "operator": ">",
    "threshold": 50,
    "entity_type": "campaign"
  },
  "notification_config": {
    "channels": ["email", "slack"],
    "recipients": ["campaign-manager@example.com"],
    "slack_webhook": "https://hooks.slack.com/..."
  }
}
```

**2. Anomaly Alerts**
Integration with Phase 16 anomaly detection:
```json
{
  "alert_type": "anomaly",
  "condition": {
    "severity": ["high", "critical"],
    "metric": "conversions"
  }
}
```

**3. Change Alerts**
```json
{
  "alert_type": "change",
  "condition": {
    "metric": "revenue",
    "change_percent": -20,
    "period": "24h"
  }
}
```

**4. Forecast Alerts**
Integration with Phase 16 forecasts:
```json
{
  "alert_type": "forecast",
  "condition": {
    "metric": "budget_depletion",
    "forecast_days": 7,
    "threshold": 90
  }
}
```

---

## Report Scheduling

### Schedule Configuration
```json
{
  "frequency": "weekly",
  "schedule_config": {
    "day_of_week": 1, // Monday
    "time": "09:00",
    "timezone": "America/New_York"
  },
  "recipients": [
    "executive@example.com",
    "marketing@example.com"
  ],
  "format": "pdf"
}
```

### Report Formats
- **PDF:** Executive summaries, charts, tables
- **Excel:** Raw data, pivot-ready
- **CSV:** Data export for analysis

### Report Content
- Dashboard snapshot at scheduled time
- Summary statistics
- Top performers/underperformers
- Anomaly highlights
- Automation actions taken
- Recommendations from Phase 16

---

## Dashboard Snapshots

### Use Cases
1. **Historical Comparison:** Compare current vs. past performance
2. **Audit Trail:** Record of dashboard state at specific times
3. **Reporting:** Generate reports from snapshots
4. **Backup:** Preserve data for compliance

### Snapshot Data
```json
{
  "snapshot_type": "manual",
  "snapshot_date": "2025-11-21T00:00:00Z",
  "data": {
    "kpis": {
      "total_revenue": 125430.50,
      "total_spend": 45230.00,
      "roas": 2.77,
      "conversions": 1543
    },
    "widgets": [
      {
        "widget_id": "uuid",
        "widget_type": "line_chart",
        "data": [...]
      }
    ]
  },
  "metadata": {
    "filters": {
      "date_range": "last_30_days",
      "platforms": ["meta", "google"]
    }
  }
}
```

---

## Integration Examples

### Executive Dashboard Template
```json
{
  "name": "Executive Overview",
  "category": "executive",
  "layout_config": {
    "columns": 12,
    "row_height": 60
  },
  "widgets": [
    {
      "type": "kpi_card",
      "title": "Total Revenue",
      "data_source": "campaigns",
      "query_params": {"metric": "revenue", "period": "30d"},
      "position_x": 0,
      "position_y": 0,
      "width": 3,
      "height": 2
    },
    {
      "type": "line_chart",
      "title": "Revenue Forecast (30 Days)",
      "data_source": "forecasts",
      "query_params": {
        "metric": "revenue",
        "forecast_type": "linear_regression",
        "days": 30
      },
      "position_x": 3,
      "position_y": 0,
      "width": 9,
      "height": 4
    },
    {
      "type": "table",
      "title": "Active Automation Rules",
      "data_source": "automation_rules",
      "query_params": {"status": "active"},
      "position_x": 0,
      "position_y": 4,
      "width": 6,
      "height": 4
    },
    {
      "type": "kpi_card",
      "title": "Critical Anomalies",
      "data_source": "anomalies",
      "query_params": {"severity": "critical", "status": "new"},
      "position_x": 6,
      "position_y": 4,
      "width": 3,
      "height": 2
    }
  ]
}
```

---

## Performance Optimization

### Caching Strategy
- **Level 1:** Browser cache (5 minutes)
- **Level 2:** Redis cache (15 minutes)
- **Level 3:** Database cache table (realtime_metrics_cache)

### Query Optimization
- Pre-aggregated metrics
- Materialized views for complex queries
- Index optimization on time-series data

### Real-Time Updates
- WebSocket connections for live data
- Server-Sent Events (SSE) for metric streams
- Polling fallback for compatibility

---

## Security & Compliance

### Access Control
- Dashboard templates: Org-level permissions
- Widgets: User-owned, shareable
- Snapshots: Audit-logged
- Reports: Recipient validation

### Data Privacy
- RLS enforcement on all queries
- Encrypted snapshots
- Audit trail for data access
- GDPR-compliant data retention

---

## Future Enhancements (Phase 20+)

1. **AI-Powered Insights**
   - Automatic insight generation
   - Natural language summaries
   - Predictive recommendations

2. **Custom Widget Builder**
   - Drag-and-drop interface
   - No-code metric composition
   - Custom visualizations

3. **Collaborative Dashboards**
   - Real-time co-viewing
   - Annotations and comments
   - Shared analysis sessions

4. **Mobile Dashboards**
   - Responsive design
   - Mobile-optimized widgets
   - Push notifications

5. **Advanced Analytics**
   - Cohort analysis
   - Funnel visualization
   - Attribution modeling

---

## Files Created

**Migration:**
- `database/migrations/2025_11_21_000008_create_dashboard_tables.php`

**Future:**
- `app/Models/Dashboard/DashboardTemplate.php`
- `app/Models/Dashboard/DashboardWidget.php`
- `app/Models/Dashboard/DashboardSnapshot.php`
- `app/Models/Dashboard/ReportSchedule.php`
- `app/Models/Dashboard/DashboardAlert.php`
- `app/Models/Dashboard/RealtimeMetricsCache.php`
- `app/Services/Dashboard/DashboardService.php`
- `app/Services/Dashboard/WidgetRenderingEngine.php`
- `app/Services/Dashboard/MetricsAggregator.php`
- `app/Http/Controllers/DashboardController.php`
- `resources/js/components/dashboardHub.js`

---

## Usage Example

### Create Custom Dashboard
```php
$template = DashboardTemplate::create([
    'org_id' => $orgId,
    'name' => 'My Custom Dashboard',
    'category' => 'performance',
    'layout_config' => ['columns' => 12, 'row_height' => 60],
    'widgets' => [
        [
            'type' => 'kpi_card',
            'title' => 'Revenue',
            'data_source' => 'campaigns',
            'query_params' => ['metric' => 'revenue']
        ]
    ],
    'refresh_interval' => '5m'
]);
```

### Schedule Report
```php
$schedule = ReportSchedule::create([
    'org_id' => $orgId,
    'dashboard_id' => $dashboardId,
    'name' => 'Weekly Performance Report',
    'frequency' => 'weekly',
    'schedule_config' => [
        'day_of_week' => 1,
        'time' => '09:00',
        'timezone' => 'America/New_York'
    ],
    'recipients' => ['exec@example.com'],
    'format' => 'pdf'
]);
```

### Create Alert
```php
$alert = DashboardAlert::create([
    'org_id' => $orgId,
    'name' => 'High CPA Alert',
    'alert_type' => 'threshold',
    'condition' => [
        'metric' => 'cpa',
        'operator' => '>',
        'threshold' => 50
    ],
    'notification_config' => [
        'channels' => ['email', 'slack'],
        'recipients' => ['manager@example.com']
    ]
]);
```

---

**Document Version:** 1.0 (Foundation)
**Last Updated:** 2025-11-21
**Status:** Foundation Complete - Unified Analytics Hub ✅
