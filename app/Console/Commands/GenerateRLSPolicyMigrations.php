<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GenerateRLSPolicyMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:generate-rls-migrations
                            {--schema=* : Specific schemas to process (default: cmis, cmis_*)}
                            {--critical-only : Only generate for critical tables (users, campaigns, etc.)}
                            {--batch=20 : Number of tables per migration file}
                            {--execute : Execute migrations immediately after generating}
                            {--dry-run : Show what would be generated without creating files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate migrations to add RLS policies to tables';

    /**
     * Critical tables that must have RLS
     */
    private array $criticalTables = [
        'users', 'roles', 'permissions', 'campaigns', 'budgets',
        'social_posts', 'leads', 'contacts', 'invoices', 'payments',
        'content_plans', 'creative_assets', 'templates', 'workflows',
        'teams', 'team_members', 'integrations', 'api_keys'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schemas = $this->option('schema') ?: ['cmis', 'cmis_%'];
        $criticalOnly = $this->option('critical-only');
        $batchSize = (int) $this->option('batch');
        $dryRun = $this->option('dry-run');
        $execute = $this->option('execute');

        $this->info('🔍 Scanning database for tables without RLS policies...');
        $this->newLine();

        // Find tables without RLS
        $tables = $this->findTablesWithoutRLS($schemas, $criticalOnly);

        if ($tables->isEmpty()) {
            $this->info('✅ All tables have RLS policies!');
            return self::SUCCESS;
        }

        $this->warn("Found {$tables->count()} tables without RLS policies:");
        $this->newLine();

        // Group tables into batches
        $batches = $tables->chunk($batchSize);

        $generatedFiles = [];
        $batchNumber = 1;

        foreach ($batches as $batch) {
            if ($dryRun) {
                $this->info("Batch {$batchNumber}: {$batch->count()} tables");
                foreach ($batch as $table) {
                    $this->line("  - {$table->schemaname}.{$table->tablename}");
                }
                $this->newLine();
            } else {
                $migrationFile = $this->generateMigrationBatch($batch, $batchNumber);
                $generatedFiles[] = $migrationFile;
                $this->info("✓ Generated batch {$batchNumber}: {$migrationFile}");
                $this->line("  Contains {$batch->count()} tables");
                $this->newLine();
            }

            $batchNumber++;
            usleep(100000); // 0.1 second delay for unique timestamps
        }

        if ($dryRun) {
            $this->info('🏃 Dry run complete. No files were created.');
            return self::SUCCESS;
        }

        $this->info("✅ Generated " . count($generatedFiles) . " migration file(s) for {$tables->count()} tables.");
        $this->newLine();

        if ($execute) {
            $this->warn('🚀 Executing migrations...');
            $this->call('migrate');
        } else {
            $this->comment('💡 To execute these migrations, run:');
            $this->line('   php artisan migrate');
            $this->newLine();
            $this->comment('💡 Or re-run this command with --execute flag:');
            $this->line('   php artisan db:generate-rls-migrations --execute');
        }

        return self::SUCCESS;
    }

    /**
     * Find all tables without RLS policies
     */
    private function findTablesWithoutRLS(array $schemas, bool $criticalOnly): \Illuminate\Support\Collection
    {
        $schemaConditions = [];
        $bindings = [];

        foreach ($schemas as $schema) {
            if (str_contains($schema, '%')) {
                $schemaConditions[] = "schemaname LIKE ?";
                $bindings[] = $schema;
            } else {
                $schemaConditions[] = "schemaname = ?";
                $bindings[] = $schema;
            }
        }

        $whereClause = '(' . implode(' OR ', $schemaConditions) . ')';

        $query = "
            SELECT
                t.schemaname,
                t.tablename,
                CASE WHEN EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = t.schemaname
                    AND table_name = t.tablename
                    AND column_name = 'org_id'
                ) THEN true ELSE false END as has_org_id
            FROM pg_tables t
            WHERE $whereClause
            AND relrowsecurity = false  -- RLS not enabled
            ORDER BY t.schemaname, t.tablename
        ";

        $allTables = collect(DB::select($query, $bindings));

        if ($criticalOnly) {
            return $allTables->filter(function($table) {
                return in_array($table->tablename, $this->criticalTables);
            });
        }

        return $allTables;
    }

    /**
     * Generate a migration file for a batch of tables
     */
    private function generateMigrationBatch(\Illuminate\Support\Collection $tables, int $batchNumber): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_add_rls_policies_batch_{$batchNumber}.php";
        $filepath = database_path("migrations/{$filename}");

        $upStatements = [];
        $downStatements = [];

        foreach ($tables as $table) {
            $schema = $table->schemaname;
            $tableName = $table->tablename;
            $fullTable = "$schema.$tableName";
            $policyName = "{$tableName}_org_isolation";
            $hasOrgId = $table->has_org_id;

            if (!$hasOrgId) {
                $upStatements[] = "        // WARNING: {$fullTable} does not have org_id column";
                $upStatements[] = "        // You may need to add it first or skip this table";
                $upStatements[] = "        /*";
            }

            $upStatements[] = "        // Enable RLS on {$fullTable}";
            $upStatements[] = "        DB::statement('ALTER TABLE {$fullTable} ENABLE ROW LEVEL SECURITY;');";
            $upStatements[] = "        DB::statement(\"";
            $upStatements[] = "            CREATE POLICY {$policyName} ON {$fullTable}";
            $upStatements[] = "                FOR ALL";
            $upStatements[] = "                USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)";
            $upStatements[] = "                WITH CHECK (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid);";
            $upStatements[] = "        \");";
            $upStatements[] = "        echo \"✓ Added RLS policy to {$fullTable}\\n\";";

            if (!$hasOrgId) {
                $upStatements[] = "        */";
            }

            $upStatements[] = "";

            // Down statements
            $downStatements[] = "        DB::statement('DROP POLICY IF EXISTS {$policyName} ON {$fullTable};');";
            $downStatements[] = "        DB::statement('ALTER TABLE {$fullTable} DISABLE ROW LEVEL SECURITY;');";
            $downStatements[] = "";
        }

        $upCode = implode("\n", $upStatements);
        $downCode = implode("\n", $downStatements);

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add RLS policies to batch {$batchNumber} ({$tables->count()} tables)
     */
    public function up(): void
    {
{$upCode}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
{$downCode}
    }
};
PHP;

        File::put($filepath, $content);

        return $filename;
    }
}
