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
     * Enable Row-Level Security (RLS) on all tenant-scoped tables to ensure
     * data isolation between organizations.
     */
    public function up(): void
    {
        // Tables that need RLS (all tables with org_id column that actually exist)
        $tables = [
            'cmis.orgs',
            'cmis.org_markets',
            'cmis.user_orgs',
            'cmis.campaigns',
            'cmis.content_plans',
            'cmis.content_items',
            'cmis.creative_assets',
            'cmis.ad_accounts',
            'cmis.ad_campaigns',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.ad_metrics',
        ];

        // Create function to get current org_id from session
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.current_org_id()
            RETURNS UUID AS $$
            DECLARE
                org_id_value TEXT;
            BEGIN
                -- Get the org_id from the session variable
                org_id_value := current_setting('app.current_org_id', true);

                -- Return NULL if not set or empty
                IF org_id_value IS NULL OR org_id_value = '' THEN
                    RETURN NULL;
                END IF;

                -- Return as UUID
                RETURN org_id_value::UUID;
            EXCEPTION
                WHEN OTHERS THEN
                    RETURN NULL;
            END;
            $$ LANGUAGE plpgsql STABLE SECURITY DEFINER;
        ");

        // Enable RLS on all tables using HasRLSPolicies trait
        foreach ($tables as $table) {
            echo "Enabling RLS on {$table}...\n";
            $this->enableRLS($table);
        }

        // Grant usage on the function to the application user
        DB::statement("GRANT EXECUTE ON FUNCTION cmis.current_org_id() TO begin");

        echo "Row-Level Security enabled successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'cmis.orgs',
            'cmis.org_markets',
            'cmis.user_orgs',
            'cmis.campaigns',
            'cmis.content_plans',
            'cmis.content_items',
            'cmis.creative_assets',
            'cmis.ad_accounts',
            'cmis.ad_campaigns',
            'cmis.ad_sets',
            'cmis.ad_entities',
            'cmis.ad_metrics',
        ];

        // Disable RLS on all tables using HasRLSPolicies trait
        foreach ($tables as $table) {
            $this->disableRLS($table);
        }

        // Drop the function
        DB::statement("DROP FUNCTION IF EXISTS cmis.current_org_id()");

        echo "Row-Level Security disabled!\n";
    }
};