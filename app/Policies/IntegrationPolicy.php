<?php

namespace App\Policies;

use App\Models\Core\Integration;
use App\Models\User;
use App\Services\PermissionService;

class IntegrationPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.integrations.view');
    }

    public function view(User $user, Integration $integration): bool
    {
        if (!$this->permissionService->check($user, 'cmis.integrations.view')) {
            return false;
        }
        return $integration->org_id === session('current_org_id');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.integrations.create');
    }

    public function update(User $user, Integration $integration): bool
    {
        if (!$this->permissionService->check($user, 'cmis.integrations.update')) {
            return false;
        }
        return $integration->org_id === session('current_org_id');
    }

    public function delete(User $user, Integration $integration): bool
    {
        if (!$this->permissionService->check($user, 'cmis.integrations.delete')) {
            return false;
        }
        return $integration->org_id === session('current_org_id');
    }

    public function connect(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.integrations.connect');
    }

    public function disconnect(User $user, Integration $integration): bool
    {
        return $this->permissionService->check($user, 'cmis.integrations.disconnect');
    }

    public function sync(User $user, Integration $integration): bool
    {
        return $this->permissionService->check($user, 'cmis.integrations.sync');
    }
}
