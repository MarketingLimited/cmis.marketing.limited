<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to check if column exists
        $columnExists = function ($table, $column) {
            $result = DB::selectOne("
                SELECT COUNT(*) as count
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = ?
                AND column_name = ?
            ", [$table, $column]);
            return $result->count > 0;
        };

        // Add invitation_token if not exists
        if (!$columnExists('user_orgs', 'invitation_token')) {
            DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN invitation_token VARCHAR(64) NULL");
        }

        // Add invitation_accepted_at if not exists
        if (!$columnExists('user_orgs', 'invitation_accepted_at')) {
            DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN invitation_accepted_at TIMESTAMP NULL");
        }

        // Add invitation_expires_at if not exists
        if (!$columnExists('user_orgs', 'invitation_expires_at')) {
            DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN invitation_expires_at TIMESTAMP NULL");
        }

        // Add index for faster token lookups (if not exists)
        try {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_user_orgs_invitation_token ON cmis.user_orgs(invitation_token)");
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS cmis.idx_user_orgs_invitation_token");
        DB::statement("ALTER TABLE cmis.user_orgs DROP COLUMN IF EXISTS invitation_token");
        DB::statement("ALTER TABLE cmis.user_orgs DROP COLUMN IF EXISTS invitation_accepted_at");
        DB::statement("ALTER TABLE cmis.user_orgs DROP COLUMN IF EXISTS invitation_expires_at");
    }
};
