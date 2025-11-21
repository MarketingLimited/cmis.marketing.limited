<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GeneratePrimaryKeyMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:generate-pk-migrations
                            {--schema=* : Specific schemas to check (default: cmis, cmis_*, public)}
                            {--execute : Execute migrations immediately after generating}
                            {--dry-run : Show what would be generated without creating files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate migrations for tables missing primary keys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schemas = $this->option('schema') ?: ['cmis', 'cmis_%'];
        $dryRun = $this->option('dry-run');
        $execute = $this->option('execute');

        $this->info('🔍 Scanning database for tables without primary keys...');
        $this->newLine();

        // Find tables without primary keys
        $tables = $this->findTablesWithoutPrimaryKeys($schemas);

        if ($tables->isEmpty()) {
            $this->info('✅ All tables have primary keys!');
            return self::SUCCESS;
        }

        $this->warn("Found {$tables->count()} tables without primary keys:");
        $this->newLine();

        $generatedFiles = [];

        foreach ($tables as $table) {
            $schemaName = $table->schemaname;
            $tableName = $table->tablename;
            $fullTableName = "$schemaName.$tableName";

            // Determine likely primary key column name
            $pkColumn = $this->determinePrimaryKeyColumn($schemaName, $tableName);

            $this->line("  📋 {$fullTableName}");
            $this->line("     Primary key will be: {$pkColumn}");

            if (!$dryRun) {
                $migrationFile = $this->generateMigration($schemaName, $tableName, $pkColumn);
                $generatedFiles[] = $migrationFile;
                $this->line("     ✓ Generated: {$migrationFile}");
            }

            $this->newLine();

            // Add small delay to ensure unique timestamps
            usleep(100000); // 0.1 second
        }

        if ($dryRun) {
            $this->info('🏃 Dry run complete. No files were created.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("✅ Generated {$tables->count()} migration files.");
        $this->newLine();

        if ($execute) {
            $this->warn('🚀 Executing migrations...');
            $this->call('migrate');
        } else {
            $this->comment('💡 To execute these migrations, run:');
            $this->line('   php artisan migrate');
            $this->newLine();
            $this->comment('💡 Or re-run this command with --execute flag:');
            $this->line('   php artisan db:generate-pk-migrations --execute');
        }

        return self::SUCCESS;
    }

    /**
     * Find all tables without primary keys in specified schemas
     */
    private function findTablesWithoutPrimaryKeys(array $schemas): \Illuminate\Support\Collection
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
                pg_catalog.obj_description(c.oid, 'pg_class') as table_comment
            FROM pg_tables t
            JOIN pg_class c ON c.relname = t.tablename
            JOIN pg_namespace n ON n.oid = c.relnamespace AND n.nspname = t.schemaname
            WHERE $whereClause
            AND NOT EXISTS (
                SELECT 1
                FROM pg_constraint con
                WHERE con.conrelid = c.oid
                AND con.contype = 'p'
            )
            ORDER BY t.schemaname, t.tablename
        ";

        return collect(DB::select($query, $bindings));
    }

    /**
     * Determine the likely primary key column name for a table
     */
    private function determinePrimaryKeyColumn(string $schema, string $table): string
    {
        // Common patterns for primary key column names
        $patterns = [
            "{$table}_id",           // table_name_id (most common)
            "id",                     // id
            "{$table}Id",             // tableName Id (camelCase)
            "pk_{$table}",            // pk_table_name
        ];

        // Get all columns for this table
        $columns = DB::select("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
            ORDER BY ordinal_position
        ", [$schema, $table]);

        $columnNames = array_map(fn($col) => $col->column_name, $columns);

        // Check if any pattern matches
        foreach ($patterns as $pattern) {
            if (in_array($pattern, $columnNames)) {
                return $pattern;
            }
        }

        // Check for columns with 'id' in the name
        foreach ($columnNames as $columnName) {
            if (str_contains(strtolower($columnName), 'id') &&
                str_starts_with(strtolower($columnName), $table)) {
                return $columnName;
            }
        }

        // Default to {table}_id
        return "{$table}_id";
    }

    /**
     * Generate a migration file for adding primary key
     */
    private function generateMigration(string $schema, string $table, string $pkColumn): string
    {
        $timestamp = date('Y_m_d_His');
        $className = Str::studly("add_primary_key_to_{$table}_table");
        $filename = "{$timestamp}_add_pk_to_{$schema}_{$table}.php";
        $filepath = database_path("migrations/{$filename}");

        $fullTable = "$schema.$table";

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add primary key to {$fullTable}
     */
    public function up(): void
    {
        // Check if column exists
        \$columnExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = '{$schema}'
                AND table_name = '{$table}'
                AND column_name = '{$pkColumn}'
            )
        ")->exists;

        if (\$columnExists) {
            // Add primary key constraint to existing column
            DB::statement("
                ALTER TABLE {$fullTable}
                ADD PRIMARY KEY ({$pkColumn});
            ");

            echo "✓ Added primary key to {$fullTable}.{$pkColumn}\\n";
        } else {
            echo "⚠ Warning: Column {$pkColumn} does not exist in {$fullTable}\\n";
            echo "  You may need to create the column first or adjust the migration.\\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE {$fullTable} DROP CONSTRAINT IF EXISTS {$table}_pkey;");
    }
};
PHP;

        File::put($filepath, $content);

        return $filename;
    }
}
