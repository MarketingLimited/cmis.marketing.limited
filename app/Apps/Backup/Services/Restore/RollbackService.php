<?php

namespace App\Apps\Backup\Services\Restore;

use App\Models\Backup\BackupRestore;
use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupAuditLog;
use App\Jobs\Backup\ProcessRestoreJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rollback Service
 *
 * Handles rollback operations within the 24-hour window
 * using the safety backup created before restore.
 */
class RollbackService
{
    protected RestoreOrchestrator $orchestrator;

    public function __construct(RestoreOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Rollback a restore operation
     */
    public function rollback(BackupRestore $restore): array
    {
        // Verify rollback is allowed
        if (!$restore->canRollback()) {
            throw new \Exception(__('backup.rollback_expired'));
        }

        if (!$restore->safety_backup_id) {
            throw new \Exception(__('backup.no_safety_backup'));
        }

        // Get safety backup
        $safetyBackup = OrganizationBackup::find($restore->safety_backup_id);

        if (!$safetyBackup || $safetyBackup->status !== 'completed') {
            throw new \Exception(__('backup.safety_backup_invalid'));
        }

        // Create rollback restore record
        $rollbackRestore = BackupRestore::create([
            'org_id' => $restore->org_id,
            'backup_id' => $safetyBackup->id,
            'restore_code' => BackupRestore::generateRestoreCode(),
            'type' => 'full',
            'status' => 'pending',
            'selected_categories' => null, // Full restore
            'conflict_resolution' => [
                'strategy' => 'replace', // Always replace for rollback
                'decisions' => [],
            ],
            'created_by' => auth()->id(),
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $restore->org_id,
            'action' => 'restore_rolled_back',
            'entity_id' => $restore->id,
            'entity_type' => 'backup_restore',
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => [
                'original_restore_code' => $restore->restore_code,
                'rollback_restore_code' => $rollbackRestore->restore_code,
                'safety_backup_code' => $safetyBackup->backup_code,
            ],
        ]);

        // Mark original restore as rolled back
        $restore->update([
            'status' => 'rolled_back',
            'rollback_expires_at' => null,
        ]);

        // Dispatch rollback job
        ProcessRestoreJob::dispatch($rollbackRestore);

        return [
            'success' => true,
            'message' => __('backup.rollback_started'),
            'rollback_restore' => $rollbackRestore,
        ];
    }

    /**
     * Check if a restore can be rolled back
     */
    public function canRollback(BackupRestore $restore): array
    {
        $result = [
            'can_rollback' => false,
            'reason' => null,
            'expires_at' => null,
            'time_remaining' => null,
        ];

        // Check status
        if ($restore->status !== 'completed') {
            $result['reason'] = __('backup.rollback_status_invalid');
            return $result;
        }

        // Check rollback expiration
        if (!$restore->rollback_expires_at) {
            $result['reason'] = __('backup.rollback_not_available');
            return $result;
        }

        if ($restore->rollback_expires_at->isPast()) {
            $result['reason'] = __('backup.rollback_expired');
            return $result;
        }

        // Check safety backup
        if (!$restore->safety_backup_id) {
            $result['reason'] = __('backup.no_safety_backup');
            return $result;
        }

        $safetyBackup = OrganizationBackup::find($restore->safety_backup_id);

        if (!$safetyBackup) {
            $result['reason'] = __('backup.safety_backup_missing');
            return $result;
        }

        if ($safetyBackup->status !== 'completed') {
            $result['reason'] = __('backup.safety_backup_incomplete');
            return $result;
        }

        // All checks passed
        $result['can_rollback'] = true;
        $result['expires_at'] = $restore->rollback_expires_at;
        $result['time_remaining'] = $restore->rollback_expires_at->diffForHumans();

        return $result;
    }

    /**
     * Extend rollback window (admin only)
     */
    public function extendRollbackWindow(BackupRestore $restore, int $hours = 24): array
    {
        if ($restore->status !== 'completed') {
            throw new \Exception(__('backup.cannot_extend_rollback'));
        }

        $newExpiry = now()->addHours($hours);

        $restore->update([
            'rollback_expires_at' => $newExpiry,
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $restore->org_id,
            'action' => 'rollback_extended',
            'entity_id' => $restore->id,
            'entity_type' => 'backup_restore',
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => [
                'new_expiry' => $newExpiry->toIso8601String(),
                'hours_extended' => $hours,
            ],
        ]);

        return [
            'success' => true,
            'message' => __('backup.rollback_extended'),
            'new_expiry' => $newExpiry,
        ];
    }

    /**
     * Get rollback history for an organization
     */
    public function getRollbackHistory(string $orgId): array
    {
        $restores = BackupRestore::where('org_id', $orgId)
            ->whereIn('status', ['completed', 'rolled_back'])
            ->whereNotNull('safety_backup_id')
            ->with(['backup', 'safetyBackup'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return $restores->map(function ($restore) {
            return [
                'id' => $restore->id,
                'restore_code' => $restore->restore_code,
                'type' => $restore->type,
                'status' => $restore->status,
                'completed_at' => $restore->completed_at,
                'can_rollback' => $restore->canRollback(),
                'rollback_expires_at' => $restore->rollback_expires_at,
                'backup_name' => $restore->backup?->name,
                'safety_backup_code' => $restore->safetyBackup?->backup_code,
            ];
        })->toArray();
    }

    /**
     * Cleanup expired rollback data
     */
    public function cleanupExpiredRollbacks(): int
    {
        $expiredRestores = BackupRestore::where('status', 'completed')
            ->whereNotNull('rollback_expires_at')
            ->where('rollback_expires_at', '<', now())
            ->get();

        $cleaned = 0;

        foreach ($expiredRestores as $restore) {
            // Delete safety backup if it exists
            if ($restore->safety_backup_id) {
                $safetyBackup = OrganizationBackup::find($restore->safety_backup_id);

                if ($safetyBackup) {
                    // Delete backup file
                    if ($safetyBackup->file_path) {
                        $disk = \Storage::disk($safetyBackup->storage_disk);
                        if ($disk->exists($safetyBackup->file_path)) {
                            $disk->delete($safetyBackup->file_path);
                        }
                    }

                    // Soft delete backup record
                    $safetyBackup->delete();
                }
            }

            // Clear rollback window
            $restore->update([
                'rollback_expires_at' => null,
                'safety_backup_id' => null,
            ]);

            $cleaned++;
        }

        return $cleaned;
    }
}
