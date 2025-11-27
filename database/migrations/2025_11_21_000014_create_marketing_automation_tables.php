<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Workflow Templates - Reusable workflow definitions
        if (!Schema::hasTable('cmis.workflow_templates')) { Schema::create('cmis.workflow_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Template Information
            $table->string('template_name', 255)->nullable(false);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable(); // social, campaign, lead_nurture, engagement
            $table->jsonb('tags')->default('[]');

            // Configuration
            $table->jsonb('trigger_config')->nullable(false); // What starts this workflow
            $table->jsonb('workflow_definition')->nullable(false); // Complete workflow structure
            $table->integer('total_steps')->default(0);

            // Usage & Performance
            $table->integer('usage_count')->default(0);
            $table->integer('active_instances')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Status
            $table->string('status', 50)->default('draft'); // draft, active, archived
            $table->boolean('is_public')->default(false); // Shared templates
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index('category');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.workflow_templates ENABLE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.workflow_templates");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.workflow_templates
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");
        }

        // 2. Workflow Instances - Active workflow executions
        if (!Schema::hasTable('cmis.workflow_instances')) { Schema::create('cmis.workflow_instances', function (Blueprint $table) {
            $table->uuid('instance_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('template_id')->nullable();
            $table->uuid('triggered_by')->nullable(); // User or system

            // Instance Details
            $table->string('instance_name', 255)->nullable(false);
            $table->jsonb('workflow_definition')->nullable(false); // Snapshot of workflow at execution time
            $table->jsonb('context_data')->default('{}'); // Data passed through workflow

            // Trigger Information
            $table->string('trigger_type', 50)->nullable(false); // scheduled, event, manual, api
            $table->jsonb('trigger_data')->default('{}');
            $table->timestamp('triggered_at')->default(DB::raw('NOW()'));

            // Execution State
            $table->string('status', 50)->default('pending'); // pending, running, paused, completed, failed, cancelled
            $table->uuid('current_step_id')->nullable();
            $table->integer('steps_completed')->default(0);
            $table->integer('steps_total')->default(0);
            $table->integer('steps_failed')->default(0);

            // Performance
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_seconds')->nullable();

            // Results
            $table->jsonb('execution_results')->default('{}');
            $table->text('error_message')->nullable();
            $table->jsonb('error_details')->default('{}');

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['template_id', 'status']);
            $table->index('triggered_at');
            $table->index('current_step_id');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('template_id')->references('template_id')->on('cmis.workflow_templates')->onDelete('set null');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.workflow_instances ENABLE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.workflow_instances");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.workflow_instances
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");
        }

        // 3. Workflow Steps - Individual steps in workflow execution
        if (!Schema::hasTable('cmis.workflow_steps')) { Schema::create('cmis.workflow_steps', function (Blueprint $table) {
            $table->uuid('step_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('instance_id')->nullable(false);

            // Step Configuration
            $table->string('step_name', 255)->nullable(false);
            $table->string('step_type', 50)->nullable(false); // action, condition, delay, split, merge
            $table->integer('step_order')->nullable(false);
            $table->jsonb('step_config')->nullable(false);

            // Execution
            $table->string('status', 50)->default('pending'); // pending, running, completed, failed, skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();

            // Results
            $table->jsonb('input_data')->default('{}');
            $table->jsonb('output_data')->default('{}');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);

            // Flow Control
            $table->uuid('next_step_id')->nullable();
            $table->jsonb('branch_taken')->nullable(); // For conditional branches

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'instance_id']);
            $table->index(['instance_id', 'step_order']);
            $table->index(['instance_id', 'status']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('instance_id')->references('instance_id')->on('cmis.workflow_instances')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.workflow_steps ENABLE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.workflow_steps");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.workflow_steps
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");
        }

        // COMMENTED OUT: Table already created by migration 2025_11_21_000006 with RLS policies
        // 4. Automation Rules - Simple if-this-then-that rules
        // if (!Schema::hasTable('cmis.automation_rules')) { Schema::create('cmis.automation_rules', function (Blueprint $table) {
        //     $table->uuid('rule_id')->primary()->default(DB::raw('gen_random_uuid()'));
        //     $table->uuid('org_id')->nullable(false);
        //     $table->uuid('created_by')->nullable(false);
        //
        //     // Rule Information
        //     $table->string('rule_name', 255)->nullable(false);
        //     $table->text('description')->nullable();
        //     $table->string('category', 100)->nullable();
        //
        //     // Trigger
        //     $table->string('trigger_type', 50)->nullable(false); // event, schedule, condition
        //     $table->jsonb('trigger_config')->nullable(false);
        //
        //     // Conditions
        //     $table->jsonb('conditions')->default('[]'); // Array of conditions to check
        //     $table->string('condition_logic', 20)->default('all'); // all, any, custom
        //
        //     // Actions
        //     $table->jsonb('actions')->nullable(false); // Array of actions to execute
        //     $table->boolean('execute_sequentially')->default(true);
        //
        //     // Execution Settings
        //     $table->integer('max_executions_per_day')->nullable();
        //     $table->integer('delay_between_actions_seconds')->default(0);
        //     $table->boolean('stop_on_error')->default(false);
        //
        //     // Status & Statistics
        //     $table->string('status', 50)->default('active'); // active, paused, archived
        //     $table->integer('total_executions')->default(0);
        //     $table->integer('successful_executions')->default(0);
        //     $table->integer('failed_executions')->default(0);
        //     $table->timestamp('last_executed_at')->nullable();
        //     $table->timestamp('last_success_at')->nullable();
        //
        //     $table->timestamps();
        //
        //     // Indexes
        //     $table->index(['org_id', 'status']);
        //     $table->index('trigger_type');
        //
        //     // Foreign keys
        //     $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        // });
        //
        // // RLS Policy
        // DB::statement("ALTER TABLE cmis.automation_rules ENABLE ROW LEVEL SECURITY");
        // DB::statement("
        //     CREATE POLICY org_isolation ON cmis.automation_rules
        //     USING (org_id = current_setting('app.current_org_id', true)::uuid)
        // ");

        // COMMENTED OUT: Table already created by migration 2025_11_21_000006 with RLS policies
        // 5. Automation Executions - Execution history
        // if (!Schema::hasTable('cmis.automation_executions')) { Schema::create('cmis.automation_executions', function (Blueprint $table) {
        //     $table->uuid('execution_id')->primary()->default(DB::raw('gen_random_uuid()'));
        //     $table->uuid('org_id')->nullable(false);
        //     $table->uuid('rule_id')->nullable();
        //     $table->uuid('instance_id')->nullable();
        //
        //     // Execution Type
        //     $table->string('execution_type', 50)->nullable(false); // rule, workflow
        //     $table->string('trigger_source', 100)->nullable(); // What triggered this
        //
        //     // Execution Details
        //     $table->jsonb('trigger_data')->default('{}');
        //     $table->jsonb('context_data')->default('{}');
        //     $table->timestamp('started_at')->nullable(false);
        //     $table->timestamp('completed_at')->nullable();
        //     $table->integer('execution_time_ms')->nullable();
        //
        //     // Results
        //     $table->string('status', 50)->nullable(false); // success, failed, partial
        //     $table->integer('actions_executed')->default(0);
        //     $table->integer('actions_successful')->default(0);
        //     $table->integer('actions_failed')->default(0);
        //     $table->jsonb('execution_log')->default('[]');
        //     $table->text('error_message')->nullable();
        //
        //     $table->timestamps();
        //
        //     // Indexes
        //     $table->index(['org_id', 'status']);
        //     $table->index(['rule_id', 'started_at']);
        //     $table->index(['instance_id', 'started_at']);
        //     $table->index('started_at');
        //
        //     // Foreign keys
        //     $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        //     $table->foreign('rule_id')->references('rule_id')->on('cmis.automation_rules')->onDelete('cascade');
        //     $table->foreign('instance_id')->references('instance_id')->on('cmis.workflow_instances')->onDelete('cascade');
        // });
        //
        // // RLS Policy
        // DB::statement("ALTER TABLE cmis.automation_executions ENABLE ROW LEVEL SECURITY");
        // DB::statement("
        //     CREATE POLICY org_isolation ON cmis.automation_executions
        //     USING (org_id = current_setting('app.current_org_id', true)::uuid)
        // ");

        // 6. Scheduled Jobs - Time-based automation triggers
        if (!Schema::hasTable('cmis.scheduled_jobs')) { Schema::create('cmis.scheduled_jobs', function (Blueprint $table) {
            $table->uuid('job_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('workflow_template_id')->nullable();
            $table->uuid('automation_rule_id')->nullable();

            // Schedule Configuration
            $table->string('schedule_name', 255)->nullable(false);
            $table->string('schedule_type', 50)->nullable(false); // once, recurring, cron
            $table->string('cron_expression')->nullable();
            $table->jsonb('recurrence_config')->default('{}'); // daily, weekly, monthly settings

            // Timing
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Execution Settings
            $table->integer('max_executions')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('timeout_seconds')->default(300);
            $table->jsonb('execution_context')->default('{}');

            // Status
            $table->string('status', 50)->default('active'); // active, paused, completed, failed
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['next_run_at', 'status']);
            $table->index('workflow_template_id');
            $table->index('automation_rule_id');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('workflow_template_id')->references('template_id')->on('cmis.workflow_templates')->onDelete('cascade');
            // NOTE: automation_rule_id FK removed since automation_rules table is created in earlier migration
        });

        // RLS Policy
        DB::statement("ALTER TABLE cmis.scheduled_jobs ENABLE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.scheduled_jobs");
        DB::statement("
            CREATE POLICY org_isolation ON cmis.scheduled_jobs
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");
        }

        // Create Performance Views

        // View 1: Automation Performance Dashboard
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_automation_performance AS
            SELECT
                a.org_id,
                a.rule_id,
                a.name as rule_name,
                a.rule_type,
                a.status,
                a.execution_count as total_executions,
                a.success_count as successful_executions,
                a.failure_count as failed_executions,
                CASE
                    WHEN a.execution_count > 0
                    THEN (a.success_count::float / a.execution_count * 100)
                    ELSE 0
                END as success_rate,
                a.last_executed_at,
                COUNT(DISTINCT e.execution_id) as executions_last_30d
            FROM cmis.automation_rules a
            LEFT JOIN cmis.automation_executions e
                ON a.rule_id = e.rule_id
                AND e.executed_at >= NOW() - INTERVAL '30 days'
            GROUP BY a.org_id, a.rule_id, a.name, a.rule_type, a.status,
                     a.execution_count, a.success_count, a.failure_count,
                     a.last_executed_at;
        ");

        // View 2: Workflow Execution Timeline
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_workflow_timeline AS
            SELECT
                i.org_id,
                DATE(i.triggered_at) as execution_date,
                t.template_name,
                t.category,
                COUNT(i.instance_id) as total_executions,
                COUNT(CASE WHEN i.status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN i.status = 'failed' THEN 1 END) as failed,
                AVG(i.execution_time_seconds) as avg_execution_time,
                SUM(i.steps_completed) as total_steps_executed
            FROM cmis.workflow_instances i
            LEFT JOIN cmis.workflow_templates t ON i.template_id = t.template_id
            GROUP BY i.org_id, DATE(i.triggered_at), t.template_name, t.category
            ORDER BY execution_date DESC;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement('DROP VIEW IF EXISTS cmis.v_workflow_timeline');
        DB::statement('DROP VIEW IF EXISTS cmis.v_automation_performance');

        // Drop tables in reverse order
        Schema::dropIfExists('cmis.scheduled_jobs');
        Schema::dropIfExists('cmis.automation_executions');
        Schema::dropIfExists('cmis.automation_rules');
        Schema::dropIfExists('cmis.workflow_steps');
        Schema::dropIfExists('cmis.workflow_instances');
        Schema::dropIfExists('cmis.workflow_templates');
    }
};
