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
    public function up(): void
    {
        // Drop all existing tables in schemas to ensure clean state
        $schemas = ['cmis', 'cmis_ai_analytics', 'cmis_analytics', 'cmis_audit', 'cmis_dev',
                    'cmis_knowledge', 'cmis_marketing', 'cmis_ops', 'cmis_security_backup_20251111_202413',
                    'cmis_staging', 'cmis_system_health', 'archive', 'lab', 'operations'];

        foreach ($schemas as $schema) {
            // Drop all tables in schema (but not the schema itself)
            DB::unprepared("
                DO $$
                DECLARE
                    r RECORD;
                BEGIN
                    FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = '{$schema}') LOOP
                        EXECUTE 'DROP TABLE IF EXISTS {$schema}.' || quote_ident(r.tablename) || ' CASCADE';
                    END LOOP;
                END $$;
            ");
        }

        $sql = file_get_contents(database_path('sql/complete_tables.sql'));

        if (!empty(trim($sql))) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        // Tables will be dropped when schemas are dropped
    }
};
