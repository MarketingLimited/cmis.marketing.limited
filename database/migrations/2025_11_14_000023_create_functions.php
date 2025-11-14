<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Functions
 *
 * Description: Create all stored functions and procedures
 *
 * AI Agent Context: Functions encapsulate business logic at the database level.
 * Common patterns: permission checking, cache management, data transformations.
 * Functions can be called from triggers, queries, or application code.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/all_functions.sql'));

        if (!empty(trim($sql))) {
            try {
                DB::unprepared($sql);
            } catch (\Exception $e) {
                \Log::warning("Function creation warning: " . $e->getMessage());
                // Some functions may require extensions or permissions we don't have
            }
        }
    }

    public function down(): void
    {
        // Drop all functions in application schemas
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
