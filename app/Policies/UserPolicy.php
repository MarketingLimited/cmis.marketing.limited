<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view themselves OR if they have permission
        return $user->user_id === $model->user_id
            || $this->checkPermission('users.view');
    }

    /**
     * Determine whether the user can create users (invite).
     */
    public function create(User $user): bool
    {
        return $this->checkPermission('users.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update themselves OR if they have permission
        return $user->user_id === $model->user_id
            || $this->checkPermission('users.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->user_id === $model->user_id) {
            return false;
        }

        return $this->checkPermission('users.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->checkPermission('users.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only org owners can force delete users
        if ($user->user_id === $model->user_id) {
            return false;
        }

        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can update roles.
     */
    public function updateRole(User $user, User $model): bool
    {
        // Users cannot change their own role
        if ($user->user_id === $model->user_id) {
            return false;
        }

        return $this->isOwnerOrAdmin($user)
            && $this->checkPermission('users.manage');
    }

    /**
     * Determine whether the user can deactivate the model.
     */
    public function deactivate(User $user, User $model): bool
    {
        // Users cannot deactivate themselves
        if ($user->user_id === $model->user_id) {
            return false;
        }

        return $this->checkPermission('users.manage');
    }
}
