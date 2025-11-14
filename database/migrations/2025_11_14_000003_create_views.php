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
            DB::unprepared($sql);
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
