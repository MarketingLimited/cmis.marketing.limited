<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'database:backup {--compress : Compress the backup file}';
    protected $description = 'Create a database backup';

    public function handle()
    {
        $this->info('💾 Starting database backup...');

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $compress = $this->option('compress');

            $database = config('database.connections.pgsql.database');
            $username = config('database.connections.pgsql.username');
            $host = config('database.connections.pgsql.host');
            $port = config('database.connections.pgsql.port', 5432);

            $backupPath = storage_path("app/backups/{$filename}");

            // Create backups directory if it doesn't exist
            if (!is_dir(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }

            // Build pg_dump command
            $command = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -F p -b -v -f %s %s 2>&1',
                escapeshellarg(config('database.connections.pgsql.password')),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($backupPath),
                escapeshellarg($database)
            );

            $this->line('🔄 Creating backup...');
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Backup command failed: ' . implode("\n", $output));
            }

            $fileSize = filesize($backupPath);
            $fileSizeMB = number_format($fileSize / 1024 / 1024, 2);

            if ($compress) {
                $this->line('📦 Compressing backup...');
                $gzPath = $backupPath . '.gz';
                exec("gzip -c {$backupPath} > {$gzPath}");
                unlink($backupPath);
                $backupPath = $gzPath;
                $fileSizeMB = number_format(filesize($gzPath) / 1024 / 1024, 2);
            }

            $this->info("✅ Backup created successfully!");
            $this->line("   📁 Location: {$backupPath}");
            $this->line("   📊 Size: {$fileSizeMB} MB");

            Log::info('Database backup created', [
                'filename' => basename($backupPath),
                'size' => $fileSize,
                'compressed' => $compress
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
