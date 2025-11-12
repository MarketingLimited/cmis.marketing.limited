<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Permission and Security Functions
 * Encapsulates PostgreSQL functions related to permissions, security, and access control
 */
class PermissionRepository
{
    /**
     * Check if a user has a specific permission in an organization
     * Corresponds to: cmis.check_permission(p_user_id, p_org_id, p_permission_code)
     *
     * @param string $userId UUID of the user
     * @param string $orgId UUID of the organization
     * @param string $permissionCode Permission code to check
     * @return bool True if user has the permission
     */
    public function checkPermission(string $userId, string $orgId, string $permissionCode): bool
    {
        $result = DB::select(
            'SELECT cmis.check_permission(?, ?, ?) as has_permission',
            [$userId, $orgId, $permissionCode]
        );

        return $result[0]->has_permission ?? false;
    }

    /**
     * Check permission using transaction context
     * Corresponds to: cmis.check_permission_tx(p_permission)
     *
     * @param string $permission Permission code to check
     * @return bool True if user has the permission
     */
    public function checkPermissionWithTransaction(string $permission): bool
    {
        $result = DB::select(
            'SELECT cmis.check_permission_tx(?) as has_permission',
            [$permission]
        );

        return $result[0]->has_permission ?? false;
    }

    /**
     * Initialize transaction context for a user and organization
     * Corresponds to: cmis.init_transaction_context(p_user_id, p_org_id)
     *
     * @param string $userId UUID of the user
     * @param string $orgId UUID of the organization
     * @return bool Success status
     */
    public function initTransactionContext(string $userId, string $orgId): bool
    {
        return DB::statement(
            'SELECT cmis.init_transaction_context(?, ?)',
            [$userId, $orgId]
        );
    }

    /**
     * Validate transaction context
     * Corresponds to: cmis.validate_transaction_context()
     *
     * @return Collection Collection of validation results
     */
    public function validateTransactionContext(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.validate_transaction_context()');

        return collect($results);
    }

    /**
     * Get current user ID from context
     * Corresponds to: cmis.get_current_user_id()
     *
     * @return string|null UUID of current user
     */
    public function getCurrentUserId(): ?string
    {
        $result = DB::select('SELECT cmis.get_current_user_id() as user_id');

        return $result[0]->user_id ?? null;
    }

    /**
     * Get current user ID from transaction context
     * Corresponds to: cmis.get_current_user_id_tx()
     *
     * @return string|null UUID of current user
     */
    public function getCurrentUserIdFromTransaction(): ?string
    {
        $result = DB::select('SELECT cmis.get_current_user_id_tx() as user_id');

        return $result[0]->user_id ?? null;
    }

    /**
     * Get current organization ID from context
     * Corresponds to: cmis.get_current_org_id()
     *
     * @return string|null UUID of current organization
     */
    public function getCurrentOrgId(): ?string
    {
        $result = DB::select('SELECT cmis.get_current_org_id() as org_id');

        return $result[0]->org_id ?? null;
    }

    /**
     * Get current organization ID from transaction context
     * Corresponds to: cmis.get_current_org_id_tx()
     *
     * @return string|null UUID of current organization
     */
    public function getCurrentOrgIdFromTransaction(): ?string
    {
        $result = DB::select('SELECT cmis.get_current_org_id_tx() as org_id');

        return $result[0]->org_id ?? null;
    }

    /**
     * Refresh permissions cache
     * Corresponds to: cmis.refresh_permissions_cache()
     * Note: This is a trigger function, but can be called manually
     *
     * @return bool Success status
     */
    public function refreshPermissionsCache(): bool
    {
        return DB::statement('SELECT cmis.refresh_permissions_cache()');
    }

    /**
     * Test new security context
     * Corresponds to: cmis.test_new_security_context()
     *
     * @return Collection Collection of test results
     */
    public function testSecurityContext(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.test_new_security_context()');

        return collect($results);
    }
}
