<?php

namespace App\Jobs\Backup;

use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Cleanup Expired Backups Job
 *
 * Daily job that:
 * 1. Deletes backup files that have expired
 * 2. Cleans up temporary files
 * 3. Updates backup records
 * 4. Creates audit logs
 */
class CleanupExpiredBackupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 600;

    /**
     * Create a new job instance
     */
    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info("Starting backup cleanup job");

        $stats = [
            'expired_deleted' => 0,
            'temp_cleaned' => 0,
            'errors' => 0,
        ];

        // Step 1: Delete expired backups
        $stats['expired_deleted'] = $this->deleteExpiredBackups();

        // Step 2: Clean up temporary files
        $stats['temp_cleaned'] = $this->cleanupTempFiles();

        // Step 3: Clean up orphaned files
        $this->cleanupOrphanedFiles();

        Log::info("Backup cleanup completed", $stats);
    }

    /**
     * Delete expired backup files and update records
     */
    protected function deleteExpiredBackups(): int
    {
        $deleted = 0;

        $expiredBackups = OrganizationBackup::where('status', 'completed')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredBackups as $backup) {
            try {
                // Delete file from storage
                if ($backup->file_path && Storage::disk($backup->storage_disk)->exists($backup->file_path)) {
                    Storage::disk($backup->storage_disk)->delete($backup->file_path);
                }

                // Update status
                $backup->update([
                    'status' => 'expired',
                    'file_path' => null,
                    'file_size' => 0,
                ]);

                // Create audit log
                BackupAuditLog::create([
                    'org_id' => $backup->org_id,
                    'action' => 'backup_deleted',
                    'entity_id' => $backup->id,
                    'entity_type' => 'organization_backup',
                    'user_id' => config('cmis.system_user_id'),
                    'details' => [
                        'reason' => 'expired',
                        'expired_at' => $backup->expires_at?->toISOString(),
                    ],
                ]);

                $deleted++;

                Log::info("Deleted expired backup", [
                    'backup_id' => $backup->id,
                    'org_id' => $backup->org_id,
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to delete expired backup", [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deleted;
    }

    /**
     * Clean up temporary files older than 24 hours
     */
    protected function cleanupTempFiles(): int
    {
        $cleaned = 0;
        $tempPath = config('backup.storage.temp_path', storage_path('app/temp/backups'));

        if (!is_dir($tempPath)) {
            return 0;
        }

        $threshold = now()->subHours(24)->timestamp;

        // Clean ZIP files
        $files = glob($tempPath . '/*.zip') + glob($tempPath . '/*.zip.enc');
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $cleaned++;
            }
        }

        // Clean restore directories
        $dirs = glob($tempPath . '/restore_*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if (filemtime($dir) < $threshold) {
                $this->deleteDirectory($dir);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Clean up orphaned files (files without backup records)
     */
    protected function cleanupOrphanedFiles(): void
    {
        $disks = array_keys(config('backup.storage.disks', []));

        foreach ($disks as $disk) {
            try {
                $basePath = config("backup.storage.disks.{$disk}.path", 'backups');
                $files = Storage::disk($disk)->allFiles($basePath);

                foreach ($files as $file) {
                    // Check if backup record exists
                    $exists = OrganizationBackup::where('file_path', $file)
                        ->where('status', 'completed')
                        ->exists();

                    if (!$exists) {
                        // Check file age (only delete if older than 48 hours)
                        $lastModified = Storage::disk($disk)->lastModified($file);
                        if ($lastModified < now()->subHours(48)->timestamp) {
                            Storage::disk($disk)->delete($file);
                            Log::info("Deleted orphaned backup file", ['path' => $file, 'disk' => $disk]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to clean orphaned files on disk", [
                    'disk' => $disk,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Recursively delete a directory
     */
    protected function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($path);
    }
}
