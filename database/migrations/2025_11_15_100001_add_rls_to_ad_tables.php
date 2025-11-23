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
     * Implement Row-Level Security (RLS) for Ad Platform Tables
     * This ensures organization data isolation at the database level
     */
    public function up(): void
    {
        // Enable RLS on all ad tables using HasRLSPolicies trait
        $tables = [
            'cmis.ad_campaigns',
            'cmis.ad_accounts',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.ad_metrics',
            'cmis.ad_audiences',
        ];

        foreach ($tables as $table) {
            $this->enableRLS($table);
        }

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

        // Disable RLS on all ad tables using HasRLSPolicies trait
        $tables = [
            'cmis.ad_campaigns',
            'cmis.ad_accounts',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.ad_metrics',
            'cmis.ad_audiences',
        ];

        foreach ($tables as $table) {
            $this->disableRLS($table);
        }
    }
};
