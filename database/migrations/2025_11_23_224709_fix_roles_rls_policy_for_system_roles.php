<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix the RLS policy on cmis.roles to allow:
     * 1. System roles (org_id IS NULL) to be visible to everyone
     * 2. Organization-specific roles to be visible only to their org
     * 3. Admins to bypass RLS completely
     */
    public function up(): void
    {
        // Drop existing org_isolation policy if it exists
        DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.roles");

        // Create new policy that allows system roles and admin bypass
        DB::statement("
            CREATE POLICY org_isolation ON cmis.roles
            FOR ALL
            USING (
                org_id IS NULL
                OR org_id = (current_setting('app.current_org_id', true))::uuid
                OR (current_setting('app.is_admin', true)::boolean = true)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to org-only policy
        DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.roles");

        DB::statement("
            CREATE POLICY org_isolation ON cmis.roles
            FOR ALL
            USING (org_id = (current_setting('app.current_org_id', true))::uuid)
        ");
    }
};
