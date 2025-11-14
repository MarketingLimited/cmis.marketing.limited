<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Functions
 *
 * Description: Create all 126 stored functions and 1 procedure
 *
 * AI Agent Context: Functions encapsulate business logic at the database level.
 */
return new class extends Migration
{
    public function up(): void
    {
        $functions = file_get_contents(database_path('sql/all_functions.sql'));
        $procedures = file_get_contents(database_path('sql/complete_procedures.sql'));

        if (!empty(trim($functions))) {
            try {
                DB::unprepared($functions);
            } catch (\Exception $e) {
                \Log::warning("Function creation warning: " . $e->getMessage());
            }
        }

        if (!empty(trim($procedures))) {
            try {
                DB::unprepared($procedures);
            } catch (\Exception $e) {
                \Log::warning("Procedure creation warning: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
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
                    WHERE n.nspname IN ('cmis', 'cmis_audit', 'cmis_ops', 'cmis_analytics')
                LOOP
                    EXECUTE format('DROP FUNCTION IF EXISTS %I.%I(%s) CASCADE',
                        func.schema_name, func.function_name, func.args);
                END LOOP;
            END $$;
        ");
    }
};
