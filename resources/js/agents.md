# دليل الوكلاء - Frontend JavaScript Layer (resources/js/)

## 1. Purpose (الغرض)

طبقة JavaScript توفر **Alpine.js Components + Chart.js** للواجهة الأمامية:
- **10 Alpine.js Components**: لوحات المعلومات التفاعلية والتحليلات
- **Chart.js Integration**: رسوم بيانية للمقاييس والاتجاهات
- **CMIS API Client**: عميل موحد للتواصل مع API
- **Feature Flag Service**: إدارة ميزات النظام
- **Reactive State Management**: إدارة الحالة باستخدام Alpine.js

## 2. Owned Scope (النطاق المملوك)

### JavaScript File Organization

```
resources/js/
├── app.js                          # Entry point (Alpine + Chart.js)
├── bootstrap.js                    # Axios configuration
│
├── components/                     # Alpine.js Components (10 total)
│   ├── index.js                   # Components registry
│   ├── realtimeDashboard.js       # Real-time metrics dashboard
│   ├── campaignAnalytics.js       # Campaign analytics component
│   ├── campaignDashboard.js       # Campaign performance dashboard
│   ├── kpiDashboard.js            # KPI tracking dashboard
│   ├── notificationCenter.js      # Notifications UI
│   ├── campaignComparison.js      # Campaign comparison tool
│   ├── scheduledReports.js        # Reports scheduling
│   ├── alertsManagement.js        # Alerts management UI
│   ├── dataExports.js             # Data export functionality
│   ├── experiments.js             # A/B testing UI
│   ├── predictiveAnalytics.js     # Predictive analytics dashboard
│   ├── contextSelector.js         # Organization context selector
│   └── userManagement.js          # User management UI
│
├── api/
│   └── cmis-api-client.js         # Centralized API client
│
└── services/
    └── FeatureFlagService.js      # Feature flag management
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Entry Points
- `app.js`: Main entry point
  ```javascript
  import Alpine from 'alpinejs';
  import Chart from 'chart.js/auto';

  window.Alpine = Alpine;
  window.Chart = Chart;
  Alpine.start();
  ```

- `bootstrap.js`: Axios configuration
  - CSRF token setup
  - Default headers
  - Base URL configuration

### Core Components

#### Campaign Dashboard (`campaignDashboard.js`)
**Purpose**: Campaign performance analytics with Chart.js visualization

**Key Features**:
- Real-time metrics loading
- Date range filtering
- Performance trends (impressions, clicks, conversions)
- Chart.js line charts
- Top performing campaigns

**Usage**:
```html
<div x-data="campaignDashboard(campaignId)" x-init="init()">
    <div x-html="renderDashboard()"></div>
</div>
```

**API Endpoints Used**:
- `GET /api/campaigns/{id}/performance-metrics`
- `GET /api/campaigns/{id}/performance-trends`
- `GET /api/campaigns/top-performing`

#### Components Registry (`index.js`)
Exports all components and registers them globally with Alpine.js:

```javascript
// Export all components
export {
    realtimeDashboard,
    campaignAnalytics,
    kpiDashboard,
    notificationCenter,
    campaignComparison,
    scheduledReports,
    alertsManagement,
    dataExports,
    experiments,
    predictiveAnalytics
};

// Register globally with Alpine.js
if (window.Alpine) {
    window.Alpine.data('realtimeDashboard', realtimeDashboard);
    window.Alpine.data('campaignAnalytics', campaignAnalytics);
    // ...
}
```

### API Client

#### CMIS API Client (`api/cmis-api-client.js`)
**Purpose**: Centralized HTTP client for all API requests

**Features**:
- Authentication handling
- Request/response interceptors
- Error handling
- Base URL configuration
- Organization context header injection

**Typical Structure**:
```javascript
class CMISApiClient {
    constructor() {
        this.baseURL = '/api';
        this.authToken = this.getAuthToken();
    }

    async get(endpoint, params = {}) {
        // GET request
    }

    async post(endpoint, data) {
        // POST request
    }

    getAuthToken() {
        // Retrieve from localStorage or cookies
    }
}
```

### Feature Flags

#### Feature Flag Service (`services/FeatureFlagService.js`)
**Purpose**: Enable/disable features dynamically

**Usage**:
```javascript
import FeatureFlagService from './services/FeatureFlagService';

const flags = new FeatureFlagService();
if (flags.isEnabled('predictive-analytics')) {
    // Show predictive analytics UI
}
```

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### NPM Dependencies (from package.json)
```json
{
  "dependencies": {
    "alpinejs": "^3.14.3",
    "axios": "^1.7.9",
    "chart.js": "^4.4.7",
    "laravel-vite-plugin": "^1.1.1",
    "vite": "^6.0.3"
  }
}
```

### External APIs
- **CMIS API**: `/api/*` endpoints
- **Authentication**: Bearer token via `Authorization` header
- **Organization Context**: `X-Organization-Id` header (optional)

### Backend Integration
```
Blade Templates → Alpine.js Components → CMIS API → Laravel Controllers
     ↓                    ↓                   ↓
  x-data=""         Fetch/Axios          ApiResponse trait
```

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Alpine.js Component Pattern

#### ✅ Standard Component Structure
```javascript
export default function componentName(params) {
    return {
        // State
        data: null,
        isLoading: false,
        error: null,

        // Lifecycle
        async init() {
            await this.loadData();
        },

        // Methods
        async loadData() {
            this.isLoading = true;
            this.error = null;

            try {
                const response = await fetch('/api/endpoint', {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load data');
                }

                const result = await response.json();

                if (result.success) {
                    this.data = result.data;
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Load error:', error);
                this.error = error.message;
            } finally {
                this.isLoading = false;
            }
        },

        // Helpers
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        formatNumber(num) {
            return num.toLocaleString();
        }
    };
}

// Export globally
window.componentName = componentName;
```

### Chart.js Integration Pattern

```javascript
// In component
charts: {},

renderChart(canvasId, type, data, options) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Destroy existing chart
    if (this.charts[canvasId]) {
        this.charts[canvasId].destroy();
    }

    // Create new chart
    this.charts[canvasId] = new Chart(ctx, {
        type: type,
        data: data,
        options: options
    });
}
```

### API Request Pattern

```javascript
// Fetch with authentication
async apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Authorization': `Bearer ${this.getAuthToken()}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    };

    const response = await fetch(`/api/${endpoint}`, {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return await response.json();
}
```

### Error Handling Pattern

```javascript
// Consistent error handling
try {
    const data = await this.loadData();
    this.processData(data);
} catch (error) {
    console.error('Error:', error);
    this.error = error.message;

    // Optional: Show user-friendly error
    this.showNotification('error', 'Failed to load data. Please try again.');
}
```

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Development

```bash
# Install dependencies
npm install

# Start Vite dev server (hot reload)
npm run dev

# Build for production
npm run build

# Watch mode
npm run watch
```

### Build Output

```bash
# Vite builds to public/build/
npm run build

# Output:
# public/build/assets/app-[hash].js
# public/build/assets/app-[hash].css
# public/build/manifest.json
```

### Testing Components

```bash
# No dedicated JS tests yet (planned)
# Manual testing in browser

# Test component in console
Alpine.data('campaignDashboard')()
```

### Debugging

```javascript
// In component init()
console.log('Component initialized:', this);

// In methods
console.log('Data loaded:', this.data);

// Alpine.js DevTools (browser extension)
// https://github.com/alpine-collective/alpinejs-devtools
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Create New Alpine.js Component

1. **Create component file**:
   ```javascript
   // resources/js/components/newComponent.js
   export default function newComponent(params) {
       return {
           data: null,
           isLoading: false,

           async init() {
               await this.loadData();
           },

           async loadData() {
               // Fetch data from API
           }
       };
   }

   window.newComponent = newComponent;
   ```

2. **Register in index.js**:
   ```javascript
   import newComponent from './newComponent.js';

   export { ..., newComponent };

   if (window.Alpine) {
       window.Alpine.data('newComponent', newComponent);
   }
   ```

3. **Use in Blade template**:
   ```html
   <div x-data="newComponent()" x-init="init()">
       <div x-show="isLoading">Loading...</div>
       <div x-show="!isLoading" x-text="data"></div>
   </div>
   ```

### Add Chart.js Visualization

```javascript
// In component
renderMyChart() {
    const canvas = document.getElementById('my-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'line', // 'bar', 'pie', 'doughnut', etc.
        data: {
            labels: ['Jan', 'Feb', 'Mar'],
            datasets: [{
                label: 'My Data',
                data: [10, 20, 30],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true },
                tooltip: { mode: 'index' }
            }
        }
    });
}
```

### Add API Endpoint Call

```javascript
async fetchCampaigns() {
    try {
        const response = await fetch('/api/campaigns', {
            headers: {
                'Authorization': `Bearer ${this.getAuthToken()}`,
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch campaigns');
        }

        const result = await response.json();

        if (result.success) {
            this.campaigns = result.data;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        this.error = error.message;
    }
}
```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Mistakes

1. **Forgetting to Export Component Globally**
   ```javascript
   ❌ export default function myComponent() { ... }

   ✅ export default function myComponent() { ... }
   window.myComponent = myComponent;
   ```

2. **Chart Memory Leaks**
   ```javascript
   ❌ new Chart(ctx, {...}) // Creates new chart without destroying old one

   ✅ if (this.chart) this.chart.destroy();
       this.chart = new Chart(ctx, {...});
   ```

3. **Missing Auth Token**
   ```javascript
   ❌ fetch('/api/endpoint') // No authentication

   ✅ fetch('/api/endpoint', {
       headers: { 'Authorization': `Bearer ${token}` }
   })
   ```

4. **Not Handling API Errors**
   ```javascript
   ❌ const data = await response.json(); // Assumes success

   ✅ if (!response.ok) throw new Error('...');
       const data = await response.json();
       if (!data.success) throw new Error(data.message);
   ```

### 🎯 Best Practices

1. **Use Alpine.js Reactivity**
   ```javascript
   ✅ this.data = newData; // Alpine.js auto-updates DOM
   ❌ document.getElementById('data').innerHTML = newData; // Manual DOM manipulation
   ```

2. **Destroy Charts on Cleanup**
   ```javascript
   cleanup() {
       Object.values(this.charts).forEach(chart => chart.destroy());
       this.charts = {};
   }
   ```

3. **Format Data for Display**
   ```javascript
   formatNumber(num) {
       return num.toLocaleString();
   }

   formatCurrency(amount, currency = 'BHD') {
       return `${currency} ${amount.toFixed(2)}`;
   }

   formatPercentage(value) {
       return `${value.toFixed(1)}%`;
   }
   ```

4. **Handle Loading States**
   ```html
   <div x-show="isLoading">
       <svg class="animate-spin ...">...</svg>
   </div>
   <div x-show="!isLoading && data">
       <!-- Content -->
   </div>
   ```

### 📊 Statistics

- **Total Components**: 13 Alpine.js components
- **API Client**: 1 centralized client
- **Services**: 1 feature flag service
- **Charts**: Chart.js for all visualizations
- **Build Tool**: Vite for hot reload & bundling

### 🔗 Related Files

- **Blade Templates**: `resources/views/` - Use components via `x-data`
- **API Routes**: `routes/api.php` - Backend endpoints
- **Controllers**: `app/Http/Controllers/` - API logic
- **Vite Config**: `vite.config.js` - Build configuration

### 🚀 Performance Tips

1. **Lazy Load Components**: Only initialize when needed
2. **Debounce API Calls**: Use debounce for search/filter
3. **Cache API Responses**: Store in component state
4. **Optimize Chart Rendering**: Limit data points, use sampling
5. **Use CDN for Static Assets**: Serve Chart.js, Alpine.js from CDN in production

### 🎨 Styling

All components use **Tailwind CSS** classes:
- `bg-white`, `rounded-lg`, `shadow` for cards
- `text-gray-900`, `text-sm`, `font-medium` for typography
- `animate-spin` for loading spinners
- RTL support: `rtl:space-x-reverse`
