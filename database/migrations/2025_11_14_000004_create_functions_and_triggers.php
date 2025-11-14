<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates all database functions, stored procedures, and triggers.
     * These provide business logic and automation at the database level.
     */
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/functions_and_triggers.sql'));

        try {
            DB::unprepared($sql);
        } catch (\Exception $e) {
            // Log error but allow migration to continue
            // Some functions may require extensions that aren't available
            \Log::warning("Function/Trigger creation warning: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all functions in cmis schema
        DB::unprepared("
            DO $$
            DECLARE
                func record;
            BEGIN
                FOR func IN
                    SELECT
                        n.nspname as schema_name,
                        p.proname as function_name,
                        pg_get_function_identity_arguments(p.oid) as args
                    FROM pg_proc p
                    JOIN pg_namespace n ON p.pronamespace = n.oid
                    WHERE n.nspname IN ('cmis', 'cmis_audit', 'cmis_ops')
                LOOP
                    EXECUTE format('DROP FUNCTION IF EXISTS %I.%I(%s) CASCADE',
                        func.schema_name, func.function_name, func.args);
                END LOOP;
            END $$;
        ");
    }
};
