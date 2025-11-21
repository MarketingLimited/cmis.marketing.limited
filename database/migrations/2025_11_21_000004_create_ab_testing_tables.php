<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations (Phase 15 - A/B Testing & Experimentation)
     */
    public function up(): void
    {
        // Experiments table
        Schema::create('cmis.experiments', function (Blueprint $table) {
            $table->uuid('experiment_id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('experiment_type', 50); // campaign, content, audience, budget
            $table->string('entity_type', 50); // campaign, ad_set, ad, content_plan
            $table->uuid('entity_id')->nullable(); // Related entity ID
            $table->string('metric', 100); // Primary metric to optimize
            $table->jsonb('metrics')->nullable(); // Additional metrics to track
            $table->string('hypothesis', 500)->nullable();
            $table->string('status', 20)->default('draft'); // draft, running, paused, completed, cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->integer('sample_size_per_variant')->nullable();
            $table->decimal('confidence_level', 5, 2)->default(95.00); // 95%
            $table->decimal('minimum_detectable_effect', 5, 2)->default(5.00); // 5%
            $table->string('traffic_allocation', 20)->default('equal'); // equal, weighted, adaptive
            $table->jsonb('config')->nullable(); // Additional configuration
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('winner_variant_id')->nullable();
            $table->decimal('statistical_significance', 5, 2)->nullable();
            $table->jsonb('results')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users');
        });

        // Experiment Variants table
        Schema::create('cmis.experiment_variants', function (Blueprint $table) {
            $table->uuid('variant_id')->primary();
            $table->uuid('experiment_id')->index();
            $table->string('name', 100); // Control, Variant A, Variant B, etc.
            $table->text('description')->nullable();
            $table->boolean('is_control')->default(false);
            $table->decimal('traffic_percentage', 5, 2)->default(50.00);
            $table->jsonb('config'); // Variant-specific configuration
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('spend', 15, 2)->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->jsonb('metrics')->nullable(); // Additional metrics
            $table->decimal('conversion_rate', 8, 4)->nullable();
            $table->decimal('improvement_over_control', 8, 2)->nullable();
            $table->decimal('confidence_interval_lower', 8, 4)->nullable();
            $table->decimal('confidence_interval_upper', 8, 4)->nullable();
            $table->string('status', 20)->default('active'); // active, paused, stopped
            $table->timestamps();

            $table->foreign('experiment_id')->references('experiment_id')->on('cmis.experiments')->onDelete('cascade');
        });

        // Experiment Results (detailed time-series data)
        Schema::create('cmis.experiment_results', function (Blueprint $table) {
            $table->uuid('result_id')->primary();
            $table->uuid('experiment_id')->index();
            $table->uuid('variant_id')->index();
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('spend', 15, 2)->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('cpc', 10, 4)->nullable();
            $table->decimal('conversion_rate', 8, 4)->nullable();
            $table->decimal('roi', 10, 2)->nullable();
            $table->jsonb('additional_metrics')->nullable();
            $table->timestamps();

            $table->foreign('experiment_id')->references('experiment_id')->on('cmis.experiments')->onDelete('cascade');
            $table->foreign('variant_id')->references('variant_id')->on('cmis.experiment_variants')->onDelete('cascade');
            $table->unique(['experiment_id', 'variant_id', 'date']);
        });

        // Experiment Events (user interactions, conversions)
        Schema::create('cmis.experiment_events', function (Blueprint $table) {
            $table->uuid('event_id')->primary();
            $table->uuid('experiment_id')->index();
            $table->uuid('variant_id')->index();
            $table->string('event_type', 50); // impression, click, conversion, custom
            $table->string('user_id', 255)->nullable(); // External user identifier
            $table->string('session_id', 255)->nullable();
            $table->decimal('value', 15, 2)->nullable(); // Conversion value
            $table->jsonb('properties')->nullable(); // Event properties
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->foreign('experiment_id')->references('experiment_id')->on('cmis.experiments')->onDelete('cascade');
            $table->foreign('variant_id')->references('variant_id')->on('cmis.experiment_variants')->onDelete('cascade');
        });

        // Create indexes
        DB::statement('CREATE INDEX idx_experiments_status ON cmis.experiments(status)');
        DB::statement('CREATE INDEX idx_experiments_dates ON cmis.experiments(start_date, end_date)');
        DB::statement('CREATE INDEX idx_experiments_entity ON cmis.experiments(entity_type, entity_id)');
        DB::statement('CREATE INDEX idx_variants_experiment ON cmis.experiment_variants(experiment_id, is_control)');
        DB::statement('CREATE INDEX idx_results_date ON cmis.experiment_results(date DESC)');
        DB::statement('CREATE INDEX idx_events_occurred_at ON cmis.experiment_events(occurred_at DESC)');
        DB::statement('CREATE INDEX idx_events_type ON cmis.experiment_events(event_type)');

        // Enable Row Level Security on all tables
        DB::statement('ALTER TABLE cmis.experiments ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.experiment_variants ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.experiment_results ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.experiment_events ENABLE ROW LEVEL SECURITY');

        // Create RLS policies
        DB::statement("
            CREATE POLICY org_isolation ON cmis.experiments
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Variants inherit org_id from experiment
        DB::statement("
            CREATE POLICY org_isolation ON cmis.experiment_variants
            USING (
                experiment_id IN (
                    SELECT experiment_id FROM cmis.experiments
                    WHERE org_id = current_setting('app.current_org_id')::uuid
                )
            )
        ");

        // Results inherit org_id from experiment
        DB::statement("
            CREATE POLICY org_isolation ON cmis.experiment_results
            USING (
                experiment_id IN (
                    SELECT experiment_id FROM cmis.experiments
                    WHERE org_id = current_setting('app.current_org_id')::uuid
                )
            )
        ");

        // Events inherit org_id from experiment
        DB::statement("
            CREATE POLICY org_isolation ON cmis.experiment_events
            USING (
                experiment_id IN (
                    SELECT experiment_id FROM cmis.experiments
                    WHERE org_id = current_setting('app.current_org_id')::uuid
                )
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.experiment_events');
        Schema::dropIfExists('cmis.experiment_results');
        Schema::dropIfExists('cmis.experiment_variants');
        Schema::dropIfExists('cmis.experiments');
    }
};
