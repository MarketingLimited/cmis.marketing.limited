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
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.creative_assets.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.creative_assets.create');
    }

    public function update(User $user, CreativeAsset $asset): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.creative_assets.update');
    }

    public function delete(User $user, CreativeAsset $asset): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.creative_assets.delete');
    }

    public function download(User $user, CreativeAsset $asset): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.creative_assets.download');
    }

    public function approve(User $user, CreativeAsset $asset): bool
    {
        return $this->permissionService->check($user, 'cmis.creative_assets.approve');
    }
}
