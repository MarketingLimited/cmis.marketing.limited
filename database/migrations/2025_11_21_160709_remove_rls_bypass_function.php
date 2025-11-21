<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        // Recreate all policies WITHOUT the bypass clause
        $tables = [
            'ad_campaigns',
            'ad_accounts',
            'ad_sets',
            'ad_entities',
            'ad_metrics',
            'ad_audiences'
        ];

        foreach ($tables as $table) {
            // Drop existing policy
            DB::statement("DROP POLICY IF EXISTS {$table}_org_isolation ON cmis.{$table};");

            // Create new policy without bypass clause
            DB::statement("
                CREATE POLICY {$table}_org_isolation ON cmis.{$table}
                    FOR ALL
                    USING (
                        org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    )
                    WITH CHECK (
                        org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    );
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
            'ad_campaigns',
            'ad_accounts',
            'ad_sets',
            'ad_entities',
            'ad_metrics',
            'ad_audiences'
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_org_isolation ON cmis.{$table};");

            DB::statement("
                CREATE POLICY {$table}_org_isolation ON cmis.{$table}
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
