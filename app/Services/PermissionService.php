<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    /**
     * Check if user has permission using DB function
     *
     * @param User $user
     * @param string $permissionCode
     * @return bool
     */
    public function check(User $user, string $permissionCode): bool
    {
        $orgId = session('current_org_id');
        if (!$orgId) {
            Log::warning('Permission check without org context', [
                'user_id' => $user->user_id,
                'permission' => $permissionCode
            ]);
            return false;
        }

        try {
            $result = DB::selectOne(
                'SELECT cmis.check_permission(?, ?, ?) as has_permission',
                [$user->user_id, $orgId, $permissionCode]
            );

            return (bool) $result->has_permission;
        } catch (\Exception $e) {
            Log::error('Permission check error', [
                'user_id' => $user->user_id,
                'org_id' => $orgId,
                'permission' => $permissionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check permission using transaction context
     *
     * @param string $permissionCode
     * @return bool
     */
    public function checkTx(string $permissionCode): bool
    {
        try {
            $result = DB::selectOne(
                'SELECT cmis.check_permission_tx(?) as has_permission',
                [$permissionCode]
            );

            return (bool) $result->has_permission;
        } catch (\Exception $e) {
            Log::error('Transaction context permission check error', [
                'permission' => $permissionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Grant permission to role
     *
     * @param Role $role
     * @param Permission $permission
     * @param User $grantedBy
     * @return void
     */
    public function grantToRole(Role $role, Permission $permission, User $grantedBy): void
    {
        $role->permissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'granted_by' => $grantedBy->user_id,
                'created_at' => now(),
            ]
        ]);

        // Refresh cache
        $this->refreshCacheForRole($role);

        Log::info('Permission granted to role', [
            'role_id' => $role->role_id,
            'role_name' => $role->role_name,
            'permission' => $permission->permission_code,
            'granted_by' => $grantedBy->user_id
        ]);
    }

    /**
     * Revoke permission from role
     *
     * @param Role $role
     * @param Permission $permission
     * @return void
     */
    public function revokeFromRole(Role $role, Permission $permission): void
    {
        $role->permissions()->detach($permission->permission_id);

        // Refresh cache
        $this->refreshCacheForRole($role);

        Log::info('Permission revoked from role', [
            'role_id' => $role->role_id,
            'role_name' => $role->role_name,
            'permission' => $permission->permission_code
        ]);
    }

    /**
     * Grant permission to user
     *
     * @param User $user
     * @param Permission $permission
     * @param User $grantedBy
     * @param \DateTime|null $expiresAt
     * @return void
     */
    public function grantToUser(
        User $user,
        Permission $permission,
        User $grantedBy,
        ?\DateTime $expiresAt = null
    ): void {
        $user->permissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'is_granted' => true,
                'expires_at' => $expiresAt,
                'granted_by' => $grantedBy->user_id,
                'created_at' => now(),
            ]
        ]);

        // Refresh cache
        $this->refreshCacheForUser($user, session('current_org_id'));

        Log::info('Permission granted to user', [
            'user_id' => $user->user_id,
            'permission' => $permission->permission_code,
            'granted_by' => $grantedBy->user_id,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Revoke permission from user
     *
     * @param User $user
     * @param Permission $permission
     * @return void
     */
    public function revokeFromUser(User $user, Permission $permission): void
    {
        $user->permissions()->updateExistingPivot($permission->permission_id, [
            'is_granted' => false,
            'updated_at' => now(),
        ]);

        // Refresh cache
        $this->refreshCacheForUser($user, session('current_org_id'));

        Log::info('Permission revoked from user', [
            'user_id' => $user->user_id,
            'permission' => $permission->permission_code
        ]);
    }

    /**
     * Get all permissions for user in org
     *
     * @param User $user
     * @param string $orgId
     * @return array
     */
    public function getUserPermissions(User $user, string $orgId): array
    {
        return Cache::remember(
            "user_permissions:{$user->user_id}:{$orgId}",
            now()->addMinutes(10),
            function () use ($user, $orgId) {
                $userOrg = $user->orgs()
                    ->where('cmis.orgs.org_id', $orgId)
                    ->with('pivot.role.permissions')
                    ->first();

                if (!$userOrg) {
                    return [];
                }

                $rolePermissions = [];
                if ($userOrg->pivot && $userOrg->pivot->role) {
                    $rolePermissions = $userOrg->pivot->role->permissions
                        ->pluck('permission_code')
                        ->toArray();
                }

                // Add user-specific permissions
                $userPermissions = $user->permissions()
                    ->wherePivot('is_granted', true)
                    ->where(function ($query) {
                        $query->whereNull('cmis.user_permissions.expires_at')
                            ->orWhere('cmis.user_permissions.expires_at', '>', now());
                    })
                    ->pluck('permission_code')
                    ->toArray();

                return array_unique(array_merge($rolePermissions, $userPermissions));
            }
        );
    }

    /**
     * Refresh permissions cache for user
     *
     * @param User $user
     * @param string $orgId
     * @return void
     */
    public function refreshCacheForUser(User $user, string $orgId): void
    {
        Cache::forget("user_permissions:{$user->user_id}:{$orgId}");

        try {
            // Call DB function to refresh
            DB::select('SELECT cmis.refresh_permissions_cache()');
        } catch (\Exception $e) {
            Log::error('Failed to refresh permissions cache', [
                'user_id' => $user->user_id,
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh permissions cache for role
     *
     * @param Role $role
     * @return void
     */
    public function refreshCacheForRole(Role $role): void
    {
        // Find all users with this role and refresh their cache
        $userOrgs = $role->userOrgs()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($userOrgs as $userOrg) {
            $this->refreshCacheForUser($userOrg->user, $userOrg->org_id);
        }
    }

    /**
     * Get all available permissions
     *
     * @param string|null $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllPermissions(?string $module = null)
    {
        $query = Permission::query();

        if ($module) {
            $query->where('module', $module);
        }

        return $query->orderBy('module')
            ->orderBy('resource')
            ->orderBy('action')
            ->get();
    }

    /**
     * Get permissions by module
     *
     * @return array
     */
    public function getPermissionsByModule(): array
    {
        return Permission::all()
            ->groupBy('module')
            ->map(function ($permissions) {
                return $permissions->groupBy('resource');
            })
            ->toArray();
    }

    /**
     * Create a new permission
     *
     * @param array $data
     * @param User $createdBy
     * @return Permission
     */
    public function createPermission(array $data, User $createdBy): Permission
    {
        $permission = Permission::create([
            'permission_code' => $data['permission_code'],
            'permission_name' => $data['permission_name'],
            'description' => $data['description'] ?? null,
            'module' => $data['module'],
            'resource' => $data['resource'],
            'action' => $data['action'],
            'is_system' => $data['is_system'] ?? false,
            'created_by' => $createdBy->user_id,
        ]);

        Log::info('Permission created', [
            'permission_id' => $permission->permission_id,
            'permission_code' => $permission->permission_code,
            'created_by' => $createdBy->user_id
        ]);

        return $permission;
    }
}
