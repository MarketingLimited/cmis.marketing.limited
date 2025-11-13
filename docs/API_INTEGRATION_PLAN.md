# CMIS API Integration Plan - Phase 4

**Date:** November 12, 2025
**Branch:** `claude/cmis-laravel-continuation-011CV3wo4gkrG971Ucr2ZMmA`
**Status:** In Progress

---

## Overview

This document maps all frontend pages to their required backend API endpoints and tracks integration progress.

---

## Integration Status Summary

| Page | Frontend Status | Backend API | Integration Status |
|------|----------------|-------------|-------------------|
| Dashboard | ✅ Complete | ✅ Complete | ✅ **INTEGRATED** |
| Organizations | ✅ Complete | ✅ Complete | ⏳ Pending |
| Campaigns | ✅ Complete | ✅ Complete | ⏳ Pending |
| Analytics | ✅ Complete | ✅ Complete | ⏳ Pending |
| Integrations | ✅ Complete | 🔨 Needs Creation | ⏳ Pending |
| AI & Knowledge | ✅ Complete | ⚠️ Partial | ⏳ Pending |
| Creative Studio | ✅ Complete | ✅ Complete | ⏳ Pending |
| Social Scheduler | ✅ Complete | 🔨 Needs Creation | ⏳ Pending |

---

## 1. Dashboard - ✅ INTEGRATED

### Frontend Location
`resources/views/dashboard.blade.php`

### API Endpoints (Existing)
- ✅ `GET /dashboard/data` - Main dashboard metrics
- ✅ `GET /notifications/latest` - Recent activity notifications

### Data Structure
```json
{
  "stats": {
    "orgs": 12,
    "campaigns": 45,
    "creative_assets": 234,
    "kpis": 18,
    "offerings": 56
  },
  "campaignStatus": {
    "نشط": 25,
    "مجدول": 12,
    "مكتمل": 8
  },
  "campaignsByOrg": [
    { "org_name": "...", "total": 15 }
  ],
  "offerings": {...},
  "analytics": {...},
  "creative": {...},
  "ai": {...}
}
```

### Controller
`App\Http\Controllers\DashboardController@data`

### Integration Status
✅ **COMPLETE** - Dashboard now fetches real data from `/dashboard/data` endpoint

---

## 2. Organizations - ⏳ PENDING

### Frontend Location
`resources/views/orgs/index.blade.php`

### Required API Endpoints

#### Existing ✅
- `GET /orgs` - List all organizations
- `GET /orgs/{org}` - Get single organization
- `POST /orgs` - Create new organization (requires auth)
- `PUT /orgs/{org_id}` - Update organization (requires auth, org context)
- `DELETE /orgs/{org_id}` - Delete organization (requires auth, org context)
- `GET /orgs/{org}/statistics` - Organization statistics

#### Needed 🔨
- `GET /api/orgs/search?q={query}` - Search organizations
- `GET /api/orgs?status={status}&sort={field}` - Filter & sort

### Data Structure
```json
{
  "orgs": [
    {
      "org_id": 1,
      "name": "شركة التسويق",
      "industry": "تسويق",
      "created_at": "2025-01-15",
      "campaigns_count": 12,
      "status": "active"
    }
  ],
  "stats": {
    "total": 12,
    "active": 10,
    "inactive": 2
  }
}
```

### Controller
`App\Http\Controllers\OrgController`

### Integration Steps
1. Add search/filter methods to OrgController
2. Update frontend to use real API
3. Add form validation
4. Test CRUD operations

---

## 3. Campaigns - ⏳ PENDING

### Frontend Location
`resources/views/campaigns/index.blade.php`

### Required API Endpoints

#### Existing ✅
- `GET /api/orgs/{org_id}/campaigns` - List campaigns (requires auth)
- `POST /api/orgs/{org_id}/campaigns` - Create campaign
- `GET /api/orgs/{org_id}/campaigns/{campaign_id}` - Get single campaign
- `PUT /api/orgs/{org_id}/campaigns/{campaign_id}` - Update campaign
- `DELETE /api/orgs/{org_id}/campaigns/{campaign_id}` - Delete campaign

#### Needed 🔨
- `GET /campaigns` - List all campaigns (cross-org view)
- `GET /campaigns/search?q={query}` - Search campaigns
- `GET /campaigns?status={status}&org={org_id}` - Filter campaigns

### Data Structure
```json
{
  "campaigns": [
    {
      "campaign_id": 1,
      "name": "حملة الصيف 2025",
      "org_name": "شركة التسويق",
      "status": "active",
      "budget": 50000,
      "spend": 23450,
      "impressions": 125000,
      "clicks": 5230,
      "conversions": 234,
      "start_date": "2025-06-01",
      "end_date": "2025-08-31"
    }
  ],
  "stats": {
    "total": 45,
    "active": 25,
    "scheduled": 12,
    "completed": 8
  }
}
```

### Controller
`App\Http\Controllers\Campaigns\CampaignController`

### Integration Steps
1. Add cross-org campaign list method
2. Add search/filter functionality
3. Update frontend to use real API
4. Test campaign CRUD operations

---

## 4. Analytics - ⏳ PENDING

### Frontend Location
`resources/views/analytics/index.blade.php`

### Required API Endpoints

#### Existing ✅
- `GET /api/orgs/{org_id}/analytics/kpis` - KPI metrics
- `GET /api/orgs/{org_id}/analytics/summary` - Analytics summary
- `GET /api/orgs/{org_id}/analytics/trends` - Performance trends

#### Needed 🔨
- `GET /analytics/dashboard?start_date={date}&end_date={date}&org={org_id}&platform={platform}` - Main analytics data with filters

### Data Structure
```json
{
  "kpis": {
    "totalSpend": 245000,
    "spendChange": 12.5,
    "impressions": 3200000,
    "impressionsChange": 18.3,
    "clicks": 128000,
    "clicksChange": 15.7,
    "conversions": 5400,
    "conversionsChange": 22.1,
    "ctr": 4.0,
    "cpc": 1.91,
    "roas": 4.2
  },
  "platformPerformance": [
    {
      "name": "Meta",
      "spend": 120000,
      "clicks": 72000,
      "ctr": 4.0,
      "roas": 4.5
    }
  ],
  "charts": {
    "spendOverTime": [...],
    "platformDistribution": [...]
  }
}
```

### Controller
`App\Http\Controllers\Analytics\KpiController`

### Integration Steps
1. Add dashboard method with filters to KpiController
2. Update frontend to use real API
3. Test date range filtering
4. Test platform filtering

---

## 5. Integrations - 🔨 NEEDS CREATION

### Frontend Location
`resources/views/integrations/index.blade.php`

### Required API Endpoints

#### Needed 🔨 (All new)
- `GET /api/integrations` - List all integrations
- `GET /api/integrations/stats` - Integration statistics
- `POST /api/integrations/{platform}/connect` - Connect platform (OAuth)
- `POST /api/integrations/{platform}/sync` - Sync platform data
- `POST /api/integrations/{platform}/test` - Test connection
- `DELETE /api/integrations/{platform}/disconnect` - Disconnect platform
- `GET /api/integrations/activity` - Recent integration activity

### Data Structure
```json
{
  "stats": {
    "total": 6,
    "connected": 4,
    "disconnected": 2,
    "lastSync": "2025-11-12 10:30:00"
  },
  "platforms": [
    {
      "id": "meta",
      "name": "Meta Ads",
      "connected": true,
      "status": "active",
      "lastSync": "2025-11-12 10:30",
      "accountId": "act_123456",
      "metrics": {
        "campaigns": 12,
        "spend": 45000
      }
    }
  ],
  "recentActivity": [...]
}
```

### Controller
🔨 **TO CREATE:** `App\Http\Controllers\Integrations\IntegrationController`

### Integration Steps
1. Create IntegrationController
2. Implement OAuth flow for each platform
3. Add sync functionality
4. Update frontend to use new API
5. Test connection flows

---

## 6. AI & Knowledge Center - ⚠️ PARTIAL

### Frontend Location
`resources/views/ai/index.blade.php`

### Required API Endpoints

#### Existing ✅
- `POST /api/orgs/{org_id}/cmis/search` - CMIS embedding search
- `POST /api/orgs/{org_id}/semantic-search` - Semantic search
- `GET /api/orgs/{org_id}/cmis/status` - CMIS status

#### Needed 🔨
- `GET /api/ai/dashboard` - AI dashboard metrics
- `POST /api/ai/generate` - Generate content (Gemini)
- `GET /api/ai/content` - List generated content
- `GET /api/ai/recommendations` - Get AI recommendations
- `POST /api/ai/recommendations/{id}/apply` - Apply recommendation
- `GET /api/ai/models` - List AI models
- `GET /api/ai/knowledge/search?q={query}` - Search knowledge base

### Data Structure
```json
{
  "stats": {
    "generatedContent": 1247,
    "contentChange": 28.5,
    "activeRecommendations": 34,
    "appliedRecommendations": 89,
    "aiCampaigns": 156,
    "campaignSuccess": 87.3,
    "processedDocs": 2843,
    "vectorsStored": 45620
  },
  "aiServices": [...],
  "recentContent": [...],
  "recommendations": [...],
  "aiModels": [...],
  "knowledgeDocs": [...]
}
```

### Controllers
- Existing: `App\Http\Controllers\API\CMISEmbeddingController`
- Existing: `App\Http\Controllers\API\SemanticSearchController`
- 🔨 **TO CREATE:** `App\Http\Controllers\AI\AIGenerationController`

### Integration Steps
1. Create AIGenerationController for content generation
2. Integrate Gemini API
3. Add dashboard method to return AI metrics
4. Update frontend to use APIs
5. Test content generation flow
6. Test semantic search

---

## 7. Creative Studio - ⏳ PENDING

### Frontend Location
`resources/views/creative/index.blade.php`

### Required API Endpoints

#### Existing ✅
- `GET /api/orgs/{org_id}/creative/assets` - List assets
- `POST /api/orgs/{org_id}/creative/assets` - Upload asset
- `GET /api/orgs/{org_id}/creative/assets/{asset_id}` - Get asset
- `PUT /api/orgs/{org_id}/creative/assets/{asset_id}` - Update asset
- `DELETE /api/orgs/{org_id}/creative/assets/{asset_id}` - Delete asset

#### Needed 🔨
- `GET /api/creative/dashboard` - Creative dashboard metrics
- `GET /api/creative/templates` - List templates
- `GET /api/creative/brand-guidelines` - Brand guidelines
- `GET /api/creative/top-performing` - Top performing assets
- `GET /api/creative/activity` - Recent activity

### Data Structure
```json
{
  "stats": {
    "totalAssets": 1834,
    "assetsChange": 15.3,
    "pendingReview": 28,
    "avgReviewTime": 4.2,
    "approved": 1654,
    "approvalRate": 92.5,
    "templates": 45,
    "popularTemplates": 12
  },
  "assets": [...],
  "templates": [...],
  "topPerforming": [...],
  "recentActivity": [...],
  "brandGuidelines": {...}
}
```

### Controller
`App\Http\Controllers\Creative\CreativeAssetController`

### Integration Steps
1. Add dashboard method to CreativeAssetController
2. Add template management
3. Add brand guidelines methods
4. Update frontend to use real API
5. Test asset upload
6. Test template selection

---

## 8. Social Media Scheduler - 🔨 NEEDS CREATION

### Frontend Location
`resources/views/channels/index.blade.php`

### Required API Endpoints

#### Needed 🔨 (All new)
- `GET /api/social/dashboard` - Scheduler dashboard metrics
- `GET /api/social/posts` - List scheduled posts
- `POST /api/social/posts/schedule` - Schedule new post
- `PUT /api/social/posts/{id}` - Update scheduled post
- `DELETE /api/social/posts/{id}` - Delete post
- `GET /api/social/posts/published` - Get published posts
- `GET /api/social/posts/drafts` - Get draft posts
- `POST /api/social/posts/drafts` - Save draft
- `GET /api/social/calendar?month={month}&year={year}` - Calendar data

### Data Structure
```json
{
  "stats": {
    "scheduled": 47,
    "nextPost": "2025-11-12 18:00",
    "publishedToday": 12,
    "engagementChange": 18.5,
    "drafts": 8,
    "recentDrafts": 3,
    "activePlatforms": 5,
    "totalPlatforms": 5
  },
  "platforms": [...],
  "scheduledPosts": [...],
  "publishedPosts": [...],
  "drafts": [...]
}
```

### Controller
🔨 **TO CREATE:** `App\Http\Controllers\Social\SocialSchedulerController`

### Integration Steps
1. Create SocialSchedulerController
2. Implement post scheduling logic
3. Add calendar view data
4. Integrate with platform APIs
5. Update frontend to use new API
6. Test scheduling flow
7. Test multi-platform posting

---

## Implementation Priority

### Phase 4A - High Priority (Current Focus)
1. ✅ **Dashboard** - COMPLETE
2. **Organizations** - Simple CRUD, mostly exists
3. **Campaigns** - Core functionality, mostly exists
4. **Analytics** - Reporting, enhance existing

### Phase 4B - Medium Priority
5. **Creative Studio** - Asset management, mostly exists
6. **AI & Knowledge** - Partial exists, add generation

### Phase 4C - Lower Priority
7. **Integrations** - Platform connections, create new
8. **Social Scheduler** - Scheduling system, create new

---

## Missing Controllers to Create

1. 🔨 `App\Http\Controllers\Integrations\IntegrationController`
2. 🔨 `App\Http\Controllers\AI\AIGenerationController`
3. 🔨 `App\Http\Controllers\Social\SocialSchedulerController`

---

## API Route Additions Needed

Add to `routes/api.php` or `routes/web.php`:

```php
// Integrations (new)
Route::get('/api/integrations', [IntegrationController::class, 'index']);
Route::get('/api/integrations/stats', [IntegrationController::class, 'stats']);
Route::post('/api/integrations/{platform}/connect', [IntegrationController::class, 'connect']);
Route::post('/api/integrations/{platform}/sync', [IntegrationController::class, 'sync']);

// AI Generation (new)
Route::get('/api/ai/dashboard', [AIGenerationController::class, 'dashboard']);
Route::post('/api/ai/generate', [AIGenerationController::class, 'generate']);
Route::get('/api/ai/content', [AIGenerationController::class, 'listContent']);

// Social Scheduler (new)
Route::get('/api/social/dashboard', [SocialSchedulerController::class, 'dashboard']);
Route::post('/api/social/posts/schedule', [SocialSchedulerController::class, 'schedule']);
Route::get('/api/social/posts', [SocialSchedulerController::class, 'listPosts']);

// Enhanced existing routes
Route::get('/api/orgs/search', [OrgController::class, 'search']);
Route::get('/api/campaigns', [CampaignController::class, 'globalIndex']);
Route::get('/api/analytics/dashboard', [KpiController::class, 'dashboard']);
Route::get('/api/creative/dashboard', [CreativeAssetController::class, 'dashboard']);
```

---

## Testing Checklist

For each integrated page:
- [ ] API endpoint returns correct data structure
- [ ] Frontend displays data correctly
- [ ] Loading states work
- [ ] Error handling works
- [ ] Form submission works (if applicable)
- [ ] Validation works
- [ ] Empty states display correctly
- [ ] Real-time updates work (if applicable)

---

## Progress Tracking

- **Dashboard:** ✅ 100% Complete
- **Organizations:** 0% - Not started
- **Campaigns:** 0% - Not started
- **Analytics:** 0% - Not started
- **Integrations:** 0% - Not started (needs controller creation)
- **AI & Knowledge:** 0% - Not started (needs generation controller)
- **Creative Studio:** 0% - Not started
- **Social Scheduler:** 0% - Not started (needs controller creation)

**Overall Phase 4 Progress:** 12.5% (1/8 pages)

---

**Last Updated:** November 12, 2025
**Status:** Dashboard integrated, Organizations next in queue
