<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates all database tables from the schema definition.
     * Tables are created without foreign keys initially to avoid dependency issues.
     * Foreign keys and constraints are added in a subsequent migration.
     */
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/tables.sql'));
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all schemas with CASCADE to remove all tables
        $schemas = [
            'operations',
            'lab',
            'archive',
            'cmis_system_health',
            'cmis_staging',
            'cmis_ops',
            'cmis_marketing',
            'cmis_knowledge',
            'cmis_dev',
            'cmis_audit',
            'cmis_analytics',
            'cmis_ai_analytics',
            'cmis',
        ];

        foreach ($schemas as $schema) {
            DB::statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        }

        // Also drop public schema tables
        DB::statement("DROP TABLE IF EXISTS public.awareness_stages CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.channel_formats CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.channels CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.component_types CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.frameworks CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.funnel_stages CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.industries CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.kpis CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.marketing_objectives CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.markets CASCADE");
        DB::statement("DROP TABLE IF EXISTS public.proof_layers CASCADE");
    }
};
