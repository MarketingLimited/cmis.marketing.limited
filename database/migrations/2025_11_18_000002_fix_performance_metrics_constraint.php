<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Schema Fix
 *
 * Description: Remove overly restrictive check constraint on performance_metrics.observed
 * The constraint limited values to 0-1 range, but the table stores raw metric values
 * like impressions (thousands) and clicks (hundreds), not normalized scores.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the restrictive check constraint
        DB::statement('ALTER TABLE cmis.performance_metrics DROP CONSTRAINT IF EXISTS performance_score_range');

        // Add a more reasonable constraint - just ensure non-negative values
        DB::statement('
            ALTER TABLE cmis.performance_metrics
            ADD CONSTRAINT performance_metrics_observed_non_negative
            CHECK (observed IS NULL OR observed >= 0)
        ');

        echo "✓ Fixed performance_metrics constraints\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.performance_metrics DROP CONSTRAINT IF EXISTS performance_metrics_observed_non_negative');

        DB::statement('
            ALTER TABLE cmis.performance_metrics
            ADD CONSTRAINT performance_score_range
            CHECK (observed >= 0::numeric AND observed <= 1::numeric)
        ');
    }
};
