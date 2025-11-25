<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * All cmis tables that need deleted_at column for SoftDeletes support.
     * BaseModel uses SoftDeletes trait, so all models need this column.
     */
    private array $tables = [
        'ab_test_variations',
        'ab_tests',
        'activities',
        'activity_logs',
        'ad_variants',
        'alert_history',
        'alert_notifications',
        'alert_templates',
        'analytics_metrics',
        'analytics_reports',
        'analytics_snapshots',
        'anomalies',
        'api_logs',
        'attribution_models',
        'audience_overlaps',
        'audience_segments',
        'audience_templates',
        'audiences',
        'audit_logs',
        'automation_audit_log',
        'automation_executions',
        'automation_rules',
        'automation_schedules',
        'automation_workflows',
        'best_time_recommendations',
        'budget_allocations',
        'budgets',
        'campaign_analytics',
        'campaign_budgets',
        'campaign_deliverables',
        'campaign_metrics',
        'campaign_orchestrations',
        'campaign_templates',
        'comments',
        'competitor_profiles',
        'content',
        'content_library',
        'content_media',
        'content_plan_items',
        'content_plans_v2',
        'creative_performance',
        'custom_fields',
        'custom_reports',
        'dashboard_alerts',
        'dashboard_configs',
        'dashboard_snapshots',
        'dashboard_templates',
        'dashboard_widgets',
        'data_export_configs',
        'data_export_logs',
        'data_exports',
        'data_snapshots',
        'embeddings_cache',
        'escalation_policies',
        'experiment_events',
        'experiment_results',
        'experiment_variants',
        'experiments',
        'feature_flag_audit_log',
        'feature_flag_overrides',
        'feature_flags',
        'forecasts',
        'inbox_items',
        'influencer_applications',
        'influencer_campaigns',
        'influencer_partnerships',
        'influencer_payments',
        'influencer_performance',
        'influencer_profiles',
        'invoices',
        'knowledge_index',
        'knowledge_indexes',
        'metric_definitions',
        'metrics',
        'monitoring_alerts',
        'monitoring_keywords',
        'notification_preferences',
        'notifications',
        'offerings',
        'onboarding_tips',
        'optimization_insights',
        'optimization_models',
        'optimization_runs',
        'orchestration_platforms',
        'orchestration_rules',
        'orchestration_sync_logs',
        'orchestration_workflows',
        'permissions_cache',
        'personal_access_tokens',
        'platform_api_calls',
        'platform_entity_mappings',
        'platform_posts',
        'platform_rate_limits',
        'platform_sync_logs',
        'platform_webhooks',
        'post_approvals',
        'posts',
        'prediction_models',
        'publishing_queue',
        'publishing_queues',
        'realtime_metrics_cache',
        'recommendations',
        'report_execution_logs',
        'report_schedules',
        'report_templates',
        'reports',
        'response_templates',
        'scheduled_jobs',
        'scheduled_posts',
        'scheduled_sms',
        'scheduled_social_posts_v2',
        'schedules',
        'security_context_audit',
        'semantic_search_log',
        'sentiment_analysis',
        'sessions',
        'sms_log',
        'social_accounts_v2',
        'social_conversations',
        'social_mentions',
        'social_posts_v2',
        'subscriptions',
        'tags',
        'team_account_access',
        'team_invitations',
        'team_members',
        'templates',
        'trend_analysis',
        'trending_topics',
        'user_onboarding_progress',
        'webhooks',
        'workflow_instances',
        'workflow_steps',
        'workflow_templates',
    ];

    public function up(): void
    {
        $added = 0;
        $skipped = 0;

        foreach ($this->tables as $table) {
            $hasDeletedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = 'cmis' AND table_name = ? AND column_name = 'deleted_at'
                ) as exists
            ", [$table]);

            if (!$hasDeletedAt->exists) {
                // Check if table exists first
                $tableExists = DB::selectOne("
                    SELECT EXISTS (
                        SELECT 1 FROM information_schema.tables
                        WHERE table_schema = 'cmis' AND table_name = ?
                    ) as exists
                ", [$table]);

                if ($tableExists->exists) {
                    DB::unprepared("ALTER TABLE cmis.{$table} ADD COLUMN deleted_at TIMESTAMP WITH TIME ZONE");
                    $added++;
                }
            } else {
                $skipped++;
            }
        }

        echo "✓ Added deleted_at column to {$added} tables, skipped {$skipped} (already had it)\n";
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $hasDeletedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = 'cmis' AND table_name = ? AND column_name = 'deleted_at'
                ) as exists
            ", [$table]);

            if ($hasDeletedAt->exists) {
                DB::unprepared("ALTER TABLE cmis.{$table} DROP COLUMN deleted_at");
            }
        }
    }
};
