<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cmis.integration_queue_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('cmis.integration_queue_settings', 'schedule')) {
                $table->jsonb('schedule')->nullable()->after('days_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cmis.integration_queue_settings', function (Blueprint $table) {
            if (Schema::hasColumn('cmis.integration_queue_settings', 'schedule')) {
                $table->dropColumn('schedule');
            }
        });
    }
};
