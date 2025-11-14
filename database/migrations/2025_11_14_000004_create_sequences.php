<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Sequences
 *
 * Description: Create all 30 sequences for auto-incrementing columns
 *
 * AI Agent Context: Sequences must be created before constraints that reference them
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing sequences first
        $schemas = ['cmis', 'cmis_ai_analytics', 'cmis_analytics', 'cmis_audit', 'cmis_dev',
                    'cmis_knowledge', 'cmis_marketing', 'cmis_ops', 'cmis_security_backup_20251111_202413',
                    'cmis_staging', 'cmis_system_health', 'archive', 'lab', 'operations', 'public'];

        foreach ($schemas as $schema) {
            DB::unprepared("
                DO $$
                DECLARE
                    r RECORD;
                BEGIN
                    FOR r IN (SELECT sequencename FROM pg_sequences WHERE schemaname = '{$schema}') LOOP
                        IF NOT (r.sequencename = 'migrations_id_seq' AND '{$schema}' = 'cmis') THEN
                            EXECUTE 'DROP SEQUENCE IF EXISTS {$schema}.' || quote_ident(r.sequencename) || ' CASCADE';
                        END IF;
                    END LOOP;
                END $$;
            ");
        }

        $sql = file_get_contents(database_path('sql/all_sequences.sql'));

        if (!empty(trim($sql))) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        // Sequences are dropped when tables are dropped
    }
};
