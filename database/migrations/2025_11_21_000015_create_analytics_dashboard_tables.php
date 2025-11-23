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
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if report_schedules already exists (created by earlier migration)
        if (Schema::hasTable('cmis.report_schedules')) {
            return;
        }

        // 1. Dashboard Configurations - Custom dashboard layouts
        Schema::create('cmis.dashboard_configs', function (Blueprint $table) {
            $table->uuid('dashboard_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Dashboard Information
            $table->string('dashboard_name', 255)->nullable(false);
            $table->text('description')->nullable();
            $table->string('dashboard_type', 50)->default('custom'); // overview, campaign, social, influencer, custom

            // Configuration
            $table->jsonb('layout_config')->nullable(false); // Widget positions, sizes, grid layout
            $table->jsonb('widgets')->nullable(false); // Array of widget configurations
            $table->jsonb('filters')->default('{}'); // Default filters (date range, platforms, etc.)
            $table->jsonb('theme_settings')->default('{}'); // Colors, fonts, styling

            // Permissions
            $table->boolean('is_public')->default(false);
            $table->jsonb('shared_with')->default('[]'); // User IDs with access
            $table->string('visibility', 50)->default('private'); // private, team, organization

            // Settings
            $table->integer('refresh_interval_seconds')->default(300); // Auto-refresh
            $table->boolean('enable_export')->default(true);
            $table->boolean('enable_drill_down')->default(true);

            // Usage Statistics
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            // Status
            $table->string('status', 50)->default('active'); // active, archived
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['created_by', 'status']);
            $table->index('dashboard_type');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.dashboard_configs');

        // 2. Custom Reports - Saved report configurations
        Schema::create('cmis.custom_reports', function (Blueprint $table) {
            $table->uuid('report_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Report Information
            $table->string('report_name', 255)->nullable(false);
            $table->text('description')->nullable();
            $table->string('report_type', 50)->nullable(false); // performance, roi, engagement, comparison, custom
            $table->string('category', 100)->nullable(); // social, campaign, influencer, automation

            // Data Source Configuration
            $table->jsonb('data_sources')->nullable(false); // Which tables/views to query
            $table->jsonb('metrics')->nullable(false); // Metrics to include
            $table->jsonb('dimensions')->nullable(false); // Grouping dimensions
            $table->jsonb('filters')->default('{}'); // Report filters
            $table->jsonb('aggregations')->default('[]'); // SUM, AVG, COUNT, etc.

            // Visualization
            $table->string('visualization_type', 50)->default('table'); // table, chart, graph, mixed
            $table->jsonb('chart_config')->default('{}'); // Chart type, colors, axes
            $table->jsonb('column_config')->default('[]'); // Table columns and formatting

            // Schedule Configuration
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency', 50)->nullable(); // daily, weekly, monthly
            $table->jsonb('schedule_config')->default('{}');
            $table->jsonb('recipients')->default('[]'); // Email recipients

            // Export Settings
            $table->jsonb('export_formats')->default('["pdf","excel","csv"]');
            $table->boolean('auto_send')->default(false);

            // Performance
            $table->boolean('use_cached_data')->default(true);
            $table->integer('cache_ttl_minutes')->default(15);
            $table->timestamp('last_generated_at')->nullable();
            $table->integer('generation_time_ms')->nullable();

            // Usage
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();

            // Status
            $table->string('status', 50)->default('active'); // active, archived
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['report_type', 'status']);
            $table->index('category');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.custom_reports');

        // 3. Data Snapshots - Historical data for comparisons
        Schema::create('cmis.data_snapshots', function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);

            // Snapshot Information
            $table->string('snapshot_type', 50)->nullable(false); // daily, weekly, monthly, quarterly, custom
            $table->date('snapshot_date')->nullable(false);
            $table->timestamp('captured_at')->default(DB::raw('NOW()'));

            // Aggregated Metrics from All Phases
            // Campaign Metrics (Phase 21)
            $table->jsonb('campaign_metrics')->default('{}'); // Total campaigns, active, budget spent, ROI

            // Publishing Metrics (Phase 22)
            $table->jsonb('publishing_metrics')->default('{}'); // Posts scheduled, published, engagement

            // Listening Metrics (Phase 23)
            $table->jsonb('listening_metrics')->default('{}'); // Mentions, sentiment, trends

            // Influencer Metrics (Phase 24)
            $table->jsonb('influencer_metrics')->default('{}'); // Partnerships, campaigns, ROI

            // Automation Metrics (Phase 25)
            $table->jsonb('automation_metrics')->default('{}'); // Workflows executed, success rate

            // Overall Metrics
            $table->jsonb('overall_metrics')->default('{}'); // Total reach, engagement, conversions, revenue

            // Growth Calculations
            $table->jsonb('growth_rates')->default('{}'); // Period-over-period growth
            $table->jsonb('trend_indicators')->default('{}'); // Positive, negative, stable trends

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'snapshot_date']);
            $table->index(['org_id', 'snapshot_type', 'snapshot_date']);
            $table->unique(['org_id', 'snapshot_type', 'snapshot_date']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.data_snapshots');

        // 4. Analytics Metrics - Real-time aggregated metrics
        Schema::create('cmis.analytics_metrics', function (Blueprint $table) {
            $table->uuid('metric_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);

            // Metric Identification
            $table->string('metric_name', 255)->nullable(false);
            $table->string('metric_category', 100)->nullable(false); // social, campaign, influencer, automation, financial
            $table->string('metric_type', 50)->nullable(false); // counter, gauge, rate, percentage, currency

            // Metric Value
            $table->decimal('metric_value', 20, 4)->nullable(false);
            $table->string('unit', 50)->nullable(); // USD, percent, count, etc.

            // Time Period
            $table->timestamp('period_start')->nullable(false);
            $table->timestamp('period_end')->nullable(false);
            $table->string('period_type', 20)->nullable(false); // hour, day, week, month, quarter, year

            // Dimensions (for filtering/grouping)
            $table->jsonb('dimensions')->default('{}'); // platform, campaign_id, influencer_id, etc.

            // Comparison Data
            $table->decimal('previous_value', 20, 4)->nullable();
            $table->decimal('change_value', 20, 4)->nullable();
            $table->decimal('change_percentage', 10, 4)->nullable();
            $table->string('trend', 20)->nullable(); // up, down, stable

            // Metadata
            $table->jsonb('metadata')->default('{}'); // Additional context
            $table->timestamp('calculated_at')->default(DB::raw('NOW()'));

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'metric_category']);
            $table->index(['org_id', 'metric_name', 'period_end']);
            $table->index(['period_start', 'period_end']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.analytics_metrics');

        // 5. Report Schedules - Automated report generation
        Schema::create('cmis.report_schedules', function (Blueprint $table) {
            $table->uuid('schedule_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('report_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Schedule Configuration
            $table->string('schedule_name', 255)->nullable(false);
            $table->string('frequency', 50)->nullable(false); // daily, weekly, monthly, quarterly
            $table->string('cron_expression')->nullable();
            $table->jsonb('schedule_config')->default('{}'); // Day of week, time, timezone

            // Recipients
            $table->jsonb('recipients')->nullable(false); // Email addresses
            $table->jsonb('cc_recipients')->default('[]');
            $table->string('subject_template')->nullable();
            $table->text('email_body_template')->nullable();

            // Delivery Settings
            $table->jsonb('delivery_formats')->default('["pdf"]'); // pdf, excel, csv
            $table->boolean('attach_to_email')->default(true);
            $table->boolean('include_dashboard_link')->default(true);

            // Execution Tracking
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->text('last_error')->nullable();

            // Status
            $table->string('status', 50)->default('active'); // active, paused, failed

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['report_id', 'status']);
            $table->index(['next_run_at', 'status']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('report_id')->references('report_id')->on('cmis.custom_reports')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.report_schedules');

        // 6. Data Exports - Export history and downloads
        Schema::create('cmis.data_exports', function (Blueprint $table) {
            $table->uuid('export_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('exported_by')->nullable(false);

            // Export Information
            $table->string('export_name', 255)->nullable(false);
            $table->string('export_type', 50)->nullable(false); // dashboard, report, raw_data
            $table->uuid('source_id')->nullable(); // dashboard_id or report_id
            $table->string('export_format', 20)->nullable(false); // pdf, excel, csv, json

            // Export Configuration
            $table->jsonb('export_config')->default('{}'); // Filters, date range, columns
            $table->jsonb('data_summary')->default('{}'); // Row counts, data ranges

            // File Information
            $table->string('file_path')->nullable();
            $table->string('file_url')->nullable();
            $table->integer('file_size_bytes')->nullable();
            $table->string('download_token')->nullable();

            // Processing
            $table->string('status', 50)->default('pending'); // pending, processing, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_seconds')->nullable();
            $table->text('error_message')->nullable();

            // Access Control
            $table->timestamp('expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->integer('max_downloads')->default(10);

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['exported_by', 'created_at']);
            $table->index('download_token');
            $table->index('expires_at');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.data_exports');

        // Create Comprehensive Analytics Views

        // View 1: Executive Summary Dashboard
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_executive_summary AS
            SELECT
                org_id,

                -- Campaign Performance (Phase 21)
                (SELECT COUNT(*) FROM cmis.campaign_orchestrations WHERE org_id = o.org_id AND status = 'active') as active_campaigns,
                (SELECT AVG(total_spend) FROM cmis.campaign_orchestrations WHERE org_id = o.org_id) as avg_campaign_spend,
                (SELECT AVG(roas) FROM cmis.campaign_orchestrations WHERE org_id = o.org_id) as avg_roas,

                -- Social Publishing (Phase 22)
                (SELECT COUNT(*) FROM cmis.scheduled_posts WHERE org_id = o.org_id AND status = 'published') as total_posts_published,
                (SELECT AVG(engagement_rate) FROM cmis.platform_posts WHERE org_id = o.org_id) as avg_post_engagement,

                -- Social Listening (Phase 23)
                (SELECT COUNT(*) FROM cmis.social_mentions WHERE org_id = o.org_id AND captured_at >= NOW() - INTERVAL '30 days') as mentions_30d,
                (SELECT AVG(CASE WHEN sentiment = 'positive' THEN 1 WHEN sentiment = 'negative' THEN -1 ELSE 0 END)
                 FROM cmis.social_mentions WHERE org_id = o.org_id AND captured_at >= NOW() - INTERVAL '30 days') as avg_sentiment_score,

                -- Influencer Marketing (Phase 24)
                (SELECT COUNT(*) FROM cmis.influencer_partnerships WHERE org_id = o.org_id AND status = 'active') as active_partnerships,
                (SELECT AVG(avg_roi) FROM cmis.influencer_profiles WHERE org_id = o.org_id) as avg_influencer_roi,

                -- Automation (Phase 25)
                (SELECT COUNT(*) FROM cmis.workflow_instances WHERE org_id = o.org_id AND status = 'completed' AND completed_at >= NOW() - INTERVAL '30 days') as workflows_completed_30d,
                (SELECT COUNT(*) FROM cmis.automation_rules WHERE org_id = o.org_id AND status = 'active') as active_automation_rules

            FROM cmis.orgs o;
        ");

        // View 2: Performance Trends (Last 30 Days)
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_performance_trends AS
            SELECT
                org_id,
                DATE(created_at) as date,

                -- Daily metrics
                COUNT(DISTINCT CASE WHEN EXISTS(SELECT 1 FROM cmis.social_mentions m WHERE m.org_id = o.org_id AND DATE(m.captured_at) = DATE(o.created_at)) THEN 1 END) as daily_mentions,
                COUNT(DISTINCT CASE WHEN EXISTS(SELECT 1 FROM cmis.scheduled_posts p WHERE p.org_id = o.org_id AND DATE(p.published_at) = DATE(o.created_at)) THEN 1 END) as daily_posts

            FROM cmis.orgs o
            WHERE o.created_at >= NOW() - INTERVAL '30 days'
            GROUP BY org_id, DATE(created_at)
            ORDER BY date DESC;
        ");

        // View 3: ROI Summary Across All Phases
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_roi_summary AS
            SELECT
                o.org_id,

                -- Campaign ROI
                COALESCE(SUM(c.total_spend), 0) as total_campaign_spend,
                COALESCE(SUM(c.total_revenue), 0) as total_campaign_revenue,
                CASE
                    WHEN SUM(c.total_spend) > 0
                    THEN ((SUM(c.total_revenue) - SUM(c.total_spend)) / SUM(c.total_spend) * 100)
                    ELSE 0
                END as campaign_roi_percentage,

                -- Influencer ROI
                COALESCE(SUM(ic.influencer_payment), 0) as total_influencer_spend,
                COALESCE(SUM(ic.conversion_value), 0) as total_influencer_revenue,
                CASE
                    WHEN SUM(ic.influencer_payment) > 0
                    THEN ((SUM(ic.conversion_value) - SUM(ic.influencer_payment)) / SUM(ic.influencer_payment) * 100)
                    ELSE 0
                END as influencer_roi_percentage,

                -- Overall ROI
                CASE
                    WHEN (COALESCE(SUM(c.total_spend), 0) + COALESCE(SUM(ic.influencer_payment), 0)) > 0
                    THEN (((COALESCE(SUM(c.total_revenue), 0) + COALESCE(SUM(ic.conversion_value), 0)) -
                           (COALESCE(SUM(c.total_spend), 0) + COALESCE(SUM(ic.influencer_payment), 0))) /
                          (COALESCE(SUM(c.total_spend), 0) + COALESCE(SUM(ic.influencer_payment), 0)) * 100)
                    ELSE 0
                END as overall_roi_percentage

            FROM cmis.orgs o
            LEFT JOIN cmis.campaign_orchestrations c ON o.org_id = c.org_id
            LEFT JOIN cmis.influencer_campaigns ic ON o.org_id = ic.org_id
            GROUP BY o.org_id;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement('DROP VIEW IF EXISTS cmis.v_roi_summary');
        DB::statement('DROP VIEW IF EXISTS cmis.v_performance_trends');
        DB::statement('DROP VIEW IF EXISTS cmis.v_executive_summary');

        // Drop tables in reverse order
        Schema::dropIfExists('cmis.data_exports');
        Schema::dropIfExists('cmis.report_schedules');
        Schema::dropIfExists('cmis.analytics_metrics');
        Schema::dropIfExists('cmis.data_snapshots');
        Schema::dropIfExists('cmis.custom_reports');
        Schema::dropIfExists('cmis.dashboard_configs');
    }
};
