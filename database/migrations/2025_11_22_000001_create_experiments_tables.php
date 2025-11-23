<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if experiments table already exists (created by earlier migration)
        if (Schema::hasTable('cmis.experiments')) {
            return;
        }

        // Create experiments table
        Schema::create('cmis.experiments', function (Blueprint $table) {
            $table->uuid('experiment_id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('experiment_type', 50); // campaign, content, audience, budget
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('metric', 100); // primary metric to optimize
            $table->jsonb('metrics')->nullable(); // additional metrics to track
            $table->string('hypothesis', 500)->nullable();
            $table->string('status', 20)->default('draft'); // draft, running, paused, completed, cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->default(14);
            $table->integer('sample_size_per_variant')->default(1000);
            $table->decimal('confidence_level', 5, 2)->default(95.00); // 95% confidence
            $table->decimal('minimum_detectable_effect', 5, 2)->default(5.00); // 5% minimum effect
            $table->string('traffic_allocation', 20)->default('equal'); // equal, weighted, adaptive
            $table->jsonb('config')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('winner_variant_id')->nullable();
            $table->decimal('statistical_significance', 5, 2)->nullable();
            $table->jsonb('results')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['experiment_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_by');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');
        });

        // Enable RLS
        $this->enableRLS('cmis.experiments');

        // Create experiment_variants table
        Schema::create('cmis.experiment_variants', function (Blueprint $table) {
            $table->uuid('variant_id')->primary();
            $table->uuid('experiment_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_control')->default(false);
            $table->decimal('traffic_percentage', 5, 2)->default(50.00);
            $table->jsonb('config'); // variant configuration
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('conversions')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->jsonb('metrics')->nullable(); // additional metrics
            $table->decimal('conversion_rate', 8, 4)->default(0);
            $table->decimal('improvement_over_control', 8, 2)->nullable();
            $table->decimal('confidence_interval_lower', 8, 4)->nullable();
            $table->decimal('confidence_interval_upper', 8, 4)->nullable();
            $table->string('status', 20)->default('active'); // active, paused, stopped
            $table->timestamps();

            // Indexes
            $table->index('experiment_id');
            $table->index(['experiment_id', 'is_control']);
            $table->index('status');

            // Foreign keys
            $table->foreign('experiment_id')->references('experiment_id')->on('cmis.experiments')->onDelete('cascade');
        });

        // Create experiment_results table (daily aggregated results)
        Schema::create('cmis.experiment_results', function (Blueprint $table) {
            $table->uuid('result_id')->primary();
            $table->uuid('experiment_id');
            $table->uuid('variant_id');
            $table->date('date');
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('conversions')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('ctr', 8, 4)->default(0); // click-through rate
            $table->decimal('cpc', 8, 4)->default(0); // cost per click
            $table->decimal('conversion_rate', 8, 4)->default(0);
            $table->decimal('roi', 8, 2)->default(0); // return on investment
            $table->jsonb('additional_metrics')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('experiment_id');
            $table->index('variant_id');
            $table->index('date');
            $table->unique(['experiment_id', 'variant_id', 'date']);

            // Foreign keys
            $table->foreign('experiment_id')->references('experiment_id')->on('cmis.experiments')->onDelete('cascade');
            $table->foreign('variant_id')->references('variant_id')->on('cmis.experiment_variants')->onDelete('cascade');
        });

        // Create experiment_events table (raw event tracking)
        Schema::create('cmis.experiment_events', function (Blueprint $table) {
            $table->uuid('event_id')->primary();
            $table->uuid('experiment_id');
            $table->uuid('variant_id');
            $table->string('event_type', 50); // impression, click, conversion, custom
            $table->string('user_id')->nullable(); // can be external user ID
            $table->string('session_id')->nullable();
            $table->decimal('value', 12, 2)->nullable(); // monetary value for conversions
            $table->jsonb('properties')->nullable(); // additional event properties
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Indexes
            $table->index('experiment_id');
            $table->index('variant_id');
            $table->index('event_type');
            $table->index('occurred_at');
            $table->index(['experiment_id', 'event_type', 'occurred_at']);

            // Foreign keys
            $table->foreign('experiment_id')->references('experiment_id')->on('cmis.experiments')->onDelete('cascade');
            $table->foreign('variant_id')->references('variant_id')->on('cmis.experiment_variants')->onDelete('cascade');
        });

        // Create indexes for performance
        DB::statement('CREATE INDEX idx_experiments_started_at ON cmis.experiments(started_at) WHERE started_at IS NOT NULL');
        DB::statement('CREATE INDEX idx_experiment_events_user_variant ON cmis.experiment_events(user_id, variant_id) WHERE user_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_experiment_results_date_range ON cmis.experiment_results(experiment_id, date)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order
        Schema::dropIfExists('cmis.experiment_events');
        Schema::dropIfExists('cmis.experiment_results');
        Schema::dropIfExists('cmis.experiment_variants');

        // Disable RLS before dropping
        $this->disableRLS('cmis.experiments');
        Schema::dropIfExists('cmis.experiments');
    }
};
