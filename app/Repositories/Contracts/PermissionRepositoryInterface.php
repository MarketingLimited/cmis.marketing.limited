<?php

namespace App\Repositories\Contracts;

interface PermissionRepositoryInterface
{
    /**
     * Check if user can access campaign
     */
    public function canAccessCampaign(string $userId, string $campaignId): bool;

    /**
     * Check if user can manage org
     */
    public function canManageOrg(string $userId, string $orgId): bool;

    /**
     * Get user permissions for org
     */
    public function getUserOrgPermissions(string $userId, string $orgId): array;
}
