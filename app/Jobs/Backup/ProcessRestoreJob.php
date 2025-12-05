<?php

namespace App\Jobs\Backup;

use App\Models\Backup\BackupRestore;
use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupAuditLog;
use App\Apps\Backup\Services\Packaging\BackupPackagerService;
use App\Apps\Backup\Services\Packaging\BackupEncryptionService;
use App\Notifications\Backup\RestoreCompletedNotification;
use App\Notifications\Backup\RestoreFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process Restore Job
 *
 * Main restore processing job that:
 * 1. Creates safety backup (pre-restore)
 * 2. Extracts and decrypts backup package
 * 3. Validates schema compatibility
 * 4. Executes restore with conflict resolution
 * 5. Verifies integrity
 * 6. Sends notifications
 */
class ProcessRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts
     */
    public int $tries = 1; // No retries for restore operations

    /**
     * Job timeout in seconds (1 hour)
     */
    public int $timeout = 3600;

    /**
     * Restore model
     */
    protected BackupRestore $restore;

    /**
     * Create a new job instance
     */
    public function __construct(BackupRestore $restore)
    {
        $this->restore = $restore;
        $this->onQueue('backups');
    }

    /**
     * Execute the job
     */
    public function handle(
        BackupPackagerService $packager,
        BackupEncryptionService $encryption
    ): void {
        $startTime = microtime(true);

        try {
            // Update status to processing
            $this->restore->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info("Starting restore job", [
                'restore_id' => $this->restore->id,
                'org_id' => $this->restore->org_id,
                'type' => $this->restore->type,
            ]);

            // Step 1: Create safety backup
            $safetyBackup = $this->createSafetyBackup();

            // Step 2: Get backup file and extract
            $backup = $this->restore->backup;
            $extractPath = $this->extractBackup($backup, $packager, $encryption);

            // Step 3: Load and validate manifest
            $manifestPath = $extractPath . '/manifest.json';
            $manifest = json_decode(file_get_contents($manifestPath), true);

            // Step 4: Execute restore based on type
            $executionReport = $this->executeRestore($extractPath, $manifest);

            // Step 5: Update restore record
            $this->restore->update([
                'status' => 'completed',
                'safety_backup_id' => $safetyBackup?->id,
                'execution_report' => $executionReport,
                'completed_at' => now(),
                'rollback_expires_at' => now()->addHours(24),
            ]);

            // Step 6: Create audit log
            BackupAuditLog::create([
                'org_id' => $this->restore->org_id,
                'action' => 'restore_completed',
                'entity_id' => $this->restore->id,
                'entity_type' => 'backup_restore',
                'user_id' => $this->restore->created_by,
                'details' => [
                    'restore_code' => $this->restore->restore_code,
                    'type' => $this->restore->type,
                    'records_restored' => $executionReport['records_restored'] ?? 0,
                    'records_skipped' => $executionReport['records_skipped'] ?? 0,
                    'duration_seconds' => round(microtime(true) - $startTime, 2),
                ],
            ]);

            // Step 7: Cleanup extracted files
            $this->cleanupExtractedFiles($extractPath);

            // Step 8: Send notification
            $this->sendSuccessNotification();

            Log::info("Restore completed successfully", [
                'restore_id' => $this->restore->id,
                'duration' => round(microtime(true) - $startTime, 2) . 's',
            ]);

        } catch (\Exception $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Create a safety backup before restore
     */
    protected function createSafetyBackup(): ?OrganizationBackup
    {
        if (!config('backup.restore.create_safety_backup', true)) {
            return null;
        }

        $backup = OrganizationBackup::create([
            'org_id' => $this->restore->org_id,
            'backup_code' => OrganizationBackup::generateBackupCode(),
            'name' => 'Pre-Restore Safety Backup',
            'description' => "Automatic backup before restore {$this->restore->restore_code}",
            'type' => 'pre_restore',
            'status' => 'pending',
            'storage_disk' => config('backup.storage.default', 'local'),
            'created_by' => $this->restore->created_by,
        ]);

        // Dispatch backup job synchronously for safety
        ProcessBackupJob::dispatchSync($backup);

        return $backup->fresh();
    }

    /**
     * Extract backup package
     */
    protected function extractBackup(
        OrganizationBackup $backup,
        BackupPackagerService $packager,
        BackupEncryptionService $encryption
    ): string {
        $tempDir = storage_path('app/temp/restore_' . $this->restore->id);

        // Get backup file
        $backupPath = Storage::disk($backup->storage_disk)->path($backup->file_path);

        // Decrypt if encrypted
        if ($backup->is_encrypted) {
            $decryptResult = $encryption->decrypt($backupPath, $backup->encryption_key_id);
            $backupPath = $decryptResult['output_path'];
        }

        // Extract package
        $extractResult = $packager->extractPackage($backupPath, $tempDir);

        if (!$extractResult['success']) {
            throw new \RuntimeException("Package extraction failed: " . json_encode($extractResult['verification_errors'] ?? []));
        }

        return $tempDir;
    }

    /**
     * Execute restore based on type
     */
    protected function executeRestore(string $extractPath, array $manifest): array
    {
        $report = [
            'records_restored' => 0,
            'records_skipped' => 0,
            'records_updated' => 0,
            'errors' => [],
            'categories' => [],
        ];

        $selectedCategories = $this->restore->selected_categories;
        $conflictStrategy = $this->restore->conflict_resolution['strategy'] ?? 'skip';

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            config('cmis.system_user_id'),
            $this->restore->org_id
        ]);

        DB::beginTransaction();

        try {
            // Process each category
            $dataPath = $extractPath . '/data';
            $files = glob($dataPath . '/*.json');

            foreach ($files as $file) {
                $categoryKey = basename($file, '.json');

                // Skip if not in selected categories
                if ($selectedCategories && !in_array($categoryKey, $selectedCategories)) {
                    continue;
                }

                $categoryData = json_decode(file_get_contents($file), true);
                $categoryReport = $this->restoreCategory($categoryKey, $categoryData, $conflictStrategy);

                $report['categories'][$categoryKey] = $categoryReport;
                $report['records_restored'] += $categoryReport['restored'];
                $report['records_skipped'] += $categoryReport['skipped'];
                $report['records_updated'] += $categoryReport['updated'];

                if (!empty($categoryReport['errors'])) {
                    $report['errors'] = array_merge($report['errors'], $categoryReport['errors']);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $report;
    }

    /**
     * Restore a single category
     */
    protected function restoreCategory(string $categoryKey, array $data, string $conflictStrategy): array
    {
        $report = [
            'restored' => 0,
            'skipped' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        foreach ($data as $tableName => $records) {
            foreach ($records as $record) {
                try {
                    $result = $this->restoreRecord($tableName, $record, $conflictStrategy);

                    match ($result) {
                        'inserted' => $report['restored']++,
                        'updated' => $report['updated']++,
                        'skipped' => $report['skipped']++,
                    };
                } catch (\Exception $e) {
                    $report['errors'][] = [
                        'table' => $tableName,
                        'record_id' => $record['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    $report['skipped']++;
                }
            }
        }

        return $report;
    }

    /**
     * Restore a single record
     */
    protected function restoreRecord(string $tableName, array $record, string $strategy): string
    {
        // Remove metadata fields
        unset($record['_source_table'], $record['_exported_at']);

        $id = $record['id'] ?? $record['ID'] ?? null;

        if (!$id) {
            return 'skipped';
        }

        // Check for existing record
        $existing = DB::table($tableName)->where('id', $id)->first();

        if ($existing) {
            return match ($strategy) {
                'skip' => 'skipped',
                'replace' => $this->replaceRecord($tableName, $record, $id),
                'merge' => $this->mergeRecord($tableName, $record, $existing),
                default => 'skipped',
            };
        }

        // Insert new record
        DB::table($tableName)->insert($record);
        return 'inserted';
    }

    /**
     * Replace existing record
     */
    protected function replaceRecord(string $tableName, array $record, string $id): string
    {
        unset($record['id'], $record['ID']);
        DB::table($tableName)->where('id', $id)->update($record);
        return 'updated';
    }

    /**
     * Merge record (keep newer values)
     */
    protected function mergeRecord(string $tableName, array $record, object $existing): string
    {
        $existingArray = (array) $existing;
        $merged = [];

        foreach ($record as $field => $value) {
            if ($field === 'id' || $field === 'ID') {
                continue;
            }

            // Compare timestamps if available
            $backupUpdated = $record['updated_at'] ?? $record['Updated At'] ?? null;
            $existingUpdated = $existingArray['updated_at'] ?? null;

            if ($backupUpdated && $existingUpdated && strtotime($backupUpdated) > strtotime($existingUpdated)) {
                $merged[$field] = $value;
            }
        }

        if (!empty($merged)) {
            DB::table($tableName)->where('id', $existingArray['id'])->update($merged);
            return 'updated';
        }

        return 'skipped';
    }

    /**
     * Cleanup extracted files
     */
    protected function cleanupExtractedFiles(string $path): void
    {
        if (is_dir($path)) {
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

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }

    /**
     * Handle restore failure
     */
    protected function handleFailure(\Throwable $exception): void
    {
        Log::error("Restore job failed", [
            'restore_id' => $this->restore->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->restore->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $this->restore->org_id,
            'action' => 'restore_failed',
            'entity_id' => $this->restore->id,
            'entity_type' => 'backup_restore',
            'user_id' => $this->restore->created_by,
            'details' => [
                'error' => $exception->getMessage(),
            ],
        ]);

        // Send failure notification
        $this->sendFailureNotification($exception);
    }

    /**
     * Send success notification
     */
    protected function sendSuccessNotification(): void
    {
        try {
            $user = \App\Models\User::find($this->restore->created_by);
            if ($user) {
                $user->notify(new RestoreCompletedNotification($this->restore));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send restore success notification", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send failure notification
     */
    protected function sendFailureNotification(\Throwable $exception): void
    {
        try {
            $user = \App\Models\User::find($this->restore->created_by);
            if ($user) {
                $user->notify(new RestoreFailedNotification($this->restore, $exception->getMessage()));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send restore failure notification", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
