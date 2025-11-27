<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper to check if table exists
     */
    private function tableExists(string $schema, string $table): bool
    {
        $result = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name = ?
        ", [$schema, $table]);
        return $result->count > 0;
    }

    /**
     * Run the migrations (Phase 16 - Predictive Analytics & Forecasting)
     */
    public function up(): void
    {
        // Forecasts table - time-series predictions
        if (!$this->tableExists('cmis', 'forecasts')) {
            DB::statement("
                CREATE TABLE cmis.forecasts (
                    forecast_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    metric VARCHAR(100) NOT NULL,
                    forecast_type VARCHAR(50) NOT NULL DEFAULT 'time_series' CHECK (forecast_type IN ('time_series', 'linear_regression', 'arima')),
                    forecast_date DATE NOT NULL,
                    predicted_value DECIMAL(15, 2) NOT NULL,
                    confidence_lower DECIMAL(15, 2) NULL,
                    confidence_upper DECIMAL(15, 2) NULL,
                    confidence_level DECIMAL(5, 2) NOT NULL DEFAULT 95.00,
                    actual_value DECIMAL(15, 2) NULL,
                    error DECIMAL(15, 2) NULL,
                    model_params JSONB NULL,
                    generated_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_forecasts_org_id ON cmis.forecasts(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_forecasts_entity ON cmis.forecasts(entity_type, entity_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_forecasts_date ON cmis.forecasts(forecast_date DESC)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_forecasts_metric ON cmis.forecasts(metric)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_forecasts_generated ON cmis.forecasts(generated_at)");

            DB::statement('ALTER TABLE cmis.forecasts ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.forecasts");
            DB::statement("CREATE POLICY org_isolation ON cmis.forecasts USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Anomalies table - unusual pattern detection
        if (!$this->tableExists('cmis', 'anomalies')) {
            DB::statement("
                CREATE TABLE cmis.anomalies (
                    anomaly_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    metric VARCHAR(100) NOT NULL,
                    anomaly_type VARCHAR(50) NOT NULL CHECK (anomaly_type IN ('spike', 'drop', 'trend_change', 'outlier')),
                    severity VARCHAR(20) NOT NULL CHECK (severity IN ('critical', 'high', 'medium', 'low')),
                    expected_value DECIMAL(15, 2) NOT NULL,
                    actual_value DECIMAL(15, 2) NOT NULL,
                    deviation_percentage DECIMAL(8, 2) NOT NULL,
                    confidence_score DECIMAL(5, 2) NOT NULL,
                    detected_date DATE NOT NULL,
                    description TEXT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'acknowledged', 'resolved', 'false_positive')),
                    acknowledged_by UUID NULL REFERENCES cmis.users(user_id),
                    acknowledged_at TIMESTAMP NULL,
                    resolution_notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_anomalies_org_id ON cmis.anomalies(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_anomalies_detected ON cmis.anomalies(detected_date DESC)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_anomalies_status ON cmis.anomalies(status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_anomalies_severity ON cmis.anomalies(severity)");

            DB::statement('ALTER TABLE cmis.anomalies ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.anomalies");
            DB::statement("CREATE POLICY org_isolation ON cmis.anomalies USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Recommendations table - AI-powered suggestions
        if (!$this->tableExists('cmis', 'recommendations')) {
            DB::statement("
                CREATE TABLE cmis.recommendations (
                    recommendation_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    recommendation_type VARCHAR(50) NOT NULL,
                    category VARCHAR(50) NOT NULL CHECK (category IN ('performance', 'budget', 'targeting', 'creative')),
                    priority VARCHAR(20) NOT NULL CHECK (priority IN ('critical', 'high', 'medium', 'low')),
                    confidence_score DECIMAL(5, 2) NOT NULL,
                    potential_impact DECIMAL(15, 2) NULL,
                    impact_metric VARCHAR(50) NULL,
                    title TEXT NOT NULL,
                    description TEXT NOT NULL,
                    action_details JSONB NOT NULL DEFAULT '{}',
                    supporting_data JSONB NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected', 'implemented')),
                    actioned_by UUID NULL REFERENCES cmis.users(user_id),
                    actioned_at TIMESTAMP NULL,
                    action_notes TEXT NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_recommendations_org_id ON cmis.recommendations(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_recommendations_status ON cmis.recommendations(status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_recommendations_priority ON cmis.recommendations(priority)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_recommendations_expires ON cmis.recommendations(expires_at)");

            DB::statement('ALTER TABLE cmis.recommendations ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.recommendations");
            DB::statement("CREATE POLICY org_isolation ON cmis.recommendations USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Trend Analysis table - pattern detection
        if (!$this->tableExists('cmis', 'trend_analysis')) {
            DB::statement("
                CREATE TABLE cmis.trend_analysis (
                    trend_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    metric VARCHAR(100) NOT NULL,
                    trend_type VARCHAR(50) NOT NULL CHECK (trend_type IN ('upward', 'downward', 'stable', 'seasonal', 'volatile')),
                    trend_strength DECIMAL(5, 2) NOT NULL,
                    confidence DECIMAL(5, 2) NOT NULL,
                    period_start DATE NOT NULL,
                    period_end DATE NOT NULL,
                    data_points INTEGER NOT NULL,
                    slope DECIMAL(15, 4) NULL,
                    seasonality_detected JSONB NULL,
                    pattern_details JSONB NULL,
                    interpretation TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_trends_org_id ON cmis.trend_analysis(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_trends_period ON cmis.trend_analysis(period_start, period_end)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_trends_type ON cmis.trend_analysis(trend_type)");

            DB::statement('ALTER TABLE cmis.trend_analysis ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.trend_analysis");
            DB::statement("CREATE POLICY org_isolation ON cmis.trend_analysis USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Model Performance Tracking
        if (!$this->tableExists('cmis', 'prediction_models')) {
            DB::statement("
                CREATE TABLE cmis.prediction_models (
                    model_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    model_type VARCHAR(50) NOT NULL CHECK (model_type IN ('time_series', 'regression', 'classification', 'neural_network')),
                    metric VARCHAR(100) NOT NULL,
                    algorithm VARCHAR(50) NOT NULL,
                    hyperparameters JSONB NULL,
                    accuracy_score DECIMAL(5, 2) NULL,
                    mae DECIMAL(15, 2) NULL,
                    rmse DECIMAL(15, 2) NULL,
                    r_squared DECIMAL(5, 4) NULL,
                    training_samples INTEGER NULL,
                    trained_at DATE NULL,
                    last_evaluated_at DATE NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'deprecated', 'retraining')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_models_org_id ON cmis.prediction_models(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_models_type ON cmis.prediction_models(model_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_models_status ON cmis.prediction_models(status)");

            DB::statement('ALTER TABLE cmis.prediction_models ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.prediction_models");
            DB::statement("CREATE POLICY org_isolation ON cmis.prediction_models USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.prediction_models CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.trend_analysis CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.recommendations CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.anomalies CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.forecasts CASCADE');
    }
};
