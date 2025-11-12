<?php

namespace App\Services;

use App\Models\Core\Role;
use App\Models\Security\Permission;
use App\Models\Security\PermissionsCache;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    /**
     * Check if user has permission using database function
     */
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

        // Check cache first
        $cacheKey = "permission:{$user->user_id}:{$orgId}:{$permissionCode}";
        $cached = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $orgId, $permissionCode) {
            try {
                $result = DB::selectOne(
                    'SELECT cmis.check_permission(?, ?, ?) as has_permission',
                    [$user->user_id, $orgId, $permissionCode]
                );

                $hasPermission = (bool) ($result->has_permission ?? false);
                PermissionsCache::getOrCreate($user->user_id, $orgId, $permissionCode, $hasPermission);

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

        return $cached;
    }

    /**
     * Check permission using transaction context
     */
    public function checkTx(string $permissionCode): bool
    {
        try {
            $result = DB::selectOne(
                'SELECT cmis.check_permission_tx(?) as has_permission',
                [$permissionCode]
            );

            return (bool) ($result->has_permission ?? false);
        } catch (\Exception $e) {
            Log::error('Transaction permission check failed', [
                'permission' => $permissionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Grant permission to role
     */
    public function grantToRole(Role $role, Permission $permission, User $grantedBy): void
    {
        $role->permissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'granted_by' => $grantedBy->user_id,
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $this->clearCacheForRole($role);

        Log::info('Permission granted to role', [
            'role_id' => $role->role_id,
            'permission' => $permission->permission_code,
            'granted_by' => $grantedBy->user_id
        ]);
    }

    /**
     * Revoke permission from role
     */
    public function revokeFromRole(Role $role, Permission $permission): void
    {
        $role->permissions()->detach($permission->permission_id);
        $this->clearCacheForRole($role);

        Log::info('Permission revoked from role', [
            'role_id' => $role->role_id,
            'permission' => $permission->permission_code
        ]);
    }

    /**
     * Grant permission to user
     */
    public function grantToUser(User $user, Permission $permission, User $grantedBy, ?\DateTime $expiresAt = null, ?string $reason = null): void
    {
        $user->permissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'is_granted' => true,
                'expires_at' => $expiresAt,
                'granted_by' => $grantedBy->user_id,
                'granted_at' => now(),
                'reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $this->clearCacheForUser($user);

        Log::info('Permission granted to user', [
            'user_id' => $user->user_id,
            'permission' => $permission->permission_code,
            'granted_by' => $grantedBy->user_id
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

        $this->clearCacheForUser($user);
    }

    /**
     * Get all permissions for user in org
     */
    public function getUserPermissions(User $user, string $orgId): array
    {
        return Cache::remember(
            "user_permissions:{$user->user_id}:{$orgId}",
            now()->addMinutes(30),
            function () use ($user, $orgId) {
                $userOrg = $user->orgs()
                    ->where('cmis.orgs.org_id', $orgId)
                    ->with('role.permissions')
                    ->first();

                if (!$userOrg || !$userOrg->role) {
                    return [];
                }

                $rolePermissions = $userOrg->role->permissions->pluck('permission_code')->toArray();

                $userPermissions = $user->permissions()
                    ->wherePivot('is_granted', true)
                    ->where(function ($query) {
                        $query->whereNull('user_permissions.expires_at')
                            ->orWhere('user_permissions.expires_at', '>', now());
                    })
                    ->pluck('permissions.permission_code')
                    ->toArray();

                return array_values(array_unique(array_merge($rolePermissions, $userPermissions)));
            }
        );
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAny(User $user, array $permissionCodes, ?string $orgId = null): bool
    {
        foreach ($permissionCodes as $code) {
            if ($this->check($user, $code, $orgId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAll(User $user, array $permissionCodes, ?string $orgId = null): bool
    {
        foreach ($permissionCodes as $code) {
            if (!$this->check($user, $code, $orgId)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Clear cache for user
     */
    public function clearCacheForUser(User $user, ?string $orgId = null): void
    {
        if ($orgId) {
            Cache::forget("user_permissions:{$user->user_id}:{$orgId}");
            PermissionsCache::clearForUser($user->user_id, $orgId);
        } else {
            $user->orgs->each(function ($org) use ($user) {
                Cache::forget("user_permissions:{$user->user_id}:{$org->org_id}");
            });
            PermissionsCache::clearForUser($user->user_id);
        }

        Cache::tags("user_permissions:{$user->user_id}")->flush();
    }

    /**
     * Clear cache for role
     */
    protected function clearCacheForRole(Role $role): void
    {
        $userOrgs = DB::table('cmis.user_orgs')
            ->where('role_id', $role->role_id)
            ->get(['user_id', 'org_id']);

        foreach ($userOrgs as $userOrg) {
            Cache::forget("user_permissions:{$userOrg->user_id}:{$userOrg->org_id}");
            PermissionsCache::clearForUser($userOrg->user_id, $userOrg->org_id);
        }
    }

    /**
     * Refresh permissions cache
     */
    public function refreshCache(User $user, string $orgId): void
    {
        $this->clearCacheForUser($user, $orgId);
        $this->getUserPermissions($user, $orgId);
    }

    /**
     * Cleanup expired user permissions
     */
    public function cleanupExpired(): int
    {
        return DB::table('cmis.user_permissions')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('is_granted', true)
            ->update([
                'is_granted' => false,
                'updated_at' => now()
            ]);
    }
}
