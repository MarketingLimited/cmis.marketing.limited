<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations (Phase 20: AI-Powered Campaign Optimization Engine).
     */
    public function up(): void
    {
        // ===== Optimization Models Table =====
        Schema::create('cmis.optimization_models', function (Blueprint $table) {
            $table->uuid('model_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->string('model_name', 255);
            $table->string('model_type', 50); // budget_allocation, bid_optimization, audience_targeting, creative_optimization
            $table->string('algorithm', 100); // gradient_descent, genetic_algorithm, bayesian_optimization, reinforcement_learning
            $table->string('objective', 50); // maximize_roas, minimize_cpa, maximize_conversions, maximize_revenue
            $table->jsonb('hyperparameters')->nullable();
            $table->jsonb('feature_config'); // Which features to use for optimization
            $table->decimal('performance_score', 8, 4)->nullable(); // Model accuracy/performance
            $table->integer('training_samples')->default(0);
            $table->timestamp('trained_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('status', 30)->default('training'); // training, ready, deprecated
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->index(['org_id', 'model_type']);
            $table->index('status');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.optimization_models ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.optimization_models
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Optimization Runs Table =====
        Schema::create('cmis.optimization_runs', function (Blueprint $table) {
            $table->uuid('run_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('model_id')->nullable();
            $table->string('optimization_type', 50); // budget, bid, audience, creative, multi_objective
            $table->string('scope', 50); // account, campaign, ad_set, ad
            $table->jsonb('scope_entities'); // Entity IDs being optimized
            $table->jsonb('constraints'); // Min/max budgets, target CPA, etc.
            $table->jsonb('current_state'); // Current configuration
            $table->jsonb('optimized_state'); // Recommended configuration
            $table->jsonb('performance_metrics'); // Expected improvement
            $table->string('status', 30)->default('running'); // running, completed, failed, applied
            $table->timestamp('started_at')->default(DB::raw('NOW()'));
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('iterations')->default(0);
            $table->decimal('improvement_score', 8, 2)->nullable(); // Expected % improvement
            $table->boolean('auto_apply')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('model_id')->references('model_id')->on('cmis.optimization_models')->onDelete('set null');

            $table->index(['org_id', 'optimization_type']);
            $table->index('status');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.optimization_runs ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.optimization_runs
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Budget Allocations Table =====
        Schema::create('cmis.budget_allocations', function (Blueprint $table) {
            $table->uuid('allocation_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('optimization_run_id')->nullable();
            $table->string('allocation_period', 50); // daily, weekly, monthly
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_budget', 15, 2);
            $table->jsonb('allocations'); // Per-campaign/platform budget distribution
            $table->jsonb('allocation_strategy'); // Algorithm used, constraints applied
            $table->decimal('expected_roas', 8, 2)->nullable();
            $table->decimal('expected_conversions', 10, 2)->nullable();
            $table->decimal('actual_roas', 8, 2)->nullable();
            $table->decimal('actual_conversions', 10, 2)->nullable();
            $table->string('status', 30)->default('proposed'); // proposed, active, completed, cancelled
            $table->boolean('auto_adjust')->default(false);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('optimization_run_id')->references('run_id')->on('cmis.optimization_runs')->onDelete('set null');

            $table->index(['org_id', 'status']);
            $table->index(['period_start', 'period_end']);
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.budget_allocations ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.budget_allocations
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Audience Overlaps Table =====
        Schema::create('cmis.audience_overlaps', function (Blueprint $table) {
            $table->uuid('overlap_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('campaign_a_id');
            $table->uuid('campaign_b_id');
            $table->string('platform', 50); // meta, google, tiktok, etc.
            $table->integer('audience_a_size')->default(0);
            $table->integer('audience_b_size')->default(0);
            $table->integer('overlap_size')->default(0);
            $table->decimal('overlap_percentage', 5, 2)->default(0.00);
            $table->decimal('competition_score', 5, 2)->default(0.00); // How much campaigns compete for same audience
            $table->jsonb('overlap_segments')->nullable(); // Demographic overlap details
            $table->jsonb('recommendations')->nullable(); // Deduplication suggestions
            $table->timestamp('analyzed_at')->default(DB::raw('NOW()'));
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->index(['org_id', 'platform']);
            $table->index(['campaign_a_id', 'campaign_b_id']);
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.audience_overlaps ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.audience_overlaps
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Attribution Models Table =====
        Schema::create('cmis.attribution_models', function (Blueprint $table) {
            $table->uuid('attribution_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->string('model_type', 50); // first_touch, last_touch, linear, time_decay, position_based, data_driven
            $table->string('conversion_window', 20)->default('30d'); // 1d, 7d, 30d, 90d
            $table->jsonb('touchpoint_weights'); // Weight assigned to each touchpoint
            $table->jsonb('channel_attribution'); // Revenue/conversions attributed to each channel
            $table->jsonb('campaign_attribution'); // Attribution per campaign
            $table->decimal('total_attributed_revenue', 15, 2)->default(0.00);
            $table->integer('total_attributed_conversions')->default(0);
            $table->date('analysis_start_date');
            $table->date('analysis_end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->index(['org_id', 'model_type']);
            $table->index('is_active');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.attribution_models ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.attribution_models
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Creative Performance Table =====
        Schema::create('cmis.creative_performance', function (Blueprint $table) {
            $table->uuid('performance_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('campaign_id');
            $table->uuid('creative_id');
            $table->string('creative_type', 50); // image, video, carousel, collection
            $table->jsonb('creative_elements'); // Headlines, descriptions, CTAs, colors, etc.
            $table->jsonb('performance_metrics'); // CTR, CVR, engagement rate, etc.
            $table->decimal('engagement_score', 8, 4)->default(0.0000);
            $table->decimal('conversion_score', 8, 4)->default(0.0000);
            $table->jsonb('audience_segments'); // Which audiences perform best
            $table->jsonb('recommendations'); // Creative optimization suggestions
            $table->date('analysis_date');
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->index(['org_id', 'campaign_id']);
            $table->index('creative_type');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.creative_performance ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.creative_performance
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Optimization Insights Table =====
        Schema::create('cmis.optimization_insights', function (Blueprint $table) {
            $table->uuid('insight_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('optimization_run_id')->nullable();
            $table->string('insight_type', 50); // opportunity, warning, recommendation, trend
            $table->string('category', 50); // budget, audience, creative, bidding, scheduling
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('title', 255);
            $table->text('description');
            $table->jsonb('data'); // Supporting data for the insight
            $table->jsonb('actions'); // Recommended actions
            $table->decimal('potential_impact', 8, 2)->nullable(); // Expected improvement %
            $table->decimal('confidence_score', 5, 2)->default(75.00); // Confidence in insight
            $table->boolean('auto_apply_eligible')->default(false);
            $table->string('status', 30)->default('new'); // new, reviewed, applied, dismissed
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('optimization_run_id')->references('run_id')->on('cmis.optimization_runs')->onDelete('set null');

            $table->index(['org_id', 'status']);
            $table->index(['category', 'severity']);
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.optimization_insights ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.optimization_insights
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.optimization_insights');
        Schema::dropIfExists('cmis.creative_performance');
        Schema::dropIfExists('cmis.attribution_models');
        Schema::dropIfExists('cmis.audience_overlaps');
        Schema::dropIfExists('cmis.budget_allocations');
        Schema::dropIfExists('cmis.optimization_runs');
        Schema::dropIfExists('cmis.optimization_models');
    }
};
