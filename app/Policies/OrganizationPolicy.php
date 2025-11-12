<?php

namespace App\Policies;

use App\Models\Core\Org;
use App\Models\User;

class OrganizationPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        // Users can see their own organizations
        return true;
    }

    /**
     * Determine whether the user can view the organization.
     */
    public function view(User $user, Org $org): bool
    {
        // Users can only view organizations they belong to
        return $user->belongsToOrg($org->org_id);
    }

    /**
     * Determine whether the user can create organizations.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create an organization
        return true;
    }

    /**
     * Determine whether the user can update the organization.
     */
    public function update(User $user, Org $org): bool
    {
        return $this->isOwnerOrAdmin($user, $org->org_id)
            && $this->checkPermission('orgs.manage');
    }

    /**
     * Determine whether the user can delete the organization.
     */
    public function delete(User $user, Org $org): bool
    {
        // Only owners can delete organizations
        return $user->hasRoleInOrg($org->org_id, 'owner');
    }

    /**
     * Determine whether the user can restore the organization.
     */
    public function restore(User $user, Org $org): bool
    {
        return $user->hasRoleInOrg($org->org_id, 'owner');
    }

    /**
     * Determine whether the user can view organization statistics.
     */
    public function viewStatistics(User $user, Org $org): bool
    {
        return $user->belongsToOrg($org->org_id)
            && $this->checkPermission('orgs.view');
    }

    /**
     * Determine whether the user can manage organization members.
     */
    public function manageMembers(User $user, Org $org): bool
    {
        return $this->isOwnerOrAdmin($user, $org->org_id)
            && $this->checkPermission('users.manage');
    }
}
