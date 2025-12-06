<?php

namespace App\Jobs\Backup;

use App\Models\Backup\BackupAuditLog;
use App\Models\Backup\BackupSetting;
use App\Models\Backup\OrganizationBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Delete Backup Files Job
 *
 * Handles the actual file deletion after a backup is soft-deleted.
 * This allows for a retention period before permanent file removal.
 */
class DeleteBackupFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 120;

    /**
     * The backup ID to delete files for
     */
    protected string $backupId;

    /**
     * The organization ID
     */
    protected string $orgId;

    /**
     * User ID who initiated the deletion (for audit)
     */
    protected ?string $userId;

    /**
     * Create a new job instance
     */
    public function __construct(string $backupId, string $orgId, ?string $userId = null)
    {
        $this->backupId = $backupId;
        $this->orgId = $orgId;
        $this->userId = $userId;
        $this->onQueue('default');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info("Starting backup file deletion", [
            'backup_id' => $this->backupId,
            'org_id' => $this->orgId,
        ]);

        // Find the backup (including soft-deleted)
        $backup = OrganizationBackup::withTrashed()
            ->where('id', $this->backupId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$backup) {
            Log::warning("Backup not found for file deletion", [
                'backup_id' => $this->backupId,
            ]);
            return;
        }

        // Only proceed if the backup is still soft-deleted
        if (!$backup->trashed()) {
            Log::info("Backup was restored, skipping file deletion", [
                'backup_id' => $this->backupId,
            ]);
            return;
        }

        try {
            $fileDeleted = false;
            $filePath = $backup->file_path;
            $storageDisk = $backup->storage_disk ?? 'local';

            // Delete the backup file from storage
            if ($filePath && Storage::disk($storageDisk)->exists($filePath)) {
                Storage::disk($storageDisk)->delete($filePath);
                $fileDeleted = true;

                Log::info("Backup file deleted from storage", [
                    'backup_id' => $this->backupId,
                    'file_path' => $filePath,
                    'disk' => $storageDisk,
                ]);
            }

            // Update the backup record
            $backup->update([
                'file_path' => null,
                'file_size' => 0,
            ]);

            // Force delete the record (remove soft-delete)
            $backup->forceDelete();

            // Create audit log
            BackupAuditLog::create([
                'org_id' => $this->orgId,
                'action' => 'backup_files_deleted',
                'entity_id' => $this->backupId,
                'entity_type' => 'organization_backup',
                'user_id' => $this->userId ?? config('cmis.system_user_id'),
                'details' => [
                    'file_deleted' => $fileDeleted,
                    'file_path' => $filePath,
                    'storage_disk' => $storageDisk,
                    'deleted_after_retention' => true,
                ],
            ]);

            Log::info("Backup files deletion completed", [
                'backup_id' => $this->backupId,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to delete backup files", [
                'backup_id' => $this->backupId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Let it retry
        }
    }

    /**
     * Get the retention period delay in seconds
     *
     * @param string $orgId
     * @return int
     */
    public static function getRetentionDelay(string $orgId): int
    {
        $settings = BackupSetting::where('org_id', $orgId)->first();

        // Default retention: 7 days before permanent deletion
        $retentionDays = $settings?->deleted_file_retention_days ?? 7;

        return $retentionDays * 24 * 60 * 60; // Convert days to seconds
    }

    /**
     * Dispatch job with retention delay
     *
     * @param OrganizationBackup $backup
     * @param string|null $userId
     * @return void
     */
    public static function dispatchWithRetention(OrganizationBackup $backup, ?string $userId = null): void
    {
        $delay = self::getRetentionDelay($backup->org_id);

        self::dispatch($backup->id, $backup->org_id, $userId)
            ->delay(now()->addSeconds($delay));

        Log::info("Scheduled backup file deletion", [
            'backup_id' => $backup->id,
            'delay_seconds' => $delay,
            'scheduled_for' => now()->addSeconds($delay)->toISOString(),
        ]);
    }
}
