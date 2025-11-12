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
        if (!$this->permissionService->check($user, 'cmis.campaigns.view')) {
            return false;
        }
        return $campaign->org_id === session('current_org_id');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        if (!$this->permissionService->check($user, 'cmis.campaigns.update')) {
            return false;
        }
        return $campaign->org_id === session('current_org_id');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        if (!$this->permissionService->check($user, 'cmis.campaigns.delete')) {
            return false;
        }
        return $campaign->org_id === session('current_org_id');
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
        if (!$this->permissionService->check($user, 'cmis.campaigns.publish')) {
            return false;
        }
        return $campaign->org_id === session('current_org_id');
    }

    public function viewAnalytics(User $user, Campaign $campaign): bool
    {
        if (!$this->permissionService->check($user, 'cmis.campaigns.view_analytics')) {
            return false;
        }
        return $campaign->org_id === session('current_org_id');
    }
}
