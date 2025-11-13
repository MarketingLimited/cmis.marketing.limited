<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Publishing Queues (Sprint 2.1)
 * Enables Buffer-style scheduling with custom time slots per social account
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql')->create('cmis.publishing_queues', function (Blueprint $table) {
            $table->uuid('queue_id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('social_account_id')->index();

            // Schedule configuration
            $table->string('weekdays_enabled', 7)->default('1111111'); // MTWTFSS bitmap
            $table->jsonb('time_slots')->default('[]'); // [{time: "09:00", enabled: true}, ...]
            $table->string('timezone', 50)->default('UTC');

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('social_account_id')->references('account_id')->on('cmis.social_accounts')->onDelete('cascade');

            // Indexes
            $table->unique(['social_account_id'], 'publishing_queues_account_unique');
        });

        // Add helpful comment
        DB::statement("COMMENT ON TABLE cmis.publishing_queues IS 'Buffer-style publishing queues with custom time slots per social account'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('cmis.publishing_queues');
    }
};
