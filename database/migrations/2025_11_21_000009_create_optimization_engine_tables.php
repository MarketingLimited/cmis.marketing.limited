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
     * Run the migrations (Phase 20: AI-Powered Campaign Optimization Engine).
     */
    public function up(): void
    {
        // ===== Optimization Models Table =====
        if (!$this->tableExists('cmis', 'optimization_models')) {
            DB::statement("
                CREATE TABLE cmis.optimization_models (
                    model_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    model_name VARCHAR(255) NOT NULL,
                    model_type VARCHAR(50) NOT NULL,
                    algorithm VARCHAR(100) NOT NULL,
                    objective VARCHAR(50) NOT NULL,
                    hyperparameters JSONB NULL,
                    feature_config JSONB NOT NULL DEFAULT '{}',
                    performance_score DECIMAL(8, 4) NULL,
                    training_samples INTEGER NOT NULL DEFAULT 0,
                    trained_at TIMESTAMP NULL,
                    last_used_at TIMESTAMP NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'training' CHECK (status IN ('training', 'ready', 'deprecated')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_models_org_id ON cmis.optimization_models(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_models_type ON cmis.optimization_models(org_id, model_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_models_status ON cmis.optimization_models(status)");

            DB::statement('ALTER TABLE cmis.optimization_models ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.optimization_models");
            DB::statement("CREATE POLICY org_isolation ON cmis.optimization_models USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Optimization Runs Table =====
        if (!$this->tableExists('cmis', 'optimization_runs')) {
            DB::statement("
                CREATE TABLE cmis.optimization_runs (
                    run_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    model_id UUID NULL REFERENCES cmis.optimization_models(model_id) ON DELETE SET NULL,
                    optimization_type VARCHAR(50) NOT NULL,
                    scope VARCHAR(50) NOT NULL,
                    scope_entities JSONB NOT NULL DEFAULT '[]',
                    constraints JSONB NOT NULL DEFAULT '{}',
                    current_state JSONB NOT NULL DEFAULT '{}',
                    optimized_state JSONB NOT NULL DEFAULT '{}',
                    performance_metrics JSONB NOT NULL DEFAULT '{}',
                    status VARCHAR(30) NOT NULL DEFAULT 'running' CHECK (status IN ('running', 'completed', 'failed', 'applied')),
                    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    duration_ms INTEGER NULL,
                    iterations INTEGER NOT NULL DEFAULT 0,
                    improvement_score DECIMAL(8, 2) NULL,
                    auto_apply BOOLEAN NOT NULL DEFAULT FALSE,
                    applied_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_runs_org_id ON cmis.optimization_runs(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_runs_type ON cmis.optimization_runs(org_id, optimization_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_runs_status ON cmis.optimization_runs(status)");

            DB::statement('ALTER TABLE cmis.optimization_runs ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.optimization_runs");
            DB::statement("CREATE POLICY org_isolation ON cmis.optimization_runs USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Budget Allocations Table =====
        if (!$this->tableExists('cmis', 'budget_allocations')) {
            DB::statement("
                CREATE TABLE cmis.budget_allocations (
                    allocation_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    optimization_run_id UUID NULL REFERENCES cmis.optimization_runs(run_id) ON DELETE SET NULL,
                    allocation_period VARCHAR(50) NOT NULL,
                    period_start DATE NOT NULL,
                    period_end DATE NOT NULL,
                    total_budget DECIMAL(15, 2) NOT NULL,
                    allocations JSONB NOT NULL DEFAULT '{}',
                    allocation_strategy JSONB NOT NULL DEFAULT '{}',
                    expected_roas DECIMAL(8, 2) NULL,
                    expected_conversions DECIMAL(10, 2) NULL,
                    actual_roas DECIMAL(8, 2) NULL,
                    actual_conversions DECIMAL(10, 2) NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'proposed' CHECK (status IN ('proposed', 'active', 'completed', 'cancelled')),
                    auto_adjust BOOLEAN NOT NULL DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_budget_allocations_org_id ON cmis.budget_allocations(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_budget_allocations_status ON cmis.budget_allocations(org_id, status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_budget_allocations_period ON cmis.budget_allocations(period_start, period_end)");

            DB::statement('ALTER TABLE cmis.budget_allocations ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.budget_allocations");
            DB::statement("CREATE POLICY org_isolation ON cmis.budget_allocations USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Audience Overlaps Table =====
        if (!$this->tableExists('cmis', 'audience_overlaps')) {
            DB::statement("
                CREATE TABLE cmis.audience_overlaps (
                    overlap_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    campaign_a_id UUID NOT NULL,
                    campaign_b_id UUID NOT NULL,
                    platform VARCHAR(50) NOT NULL,
                    audience_a_size INTEGER NOT NULL DEFAULT 0,
                    audience_b_size INTEGER NOT NULL DEFAULT 0,
                    overlap_size INTEGER NOT NULL DEFAULT 0,
                    overlap_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                    competition_score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                    overlap_segments JSONB NULL,
                    recommendations JSONB NULL,
                    analyzed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_audience_overlaps_org_id ON cmis.audience_overlaps(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_audience_overlaps_platform ON cmis.audience_overlaps(org_id, platform)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_audience_overlaps_campaigns ON cmis.audience_overlaps(campaign_a_id, campaign_b_id)");

            DB::statement('ALTER TABLE cmis.audience_overlaps ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.audience_overlaps");
            DB::statement("CREATE POLICY org_isolation ON cmis.audience_overlaps USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Attribution Models Table =====
        if (!$this->tableExists('cmis', 'attribution_models')) {
            DB::statement("
                CREATE TABLE cmis.attribution_models (
                    attribution_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    model_type VARCHAR(50) NOT NULL,
                    conversion_window VARCHAR(20) NOT NULL DEFAULT '30d',
                    touchpoint_weights JSONB NOT NULL DEFAULT '{}',
                    channel_attribution JSONB NOT NULL DEFAULT '{}',
                    campaign_attribution JSONB NOT NULL DEFAULT '{}',
                    total_attributed_revenue DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
                    total_attributed_conversions INTEGER NOT NULL DEFAULT 0,
                    analysis_start_date DATE NOT NULL,
                    analysis_end_date DATE NOT NULL,
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_attribution_models_org_id ON cmis.attribution_models(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_attribution_models_type ON cmis.attribution_models(org_id, model_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_attribution_models_active ON cmis.attribution_models(is_active)");

            DB::statement('ALTER TABLE cmis.attribution_models ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.attribution_models");
            DB::statement("CREATE POLICY org_isolation ON cmis.attribution_models USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Creative Performance Table =====
        if (!$this->tableExists('cmis', 'creative_performance')) {
            DB::statement("
                CREATE TABLE cmis.creative_performance (
                    performance_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    campaign_id UUID NOT NULL,
                    creative_id UUID NOT NULL,
                    creative_type VARCHAR(50) NOT NULL,
                    creative_elements JSONB NOT NULL DEFAULT '{}',
                    performance_metrics JSONB NOT NULL DEFAULT '{}',
                    engagement_score DECIMAL(8, 4) NOT NULL DEFAULT 0.0000,
                    conversion_score DECIMAL(8, 4) NOT NULL DEFAULT 0.0000,
                    audience_segments JSONB NOT NULL DEFAULT '{}',
                    recommendations JSONB NULL,
                    analysis_date DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_creative_performance_org_id ON cmis.creative_performance(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_creative_performance_campaign ON cmis.creative_performance(org_id, campaign_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_creative_performance_type ON cmis.creative_performance(creative_type)");

            DB::statement('ALTER TABLE cmis.creative_performance ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.creative_performance");
            DB::statement("CREATE POLICY org_isolation ON cmis.creative_performance USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Optimization Insights Table =====
        if (!$this->tableExists('cmis', 'optimization_insights')) {
            DB::statement("
                CREATE TABLE cmis.optimization_insights (
                    insight_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    optimization_run_id UUID NULL REFERENCES cmis.optimization_runs(run_id) ON DELETE SET NULL,
                    insight_type VARCHAR(50) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    severity VARCHAR(20) NOT NULL DEFAULT 'medium' CHECK (severity IN ('low', 'medium', 'high', 'critical')),
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    data JSONB NOT NULL DEFAULT '{}',
                    actions JSONB NOT NULL DEFAULT '[]',
                    potential_impact DECIMAL(8, 2) NULL,
                    confidence_score DECIMAL(5, 2) NOT NULL DEFAULT 75.00,
                    auto_apply_eligible BOOLEAN NOT NULL DEFAULT FALSE,
                    status VARCHAR(30) NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'reviewed', 'applied', 'dismissed')),
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_insights_org_id ON cmis.optimization_insights(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_insights_status ON cmis.optimization_insights(org_id, status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_optimization_insights_category ON cmis.optimization_insights(category, severity)");

            DB::statement('ALTER TABLE cmis.optimization_insights ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.optimization_insights");
            DB::statement("CREATE POLICY org_isolation ON cmis.optimization_insights USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.optimization_insights CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.creative_performance CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.attribution_models CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.audience_overlaps CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.budget_allocations CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.optimization_runs CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.optimization_models CASCADE');
    }
};
