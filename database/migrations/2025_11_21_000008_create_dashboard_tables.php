<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations (Phase 19: Real-Time Analytics Dashboard & Reporting Hub).
     */
    public function up(): void
    {
        // ===== Dashboard Templates Table =====
        Schema::create('cmis.dashboard_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(); // NULL for global templates
            $table->uuid('created_by')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 50); // executive, performance, campaigns, automation, platform_health
            $table->boolean('is_global')->default(false);
            $table->boolean('is_default')->default(false);
            $table->jsonb('layout_config'); // Grid layout configuration
            $table->jsonb('widgets'); // Array of widget configurations
            $table->jsonb('filters'); // Default filters
            $table->string('refresh_interval', 20)->default('5m'); // 1m, 5m, 15m, 1h, manual
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['org_id', 'category']);
            $table->index('is_global');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.dashboard_templates ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.dashboard_templates
            USING (org_id IS NULL OR org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Dashboard Widgets Table =====
        Schema::create('cmis.dashboard_widgets', function (Blueprint $table) {
            $table->uuid('widget_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('user_id'); // Owner of the widget
            $table->uuid('dashboard_id')->nullable(); // If part of a saved dashboard
            $table->string('widget_type', 50); // kpi_card, line_chart, bar_chart, pie_chart, table, map
            $table->string('data_source', 100); // campaigns, forecasts, anomalies, automation_rules, platform_connections
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->jsonb('config'); // Widget-specific configuration
            $table->jsonb('query_params'); // Data query parameters
            $table->jsonb('style_config')->nullable(); // Colors, fonts, etc.
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(4);
            $table->integer('height')->default(4);
            $table->string('refresh_rate', 20)->default('5m');
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'user_id']);
            $table->index('widget_type');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.dashboard_widgets ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.dashboard_widgets
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Dashboard Snapshots Table =====
        Schema::create('cmis.dashboard_snapshots', function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('dashboard_id')->nullable();
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('snapshot_type', 50)->default('manual'); // manual, scheduled, automated
            $table->timestamp('snapshot_date')->default(DB::raw('NOW()'));
            $table->jsonb('data'); // Complete snapshot of dashboard data
            $table->jsonb('metadata'); // Filters, date range, etc.
            $table->integer('data_size_bytes')->nullable();
            $table->string('format', 20)->default('json'); // json, pdf, excel
            $table->string('status', 30)->default('completed'); // generating, completed, failed
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'snapshot_date']);
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.dashboard_snapshots ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.dashboard_snapshots
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Report Schedules Table =====
        Schema::create('cmis.report_schedules', function (Blueprint $table) {
            $table->uuid('schedule_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('dashboard_id')->nullable();
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->string('frequency', 50); // daily, weekly, monthly, quarterly
            $table->jsonb('schedule_config'); // Day of week, time, timezone
            $table->jsonb('recipients'); // Email addresses
            $table->string('format', 20)->default('pdf'); // pdf, excel, csv
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->integer('send_count')->default(0);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'enabled']);
            $table->index('next_send_at');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.report_schedules ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.report_schedules
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Dashboard Alerts Table =====
        Schema::create('cmis.dashboard_alerts', function (Blueprint $table) {
            $table->uuid('alert_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('widget_id')->nullable();
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->string('alert_type', 50); // threshold, anomaly, change, forecast
            $table->jsonb('condition'); // Alert trigger condition
            $table->jsonb('notification_config'); // Email, Slack, webhook
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'enabled']);
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.dashboard_alerts ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.dashboard_alerts
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // ===== Real-Time Metrics Cache Table =====
        Schema::create('cmis.realtime_metrics_cache', function (Blueprint $table) {
            $table->uuid('cache_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->string('metric_key', 255); // Unique identifier for the metric
            $table->string('metric_type', 100); // revenue, conversions, spend, roi, etc.
            $table->string('entity_type', 50)->nullable(); // campaign, account, platform
            $table->uuid('entity_id')->nullable();
            $table->jsonb('metric_data'); // Cached metric values
            $table->string('aggregation_period', 20); // realtime, hourly, daily, weekly
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('cached_at')->default(DB::raw('NOW()'));
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->unique(['org_id', 'metric_key', 'aggregation_period']);
            $table->index('expires_at');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.realtime_metrics_cache ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.realtime_metrics_cache
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.realtime_metrics_cache');
        Schema::dropIfExists('cmis.dashboard_alerts');
        Schema::dropIfExists('cmis.report_schedules');
        Schema::dropIfExists('cmis.dashboard_snapshots');
        Schema::dropIfExists('cmis.dashboard_widgets');
        Schema::dropIfExists('cmis.dashboard_templates');
    }
};
