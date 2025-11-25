# Multi-Organization URL-Based Access Migration Plan

**Date:** 2025-11-25
**Status:** In Progress
**Goal:** Enable users to work with multiple organizations simultaneously via URL-based org_id

## Executive Summary

Migrating from session-based organization context to URL-based org_id to allow:
- Users working with multiple organizations in different browser tabs
- Direct linking to specific organization resources
- Better API clarity and RESTful design
- Reading data from multiple organizations simultaneously

## Changes Made

### ✅ Phase 1: Core Infrastructure (Completed)

1. **Disabled Global OrgScope**
   - File: `app/Models/Scopes/OrgScope.php`
   - Change: Disabled automatic org filtering in `apply()` method
   - Reason: Allows queries to access multiple orgs when needed
   - Note: Org filtering now handled explicitly in controllers

2. **Updated SetOrganizationContext Middleware**
   - File: `app/Http/Middleware/SetOrganizationContext.php`
   - Change: Priority order for org_id extraction:
     1. URL route parameter (`{org}` or `{org_id}`)
     2. User's `current_org_id`
     3. User's `org_id`
   - Benefit: Automatic multi-org support for routes with org parameter

3. **Existing ValidateOrgAccess Middleware**
   - File: `app/Http/Middleware/ValidateOrgAccess.php`
   - Already validates user has access to requested org
   - Works with URL-based org_id extraction

### 🔄 Phase 2: Route Restructuring (In Progress)

#### Current Route Analysis

**Web Routes (routes/web.php - 280 lines)**
- Total routes: ~80+ routes
- Already org-prefixed: `/orgs/{org}/*` routes (lines 114-129)
- Need org-prefix: Dashboard, Campaigns, Analytics, Creative, AI, Knowledge, Workflows, Social

**API Routes (routes/api.php - 2,433 lines)**
- Total routes: 927 route definitions
- Already org-prefixed: Some routes at lines 195, 1373, 1526
- Need org-prefix: Most API endpoints

#### Route Restructuring Strategy

**Principle:** All organization-specific resources should be under `/orgs/{org}/*`

**Exceptions (No org_id needed):**
- Authentication routes (`/login`, `/register`, `/logout`)
- Webhook endpoints (`/webhooks/*`) - use credentials for org identification
- Public documentation (`/api/documentation`)
- Invitation acceptance (`/invitations/*`)
- Organization selection/listing (`/orgs` - list user's orgs)

**Route Structure:**

```
# Web Routes
GET  /orgs/{org}/dashboard
GET  /orgs/{org}/campaigns
GET  /orgs/{org}/campaigns/{campaign}
GET  /orgs/{org}/analytics
GET  /orgs/{org}/creative
GET  /orgs/{org}/ai
GET  /orgs/{org}/knowledge
GET  /orgs/{org}/social
GET  /orgs/{org}/settings

# API Routes
GET    /api/v1/orgs/{org}/campaigns
POST   /api/v1/orgs/{org}/campaigns
GET    /api/v1/orgs/{org}/campaigns/{campaign}
PUT    /api/v1/orgs/{org}/campaigns/{campaign}
DELETE /api/v1/orgs/{org}/campaigns/{campaign}

# Multi-org queries (special endpoints)
POST   /api/v1/multi-org/campaigns/compare
POST   /api/v1/multi-org/analytics/aggregate
```

### 📋 Phase 3: Controller Updates (Pending)

#### Changes Needed

1. **Extract org_id from route:**
   ```php
   public function index(Request $request, string $org)
   {
       $orgId = $org; // or $request->route('org')
       // Use $orgId explicitly in queries
       $campaigns = Campaign::where('org_id', $orgId)->get();
   }
   ```

2. **Remove reliance on session:**
   - Remove `session('current_org_id')` calls
   - Use route parameter instead

3. **Add explicit org filtering:**
   - Since OrgScope is disabled, add `where('org_id', $orgId)` to queries
   - Or use eloquent relationships with org context

#### Controllers to Update

**High Priority (Core Functionality):**
- `DashboardController` - dashboard data
- `CampaignController` - campaign management
- `OrgController` - organization management (partial, already has some org routes)
- `AnalyticsController` - analytics data
- `CreativeAssetController` - creative assets
- `AIGenerationController` - AI operations
- `KnowledgeController` - knowledge base

**Medium Priority (Feature Areas):**
- `CreativeBriefController`
- `WorkflowController`
- `SocialSchedulerController`
- `IntegrationController`
- `ChannelController`
- `AdCampaignController`

**Lower Priority (Admin/Settings):**
- `SettingsController`
- `SubscriptionController`
- `ProfileController`

### 📋 Phase 4: View Updates (Pending)

#### Changes Needed

1. **Update links to include org_id:**
   ```blade
   {{-- Before --}}
   <a href="{{ route('campaigns.index') }}">Campaigns</a>

   {{-- After --}}
   <a href="{{ route('campaigns.index', ['org' => $currentOrg->org_id]) }}">Campaigns</a>
   ```

2. **Update form actions:**
   ```blade
   <form action="{{ route('campaigns.store', ['org' => $currentOrg->org_id]) }}" method="POST">
   ```

3. **Pass org_id to all views:**
   ```php
   // In controllers
   return view('campaigns.index', [
       'org' => $org,
       'campaigns' => $campaigns
   ]);
   ```

#### Views to Update

- `resources/views/layouts/*.blade.php` - navigation menus
- `resources/views/dashboard/*.blade.php`
- `resources/views/campaigns/*.blade.php`
- `resources/views/analytics/*.blade.php`
- `resources/views/creative/*.blade.php`
- `resources/views/ai/*.blade.php`
- `resources/views/knowledge/*.blade.php`
- `resources/views/workflows/*.blade.php`
- `resources/views/social/*.blade.php`

### 📋 Phase 5: RLS Policy Updates (Pending)

#### Current RLS Approach

PostgreSQL RLS policies currently use:
```sql
current_setting('app.current_org_id')
```

This is set by `cmis.init_transaction_context(user_id, org_id)` in middleware.

#### No Changes Needed!

The `SetOrganizationContext` middleware already:
1. Extracts org_id from URL (after our update)
2. Calls `cmis.init_transaction_context(user_id, org_id)`
3. RLS policies automatically use the org_id from URL

**Why this works:**
- Middleware sets RLS context to org_id from URL
- All queries within that request use that org context
- Different browser tabs/requests can have different org contexts
- RLS still provides security isolation

### 📋 Phase 6: Multi-Org Query Support (Future)

For endpoints that need to query multiple orgs:

1. **Option A: Separate Endpoint**
   ```php
   // POST /api/v1/multi-org/campaigns/compare
   public function compareMultiOrg(Request $request)
   {
       $orgIds = $request->input('org_ids'); // [org1, org2, org3]

       // Validate user has access to all orgs
       foreach ($orgIds as $orgId) {
           // Check access
       }

       // Query without RLS, explicit filtering
       $campaigns = DB::table('cmis.campaigns')
           ->whereIn('org_id', $orgIds)
           ->get();
   }
   ```

2. **Option B: Disable RLS for specific queries**
   ```php
   DB::statement("SET LOCAL row_security = OFF");
   // Run multi-org query with explicit filtering
   DB::statement("SET LOCAL row_security = ON");
   ```

## Implementation Priority

### Sprint 1: Core Routes (2-3 days)
1. ✅ Update middleware (completed)
2. ✅ Disable OrgScope (completed)
3. Update web.php routes for:
   - Dashboard
   - Campaigns
   - Organizations
4. Update corresponding controllers
5. Update navigation views

### Sprint 2: Feature Routes (3-4 days)
1. Update API routes for campaigns, analytics
2. Update controllers for API endpoints
3. Test multi-org access

### Sprint 3: Remaining Routes (2-3 days)
1. Update creative, AI, knowledge routes
2. Update social, workflow routes
3. Update all remaining views

### Sprint 4: Polish & Testing (2-3 days)
1. Comprehensive testing
2. Fix any edge cases
3. Update documentation
4. Deploy to staging

## Testing Strategy

### Manual Testing
- [ ] Open org 1 in tab 1, org 2 in tab 2
- [ ] Verify data isolation
- [ ] Test all major features in both tabs
- [ ] Verify URLs contain org_id

### Automated Testing
- [ ] Update existing tests to include org_id in routes
- [ ] Add multi-org access tests
- [ ] Test RLS still works correctly

### Security Testing
- [ ] Verify user can't access org they don't belong to
- [ ] Test RLS policies prevent data leakage
- [ ] Validate all org_id inputs

## Rollback Plan

If issues arise:
1. Re-enable OrgScope (`remove` the early return)
2. Revert middleware changes
3. Deploy previous version

## Notes & Considerations

1. **Backward Compatibility:**
   - Old bookmarks will break (no org_id in URL)
   - Consider redirect from `/campaigns` to `/orgs/{default_org}/campaigns`

2. **Performance:**
   - URL routing overhead is minimal
   - RLS performance unchanged (still uses same mechanism)

3. **User Experience:**
   - URLs are longer but more explicit
   - Easier to share specific org resources
   - Better for bookmarking

4. **API Clients:**
   - Breaking change for API consumers
   - Need to update API documentation
   - Consider versioning (v1 vs v2)

## Questions & Decisions

- **Q:** Should we support both old and new route formats during transition?
  - **A:** TBD - discuss with team

- **Q:** How to handle user's default org?
  - **A:** Store in `users.current_org_id`, use as fallback if no URL param

- **Q:** What about routes that don't have org context (e.g., /profile)?
  - **A:** Keep those without org_id, but major features need org_id

## References

- CLAUDE.md - Project guidelines
- MULTI_TENANCY_PATTERNS.md - RLS documentation
- Middleware: SetOrganizationContext.php
- Middleware: ValidateOrgAccess.php
