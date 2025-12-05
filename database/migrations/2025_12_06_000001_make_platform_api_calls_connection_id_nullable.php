<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * During OAuth flows, API calls are logged BEFORE the connection is created,
     * so connection_id needs to be nullable.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE cmis.platform_api_calls ALTER COLUMN connection_id DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't re-add NOT NULL as it would fail if there are null values
    }
};
