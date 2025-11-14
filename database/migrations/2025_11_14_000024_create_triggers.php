<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Triggers
 *
 * Description: Create all triggers for automated actions
 *
 * AI Agent Context: Triggers automatically execute functions when events occur (INSERT, UPDATE, DELETE).
 * Common uses: audit logging, cache invalidation, data validation, cascading updates.
 * Triggers depend on functions, so this must run after create_functions migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/all_triggers.sql'));

        if (!empty(trim($sql))) {
            try {
                DB::unprepared($sql);
            } catch (\Exception $e) {
                \Log::warning("Trigger creation warning: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Drop all triggers in application schemas
        DB::unprepared("
            DO $$
            DECLARE
                trig record;
            BEGIN
                FOR trig IN
                    SELECT
                        schemaname,
                        tablename,
                        trigname
                    FROM pg_trigger t
                    JOIN pg_class c ON t.tgrelid = c.oid
                    JOIN pg_namespace n ON c.relnamespace = n.oid
                    WHERE n.nspname IN ('cmis', 'cmis_audit', 'cmis_ops')
                    AND NOT t.tgisinternal
                LOOP
                    EXECUTE format('DROP TRIGGER IF EXISTS %I ON %I.%I CASCADE',
                        trig.trigname, trig.schemaname, trig.tablename);
                END LOOP;
            END $$;
        ");
    }
};
