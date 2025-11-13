<?php

namespace App\Policies;

use App\Models\CreativeAsset;
use App\Models\User;
use App\Services\PermissionService;

class CreativeAssetPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.creative_assets.view');
    }

    public function view(User $user, CreativeAsset $asset): bool
    {
        if (!$this->permissionService->check($user, 'cmis.creative_assets.view')) {
            return false;
        }
        return $asset->org_id === session('current_org_id');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.creative_assets.create');
    }

    public function update(User $user, CreativeAsset $asset): bool
    {
        if (!$this->permissionService->check($user, 'cmis.creative_assets.update')) {
            return false;
        }
        return $asset->org_id === session('current_org_id');
    }

    public function delete(User $user, CreativeAsset $asset): bool
    {
        if (!$this->permissionService->check($user, 'cmis.creative_assets.delete')) {
            return false;
        }
        return $asset->org_id === session('current_org_id');
    }

    public function download(User $user, CreativeAsset $asset): bool
    {
        if (!$this->permissionService->check($user, 'cmis.creative_assets.download')) {
            return false;
        }
        return $asset->org_id === session('current_org_id');
    }

    public function approve(User $user, CreativeAsset $asset): bool
    {
        return $this->permissionService->check($user, 'cmis.creative_assets.approve');
    }
}
