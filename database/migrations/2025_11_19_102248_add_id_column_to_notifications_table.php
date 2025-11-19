<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if id column already exists
        $hasId = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'notifications'
                AND column_name = 'id'
            ) as exists
        ");

        if (!$hasId->exists) {
            // Add id column as an auto-incrementing serial column
            DB::statement("
                ALTER TABLE cmis.notifications
                ADD COLUMN id SERIAL UNIQUE
            ");

            echo "✓ Added id column to cmis.notifications\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasId = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'notifications'
                AND column_name = 'id'
            ) as exists
        ");

        if ($hasId->exists) {
            DB::statement("ALTER TABLE cmis.notifications DROP COLUMN IF EXISTS id CASCADE");
        }
    }
};
