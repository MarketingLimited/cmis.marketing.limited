<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Command to restore database from backup
 *
 * Usage:
 *   php artisan db:restore {filename}
 *   php artisan db:restore cmis_full_2025-11-18_120000.sql.gz
 *   php artisan db:restore --from-s3 --file=cmis_full_2025-11-18_120000.sql.gz
 */
class RestoreDatabaseCommand extends Command
{
    protected $signature = 'db:restore
                            {filename? : Backup filename to restore}
                            {--from-s3 : Download from S3 first}
                            {--list : List available backups}
                            {--verify : Verify backup without restoring}';

    protected $description = 'Restore database from backup';

    public function handle(): int
    {
        // List backups
        if ($this->option('list')) {
            return $this->listBackups();
        }

        $filename = $this->argument('filename');

        if (!$filename) {
            $this->error('Please provide a backup filename');
            $this->info('Use --list to see available backups');
            return self::FAILURE;
        }

        $fromS3 = $this->option('from-s3');
        $verifyOnly = $this->option('verify');

        if ($fromS3) {
            if (!$this->downloadFromS3($filename)) {
                return self::FAILURE;
            }
        }

        $backupPath = storage_path("backups/database/{$filename}");

        if (!file_exists($backupPath)) {
            $this->error("Backup file not found: {$backupPath}");
            return self::FAILURE;
        }

        // Verify backup
        $this->info('🔍 Verifying backup integrity...');
        if (!$this->verifyBackup($backupPath)) {
            $this->error('❌ Backup verification failed');
            return self::FAILURE;
        }
        $this->info('✅ Backup verified successfully');

        if ($verifyOnly) {
            return self::SUCCESS;
        }

        // Confirm restoration
        if (!$this->confirmRestore($backupPath)) {
            $this->warn('Restoration cancelled');
            return self::FAILURE;
        }

        // Restore database
        return $this->restoreDatabase($backupPath);
    }

    /**
     * List available backups
     */
    protected function listBackups(): int
    {
        $backupDir = storage_path('backups/database');
        $files = glob("{$backupDir}/cmis_*.sql.gz");

        if (empty($files)) {
            $this->info('No backups found');
            return self::SUCCESS;
        }

        $this->info('📦 Available backups:');
        $this->newLine();

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $sizeMB = round($size / 1024 / 1024, 2);
            $modified = date('Y-m-d H:i:s', filemtime($file));

            $backups[] = [
                'Filename' => $filename,
                'Size (MB)' => $sizeMB,
                'Created' => $modified,
            ];
        }

        $this->table(['Filename', 'Size (MB)', 'Created'], $backups);

        return self::SUCCESS;
    }

    /**
     * Download backup from S3
     */
    protected function downloadFromS3(string $filename): bool
    {
        $this->info("☁️ Downloading {$filename} from S3...");

        try {
            $s3Path = "database-backups/*/{$filename}";
            $localPath = storage_path("backups/database/{$filename}");

            // Search for file in S3
            $files = Storage::disk('s3')->files('database-backups');
            $matchingFile = null;

            foreach ($files as $file) {
                if (basename($file) === $filename) {
                    $matchingFile = $file;
                    break;
                }
            }

            if (!$matchingFile) {
                $this->error("File not found in S3: {$filename}");
                return false;
            }

            // Download file
            $contents = Storage::disk('s3')->get($matchingFile);
            file_put_contents($localPath, $contents);

            $this->info('✅ Downloaded from S3');
            return true;

        } catch (\Exception $e) {
            $this->error("Failed to download from S3: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Verify backup integrity
     */
    protected function verifyBackup(string $backupPath): bool
    {
        $command = "gunzip -t {$backupPath} 2>&1";
        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Confirm restoration with user
     */
    protected function confirmRestore(string $backupPath): bool
    {
        $this->warn('⚠️  WARNING: This will REPLACE the current database!');
        $this->info("Backup file: {$backupPath}");
        $this->info("Database: " . config('database.connections.pgsql.database'));
        $this->newLine();

        return $this->confirm('Are you absolutely sure you want to proceed?', false);
    }

    /**
     * Restore database from backup
     */
    protected function restoreDatabase(string $backupPath): int
    {
        $this->info('🔄 Restoring database...');

        $dbHost = config('database.connections.pgsql.host');
        $dbPort = config('database.connections.pgsql.port', 5432);
        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');

        // Build psql command
        $command = "PGPASSWORD='" . addslashes($dbPassword) . "' ";
        $command .= "gunzip -c {$backupPath} | ";
        $command .= "psql -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbName} 2>&1";

        $this->info('Executing restore...');

        $output = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('❌ Restore failed');
            $this->error(implode("\n", $output));
            return self::FAILURE;
        }

        $this->info('✅ Database restored successfully!');
        $this->warn('⚠️  Please verify the restoration and restart services if needed');

        return self::SUCCESS;
    }
}
