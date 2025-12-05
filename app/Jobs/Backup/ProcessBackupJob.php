<?php

namespace App\Jobs\Backup;

use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupAuditLog;
use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use App\Apps\Backup\Services\Discovery\DependencyResolver;
use App\Apps\Backup\Services\Extraction\DataExtractorService;
use App\Apps\Backup\Services\Extraction\FileCollectorService;
use App\Apps\Backup\Services\Packaging\BackupPackagerService;
use App\Apps\Backup\Services\Packaging\BackupEncryptionService;
use App\Notifications\Backup\BackupCompletedNotification;
use App\Notifications\Backup\BackupFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Process Backup Job
 *
 * Main backup processing job that:
 * 1. Discovers organization tables
 * 2. Extracts data with RLS context
 * 3. Collects associated files
 * 4. Packages into ZIP
 * 5. Optionally encrypts
 * 6. Stores to configured disk
 * 7. Sends notifications
 */
class ProcessBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds (30 minutes)
     */
    public int $timeout = 1800;

    /**
     * Retry backoff in seconds
     */
    public array $backoff = [60, 300, 900];

    /**
     * Backup model
     */
    protected OrganizationBackup $backup;

    /**
     * Categories to backup (null = all)
     */
    protected ?array $categories;

    /**
     * Whether to encrypt the backup
     */
    protected bool $encrypt;

    /**
     * Custom encryption key ID
     */
    protected ?string $encryptionKeyId;

    /**
     * Create a new job instance
     */
    public function __construct(
        OrganizationBackup $backup,
        ?array $categories = null,
        bool $encrypt = false,
        ?string $encryptionKeyId = null
    ) {
        $this->backup = $backup;
        $this->categories = $categories;
        $this->encrypt = $encrypt;
        $this->encryptionKeyId = $encryptionKeyId;
        $this->onQueue('backups');
    }

    /**
     * Execute the job
     */
    public function handle(
        SchemaDiscoveryService $schemaDiscovery,
        DependencyResolver $dependencyResolver,
        DataExtractorService $dataExtractor,
        FileCollectorService $fileCollector,
        BackupPackagerService $packager,
        BackupEncryptionService $encryption
    ): void {
        $startTime = microtime(true);

        try {
            // Update status to processing
            $this->backup->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info("Starting backup job", [
                'backup_id' => $this->backup->id,
                'org_id' => $this->backup->org_id,
            ]);

            // Step 1: Get schema snapshot
            $schemaSnapshot = $schemaDiscovery->getSchemaSnapshot();

            // Step 2: Extract data
            $extractedData = $dataExtractor->extractAllData(
                $this->backup->org_id,
                $this->categories,
                function ($table, $count) {
                    Log::debug("Extracted {$count} records from {$table}");
                }
            );

            // Step 3: Collect files
            $files = $fileCollector->collectFiles(
                $this->backup->org_id,
                $extractedData,
                function ($path, $size) {
                    Log::debug("Collected file: {$path} ({$size} bytes)");
                }
            );

            // Step 4: Create package
            $packageResult = $packager->createPackage(
                $this->backup->org_id,
                $extractedData,
                $files,
                $schemaSnapshot,
                [
                    'backup_id' => $this->backup->id,
                    'backup_code' => $this->backup->backup_code,
                    'type' => $this->backup->type,
                    'categories' => $this->categories,
                ]
            );

            $filePath = $packageResult['path'];
            $fileSize = $packageResult['size'];
            $checksum = $packageResult['checksum'];

            // Step 5: Encrypt if requested
            if ($this->encrypt) {
                $encryptResult = $encryption->encrypt($filePath, $this->encryptionKeyId);
                $filePath = $encryptResult['output_path'];
                $fileSize = filesize($filePath);
                $checksum = hash_file('sha256', $filePath);

                // Clean up unencrypted file
                if (file_exists($packageResult['path'])) {
                    unlink($packageResult['path']);
                }
            }

            // Step 6: Move to permanent storage
            $storagePath = $this->getStoragePath();
            $finalPath = $packager->moveToStorage(
                $filePath,
                $this->backup->storage_disk,
                $storagePath
            );

            // Step 7: Update backup record
            $this->backup->update([
                'status' => 'completed',
                'file_path' => $finalPath,
                'file_size' => $fileSize,
                'checksum_sha256' => $checksum,
                'is_encrypted' => $this->encrypt,
                'encryption_key_id' => $this->encryptionKeyId,
                'summary' => $packageResult['manifest']['summary'],
                'schema_snapshot' => $schemaSnapshot,
                'completed_at' => now(),
                'expires_at' => $this->calculateExpiryDate(),
            ]);

            // Step 8: Create audit log
            BackupAuditLog::create([
                'org_id' => $this->backup->org_id,
                'action' => 'backup_created',
                'entity_id' => $this->backup->id,
                'entity_type' => 'organization_backup',
                'user_id' => $this->backup->created_by,
                'details' => [
                    'backup_code' => $this->backup->backup_code,
                    'type' => $this->backup->type,
                    'file_size' => $fileSize,
                    'record_count' => $packageResult['manifest']['summary']['total_records'] ?? 0,
                    'duration_seconds' => round(microtime(true) - $startTime, 2),
                ],
            ]);

            // Step 9: Clean up temp files
            $fileCollector->cleanupTempFiles($files);

            // Step 10: Send notification
            $this->sendSuccessNotification();

            Log::info("Backup completed successfully", [
                'backup_id' => $this->backup->id,
                'duration' => round(microtime(true) - $startTime, 2) . 's',
                'file_size' => $fileSize,
            ]);

        } catch (\Exception $e) {
            $this->handleFailure($e);
            throw $e;
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
     * Handle backup failure
     */
    protected function handleFailure(\Throwable $exception): void
    {
        Log::error("Backup job failed", [
            'backup_id' => $this->backup->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->backup->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $this->backup->org_id,
            'action' => 'backup_failed',
            'entity_id' => $this->backup->id,
            'entity_type' => 'organization_backup',
            'user_id' => $this->backup->created_by,
            'details' => [
                'error' => $exception->getMessage(),
            ],
        ]);

        // Send failure notification
        $this->sendFailureNotification($exception);
    }

    /**
     * Get storage path for backup file
     */
    protected function getStoragePath(): string
    {
        $basePath = config('backup.storage.disks.' . $this->backup->storage_disk . '.path', 'backups');
        $orgId = $this->backup->org_id;
        $date = now()->format('Y/m');
        $filename = $this->backup->backup_code . ($this->encrypt ? '.zip.enc' : '.zip');

        return "{$basePath}/{$orgId}/{$date}/{$filename}";
    }

    /**
     * Calculate backup expiry date
     */
    protected function calculateExpiryDate(): ?\DateTime
    {
        $retentionDays = config('backup.scheduling.retention_days', 30);

        if ($retentionDays <= 0) {
            return null; // Never expires
        }

        return now()->addDays($retentionDays);
    }

    /**
     * Send success notification
     */
    protected function sendSuccessNotification(): void
    {
        try {
            $user = \App\Models\User::find($this->backup->created_by);
            if ($user) {
                $user->notify(new BackupCompletedNotification($this->backup));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send backup success notification", [
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
            $user = \App\Models\User::find($this->backup->created_by);
            if ($user) {
                $user->notify(new BackupFailedNotification($this->backup, $exception->getMessage()));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send backup failure notification", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
