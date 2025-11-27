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
     * Run the migrations (Phase 15 - A/B Testing & Experimentation)
     */
    public function up(): void
    {
        // Experiments table
        if (!$this->tableExists('cmis', 'experiments')) {
            DB::statement("
                CREATE TABLE cmis.experiments (
                    experiment_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id),
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    experiment_type VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    metric VARCHAR(100) NOT NULL,
                    metrics JSONB NULL,
                    hypothesis VARCHAR(500) NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'running', 'paused', 'completed', 'cancelled')),
                    start_date DATE NULL,
                    end_date DATE NULL,
                    duration_days INTEGER NULL,
                    sample_size_per_variant INTEGER NULL,
                    confidence_level DECIMAL(5, 2) NOT NULL DEFAULT 95.00,
                    minimum_detectable_effect DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
                    traffic_allocation VARCHAR(20) NOT NULL DEFAULT 'equal' CHECK (traffic_allocation IN ('equal', 'weighted', 'adaptive')),
                    config JSONB NULL,
                    started_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    winner_variant_id VARCHAR(255) NULL,
                    statistical_significance DECIMAL(5, 2) NULL,
                    results JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_experiments_org_id ON cmis.experiments(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_experiments_status ON cmis.experiments(status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_experiments_dates ON cmis.experiments(start_date, end_date)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_experiments_entity ON cmis.experiments(entity_type, entity_id)");

            DB::statement('ALTER TABLE cmis.experiments ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.experiments");
            DB::statement("CREATE POLICY org_isolation ON cmis.experiments USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Experiment Variants table
        if (!$this->tableExists('cmis', 'experiment_variants')) {
            DB::statement("
                CREATE TABLE cmis.experiment_variants (
                    variant_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    experiment_id UUID NOT NULL REFERENCES cmis.experiments(experiment_id) ON DELETE CASCADE,
                    name VARCHAR(100) NOT NULL,
                    description TEXT NULL,
                    is_control BOOLEAN NOT NULL DEFAULT FALSE,
                    traffic_percentage DECIMAL(5, 2) NOT NULL DEFAULT 50.00,
                    config JSONB NOT NULL DEFAULT '{}',
                    impressions INTEGER NOT NULL DEFAULT 0,
                    clicks INTEGER NOT NULL DEFAULT 0,
                    conversions INTEGER NOT NULL DEFAULT 0,
                    spend DECIMAL(15, 2) NOT NULL DEFAULT 0,
                    revenue DECIMAL(15, 2) NOT NULL DEFAULT 0,
                    metrics JSONB NULL,
                    conversion_rate DECIMAL(8, 4) NULL,
                    improvement_over_control DECIMAL(8, 2) NULL,
                    confidence_interval_lower DECIMAL(8, 4) NULL,
                    confidence_interval_upper DECIMAL(8, 4) NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'stopped')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_variants_experiment_id ON cmis.experiment_variants(experiment_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_variants_experiment ON cmis.experiment_variants(experiment_id, is_control)");

            DB::statement('ALTER TABLE cmis.experiment_variants ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.experiment_variants");
            DB::statement("
                CREATE POLICY org_isolation ON cmis.experiment_variants
                USING (
                    experiment_id IN (
                        SELECT experiment_id FROM cmis.experiments
                        WHERE org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    )
                )
            ");
        }

        // Experiment Results (detailed time-series data)
        if (!$this->tableExists('cmis', 'experiment_results')) {
            DB::statement("
                CREATE TABLE cmis.experiment_results (
                    result_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    experiment_id UUID NOT NULL REFERENCES cmis.experiments(experiment_id) ON DELETE CASCADE,
                    variant_id UUID NOT NULL REFERENCES cmis.experiment_variants(variant_id) ON DELETE CASCADE,
                    date DATE NOT NULL,
                    impressions INTEGER NOT NULL DEFAULT 0,
                    clicks INTEGER NOT NULL DEFAULT 0,
                    conversions INTEGER NOT NULL DEFAULT 0,
                    spend DECIMAL(15, 2) NOT NULL DEFAULT 0,
                    revenue DECIMAL(15, 2) NOT NULL DEFAULT 0,
                    ctr DECIMAL(8, 4) NULL,
                    cpc DECIMAL(10, 4) NULL,
                    conversion_rate DECIMAL(8, 4) NULL,
                    roi DECIMAL(10, 2) NULL,
                    additional_metrics JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT uq_experiment_results UNIQUE (experiment_id, variant_id, date)
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_results_experiment_id ON cmis.experiment_results(experiment_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_results_variant_id ON cmis.experiment_results(variant_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_results_date ON cmis.experiment_results(date DESC)");

            DB::statement('ALTER TABLE cmis.experiment_results ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.experiment_results");
            DB::statement("
                CREATE POLICY org_isolation ON cmis.experiment_results
                USING (
                    experiment_id IN (
                        SELECT experiment_id FROM cmis.experiments
                        WHERE org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    )
                )
            ");
        }

        // Experiment Events (user interactions, conversions)
        if (!$this->tableExists('cmis', 'experiment_events')) {
            DB::statement("
                CREATE TABLE cmis.experiment_events (
                    event_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    experiment_id UUID NOT NULL REFERENCES cmis.experiments(experiment_id) ON DELETE CASCADE,
                    variant_id UUID NOT NULL REFERENCES cmis.experiment_variants(variant_id) ON DELETE CASCADE,
                    event_type VARCHAR(50) NOT NULL,
                    user_id VARCHAR(255) NULL,
                    session_id VARCHAR(255) NULL,
                    value DECIMAL(15, 2) NULL,
                    properties JSONB NULL,
                    occurred_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_events_experiment_id ON cmis.experiment_events(experiment_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_events_variant_id ON cmis.experiment_events(variant_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_events_occurred_at ON cmis.experiment_events(occurred_at DESC)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_events_type ON cmis.experiment_events(event_type)");

            DB::statement('ALTER TABLE cmis.experiment_events ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.experiment_events");
            DB::statement("
                CREATE POLICY org_isolation ON cmis.experiment_events
                USING (
                    experiment_id IN (
                        SELECT experiment_id FROM cmis.experiments
                        WHERE org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                    )
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.experiment_events CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.experiment_results CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.experiment_variants CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.experiments CASCADE');
    }
};
