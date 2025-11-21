# CMIS Enterprise Analytics System Documentation

**Version:** 1.0
**Last Updated:** 2025-11-21
**Phases:** 5-10 (Complete)

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Components](#components)
4. [API Endpoints](#api-endpoints)
5. [Frontend Integration](#frontend-integration)
6. [Security & Multi-Tenancy](#security--multi-tenancy)
7. [Performance](#performance)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)

---

## Overview

The CMIS Enterprise Analytics System provides comprehensive real-time and historical performance insights for marketing campaigns. Built across Phases 5-10, it combines:

- **Backend APIs** (Phase 5-7): Real-time analytics, ROI calculation, attribution modeling, KPI tracking
- **Frontend Components** (Phase 8): Alpine.js reactive dashboards with Chart.js visualizations
- **Laravel Integration** (Phase 9): Blade views, routes, and controllers
- **Testing & Documentation** (Phase 10): Comprehensive test coverage and guides

### Key Features

✅ **Real-Time Analytics**: Auto-refreshing metrics with 1m, 5m, 15m, 1h windows
✅ **ROI Analysis**: Financial metrics, profitability status, ROAS calculation
✅ **Attribution Modeling**: 6 models (last-click, first-click, linear, time-decay, position-based, data-driven)
✅ **KPI Monitoring**: Health scores, status indicators, gap-to-target analysis
✅ **Lifetime Value**: Customer value calculation, LTV/CAC ratio, payback period
✅ **Projections**: 30-day forecasts with confidence levels
✅ **Enterprise Alerts**: Real-time notifications with acknowledgment/resolution workflows

---

## Architecture

### System Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Real-Time    │  │ Campaign     │  │ KPI          │     │
│  │ Dashboard    │  │ Analytics    │  │ Dashboard    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              Laravel Controllers & Routes Layer              │
│  ┌────────────────────────────────────────────────────┐    │
│  │    EnterpriseAnalyticsController                   │    │
│  │    - enterprise()  - realtime()  - campaign()      │    │
│  │    - campaigns()   - kpis()                        │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                 Alpine.js Components Layer                   │
│  ┌────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │ realtimeDash   │  │ campaignAnalytics│  │ kpiDashboard│ │
│  │ board          │  │                  │  │             │ │
│  └────────────────┘  └─────────────────┘  └─────────────┘ │
│  ┌────────────────┐                                         │
│  │ notification   │                                         │
│  │ Center         │                                         │
│  └────────────────┘                                         │
└─────────────────────────────────────────────────────────────┘
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                      API Layer (Phase 5-7)                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Real-Time Analytics  │  ROI Calculation            │  │
│  │  Attribution Modeling │  KPI Tracking               │  │
│  │  Enterprise Alerts    │  Performance Profiling      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              Data Layer (PostgreSQL + RLS)                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ cmis.*       │  │ cmis_        │  │ cmis_ai.*    │     │
│  │ (core)       │  │ enterprise.* │  │ (embeddings) │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Backend** | Laravel | 10.x | API routes, controllers, middleware |
| **Database** | PostgreSQL | 15+ | Multi-tenant data storage with RLS |
| **Frontend Framework** | Alpine.js | 3.x | Reactive UI components |
| **Visualizations** | Chart.js | 4.x | Interactive charts and graphs |
| **Styling** | Tailwind CSS | 2.x | Utility-first CSS framework |
| **Icons** | Font Awesome | 6.4 | Icon library |
| **Authentication** | Laravel Sanctum | 3.x | API token authentication |
| **Testing** | PHPUnit | 10.x | Unit and feature tests |

---

## Components

### 1. Real-Time Dashboard (`realtimeDashboard.js`)

**Purpose**: Live performance metrics with auto-refresh

**Features**:
- Time window selector (1m, 5m, 15m, 1h)
- Auto-refresh toggle (default: 30 seconds)
- Organization-wide metric aggregation
- Campaign-level breakdown
- Derived metrics (CTR, CPC, conversion rate)
- Chart.js bar chart for campaign comparison

**API Dependencies**:
```
GET /api/orgs/{org_id}/analytics/realtime/dashboard?window={window}
```

**Usage**:
```html
<div x-data="realtimeDashboard()"
     data-org-id="{{ $orgId }}"
     x-init="init()">
    <!-- Component renders metrics -->
</div>
```

### 2. Campaign Analytics (`campaignAnalytics.js`)

**Purpose**: Comprehensive campaign performance analysis

**Features**:
- **Overview Tab**: Spend, revenue, profit, ROI summary
- **ROI Analysis Tab**: Doughnut chart + financial metrics
- **Attribution Tab**: Channel contribution with 6 models
- **LTV Tab**: Customer lifetime value and acquisition cost
- **Projection Tab**: 30-day forecast with confidence levels

**API Dependencies**:
```
POST /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}
POST /api/orgs/{org_id}/analytics/attribution/campaigns/{campaign_id}/insights
GET  /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}/ltv
POST /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}/project
```

**Usage**:
```html
<div x-data="campaignAnalytics()"
     data-org-id="{{ $orgId }}"
     data-campaign-id="{{ $campaignId }}"
     x-init="init()">
    <!-- Component renders tabs -->
</div>
```

### 3. KPI Dashboard (`kpiDashboard.js`)

**Purpose**: Performance indicator monitoring with health scores

**Features**:
- Organization or entity-level KPI tracking
- Health score (0-100) with color-coded labels
- Status indicators: exceeded, on_track, at_risk, off_track
- Progress bars with gap-to-target
- Auto-refresh (default: 60 seconds)
- Status distribution overview

**API Dependencies**:
```
GET /api/orgs/{org_id}/analytics/kpis/dashboard?entity_type={type}&entity_id={id}
```

**Usage**:
```html
<div x-data="kpiDashboard()"
     data-org-id="{{ $orgId }}"
     data-entity-type="campaign"
     data-entity-id="{{ $campaignId }}"
     x-html="render()">
    <!-- Component renders own HTML -->
</div>
```

### 4. Notification Center (`notificationCenter.js`)

**Purpose**: Real-time alert management

**Features**:
- Auto-polling (default: 30 seconds)
- Severity filtering (critical, high, medium, low)
- Alert acknowledgment and resolution
- Browser notifications (requires permission)
- Unread count badge
- Anomaly alert integration via events

**API Dependencies**:
```
GET  /api/orgs/{org_id}/enterprise/alerts?status={status}&severity={severity}
POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/acknowledge
POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/resolve
```

**Usage**:
```html
<div x-data="notificationCenter()"
     data-org-id="{{ $orgId }}"
     x-init="init()">
    <!-- Bell icon with badge -->
    <button @click="toggle()">
        <i class="fas fa-bell"></i>
        <span x-show="unreadCount > 0" x-text="unreadCount"></span>
    </button>
    <!-- Dropdown panel -->
</div>
```

---

## API Endpoints

### Real-Time Analytics

#### Get Real-Time Dashboard
```http
GET /api/orgs/{org_id}/analytics/realtime/dashboard?window={window}
```

**Parameters**:
- `org_id` (UUID): Organization identifier
- `window` (string): Time window - `1m`, `5m`, `15m`, `1h`

**Response**:
```json
{
  "success": true,
  "campaigns": [
    {
      "campaign_id": "uuid",
      "campaign_name": "string",
      "impressions": 1000,
      "clicks": 50,
      "conversions": 5,
      "spend": 100.00,
      "ctr": 5.0
    }
  ],
  "totals": {
    "impressions": 5000,
    "clicks": 250,
    "conversions": 25,
    "spend": 500.00
  },
  "derived_metrics": {
    "ctr": 5.0,
    "cpc": 2.0,
    "conversion_rate": 10.0
  }
}
```

### ROI & Financial Analytics

#### Calculate Campaign ROI
```http
POST /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}
```

**Body**:
```json
{
  "date_range": {
    "start": "2025-01-01",
    "end": "2025-01-31"
  }
}
```

**Response**:
```json
{
  "success": true,
  "financial_metrics": {
    "total_spend": 10000.00,
    "total_revenue": 15000.00,
    "profit": 5000.00,
    "roi_percentage": 50.0,
    "roas": 1.5
  },
  "profitability": {
    "status": "profitable",
    "message": "Campaign is generating positive returns",
    "gross_margin": 33.33,
    "net_margin": 25.0,
    "break_even_point": 6666.67
  }
}
```

### Attribution Modeling

#### Get Attribution Insights
```http
POST /api/orgs/{org_id}/analytics/attribution/campaigns/{campaign_id}/insights
```

**Body**:
```json
{
  "model": "linear",
  "date_range": {
    "start": "2025-01-01",
    "end": "2025-01-31"
  }
}
```

**Models**: `last-click`, `first-click`, `linear`, `time-decay`, `position-based`, `data-driven`

**Response**:
```json
{
  "success": true,
  "model": "linear",
  "insights": [
    {
      "channel": "Google Ads",
      "touchpoints": 500,
      "contribution_percentage": 40.0,
      "attributed_conversions": 20.0
    }
  ]
}
```

### KPI Tracking

#### Get KPI Dashboard
```http
GET /api/orgs/{org_id}/analytics/kpis/dashboard?entity_type={type}&entity_id={id}
```

**Parameters**:
- `entity_type` (string): `org`, `campaign`, `channel`
- `entity_id` (UUID): Entity identifier

**Response**:
```json
{
  "success": true,
  "kpis": [
    {
      "kpi_id": "uuid",
      "kpi_name": "Conversion Rate",
      "current_value": 8.5,
      "target_value": 10.0,
      "status": "at_risk",
      "progress_percentage": 85.0,
      "gap": 1.5
    }
  ],
  "summary": {
    "health_score": 75.5,
    "status_counts": {
      "exceeded": 2,
      "on_track": 5,
      "at_risk": 3,
      "off_track": 1
    }
  }
}
```

### Enterprise Alerts

#### Get Active Alerts
```http
GET /api/orgs/{org_id}/enterprise/alerts?status=active&severity=high
```

**Response**:
```json
{
  "success": true,
  "alerts": [
    {
      "alert_id": "uuid",
      "type": "budget_exceeded",
      "severity": "high",
      "message": "Campaign budget exceeded by 20%",
      "status": "active",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

#### Acknowledge Alert
```http
POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/acknowledge
```

**Body**:
```json
{
  "acknowledged_by": "user_uuid",
  "notes": "Acknowledged and reviewing"
}
```

---

## Frontend Integration

### Route Structure

```
/analytics                   → Redirect to /analytics/enterprise
/analytics/enterprise        → Unified analytics hub
/analytics/realtime          → Real-time performance dashboard
/analytics/campaigns         → Campaign list with analytics links
/analytics/campaign/{id}     → Individual campaign analytics
/analytics/kpis              → Organization-level KPIs
/analytics/kpis/{type}/{id}  → Entity-specific KPIs
```

### Controller Methods

**EnterpriseAnalyticsController**:
```php
public function enterprise(Request $request): View
public function realtime(Request $request): View
public function campaigns(Request $request): View
public function campaign(Request $request, string $campaignId): View
public function kpis(Request $request, ?string $entityType, ?string $entityId): View
```

### Blade Templates

| Template | Layout | Purpose |
|----------|--------|---------|
| `analytics/enterprise.blade.php` | `layouts/analytics` | Unified hub with tabs |
| `analytics/realtime.blade.php` | `layouts/analytics` | Real-time metrics |
| `analytics/campaigns.blade.php` | `layouts/analytics` | Campaign grid list |
| `analytics/campaign.blade.php` | `layouts/analytics` | Campaign details (5 tabs) |
| `analytics/kpis.blade.php` | `layouts/analytics` | KPI monitoring |

### Authentication

All routes require:
- `auth:sanctum` middleware
- `tenant` middleware (for RLS context)

Auth tokens are stored in localStorage:
```javascript
localStorage.setItem('auth_token', 'token-here');
localStorage.setItem('user_id', 'user-uuid');
```

---

## Security & Multi-Tenancy

### Row-Level Security (RLS)

All database queries automatically filtered by organization:
```sql
SET app.current_org_id = 'org-uuid';
```

RLS policies ensure:
- ✅ Users only see their organization's data
- ✅ No cross-tenant data leakage
- ✅ Automatic filtering at database level

### Authentication Flow

1. User logs in via Laravel authentication
2. Sanctum token created and stored
3. Frontend components use token for API calls
4. Backend validates token and sets RLS context
5. Database enforces multi-tenancy via RLS policies

### Authorization

Controllers verify:
- User is authenticated (Sanctum)
- User has active organization
- User has access to requested resources

### API Security

- All endpoints require Bearer token
- CSRF protection for web routes
- Rate limiting on API endpoints
- Input validation and sanitization

---

## Performance

### Optimization Strategies

1. **Auto-Refresh Intervals**:
   - Real-time: 30 seconds
   - KPIs: 60 seconds
   - Alerts: 30 seconds

2. **Silent Updates**:
   - Background refresh without UI flicker
   - Loading states only on user actions

3. **Chart Cleanup**:
   - Destroy Chart.js instances before re-render
   - Prevents memory leaks

4. **Pagination**:
   - Campaign lists paginated (20 per page)
   - Alert lists limited to recent items

5. **Caching** (Backend):
   - Dashboard metrics cached (5 min TTL)
   - API responses cached when appropriate

### Performance Targets

| Metric | Target | Actual |
|--------|--------|--------|
| API Response Time | < 2000ms | ✅ Tested |
| Page Load Time | < 3000ms | ✅ Optimized |
| Auto-Refresh Impact | Minimal | ✅ Silent updates |
| Chart Render Time | < 500ms | ✅ Chart.js optimized |

---

## Testing

### Test Coverage

**Feature Tests** (`tests/Feature/Analytics/`):
- `EnterpriseAnalyticsControllerTest.php` - 18 tests
- `AnalyticsAPITest.php` - 17 tests

**Total**: 35 tests covering:
- ✅ Authentication & authorization
- ✅ Multi-tenancy (RLS enforcement)
- ✅ Route accessibility
- ✅ View rendering
- ✅ Data structure validation
- ✅ API response formats
- ✅ Error handling
- ✅ Performance benchmarks

### Running Tests

```bash
# All analytics tests
vendor/bin/phpunit tests/Feature/Analytics/

# Controller tests only
vendor/bin/phpunit tests/Feature/Analytics/EnterpriseAnalyticsControllerTest.php

# API tests only
vendor/bin/phpunit tests/Feature/Analytics/AnalyticsAPITest.php

# Specific test
vendor/bin/phpunit --filter test_enterprise_hub_loads_successfully
```

### Test Database Setup

Tests use RefreshDatabase trait:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

RLS context automatically set/reset:
```php
protected function setUp(): void
{
    DB::statement("SET app.current_org_id = '{$this->org->org_id}'");
}

protected function tearDown(): void
{
    DB::statement("RESET app.current_org_id");
}
```

---

## Troubleshooting

### Common Issues

#### 1. Components Not Loading

**Symptom**: Alpine.js components not rendering

**Solution**:
```javascript
// Check Alpine is loaded
console.log(window.Alpine); // Should not be undefined

// Check components are registered
console.log(window.Alpine._x_dataStack); // Should show registered components
```

**Fix**: Ensure `layouts/analytics.blade.php` loads CDN scripts:
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.js"></script>
```

#### 2. API Returns 401 Unauthorized

**Symptom**: API calls fail with 401 status

**Solution**:
```javascript
// Check token is set
console.log(localStorage.getItem('auth_token')); // Should have token
```

**Fix**: Ensure token is created and stored:
```php
$token = $user->createToken('app-token')->plainTextToken;
```

#### 3. Multi-Tenancy Issues

**Symptom**: User sees data from other organizations

**Solution**:
```sql
-- Verify RLS context is set
SELECT current_setting('app.current_org_id', true);

-- Check RLS policies exist
SELECT * FROM pg_policies WHERE tablename = 'campaigns';
```

**Fix**: Ensure tenant middleware sets context:
```php
DB::statement("SET LOCAL app.current_org_id = ?", [$orgId]);
```

#### 4. Charts Not Rendering

**Symptom**: Canvas element exists but chart doesn't display

**Solution**:
```javascript
// Check Chart.js is loaded
console.log(window.Chart); // Should be Chart constructor

// Check canvas element exists
console.log(document.getElementById('roiChart')); // Should be canvas element
```

**Fix**: Destroy previous chart instance:
```javascript
if (this.charts.roi) {
    this.charts.roi.destroy();
}
this.charts.roi = new Chart(ctx, config);
```

#### 5. Auto-Refresh Not Working

**Symptom**: Dashboard doesn't update automatically

**Solution**:
```javascript
// Check auto-refresh is enabled
console.log(this.autoRefresh); // Should be true

// Check interval is set
console.log(this.refreshTimer); // Should be interval ID
```

**Fix**: Ensure interval is started:
```javascript
startAutoRefresh() {
    if (this.refreshTimer) {
        clearInterval(this.refreshTimer);
    }
    this.refreshTimer = setInterval(() => {
        this.loadDashboard();
    }, this.refreshInterval);
}
```

### Debug Mode

Enable component debugging:
```javascript
// In component init()
init() {
    this.debug = true; // Enable debug logging
    console.log('Component initialized:', this);
}
```

### Performance Debugging

Check API response times:
```javascript
async loadData() {
    const start = performance.now();
    const response = await fetch(url);
    const end = performance.now();
    console.log(`API call took ${end - start}ms`);
}
```

---

## Changelog

### Phase 10 (2025-11-21)
- ✅ Added 35 comprehensive tests (feature + API)
- ✅ Created system documentation
- ✅ Performance optimization guidelines
- ✅ Troubleshooting guide

### Phase 9 (2025-11-21)
- ✅ Laravel controller integration
- ✅ 5 Blade view templates
- ✅ 7 authenticated routes
- ✅ Multi-tenancy support

### Phase 8 (2025-11-21)
- ✅ 4 Alpine.js components
- ✅ Chart.js visualizations
- ✅ Component documentation
- ✅ Auto-refresh functionality

### Phase 5-7 (Previous Sessions)
- ✅ Backend API endpoints
- ✅ Real-time analytics
- ✅ ROI calculation
- ✅ Attribution modeling
- ✅ KPI tracking
- ✅ Enterprise alerts

---

## Support

For questions or issues:
1. Check this documentation first
2. Review component README: `resources/js/components/README.md`
3. Run tests to verify system integrity
4. Check logs: `storage/logs/laravel.log`
5. Contact development team

---

**End of Documentation**
