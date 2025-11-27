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
        // Always ensure sequence exists
        DB::statement("CREATE SEQUENCE IF NOT EXISTS cmis.notifications_id_seq");

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
            // Add id column with default from sequence
            DB::statement("
                ALTER TABLE cmis.notifications
                ADD COLUMN id INTEGER NOT NULL DEFAULT nextval('cmis.notifications_id_seq')
            ");

            echo "✓ Added id column to cmis.notifications\n";
        } else {
            // Column exists - ensure it has the correct default
            DB::statement("
                ALTER TABLE cmis.notifications
                ALTER COLUMN id SET DEFAULT nextval('cmis.notifications_id_seq')
            ");
        }

        // Set sequence ownership (idempotent)
        DB::statement("ALTER SEQUENCE cmis.notifications_id_seq OWNED BY cmis.notifications.id");

        // Add unique constraint (if not exists)
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS notifications_id_unique ON cmis.notifications (id)");
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
