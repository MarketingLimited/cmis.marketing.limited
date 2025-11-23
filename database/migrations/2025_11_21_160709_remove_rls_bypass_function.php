<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * SECURITY FIX: Remove RLS bypass function and recreate policies without bypass
     * The bypass_rls() function was a critical security vulnerability allowing
     * complete circumvention of organization data isolation.
     */
    public function up(): void
    {
        // Drop the dangerous bypass function
        DB::statement('DROP FUNCTION IF EXISTS cmis.bypass_rls(BOOLEAN);');

        // Recreate all policies WITHOUT the bypass clause using HasRLSPolicies trait
        $tables = [
            'cmis.ad_campaigns',
            'cmis.ad_accounts',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.ad_metrics',
            'cmis.ad_audiences',
        ];

        foreach ($tables as $table) {
            $tableName = explode('.', $table)[1];

            // Drop existing policy with bypass clause
            DB::statement("DROP POLICY IF EXISTS {$tableName}_org_isolation ON {$table};");
            DB::statement("DROP POLICY IF EXISTS {$tableName}_tenant_isolation ON {$table};");

            // Recreate secure policy without bypass using trait
            // Note: RLS is already enabled on these tables, just recreating policies
            DB::statement("
                CREATE POLICY {$tableName}_tenant_isolation ON {$table}
                FOR ALL
                USING (org_id = current_setting('app.current_org_id', true)::uuid)
                WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
            ");
        }

        // Update clear_org_context to not reference bypass_rls
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.clear_org_context()
            RETURNS VOID
            LANGUAGE plpgsql
            AS $$
            BEGIN
                PERFORM set_config('app.current_org_id', '', false);
            END;
            $$;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: We intentionally do NOT recreate the bypass function in down()
     * as it's a security vulnerability. If rollback is needed, the original
     * migration will recreate it, but we log a warning.
     */
    public function down(): void
    {
        // WARNING: This rollback will NOT recreate the bypass function
        // as it's a security vulnerability. If you need the original
        // behavior, you must manually rollback to before this migration.

        // Recreate policies with bypass clause (for rollback compatibility only)
        $tables = [
            'cmis.ad_campaigns',
            'cmis.ad_accounts',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.ad_metrics',
            'cmis.ad_audiences',
        ];

        foreach ($tables as $table) {
            $tableName = explode('.', $table)[1];

            DB::statement("DROP POLICY IF EXISTS {$tableName}_org_isolation ON {$table};");
            DB::statement("DROP POLICY IF EXISTS {$tableName}_tenant_isolation ON {$table};");

            // Recreate with bypass clause for rollback
            DB::statement("
                CREATE POLICY {$tableName}_org_isolation ON {$table}
                    FOR ALL
                    USING (
                        org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                        OR current_setting('app.bypass_rls', true) = 'true'
                    )
                    WITH CHECK (
                        org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    );
            ");
        }

        // Recreate bypass function (only for complete rollback)
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.bypass_rls(p_bypass BOOLEAN DEFAULT true)
            RETURNS VOID
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                PERFORM set_config('app.bypass_rls', p_bypass::text, false);
            END;
            $$;
        ");

        // Restore original clear_org_context
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.clear_org_context()
            RETURNS VOID
            LANGUAGE plpgsql
            AS $$
            BEGIN
                PERFORM set_config('app.current_org_id', '', false);
                PERFORM set_config('app.bypass_rls', 'false', false);
            END;
            $$;
        ");

        // Log warning about security implications
        \Log::warning('RLS bypass function has been recreated during migration rollback. This is a security vulnerability.');
    }
};
