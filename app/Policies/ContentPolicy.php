<?php

namespace App\Policies;

use App\Models\Content\ContentItem;
use App\Models\User;
use App\Services\PermissionService;

class ContentPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.content.view');
    }

    public function view(User $user, ContentItem $content): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.content.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.content.create');
    }

    public function update(User $user, ContentItem $content): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.content.update');
    }

    public function delete(User $user, ContentItem $content): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.content.delete');
    }

    public function publish(User $user, ContentItem $content): bool
    {
        // RLS ensures org isolation at database level
        return $this->permissionService->check($user, 'cmis.content.publish');
    }

    public function schedule(User $user, ContentItem $content): bool
    {
        return $this->permissionService->check($user, 'cmis.content.schedule');
    }
}
