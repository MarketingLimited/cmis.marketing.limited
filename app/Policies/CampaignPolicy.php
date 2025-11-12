<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission('campaigns.view');
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $this->resourceBelongsToOrg($campaign)
            && $this->checkPermission('campaigns.view');
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission('campaigns.create');
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $this->resourceBelongsToOrg($campaign)
            && $this->checkPermission('campaigns.edit');
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $this->resourceBelongsToOrg($campaign)
            && $this->checkPermission('campaigns.delete');
    }

    /**
     * Determine whether the user can restore the campaign.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        return $this->resourceBelongsToOrg($campaign)
            && $this->checkPermission('campaigns.delete');
    }

    /**
     * Determine whether the user can permanently delete the campaign.
     */
    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return $this->isOwnerOrAdmin($user, $campaign->org_id)
            && $this->checkPermission('campaigns.delete');
    }
}
