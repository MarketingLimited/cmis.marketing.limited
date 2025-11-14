<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Foundation: PostgreSQL Extensions and Database Schemas
 *
 * AI Agent Context:
 * - This migration sets up the database foundation
 * - Creates 8 PostgreSQL extensions for advanced features
 * - Creates 14 application schemas for logical separation
 * - Must run first before any tables can be created
 */
return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL Extensions
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS btree_gin');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS citext');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS ltree');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS vector');

        // Application Schemas
        $schemas = [
            'cmis',                    // Main application
            'cmis_ai_analytics',       // AI/ML analytics
            'cmis_analytics',          // Business analytics
            'cmis_audit',              // Audit logs
            'cmis_dev',                // Development
            'cmis_knowledge',          // Knowledge base
            'cmis_marketing',          // Marketing data
            'cmis_ops',                // Operations
            'cmis_staging',            // Staging area
            'cmis_system_health',      // Health monitoring
            'archive',                 // Historical data
            'lab',                     // Experimental features
            'operations',              // Ops tables
        ];

        foreach ($schemas as $schema) {
            DB::unprepared("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }
    }

    public function down(): void
    {
        $schemas = [
            'operations', 'lab', 'archive', 'cmis_system_health',
            'cmis_staging', 'cmis_ops', 'cmis_marketing', 'cmis_knowledge',
            'cmis_dev', 'cmis_audit', 'cmis_analytics', 'cmis_ai_analytics', 'cmis'
        ];

        foreach ($schemas as $schema) {
            DB::unprepared("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        }
    }
};
