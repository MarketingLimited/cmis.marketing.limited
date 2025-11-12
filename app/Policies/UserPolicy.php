<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class UserPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.users.view');
    }

    public function view(User $user, User $targetUser): bool
    {
        if ($user->user_id === $targetUser->user_id) {
            return true; // Can always view own profile
        }
        return $this->permissionService->check($user, 'cmis.users.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.users.create');
    }

    public function update(User $user, User $targetUser): bool
    {
        if ($user->user_id === $targetUser->user_id) {
            return true; // Can always update own profile
        }
        return $this->permissionService->check($user, 'cmis.users.update');
    }

    public function delete(User $user, User $targetUser): bool
    {
        if ($user->user_id === $targetUser->user_id) {
            return false; // Cannot delete own account
        }
        return $this->permissionService->check($user, 'cmis.users.delete');
    }

    public function invite(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.users.invite');
    }

    public function assignRole(User $user, User $targetUser): bool
    {
        if ($user->user_id === $targetUser->user_id) {
            return false; // Cannot change own role
        }
        return $this->permissionService->check($user, 'cmis.users.assign_role');
    }

    public function grantPermission(User $user, User $targetUser): bool
    {
        return $this->permissionService->check($user, 'cmis.users.grant_permission');
    }

    public function viewActivity(User $user, User $targetUser): bool
    {
        return $this->permissionService->check($user, 'cmis.users.view_activity');
    }
}
