<?php

namespace App\Policies;

use App\Models\Core\Integration;
use App\Models\User;

class IntegrationPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any integrations.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission('integrations.view');
    }

    /**
     * Determine whether the user can view the integration.
     */
    public function view(User $user, Integration $integration): bool
    {
        return $this->resourceBelongsToOrg($integration)
            && $this->checkPermission('integrations.view');
    }

    /**
     * Determine whether the user can create integrations.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission('integrations.manage');
    }

    /**
     * Determine whether the user can update the integration.
     */
    public function update(User $user, Integration $integration): bool
    {
        return $this->resourceBelongsToOrg($integration)
            && $this->checkPermission('integrations.manage');
    }

    /**
     * Determine whether the user can delete the integration.
     */
    public function delete(User $user, Integration $integration): bool
    {
        return $this->resourceBelongsToOrg($integration)
            && $this->checkPermission('integrations.manage');
    }

    /**
     * Determine whether the user can restore the integration.
     */
    public function restore(User $user, Integration $integration): bool
    {
        return $this->resourceBelongsToOrg($integration)
            && $this->checkPermission('integrations.manage');
    }

    /**
     * Determine whether the user can sync the integration.
     */
    public function sync(User $user, Integration $integration): bool
    {
        return $this->resourceBelongsToOrg($integration)
            && $this->checkPermission('integrations.manage');
    }

    /**
     * Determine whether the user can test the integration connection.
     */
    public function test(User $user, Integration $integration): bool
    {
        return $this->resourceBelongsToOrg($integration)
            && $this->checkPermission('integrations.view');
    }
}
