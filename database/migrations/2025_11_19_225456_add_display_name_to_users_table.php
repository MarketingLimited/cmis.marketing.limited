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
        // Check if column already exists
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'display_name'
            ) as exists
        ");

        if (!$exists->exists) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN display_name VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.users DROP COLUMN IF EXISTS display_name');
    }
};
