# CMIS (Cognitive Marketing Intelligence System)
# 📋 DETAILED IMPLEMENTATION PLAN

**Project:** CMIS Laravel Backend & Frontend Completion
**Timeline:** 16 Weeks (4 Months)
**Status:** Implementation Ready
**Generated:** 2025-11-12

---

## 🎯 PROJECT OVERVIEW

This implementation plan provides concrete, actionable steps to complete the CMIS system, bridging the gap between the comprehensive PostgreSQL schema (170 tables, 119 functions) and the partially implemented Laravel application (21 models, 39 controllers).

### Current State
- ✅ Database: 100% complete and production-ready
- ⚠️ Backend: ~25% complete (foundation laid, needs expansion)
- ⚠️ Frontend: ~70% complete (modern UI exists, needs integration)
- ❌ Integration: ~15% complete (OAuth started, platforms not connected)
- ❌ AI/ML: ~10% complete (infrastructure exists, not functional)

### Target State
- ✅ Fully functional CMIS system
- ✅ All database tables represented and accessible
- ✅ Complete authorization and security
- ✅ Working integrations with all platforms
- ✅ Operational AI/ML features
- ✅ Production-ready application

---

## 📅 PHASE 1: SECURITY & FOUNDATION (Weeks 1-2)

**Goal:** Establish security foundation and core infrastructure

### Week 1: Authorization System

#### Day 1-2: Permission System Models

**Create Models:**

```php
// app/Models/Permission.php
class Permission extends Model
{
    protected $table = 'cmis.permissions';
    protected $primaryKey = 'permission_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'permission_code', 'permission_name', 'description',
        'module', 'resource', 'action', 'is_system'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'cmis.role_permissions',
            'permission_id', 'role_id')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cmis.user_permissions',
            'permission_id', 'user_id')
            ->withPivot('is_granted', 'expires_at', 'granted_by')
            ->withTimestamps();
    }
}

// app/Models/RolePermission.php
class RolePermission extends Model
{
    protected $table = 'cmis.role_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'role_id', 'permission_id', 'granted_by'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}

// app/Models/UserPermission.php
class UserPermission extends Model
{
    protected $table = 'cmis.user_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'permission_id', 'is_granted',
        'expires_at', 'granted_by'
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'expires_at' => 'datetime',
    ];
}

// app/Models/PermissionsCache.php
class PermissionsCache extends Model
{
    protected $table = 'cmis.permissions_cache';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'org_id', 'permission_code',
        'has_permission', 'last_used'
    ];

    protected $casts = [
        'has_permission' => 'boolean',
        'last_used' => 'datetime',
    ];
}
```

**Update Role Model:**
```php
// app/Models/Core/Role.php - Add relationship
public function permissions()
{
    return $this->belongsToMany(Permission::class, 'cmis.role_permissions',
        'role_id', 'permission_id')
        ->withPivot('granted_by')
        ->withTimestamps();
}
```

**Update User Model:**
```php
// app/Models/User.php - Add methods
public function permissions()
{
    return $this->belongsToMany(Permission::class, 'cmis.user_permissions',
        'user_id', 'permission_id')
        ->withPivot('is_granted', 'expires_at', 'granted_by')
        ->withTimestamps();
}

public function hasPermission(string $permissionCode): bool
{
    $orgId = session('current_org_id');
    if (!$orgId) return false;

    $result = DB::selectOne(
        'SELECT cmis.check_permission(?, ?, ?) as has_permission',
        [$this->user_id, $orgId, $permissionCode]
    );

    return (bool) $result->has_permission;
}

public function can($ability, $arguments = [])
{
    // Integrate with Laravel's Gate system
    if (str_starts_with($ability, 'cmis.')) {
        return $this->hasPermission($ability);
    }
    return parent::can($ability, $arguments);
}
```

**Action Items:**
- [x] Create Permission model ✅ COMPLETED (2025-11-19) - `/app/Models/Security/Permission.php`
- [x] Create RolePermission model ✅ COMPLETED (2025-11-19) - Pivot relationship in Permission.php
- [x] Create UserPermission model ✅ COMPLETED (2025-11-19) - Pivot relationship in Permission.php
- [x] Create PermissionsCache model ✅ COMPLETED (2025-11-19) - `/app/Models/Security/PermissionsCache.php`
- [x] Update Role model with permissions relationship ✅ COMPLETED (2025-11-19)
- [x] Update User model with permissions methods ✅ COMPLETED (2025-11-19)
- [x] Test models and relationships ✅ COMPLETED (2025-11-20)

#### Day 3-4: Permission Service & Middleware

**Create Permission Service:**

```php
// app/Services/PermissionService.php
<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Check if user has permission using DB function
     */
    public function check(User $user, string $permissionCode): bool
    {
        $orgId = session('current_org_id');
        if (!$orgId) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT cmis.check_permission(?, ?, ?) as has_permission',
            [$user->user_id, $orgId, $permissionCode]
        );

        return (bool) $result->has_permission;
    }

    /**
     * Check permission using transaction context
     */
    public function checkTx(string $permissionCode): bool
    {
        $result = DB::selectOne(
            'SELECT cmis.check_permission_tx(?) as has_permission',
            [$permissionCode]
        );

        return (bool) $result->has_permission;
    }

    /**
     * Grant permission to role
     */
    public function grantToRole(Role $role, Permission $permission, User $grantedBy): void
    {
        $role->permissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'granted_by' => $grantedBy->user_id,
                'created_at' => now(),
            ]
        ]);
    }

    /**
     * Revoke permission from role
     */
    public function revokeFromRole(Role $role, Permission $permission): void
    {
        $role->permissions()->detach($permission->permission_id);
    }

    /**
     * Grant permission to user
     */
    public function grantToUser(User $user, Permission $permission, User $grantedBy, ?\DateTime $expiresAt = null): void
    {
        $user->permissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'is_granted' => true,
                'expires_at' => $expiresAt,
                'granted_by' => $grantedBy->user_id,
                'created_at' => now(),
            ]
        ]);
    }

    /**
     * Revoke permission from user
     */
    public function revokeFromUser(User $user, Permission $permission): void
    {
        $user->permissions()->updateExistingPivot($permission->permission_id, [
            'is_granted' => false,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get all permissions for user in org
     */
    public function getUserPermissions(User $user, string $orgId): array
    {
        return Cache::remember(
            "user_permissions:{$user->user_id}:{$orgId}",
            now()->addMinutes(10),
            function () use ($user, $orgId) {
                $userOrg = $user->orgs()
                    ->where('cmis.orgs.org_id', $orgId)
                    ->with('role.permissions')
                    ->first();

                if (!$userOrg || !$userOrg->role) {
                    return [];
                }

                $rolePermissions = $userOrg->role->permissions->pluck('permission_code')->toArray();

                // Add user-specific permissions
                $userPermissions = $user->permissions()
                    ->wherePivot('is_granted', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->pluck('permission_code')
                    ->toArray();

                return array_unique(array_merge($rolePermissions, $userPermissions));
            }
        );
    }

    /**
     * Refresh permissions cache for user
     */
    public function refreshCache(User $user, string $orgId): void
    {
        Cache::forget("user_permissions:{$user->user_id}:{$orgId}");

        // Call DB function to refresh
        DB::select('SELECT cmis.refresh_permissions_cache()');
    }
}
```

**Create Permission Middleware:**

```php
// app/Http/Middleware/CheckPermission.php
<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            Log::warning('Permission check failed: User not authenticated');
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        if (!session()->has('current_org_id')) {
            Log::warning('Permission check failed: No org context', [
                'user_id' => auth()->id(),
                'permission' => $permission
            ]);
            return response()->json([
                'error' => 'Organization context not set'
            ], 400);
        }

        // Use transaction context version if available
        try {
            $hasPermission = $this->permissionService->checkTx($permission);
        } catch (\Exception $e) {
            Log::error('Permission check error', [
                'user_id' => auth()->id(),
                'permission' => $permission,
                'error' => $e->getMessage()
            ]);

            // Fallback to direct check
            $hasPermission = $this->permissionService->check(auth()->user(), $permission);
        }

        if (!$hasPermission) {
            Log::warning('Permission denied', [
                'user_id' => auth()->id(),
                'org_id' => session('current_org_id'),
                'permission' => $permission,
                'route' => $request->path()
            ]);

            return response()->json([
                'error' => 'Insufficient permissions',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
```

**Register Middleware:**

```php
// app/Http/Kernel.php - Add to $middlewareAliases
'permission' => \App\Http\Middleware\CheckPermission::class,
```

**Action Items:**
- [x] Create PermissionService ✅ COMPLETED (2025-11-19) - Via Laravel Policies
- [x] Create CheckPermission middleware ✅ COMPLETED (2025-11-19) - Laravel authorization
- [x] Register middleware in Kernel ✅ COMPLETED (2025-11-19)
- [ ] Update routes with permission middleware ⏳ PARTIAL - Some routes protected
- [ ] Test permission checking ⏳ PARTIAL - Basic tests exist
- [ ] Add audit logging for permission checks ⏳ PLANNED

#### Day 5: Policy Classes

**Create Base Policy:**

```php
// app/Policies/BasePolicy.php
<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

abstract class BasePolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Check permission using transaction context
     */
    protected function checkPermission(string $permissionCode): bool
    {
        return $this->permissionService->checkTx($permissionCode);
    }

    /**
     * Check if user is owner or admin
     */
    protected function isOwnerOrAdmin(User $user, string $orgId): bool
    {
        return $user->hasRoleInOrg($orgId, 'owner')
            || $user->hasRoleInOrg($orgId, 'admin');
    }
}
```

**Create Campaign Policy:**

```php
// app/Policies/CampaignPolicy.php
<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission('campaigns.view');
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->org_id === session('current_org_id')
            && $this->checkPermission('campaigns.view');
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission('campaigns.create');
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $campaign->org_id === session('current_org_id')
            && $this->checkPermission('campaigns.edit');
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->org_id === session('current_org_id')
            && $this->checkPermission('campaigns.delete');
    }

    /**
     * Determine whether the user can restore the campaign.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        return $campaign->org_id === session('current_org_id')
            && $this->checkPermission('campaigns.delete');
    }
}
```

**Create Additional Policies:**

```php
// app/Policies/CreativeAssetPolicy.php
// app/Policies/IntegrationPolicy.php
// app/Policies/OrganizationPolicy.php
// app/Policies/UserPolicy.php
// Similar structure to CampaignPolicy
```

**Register Policies:**

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Campaign::class => CampaignPolicy::class,
    CreativeAsset::class => CreativeAssetPolicy::class,
    Integration::class => IntegrationPolicy::class,
    Org::class => OrganizationPolicy::class,
    User::class => UserPolicy::class,
];
```

**Update Controllers:**

```php
// app/Http/Controllers/Campaigns/CampaignController.php
public function index($orgId)
{
    $this->authorize('viewAny', Campaign::class);
    // ... rest of implementation
}

public function store($orgId, Request $request)
{
    $this->authorize('create', Campaign::class);
    // ... rest of implementation
}

public function update($orgId, $campaignId, Request $request)
{
    $campaign = Campaign::findOrFail($campaignId);
    $this->authorize('update', $campaign);
    // ... rest of implementation
}
```

**Action Items:**
- [x] Create BasePolicy ✅ COMPLETED (2025-11-19) - `/app/Policies/BasePolicy.php`
- [x] Create CampaignPolicy ✅ COMPLETED (2025-11-19) - `/app/Policies/CampaignPolicy.php`
- [x] Create CreativeAssetPolicy ✅ COMPLETED (2025-11-19) - `/app/Policies/CreativeAssetPolicy.php`
- [x] Create IntegrationPolicy ✅ COMPLETED (2025-11-19) - `/app/Policies/IntegrationPolicy.php`
- [x] Create OrganizationPolicy ✅ COMPLETED (2025-11-19) - `/app/Policies/OrganizationPolicy.php`
- [x] Create UserPolicy ✅ COMPLETED (2025-11-19) - `/app/Policies/UserPolicy.php`
- [x] Register all policies ✅ COMPLETED (2025-11-19) - 12 policies registered
- [ ] Add authorize() calls to all controllers 🔄 IN PROGRESS - Some controllers done
- [ ] Test authorization flow 🔄 IN PROGRESS - Basic tests exist

### Week 2: Authentication & Core Models

#### Day 6-7: Authentication System

**Install Laravel Breeze:**

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan migrate
```

**Customize Auth Views:**

```bash
# Migrate auth views to use admin.blade.php layout
# Update resources/views/auth/*.blade.php
```

**Update Auth Controllers:**

```php
// app/Http/Controllers/Auth/AuthController.php
// Integrate with cmis.users table
// Set up multi-org authentication
// Initialize session context after login
```

**Action Items:**
- [ ] Install and configure Laravel Breeze
- [ ] Customize auth views to match admin layout
- [ ] Update authentication to use cmis.users
- [ ] Add multi-org selection after login
- [ ] Initialize security context on login
- [ ] Test authentication flow
- [ ] Add remember me functionality
- [ ] Add email verification

#### Day 8-10: Context System Models

**Create Context Models:**

```php
// app/Models/ContextBase.php
class ContextBase extends Model
{
    protected $table = 'cmis.contexts_base';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    use SoftDeletes;

    protected $fillable = [
        'org_id', 'name', 'description', 'context_type',
        'metadata', 'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

// app/Models/CreativeContext.php (extends ContextBase pattern)
// app/Models/OfferingContext.php
// app/Models/ValueContext.php (update existing if exists)
// app/Models/CampaignContextLink.php
// app/Models/FieldDefinition.php
// app/Models/FieldValue.php
```

**Action Items:**
- [x] Create ContextBase model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/ContextBase.php`
- [x] Create CreativeContext model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/CreativeContext.php`
- [x] Create OfferingContext model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/OfferingContext.php`
- [x] Update ValueContext model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/ValueContext.php`
- [x] Create CampaignContextLink model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/CampaignContextLink.php`
- [x] Create FieldDefinition model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/FieldDefinition.php`
- [x] Create FieldValue model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/FieldValue.php`
- [x] Create FieldAlias model ✅ COMPLETED (2025-11-19) - `/app/Models/Context/FieldAlias.php`
- [ ] Update Campaign model with context relationships 🔄 PARTIAL - Basic relationships exist
- [ ] Update CreativeAsset model with context relationships 🔄 PARTIAL - Basic relationships exist
- [ ] Test context system ⏳ PLANNED
- [ ] Create context seeder with test data ⏳ PLANNED

---

## 📅 PHASE 2: CORE FEATURES (Weeks 3-4)

**Goal:** Implement campaign management, creative system, and user management

### Week 3: Campaign System Enhancement

#### Day 11-12: Campaign Context Integration

**Update CampaignController:**

```php
// Integrate create_campaign_and_context_safe() function
public function store($orgId, Request $request)
{
    $this->authorize('create', Campaign::class);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'offering_id' => 'required|uuid',
        'segment_id' => 'nullable|uuid',
        'framework' => 'required|string',
        'tone' => 'required|string',
        'tags' => 'nullable|array',
    ]);

    // Use DB function for atomic creation
    $result = DB::selectOne(
        'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?, ?)',
        [
            $orgId,
            $validated['offering_id'],
            $validated['segment_id'] ?? null,
            $validated['name'],
            $validated['framework'],
            $validated['tone'],
            json_encode($validated['tags'] ?? [])
        ]
    );

    if (!$result->success) {
        return response()->json([
            'error' => $result->error_message
        ], 400);
    }

    return response()->json([
        'campaign_id' => $result->campaign_id,
        'message' => 'Campaign created successfully'
    ], 201);
}
```

**Create Context Search:**

```php
// app/Http/Controllers/ContextController.php
public function search(Request $request)
{
    $validated = $request->validate([
        'query' => 'required|string',
        'context_type' => 'nullable|string',
        'limit' => 'integer|max:100'
    ]);

    $results = DB::select(
        'SELECT * FROM cmis.search_contexts(?, ?, ?)',
        [
            $validated['query'],
            $validated['context_type'] ?? null,
            $validated['limit'] ?? 20
        ]
    );

    return response()->json($results);
}

public function relatedCampaigns($campaignId)
{
    $results = DB::select(
        'SELECT * FROM cmis.find_related_campaigns(?, ?)',
        [$campaignId, 10]
    );

    return response()->json($results);
}
```

**Action Items:**
- [ ] Update CampaignController with DB function integration
- [ ] Create ContextController
- [ ] Add context search endpoint
- [ ] Add related campaigns endpoint
- [ ] Update campaign views with context selection
- [ ] Add context tagging UI
- [ ] Test campaign creation with contexts

#### Day 13-14: Campaign Performance & Comparison

**Enhance Performance Tracking:**

```php
// app/Services/CampaignService.php
class CampaignService
{
    public function trackPerformance(Campaign $campaign, array $metrics): void
    {
        foreach ($metrics as $kpi => $data) {
            PerformanceMetric::create([
                'org_id' => $campaign->org_id,
                'campaign_id' => $campaign->campaign_id,
                'kpi' => $kpi,
                'observed' => $data['observed'],
                'target' => $data['target'] ?? null,
                'baseline' => $data['baseline'] ?? null,
                'observed_at' => now(),
            ]);
        }
    }

    public function comparePerformance(array $campaignIds, string $kpi): array
    {
        return PerformanceMetric::whereIn('campaign_id', $campaignIds)
            ->where('kpi', $kpi)
            ->with('campaign:campaign_id,name')
            ->get()
            ->groupBy('campaign_id')
            ->map(function ($metrics) {
                return [
                    'campaign' => $metrics->first()->campaign->name,
                    'average' => $metrics->avg('observed'),
                    'trend' => $this->calculateTrend($metrics),
                ];
            })->values();
    }
}
```

**Action Items:**
- [ ] Create CampaignService
- [ ] Implement performance tracking
- [ ] Enhance comparison functionality
- [ ] Add time-series performance charts
- [ ] Test performance APIs

### Week 4: Creative System & User Management

#### Day 15-17: Creative System

**Create Creative Models:**

```php
// app/Models/CreativeBrief.php
// app/Models/CreativeOutput.php
// app/Models/ContentItem.php
// app/Models/ContentPlan.php
// app/Models/CopyComponent.php
// app/Models/VideoTemplate.php
// app/Models/VideoScene.php
```

**Create Controllers:**

```php
// app/Http/Controllers/Creative/CreativeBriefController.php
// app/Http/Controllers/Creative/ContentItemController.php
// app/Http/Controllers/Creative/CopyComponentController.php
```

**Integrate Brief Validation:**

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'brief_data' => 'required|json'
    ]);

    // Validate using DB function
    $isValid = DB::selectOne(
        'SELECT cmis.validate_brief_structure(?::jsonb) as is_valid',
        [$validated['brief_data']]
    );

    if (!$isValid->is_valid) {
        return response()->json([
            'error' => 'Brief structure is invalid'
        ], 422);
    }

    // Create brief
    // ...
}
```

**Action Items:**
- [ ] Create all creative models
- [ ] Create creative controllers
- [ ] Integrate brief validation
- [ ] Add file upload handling
- [ ] Create copy library UI
- [ ] Test creative workflows

#### Day 18-20: User Management

**Create User Views:**

```blade
<!-- resources/views/users/index.blade.php -->
<!-- resources/views/users/show.blade.php -->
<!-- resources/views/users/create.blade.php -->
<!-- resources/views/users/profile.blade.php -->
```

**Enhance UserController:**

```php
// Complete invitation system
// Add role management
// Add activity tracking
// Add user deactivation
```

**Action Items:**
- [ ] Create all user management views
- [ ] Complete UserController
- [ ] Add email invitation system
- [ ] Add role assignment UI
- [ ] Add user activity logging
- [ ] Test user management flow

---

## 📅 PHASE 3: INTEGRATIONS & SOCIAL (Weeks 5-6)

**Goal:** Complete OAuth integration and social media scheduling

### Week 5: Platform Integration

#### Day 21-23: OAuth Implementation

**Complete OAuth for All Platforms:**

```php
// app/Services/OAuth/
// - FacebookOAuthService.php
// - InstagramOAuthService.php
// - GoogleOAuthService.php
// - TikTokOAuthService.php
// - LinkedInOAuthService.php
// - TwitterOAuthService.php
```

**Update IntegrationController:**

```php
// Complete all OAuth flows
// Add token refresh logic
// Add connection testing
// Add error handling
```

**Action Items:**
- [ ] Create OAuth service for each platform
- [ ] Implement token encryption
- [ ] Add token refresh scheduling
- [ ] Add connection status checking
- [ ] Test OAuth flow for all platforms
- [ ] Add OAuth error handling
- [ ] Create OAuth status dashboard

#### Day 24-25: Data Synchronization

**Create Sync Services:**

```php
// app/Services/Sync/
// - MetaSyncService.php
// - GoogleAdsSyncService.php
// - TikTokSyncService.php
// - etc.
```

**Create Sync Jobs:**

```php
// app/Jobs/
// - SyncPlatformData.php
// - SyncMetrics.php
// - SyncAudiences.php
```

**Schedule Syncs:**

```php
// app/Console/Kernel.php
$schedule->job(new SyncPlatformData('facebook'))->hourly();
$schedule->job(new SyncPlatformData('instagram'))->hourly();
// etc.
```

**Action Items:**
- [ ] Create sync service for each platform
- [ ] Create sync jobs
- [ ] Schedule sync jobs
- [ ] Add sync progress tracking
- [ ] Add sync error logging
- [ ] Test data synchronization

### Week 6: Social Media Management

#### Day 26-28: Social Scheduler

**Complete SocialSchedulerController:**

```php
// Implement platform-specific publishing
// Add media upload handling
// Add post preview generation
// Add scheduling queue processing
```

**Create Publishing Service:**

```php
// app/Services/Social/PublishingService.php
class PublishingService
{
    public function publish(ScheduledSocialPost $post): array
    {
        $results = [];

        foreach ($post->platforms as $platform) {
            try {
                $adapter = $this->getAdapter($platform);
                $publishedId = $adapter->publish(
                    $post->content,
                    $post->media
                );

                $results[$platform] = [
                    'success' => true,
                    'published_id' => $publishedId
                ];
            } catch (\Exception $e) {
                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
```

**Create Publishing Job:**

```php
// app/Jobs/PublishScheduledPost.php
class PublishScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ScheduledSocialPost $post;

    public function handle(PublishingService $publishingService)
    {
        $this->post->markAsPublishing();

        $results = $publishingService->publish($this->post);

        $publishedIds = collect($results)
            ->where('success', true)
            ->pluck('published_id')
            ->toArray();

        if (count($publishedIds) > 0) {
            $this->post->markAsPublished($publishedIds);
        } else {
            $errors = collect($results)->pluck('error')->join(', ');
            $this->post->markAsFailed($errors);
        }
    }
}
```

**Schedule Post Publishing:**

```php
// app/Console/Kernel.php
$schedule->call(function () {
    $postsToPublish = ScheduledSocialPost::where('status', 'scheduled')
        ->where('scheduled_at', '<=', now())
        ->get();

    foreach ($postsToPublish as $post) {
        PublishScheduledPost::dispatch($post);
    }
})->everyMinute();
```

**Action Items:**
- [ ] Complete SocialSchedulerController
- [ ] Create PublishingService
- [ ] Create platform adapters
- [ ] Create PublishScheduledPost job
- [ ] Add media upload handling
- [ ] Add post preview generation
- [ ] Schedule publishing job
- [ ] Connect frontend to backend
- [ ] Test scheduling and publishing

#### Day 29-30: Ad Platform Integration

**Create Ad Models:**

```php
// app/Models/AdAccount.php
// app/Models/AdCampaign.php
// app/Models/AdSet.php
// app/Models/AdEntity.php
// app/Models/AdAudience.php
// app/Models/AdMetric.php
```

**Create Ad Sync Services:**

```php
// app/Services/Ads/MetaAdsService.php
// app/Services/Ads/GoogleAdsService.php
```

**Action Items:**
- [ ] Create ad platform models
- [ ] Create ad sync services
- [ ] Add ad metrics collection
- [ ] Create ad performance dashboard
- [ ] Test ad platform integration

---

## 📅 PHASE 4: AI & KNOWLEDGE BASE (Weeks 7-8)

**Goal:** Implement AI features and knowledge base

### Week 7: Knowledge Base Implementation

#### Day 31-33: Knowledge Models & Vector Search

**Create Knowledge Models:**

```php
// app/Models/Knowledge/KnowledgeIndex.php
class KnowledgeIndex extends Model
{
    protected $table = 'cmis_knowledge.index';
    protected $connection = 'pgsql';

    protected $casts = [
        'topic_embedding' => 'array',  // Will need custom cast for vector
        'intent_vector' => 'array',
        'direction_vector' => 'array',
        'purpose_vector' => 'array',
        'keywords_embedding' => 'array',
    ];

    public function scopeSimilarTo($query, string $searchText, float $threshold = 0.7)
    {
        // Will implement semantic search
    }
}

// app/Models/Knowledge/DevKnowledge.php
// app/Models/Knowledge/MarketingKnowledge.php
// app/Models/Knowledge/EmbeddingsCache.php
// app/Models/Knowledge/EmbeddingUpdateQueue.php
```

**Create Vector Cast:**

```php
// app/Casts/VectorCast.php
<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class VectorCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        // PostgreSQL vector format: [1,2,3]
        $value = trim($value, '[]');
        return array_map('floatval', explode(',', $value));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return '[' . implode(',', $value) . ']';
        }

        return $value;
    }
}
```

**Create Semantic Search Service:**

```php
// app/Services/CMIS/SemanticSearchService.php
class SemanticSearchService
{
    public function search(
        string $query,
        ?string $domain = null,
        ?string $category = null,
        ?string $tier = null,
        int $limit = 20,
        float $threshold = 0.7
    ): array {
        $results = DB::select(
            'SELECT * FROM cmis_knowledge.semantic_search_advanced(?, ?, ?, ?, ?, ?, ?)',
            [
                $query,
                $domain,
                $category,
                $tier,
                null, // direction
                $limit,
                $threshold
            ]
        );

        return $results;
    }

    public function loadContext(
        string $prompt,
        ?string $domain = null,
        ?string $category = null,
        int $tokenLimit = 2000
    ): array {
        $results = DB::select(
            'SELECT * FROM cmis_knowledge.smart_context_loader(?, ?, ?, ?)',
            [
                $prompt,
                $domain,
                $category,
                $tokenLimit
            ]
        );

        return $results;
    }
}
```

**Action Items:**
- [ ] Create all knowledge base models
- [ ] Create VectorCast for pgvector support
- [ ] Update SemanticSearchService
- [ ] Create knowledge API endpoints
- [ ] Test vector search
- [ ] Add search logging

#### Day 34-35: Embedding Generation

**Create Embedding Service:**

```php
// app/Services/CMIS/EmbeddingService.php
class EmbeddingService
{
    protected string $apiKey;
    protected string $apiEndpoint;

    public function generate(string $text): array
    {
        // Check cache first
        $hash = md5($text);
        $cached = EmbeddingsCache::where('input_hash', $hash)->first();

        if ($cached) {
            $cached->update(['last_used_at' => now()]);
            return json_decode($cached->embedding, true);
        }

        // Call DB function which calls API
        $result = DB::selectOne(
            'SELECT cmis_knowledge.generate_embedding_improved(?) as embedding',
            [$text]
        );

        return json_decode($result->embedding, true);
    }

    public function queueUpdate(string $table, string $id, string $content, int $priority = 5): void
    {
        EmbeddingUpdateQueue::create([
            'source_table' => $table,
            'source_id' => $id,
            'content' => $content,
            'priority' => $priority,
            'status' => 'pending',
        ]);
    }

    public function processQueue(int $batchSize = 10): int
    {
        $result = DB::selectOne(
            'SELECT cmis_knowledge.batch_update_embeddings(?, ?) as processed_count',
            [$batchSize, null]
        );

        return $result->processed_count;
    }
}
```

**Create Embedding Processing Job:**

```php
// app/Jobs/ProcessEmbeddingQueue.php
class ProcessEmbeddingQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(EmbeddingService $embeddingService)
    {
        $processed = $embeddingService->processQueue(10);

        Log::info("Processed {$processed} embeddings");

        // Re-dispatch if there are more to process
        $pending = EmbeddingUpdateQueue::where('status', 'pending')->count();
        if ($pending > 0) {
            ProcessEmbeddingQueue::dispatch()->delay(now()->addSeconds(30));
        }
    }
}
```

**Schedule Embedding Processing:**

```php
// app/Console/Kernel.php
$schedule->job(new ProcessEmbeddingQueue())->everyFiveMinutes();
```

**Action Items:**
- [ ] Create EmbeddingService
- [ ] Create ProcessEmbeddingQueue job
- [ ] Schedule embedding processing
- [ ] Test embedding generation
- [ ] Monitor embedding queue

### Week 8: AI Content Generation

#### Day 36-38: AI Generation Service

**Complete AIGenerationController:**

```php
// app/Http/Controllers/AI/AIGenerationController.php
public function generate($orgId, Request $request)
{
    $validated = $request->validate([
        'type' => 'required|in:campaign,ad_copy,social_post,strategy,headline',
        'topic' => 'required|string',
        'objective' => 'required|string',
        'language' => 'string|default:ar-BH',
        'tone' => 'string',
        'model' => 'string|in:gemini-pro,gpt-4,gpt-3.5-turbo',
    ]);

    // Load context from knowledge base
    $context = $this->semanticSearchService->loadContext(
        $validated['topic'],
        'marketing',
        $validated['type']
    );

    // Build prompt
    $prompt = $this->buildPrompt($validated, $context);

    // Generate using selected AI model
    $result = $this->aiService->generate(
        $prompt,
        $validated['model'] ?? 'gemini-pro'
    );

    // Store generated content
    GeneratedCreative::create([
        'org_id' => $orgId,
        'content_type' => $validated['type'],
        'prompt' => $prompt,
        'generated_content' => $result['content'],
        'model_used' => $result['model'],
        'metadata' => $result['metadata'],
    ]);

    return response()->json([
        'content' => $result['content'],
        'metadata' => $result['metadata']
    ]);
}
```

**Create AI Services:**

```php
// app/Services/AI/GeminiService.php
// app/Services/AI/OpenAIService.php
// app/Services/AI/AIOrchestrator.php - Routes to appropriate service
```

**Action Items:**
- [ ] Complete AIGenerationController
- [ ] Create AI service classes
- [ ] Integrate with knowledge base
- [ ] Add prompt templates
- [ ] Test content generation
- [ ] Connect frontend to backend

#### Day 39-40: AI Recommendations & Insights

**Create AI Recommendation Service:**

```php
// app/Services/AI/RecommendationService.php
class RecommendationService
{
    public function getRecommendations($orgId): array
    {
        $result = DB::selectOne(
            'SELECT cmis_ai_analytics.fn_recommend_focus() as recommendations'
        );

        return json_decode($result->recommendations, true);
    }

    public function predictPerformance(Campaign $campaign): array
    {
        // Use predictive visual engine
        $prediction = DB::selectOne(
            'SELECT * FROM cmis.predictive_visual_engine
             WHERE campaign_id = ?
             ORDER BY created_at DESC
             LIMIT 1',
            [$campaign->campaign_id]
        );

        return [
            'predicted_ctr' => $prediction->predicted_ctr,
            'predicted_engagement' => $prediction->predicted_engagement,
            'confidence' => $prediction->confidence_level,
        ];
    }
}
```

**Action Items:**
- [ ] Create RecommendationService
- [ ] Add recommendation API endpoints
- [ ] Create AI insights dashboard
- [ ] Test AI recommendations
- [ ] Add visual performance predictions

---

## 📅 PHASE 5-8: REMAINING IMPLEMENTATION (Weeks 9-16)

Due to length constraints, here's a summary of remaining phases:

### Phase 5: Analytics & Reporting (Weeks 9-10)
- Complete analytics models
- Implement KPI tracking
- Create report generation system
- Add PDF/Excel exports
- Implement scheduled reports

### Phase 6: Advanced Features (Weeks 11-12)
- Workflow engine
- Compliance system
- A/B testing framework
- Advanced automation

### Phase 7: Offerings & Products (Weeks 13-14)
- Complete offering system
- Product/service/bundle management
- Pricing and configuration
- Market segmentation

### Phase 8: Polish & Optimization (Weeks 15-16)
- Frontend migration and cleanup
- Performance optimization
- Testing and QA
- Documentation
- Deployment preparation

---

## 🔧 MAINTENANCE TASKS

### Scheduled Jobs to Implement

```php
// app/Console/Kernel.php

// Every minute
$schedule->call(function () {
    // Publish scheduled social posts
})->everyMinute();

// Every 5 minutes
$schedule->job(new ProcessEmbeddingQueue())->everyFiveMinutes();

// Every hour
$schedule->call(function () {
    // Sync platform data
    DB::select('SELECT cmis_ops.sync_integrations()');
})->hourly();

// Daily
$schedule->call(function () {
    // Cleanup expired sessions
    DB::select('SELECT cmis.cleanup_expired_sessions()');
})->daily();

$schedule->call(function () {
    // Delete unapproved assets older than 7 days
    DB::select('SELECT cmis.auto_delete_unapproved_assets()');
})->daily();

$schedule->call(function () {
    // Refresh AI insights
    DB::select('SELECT cmis_ops.refresh_ai_insights()');
})->daily();

// Weekly
$schedule->call(function () {
    // Cleanup old cache entries
    DB::select('SELECT cmis.cleanup_old_cache_entries()');
})->weekly();
```

### Monitoring & Health Checks

```php
// app/Http/Controllers/HealthController.php
public function index()
{
    $health = [
        'database' => $this->checkDatabase(),
        'cache' => $this->checkCache(),
        'queue' => $this->checkQueue(),
        'embeddings' => $this->checkEmbeddings(),
        'integrations' => $this->checkIntegrations(),
    ];

    $isHealthy = collect($health)->every(fn($check) => $check['status'] === 'ok');

    return response()->json([
        'status' => $isHealthy ? 'healthy' : 'degraded',
        'checks' => $health,
        'timestamp' => now()
    ], $isHealthy ? 200 : 503);
}
```

---

## 📊 PROGRESS TRACKING

### Weekly Milestones

| Week | Milestone | Deliverables |
|------|-----------|--------------|
| 1 | Security Foundation | Permission system, middleware, policies |
| 2 | Authentication & Contexts | Auth system, context models |
| 3 | Campaign Enhancement | Context integration, performance tracking |
| 4 | Creative & Users | Creative system, user management |
| 5 | Platform Integration | OAuth, sync services |
| 6 | Social Management | Publishing system, ad integration |
| 7 | Knowledge Base | Vector search, embedding generation |
| 8 | AI Generation | Content generation, recommendations |
| 9-10 | Analytics | Reports, exports, dashboards |
| 11-12 | Advanced Features | Workflows, compliance, A/B testing |
| 13-14 | Offerings | Products, services, bundles |
| 15-16 | Polish | Optimization, testing, deployment |

### Success Criteria

**Week 1:**
- [ ] All controllers have authorization
- [ ] Permission middleware working
- [ ] RLS policies tested

**Week 2:**
- [ ] Users can log in
- [ ] Org context initialized
- [ ] Context models functional

**Week 4:**
- [ ] Campaigns fully CRUD operational
- [ ] Creative assets can be uploaded
- [ ] Users can be invited

**Week 6:**
- [ ] OAuth working for Meta/Google
- [ ] Social posts can be scheduled
- [ ] Posts publish successfully

**Week 8:**
- [ ] Semantic search functional
- [ ] AI content generation working
- [ ] Embeddings processing automatically

**Week 12:**
- [ ] All major features operational
- [ ] 100+ models created
- [ ] System 80% complete

**Week 16:**
- [ ] Production ready
- [ ] Documentation complete
- [ ] All tests passing
- [ ] Performance optimized

---

## 🚀 DEPLOYMENT STRATEGY

### Pre-Deployment Checklist

- [ ] All environment variables configured
- [ ] Database migrations tested
- [ ] Scheduled tasks configured
- [ ] Queue workers running
- [ ] File storage configured
- [ ] API keys secured
- [ ] SSL certificates installed
- [ ] Monitoring configured
- [ ] Backup strategy in place
- [ ] Rollback plan prepared

### Deployment Steps

1. **Staging Deployment**
   - Deploy to staging environment
   - Run full test suite
   - Perform manual testing
   - Load testing
   - Security scanning

2. **Production Deployment**
   - Backup database
   - Enable maintenance mode
   - Pull latest code
   - Run migrations
   - Clear caches
   - Restart queue workers
   - Disable maintenance mode
   - Monitor for errors

3. **Post-Deployment**
   - Verify all features working
   - Check scheduled tasks
   - Monitor performance
   - Check error logs
   - Verify integrations

---

## 📝 NOTES

- Prioritize security and authorization first
- Test thoroughly after each phase
- Document as you implement
- Keep stakeholders informed of progress
- Be prepared to adjust timeline based on discoveries
- Focus on MVP features first, enhance later

**Total Estimated Effort:** 640-800 developer hours (16 weeks × 40-50 hours/week)
**Recommended Team:** 3-4 full-stack developers
**Critical Path:** Security → Core Features → Integrations → AI Features

---

**End of Implementation Plan**
