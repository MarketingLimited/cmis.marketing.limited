# CMIS Routes Reference Guide

**Last Updated:** 2025-11-22
**Version:** 3.2
**Project:** CMIS - Cognitive Marketing Intelligence Suite

---

## Table of Contents

1. [Overview](#overview)
2. [API Routes](#api-routes)
3. [Web Routes](#web-routes)
4. [Convenience Routes (NEW)](#convenience-routes)
5. [Middleware Reference](#middleware-reference)
6. [Best Practices](#best-practices)

---

## Overview

CMIS uses a **multi-tenancy architecture** with Row-Level Security (RLS). Most routes require:
- `auth:sanctum` - User authentication
- `validate.org.access` - Organization access validation
- `set.db.context` - Database context for RLS

### Route Patterns

**API Routes:** `/api/...`
- Prefix: `/api`
- Authentication: Sanctum (Bearer tokens)
- Response Format: JSON
- Total Routes: ~800+

**Web Routes:** `/...`
- Prefix: None (root)
- Authentication: Session-based
- Response Format: HTML (Blade views)
- Total Routes: ~260+

---

## API Routes

### Authentication Routes

**Base:** `/api/auth`

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| POST | `/auth/register` | AuthController@register | Register new user |
| POST | `/auth/login` | AuthController@login | User login |
| POST | `/auth/logout` | AuthController@logout | Logout (invalidate token) |
| POST | `/auth/refresh` | AuthController@refresh | Refresh auth token |
| GET | `/auth/me` | AuthController@me | Get current user info |
| PUT | `/auth/profile` | AuthController@updateProfile | Update profile |
| PUT | `/auth/profile/avatar` | ProfileController@avatar | Upload avatar |
| GET | `/auth/activity` | AuthController@activity | User activity log |

**Rate Limiting:** `throttle:auth` (10 attempts/min)

---

### Organization-Level Routes

**Base:** `/api/orgs/{org_id}`

**Required Middleware:** `['auth:sanctum', 'validate.org.access', 'set.db.context']`

#### Organization Management

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/` | OrgController@show | Get org details |
| PUT | `/` | OrgController@update | Update org info |
| DELETE | `/` | OrgController@destroy | Delete organization |
| GET | `/statistics` | OrgController@statistics | Org statistics |

#### User Management

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/users` | UserManagementController@index | List users |
| GET | `/users/{user_id}` | UserManagementController@show | User details |
| POST | `/users/invite` | UserManagementController@inviteUser | Invite user |
| GET | `/users/invitations` | UserManagementController@getInvitations | List invitations |
| PUT | `/users/{user_id}/role` | UserManagementController@updateRole | Update user role |
| PUT | `/users/{user_id}/status` | UserManagementController@updateStatus | Update user status |
| DELETE | `/users/{user_id}` | UserManagementController@removeUser | Remove user |

#### Markets

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/markets` | OrgMarketController@index | List markets |
| POST | `/markets` | OrgMarketController@store | Create market |
| GET | `/markets/available` | OrgMarketController@available | Available markets |
| GET | `/markets/stats` | OrgMarketController@stats | Market statistics |
| GET | `/markets/{market_id}` | OrgMarketController@show | Market details |
| PUT | `/markets/{market_id}` | OrgMarketController@update | Update market |
| DELETE | `/markets/{market_id}` | OrgMarketController@destroy | Delete market |

#### Creative Assets

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/creative/assets` | CreativeAssetController@index | List assets |
| POST | `/creative/assets` | CreativeAssetController@store | Upload asset |
| GET | `/creative/assets/{asset_id}` | CreativeAssetController@show | Asset details |
| PUT | `/creative/assets/{asset_id}` | CreativeAssetController@update | Update asset |
| DELETE | `/creative/assets/{asset_id}` | CreativeAssetController@destroy | Delete asset |

#### Content Plans

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/creative/content-plans` | ContentPlanController@index | List plans |
| POST | `/creative/content-plans` | ContentPlanController@store | Create plan |
| GET | `/creative/content-plans/{plan_id}` | ContentPlanController@show | Plan details |
| PUT | `/creative/content-plans/{plan_id}` | ContentPlanController@update | Update plan |
| DELETE | `/creative/content-plans/{plan_id}` | ContentPlanController@destroy | Delete plan |
| POST | `/creative/content-plans/{plan_id}/approve` | ContentPlanController@approve | Approve plan |
| POST | `/creative/content-plans/{plan_id}/reject` | ContentPlanController@reject | Reject plan |
| POST | `/creative/content-plans/{plan_id}/publish` | ContentPlanController@publish | Publish plan |
| POST | `/creative/content-plans/{plan_id}/generate` | ContentPlanController@generateContent | Generate content (AI) |

**AI Routes:** Uses `throttle.ai` middleware

#### Social Media

**Channels:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/channels` | ChannelController@index | List social channels |
| POST | `/channels` | ChannelController@store | Add channel |
| GET | `/channels/{channel_id}` | ChannelController@show | Channel details |
| PUT | `/channels/{channel_id}` | ChannelController@update | Update channel |
| DELETE | `/channels/{channel_id}` | ChannelController@destroy | Delete channel |

**Posts:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/social/posts/scheduled` | SocialSchedulerController@scheduled | Scheduled posts |
| GET | `/social/posts/published` | SocialSchedulerController@published | Published posts |
| GET | `/social/posts/drafts` | SocialSchedulerController@drafts | Draft posts |
| POST | `/social/posts/schedule` | SocialSchedulerController@schedule | Schedule post |
| GET | `/social/posts/{post_id}` | SocialSchedulerController@show | Post details |
| PUT | `/social/posts/{post_id}` | SocialSchedulerController@update | Update post |
| DELETE | `/social/posts/{post_id}` | SocialSchedulerController@destroy | Delete post |
| POST | `/social/posts/{post_id}/publish-now` | SocialSchedulerController@publishNow | Publish immediately |
| POST | `/social/posts/{post_id}/reschedule` | SocialSchedulerController@reschedule | Reschedule post |

**Publishing Queues:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/queues/{account_id}` | PublishingQueueController@show | Queue details |
| POST | `/queues` | PublishingQueueController@store | Create queue |
| PUT | `/queues/{account_id}` | PublishingQueueController@update | Update queue |
| GET | `/queues/{account_id}/next-slot` | PublishingQueueController@nextSlot | Next available slot |
| GET | `/queues/{account_id}/statistics` | PublishingQueueController@statistics | Queue statistics |
| GET | `/queues/{account_id}/posts` | PublishingQueueController@queuedPosts | Queued posts |
| POST | `/queues/{account_id}/schedule` | PublishingQueueController@schedulePost | Add to queue |
| DELETE | `/queues/posts/{post_id}` | PublishingQueueController@removePost | Remove from queue |

**Bulk Operations:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| POST | `/bulk-posts/create` | BulkPostController@createBulk | Create multiple posts |
| POST | `/bulk-posts/import-csv` | BulkPostController@importCSV | Import from CSV |
| PUT | `/bulk-posts/update` | BulkPostController@bulkUpdate | Update multiple |
| DELETE | `/bulk-posts/delete` | BulkPostController@bulkDelete | Delete multiple |

**Best Time Analysis:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/best-times/{account_id}` | BestTimeController@analyze | Analyze best times |
| GET | `/best-times/{account_id}/recommendations` | BestTimeController@recommendations | Get recommendations |
| GET | `/best-times/{account_id}/compare` | BestTimeController@compare | Compare platforms |

**Approvals:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| POST | `/approvals/request` | ApprovalController@requestApproval | Request approval |
| POST | `/approvals/{approval_id}/approve` | ApprovalController@approve | Approve request |
| POST | `/approvals/{approval_id}/reject` | ApprovalController@reject | Reject request |
| POST | `/approvals/{approval_id}/reassign` | ApprovalController@reassign | Reassign approver |
| GET | `/approvals/pending` | ApprovalController@pending | Pending approvals |
| GET | `/approvals/post/{post_id}/history` | ApprovalController@history | Approval history |

#### Analytics

**Dashboard:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/analytics/dashboard/overview` | AnalyticsDashboardController@orgOverview | Organization overview |
| GET | `/analytics/dashboard/snapshot` | AnalyticsDashboardController@snapshot | Quick snapshot |
| GET | `/analytics/dashboard/account/{account_id}` | AnalyticsDashboardController@accountDashboard | Account dashboard |
| GET | `/analytics/dashboard/platforms` | AnalyticsDashboardController@platformComparison | Platform comparison |

**Content Performance:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/content/analytics/post/{post_id}` | ContentAnalyticsController@postAnalytics | Post analytics |
| GET | `/content/analytics/hashtags/{account_id}` | ContentAnalyticsController@hashtagAnalytics | Hashtag analysis |
| GET | `/content/analytics/demographics/{account_id}` | ContentAnalyticsController@audienceDemographics | Demographics |
| GET | `/content/analytics/patterns/{account_id}` | ContentAnalyticsController@engagementPatterns | Engagement patterns |
| GET | `/content/analytics/content-types/{account_id}` | ContentAnalyticsController@contentTypePerformance | Content type performance |
| GET | `/content/analytics/top-posts/{account_id}` | ContentAnalyticsController@topPosts | Top performing posts |

**AI Insights:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/ai/insights/{account_id}` | AIInsightsController@accountInsights | AI insights |
| GET | `/ai/insights/{account_id}/summary` | AIInsightsController@insightsSummary | Insights summary |
| GET | `/ai/insights/{account_id}/recommendations` | AIInsightsController@contentRecommendations | Content recommendations |
| GET | `/ai/insights/{account_id}/anomalies` | AIInsightsController@anomalyDetection | Anomaly detection |
| GET | `/ai/insights/{account_id}/predictions` | AIInsightsController@predictions | Predictions |
| GET | `/ai/insights/{account_id}/opportunities` | AIInsightsController@optimizationOpportunities | Optimization opportunities |

**Reports:**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/reports/types` | ReportsController@getReportTypes | Available report types |
| POST | `/reports/performance` | ReportsController@generatePerformanceReport | Performance report (PDF) |
| POST | `/reports/ai-insights` | ReportsController@generateAIInsightsReport | AI insights report (PDF) |
| POST | `/reports/organization` | ReportsController@generateOrgReport | Organization report |
| POST | `/reports/content-analysis` | ReportsController@generateContentAnalysisReport | Content analysis report |

#### Alerts

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/alerts/rules` | AlertsController@index | List alert rules |
| POST | `/alerts/rules` | AlertsController@store | Create rule |
| GET | `/alerts/rules/{rule_id}` | AlertsController@show | Rule details |
| PUT | `/alerts/rules/{rule_id}` | AlertsController@update | Update rule |
| DELETE | `/alerts/rules/{rule_id}` | AlertsController@destroy | Delete rule |
| POST | `/alerts/rules/{rule_id}/test` | AlertsController@testRule | Test rule |
| GET | `/alerts/history` | AlertsController@history | Alert history |
| POST | `/alerts/{alert_id}/acknowledge` | AlertsController@acknowledge | Acknowledge alert |
| POST | `/alerts/{alert_id}/resolve` | AlertsController@resolve | Resolve alert |
| POST | `/alerts/{alert_id}/snooze` | AlertsController@snooze | Snooze alert |

**Global (no org_id):**

| Method | Endpoint | Controller@Method | Description |
|--------|----------|-------------------|-------------|
| GET | `/alerts/templates` | AlertsController@templates | Get alert templates |

---

## Convenience Routes (NEW)

**Base:** `/api/convenience`

**Middleware:** `['auth:sanctum', 'resolve.active.org']`

These routes automatically resolve the user's active organization, eliminating the need to pass `org_id` in the URL.

### Dashboard

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| GET | `/convenience/dashboard` | DashboardController@data | Dashboard data |

### Campaigns

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| GET | `/convenience/campaigns` | CampaignController@index | List campaigns |
| GET | `/convenience/campaigns/{campaign}` | CampaignController@show | Campaign details |

### Integrations

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| GET | `/convenience/integrations/activity` | IntegrationHubController@getIntegrationLogs | Integration activity log |
| GET | `/convenience/integrations` | IntegrationHubController@index | List integrations |
| GET | `/convenience/integrations/available` | IntegrationHubController@available | Available integrations |

### Analytics

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| GET | `/convenience/analytics/summary` | AnalyticsController@summary | Analytics summary |
| GET | `/convenience/analytics/kpis` | KpiController@index | KPIs |
| POST | `/convenience/analytics/export/excel` | AnalyticsController@exportReport | Export Excel |
| POST | `/convenience/analytics/export/pdf` | AnalyticsController@exportReport | Export PDF |
| POST | `/convenience/analytics/export` | AnalyticsController@exportReport | Export (any format) |

### Content

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| GET | `/convenience/content/plans` | ContentPlanController@index | Content plans |
| GET | `/convenience/content/assets` | CreativeAssetController@index | Creative assets |

### Social Media

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| GET | `/convenience/social/posts/scheduled` | SocialSchedulerController@scheduled | Scheduled posts |
| GET | `/convenience/social/posts/published` | SocialSchedulerController@published | Published posts |
| GET | `/convenience/social/dashboard` | SocialSchedulerController@dashboard | Social dashboard |

### AI

| Method | Endpoint | Maps To | Description |
|--------|----------|---------|-------------|
| POST | `/convenience/ai/generate` | AIGenerationController@generateContent | Generate content |
| GET | `/convenience/ai/history` | AIGenerationController@getHistory | Generation history |
| GET | `/convenience/ai/insights` | AIInsightsController@index | AI insights |

**Rate Limiting:** AI routes use `throttle.ai` (30 requests/min)

### Placeholders (Coming Soon)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/convenience/leads` | Lead management (placeholder) |
| GET | `/convenience/experiments/stats` | Experiments stats (placeholder) |

---

## Web Routes

### Authentication

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/login` | LoginController@create | auth.login | Login page |
| POST | `/login` | LoginController@store | - | Process login |
| GET | `/register` | RegisterController@create | auth.register | Registration page |
| POST | `/register` | RegisterController@store | - | Process registration |
| POST | `/logout` | LoginController@destroy | - | Logout |

### Invitations

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/invitations/accept/{token}` | InvitationController@show | invitations.accept | View invitation |
| POST | `/invitations/accept/{token}` | InvitationController@accept | - | Accept invitation |
| GET | `/invitations/decline/{token}` | InvitationController@decline | - | Decline invitation |

### Dashboard

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/` | (redirect) | - | Redirects to dashboard or login |
| GET | `/dashboard` | DashboardController@index | dashboard.index | Main dashboard |
| GET | `/dashboard/data` | DashboardController@data | JSON | Dashboard data (AJAX) |
| GET | `/notifications/latest` | DashboardController@latest | JSON | Latest notifications |
| POST | `/notifications/{id}/read` | DashboardController@markAsRead | JSON | Mark as read |

### Campaigns

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/campaigns` | CampaignController@index | campaigns.index | Campaigns list |
| GET | `/campaigns/create` | CampaignController@create | campaigns.create | Create campaign |
| POST | `/campaigns` | CampaignController@store | - | Save campaign |
| GET | `/campaigns/{campaign}` | CampaignController@show | campaigns.show | Campaign details |
| GET | `/campaigns/{campaign}/edit` | CampaignController@edit | campaigns.edit | Edit campaign |
| PUT | `/campaigns/{campaign}` | CampaignController@update | - | Update campaign |
| DELETE | `/campaigns/{campaign}` | CampaignController@destroy | - | Delete campaign |
| GET | `/campaigns/performance-dashboard` | (closure) | campaigns.performance-dashboard | Performance dashboard |
| GET | `/campaigns/{campaign}/performance/{range}` | CampaignController@performanceByRange | JSON | Performance by range |

### Campaign Wizard

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/campaigns/wizard/create` | CampaignWizardController@create | wizard.create | Start wizard |
| GET | `/campaigns/wizard/{session_id}/step/{step}` | CampaignWizardController@showStep | wizard.step | Show step |
| POST | `/campaigns/wizard/{session_id}/step/{step}` | CampaignWizardController@updateStep | - | Update step |
| GET | `/campaigns/wizard/{session_id}/save-draft` | CampaignWizardController@saveDraft | - | Save draft |
| GET | `/campaigns/wizard/{session_id}/complete` | CampaignWizardController@complete | - | Complete wizard |

### Organizations

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/orgs` | OrgController@index | orgs.index | Organizations list |
| GET | `/orgs/create` | (closure) | orgs.create | Create org page |
| POST | `/orgs` | OrgController@store | - | Save org |
| GET | `/orgs/{org}` | OrgController@show | orgs.show | Org details |
| GET | `/orgs/{org}/edit` | OrgController@edit | orgs.edit | Edit org |
| PUT | `/orgs/{org}` | OrgController@update | - | Update org |
| GET | `/orgs/{org}/campaigns` | OrgController@campaigns | orgs.campaigns | Org campaigns |
| GET | `/orgs/{org}/campaigns/compare` | OrgController@compareCampaigns | orgs.campaigns.compare | Compare campaigns |
| POST | `/orgs/{org}/campaigns/export/pdf` | OrgController@exportComparePdf | (file) | Export PDF |
| POST | `/orgs/{org}/campaigns/export/excel` | OrgController@exportCompareExcel | (file) | Export Excel |

### Team Management (NEW)

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/orgs/{org}/team` | TeamController@index | orgs.team | Team members |
| POST | `/orgs/{org}/team/invite` | TeamController@invite | - | Invite member |

### Unified Inbox (NEW)

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/inbox` | UnifiedInboxController@index | inbox.index | Unified inbox |
| GET | `/inbox/comments` | UnifiedCommentsController@index | inbox.comments | Comments view |
| POST | `/inbox/comments/{comment_id}/reply` | UnifiedCommentsController@reply | - | Reply to comment |

### Analytics

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/analytics/enterprise` | EnterpriseAnalyticsController@enterprise | analytics.enterprise | Enterprise hub |
| GET | `/analytics/realtime` | EnterpriseAnalyticsController@realtime | analytics.realtime | Real-time dashboard |
| GET | `/analytics/campaigns` | EnterpriseAnalyticsController@campaigns | analytics.campaigns | Campaigns analytics |
| GET | `/analytics/kpis` | EnterpriseAnalyticsController@kpis | analytics.kpis | KPI dashboard |
| GET | `/analytics` | (redirect) | - | Redirects to enterprise |

### Creative

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/creative` | CreativeOverviewController@index | creative.index | Creative overview |
| GET | `/creative-assets` | CreativeAssetController@index | creative.assets | Assets library |
| GET | `/briefs` | CreativeBriefController@index | briefs.index | Creative briefs |
| GET | `/briefs/create` | CreativeBriefController@create | briefs.create | Create brief |
| POST | `/briefs` | CreativeBriefController@store | - | Save brief |
| GET | `/briefs/{briefId}` | CreativeBriefController@show | briefs.show | Brief details |
| POST | `/briefs/{briefId}/approve` | CreativeBriefController@approve | - | Approve brief |

### Settings

| Method | Endpoint | Controller@Method | View | Description |
|--------|----------|-------------------|------|-------------|
| GET | `/settings` | SettingsController@index | settings.index | Settings home |
| GET | `/settings/profile` | SettingsController@profile | settings.profile | Profile settings |
| GET | `/settings/notifications` | SettingsController@notifications | settings.notifications | Notification preferences |
| GET | `/settings/security` | SettingsController@security | settings.security | Security settings |
| GET | `/settings/integrations` | SettingsController@integrations | settings.integrations | Integration settings |

---

## Middleware Reference

### Authentication

- **`auth`** - Session-based authentication (web routes)
- **`auth:sanctum`** - Token-based authentication (API routes)
- **`guest`** - Only for unauthenticated users

### Multi-Tenancy

- **`org.context`** - Set organization context (recommended)
- **`resolve.active.org`** - Auto-resolve user's active organization (NEW)
- **`validate.org.access`** - Validate user has access to organization
- **`set.db.context`** - Set database context for RLS (deprecated, use `org.context`)
- **`set.rls.context`** - Set RLS context (deprecated, use `org.context`)

### Rate Limiting

- **`throttle:auth`** - Auth routes (10 attempts/min)
- **`throttle:api`** - General API (60 requests/min)
- **`throttle:webhooks`** - Webhooks (100 requests/min)
- **`throttle.ai`** - AI operations (30 requests/min, 500/hour)
- **`throttle.platform`** - Platform API calls (custom limits)
- **`ai.rate.limit`** - AI quota middleware

### Security

- **`verify.webhook`** - Verify webhook signatures
- **`security.headers`** - Add security headers
- **`permission`** - Check user permissions
- **`admin`** - Admin-only access

### Features

- **`feature.platform`** - Check if platform feature enabled
- **`check.ai.quota`** - Check AI quota before request

---

## Best Practices

### 1. Use Convenience Routes for Frontend

**❌ Don't:**
```javascript
// Hardcoding org_id everywhere
fetch(`/api/orgs/${orgId}/campaigns`)
fetch(`/api/orgs/${orgId}/analytics/summary`)
```

**✅ Do:**
```javascript
// Use convenience routes
fetch('/api/convenience/campaigns')
fetch('/api/convenience/analytics/summary')
```

### 2. Always Set Active Organization

When user logs in or switches org:

```php
$user->active_org_id = $orgId;
$user->save();
```

### 3. Respect Rate Limits

**AI Operations:**
- 30 requests/min per user
- 500 requests/hour per organization
- Use queues for batch operations

**Platform API:**
- Follow platform-specific limits
- Use exponential backoff on errors

### 4. Use Middleware Appropriately

**Multi-step middleware chain:**
```php
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])
```

**Convenience routes middleware:**
```php
Route::middleware(['auth:sanctum', 'resolve.active.org'])
```

### 5. Error Handling

Always return consistent JSON responses:

```json
{
  "success": false,
  "error": "Error type",
  "message": "Human-readable message",
  "errors": { /* validation errors */ }
}
```

### 6. Testing Routes

```bash
# List all routes
php artisan route:list

# Filter by name
php artisan route:list --name=api.org

# Filter by middleware
php artisan route:list --middleware=auth:sanctum
```

---

## Changelog

### 2025-11-22 (Current)
- ✅ Added Convenience Routes (`/api/convenience/*`)
- ✅ Created `ResolveActiveOrg` middleware
- ✅ Added Team Management web routes
- ✅ Added Unified Inbox web routes
- ✅ Fixed home page route conflict
- ✅ Added placeholder routes for future features

### 2025-11-21
- Added Phase documentation organization
- Completed duplication elimination initiative

### Earlier
- Initial multi-tenancy architecture
- RLS implementation
- Platform integrations
- AI features

---

## Support

For issues or questions:
- Check `CLAUDE.md` for project guidelines
- Review `docs/analysis/COMPREHENSIVE_ROUTES_ANALYSIS.md` for detailed analysis
- See `docs/phases/` for phase-specific documentation

**Last Updated:** 2025-11-22
**Maintainer:** CMIS Development Team
