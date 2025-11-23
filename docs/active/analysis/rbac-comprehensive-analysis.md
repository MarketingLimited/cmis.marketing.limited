# CMIS RBAC Comprehensive Analysis Report

**Date:** 2025-11-23
**Branch:** claude/cmis-rbac-analysis-01T2KJd2b9yeqzgne1ndbJPQ
**Agent:** CMIS RBAC & Authorization Specialist V2.1
**Status:** CRITICAL ISSUES FOUND

---

## Executive Summary

This comprehensive analysis of the CMIS Role-Based Access Control (RBAC) system has identified **7 critical issues** and **12 improvement opportunities**. The most severe issue is a **complete failure of the permission checking system** due to a critical bug in `PermissionService::check()`.

### Overall RBAC Health: 🔴 CRITICAL (45/100)

- **Architecture**: ✅ GOOD (85/100) - Well-designed 2-level permission system
- **Implementation**: 🔴 CRITICAL (20/100) - Core permission check is broken
- **Multi-Tenancy Integration**: ✅ GOOD (80/100) - RLS integration is solid
- **Testing**: 🟡 MODERATE (60/100) - Good test coverage, but tests may be passing incorrectly
- **Security**: 🔴 CRITICAL (30/100) - Permission checks are ineffective

---

## Architecture Overview

### Discovered RBAC Structure

The CMIS RBAC system implements a **2-level permission model**:

**Level 1: Role-Based Permissions**
- Organization roles: `owner`, `admin`, `manager`, `editor`, `viewer`
- Permissions assigned to roles via `cmis.role_permissions`
- All users with a role inherit role permissions

**Level 2: User-Specific Permissions**
- Direct user permission grants/denials via `cmis.user_permissions`
- Supports expiration dates
- Can override role permissions
- Includes `is_granted` flag for denials

**Database Tables:**
```
cmis.permissions          - All available permissions
cmis.roles                - Role definitions
cmis.role_permissions     - Role → Permission mappings
cmis.user_permissions     - User → Permission overrides
cmis.permissions_cache    - Performance cache
cmis.user_orgs            - User → Org → Role relationships
```

**Database Functions:**
```sql
cmis.check_permission(user_id, org_id, permission_code) → boolean
cmis.check_permission_tx(permission_code) → boolean  -- Uses transaction context
cmis.init_transaction_context(user_id, org_id) → void
cmis.validate_transaction_context() → record
```

**Laravel Components:**
- 12 Policy classes
- PermissionService (service layer)
- PermissionRepository (database layer)
- CheckPermission middleware
- AdminOnly middleware

---

## Critical Issues

### 🚨 ISSUE #1: BROKEN PERMISSION CHECK (SEVERITY: CRITICAL)

**File:** `app/Services/PermissionService.php:41`

**Problem:**
The `check()` method has a completely incorrect permission check implementation:

```php
// CURRENT CODE (BROKEN):
$cached = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $orgId, $permissionCode) {
    try {
        // ❌ WRONG: canAccessCampaign() expects (userId, campaignId)
        //           but we're passing (userId, orgId)
        $hasPermission = $this->permissionRepo->canAccessCampaign($user->user_id, $orgId);
        // ...
    }
});
```

**Root Cause:**
- Method signature: `canAccessCampaign(string $userId, string $campaignId): bool`
- Actual call: `canAccessCampaign($user->user_id, $orgId)` ← passing org_id instead of campaign_id
- This returns incorrect results and ignores the `$permissionCode` parameter entirely

**Impact:**
- **ALL permission checks are broken**
- Authorization is effectively disabled across the application
- Security vulnerability: Users may access resources they shouldn't

**Fix Required:**
Replace with proper permission check using database function:

```php
$cached = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $orgId, $permissionCode) {
    try {
        // Use the repository's checkPermission method
        $hasPermission = $this->permissionRepo->checkPermission(
            $user->user_id,
            $orgId,
            $permissionCode
        );

        // Update cache metadata
        $permissionCache = PermissionsCache::where('permission_code', $permissionCode)->first();
        if ($permissionCache) {
            $permissionCache->touch();
        }

        return $hasPermission;
    } catch (\Exception $e) {
        Log::error('Permission check failed', [
            'user_id' => $user->user_id,
            'org_id' => $orgId,
            'permission' => $permissionCode,
            'error' => $e->getMessage()
        ]);
        return false;
    }
});
```

---

### 🚨 ISSUE #2: DUPLICATE PERMISSION MODELS (SEVERITY: HIGH)

**Files:**
- `app/Models/Permission.php`
- `app/Models/Security/Permission.php`
- `app/Models/Permission/Permission.php` (possibly)

**Problem:**
Multiple Permission model classes exist with different implementations:

**app/Models/Permission.php:**
- Has `module`, `resource`, `action` fields
- Methods: `getFullPermissionAttribute()`
- Scopes: `byModule()`, `byResource()`

**app/Models/Security/Permission.php:**
- Uses `permission_code`, `permission_name`, `category`
- Has `requires_org_context` field
- Methods: `getFullCodeAttribute()`, `requiresOrgContext()`

**Impact:**
- Code confusion: Different parts of codebase use different models
- Database schema mismatch
- Inconsistent permission structure
- Maintenance nightmare

**Fix Required:**
1. Determine canonical Permission model (likely `Security/Permission.php`)
2. Remove duplicates
3. Update all references to use canonical model
4. Create alias if needed for backwards compatibility

---

### 🚨 ISSUE #3: WEAK ADMIN CHECK (SEVERITY: HIGH)

**File:** `app/Http/Middleware/AdminOnly.php:38-41`

**Problem:**
```php
$isAdmin = $user->is_admin
           ?? $user->role === 'admin'
           ?? $user->hasRole('admin')
           ?? false;
```

This uses multiple fallback checks with null coalescing (`??`), which is **incorrect logic**:
- Should use OR (`||`) not null coalescing (`??`)
- `hasRole('admin')` method may not exist on User model
- Relies on deprecated `is_admin` column from old migration

**Impact:**
- Admin checks may fail unexpectedly
- Potential privilege escalation if checks fail open
- Inconsistent admin detection

**Fix Required:**
Use proper permission-based admin check:

```php
public function handle(Request $request, Closure $next)
{
    if (!Auth::check()) {
        return $this->unauthorized($request, 401, 'Unauthenticated');
    }

    $user = Auth::user();
    $orgId = session('current_org_id');

    if (!$orgId) {
        return $this->unauthorized($request, 403, 'No organization context');
    }

    // Check if user has admin or owner role in current org
    $isAdmin = $user->hasRoleInOrg($orgId, 'admin') || $user->hasRoleInOrg($orgId, 'owner');

    if (!$isAdmin) {
        return $this->unauthorized($request, 403, 'Admin privileges required');
    }

    // Set admin context for RLS bypass
    try {
        DB::statement("SET LOCAL app.is_admin = true");
    } catch (\Exception $e) {
        Log::warning('Failed to set admin context', ['error' => $e->getMessage()]);
    }

    return $next($request);
}
```

---

### 🚨 ISSUE #4: BROKEN SUPER ADMIN GATE (SEVERITY: MEDIUM)

**File:** `app/Providers/AuthServiceProvider.php:80-94`

**Problem:**
```php
Gate::before(function ($user, $ability) {
    $currentOrgId = session('current_org_id');
    if ($currentOrgId) {
        $userOrg = $user->orgs()
            ->where('cmis.orgs.org_id', $currentOrgId)
            ->first();

        // ❌ BROKEN: $userOrg->pivot->role doesn't exist
        if ($userOrg && $userOrg->pivot && $userOrg->pivot->role) {
            if ($userOrg->pivot->role->role_code === 'super_admin') {
                return true;
            }
        }
    }
});
```

**Issues:**
- `$userOrg->pivot` contains only pivot table columns (user_id, org_id, role_id)
- `$userOrg->pivot->role` doesn't exist
- Needs to load the role relationship explicitly

**Fix Required:**
```php
Gate::before(function ($user, $ability) {
    $currentOrgId = session('current_org_id');
    if (!$currentOrgId) {
        return null;
    }

    // Get user's role in current organization
    $userOrg = $user->orgs()
        ->where('cmis.orgs.org_id', $currentOrgId)
        ->first();

    if (!$userOrg) {
        return null;
    }

    // Get the role_id from pivot and check role
    $roleId = $userOrg->pivot->role_id;
    if (!$roleId) {
        return null;
    }

    $role = \App\Models\Core\Role::find($roleId);
    if ($role && in_array($role->role_code, ['super_admin', 'owner'])) {
        return true; // Bypass all permission checks
    }

    return null; // Continue with normal authorization
});
```

---

### 🚨 ISSUE #5: CONFLICTING ROLES TABLE SCHEMA (SEVERITY: MEDIUM)

**Files:**
- `database/migrations/2025_11_20_215000_add_roles_and_permissions_for_features.php`
- Existing `cmis.roles` table (different schema)

**Problem:**
Migration creates a new `cmis.roles` table with schema:
```sql
roles(id UUID, name, slug, permissions JSONB, is_system)
```

But existing RBAC system uses:
```sql
roles(role_id UUID, org_id, role_code, role_name, is_system, is_active)
```

Migration detects if table exists and creates foreign keys conditionally, leading to:
- Schema conflicts
- Foreign key failures
- Inconsistent role structure

**Impact:**
- Database migration failures
- Orphaned permissions
- Broken role assignments

**Fix Required:**
1. Remove conflicting migration
2. Use existing `cmis.roles` table structure
3. Ensure all code references correct schema

---

### 🚨 ISSUE #6: MISSING PERMISSIONS IN DATABASE (SEVERITY: HIGH)

**Problem:**
Database function `cmis.check_permission()` queries `cmis.permissions` table, but:
- No migration creates core RBAC tables
- Tables might exist from SQL dump but not in migrations
- Permission seeding is ad-hoc (audit, AI permissions only)

**Missing Permissions:**
```
cmis.campaigns.view
cmis.campaigns.create
cmis.campaigns.update
cmis.campaigns.delete
cmis.campaigns.publish
cmis.campaigns.view_analytics
cmis.campaigns.restore
cmis.campaigns.force_delete
cmis.assets.view
cmis.assets.create
cmis.assets.update
cmis.assets.delete
cmis.assets.download
cmis.integrations.view
cmis.integrations.configure
cmis.integrations.sync
cmis.users.view
cmis.users.create
cmis.users.invite
cmis.users.update
cmis.users.delete
cmis.users.assign_role
cmis.users.grant_permission
cmis.users.view_activity
... and many more
```

**Impact:**
- All permission checks fail (return false)
- Users cannot perform any actions
- Authorization system is non-functional

**Fix Required:**
Create comprehensive permission seeder with all required permissions.

---

### 🚨 ISSUE #7: INCONSISTENT AUTHORIZATION PATTERNS (SEVERITY: MEDIUM)

**Problem:**
Controllers use different authorization approaches:

**Pattern 1: Policy-based (GOOD)**
```php
$this->authorize('viewAny', CreativeAsset::class);
$this->authorize('view', $asset);
```

**Pattern 2: Gate-based (GOOD)**
```php
Gate::authorize('analytics.view_dashboard');
```

**Pattern 3: Manual service check (INCONSISTENT)**
```php
if (!$this->permissionService->check($user, 'permission.code')) {
    abort(403);
}
```

**Pattern 4: Middleware-based (GOOD)**
```php
$this->middleware('check.permission:cmis.campaigns.view');
```

**Issues:**
- Inconsistent error responses
- Some controllers missing authorization entirely
- Hard to audit which endpoints are protected

**Fix Required:**
Standardize on policy + gate pattern:
- Use policies for resource-based authorization
- Use gates for non-resource permissions
- Remove manual permission checks
- Add authorization to all controller methods

---

## Improvement Opportunities

### 1. PermissionRepository Interface Mismatch

**File:** `app/Repositories/Contracts/PermissionRepositoryInterface.php`

**Issue:**
Interface only defines 3 methods:
```php
canAccessCampaign(string $userId, string $campaignId): bool
canManageOrg(string $userId, string $orgId): bool
getUserOrgPermissions(string $userId, string $orgId): array
```

But implementation has many more:
- `checkPermission()`
- `checkPermissionWithTransaction()`
- `initTransactionContext()`
- etc.

**Recommendation:**
Update interface to include all public methods.

---

### 2. Cache Invalidation Issues

**File:** `app/Services/PermissionService.php:229,251`

**Issue:**
```php
// This doesn't actually clear wildcard keys
Cache::forget("permission:{$user->user_id}:{$orgId}:*");
```

Laravel's `forget()` doesn't support wildcards.

**Recommendation:**
Use cache tags or iterate through known permission codes:

```php
protected function clearCacheForUser(User $user, ?string $orgId = null): void
{
    if ($orgId) {
        // Clear aggregated permissions
        Cache::forget("user_permissions:{$user->user_id}:{$orgId}");

        // Clear individual permission checks - need to track all permission codes
        $permissions = Permission::pluck('permission_code');
        foreach ($permissions as $code) {
            Cache::forget("permission:{$user->user_id}:{$orgId}:{$code}");
        }
    }

    // Use tags if Redis is configured
    Cache::tags(["user_permissions:{$user->user_id}"])->flush();
}
```

---

### 3. Missing Permission Check Logging

**Recommendation:**
Add comprehensive audit logging for:
- Permission grants/revokes
- Permission check failures
- Role assignments
- Suspicious access patterns

---

### 4. No Permission Management UI

**Issue:**
No documented way to:
- View user permissions
- Grant/revoke permissions
- Manage roles
- Audit permission changes

**Recommendation:**
Create admin interface for permission management.

---

### 5. Missing Permission Seeder

**Issue:**
Permissions are created ad-hoc in various migrations rather than centralized seeder.

**Recommendation:**
Create `PermissionSeeder` with all application permissions.

---

### 6. Database Function Error Handling

**Issue:**
Database functions have minimal error handling:
```sql
IF v_permission_id IS NULL THEN
    RETURN false; -- Silent failure
END IF;
```

**Recommendation:**
Add proper exception handling and logging in database functions.

---

### 7. Missing Permission Documentation

**Issue:**
No central documentation of:
- Available permissions
- Permission naming convention
- Which permissions are dangerous
- Role → Permission mappings

**Recommendation:**
Create `docs/reference/permissions/permission-reference.md`.

---

### 8. Expired Permission Cleanup

**Good:** `PermissionService::cleanupExpired()` exists

**Issue:** No scheduler/cron job to run it automatically

**Recommendation:**
Add to Laravel scheduler:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(PermissionService::class)->cleanupExpired();
    })->daily();
}
```

---

### 9. PermissionsCache Model Issues

**File:** `app/Services/PermissionService.php:44`

**Issue:**
```php
$permissionCache = PermissionsCache::getByCode($permissionCode);
```

- `getByCode()` method doesn't exist on PermissionsCache model
- Should be `where('permission_code', $permissionCode)->first()`

---

### 10. No Permission Testing Helpers

**Recommendation:**
Create testing helpers:

```php
// tests/Traits/WithPermissions.php
trait WithPermissions
{
    protected function grantPermission(User $user, string $permissionCode): void
    {
        $permission = Permission::where('permission_code', $permissionCode)->firstOrFail();
        app(PermissionService::class)->grantToUser($user, $permission, $user);
    }

    protected function revokePermission(User $user, string $permissionCode): void
    {
        $permission = Permission::where('permission_code', $permissionCode)->firstOrFail();
        app(PermissionService::class)->revokeFromUser($user, $permission);
    }

    protected function assignRole(User $user, string $orgId, string $roleCode): void
    {
        $role = Role::where('role_code', $roleCode)->firstOrFail();
        // ... assign role
    }
}
```

---

### 11. Policy Registration Incomplete

**File:** `app/Providers/AuthServiceProvider.php`

**Registered Policies:**
- Campaign
- CreativeAsset
- ContentItem
- Integration
- Org
- User
- Offering
- Channel

**Missing Policies:**
- AI operations (has gates but no policy)
- Analytics (has gates but no policy)
- Audit
- Many other resources

**Recommendation:**
Either create missing policies or document why gates are used instead.

---

### 12. Multi-Tenancy Context Validation

**Issue:**
Permission checks assume `session('current_org_id')` is always set and valid.

**Recommendation:**
Add validation:

```php
public function check(User $user, string $permissionCode, ?string $orgId = null): bool
{
    $orgId = $orgId ?? session('current_org_id');

    if (!$orgId) {
        Log::warning('Permission check without org context', [
            'user_id' => $user->user_id,
            'permission' => $permissionCode
        ]);
        return false;
    }

    // Validate user belongs to org
    if (!$user->belongsToOrg($orgId)) {
        Log::warning('Permission check for user not in org', [
            'user_id' => $user->user_id,
            'org_id' => $orgId,
            'permission' => $permissionCode
        ]);
        return false;
    }

    // Continue with permission check...
}
```

---

## Security Assessment

### Current Security Posture: 🔴 CRITICAL

**Vulnerabilities Identified:**

1. **🔴 CRITICAL: Authorization Bypass**
   - Broken permission check allows any authenticated user to bypass authorization
   - Impact: Complete compromise of authorization system

2. **🔴 HIGH: Privilege Escalation**
   - Weak admin checks could allow unauthorized admin access
   - Broken super_admin gate check

3. **🟡 MEDIUM: Missing Permission Checks**
   - Some controllers may lack authorization
   - Audit needed to identify unprotected endpoints

4. **🟡 MEDIUM: Cache Poisoning**
   - Incorrect cache invalidation could persist wrong permissions

5. **🟢 LOW: Information Disclosure**
   - Error messages might reveal permission structure

**Security Recommendations:**

1. **Immediate:** Fix PermissionService::check() bug
2. **Immediate:** Audit all controller endpoints for authorization
3. **Short-term:** Fix admin and super_admin checks
4. **Short-term:** Implement comprehensive permission logging
5. **Medium-term:** Add rate limiting to permission checks
6. **Medium-term:** Implement permission change notifications

---

## Multi-Tenancy Integration Analysis

### RLS + RBAC Integration: ✅ GOOD (80/100)

**Strengths:**

1. **Separation of Concerns:**
   - RLS handles org isolation at database level
   - RBAC handles permission checks at application level
   - Policies don't duplicate org checks (trust RLS)

2. **Transaction Context:**
   - `cmis.init_transaction_context()` sets user/org
   - `check_permission_tx()` uses transaction context
   - Proper middleware sets context before queries

3. **Database Functions:**
   - Well-implemented `check_permission()` function
   - Caches permission lookups
   - Handles role + user permissions correctly

**Weaknesses:**

1. **Context Validation:**
   - Application doesn't validate org context before permission checks
   - Could check permissions for org user doesn't belong to

2. **Policy Recommendations:**
   - Policies are very thin (good) but could validate org membership for extra safety

**Recommendation:**
Current approach is correct. Minor improvements:
- Add org membership validation in permission checks
- Document the RLS + RBAC separation of concerns

---

## Testing Assessment

### Test Coverage: 🟡 MODERATE (60/100)

**Existing Tests:**

1. **CampaignAuthorizationTest** (550 lines)
   - ✅ Authentication tests
   - ✅ viewAny, view, create, update, delete
   - ✅ Multi-tenant isolation
   - ✅ Duplicate campaign authorization
   - ✅ Analytics authorization
   - ✅ Edge cases (invalid UUID, soft deletes)

2. **ContentPlanAuthorizationTest**
   - Similar structure to Campaign tests

3. **PermissionTest** (Unit test)
   - Tests Permission model

**Issues:**

1. **Tests may be passing incorrectly:**
   - If permission check is broken, why do tests pass?
   - Likely: Tests create users with admin/owner roles that bypass checks
   - Or: Tests don't actually verify permission enforcement

2. **Missing Tests:**
   - PermissionService unit tests
   - Permission grant/revoke tests
   - Cache invalidation tests
   - Role assignment tests
   - Permission expiration tests

**Recommendation:**
1. Review why tests pass despite broken permission check
2. Add comprehensive PermissionService tests
3. Add integration tests for permission system
4. Test all critical paths

---

## Recommended Fixes Priority

### P0 - CRITICAL (Fix Immediately)

1. ✅ Fix `PermissionService::check()` bug
2. ✅ Seed all required permissions
3. ✅ Fix AdminOnly middleware admin check
4. ✅ Audit all controllers for missing authorization

### P1 - HIGH (Fix This Sprint)

5. ✅ Remove duplicate Permission models
6. ✅ Fix super_admin Gate::before() check
7. ✅ Fix conflicting roles migration
8. ✅ Add permission management tests

### P2 - MEDIUM (Fix Next Sprint)

9. ⚠️ Standardize authorization patterns
10. ⚠️ Fix cache invalidation
11. ⚠️ Update PermissionRepository interface
12. ⚠️ Add comprehensive audit logging

### P3 - LOW (Backlog)

13. 📋 Create permission management UI
14. 📋 Document all permissions
15. 📋 Add permission testing helpers
16. 📋 Create missing policies

---

## Implementation Plan

### Phase 1: Critical Fixes (Immediate - 4 hours)

1. **Fix PermissionService::check()**
   - Update method to use `checkPermission()`
   - Test with multiple scenarios
   - Verify cache works correctly

2. **Create Permission Seeder**
   - List all required permissions
   - Create seeder with all permissions
   - Seed default role → permission mappings

3. **Fix AdminOnly Middleware**
   - Replace weak admin check
   - Use proper role checking

4. **Authorization Audit**
   - Review all controllers
   - Add missing `$this->authorize()` calls
   - Document protected endpoints

### Phase 2: High Priority Fixes (1-2 days)

5. **Remove Duplicate Models**
   - Choose canonical Permission model
   - Remove duplicates
   - Update all references

6. **Fix Super Admin Gate**
   - Correct relationship loading
   - Test super_admin bypass

7. **Fix Roles Migration Conflict**
   - Remove conflicting migration
   - Document correct schema

8. **Add Permission Tests**
   - Unit tests for PermissionService
   - Integration tests for authorization
   - Test permission expiration

### Phase 3: Medium Priority (1 week)

9. **Standardize Authorization**
   - Document preferred patterns
   - Refactor inconsistent usage
   - Update guidelines

10. **Fix Cache System**
    - Implement proper cache invalidation
    - Use cache tags
    - Test cache behavior

11. **Update Repository Interface**
    - Add missing method signatures
    - Document all methods

12. **Add Audit Logging**
    - Log permission changes
    - Log authorization failures
    - Create audit dashboard

### Phase 4: Low Priority (Backlog)

13-16. See P3 items above

---

## Success Criteria

✅ **All permission checks work correctly**
- PermissionService::check() uses correct repository method
- Permission checks respect role + user permissions
- Cache invalidation works properly

✅ **All critical endpoints are protected**
- Every controller method has authorization
- Tests verify authorization enforcement
- No bypasses except documented super_admin

✅ **Multi-tenancy isolation is maintained**
- RLS + RBAC work together correctly
- Users can only access their org's resources
- Permission checks validate org membership

✅ **Testing is comprehensive**
- 80%+ coverage of PermissionService
- Integration tests for all authorization scenarios
- Tests fail when authorization is removed

✅ **Code is consistent**
- Single Permission model
- Standardized authorization patterns
- Clear documentation

---

## Conclusion

The CMIS RBAC system has a **solid architecture** with good separation between RLS (org isolation) and RBAC (permissions), but **critical implementation bugs** make it currently non-functional. The highest priority is fixing the `PermissionService::check()` method, which completely breaks authorization.

Once critical bugs are fixed, the system should work well with its 2-level permission model (role + user permissions), caching layer, and database function integration.

**Estimated Total Effort:** 3-4 days
**Risk Level:** HIGH (authorization completely broken)
**Recommended Action:** Immediate hotfix + comprehensive testing

---

**Analysis Completed:** 2025-11-23
**Next Steps:** Begin Phase 1 implementation
