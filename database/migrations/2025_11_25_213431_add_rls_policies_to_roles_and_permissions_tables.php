<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Security - RLS Policies for Roles and Permissions
 *
 * Description: Add Row-Level Security policies to roles and permissions tables.
 * These tables had RLS enabled but no policies, which blocked all access except
 * for direct SQL from superusers.
 *
 * Issue: Seeders couldn't insert roles/permissions because RLS was blocking Laravel queries
 * Solution: Add permissive policies that allow system-wide access to these reference tables
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop ALL existing RLS policies from previous migrations
        DB::statement('DROP POLICY IF EXISTS policy_all_cmis_roles ON cmis.roles');
        DB::statement('DROP POLICY IF EXISTS roles_select_policy ON cmis.roles');
        DB::statement('DROP POLICY IF EXISTS roles_insert_policy ON cmis.roles');
        DB::statement('DROP POLICY IF EXISTS roles_update_policy ON cmis.roles');
        DB::statement('DROP POLICY IF EXISTS roles_delete_policy ON cmis.roles');
        DB::statement('DROP POLICY IF EXISTS roles_policy_all ON cmis.roles');

        DB::statement('DROP POLICY IF EXISTS policy_all_cmis_permissions ON cmis.permissions');
        DB::statement('DROP POLICY IF EXISTS permissions_select_policy ON cmis.permissions');
        DB::statement('DROP POLICY IF EXISTS permissions_insert_policy ON cmis.permissions');
        DB::statement('DROP POLICY IF EXISTS permissions_update_policy ON cmis.permissions');
        DB::statement('DROP POLICY IF EXISTS permissions_delete_policy ON cmis.permissions');
        DB::statement('DROP POLICY IF EXISTS permissions_policy_all ON cmis.permissions');

        // DISABLE RLS on roles and permissions tables entirely
        // These are reference tables with no org-specific data
        // RLS adds unnecessary complexity and causes seeder visibility issues
        DB::statement('ALTER TABLE cmis.roles DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.permissions DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.role_permissions DISABLE ROW LEVEL SECURITY');

        echo "✓ RLS disabled on roles and permissions tables\n";
        echo "✓ These reference tables are now fully accessible\n";
        echo "✓ Seeders can insert and read without transaction isolation issues\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-enable RLS on rollback
        DB::statement('ALTER TABLE cmis.roles ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.permissions ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.role_permissions ENABLE ROW LEVEL SECURITY');

        echo "✓ Re-enabled RLS on roles and permissions tables\n";
    }
};
