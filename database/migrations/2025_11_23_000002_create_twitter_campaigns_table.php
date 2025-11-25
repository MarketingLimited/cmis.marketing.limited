<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Skip if table already exists from earlier migration
        if (!Schema::hasTable('cmis_twitter.campaigns')) {
            Schema::create('cmis_twitter.campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('integration_id')->nullable();

            // Twitter platform identifiers
            $table->string('platform_campaign_id')->unique()->index();
            $table->string('platform_account_id')->index();
            $table->string('funding_instrument_id')->nullable();

            // Campaign details
            $table->string('name');
            $table->string('objective')->nullable(); // AWARENESS, TWEET_ENGAGEMENTS, VIDEO_VIEWS, etc.
            $table->string('campaign_type')->default('PROMOTED_TWEETS'); // PROMOTED_TWEETS, PROMOTED_ACCOUNTS, PROMOTED_TRENDS

            // Budget information (stored in micros for precision)
            $table->bigInteger('daily_budget_amount_local_micro')->nullable();
            $table->bigInteger('total_budget_amount_local_micro')->nullable();
            $table->string('currency', 3)->default('USD');

            // Scheduling
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();

            // Campaign settings
            $table->string('status')->default('PAUSED'); // ACTIVE, PAUSED, DELETED
            $table->boolean('standard_delivery')->default(true);
            $table->integer('frequency_cap')->nullable();

            // Metadata and targeting (JSONB for flexibility)
            $table->jsonb('targeting_metadata')->nullable();
            $table->jsonb('platform_metadata')->nullable(); // Raw data from Twitter API

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.organizations')
                ->onDelete('cascade');

            $table->foreign('integration_id')
                ->references('integration_id')
                ->on('cmis.integrations')
                ->onDelete('set null');

            // Indexes
            $table->index('status');
            $table->index('campaign_type');
            $table->index('objective');
            $table->index('created_at');
        });

        // Enable RLS with standard org_id policy
        $this->enableRLS('cmis_twitter.campaigns');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->disableRLS('cmis_twitter.campaigns');
        Schema::dropIfExists('cmis_twitter.campaigns');
    }
};
