<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Services\SQLValidator;
use App\Console\Commands\Traits\HasHelpfulErrors;

class DbExecuteSql extends Command
{
    use HasHelpfulErrors;

    protected $signature = 'db:execute-sql {file}
                            {--allow-destructive : Allow destructive SQL operations (DROP, TRUNCATE, etc.)}
                            {--skip-validation : Skip SQL validation (dangerous!)}';
    protected $description = 'Execute a raw SQL file against the default database connection (SECURE VERSION with SQL validation)';

    public function handle()
    {
        $filename = $this->argument('file');

        // Register error solutions (Issue #42)
        $this->registerCommonErrorSolutions();

        // Security: Restrict to specific directory only
        $allowedDir = database_path('sql');

        // Ensure the allowed directory exists
        if (!File::isDirectory($allowedDir)) {
            $this->handleErrorWithSolution(
                new \RuntimeException('SQL directory not found'),
                'Create the directory: mkdir -p database/sql/'
            );
            return Command::FAILURE;
        }

        // Construct the full path
        $requestedPath = $allowedDir . DIRECTORY_SEPARATOR . $filename;

        // Use realpath to resolve any .. or symbolic links
        $filePath = realpath($requestedPath);

        // Security validation: Ensure the resolved path is within the allowed directory
        if (!$filePath || !str_starts_with($filePath, realpath($allowedDir))) {
            $this->error('❌ Invalid file path. Only files in database/sql/ are allowed.');
            $this->error('   Attempted path: ' . $requestedPath);
            $this->warn('   Path traversal attacks are not permitted.');
            $this->newLine();
            $this->info('💡 Place your SQL file in: ' . $allowedDir);
            return Command::FAILURE;
        }

        // Check if file exists
        if (!File::exists($filePath)) {
            $this->error('❌ File not found: ' . $filename);
            $this->info('   Looking in: ' . $allowedDir);
            $this->newLine();
            $this->info('💡 Available SQL files:');
            $files = File::files($allowedDir);
            if (empty($files)) {
                $this->line('   (none)');
            } else {
                foreach ($files as $file) {
                    $this->line('   - ' . $file->getFilename());
                }
            }
            return Command::FAILURE;
        }

        try {
            $sql = File::get($filePath);

            // Issue #40: SQL Content Validation
            if (!$this->option('skip-validation')) {
                $this->info('🔍 Validating SQL content...');
                $validator = new SQLValidator();
                $validation = $validator->validate($sql);

                // Show warnings
                if (!empty($validation['warnings'])) {
                    $this->newLine();
                    $this->warn('⚠️  SQL Validation Warnings:');
                    foreach ($validation['warnings'] as $warning) {
                        $this->line("   - {$warning}");
                    }
                    $this->newLine();
                }

                // Show errors
                if (!empty($validation['errors'])) {
                    $this->newLine();
                    $this->error('❌ SQL Validation Errors:');
                    foreach ($validation['errors'] as $error) {
                        $this->line("   - {$error}");
                    }
                    $this->newLine();
                    $this->error('Dangerous SQL detected! Execution blocked for safety.');
                    $this->info('If you must execute this SQL, use --skip-validation (NOT RECOMMENDED).');
                    return Command::FAILURE;
                }

                // Block destructive operations without flag
                if ($validation['is_destructive'] && !$this->option('allow-destructive')) {
                    $this->error('❌ Destructive SQL operations detected!');
                    $this->newLine();
                    $this->line('This SQL contains operations that could:');
                    $this->line('  • Delete data permanently');
                    $this->line('  • Drop tables or schemas');
                    $this->line('  • Truncate tables');
                    $this->newLine();
                    $this->info('To proceed, use the --allow-destructive flag:');
                    $this->line("   php artisan db:execute-sql {$filename} --allow-destructive");
                    return Command::FAILURE;
                }

                $this->info('✅ SQL validation passed');
                $this->newLine();
            }

            // Security: Require confirmation in production
            if (app()->environment('production')) {
                $this->warn('⚠️  You are about to execute SQL in PRODUCTION environment!');
                $this->warn('   File: ' . $filename);
                $this->warn('   Database: ' . config('database.default'));
                $this->newLine();

                if (!$this->confirm('Type "yes" to confirm you want to proceed')) {
                    $this->info('Cancelled. No SQL was executed.');
                    return Command::SUCCESS;
                }
            }

            $this->info('Executing SQL from: ' . $filename);
            $this->info('File size: ' . File::size($filePath) . ' bytes');
            $this->newLine();

            DB::unprepared($sql);

            $this->info('✅ Successfully executed SQL file.');

            // Log the execution for audit trail
            \Log::info('SQL file executed', [
                'file' => $filename,
                'user' => posix_getpwuid(posix_geteuid())['name'] ?? 'unknown',
                'environment' => app()->environment(),
                'validation_skipped' => $this->option('skip-validation'),
                'destructive_allowed' => $this->option('allow-destructive'),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->handleErrorWithSolution($e, 'SQL execution');
            return Command::FAILURE;
        }
    }

    protected function registerCommonErrorSolutions(): void
    {
        $this->registerErrorSolution(
            'syntax error',
            'Check your SQL syntax. The file may contain invalid SQL statements.'
        );
        $this->registerErrorSolution(
            'relation',
            'The table or relation does not exist. Make sure migrations have run.'
        );
        $this->registerErrorSolution(
            'permission',
            'Database user lacks necessary permissions. Check PostgreSQL grants.'
        );
        $this->registerErrorSolution(
            'connection',
            'Cannot connect to database. Check .env configuration and ensure PostgreSQL is running.'
        );
    }
}
