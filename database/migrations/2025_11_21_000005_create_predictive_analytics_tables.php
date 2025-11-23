<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations (Phase 16 - Predictive Analytics & Forecasting)
     */
    public function up(): void
    {
        // Forecasts table - time-series predictions
        Schema::create('cmis.forecasts', function (Blueprint $table) {
            $table->uuid('forecast_id')->primary();
            $table->uuid('org_id')->index();
            $table->string('entity_type', 50); // campaign, org, ad_set
            $table->uuid('entity_id')->nullable();
            $table->string('metric', 100); // revenue, conversions, spend, roi
            $table->string('forecast_type', 50)->default('time_series'); // time_series, linear_regression, arima
            $table->date('forecast_date');
            $table->decimal('predicted_value', 15, 2);
            $table->decimal('confidence_lower', 15, 2)->nullable();
            $table->decimal('confidence_upper', 15, 2)->nullable();
            $table->decimal('confidence_level', 5, 2)->default(95.00);
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->decimal('error', 15, 2)->nullable(); // prediction error
            $table->jsonb('model_params')->nullable();
            $table->timestamp('generated_at')->index();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // Anomalies table - unusual pattern detection
        Schema::create('cmis.anomalies', function (Blueprint $table) {
            $table->uuid('anomaly_id')->primary();
            $table->uuid('org_id')->index();
            $table->string('entity_type', 50);
            $table->uuid('entity_id')->nullable();
            $table->string('metric', 100);
            $table->string('anomaly_type', 50); // spike, drop, trend_change, outlier
            $table->string('severity', 20); // critical, high, medium, low
            $table->decimal('expected_value', 15, 2);
            $table->decimal('actual_value', 15, 2);
            $table->decimal('deviation_percentage', 8, 2);
            $table->decimal('confidence_score', 5, 2); // 0-100
            $table->date('detected_date');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('new'); // new, acknowledged, resolved, false_positive
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('user_id')->on('cmis.users');
        });

        // Recommendations table - AI-powered suggestions
        Schema::create('cmis.recommendations', function (Blueprint $table) {
            $table->uuid('recommendation_id')->primary();
            $table->uuid('org_id')->index();
            $table->string('entity_type', 50);
            $table->uuid('entity_id')->nullable();
            $table->string('recommendation_type', 50); // budget_increase, bid_adjustment, pause_campaign, creative_refresh
            $table->string('category', 50); // performance, budget, targeting, creative
            $table->string('priority', 20); // critical, high, medium, low
            $table->decimal('confidence_score', 5, 2); // 0-100
            $table->decimal('potential_impact', 15, 2)->nullable(); // Expected improvement ($)
            $table->string('impact_metric', 50)->nullable(); // roi, conversions, revenue
            $table->text('title');
            $table->text('description');
            $table->jsonb('action_details'); // Specific actions to take
            $table->jsonb('supporting_data')->nullable(); // Evidence/reasoning
            $table->string('status', 20)->default('pending'); // pending, accepted, rejected, implemented
            $table->uuid('actioned_by')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->text('action_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('actioned_by')->references('user_id')->on('cmis.users');
        });

        // Trend Analysis table - pattern detection
        Schema::create('cmis.trend_analysis', function (Blueprint $table) {
            $table->uuid('trend_id')->primary();
            $table->uuid('org_id')->index();
            $table->string('entity_type', 50);
            $table->uuid('entity_id')->nullable();
            $table->string('metric', 100);
            $table->string('trend_type', 50); // upward, downward, stable, seasonal, volatile
            $table->decimal('trend_strength', 5, 2); // -100 to 100
            $table->decimal('confidence', 5, 2); // 0-100
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('data_points');
            $table->decimal('slope', 15, 4)->nullable(); // Rate of change
            $table->jsonb('seasonality_detected')->nullable();
            $table->jsonb('pattern_details')->nullable();
            $table->text('interpretation')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // Model Performance Tracking
        Schema::create('cmis.prediction_models', function (Blueprint $table) {
            $table->uuid('model_id')->primary();
            $table->uuid('org_id')->index();
            $table->string('model_type', 50); // time_series, regression, classification, neural_network
            $table->string('metric', 100); // What it predicts
            $table->string('algorithm', 50); // arima, linear_regression, random_forest, lstm
            $table->jsonb('hyperparameters')->nullable();
            $table->decimal('accuracy_score', 5, 2)->nullable(); // 0-100
            $table->decimal('mae', 15, 2)->nullable(); // Mean Absolute Error
            $table->decimal('rmse', 15, 2)->nullable(); // Root Mean Square Error
            $table->decimal('r_squared', 5, 4)->nullable(); // R² score
            $table->integer('training_samples')->nullable();
            $table->date('trained_at')->nullable();
            $table->date('last_evaluated_at')->nullable();
            $table->string('status', 20)->default('active'); // active, deprecated, retraining
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // Create indexes
        DB::statement('CREATE INDEX idx_forecasts_entity ON cmis.forecasts(entity_type, entity_id)');
        DB::statement('CREATE INDEX idx_forecasts_date ON cmis.forecasts(forecast_date DESC)');
        DB::statement('CREATE INDEX idx_forecasts_metric ON cmis.forecasts(metric)');

        DB::statement('CREATE INDEX idx_anomalies_detected ON cmis.anomalies(detected_date DESC)');
        DB::statement('CREATE INDEX idx_anomalies_status ON cmis.anomalies(status)');
        DB::statement('CREATE INDEX idx_anomalies_severity ON cmis.anomalies(severity)');

        DB::statement('CREATE INDEX idx_recommendations_status ON cmis.recommendations(status)');
        DB::statement('CREATE INDEX idx_recommendations_priority ON cmis.recommendations(priority)');
        DB::statement('CREATE INDEX idx_recommendations_expires ON cmis.recommendations(expires_at)');

        DB::statement('CREATE INDEX idx_trends_period ON cmis.trend_analysis(period_start, period_end)');
        DB::statement('CREATE INDEX idx_trends_type ON cmis.trend_analysis(trend_type)');

        // Enable Row Level Security
        
        
        
        
        

        // Create RLS policies
        $this->enableRLS('cmis.forecasts');

        $this->enableRLS('cmis.anomalies');

        $this->enableRLS('cmis.recommendations');

        $this->enableRLS('cmis.trend_analysis');

        $this->enableRLS('cmis.prediction_models');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.prediction_models');
        Schema::dropIfExists('cmis.trend_analysis');
        Schema::dropIfExists('cmis.recommendations');
        Schema::dropIfExists('cmis.anomalies');
        Schema::dropIfExists('cmis.forecasts');
    }
};
