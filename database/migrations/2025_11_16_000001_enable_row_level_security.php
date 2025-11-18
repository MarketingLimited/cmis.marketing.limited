<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        // Enable RLS on all tables
        foreach ($tables as $table) {
            echo "Enabling RLS on {$table}...\n";
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        }

        // Create RLS policies for each table
        foreach ($tables as $table) {
            $tableName = explode('.', $table)[1];

            // Drop existing policy if it exists
            DB::statement("DROP POLICY IF EXISTS {$tableName}_tenant_isolation ON {$table}");

            // Create policy for SELECT, UPDATE, DELETE
            echo "Creating RLS policy for {$table}...\n";
            DB::statement("
                CREATE POLICY {$tableName}_tenant_isolation ON {$table}
                FOR ALL
                USING (org_id = cmis.current_org_id())
                WITH CHECK (org_id = cmis.current_org_id())
            ");
        }

        // Special policy for orgs table (users can only see their own orgs)
        DB::statement("DROP POLICY IF EXISTS orgs_tenant_isolation ON cmis.orgs");
        DB::statement("
            CREATE POLICY orgs_tenant_isolation ON cmis.orgs
            FOR ALL
            USING (org_id = cmis.current_org_id())
            WITH CHECK (org_id = cmis.current_org_id())
        ");

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

        // Drop all policies
        foreach ($tables as $table) {
            $tableName = explode('.', $table)[1];
            DB::statement("DROP POLICY IF EXISTS {$tableName}_tenant_isolation ON {$table}");
        }

        // Disable RLS on all tables
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }

        // Drop the function
        DB::statement("DROP FUNCTION IF EXISTS cmis.current_org_id()");

        echo "Row-Level Security disabled!\n";
    }
};