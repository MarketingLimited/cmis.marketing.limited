<?php

namespace App\Apps\Backup\Services\Restore;

use App\Models\Backup\BackupRestore;
use App\Models\Backup\OrganizationBackup;
use App\Apps\Backup\Services\Packaging\BackupPackagerService;
use App\Apps\Backup\Services\Packaging\BackupEncryptionService;
use App\Apps\Backup\Services\Packaging\ChecksumService;
use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Restore Orchestrator
 *
 * Coordinates the restore workflow including analysis, reconciliation,
 * conflict resolution, and data restoration.
 */
class RestoreOrchestrator
{
    protected BackupPackagerService $packager;
    protected BackupEncryptionService $encryption;
    protected ChecksumService $checksum;
    protected SchemaReconcilerService $reconciler;
    protected ConflictResolverService $conflictResolver;
    protected RestoreExecutorService $executor;
    protected RollbackService $rollbackService;
    protected SchemaDiscoveryService $schemaDiscovery;

    public function __construct(
        BackupPackagerService $packager,
        BackupEncryptionService $encryption,
        ChecksumService $checksum,
        SchemaReconcilerService $reconciler,
        ConflictResolverService $conflictResolver,
        RestoreExecutorService $executor,
        RollbackService $rollbackService,
        SchemaDiscoveryService $schemaDiscovery
    ) {
        $this->packager = $packager;
        $this->encryption = $encryption;
        $this->checksum = $checksum;
        $this->reconciler = $reconciler;
        $this->conflictResolver = $conflictResolver;
        $this->executor = $executor;
        $this->rollbackService = $rollbackService;
        $this->schemaDiscovery = $schemaDiscovery;
    }

    /**
     * Analyze a backup for restore compatibility
     */
    public function analyze(BackupRestore $restore): array
    {
        $restore->update(['status' => 'analyzing']);

        try {
            // Get backup file
            $backup = $restore->backup;
            if (!$backup) {
                throw new \Exception('Backup not found');
            }

            // Download and extract backup
            $extractedPath = $this->extractBackup($backup);

            // Read manifest
            $manifest = $this->readManifest($extractedPath);

            // Get backup schema snapshot
            $backupSchema = $manifest['schema_snapshot'] ?? [];

            // Compare with current schema
            $reconciliationReport = $this->reconciler->reconcile($backupSchema);

            // Analyze potential conflicts
            $conflictPreview = $this->conflictResolver->previewConflicts(
                $restore->org_id,
                $extractedPath,
                $manifest
            );

            // Update restore record
            $restore->update([
                'status' => 'awaiting_confirmation',
                'reconciliation_report' => $reconciliationReport->toArray(),
                'conflict_resolution' => [
                    'strategy' => 'skip',
                    'preview' => $conflictPreview,
                    'decisions' => [],
                ],
            ]);

            // Cleanup extracted files
            $this->cleanupExtracted($extractedPath);

            return [
                'success' => true,
                'reconciliation' => $reconciliationReport->toArray(),
                'conflict_preview' => $conflictPreview,
            ];
        } catch (\Exception $e) {
            $restore->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process restore with selected options
     */
    public function process(BackupRestore $restore, array $options = []): array
    {
        $restore->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            // Set RLS context for safety backup
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                config('cmis.system_user_id'),
                $restore->org_id
            ]);

            // Create safety backup if not full restore
            if ($restore->type !== 'full') {
                $safetyBackup = $this->createSafetyBackup($restore);
                $restore->update(['safety_backup_id' => $safetyBackup->id]);
            }

            // Extract backup
            $backup = $restore->backup;
            $extractedPath = $this->extractBackup($backup);

            // Read manifest
            $manifest = $this->readManifest($extractedPath);

            // Determine categories to restore
            $categories = $restore->selected_categories ?? array_keys($manifest['data_files'] ?? []);

            // Get conflict resolution strategy
            $conflictResolution = $restore->conflict_resolution ?? ['strategy' => 'skip'];

            // Execute restore
            $executionReport = $this->executor->execute(
                $restore->org_id,
                $extractedPath,
                $manifest,
                $categories,
                $conflictResolution,
                $restore->type
            );

            // Update restore record
            $restore->update([
                'status' => 'completed',
                'completed_at' => now(),
                'execution_report' => $executionReport,
                'rollback_expires_at' => now()->addHours(24),
            ]);

            // Cleanup
            $this->cleanupExtracted($extractedPath);

            return [
                'success' => true,
                'execution_report' => $executionReport,
            ];
        } catch (\Exception $e) {
            $restore->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Rollback a restore operation
     */
    public function rollback(BackupRestore $restore): array
    {
        if (!$restore->canRollback()) {
            throw new \Exception('Rollback window has expired');
        }

        if (!$restore->safety_backup_id) {
            throw new \Exception('No safety backup available for rollback');
        }

        return $this->rollbackService->rollback($restore);
    }

    /**
     * Extract backup file to temporary directory
     */
    protected function extractBackup(OrganizationBackup $backup): string
    {
        $disk = Storage::disk($backup->storage_disk);

        if (!$disk->exists($backup->file_path)) {
            throw new \Exception('Backup file not found');
        }

        // Create temp directory
        $tempDir = storage_path('app/temp/restore_' . Str::uuid());
        mkdir($tempDir, 0755, true);

        // Copy file to temp
        $localPath = $tempDir . '/backup.zip';

        if ($backup->is_encrypted) {
            // Download encrypted file
            $encryptedPath = $tempDir . '/backup.zip.enc';
            file_put_contents($encryptedPath, $disk->get($backup->file_path));

            // Decrypt
            $decrypted = $this->encryption->decrypt(
                $encryptedPath,
                $backup->encryption_key_id
            );

            if (!$decrypted['success']) {
                throw new \Exception('Failed to decrypt backup: ' . ($decrypted['error'] ?? 'Unknown error'));
            }

            $localPath = $decrypted['output_path'];

            // Cleanup encrypted file
            unlink($encryptedPath);
        } else {
            file_put_contents($localPath, $disk->get($backup->file_path));
        }

        // Verify checksum
        if ($backup->checksum_sha256) {
            if (!$this->checksum->verifyFile($localPath, $backup->checksum_sha256)) {
                throw new \Exception('Backup checksum verification failed');
            }
        }

        // Extract
        $extracted = $this->packager->extractPackage($localPath, $tempDir . '/extracted');

        if (!$extracted['success']) {
            throw new \Exception('Failed to extract backup: ' . ($extracted['error'] ?? 'Unknown error'));
        }

        return $extracted['extract_path'];
    }

    /**
     * Read manifest from extracted backup
     */
    protected function readManifest(string $extractedPath): array
    {
        $manifestPath = $extractedPath . '/manifest.json';

        if (!file_exists($manifestPath)) {
            throw new \Exception('Manifest file not found in backup');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid manifest JSON');
        }

        return $manifest;
    }

    /**
     * Create safety backup before restore
     */
    protected function createSafetyBackup(BackupRestore $restore): OrganizationBackup
    {
        $backup = OrganizationBackup::create([
            'org_id' => $restore->org_id,
            'backup_code' => OrganizationBackup::generateBackupCode(),
            'name' => 'Pre-Restore Safety Backup',
            'description' => 'Automatic backup created before restore ' . $restore->restore_code,
            'type' => 'pre_restore',
            'status' => 'pending',
            'storage_disk' => config('backup.storage.default', 'local'),
            'created_by' => auth()->id(),
        ]);

        // Dispatch backup job synchronously
        \App\Jobs\Backup\ProcessBackupJob::dispatchSync($backup);

        // Refresh to get updated status
        $backup->refresh();

        if ($backup->status !== 'completed') {
            throw new \Exception('Failed to create safety backup');
        }

        return $backup;
    }

    /**
     * Cleanup extracted temporary files
     */
    protected function cleanupExtracted(string $path): void
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
     * Get restore progress
     */
    public function getProgress(BackupRestore $restore): array
    {
        return [
            'id' => $restore->id,
            'status' => $restore->status,
            'type' => $restore->type,
            'started_at' => $restore->started_at,
            'completed_at' => $restore->completed_at,
            'execution_report' => $restore->execution_report,
            'error_message' => $restore->error_message,
            'can_rollback' => $restore->canRollback(),
        ];
    }

    /**
     * Upload and validate external backup file
     */
    public function uploadExternalBackup(string $orgId, string $filePath, string $fileName): array
    {
        // Validate file extension
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!in_array($extension, ['zip', 'enc'])) {
            throw new \Exception('Invalid file type. Only .zip and .enc files are allowed.');
        }

        // Create temp directory
        $tempDir = storage_path('app/temp/upload_' . Str::uuid());
        mkdir($tempDir, 0755, true);

        $localPath = $tempDir . '/' . $fileName;
        copy($filePath, $localPath);

        try {
            // If encrypted, require decryption key
            if ($extension === 'enc') {
                // For now, we don't support encrypted external backups without key
                throw new \Exception('Encrypted external backups require encryption key');
            }

            // Try to extract and validate
            $extracted = $this->packager->extractPackage($localPath, $tempDir . '/extracted');

            if (!$extracted['success']) {
                throw new \Exception('Invalid backup file format');
            }

            // Read manifest
            $manifest = $this->readManifest($extracted['extract_path']);

            // Validate manifest structure
            if (!isset($manifest['version']) || !isset($manifest['data_files'])) {
                throw new \Exception('Invalid backup manifest');
            }

            // Cleanup
            $this->cleanupExtracted($tempDir);

            return [
                'success' => true,
                'manifest' => $manifest,
                'file_path' => $localPath,
            ];
        } catch (\Exception $e) {
            $this->cleanupExtracted($tempDir);
            throw $e;
        }
    }
}
