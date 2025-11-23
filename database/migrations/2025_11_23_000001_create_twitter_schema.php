<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Create cmis_twitter schema for Twitter/X Ads platform
        DB::statement('CREATE SCHEMA IF NOT EXISTS cmis_twitter');

        // Grant usage to application role
        DB::statement('GRANT USAGE ON SCHEMA cmis_twitter TO begin');
        DB::statement('GRANT CREATE ON SCHEMA cmis_twitter TO begin');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop schema (CASCADE removes all objects in schema)
        DB::statement('DROP SCHEMA IF EXISTS cmis_twitter CASCADE');
    }
};
