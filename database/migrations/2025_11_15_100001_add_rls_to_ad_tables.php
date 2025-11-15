<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Implement Row-Level Security (RLS) for Ad Platform Tables
     * This ensures organization data isolation at the database level
     */
    public function up(): void
    {
        // Enable Row-Level Security on all ad tables
        DB::statement('ALTER TABLE cmis.ad_campaigns ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_accounts ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_sets ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_entities ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_metrics ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_audiences ENABLE ROW LEVEL SECURITY');

        // Create RLS policies for ad_campaigns
        DB::statement("
            CREATE POLICY ad_campaigns_org_isolation ON cmis.ad_campaigns
                FOR ALL
                USING (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    OR current_setting('app.bypass_rls', true) = 'true'
                )
                WITH CHECK (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                );
        ");

        // Create RLS policies for ad_accounts
        DB::statement("
            CREATE POLICY ad_accounts_org_isolation ON cmis.ad_accounts
                FOR ALL
                USING (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    OR current_setting('app.bypass_rls', true) = 'true'
                )
                WITH CHECK (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                );
        ");

        // Create RLS policies for ad_sets
        DB::statement("
            CREATE POLICY ad_sets_org_isolation ON cmis.ad_sets
                FOR ALL
                USING (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    OR current_setting('app.bypass_rls', true) = 'true'
                )
                WITH CHECK (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                );
        ");

        // Create RLS policies for ad_entities
        DB::statement("
            CREATE POLICY ad_entities_org_isolation ON cmis.ad_entities
                FOR ALL
                USING (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    OR current_setting('app.bypass_rls', true) = 'true'
                )
                WITH CHECK (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                );
        ");

        // Create RLS policies for ad_metrics
        DB::statement("
            CREATE POLICY ad_metrics_org_isolation ON cmis.ad_metrics
                FOR ALL
                USING (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    OR current_setting('app.bypass_rls', true) = 'true'
                )
                WITH CHECK (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                );
        ");

        // Create RLS policies for ad_audiences
        DB::statement("
            CREATE POLICY ad_audiences_org_isolation ON cmis.ad_audiences
                FOR ALL
                USING (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    OR current_setting('app.bypass_rls', true) = 'true'
                )
                WITH CHECK (
                    org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                );
        ");

        // Create helper function to set org context
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.set_org_context(p_org_id UUID)
            RETURNS VOID
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                PERFORM set_config('app.current_org_id', p_org_id::text, false);
            END;
            $$;
        ");

        // Create helper function to bypass RLS (admin only)
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

        // Create helper function to clear org context
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop helper functions
        DB::statement('DROP FUNCTION IF EXISTS cmis.set_org_context(UUID)');
        DB::statement('DROP FUNCTION IF EXISTS cmis.bypass_rls(BOOLEAN)');
        DB::statement('DROP FUNCTION IF EXISTS cmis.clear_org_context()');

        // Drop RLS policies
        DB::statement('DROP POLICY IF EXISTS ad_campaigns_org_isolation ON cmis.ad_campaigns');
        DB::statement('DROP POLICY IF EXISTS ad_accounts_org_isolation ON cmis.ad_accounts');
        DB::statement('DROP POLICY IF EXISTS ad_sets_org_isolation ON cmis.ad_sets');
        DB::statement('DROP POLICY IF EXISTS ad_entities_org_isolation ON cmis.ad_entities');
        DB::statement('DROP POLICY IF EXISTS ad_metrics_org_isolation ON cmis.ad_metrics');
        DB::statement('DROP POLICY IF EXISTS ad_audiences_org_isolation ON cmis.ad_audiences');

        // Disable Row-Level Security
        DB::statement('ALTER TABLE cmis.ad_campaigns DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_accounts DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_sets DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_entities DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_metrics DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.ad_audiences DISABLE ROW LEVEL SECURITY');
    }
};
