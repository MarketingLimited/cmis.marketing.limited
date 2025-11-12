<?php

namespace App\Repositories\Operations;

use Illuminate\Support\Facades\DB;

/**
 * Repository for Operations Audit Functions
 * Encapsulates PostgreSQL functions related to audit logging
 */
class AuditRepository
{
    /**
     * Purge old audit logs
     * Corresponds to: operations.purge_old_audit_logs()
     *
     * @param int $retentionDays Number of days to retain logs (default: 90)
     * @return int Number of deleted records
     */
    public function purgeOldAuditLogs(int $retentionDays = 90): int
    {
        $result = DB::select(
            'SELECT operations.purge_old_audit_logs(?) as deleted_count',
            [$retentionDays]
        );

        return $result[0]->deleted_count ?? 0;
    }

    /**
     * Audit trigger function (normally called by database triggers)
     * Corresponds to: operations.audit_trigger_function()
     *
     * Note: This function is automatically called by triggers on audited tables
     * Manual invocation is typically not needed but available for testing
     *
     * @return bool Success status
     */
    public function auditTriggerFunction(): bool
    {
        return DB::statement('SELECT operations.audit_trigger_function()');
    }
}
