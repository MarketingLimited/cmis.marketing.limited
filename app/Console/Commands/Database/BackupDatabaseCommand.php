<?php

namespace App\Console\Commands\Database;

use App\Jobs\Database\BackupDatabaseJob;
use Illuminate\Console\Command;

/**
 * Command to create database backups
 *
 * Usage:
 *   php artisan db:backup                    # Full backup
 *   php artisan db:backup --schema=cmis      # Schema backup
 *   php artisan db:backup --data-only        # Data only
 *   php artisan db:backup --no-s3            # Skip S3 upload
 *   php artisan db:backup --sync             # Run synchronously
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup
                            {--schema= : Backup specific schema only}
                            {--data-only : Backup data only (no schema)}
                            {--no-s3 : Skip S3 upload}
                            {--sync : Run synchronously instead of queuing}';

    protected $description = 'Create a database backup (PostgreSQL)';

    public function handle(): int
    {
        $this->info('🔄 Starting database backup...');

        // Determine backup type
        $backupType = 'full';
        $schemaName = $this->option('schema');

        if ($this->option('data-only')) {
            $backupType = 'data-only';
        } elseif ($schemaName) {
            $backupType = 'schema';
        }

        $uploadToS3 = !$this->option('no-s3');
        $sync = $this->option('sync');

        $this->info("Backup type: {$backupType}");
        if ($schemaName) {
            $this->info("Schema: {$schemaName}");
        }
        $this->info("Upload to S3: " . ($uploadToS3 ? 'Yes' : 'No'));

        if ($sync) {
            // Run synchronously
            $this->info('Running synchronously...');

            try {
                $job = new BackupDatabaseJob($backupType, $schemaName, $uploadToS3);
                $job->handle();

                $this->info('✅ Backup completed successfully!');
                $this->info('Backup location: storage/backups/database/');

                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error('❌ Backup failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            // Dispatch to queue
            BackupDatabaseJob::dispatch($backupType, $schemaName, $uploadToS3)
                ->onQueue('database-maintenance');

            $this->info('✅ Backup job dispatched to queue');
            $this->info('Monitor: storage/logs/laravel.log');

            return self::SUCCESS;
        }
    }
}
