<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallWizard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:install
                            {--force : Skip confirmation prompts}
                            {--skip-db : Skip database setup}
                            {--skip-admin : Skip admin user creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive setup wizard for CMIS first-time installation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayWelcome();

        if (!$this->option('force')) {
            if (!$this->confirm('Start CMIS installation?', true)) {
                $this->warn('Installation cancelled.');
                return 1;
            }
        }

        $this->newLine();

        // Step 1: Environment Configuration
        if (!$this->setupEnvironment()) {
            return 1;
        }

        // Step 2: Database Setup
        if (!$this->option('skip-db') && !$this->setupDatabase()) {
            return 1;
        }

        // Step 3: Run Migrations
        if (!$this->runMigrations()) {
            return 1;
        }

        // Step 4: Create Admin User
        if (!$this->option('skip-admin') && !$this->createAdminUser()) {
            return 1;
        }

        // Step 5: Generate App Key (if needed)
        $this->generateAppKey();

        // Step 6: Setup Storage
        $this->setupStorage();

        // Step 7: Cache Configuration
        $this->setupCache();

        // Final Summary
        $this->displaySummary();

        return 0;
    }

    /**
     * Display welcome message.
     */
    protected function displayWelcome(): void
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                                                            ║');
        $this->info('║         🚀 CMIS Installation Wizard                        ║');
        $this->info('║         Cognitive Marketing Intelligence Suite            ║');
        $this->info('║                                                            ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line('This wizard will guide you through the initial setup of CMIS.');
        $this->newLine();
    }

    /**
     * Setup environment configuration.
     */
    protected function setupEnvironment(): bool
    {
        $this->info('📝 Step 1: Environment Configuration');
        $this->line('──────────────────────────────────────────────────────────');
        $this->newLine();

        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (!File::exists($envPath)) {
            if (File::exists($envExamplePath)) {
                File::copy($envExamplePath, $envPath);
                $this->info('✅ Created .env file from .env.example');
            } else {
                $this->error('❌ .env.example not found. Cannot create .env file.');
                return false;
            }
        } else {
            $this->warn('.env file already exists. Skipping creation.');
        }

        // Prompt for critical environment variables
        $this->line('Configure essential settings:');
        $this->newLine();

        $appName = $this->ask('Application Name', 'CMIS');
        $appUrl = $this->ask('Application URL', 'http://localhost');
        $appEnv = $this->choice('Environment', ['local', 'staging', 'production'], 'local');

        $this->updateEnvFile([
            'APP_NAME' => $appName,
            'APP_URL' => $appUrl,
            'APP_ENV' => $appEnv,
        ]);

        $this->newLine();
        $this->info('✅ Environment configuration updated');
        $this->newLine(2);

        return true;
    }

    /**
     * Setup database configuration.
     */
    protected function setupDatabase(): bool
    {
        $this->info('🗄️  Step 2: Database Configuration');
        $this->line('──────────────────────────────────────────────────────────');
        $this->newLine();

        $dbHost = $this->ask('Database Host', '127.0.0.1');
        $dbPort = $this->ask('Database Port', '5432');
        $dbName = $this->ask('Database Name', 'cmis');
        $dbUser = $this->ask('Database Username', 'begin');
        $dbPassword = $this->secret('Database Password');

        $this->updateEnvFile([
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $dbHost,
            'DB_PORT' => $dbPort,
            'DB_DATABASE' => $dbName,
            'DB_USERNAME' => $dbUser,
            'DB_PASSWORD' => $dbPassword,
        ]);

        // Test database connection
        $this->line('Testing database connection...');

        try {
            config(['database.connections.pgsql.host' => $dbHost]);
            config(['database.connections.pgsql.port' => $dbPort]);
            config(['database.connections.pgsql.database' => $dbName]);
            config(['database.connections.pgsql.username' => $dbUser]);
            config(['database.connections.pgsql.password' => $dbPassword]);

            DB::connection('pgsql')->getPdo();
            $this->info('✅ Database connection successful');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            $this->warn('Please check your database credentials and try again.');
            return false;
        }

        $this->newLine(2);
        return true;
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): bool
    {
        $this->info('🔨 Step 3: Database Setup');
        $this->line('──────────────────────────────────────────────────────────');
        $this->newLine();

        if ($this->confirm('Run database migrations?', true)) {
            $this->line('Running migrations...');

            try {
                Artisan::call('migrate', ['--force' => true], $this->getOutput());
                $this->info('✅ Migrations completed successfully');
            } catch (\Exception $e) {
                $this->error('❌ Migration failed: ' . $e->getMessage());
                return false;
            }

            if ($this->confirm('Seed database with sample data?', false)) {
                $this->line('Seeding database...');
                Artisan::call('db:seed', ['--force' => true], $this->getOutput());
                $this->info('✅ Database seeded successfully');
            }
        }

        $this->newLine(2);
        return true;
    }

    /**
     * Create admin user and organization.
     */
    protected function createAdminUser(): bool
    {
        $this->info('👤 Step 4: Admin User Setup');
        $this->line('──────────────────────────────────────────────────────────');
        $this->newLine();

        $adminName = $this->ask('Admin Name', 'Admin User');
        $adminEmail = $this->ask('Admin Email', 'admin@cmis.marketing');
        $adminPassword = $this->secret('Admin Password (min 8 characters)');
        $adminPasswordConfirm = $this->secret('Confirm Password');

        if ($adminPassword !== $adminPasswordConfirm) {
            $this->error('❌ Passwords do not match');
            return false;
        }

        if (strlen($adminPassword) < 8) {
            $this->error('❌ Password must be at least 8 characters');
            return false;
        }

        $orgName = $this->ask('Organization Name', 'CMIS Organization');

        try {
            // Create organization
            $org = DB::table('cmis.organizations')->insertGetId([
                'id' => Str::uuid(),
                'name' => $orgName,
                'slug' => Str::slug($orgName),
                'subscription_plan' => 'professional',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create admin user
            DB::table('cmis.users')->insert([
                'id' => Str::uuid(),
                'org_id' => $org,
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => bcrypt($adminPassword),
                'email_verified_at' => now(),
                'is_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('✅ Admin user created successfully');
            $this->newLine();
            $this->line("Email: {$adminEmail}");
            $this->line('You can now log in with these credentials.');
        } catch (\Exception $e) {
            $this->error('❌ Failed to create admin user: ' . $e->getMessage());
            return false;
        }

        $this->newLine(2);
        return true;
    }

    /**
     * Generate application key.
     */
    protected function generateAppKey(): void
    {
        if (empty(config('app.key'))) {
            $this->info('🔐 Generating application key...');
            Artisan::call('key:generate', ['--force' => true], $this->getOutput());
            $this->info('✅ Application key generated');
            $this->newLine();
        }
    }

    /**
     * Setup storage directories.
     */
    protected function setupStorage(): void
    {
        $this->info('📁 Setting up storage...');
        Artisan::call('storage:link', [], $this->getOutput());
        $this->info('✅ Storage linked');
        $this->newLine();
    }

    /**
     * Setup cache.
     */
    protected function setupCache(): void
    {
        $this->info('⚡ Optimizing application...');
        Artisan::call('config:cache', [], $this->getOutput());
        Artisan::call('route:cache', [], $this->getOutput());
        $this->info('✅ Application optimized');
        $this->newLine();
    }

    /**
     * Display installation summary.
     */
    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                                                            ║');
        $this->info('║         ✅ Installation Complete!                          ║');
        $this->info('║                                                            ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line('🎉 CMIS is now installed and ready to use!');
        $this->newLine();

        $this->line('Next steps:');
        $this->line('  1. Visit ' . config('app.url') . ' to access the application');
        $this->line('  2. Log in with your admin credentials');
        $this->line('  3. Configure platform integrations (Meta, Google, etc.)');
        $this->line('  4. Start creating campaigns!');
        $this->newLine();

        $this->line('Useful commands:');
        $this->line('  php artisan serve           - Start development server');
        $this->line('  php artisan cmis:audit-rls  - Audit RLS policies');
        $this->line('  php artisan queue:work      - Start queue worker');
        $this->newLine();

        $this->info('📚 Documentation: docs/README.md');
        $this->info('💬 Support: support@cmis.marketing');
        $this->newLine();
    }

    /**
     * Update .env file with key-value pairs.
     */
    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);
    }
}
