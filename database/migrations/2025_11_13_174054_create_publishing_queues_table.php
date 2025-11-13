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
        Schema::create('cmis.publishing_queues', function (Blueprint $table) {
            $table->uuid('queue_id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('social_account_id')->index();
            $table->string('weekdays_enabled', 7)->default('1111111'); // MTWTFSS (Monday-Sunday)
            $table->jsonb('time_slots')->default('[]'); // [{time: "09:00", enabled: true}, ...]
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->string('provider')->nullable();

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            // Indexes for performance
            $table->index(['org_id', 'is_active']);
            $table->index(['social_account_id', 'is_active']);
        });

        // Add comment
        DB::statement("COMMENT ON TABLE cmis.publishing_queues IS 'Publishing queue configurations per social account - defines default posting times'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.publishing_queues');
    }
};
