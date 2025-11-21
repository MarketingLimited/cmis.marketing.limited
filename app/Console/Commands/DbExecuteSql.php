<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DbExecuteSql extends Command
{
    protected $signature = 'db:execute-sql {file}';
    protected $description = 'Execute a raw SQL file against the default database connection (SECURE VERSION)';

    public function handle()
    {
        $filename = $this->argument('file');

        // Security: Restrict to specific directory only
        $allowedDir = database_path('sql');

        // Ensure the allowed directory exists
        if (!File::isDirectory($allowedDir)) {
            $this->error('❌ SQL directory not found. Please create: database/sql/');
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
            return Command::FAILURE;
        }

        // Check if file exists
        if (!File::exists($filePath)) {
            $this->error('❌ File not found: ' . $filename);
            $this->info('   Looking in: ' . $allowedDir);
            return Command::FAILURE;
        }

        // Security: Require confirmation in production
        if (app()->environment('production')) {
            $this->warn('⚠️  You are about to execute SQL in PRODUCTION environment!');
            $this->warn('   File: ' . $filename);
            $this->warn('   Database: ' . config('database.default'));

            if (!$this->confirm('Are you absolutely sure you want to proceed?')) {
                $this->info('Cancelled. No SQL was executed.');
                return Command::SUCCESS;
            }
        }

        try {
            $sql = File::get($filePath);

            $this->info('Executing SQL from: ' . $filename);
            $this->info('File size: ' . File::size($filePath) . ' bytes');

            DB::unprepared($sql);

            $this->info('✅ Successfully executed SQL file.');

            // Log the execution for audit trail
            \Log::info('SQL file executed', [
                'file' => $filename,
                'user' => posix_getpwuid(posix_geteuid())['name'] ?? 'unknown',
                'environment' => app()->environment(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error executing SQL: ' . $e->getMessage());

            \Log::error('SQL file execution failed', [
                'file' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
