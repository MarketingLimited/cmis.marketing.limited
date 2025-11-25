<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Integrity - Sessions Table Fix
 *
 * Description: Fix sessions.user_id data type from bigint to uuid to match users.user_id
 * This enables the foreign key constraint fk_sessions_user to be created.
 *
 * Issue: sessions.user_id was bigint but users.user_id is uuid, causing FK constraint failure
 * Solution: Convert sessions.user_id to uuid and create the foreign key
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if user_id is already uuid
        $currentType = DB::selectOne("
            SELECT data_type
            FROM information_schema.columns
            WHERE table_schema = 'cmis'
            AND table_name = 'sessions'
            AND column_name = 'user_id'
        ")->data_type;

        if ($currentType !== 'uuid') {
            // Truncate sessions table first since sessions are ephemeral and can be recreated
            // This avoids issues with converting bigint IDs to UUIDs
            DB::statement('TRUNCATE TABLE cmis.sessions CASCADE');
            echo "✓ Truncated sessions table (sessions are ephemeral)\n";

            // Now safely convert the column type
            DB::statement('ALTER TABLE cmis.sessions ALTER COLUMN user_id TYPE uuid USING user_id::text::uuid');
            echo "✓ Fixed sessions.user_id data type from {$currentType} to uuid\n";
        } else {
            echo "✓ sessions.user_id is already uuid\n";
        }

        // Check if foreign key exists
        $fkExists = DB::selectOne("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE constraint_schema = 'cmis'
            AND table_name = 'sessions'
            AND constraint_name = 'fk_sessions_user'
        ");

        if (!$fkExists) {
            DB::statement('
                ALTER TABLE cmis.sessions
                ADD CONSTRAINT fk_sessions_user
                FOREIGN KEY (user_id)
                REFERENCES cmis.users(user_id)
                ON DELETE SET NULL
            ');
            echo "✓ Created foreign key constraint fk_sessions_user\n";
        } else {
            echo "✓ Foreign key fk_sessions_user already exists\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.sessions DROP CONSTRAINT IF EXISTS fk_sessions_user');
        DB::statement('ALTER TABLE cmis.sessions ALTER COLUMN user_id TYPE bigint USING user_id::text::bigint');

        echo "✓ Dropped foreign key constraint fk_sessions_user\n";
        echo "✓ Reverted sessions.user_id to bigint\n";
    }
};
