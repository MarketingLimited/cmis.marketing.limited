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
     * Run the migrations (Phase 17: Campaign Automation & Orchestration).
     */
    public function up(): void
    {
        // ===== Automation Rules Table =====
        Schema::create('cmis.automation_rules', function (Blueprint $table) {
            $table->uuid('rule_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('rule_type', 50); // campaign_performance, budget_pacing, anomaly_response, recommendation, schedule
            $table->string('entity_type', 50)->nullable(); // campaign, ad_set, ad, account
            $table->uuid('entity_id')->nullable();
            $table->jsonb('conditions'); // Array of condition objects
            $table->string('condition_logic', 10)->default('and'); // and, or
            $table->jsonb('actions'); // Array of action objects
            $table->string('priority', 20)->default('medium'); // low, medium, high, critical
            $table->string('status', 30)->default('active'); // active, paused, archived
            $table->boolean('enabled')->default(true);
            $table->integer('max_executions_per_day')->nullable();
            $table->integer('cooldown_minutes')->default(60);
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'entity_type', 'entity_id']);
            $table->index(['status', 'enabled']);
            $table->index('rule_type');
        });

        $this->enableRLS('cmis.automation_rules');

        // ===== Automation Executions Table =====
        Schema::create('cmis.automation_executions', function (Blueprint $table) {
            $table->uuid('execution_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('rule_id');
            $table->uuid('entity_id')->nullable();
            $table->string('status', 30); // success, failure, partial, skipped
            $table->timestamp('executed_at')->default(DB::raw('NOW()'));
            $table->integer('duration_ms')->nullable();
            $table->jsonb('conditions_evaluated'); // Which conditions were checked
            $table->jsonb('actions_executed'); // Which actions were taken
            $table->jsonb('results'); // Results of each action
            $table->text('error_message')->nullable();
            $table->jsonb('context')->nullable(); // Additional context data
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('rule_id')->references('rule_id')->on('cmis.automation_rules')->onDelete('cascade');

            $table->index(['org_id', 'rule_id']);
            $table->index('executed_at');
            $table->index('status');
        });

        $this->enableRLS('cmis.automation_executions');

        // ===== Automation Workflows Table =====
        Schema::create('cmis.automation_workflows', function (Blueprint $table) {
            $table->uuid('workflow_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(); // NULL for global templates
            $table->uuid('created_by')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 50); // performance, budget, creative, scheduling
            $table->boolean('is_template')->default(false);
            $table->jsonb('rules'); // Array of rule configurations
            $table->jsonb('config')->nullable();
            $table->string('status', 30)->default('draft'); // draft, active, archived
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['org_id', 'category']);
            $table->index(['is_template', 'status']);
        });

        // RLS Policy
        $this->enableRLS('cmis.automation_workflows');
        $this->enableCustomRLS(
            'cmis.automation_workflows',
            "org_id IS NULL OR org_id = current_setting('app.current_org_id')::uuid"
        );

        // ===== Automation Schedules Table =====
        Schema::create('cmis.automation_schedules', function (Blueprint $table) {
            $table->uuid('schedule_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('rule_id');
            $table->string('frequency', 50); // once, hourly, daily, weekly, monthly, custom
            $table->string('cron_expression', 100)->nullable();
            $table->time('time_of_day')->nullable();
            $table->jsonb('days_of_week')->nullable(); // [0-6] for weekly schedules
            $table->integer('day_of_month')->nullable(); // 1-31 for monthly schedules
            $table->string('timezone', 50)->default('UTC');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('rule_id')->references('rule_id')->on('cmis.automation_rules')->onDelete('cascade');

            $table->index(['org_id', 'rule_id']);
            $table->index(['next_run_at', 'enabled']);
        });

        $this->enableRLS('cmis.automation_schedules');

        // ===== Automation Audit Log Table =====
        Schema::create('cmis.automation_audit_log', function (Blueprint $table) {
            $table->uuid('audit_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('rule_id')->nullable();
            $table->uuid('execution_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('action', 100); // rule_created, rule_updated, rule_executed, action_taken
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->jsonb('changes')->nullable(); // Before/after for updates
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->default(DB::raw('NOW()'));

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('rule_id')->references('rule_id')->on('cmis.automation_rules')->onDelete('set null');
            $table->foreign('execution_id')->references('execution_id')->on('cmis.automation_executions')->onDelete('set null');

            $table->index(['org_id', 'rule_id']);
            $table->index('created_at');
            $table->index('action');
        });

        $this->enableRLS('cmis.automation_audit_log');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.automation_audit_log');
        Schema::dropIfExists('cmis.automation_schedules');
        Schema::dropIfExists('cmis.automation_workflows');
        Schema::dropIfExists('cmis.automation_executions');
        Schema::dropIfExists('cmis.automation_rules');
    }
};
