<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds profile_group_id to integrations table to link integrations to profile groups.
     */
    public function up(): void
    {
        // Add profile_group_id column if not exists
        $hasColumn = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'integrations'
                AND column_name = 'profile_group_id'
            ) as exists
        ");

        if (!$hasColumn->exists) {
            DB::statement("
                ALTER TABLE cmis.integrations
                ADD COLUMN profile_group_id UUID NULL
                REFERENCES cmis.profile_groups(group_id) ON DELETE SET NULL
            ");

            // Add index
            DB::statement("CREATE INDEX IF NOT EXISTS idx_integrations_profile_group_id ON cmis.integrations (profile_group_id)");
        }

        // Also add account_name, avatar_url columns if they don't exist (for richer profile display)
        $hasAccountName = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'integrations'
                AND column_name = 'account_name'
            ) as exists
        ");

        if (!$hasAccountName->exists) {
            DB::statement("
                ALTER TABLE cmis.integrations
                ADD COLUMN account_name VARCHAR(255) NULL,
                ADD COLUMN platform_handle VARCHAR(255) NULL,
                ADD COLUMN avatar_url TEXT NULL,
                ADD COLUMN status VARCHAR(50) DEFAULT 'active'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns if they exist
        DB::statement("
            ALTER TABLE cmis.integrations
            DROP COLUMN IF EXISTS profile_group_id,
            DROP COLUMN IF EXISTS account_name,
            DROP COLUMN IF EXISTS platform_handle,
            DROP COLUMN IF EXISTS avatar_url,
            DROP COLUMN IF EXISTS status
        ");

        // Drop index
        DB::statement("DROP INDEX IF EXISTS cmis.idx_integrations_profile_group_id");
    }
};
