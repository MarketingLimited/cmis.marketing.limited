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
        Schema::connection('pgsql')->table('cmis_integrations.integrations', function (Blueprint $table) {
            // Add encrypted token fields
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            $table->timestamp('token_refreshed_at')->nullable()->after('token_expires_at');

            // Add sync status fields
            $table->timestamp('last_synced_at')->nullable()->after('token_refreshed_at');
            $table->string('sync_status')->default('pending')->after('last_synced_at'); // pending, syncing, success, failed
            $table->json('sync_errors')->nullable()->after('sync_status');
            $table->integer('sync_retry_count')->default(0)->after('sync_errors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->table('cmis_integrations.integrations', function (Blueprint $table) {
            $table->dropColumn([
                'refresh_token',
                'token_expires_at',
                'token_refreshed_at',
                'last_synced_at',
                'sync_status',
                'sync_errors',
                'sync_retry_count',
            ]);
        });
    }
};
