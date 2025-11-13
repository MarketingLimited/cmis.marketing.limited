<?php

namespace App\Policies;

use App\Models\Core\Org;
use App\Models\User;
use App\Services\PermissionService;

class OrganizationPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return true; // Users can see organizations they belong to
    }

    public function view(User $user, Org $org): bool
    {
        return $user->orgs()->where('cmis.orgs.org_id', $org->org_id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.orgs.create');
    }

    public function update(User $user, Org $org): bool
    {
        if (!$this->permissionService->check($user, 'cmis.orgs.update')) {
            return false;
        }
        return $org->org_id === session('current_org_id');
    }

    public function delete(User $user, Org $org): bool
    {
        return $this->permissionService->check($user, 'cmis.orgs.delete');
    }

    public function manageUsers(User $user, Org $org): bool
    {
        if (!$this->permissionService->check($user, 'cmis.orgs.manage_users')) {
            return false;
        }
        return $org->org_id === session('current_org_id');
    }

    public function manageSettings(User $user, Org $org): bool
    {
        if (!$this->permissionService->check($user, 'cmis.orgs.manage_settings')) {
            return false;
        }
        return $org->org_id === session('current_org_id');
    }
}
