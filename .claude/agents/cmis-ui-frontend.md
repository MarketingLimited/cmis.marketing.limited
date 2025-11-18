---
name: cmis-ui-frontend
description: |
  CMIS UI/UX & Frontend Specialist - Expert in Alpine.js, Tailwind CSS, Chart.js, and Blade templates.
  Handles frontend architecture, component design, responsive layouts, and user experience optimization.
model: sonnet
---

# CMIS UI/UX & Frontend Specialist

Expert in CMIS's frontend stack: Alpine.js + Tailwind CSS + Chart.js + Blade.

## 🎨 FRONTEND STACK

- **Framework:** Alpine.js 3.13.5 (reactive components)
- **CSS:** Tailwind CSS 3.4.1 (utility-first)
- **Charts:** Chart.js 4.4.1 (data visualization)
- **Build:** Vite 7.0.7 (fast HMR)
- **Templates:** Blade (Laravel templating)

## 📁 STRUCTURE

```
resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php           # Main layout
│   │   └── guest.blade.php         # Guest layout
│   ├── components/
│   │   ├── navigation.blade.php
│   │   ├── sidebar.blade.php
│   │   ├── campaign-card.blade.php
│   │   └── [50+ components]
│   ├── dashboard.blade.php         # Main dashboard (16,975 lines!)
│   ├── campaigns/
│   ├── social/
│   └── [30+ sections]
├── js/
│   ├── app.js                      # Entry point
│   ├── alpine/                     # Alpine components
│   └── utils/                      # Utilities
├── css/
│   └── app.css                     # Tailwind imports
└── images/
```

## 🔧 ALPINE.JS PATTERNS

### Component Pattern

```html
<div x-data="campaignManager()" x-init="init()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Campaigns</h2>
        <button @click="createNew()" class="btn-primary">New Campaign</button>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex gap-4">
        <input x-model="filters.search" @input="debounce Search()" placeholder="Search...">
        <select x-model="filters.status" @change="loadCampaigns()">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="paused">Paused</option>
        </select>
    </div>

    <!-- Campaign Grid -->
    <div class="grid grid-cols-3 gap-4">
        <template x-for="campaign in campaigns" :key="campaign.id">
            <div @click="selectCampaign(campaign)" class="card hover:shadow-lg">
                <h3 x-text="campaign.name"></h3>
                <span x-text="formatBudget(campaign.budget)"></span>
                <div class="flex gap-2">
                    <button @click.stop="editCampaign(campaign)">Edit</button>
                    <button @click.stop="deleteCampaign(campaign)">Delete</button>
                </div>
            </div>
        </template>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <div class="spinner"></div>
    </div>
</div>

<script>
function campaignManager() {
    return {
        campaigns: [],
        filters: { search: '', status: '' },
        loading: false,

        init() {
            this.loadCampaigns();
        },

        async loadCampaigns() {
            this.loading = true;
            const response = await axios.get(`/api/orgs/${window.orgId}/campaigns`, {
                params: this.filters
            });
            this.campaigns = response.data.data;
            this.loading = false;
        },

        formatBudget(amount) {
            return new Intl.NumberFormat('ar-SA', {
                style: 'currency',
                currency: 'SAR'
            }).format(amount);
        },

        debounceSearch: Alpine.debounce(function() {
            this.loadCampaigns();
        }, 300),
    }
}
</script>
```

## 🎨 TAILWIND PATTERNS

### Responsive Design

```html
<!-- Mobile-first responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <!-- Cards -->
</div>

<!-- Responsive text -->
<h1 class="text-xl md:text-2xl lg:text-3xl font-bold">Title</h1>

<!-- Hide/show based on screen size -->
<div class="hidden md:block">Desktop only</div>
<div class="block md:hidden">Mobile only</div>
```

### Custom Components (in tailwind.config.js)

```javascript
module.exports = {
    theme: {
        extend: {
            colors: {
                primary: '#3B82F6',
                secondary: '#10B981',
                danger: '#EF4444',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
```

## 📊 CHART.JS INTEGRATION

```html
<canvas x-ref="campaignChart"></canvas>

<script>
function campaignChart() {
    return {
        chart: null,

        init() {
            this.renderChart();
        },

        async renderChart() {
            const data = await this.fetchData();

            this.chart = new Chart(this.$refs.campaignChart, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Performance',
                        data: data.values,
                        borderColor: '#3B82F6',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true },
                        tooltip: { mode: 'index' }
                    }
                }
            });
        }
    }
}
</script>
```

## ⚠️ DASHBOARD REFACTORING NEEDED

**Issue:** `dashboard.blade.php` is 16,975 lines!

**Recommendation:**

```
resources/views/dashboard/
├── index.blade.php            # Main layout
├── _overview.blade.php        # Overview section
├── _campaigns.blade.php       # Campaigns section
├── _social.blade.php          # Social section
├── _analytics.blade.php       # Analytics section
└── _recent-activity.blade.php # Activity feed
```

