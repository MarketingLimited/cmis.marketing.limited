<?php

namespace App\Policies;

use App\Models\Offering;
use App\Models\User;
use App\Services\PermissionService;

class OfferingPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.offerings.view');
    }

    public function view(User $user, Offering $offering): bool
    {
        if (!$this->permissionService->check($user, 'cmis.offerings.view')) {
            return false;
        }
        return $offering->org_id === session('current_org_id');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.offerings.create');
    }

    public function update(User $user, Offering $offering): bool
    {
        if (!$this->permissionService->check($user, 'cmis.offerings.update')) {
            return false;
        }
        return $offering->org_id === session('current_org_id');
    }

    public function delete(User $user, Offering $offering): bool
    {
        if (!$this->permissionService->check($user, 'cmis.offerings.delete')) {
            return false;
        }
        return $offering->org_id === session('current_org_id');
    }

    public function manageBundle(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.offerings.manage_bundles');
    }

    public function managePricing(User $user, Offering $offering): bool
    {
        return $this->permissionService->check($user, 'cmis.offerings.manage_pricing');
    }
}
