<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: All Database Tables
 *
 * Description: Creates all 189 tables from database/schema.sql
 *
 * AI Agent Context: This creates the complete table structure.
 * All tables are created without constraints/indexes for dependency-free creation.
 */
return new class extends Migration
{
    /**
     * Disable wrapping this migration in a transaction.
     * Extensions and large SQL imports work better without transactions.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        // Ensure extensions exist in the public schema
        // Using IF NOT EXISTS to support parallel test execution
        // Extensions may already exist from previous migration or parallel workers
        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public');
        } catch (\Exception $e) {
            // Extension already exists, safe to ignore in parallel testing
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS btree_gin WITH SCHEMA public');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS citext WITH SCHEMA public');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS ltree WITH SCHEMA public');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        // Skip table dropping in parallel test environment
        // RefreshDatabase already handles database state
        // Dropping tables causes race conditions in parallel execution
        $isParallelTest = env('TEST_TOKEN') !== null;

        if (!$isParallelTest) {
            // Ensure Laravel migrations table exists in public schema BEFORE dropping tables
            // This prevents PostgreSQL from creating it in the wrong schema (cmis)
            DB::unprepared("
                CREATE TABLE IF NOT EXISTS public.migrations (
                    id SERIAL PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL
                )
            ");

            // Only drop tables in non-parallel environments
            // Exclude 'migrations' table as Laravel manages it
            $schemas = ['public', 'cmis', 'cmis_ai_analytics', 'cmis_analytics', 'cmis_audit', 'cmis_dev',
                        'cmis_knowledge', 'cmis_marketing', 'cmis_ops', 'cmis_security_backup_20251111_202413',
                        'cmis_staging', 'cmis_system_health', 'archive', 'lab', 'operations'];

            foreach ($schemas as $schema) {
                // Drop all tables in schema except migrations (Laravel managed)
                try {
                    DB::unprepared("
                        DO $$
                        DECLARE
                            r RECORD;
                        BEGIN
                            FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = '{$schema}' AND tablename != 'migrations') LOOP
                                EXECUTE 'DROP TABLE IF EXISTS {$schema}.' || quote_ident(r.tablename) || ' CASCADE';
                            END LOOP;
                        END $$;
                    ");
                } catch (\Exception $e) {
                    // Ignore errors during drop (tables may not exist)
                }
            }

            // Drop cmis.migrations if it exists (wrong schema)
            // Laravel should only use public.migrations
            DB::unprepared("DROP TABLE IF EXISTS cmis.migrations CASCADE");
        }

        $sql = file_get_contents(database_path('sql/complete_tables.sql'));

        if (!empty(trim($sql))) {
            // Replace ALL CREATE TABLE statements to use CREATE TABLE IF NOT EXISTS
            // This makes the migration idempotent and safe for parallel test execution
            $sql = preg_replace(
                '/CREATE TABLE ([\w.]+\.)?([\w]+) \(/i',
                'CREATE TABLE IF NOT EXISTS $1$2 (',
                $sql
            );

            try {
                DB::unprepared($sql);
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('Error creating tables from complete_tables.sql: ' . $e->getMessage());

                // Ignore "already exists" errors for parallel test execution
                // Re-throw other errors
                if (!str_contains($e->getMessage(), 'already exists') &&
                    !str_contains($e->getMessage(), 'duplicate key')) {
                    throw $e;
                }
            }
        }

        // Migrations table is now managed by Laravel in public schema (not cmis schema).
        // This avoids conflicts with Laravel's internal migration tracking.
        // The complete_tables.sql file no longer creates cmis.migrations.
    }

    public function down(): void
    {
        // Tables will be dropped when schemas are dropped
    }
};
