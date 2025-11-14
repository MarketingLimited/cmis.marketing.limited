<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for A/B Testing (Sprint 4.3)
 * Enables simple A/B testing with 2-3 variants per campaign
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('cmis.ad_variants')) {
            return;
        }

        // Skip if required tables don't exist yet (migration ordering)
        if (!Schema::hasTable('cmis.campaigns')) {
            return;
        }

        Schema::connection('pgsql')->create('cmis.ad_variants', function (Blueprint $table) {
            $table->uuid('variant_id')->primary();
            $table->uuid('campaign_id')->index();

            // Variant configuration
            $table->string('variant_type', 50); // creative, copy, audience
            $table->string('variant_name', 100); // A, B, C
            $table->jsonb('variant_data'); // Platform-specific variant config

            // Budget allocation
            $table->decimal('budget_allocation', 5, 2)->default(33.33); // Percentage (33.33% for 3 variants)
            $table->decimal('actual_spend', 12, 2)->default(0);

            // Performance tracking
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_winner')->default(false);
            $table->timestamp('declared_winner_at')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign keys
            $table->foreign('campaign_id')->references('id')->on('cmis.ad_campaigns')->onDelete('cascade');

            // Indexes
            $table->index(['campaign_id', 'is_active'], 'ad_variants_campaign_active_idx');
            $table->index(['campaign_id', 'is_winner'], 'ad_variants_campaign_winner_idx');
        });

        DB::statement("COMMENT ON TABLE cmis.ad_variants IS 'A/B testing variants for ad campaigns (max 3 variants)'");
        DB::statement("COMMENT ON COLUMN cmis.ad_variants.variant_type IS 'creative, copy, or audience'");
        DB::statement("COMMENT ON COLUMN cmis.ad_variants.budget_allocation IS 'Percentage of campaign budget (0-100)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('cmis.ad_variants');
    }
};
