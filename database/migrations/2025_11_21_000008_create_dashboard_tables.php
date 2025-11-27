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
     * Run the migrations (Phase 19: Real-Time Analytics Dashboard & Reporting Hub).
     */
    public function up(): void
    {
        // ===== Dashboard Templates Table =====
        if (!$this->tableExists('cmis', 'dashboard_templates')) {
            DB::statement("
                CREATE TABLE cmis.dashboard_templates (
                    template_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NULL,
                    created_by UUID NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    category VARCHAR(50) NOT NULL CHECK (category IN ('executive', 'performance', 'campaigns', 'automation', 'platform_health')),
                    is_global BOOLEAN NOT NULL DEFAULT FALSE,
                    is_default BOOLEAN NOT NULL DEFAULT FALSE,
                    layout_config JSONB NOT NULL DEFAULT '{}',
                    widgets JSONB NOT NULL DEFAULT '[]',
                    filters JSONB NOT NULL DEFAULT '{}',
                    refresh_interval VARCHAR(20) NOT NULL DEFAULT '5m' CHECK (refresh_interval IN ('1m', '5m', '15m', '1h', 'manual')),
                    usage_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_templates_org ON cmis.dashboard_templates(org_id, category)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_templates_global ON cmis.dashboard_templates(is_global)");

            DB::statement('ALTER TABLE cmis.dashboard_templates ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.dashboard_templates");
            DB::statement("CREATE POLICY org_isolation ON cmis.dashboard_templates USING (org_id IS NULL OR org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Dashboard Widgets Table =====
        if (!$this->tableExists('cmis', 'dashboard_widgets')) {
            DB::statement("
                CREATE TABLE cmis.dashboard_widgets (
                    widget_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    user_id UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    dashboard_id UUID NULL,
                    widget_type VARCHAR(50) NOT NULL CHECK (widget_type IN ('kpi_card', 'line_chart', 'bar_chart', 'pie_chart', 'table', 'map')),
                    data_source VARCHAR(100) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    config JSONB NOT NULL DEFAULT '{}',
                    query_params JSONB NOT NULL DEFAULT '{}',
                    style_config JSONB NULL,
                    position_x INTEGER NOT NULL DEFAULT 0,
                    position_y INTEGER NOT NULL DEFAULT 0,
                    width INTEGER NOT NULL DEFAULT 4,
                    height INTEGER NOT NULL DEFAULT 4,
                    refresh_rate VARCHAR(20) NOT NULL DEFAULT '5m',
                    last_refreshed_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_widgets_org_id ON cmis.dashboard_widgets(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_widgets_user ON cmis.dashboard_widgets(org_id, user_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_widgets_type ON cmis.dashboard_widgets(widget_type)");

            DB::statement('ALTER TABLE cmis.dashboard_widgets ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.dashboard_widgets");
            DB::statement("CREATE POLICY org_isolation ON cmis.dashboard_widgets USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Dashboard Snapshots Table =====
        if (!$this->tableExists('cmis', 'dashboard_snapshots')) {
            DB::statement("
                CREATE TABLE cmis.dashboard_snapshots (
                    snapshot_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    dashboard_id UUID NULL,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    snapshot_type VARCHAR(50) NOT NULL DEFAULT 'manual' CHECK (snapshot_type IN ('manual', 'scheduled', 'automated')),
                    snapshot_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    data JSONB NOT NULL DEFAULT '{}',
                    metadata JSONB NOT NULL DEFAULT '{}',
                    data_size_bytes INTEGER NULL,
                    format VARCHAR(20) NOT NULL DEFAULT 'json' CHECK (format IN ('json', 'pdf', 'excel')),
                    status VARCHAR(30) NOT NULL DEFAULT 'completed' CHECK (status IN ('generating', 'completed', 'failed')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_snapshots_org_id ON cmis.dashboard_snapshots(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_snapshots_date ON cmis.dashboard_snapshots(org_id, snapshot_date)");

            DB::statement('ALTER TABLE cmis.dashboard_snapshots ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.dashboard_snapshots");
            DB::statement("CREATE POLICY org_isolation ON cmis.dashboard_snapshots USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Report Schedules Table =====
        if (!$this->tableExists('cmis', 'report_schedules')) {
            DB::statement("
                CREATE TABLE cmis.report_schedules (
                    schedule_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    dashboard_id UUID NULL,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    frequency VARCHAR(50) NOT NULL CHECK (frequency IN ('daily', 'weekly', 'monthly', 'quarterly')),
                    schedule_config JSONB NOT NULL DEFAULT '{}',
                    recipients JSONB NOT NULL DEFAULT '[]',
                    format VARCHAR(20) NOT NULL DEFAULT 'pdf' CHECK (format IN ('pdf', 'excel', 'csv')),
                    enabled BOOLEAN NOT NULL DEFAULT TRUE,
                    last_sent_at TIMESTAMP NULL,
                    next_send_at TIMESTAMP NULL,
                    send_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_report_schedules_org_id ON cmis.report_schedules(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_report_schedules_enabled ON cmis.report_schedules(org_id, enabled)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_report_schedules_next_send ON cmis.report_schedules(next_send_at)");

            DB::statement('ALTER TABLE cmis.report_schedules ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.report_schedules");
            DB::statement("CREATE POLICY org_isolation ON cmis.report_schedules USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Dashboard Alerts Table =====
        if (!$this->tableExists('cmis', 'dashboard_alerts')) {
            DB::statement("
                CREATE TABLE cmis.dashboard_alerts (
                    alert_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    widget_id UUID NULL,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    alert_type VARCHAR(50) NOT NULL CHECK (alert_type IN ('threshold', 'anomaly', 'change', 'forecast')),
                    condition JSONB NOT NULL DEFAULT '{}',
                    notification_config JSONB NOT NULL DEFAULT '{}',
                    enabled BOOLEAN NOT NULL DEFAULT TRUE,
                    last_triggered_at TIMESTAMP NULL,
                    trigger_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_alerts_org_id ON cmis.dashboard_alerts(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_dashboard_alerts_enabled ON cmis.dashboard_alerts(org_id, enabled)");

            DB::statement('ALTER TABLE cmis.dashboard_alerts ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.dashboard_alerts");
            DB::statement("CREATE POLICY org_isolation ON cmis.dashboard_alerts USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Real-Time Metrics Cache Table =====
        if (!$this->tableExists('cmis', 'realtime_metrics_cache')) {
            DB::statement("
                CREATE TABLE cmis.realtime_metrics_cache (
                    cache_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    metric_key VARCHAR(255) NOT NULL,
                    metric_type VARCHAR(100) NOT NULL,
                    entity_type VARCHAR(50) NULL,
                    entity_id UUID NULL,
                    metric_data JSONB NOT NULL DEFAULT '{}',
                    aggregation_period VARCHAR(20) NOT NULL CHECK (aggregation_period IN ('realtime', 'hourly', 'daily', 'weekly')),
                    period_start TIMESTAMP NULL,
                    period_end TIMESTAMP NULL,
                    cached_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT uq_realtime_metrics_cache UNIQUE (org_id, metric_key, aggregation_period)
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_realtime_metrics_cache_org_id ON cmis.realtime_metrics_cache(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_realtime_metrics_cache_expires ON cmis.realtime_metrics_cache(expires_at)");

            DB::statement('ALTER TABLE cmis.realtime_metrics_cache ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.realtime_metrics_cache");
            DB::statement("CREATE POLICY org_isolation ON cmis.realtime_metrics_cache USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.realtime_metrics_cache CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.dashboard_alerts CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.report_schedules CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.dashboard_snapshots CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.dashboard_widgets CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.dashboard_templates CASCADE');
    }
};
