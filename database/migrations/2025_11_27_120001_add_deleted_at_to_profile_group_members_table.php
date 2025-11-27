<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the deleted_at column to profile_group_members table
     * to support soft deletes (required because BaseModel uses SoftDeletes trait)
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE cmis.profile_group_members
            ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
        ");

        // Add index for soft deletes queries
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_profile_group_members_deleted_at
            ON cmis.profile_group_members (deleted_at)
            WHERE deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            DROP INDEX IF EXISTS cmis.idx_profile_group_members_deleted_at
        ");

        DB::statement("
            ALTER TABLE cmis.profile_group_members
            DROP COLUMN IF EXISTS deleted_at
        ");
    }
};
