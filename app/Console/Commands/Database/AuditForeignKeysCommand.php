<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audit foreign key constraints in the database
 *
 * Checks for:
 * - Missing foreign keys that should exist
 * - Orphaned records (child without parent)
 * - Invalid ON DELETE/ON UPDATE actions
 * - Missing indexes on foreign key columns
 *
 * Usage:
 *   php artisan db:audit-foreign-keys
 *   php artisan db:audit-foreign-keys --schema=cmis
 *   php artisan db:audit-foreign-keys --fix-orphans
 */
class AuditForeignKeysCommand extends Command
{
    protected $signature = 'db:audit-foreign-keys
                            {--schema=cmis : Schema to audit}
                            {--fix-orphans : Remove orphaned records}
                            {--export : Export results to CSV}';

    protected $description = 'Audit database foreign key constraints';

    protected array $issues = [];
    protected array $orphans = [];

    public function handle(): int
    {
        $schema = $this->option('schema');
        $fixOrphans = $this->option('fix-orphans');
        $export = $this->option('export');

        $this->info("🔍 Auditing foreign keys in schema: {$schema}");
        $this->newLine();

        // Get all foreign keys
        $foreignKeys = $this->getAllForeignKeys($schema);

        $this->info("Found " . count($foreignKeys) . " foreign key constraints");
        $this->newLine();

        // Audit each foreign key
        $progressBar = $this->output->createProgressBar(count($foreignKeys));
        $progressBar->start();

        foreach ($foreignKeys as $fk) {
            $this->auditForeignKey($fk, $schema, $fixOrphans);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Check for missing foreign keys
        $this->checkMissingForeignKeys($schema);

        // Report results
        $this->reportResults($export);

        return self::SUCCESS;
    }

    /**
     * Get all foreign keys for a schema
     */
    protected function getAllForeignKeys(string $schema): array
    {
        $query = "
            SELECT
                tc.constraint_name,
                tc.table_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.update_rule,
                rc.delete_rule
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints AS rc
                ON rc.constraint_name = tc.constraint_name
                AND rc.constraint_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_schema = ?
            ORDER BY tc.table_name, tc.constraint_name
        ";

        return DB::select($query, [$schema]);
    }

    /**
     * Audit a single foreign key
     */
    protected function auditForeignKey($fk, string $schema, bool $fixOrphans): void
    {
        $tableName = "{$schema}.{$fk->table_name}";
        $foreignTable = "{$schema}.{$fk->foreign_table_name}";
        $column = $fk->column_name;
        $foreignColumn = $fk->foreign_column_name;

        // Check for orphaned records
        $orphanQuery = "
            SELECT COUNT(*) as count
            FROM {$tableName} t
            LEFT JOIN {$foreignTable} f ON t.{$column} = f.{$foreignColumn}
            WHERE t.{$column} IS NOT NULL
            AND f.{$foreignColumn} IS NULL
        ";

        $result = DB::select($orphanQuery);
        $orphanCount = $result[0]->count ?? 0;

        if ($orphanCount > 0) {
            $this->orphans[] = [
                'table' => $fk->table_name,
                'column' => $column,
                'foreign_table' => $fk->foreign_table_name,
                'foreign_column' => $foreignColumn,
                'orphan_count' => $orphanCount,
                'constraint' => $fk->constraint_name,
            ];

            if ($fixOrphans) {
                $this->fixOrphanedRecords($tableName, $foreignTable, $column, $foreignColumn);
            }
        }

        // Check ON DELETE/UPDATE rules
        if ($fk->delete_rule === 'NO ACTION' && in_array($fk->table_name, ['ad_campaigns', 'ad_sets', 'ad_entities'])) {
            $this->issues[] = [
                'type' => 'delete_rule',
                'table' => $fk->table_name,
                'constraint' => $fk->constraint_name,
                'issue' => "ON DELETE NO ACTION - should be CASCADE for {$fk->table_name}",
                'severity' => 'medium',
            ];
        }

        // Check for index on foreign key column
        if (!$this->hasIndex($schema, $fk->table_name, $column)) {
            $this->issues[] = [
                'type' => 'missing_index',
                'table' => $fk->table_name,
                'column' => $column,
                'issue' => "No index on foreign key column: {$column}",
                'severity' => 'high',
            ];
        }
    }

    /**
     * Check if column has an index
     */
    protected function hasIndex(string $schema, string $table, string $column): bool
    {
        $query = "
            SELECT COUNT(*) as count
            FROM pg_indexes
            WHERE schemaname = ?
            AND tablename = ?
            AND indexdef LIKE ?
        ";

        $result = DB::select($query, [$schema, $table, "%{$column}%"]);
        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Fix orphaned records
     */
    protected function fixOrphanedRecords(string $table, string $foreignTable, string $column, string $foreignColumn): void
    {
        $deleteQuery = "
            DELETE FROM {$table}
            WHERE {$column} IN (
                SELECT t.{$column}
                FROM {$table} t
                LEFT JOIN {$foreignTable} f ON t.{$column} = f.{$foreignColumn}
                WHERE t.{$column} IS NOT NULL
                AND f.{$foreignColumn} IS NULL
            )
        ";

        $deleted = DB::delete($deleteQuery);

        $this->warn("  Deleted {$deleted} orphaned records from {$table}");
    }

    /**
     * Check for missing foreign keys
     */
    protected function checkMissingForeignKeys(string $schema): void
    {
        // Common patterns that should have foreign keys
        $expectedForeignKeys = [
            ['table' => 'campaigns', 'column' => 'org_id', 'references' => 'orgs(org_id)'],
            ['table' => 'campaigns', 'column' => 'created_by', 'references' => 'users(user_id)'],
            ['table' => 'content_plans', 'column' => 'campaign_id', 'references' => 'campaigns(campaign_id)'],
            ['table' => 'content_items', 'column' => 'plan_id', 'references' => 'content_plans(plan_id)'],
            ['table' => 'scheduled_social_posts', 'column' => 'org_id', 'references' => 'orgs(org_id)'],
            ['table' => 'ad_campaigns', 'column' => 'integration_id', 'references' => 'integrations(integration_id)'],
            ['table' => 'ad_sets', 'column' => 'ad_campaign_id', 'references' => 'ad_campaigns(ad_campaign_id)'],
            ['table' => 'ad_entities', 'column' => 'ad_set_id', 'references' => 'ad_sets(ad_set_id)'],
        ];

        foreach ($expectedForeignKeys as $expected) {
            if (!$this->foreignKeyExists($schema, $expected['table'], $expected['column'])) {
                $this->issues[] = [
                    'type' => 'missing_fk',
                    'table' => $expected['table'],
                    'column' => $expected['column'],
                    'issue' => "Missing foreign key: {$expected['column']} -> {$expected['references']}",
                    'severity' => 'high',
                ];
            }
        }
    }

    /**
     * Check if foreign key exists
     */
    protected function foreignKeyExists(string $schema, string $table, string $column): bool
    {
        $query = "
            SELECT COUNT(*) as count
            FROM information_schema.key_column_usage
            WHERE table_schema = ?
            AND table_name = ?
            AND column_name = ?
            AND constraint_name IN (
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE constraint_type = 'FOREIGN KEY'
                AND table_schema = ?
            )
        ";

        $result = DB::select($query, [$schema, $table, $column, $schema]);
        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Report results
     */
    protected function reportResults(bool $export): void
    {
        // Report orphans
        if (!empty($this->orphans)) {
            $this->error('❌ Found ' . count($this->orphans) . ' tables with orphaned records:');
            $this->table(
                ['Table', 'Column', 'Foreign Table', 'Orphan Count'],
                array_map(fn($o) => [
                    $o['table'],
                    $o['column'],
                    $o['foreign_table'],
                    $o['orphan_count'],
                ], $this->orphans)
            );
            $this->newLine();
        } else {
            $this->info('✅ No orphaned records found');
            $this->newLine();
        }

        // Report issues
        if (!empty($this->issues)) {
            $this->warn('⚠️  Found ' . count($this->issues) . ' foreign key issues:');

            // Group by severity
            $high = array_filter($this->issues, fn($i) => $i['severity'] === 'high');
            $medium = array_filter($this->issues, fn($i) => $i['severity'] === 'medium');

            if (!empty($high)) {
                $this->error('🚨 High Severity (' . count($high) . '):');
                $this->table(
                    ['Type', 'Table', 'Issue'],
                    array_map(fn($i) => [$i['type'], $i['table'] ?? $i['column'] ?? 'N/A', $i['issue']], $high)
                );
            }

            if (!empty($medium)) {
                $this->warn('⚠️  Medium Severity (' . count($medium) . '):');
                $this->table(
                    ['Type', 'Table', 'Issue'],
                    array_map(fn($i) => [$i['type'], $i['table'] ?? 'N/A', $i['issue']], $medium)
                );
            }
        } else {
            $this->info('✅ No foreign key issues found');
        }

        // Export to CSV if requested
        if ($export) {
            $this->exportResults();
        }

        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->info('  Orphaned records: ' . array_sum(array_column($this->orphans, 'orphan_count')));
        $this->info('  Issues found: ' . count($this->issues));
    }

    /**
     * Export results to CSV
     */
    protected function exportResults(): void
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = storage_path("logs/foreign-key-audit_{$timestamp}.csv");

        $fp = fopen($filename, 'w');

        // Write orphans
        if (!empty($this->orphans)) {
            fputcsv($fp, ['Type', 'Table', 'Column', 'Foreign Table', 'Orphan Count', 'Constraint']);
            foreach ($this->orphans as $orphan) {
                fputcsv($fp, [
                    'orphan',
                    $orphan['table'],
                    $orphan['column'],
                    $orphan['foreign_table'],
                    $orphan['orphan_count'],
                    $orphan['constraint'],
                ]);
            }
        }

        // Write issues
        if (!empty($this->issues)) {
            fputcsv($fp, ['Type', 'Table', 'Column', 'Issue', 'Severity']);
            foreach ($this->issues as $issue) {
                fputcsv($fp, [
                    $issue['type'],
                    $issue['table'] ?? '',
                    $issue['column'] ?? '',
                    $issue['issue'],
                    $issue['severity'],
                ]);
            }
        }

        fclose($fp);

        $this->info("📄 Results exported to: {$filename}");
    }
}
