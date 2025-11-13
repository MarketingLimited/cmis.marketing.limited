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
        // Create ab_tests table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_ads.ab_tests (
                ab_test_id UUID PRIMARY KEY,
                ad_account_id UUID NOT NULL,
                entity_type VARCHAR(20), -- ad, ad_set, campaign
                entity_id UUID,
                test_name VARCHAR(255) NOT NULL,
                test_type VARCHAR(50) NOT NULL DEFAULT 'creative', -- creative, audience, placement, delivery_optimization
                test_status VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft, running, stopped, completed
                hypothesis TEXT,
                metric_to_optimize VARCHAR(50) DEFAULT 'ctr', -- ctr, conversion_rate, cpa, roas, cpc, cpm
                budget_per_variation DECIMAL(15,2),
                test_duration_days INTEGER DEFAULT 7,
                min_sample_size INTEGER DEFAULT 1000,
                confidence_level DECIMAL(3,2) DEFAULT 0.95,
                winner_variation_id UUID,
                config JSONB,
                started_at TIMESTAMPTZ,
                scheduled_end_at TIMESTAMPTZ,
                completed_at TIMESTAMPTZ,
                stop_reason TEXT,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (ad_account_id) REFERENCES cmis_ads.ad_accounts(ad_account_id) ON DELETE CASCADE
            )
        ");

        // Create index on ad_account_id for faster lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ab_tests_ad_account
            ON cmis_ads.ab_tests(ad_account_id)
        ");

        // Create index on test_status for filtering
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ab_tests_status
            ON cmis_ads.ab_tests(test_status)
        ");

        // Create index on entity_id for entity-based queries
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ab_tests_entity
            ON cmis_ads.ab_tests(entity_type, entity_id)
        ");

        // Create ab_test_variations table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_ads.ab_test_variations (
                variation_id UUID PRIMARY KEY,
                ab_test_id UUID NOT NULL,
                variation_name VARCHAR(255) NOT NULL,
                is_control BOOLEAN DEFAULT false,
                entity_id UUID, -- Links to actual ad/ad_set being tested
                variation_config JSONB,
                traffic_allocation INTEGER DEFAULT 50, -- Percentage of traffic (0-100)
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (ab_test_id) REFERENCES cmis_ads.ab_tests(ab_test_id) ON DELETE CASCADE
            )
        ");

        // Create index on ab_test_id for variation lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ab_test_variations_test
            ON cmis_ads.ab_test_variations(ab_test_id)
        ");

        // Create index on entity_id for linking to ads
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ab_test_variations_entity
            ON cmis_ads.ab_test_variations(entity_id)
        ");

        // Add comment to tables
        DB::statement("
            COMMENT ON TABLE cmis_ads.ab_tests IS 'A/B testing experiments for ad campaigns - Sprint 4.6'
        ");

        DB::statement("
            COMMENT ON TABLE cmis_ads.ab_test_variations IS 'Variations within an A/B test - Sprint 4.6'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis_ads.ab_test_variations CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis_ads.ab_tests CASCADE");
    }
};
