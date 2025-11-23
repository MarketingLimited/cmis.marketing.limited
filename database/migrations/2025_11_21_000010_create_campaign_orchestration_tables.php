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
     * Run the migrations (Phase 21: Cross-Platform Campaign Orchestration).
     */
    public function up(): void
    {
        // ===== Campaign Templates Table =====
        Schema::create('cmis.campaign_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(); // NULL for global templates
            $table->uuid('created_by')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 50); // awareness, consideration, conversion, retention
            $table->string('objective', 100); // brand_awareness, traffic, conversions, lead_gen, etc.
            $table->jsonb('platforms'); // Array of target platforms: meta, google, tiktok, etc.
            $table->jsonb('base_config'); // Base campaign configuration
            $table->jsonb('platform_specific_config'); // Platform-specific overrides
            $table->jsonb('creative_requirements'); // Required creative assets
            $table->jsonb('targeting_template'); // Default targeting configuration
            $table->jsonb('budget_template'); // Budget allocation template
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['org_id', 'category']);
            $table->index('is_global');
        });

        // RLS Policy
        $this->enableRLS('cmis.campaign_templates');
        $this->enableCustomRLS(
            'cmis.campaign_templates',
            "org_id IS NULL OR org_id = current_setting('app.current_org_id')::uuid"
        );

        // ===== Campaign Orchestrations Table =====
        Schema::create('cmis.campaign_orchestrations', function (Blueprint $table) {
            $table->uuid('orchestration_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('template_id')->nullable();
            $table->uuid('master_campaign_id')->nullable(); // Link to cmis.campaigns
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft'); // draft, scheduled, active, paused, completed, failed
            $table->jsonb('platforms'); // Array of platforms this orchestration spans
            $table->jsonb('orchestration_config'); // Master configuration
            $table->decimal('total_budget', 15, 2)->nullable();
            $table->jsonb('budget_allocation'); // Budget split across platforms
            $table->string('sync_strategy', 50)->default('manual'); // manual, auto, scheduled
            $table->integer('sync_frequency_minutes')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('platform_count')->default(0);
            $table->integer('active_platform_count')->default(0);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'status']);
            $table->index('scheduled_start_at');
        });

        $this->enableRLS('cmis.campaign_orchestrations');

        // ===== Orchestration Platforms Table =====
        Schema::create('cmis.orchestration_platforms', function (Blueprint $table) {
            $table->uuid('platform_mapping_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('orchestration_id');
            $table->uuid('connection_id'); // Link to platform_connections
            $table->string('platform', 50); // meta, google, tiktok, linkedin, twitter, snapchat
            $table->string('platform_campaign_id', 255)->nullable(); // ID on platform
            $table->string('platform_campaign_name', 255)->nullable();
            $table->string('status', 30)->default('pending'); // pending, creating, active, paused, failed, deleted
            $table->jsonb('platform_config'); // Platform-specific configuration
            $table->decimal('allocated_budget', 15, 2)->nullable();
            $table->decimal('spend', 15, 2)->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->timestamp('created_on_platform_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->jsonb('sync_metadata')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('orchestration_id')->references('orchestration_id')->on('cmis.campaign_orchestrations')->onDelete('cascade');

            $table->unique(['orchestration_id', 'platform']);
            $table->index(['org_id', 'platform']);
            $table->index('status');
        });

        $this->enableRLS('cmis.orchestration_platforms');

        // ===== Orchestration Workflows Table =====
        Schema::create('cmis.orchestration_workflows', function (Blueprint $table) {
            $table->uuid('workflow_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('orchestration_id');
            $table->string('workflow_type', 50); // creation, activation, optimization, sync, deactivation
            $table->string('status', 30)->default('pending'); // pending, running, completed, failed
            $table->jsonb('steps'); // Array of workflow steps
            $table->integer('current_step')->default(0);
            $table->integer('total_steps');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->jsonb('execution_log'); // Step-by-step execution log
            $table->text('error_message')->nullable();
            $table->jsonb('rollback_data')->nullable(); // Data for rollback if needed
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('orchestration_id')->references('orchestration_id')->on('cmis.campaign_orchestrations')->onDelete('cascade');

            $table->index(['org_id', 'workflow_type']);
            $table->index(['orchestration_id', 'status']);
        });

        $this->enableRLS('cmis.orchestration_workflows');

        // ===== Orchestration Rules Table =====
        Schema::create('cmis.orchestration_rules', function (Blueprint $table) {
            $table->uuid('rule_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('orchestration_id')->nullable(); // NULL for global rules
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('rule_type', 50); // budget_reallocation, pause_underperforming, scale_winners, creative_rotation
            $table->string('trigger', 50); // schedule, performance, manual
            $table->jsonb('trigger_conditions'); // Conditions that activate the rule
            $table->jsonb('actions'); // Actions to execute when triggered
            $table->boolean('enabled')->default(true);
            $table->string('priority', 20)->default('medium'); // low, medium, high, critical
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->index(['org_id', 'enabled']);
            $table->index('rule_type');
        });

        $this->enableRLS('cmis.orchestration_rules');

        // ===== Orchestration Sync Logs Table =====
        Schema::create('cmis.orchestration_sync_logs', function (Blueprint $table) {
            $table->uuid('sync_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('orchestration_id');
            $table->uuid('platform_mapping_id')->nullable();
            $table->string('sync_type', 50); // full, incremental, settings, performance, creative
            $table->string('direction', 20); // push, pull, bidirectional
            $table->string('status', 30); // running, completed, failed, partial
            $table->timestamp('started_at')->default(DB::raw('NOW()'));
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->jsonb('changes_detected')->nullable();
            $table->jsonb('changes_applied')->nullable();
            $table->integer('entities_synced')->default(0);
            $table->integer('entities_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->jsonb('error_details')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('orchestration_id')->references('orchestration_id')->on('cmis.campaign_orchestrations')->onDelete('cascade');

            $table->index(['org_id', 'orchestration_id']);
            $table->index('started_at');
            $table->index('status');
        });

        $this->enableRLS('cmis.orchestration_sync_logs');

        // ===== Cross-Platform Performance View =====
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_orchestration_performance AS
            SELECT
                o.orchestration_id,
                o.org_id,
                o.name,
                o.status,
                o.total_budget,
                COUNT(DISTINCT op.platform_mapping_id) as platform_count,
                COUNT(DISTINCT CASE WHEN op.status = 'active' THEN op.platform_mapping_id END) as active_platforms,
                SUM(op.spend) as total_spend,
                SUM(op.impressions) as total_impressions,
                SUM(op.clicks) as total_clicks,
                SUM(op.conversions) as total_conversions,
                SUM(op.revenue) as total_revenue,
                CASE
                    WHEN SUM(op.spend) > 0 THEN SUM(op.revenue) / SUM(op.spend)
                    ELSE 0
                END as roas,
                CASE
                    WHEN SUM(op.impressions) > 0 THEN (SUM(op.clicks)::decimal / SUM(op.impressions)) * 100
                    ELSE 0
                END as ctr,
                CASE
                    WHEN SUM(op.conversions) > 0 THEN SUM(op.spend) / SUM(op.conversions)
                    ELSE 0
                END as cpa
            FROM cmis.campaign_orchestrations o
            LEFT JOIN cmis.orchestration_platforms op ON o.orchestration_id = op.orchestration_id
            GROUP BY o.orchestration_id, o.org_id, o.name, o.status, o.total_budget
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS cmis.v_orchestration_performance");
        Schema::dropIfExists('cmis.orchestration_sync_logs');
        Schema::dropIfExists('cmis.orchestration_rules');
        Schema::dropIfExists('cmis.orchestration_workflows');
        Schema::dropIfExists('cmis.orchestration_platforms');
        Schema::dropIfExists('cmis.campaign_orchestrations');
        Schema::dropIfExists('cmis.campaign_templates');
    }
};
