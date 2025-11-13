<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class AIPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function generateContent(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.generate_content');
    }

    public function generateCampaign(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.generate_campaign');
    }

    public function viewRecommendations(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.view_recommendations');
    }

    public function useSemanticSearch(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.semantic_search');
    }

    public function manageKnowledge(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.manage_knowledge');
    }

    public function managePrompts(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.manage_prompts');
    }

    public function viewInsights(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.ai.view_insights');
    }
}
