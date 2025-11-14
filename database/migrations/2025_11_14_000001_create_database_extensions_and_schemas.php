<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create PostgreSQL extensions
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS btree_gin');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS citext');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS ltree');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS vector');

        // Note: plpython3u requires superuser privileges and may not be available
        // Uncomment if needed and you have superuser access:
        // DB::unprepared('CREATE EXTENSION IF NOT EXISTS plpython3u WITH SCHEMA pg_catalog');

        // Create application schemas
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_ai_analytics');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_analytics');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_audit');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_dev');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_knowledge');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_marketing');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_ops');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_staging');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS cmis_system_health');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS archive');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS lab');
        DB::unprepared('CREATE SCHEMA IF NOT EXISTS operations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop schemas (CASCADE will drop all objects in the schemas)
        DB::unprepared('DROP SCHEMA IF EXISTS operations CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS lab CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS archive CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_system_health CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_staging CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_ops CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_marketing CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_knowledge CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_dev CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_audit CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_analytics CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_ai_analytics CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS cmis CASCADE');

        // Note: Extensions are not dropped as they might be used by other databases
        // If you need to drop them, uncomment below:
        // DB::unprepared('DROP EXTENSION IF EXISTS vector');
        // DB::unprepared('DROP EXTENSION IF EXISTS ltree');
        // DB::unprepared('DROP EXTENSION IF EXISTS citext');
        // DB::unprepared('DROP EXTENSION IF EXISTS btree_gin');
        // DB::unprepared('DROP EXTENSION IF EXISTS pg_trgm');
        // DB::unprepared('DROP EXTENSION IF EXISTS pgcrypto');
        // DB::unprepared('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
