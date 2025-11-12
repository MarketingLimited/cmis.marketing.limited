<?php

namespace App\Policies;

use App\Models\CreativeAsset;
use App\Models\User;

class CreativeAssetPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any creative assets.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission('creatives.view');
    }

    /**
     * Determine whether the user can view the creative asset.
     */
    public function view(User $user, CreativeAsset $creativeAsset): bool
    {
        return $this->resourceBelongsToOrg($creativeAsset)
            && $this->checkPermission('creatives.view');
    }

    /**
     * Determine whether the user can create creative assets.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission('creatives.create');
    }

    /**
     * Determine whether the user can update the creative asset.
     */
    public function update(User $user, CreativeAsset $creativeAsset): bool
    {
        return $this->resourceBelongsToOrg($creativeAsset)
            && $this->checkPermission('creatives.edit');
    }

    /**
     * Determine whether the user can delete the creative asset.
     */
    public function delete(User $user, CreativeAsset $creativeAsset): bool
    {
        return $this->resourceBelongsToOrg($creativeAsset)
            && $this->checkPermission('creatives.delete');
    }

    /**
     * Determine whether the user can restore the creative asset.
     */
    public function restore(User $user, CreativeAsset $creativeAsset): bool
    {
        return $this->resourceBelongsToOrg($creativeAsset)
            && $this->checkPermission('creatives.delete');
    }

    /**
     * Determine whether the user can permanently delete the creative asset.
     */
    public function forceDelete(User $user, CreativeAsset $creativeAsset): bool
    {
        return $this->isOwnerOrAdmin($user, $creativeAsset->org_id)
            && $this->checkPermission('creatives.delete');
    }
}
