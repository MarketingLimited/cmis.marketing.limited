<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Integrity - Fix Public Sessions User ID Type
 *
 * Description: Convert public.sessions.user_id from bigint to uuid to match
 * cmis.users.user_id type and enable foreign key constraint.
 *
 * Issue: public.sessions has user_id as bigint, but cmis.users.user_id is uuid.
 * This prevents adding a foreign key constraint.
 *
 * Solution: Clear sessions with invalid user_id values and convert column to uuid.
 */
return new class extends Migration
{
    /**
     * Disable transactions for direct SQL execution
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        echo "\n🔧 Fixing public.sessions.user_id type (bigint -> uuid)...\n\n";

        // Check if public.sessions table exists
        $tableExists = DB::selectOne("
            SELECT 1 as exists FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'sessions'
        ");

        if (!$tableExists) {
            echo "⏭️  Table public.sessions does not exist, skipping\n\n";
            return;
        }

        // Check current column type
        $columnType = DB::selectOne("
            SELECT data_type
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'sessions'
            AND column_name = 'user_id'
        ");

        if (!$columnType) {
            echo "⏭️  Column public.sessions.user_id does not exist, skipping\n\n";
            return;
        }

        if ($columnType->data_type === 'uuid') {
            echo "✓ Column public.sessions.user_id is already uuid type\n\n";
            return;
        }

        echo "   Current type: {$columnType->data_type}\n";

        // Clear all sessions with non-null user_id using unqualified table name
        // (search_path should find public.sessions)
        $clearedCount = DB::delete("DELETE FROM sessions WHERE user_id IS NOT NULL");

        if ($clearedCount > 0) {
            echo "⚠️  Cleared {$clearedCount} sessions with old user_id values\n";
        } else {
            echo "✓ No sessions needed clearing\n";
        }

        // Convert user_id column from bigint to uuid
        DB::unprepared("
            ALTER TABLE sessions
            ALTER COLUMN user_id TYPE uuid USING NULL
        ");

        echo "✅ Converted public.sessions.user_id to uuid type\n";
        echo "   Now compatible with cmis.users.user_id for foreign key constraints\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n🔄 Reverting public.sessions.user_id to bigint...\n\n";

        // Check if table exists
        $tableExists = DB::selectOne("
            SELECT 1 as exists FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'sessions'
        ");

        if (!$tableExists) {
            echo "⏭️  Table public.sessions does not exist, skipping\n\n";
            return;
        }

        // Clear all sessions first
        DB::delete("DELETE FROM sessions WHERE user_id IS NOT NULL");

        // Convert back to bigint
        DB::unprepared("
            ALTER TABLE sessions
            ALTER COLUMN user_id TYPE bigint USING NULL
        ");

        echo "✅ Reverted public.sessions.user_id to bigint\n\n";
    }
};
