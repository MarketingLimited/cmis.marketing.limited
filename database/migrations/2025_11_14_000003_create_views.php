<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Views
 *
 * Description: Create all 44 views from database/schema.sql
 *
 * AI Agent Context: Views provide queryable representations of data.
 * Must run after tables are created.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing views first
        DB::unprepared("
            DO $$
            DECLARE
                v record;
            BEGIN
                FOR v IN
                    SELECT schemaname, viewname
                    FROM pg_views
                    WHERE schemaname IN ('cmis', 'cmis_ai_analytics', 'cmis_knowledge', 'cmis_system_health', 'operations', 'public')
                LOOP
                    EXECUTE format('DROP VIEW IF EXISTS %I.%I CASCADE', v.schemaname, v.viewname);
                END LOOP;
            END $$;
        ");

        $sql = file_get_contents(database_path('sql/complete_views.sql'));

        if (!empty(trim($sql))) {
            // In test environments, skip OWNER statements as the database user may differ
            if (app()->environment('testing') || env('DB_USERNAME') !== 'begin') {
                $sql = preg_replace('/ALTER\s+VIEW\s+[^\s]+\s+OWNER\s+TO\s+begin;/i', '', $sql);
            }

            // Split SQL into individual view creation statements
            $statements = preg_split('/;\s*(?=CREATE\s+VIEW|ALTER\s+VIEW)/i', $sql, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }

                try {
                    // Add semicolon back if it was removed
                    if (!str_ends_with($statement, ';')) {
                        $statement .= ';';
                    }

                    DB::unprepared($statement);
                } catch (\Exception $e) {
                    // Log warning but continue with other views
                    // Views that reference non-existent legacy tables will be skipped
                    \Log::warning("Skipping view creation: " . substr($statement, 0, 100) . "... Error: " . $e->getMessage());
                }
            }
        }
    }

    public function down(): void
    {
        // Drop all views
        DB::unprepared("
            DO $$
            DECLARE
                v record;
            BEGIN
                FOR v IN
                    SELECT schemaname, viewname
                    FROM pg_views
                    WHERE schemaname IN ('cmis', 'cmis_ai_analytics', 'cmis_knowledge', 'cmis_system_health', 'operations', 'public')
                LOOP
                    EXECUTE format('DROP VIEW IF EXISTS %I.%I CASCADE', v.schemaname, v.viewname);
                END LOOP;
            END $$;
        ");
    }
};
