<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use App\Services\PermissionService;

class CampaignPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        // RLS ensures org isolation at database level
        // Only check permission here
        return $this->permissionService->check($user, 'cmis.campaigns.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.campaigns.update');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.campaigns.delete');
    }

    public function restore(User $user, Campaign $campaign): bool
    {
        return $this->permissionService->check($user, 'cmis.campaigns.restore');
    }

    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return $this->permissionService->check($user, 'cmis.campaigns.force_delete');
    }

    public function publish(User $user, Campaign $campaign): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.campaigns.publish');
    }

    public function viewAnalytics(User $user, Campaign $campaign): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.campaigns.view_analytics');
    }
}
