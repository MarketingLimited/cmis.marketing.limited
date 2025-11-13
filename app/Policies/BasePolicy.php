<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;

abstract class BasePolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Check permission using transaction context
     *
     * @param string $permissionCode
     * @return bool
     */
    protected function checkPermission(string $permissionCode): bool
    {
        try {
            return $this->permissionService->checkTx($permissionCode);
        } catch (\Exception $e) {
            Log::error('Policy permission check failed', [
                'permission' => $permissionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user is owner or admin in current org
     *
     * @param User $user
     * @param string|null $orgId
     * @return bool
     */
    protected function isOwnerOrAdmin(User $user, ?string $orgId = null): bool
    {
        $orgId = $orgId ?? session('current_org_id');

        if (!$orgId) {
            return false;
        }

        return $user->hasRoleInOrg($orgId, 'owner')
            || $user->hasRoleInOrg($orgId, 'admin');
    }

    /**
     * Check if user belongs to the current org
     *
     * @param User $user
     * @param string|null $orgId
     * @return bool
     */
    protected function belongsToOrg(User $user, ?string $orgId = null): bool
    {
        $orgId = $orgId ?? session('current_org_id');

        if (!$orgId) {
            return false;
        }

        return $user->belongsToOrg($orgId);
    }

    /**
     * Check if resource belongs to current org
     *
     * @param mixed $model
     * @return bool
     */
    protected function resourceBelongsToOrg($model): bool
    {
        $currentOrgId = session('current_org_id');

        if (!$currentOrgId) {
            return false;
        }

        if (!isset($model->org_id)) {
            return false;
        }

        return $model->org_id === $currentOrgId;
    }
}
