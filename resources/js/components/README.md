# CMIS Alpine.js Dashboard Components (Phase 8)

Comprehensive frontend components for the CMIS marketing platform built with Alpine.js, integrating with all backend analytics and monitoring APIs.

## 📦 Components

### 1. Real-Time Dashboard (`realtimeDashboard.js`)

Real-time analytics dashboard with auto-refreshing metrics and anomaly detection.

**Features:**
- Real-time metric aggregation (1m, 5m, 15m, 1h windows)
- Auto-refresh with configurable intervals
- Campaign performance overview with Chart.js visualizations
- Time-series trend analysis
- Anomaly detection integration
- Organization-level rollup metrics
- Derived metrics (CTR, CPC, conversion rate)

**Usage:**
```html
<div x-data="realtimeDashboard()" data-org-id="org-123" x-html="render()"></div>
```

**API Integration:**
- `GET /api/orgs/{org_id}/analytics/realtime/dashboard`
- `GET /api/orgs/{org_id}/analytics/realtime/{entity_type}/{entity_id}/metrics`
- `GET /api/orgs/{org_id}/analytics/realtime/{entity_type}/{entity_id}/timeseries`
- `GET /api/orgs/{org_id}/analytics/realtime/{entity_type}/{entity_id}/anomalies/{metric}`

---

### 2. Campaign Analytics (`campaignAnalytics.js`)

Comprehensive analytics dashboard for campaign performance analysis.

**Features:**
- ROI calculation and profitability analysis
- Multi-touch attribution modeling (6 models)
- Lifetime value (LTV) calculation
- ROI projection with confidence levels
- Interactive Chart.js visualizations
- Date range filtering
- Model comparison

**Usage:**
```html
<div x-data="campaignAnalytics()"
     data-org-id="org-123"
     data-campaign-id="campaign-456"></div>
```

**API Integration:**
- `POST /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}`
- `POST /api/orgs/{org_id}/analytics/attribution/campaigns/{campaign_id}/insights`
- `GET /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}/ltv`
- `POST /api/orgs/{org_id}/analytics/roi/campaigns/{campaign_id}/project`

**Attribution Models:**
- Last-click
- First-click
- Linear
- Time-decay
- Position-based
- Data-driven

---

### 3. KPI Dashboard (`kpiDashboard.js`)

Real-time KPI monitoring with status indicators and progress visualization.

**Features:**
- KPI tracking with threshold-based evaluation
- Health score calculation (0-100)
- Status indicators (exceeded, on_track, at_risk, off_track)
- Progress visualization with color-coded bars
- Gap-to-target analysis
- Status distribution overview
- Auto-refresh capability

**Usage:**
```html
<div x-data="kpiDashboard()"
     data-org-id="org-123"
     data-entity-type="campaign"
     data-entity-id="campaign-456"
     x-html="render()"></div>
```

**API Integration:**
- `GET /api/orgs/{org_id}/analytics/kpis/dashboard`

**KPI Statuses:**
- **Exceeded** (🎯): Performance exceeds target
- **On Track** (✅): Performance meeting target
- **At Risk** (⚠️): Performance below warning threshold
- **Off Track** (❌): Performance critically low

---

### 4. Notification Center (`notificationCenter.js`)

Real-time alert management and notification display.

**Features:**
- Real-time alert monitoring
- Severity-based filtering (critical, high, medium, low)
- Alert acknowledgment and resolution
- Browser notification integration
- Anomaly alert integration
- Auto-polling for new alerts (30s interval)
- Unread count badge
- Status filtering

**Usage:**
```html
<div x-data="notificationCenter()" data-org-id="org-123">
    <!-- Notification bell icon -->
    <button @click="toggle()" class="relative">
        <svg><!-- Bell icon --></svg>
        <span x-show="unreadCount > 0"
              x-text="unreadCount"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-2">
        </span>
    </button>

    <!-- Dropdown panel -->
    <div x-show="isOpen" class="notification-panel">
        <!-- Alerts list -->
    </div>
</div>
```

**API Integration:**
- `GET /api/orgs/{org_id}/enterprise/alerts`
- `POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/acknowledge`
- `POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/resolve`

**Alert Types:**
- Budget exceeded
- Performance drop
- Anomaly detected
- Spend spike
- Zero conversions
- Impression drop

---

## 🚀 Installation

### 1. Include Dependencies

```html
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.js"></script>

<!-- Tailwind CSS -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.x.x/dist/tailwind.min.css" rel="stylesheet">
```

### 2. Import Components

```html
<script type="module">
    import {
        realtimeDashboard,
        campaignAnalytics,
        kpiDashboard,
        notificationCenter
    } from '/js/components/index.js';

    // Register with Alpine (if not auto-registered)
    window.Alpine.data('realtimeDashboard', realtimeDashboard);
    window.Alpine.data('campaignAnalytics', campaignAnalytics);
    window.Alpine.data('kpiDashboard', kpiDashboard);
    window.Alpine.data('notificationCenter', notificationCenter);
</script>
```

---

## 🔧 Configuration

### Authentication

All components expect an authentication token in localStorage:

```javascript
localStorage.setItem('auth_token', 'your-jwt-token');
localStorage.setItem('user_id', 'user-uuid');
```

### Auto-Refresh

Components with auto-refresh can be configured:

```javascript
// In component data
autoRefresh: true,        // Enable/disable
refreshInterval: 30000,   // Milliseconds (30 seconds)
```

---

## 📊 Chart.js Integration

All visualization components use Chart.js. Charts are automatically created and destroyed on data updates.

**Chart Types Used:**
- **Bar charts**: Performance comparisons
- **Line charts**: Time-series trends
- **Pie charts**: Attribution distribution
- **Doughnut charts**: ROI visualization

---

## 🎨 Styling

Components use Tailwind CSS utility classes. Color scheme:

- **Primary**: Blue-600 (`#2563EB`)
- **Success**: Green-600 (`#16A34A`)
- **Warning**: Yellow-500 (`#EAB308`)
- **Danger**: Red-600 (`#DC2626`)
- **Info**: Purple-600 (`#9333EA`)

**Status Colors:**
- Exceeded/Excellent: Green
- On Track/Good: Blue
- At Risk/Fair: Yellow
- Off Track/Poor: Orange/Red

---

## 🔔 Browser Notifications

The notification center supports browser notifications for critical alerts.

**Enable notifications:**
```javascript
// Request permission
await notificationCenter().requestNotificationPermission();
```

**Requirements:**
- HTTPS or localhost
- User permission granted
- Browser support for Notification API

---

## 📡 Event System

Components communicate via custom events:

```javascript
// Listen for anomaly detection
window.addEventListener('anomaly-detected', (event) => {
    console.log(event.detail);
});

// Dispatch custom event
this.$dispatch('anomaly-detected', {
    campaignId: 'campaign-123',
    metric: 'impressions',
    message: 'Anomaly detected',
    data: { /* ... */ }
});
```

---

## 🔍 Error Handling

All components include error handling with user-friendly messages:

```javascript
// Error display
<div x-show="error" class="p-4 bg-red-50 border border-red-200 rounded-md">
    <p class="text-red-800" x-text="error"></p>
</div>
```

---

## 📈 Performance Optimization

**Best Practices:**
- Auto-refresh intervals: 30-60 seconds minimum
- Chart cleanup on destroy to prevent memory leaks
- Silent background updates to avoid UI flicker
- Debounced API calls for filter changes
- Pagination for large alert lists

---

## 🧪 Testing

To test components in development:

```javascript
// Mock data for testing
const mockDashboardData = {
    success: true,
    campaigns: [/* ... */],
    totals: {/* ... */}
};

// Override fetch for testing
window.fetch = async (url) => {
    return {
        ok: true,
        json: async () => mockDashboardData
    };
};
```

---

## 🔗 API Dependencies

Components require these backend services:
- Phase 5: Enterprise monitoring APIs
- Phase 6: Performance profiling APIs
- Phase 7: Advanced analytics APIs (ROI, Attribution, KPIs)

Ensure all API endpoints are available and properly authenticated.

---

## 📝 Example Implementation

### Complete Dashboard Page

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMIS Dashboard</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.x.x/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Real-Time Dashboard -->
        <div x-data="realtimeDashboard()"
             data-org-id="{{ auth()->user()->org_id }}"
             x-html="render()">
        </div>

        <!-- KPI Dashboard -->
        <div class="mt-8"
             x-data="kpiDashboard()"
             data-org-id="{{ auth()->user()->org_id }}"
             data-entity-type="campaign"
             data-entity-id="{{ $campaign->id }}"
             x-html="render()">
        </div>

        <!-- Notification Center -->
        <div class="fixed top-4 right-4"
             x-data="notificationCenter()"
             data-org-id="{{ auth()->user()->org_id }}">
            <button @click="toggle()" class="relative p-2 bg-white rounded-full shadow">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span x-show="unreadCount > 0"
                      x-text="unreadCount"
                      class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
                </span>
            </button>
        </div>
    </div>

    <script type="module" src="/js/components/index.js"></script>
</body>
</html>
```

---

## 🤝 Contributing

When adding new components:
1. Follow the existing component structure
2. Include comprehensive error handling
3. Add Chart.js cleanup on destroy
4. Document all API integrations
5. Export from `index.js`
6. Update this README

---

## 📄 License

Part of the CMIS (Cognitive Marketing Intelligence Suite) project.
