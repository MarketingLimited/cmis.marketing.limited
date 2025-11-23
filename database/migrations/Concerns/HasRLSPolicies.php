<?php

namespace Database\Migrations\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * HasRLSPolicies Trait
 *
 * Provides standardized Row-Level Security (RLS) policy methods for migrations.
 * Eliminates duplicate RLS policy code across 79+ migrations with inconsistent patterns.
 *
 * CMIS uses PostgreSQL Row-Level Security for multi-tenancy.
 * This trait ensures all tables follow the same security pattern.
 *
 * Usage:
 * ```php
 * class CreateCampaignsTable extends Migration
 * {
 *     use HasRLSPolicies;
 *
 *     public function up()
 *     {
 *         Schema::create('cmis.campaigns', function (Blueprint $table) {
 *             // ... table definition
 *         });
 *
 *         // Enable RLS with single line
 *         $this->enableRLS('cmis.campaigns');
 *     }
 *
 *     public function down()
 *     {
 *         $this->disableRLS('cmis.campaigns');
 *         Schema::dropIfExists('cmis.campaigns');
 *     }
 * }
 * ```
 *
 * @package Database\Migrations\Concerns
 */
trait HasRLSPolicies
{
    /**
     * Enable Row-Level Security on a table with organization isolation
     *
     * Creates a standard RLS policy that filters rows by org_id using
     * the current_setting('app.current_org_id') PostgreSQL variable.
     *
     * @param string $tableName Fully qualified table name (e.g., 'cmis.campaigns')
     * @param string $orgColumn The organization column name (default: 'org_id')
     * @param string $policyName The policy name (default: 'org_isolation')
     * @return void
     */
    protected function enableRLS(
        string $tableName,
        string $orgColumn = 'org_id',
        string $policyName = 'org_isolation'
    ): void {
        // Enable Row-Level Security
        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY");

        // Drop existing policy if it exists to make this idempotent
        DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$tableName}");

        // Create organization isolation policy
        DB::statement("
            CREATE POLICY {$policyName} ON {$tableName}
            USING ({$orgColumn} = current_setting('app.current_org_id')::uuid)
        ");
    }

    /**
     * Enable RLS with custom policy expression
     *
     * For tables that need custom RLS logic beyond simple org_id filtering.
     *
     * @param string $tableName Fully qualified table name
     * @param string $policyExpression Custom SQL expression for the policy
     * @param string $policyName The policy name (default: 'custom_policy')
     * @return void
     */
    protected function enableCustomRLS(
        string $tableName,
        string $policyExpression,
        string $policyName = 'custom_policy'
    ): void {
        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY");

        // Drop existing policy if it exists to make this idempotent
        DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$tableName}");

        DB::statement("
            CREATE POLICY {$policyName} ON {$tableName}
            USING ({$policyExpression})
        ");
    }

    /**
     * Enable RLS with separate SELECT and INSERT/UPDATE/DELETE policies
     *
     * For tables that need different policies for read vs write operations.
     *
     * @param string $tableName Fully qualified table name
     * @param string $selectExpression SQL expression for SELECT policy
     * @param string $modifyExpression SQL expression for INSERT/UPDATE/DELETE policy
     * @param string $orgColumn The organization column name (default: 'org_id')
     * @return void
     */
    protected function enableRLSWithSeparatePolicies(
        string $tableName,
        string $selectExpression,
        string $modifyExpression,
        string $orgColumn = 'org_id'
    ): void {
        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY");

        // Drop existing policies if they exist to make this idempotent
        DB::statement("DROP POLICY IF EXISTS org_select_policy ON {$tableName}");
        DB::statement("DROP POLICY IF EXISTS org_modify_policy ON {$tableName}");

        // Policy for SELECT operations
        DB::statement("
            CREATE POLICY org_select_policy ON {$tableName}
            FOR SELECT
            USING ({$selectExpression})
        ");

        // Policy for INSERT/UPDATE/DELETE operations
        DB::statement("
            CREATE POLICY org_modify_policy ON {$tableName}
            FOR ALL
            USING ({$modifyExpression})
        ");
    }

    /**
     * Disable Row-Level Security on a table
     *
     * Drops all RLS policies and disables RLS.
     * Used in down() migrations for rollback.
     *
     * @param string $tableName Fully qualified table name
     * @param array $policyNames Additional policy names to drop (optional)
     * @return void
     */
    protected function disableRLS(string $tableName, array $policyNames = []): void
    {
        // Drop default organization isolation policy
        DB::statement("DROP POLICY IF EXISTS org_isolation ON {$tableName}");

        // Drop custom policies if specified
        foreach ($policyNames as $policyName) {
            DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$tableName}");
        }

        // Drop common policy variations
        DB::statement("DROP POLICY IF EXISTS org_select_policy ON {$tableName}");
        DB::statement("DROP POLICY IF EXISTS org_modify_policy ON {$tableName}");
        DB::statement("DROP POLICY IF EXISTS custom_policy ON {$tableName}");

        // Disable RLS on the table
        DB::statement("ALTER TABLE {$tableName} DISABLE ROW LEVEL SECURITY");
    }

    /**
     * Enable RLS for a public/shared table (no org filtering)
     *
     * For reference tables or shared resources that should be visible to all orgs.
     * Still enables RLS but with a policy that allows all access.
     *
     * @param string $tableName Fully qualified table name
     * @return void
     */
    protected function enablePublicRLS(string $tableName): void
    {
        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY");

        // Drop existing policy if it exists to make this idempotent
        DB::statement("DROP POLICY IF EXISTS allow_all ON {$tableName}");

        DB::statement("
            CREATE POLICY allow_all ON {$tableName}
            USING (true)
        ");
    }

    /**
     * Add RLS policy for admin users
     *
     * Creates an additional policy that allows admin users to see all records.
     * Useful for admin dashboards and system-wide operations.
     *
     * @param string $tableName Fully qualified table name
     * @param string $orgColumn The organization column name (default: 'org_id')
     * @return void
     */
    protected function addAdminBypassPolicy(
        string $tableName,
        string $orgColumn = 'org_id'
    ): void {
        // Drop existing policy if it exists to make this idempotent
        DB::statement("DROP POLICY IF EXISTS admin_bypass ON {$tableName}");

        DB::statement("
            CREATE POLICY admin_bypass ON {$tableName}
            USING (
                {$orgColumn} = current_setting('app.current_org_id')::uuid
                OR current_setting('app.is_admin', true)::boolean = true
            )
        ");
    }
}
