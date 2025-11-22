# دليل الوكلاء - Resources Layer (resources/)

## 1. Purpose

طبقة Resources تحتوي على **Frontend Assets**:
- **Alpine.js Components**: 15+ interactive components
- **Blade Views**: Server-rendered templates
- **CSS/Tailwind**: Styling
- **Vite**: Build tool

## 2. Owned Scope

```
resources/
├── js/
│   ├── app.js              # Entry point
│   ├── bootstrap.js        # Bootstrap (Axios, Alpine)
│   │
│   ├── components/         # Alpine.js components
│   │   ├── campaignDashboard.js
│   │   ├── campaignAnalytics.js
│   │   ├── realtimeDashboard.js
│   │   ├── notificationCenter.js
│   │   └── ... (11+ more)
│   │
│   ├── api/
│   │   └── cmis-api-client.js  # API wrapper
│   │
│   └── services/
│       └── FeatureFlagService.js
│
├── views/                  # Blade templates
│   ├── layouts/
│   │   └── app.blade.php
│   ├── dashboard/
│   ├── campaigns/
│   ├── integrations/
│   └── ...
│
└── css/
    └── app.css             # Tailwind imports
```

## 3. Key Files

- `js/app.js`: Alpine.js initialization
- `js/components/index.js`: Component registry
- `js/api/cmis-api-client.js`: Axios-based API client
- `views/layouts/app.blade.php`: Main layout

## 4. Alpine.js Component Pattern

```javascript
// resources/js/components/campaignDashboard.js
export default function campaignDashboard() {
    return {
        campaigns: [],
        loading: false,
        selectedCampaign: null,

        async init() {
            await this.loadCampaigns();
        },

        async loadCampaigns() {
            this.loading = true;
            try {
                const response = await axios.get('/api/campaigns');
                this.campaigns = response.data.data;
            } catch (error) {
                console.error('Failed to load campaigns', error);
            } finally {
                this.loading = false;
            }
        },

        selectCampaign(campaign) {
            this.selectedCampaign = campaign;
        }
    }
}
```

## 5. Blade View Pattern

```blade
{{-- resources/views/campaigns/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div x-data="campaignDashboard()">
    <div x-show="loading">Loading...</div>

    <template x-for="campaign in campaigns" :key="campaign.id">
        <div @click="selectCampaign(campaign)">
            <h3 x-text="campaign.name"></h3>
        </div>
    </template>
</div>
@endsection
```

## 6. Build Commands

```bash
# Development
npm run dev

# Production build
npm run build

# Watch mode
vite --watch
```

## 7. Notes

- المشروع يستخدم **Alpine.js** (ليس Vue)
- **Tailwind CSS** للتصميم
- **Chart.js** للرسوم البيانية
- **Vite** كأداة build
