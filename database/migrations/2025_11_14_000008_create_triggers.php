<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Triggers
 *
 * Description: Create all 20 triggers for automated actions
 *
 * AI Agent Context: Triggers automatically execute functions when events occur.
 * Depends on functions, so must run after create_functions migration.
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
        DB::unprepared("
            DO $$
            DECLARE
                trig record;
            BEGIN
                FOR trig IN
                    SELECT
                        n.nspname as schema_name,
                        c.relname as table_name,
                        t.tgname as trigger_name
                    FROM pg_trigger t
                    JOIN pg_class c ON t.tgrelid = c.oid
                    JOIN pg_namespace n ON c.relnamespace = n.oid
                    WHERE n.nspname IN ('cmis', 'cmis_audit', 'cmis_ops')
                    AND NOT t.tgisinternal
                LOOP
                    EXECUTE format('DROP TRIGGER IF EXISTS %I ON %I.%I CASCADE',
                        trig.trigger_name, trig.schema_name, trig.table_name);
                END LOOP;
            END $$;
        ");
    }
};
