<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;
use App\Services\PermissionService;

class ChannelPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.channels.view');
    }

    public function view(User $user, Channel $channel): bool
    {
        if (!$this->permissionService->check($user, 'cmis.channels.view')) {
            return false;
        }
        return $channel->org_id === session('current_org_id');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.channels.create');
    }

    public function update(User $user, Channel $channel): bool
    {
        if (!$this->permissionService->check($user, 'cmis.channels.update')) {
            return false;
        }
        return $channel->org_id === session('current_org_id');
    }

    public function delete(User $user, Channel $channel): bool
    {
        if (!$this->permissionService->check($user, 'cmis.channels.delete')) {
            return false;
        }
        return $channel->org_id === session('current_org_id');
    }

    public function publish(User $user, Channel $channel): bool
    {
        if (!$this->permissionService->check($user, 'cmis.channels.publish')) {
            return false;
        }
        return $channel->org_id === session('current_org_id');
    }

    public function schedule(User $user, Channel $channel): bool
    {
        return $this->permissionService->check($user, 'cmis.channels.schedule');
    }

    public function viewAnalytics(User $user, Channel $channel): bool
    {
        return $this->permissionService->check($user, 'cmis.channels.view_analytics');
    }
}
