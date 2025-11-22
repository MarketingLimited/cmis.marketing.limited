<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditRLS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:audit-rls
                            {--table= : Audit specific table only}
                            {--schema=cmis : Schema to audit (default: cmis)}
                            {--fix : Attempt to fix RLS issues}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit Row-Level Security (RLS) policies across all CMIS tables to ensure multi-tenancy isolation';

    /**
     * Tables that should have RLS policies.
     */
    protected array $rlsTables = [];

    /**
     * Results of the audit.
     */
    protected array $results = [
        'total_tables' => 0,
        'with_rls' => 0,
        'without_rls' => 0,
        'policy_issues' => 0,
        'passed' => 0,
        'failed' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔒 CMIS RLS Policy Audit');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $schema = $this->option('schema');
        $specificTable = $this->option('table');

        // Get all tables to audit
        $tables = $this->getTablesToAudit($schema, $specificTable);

        if (empty($tables)) {
            $this->warn('No tables found to audit.');
            return 0;
        }

        $this->results['total_tables'] = count($tables);
        $this->info("Found {$this->results['total_tables']} tables to audit in schema '{$schema}'");
        $this->newLine();

        // Audit each table
        $this->withProgressBar($tables, function ($table) use ($schema) {
            $this->auditTable($schema, $table);
        });

        $this->newLine(2);

        // Display summary
        $this->displaySummary();

        // Return exit code based on results
        return $this->results['failed'] > 0 ? 1 : 0;
    }

    /**
     * Get list of tables to audit.
     */
    protected function getTablesToAudit(string $schema, ?string $specificTable): array
    {
        if ($specificTable) {
            return [$specificTable];
        }

        // Query for all tables in the schema
        $tables = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = ?
            AND tablename NOT LIKE 'pg_%'
            ORDER BY tablename
        ", [$schema]);

        return array_column($tables, 'tablename');
    }

    /**
     * Audit RLS policies for a specific table.
     */
    protected function auditTable(string $schema, string $table): void
    {
        $fullTableName = "{$schema}.{$table}";

        // Check if RLS is enabled
        $rlsEnabled = $this->isRLSEnabled($schema, $table);

        if (!$rlsEnabled) {
            $this->results['without_rls']++;
            $this->results['failed']++;

            if ($this->option('verbose')) {
                $this->newLine();
                $this->error("❌ {$fullTableName}: RLS not enabled");
            }

            if ($this->option('fix')) {
                $this->fixRLS($schema, $table);
            }

            return;
        }

        $this->results['with_rls']++;

        // Check if policies exist
        $policies = $this->getRLSPolicies($schema, $table);

        if (empty($policies)) {
            $this->results['policy_issues']++;
            $this->results['failed']++;

            if ($this->option('verbose')) {
                $this->newLine();
                $this->warn("⚠️  {$fullTableName}: RLS enabled but no policies defined");
            }

            return;
        }

        // Verify policies are correct
        $policyValid = $this->verifyPolicies($schema, $table, $policies);

        if ($policyValid) {
            $this->results['passed']++;

            if ($this->option('verbose')) {
                $this->newLine();
                $this->info("✅ {$fullTableName}: RLS properly configured");
            }
        } else {
            $this->results['failed']++;
        }
    }

    /**
     * Check if RLS is enabled on a table.
     */
    protected function isRLSEnabled(string $schema, string $table): bool
    {
        $result = DB::selectOne("
            SELECT relrowsecurity
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = ?
            AND c.relname = ?
        ", [$schema, $table]);

        return $result && $result->relrowsecurity;
    }

    /**
     * Get RLS policies for a table.
     */
    protected function getRLSPolicies(string $schema, string $table): array
    {
        $policies = DB::select("
            SELECT
                polname as policy_name,
                polcmd as command,
                polpermissive as permissive,
                pg_get_expr(polqual, polrelid) as using_expression,
                pg_get_expr(polwithcheck, polrelid) as with_check_expression
            FROM pg_policy pol
            JOIN pg_class c ON c.oid = pol.polrelid
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = ?
            AND c.relname = ?
        ", [$schema, $table]);

        return $policies;
    }

    /**
     * Verify RLS policies are correctly configured.
     */
    protected function verifyPolicies(string $schema, string $table, array $policies): bool
    {
        // Check that there's at least one SELECT policy
        $hasSelectPolicy = false;

        foreach ($policies as $policy) {
            if ($policy->command === '*' || $policy->command === 'r') {
                $hasSelectPolicy = true;

                // Verify it checks org_id
                if (!str_contains($policy->using_expression ?? '', 'org_id')) {
                    if ($this->option('verbose')) {
                        $this->newLine();
                        $this->warn("⚠️  {$schema}.{$table}: Policy '{$policy->policy_name}' doesn't filter by org_id");
                    }
                    return false;
                }
            }
        }

        if (!$hasSelectPolicy) {
            if ($this->option('verbose')) {
                $this->newLine();
                $this->warn("⚠️  {$schema}.{$table}: No SELECT policy found");
            }
            return false;
        }

        return true;
    }

    /**
     * Attempt to fix RLS issues.
     */
    protected function fixRLS(string $schema, string $table): void
    {
        $this->newLine();
        $this->info("Attempting to fix RLS for {$schema}.{$table}...");

        try {
            // Enable RLS
            DB::statement("ALTER TABLE {$schema}.{$table} ENABLE ROW LEVEL SECURITY");

            // Create default policy
            DB::statement("
                CREATE POLICY {$table}_org_isolation ON {$schema}.{$table}
                FOR ALL
                USING (org_id = current_setting('app.current_org_id')::uuid)
                WITH CHECK (org_id = current_setting('app.current_org_id')::uuid)
            ");

            $this->info("✅ Fixed RLS for {$schema}.{$table}");
            $this->results['without_rls']--;
            $this->results['with_rls']++;
            $this->results['failed']--;
            $this->results['passed']++;
        } catch (\Exception $e) {
            $this->error("❌ Failed to fix RLS for {$schema}.{$table}: {$e->getMessage()}");
        }
    }

    /**
     * Display audit summary.
     */
    protected function displaySummary(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📊 Audit Summary');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tables', $this->results['total_tables']],
                ['With RLS Enabled', $this->results['with_rls']],
                ['Without RLS', $this->results['without_rls']],
                ['Policy Issues', $this->results['policy_issues']],
                ['', ''],
                ['✅ Passed', $this->results['passed']],
                ['❌ Failed', $this->results['failed']],
            ]
        );

        $this->newLine();

        // Verdict
        if ($this->results['failed'] === 0) {
            $this->info('✅ All tables have proper RLS configuration!');
        } else {
            $this->error("❌ {$this->results['failed']} table(s) have RLS issues that need attention.");

            if (!$this->option('fix')) {
                $this->newLine();
                $this->warn('Run with --fix to automatically fix RLS issues.');
            }
        }

        // Recommendations
        if ($this->results['without_rls'] > 0) {
            $this->newLine();
            $this->warn('⚠️  SECURITY RISK: Tables without RLS can leak data across organizations!');
            $this->warn('   Enable RLS immediately or add them to the exclusion list if they are truly shared.');
        }
    }
}
