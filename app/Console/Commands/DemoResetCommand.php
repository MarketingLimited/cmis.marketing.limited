<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Traits\HasHelpfulErrors;

/**
 * Demo Reset Command
 * Issue #45: Reset system to clean state for demos
 *
 * Usage: php artisan cmis:demo-reset [--seed-examples]
 */
class DemoResetCommand extends Command
{
    use HasHelpfulErrors;

    protected $signature = 'cmis:demo-reset
                            {--seed-examples : Seed with example campaigns and content}
                            {--skip-confirmation : Skip confirmation prompt (use with caution)}';

    protected $description = 'Reset CMIS to clean state for demos and testing';

    public function handle(): int
    {
        $this->registerCommonErrors();

        if (!$this->option('skip-confirmation')) {
            if (!$this->confirmReset()) {
                $this->info('Reset cancelled.');
                return self::SUCCESS;
            }
        }

        try {
            $this->info('🔄 Starting demo reset...');
            $this->newLine();

            // Step 1: Drop all tables
            $this->task('Dropping all tables', function () {
                DB::statement('DROP SCHEMA IF EXISTS cmis CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_meta CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_google CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_tiktok CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_linkedin CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_twitter CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_snapchat CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_platform CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_ai CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_operations CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_social CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS cmis_core CASCADE');
            });

            // Step 2: Run migrations
            $this->task('Running migrations', function () {
                Artisan::call('migrate:fresh', ['--force' => true]);
            });

            // Step 3: Install pgvector extension
            $this->task('Installing pgvector extension', function () {
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            });

            // Step 4: Seed base data
            $this->task('Seeding base data', function () {
                Artisan::call('db:seed', [
                    '--class' => 'DatabaseSeeder',
                    '--force' => true
                ]);
            });

            // Step 5: Seed example data if requested
            if ($this->option('seed-examples')) {
                $this->task('Seeding example campaigns and content', function () {
                    Artisan::call('db:seed', [
                        '--class' => 'DemoDataSeeder',
                        '--force' => true
                    ]);
                });
            }

            // Step 6: Clear caches
            $this->task('Clearing caches', function () {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
            });

            $this->newLine();
            $this->info('✅ Demo reset complete!');
            $this->newLine();

            $this->displayCredentials();

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->handleErrorWithSolution($e, 'Demo reset');
            return self::FAILURE;
        }
    }

    protected function confirmReset(): bool
    {
        $this->warn('⚠️  WARNING: This will DELETE ALL DATA and reset the database!');
        $this->newLine();

        return $this->confirm('Are you sure you want to continue?');
    }

    protected function displayCredentials(): void
    {
        $this->info('📝 Demo Credentials:');
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@example.com', 'password'],
                ['User', 'user@example.com', 'password'],
            ]
        );

        if ($this->option('seed-examples')) {
            $this->newLine();
            $this->info('📊 Example data includes:');
            $this->line('  - 3 Organizations');
            $this->line('  - 10 Campaigns');
            $this->line('  - 25 Content items');
            $this->line('  - Platform integrations (Meta, Google)');
        }
    }

    protected function registerCommonErrors(): void
    {
        $this->registerErrorSolution(
            'database',
            'Make sure PostgreSQL is running and credentials in .env are correct.'
        );
        $this->registerErrorSolution(
            'migration',
            'Check database/migrations for syntax errors.'
        );
        $this->registerErrorSolution(
            'seeder',
            'Check database/seeders for errors. You may need to run migrations first.'
        );
    }
}
