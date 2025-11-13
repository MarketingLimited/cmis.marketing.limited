<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 3.4: PDF Reports - Scheduled Reports Table
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.scheduled_reports (
                schedule_id UUID PRIMARY KEY,
                report_type VARCHAR(50) NOT NULL,
                entity_id UUID NOT NULL,
                frequency VARCHAR(20) NOT NULL DEFAULT 'weekly',
                format VARCHAR(10) NOT NULL DEFAULT 'pdf',
                delivery_method VARCHAR(20) NOT NULL DEFAULT 'email',
                recipients JSONB,
                config JSONB,
                is_active BOOLEAN DEFAULT true,
                last_run_at TIMESTAMPTZ,
                next_run_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_scheduled_reports_entity ON cmis.scheduled_reports(entity_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_scheduled_reports_next_run ON cmis.scheduled_reports(next_run_at) WHERE is_active = true");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_scheduled_reports_type ON cmis.scheduled_reports(report_type)");

        DB::statement("
            COMMENT ON TABLE cmis.scheduled_reports IS 'Scheduled report configurations for automated PDF/CSV generation'
        ");

        DB::statement("
            COMMENT ON COLUMN cmis.scheduled_reports.report_type IS 'Type of report: performance, ai_insights, organization_overview, content_analysis'
        ");

        DB::statement("
            COMMENT ON COLUMN cmis.scheduled_reports.frequency IS 'Report frequency: daily, weekly, monthly'
        ");

        DB::statement("
            COMMENT ON COLUMN cmis.scheduled_reports.delivery_method IS 'Delivery method: email, storage, both'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.scheduled_reports CASCADE");
    }
};
