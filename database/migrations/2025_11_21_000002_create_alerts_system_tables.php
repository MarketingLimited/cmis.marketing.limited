<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create alert_rules table
        Schema::create('cmis.alert_rules', function (Blueprint $table) {
            $table->uuid('rule_id')->primary();
            $table->uuid('org_id');
            $table->uuid('created_by'); // User who created the rule
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('entity_type', 50); // campaign, organization, ad, post, etc.
            $table->uuid('entity_id')->nullable(); // NULL = applies to all entities of type
            $table->string('metric', 100); // ctr, roi, spend, impressions, etc.
            $table->enum('condition', ['gt', 'gte', 'lt', 'lte', 'eq', 'ne', 'change_pct']); // Comparison operators
            $table->decimal('threshold', 20, 4); // Threshold value
            $table->integer('time_window_minutes')->default(60); // Evaluation window
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->jsonb('notification_channels'); // ['email', 'in_app', 'slack', 'webhook']
            $table->jsonb('notification_config'); // Channel-specific configs
            $table->integer('cooldown_minutes')->default(60); // Minimum time between alerts
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('org_id');
            $table->index('created_by');
            $table->index(['entity_type', 'entity_id']);
            $table->index(['is_active', 'last_triggered_at']);
            $table->index('severity');

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('user_id')
                ->on('cmis.users')
                ->onDelete('cascade');
        });

        // Enable RLS
        DB::statement('ALTER TABLE cmis.alert_rules ENABLE ROW LEVEL SECURITY');

        // Create RLS policy
        DB::statement("
            CREATE POLICY org_isolation ON cmis.alert_rules
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create alert_history table (triggered alerts)
        Schema::create('cmis.alert_history', function (Blueprint $table) {
            $table->uuid('alert_id')->primary();
            $table->uuid('rule_id');
            $table->uuid('org_id');
            $table->timestamp('triggered_at');
            $table->string('entity_type', 50);
            $table->uuid('entity_id')->nullable();
            $table->string('metric', 100);
            $table->decimal('actual_value', 20, 4); // Value that triggered alert
            $table->decimal('threshold_value', 20, 4); // Threshold that was exceeded
            $table->string('condition', 20);
            $table->enum('severity', ['critical', 'high', 'medium', 'low']);
            $table->text('message'); // Alert message
            $table->jsonb('metadata')->nullable(); // Additional context
            $table->enum('status', ['new', 'acknowledged', 'resolved', 'snoozed'])->default('new');
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('rule_id');
            $table->index('org_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('triggered_at');
            $table->index('status');
            $table->index('severity');
            $table->index('snoozed_until');

            // Foreign keys
            $table->foreign('rule_id')
                ->references('rule_id')
                ->on('cmis.alert_rules')
                ->onDelete('cascade');

            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');

            $table->foreign('acknowledged_by')
                ->references('user_id')
                ->on('cmis.users')
                ->onDelete('set null');
        });

        // Enable RLS for alert_history
        DB::statement('ALTER TABLE cmis.alert_history ENABLE ROW LEVEL SECURITY');

        // Create RLS policy for alert_history
        DB::statement("
            CREATE POLICY org_isolation ON cmis.alert_history
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create alert_notifications table (delivery tracking)
        Schema::create('cmis.alert_notifications', function (Blueprint $table) {
            $table->uuid('notification_id')->primary();
            $table->uuid('alert_id');
            $table->uuid('org_id');
            $table->string('channel', 50); // email, in_app, slack, webhook
            $table->text('recipient'); // Email address, Slack channel, webhook URL, or user_id
            $table->timestamp('sent_at');
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'read'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->jsonb('metadata')->nullable(); // Channel-specific delivery info
            $table->timestamps();

            // Indexes
            $table->index('alert_id');
            $table->index('org_id');
            $table->index(['channel', 'status']);
            $table->index('sent_at');

            // Foreign key
            $table->foreign('alert_id')
                ->references('alert_id')
                ->on('cmis.alert_history')
                ->onDelete('cascade');

            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');
        });

        // Enable RLS for alert_notifications
        DB::statement('ALTER TABLE cmis.alert_notifications ENABLE ROW LEVEL SECURITY');

        // Create RLS policy for alert_notifications
        DB::statement("
            CREATE POLICY org_isolation ON cmis.alert_notifications
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create alert_templates table (pre-built alert configurations)
        Schema::create('cmis.alert_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary();
            $table->uuid('created_by')->nullable(); // NULL = system template
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 50); // performance, budget, engagement, anomaly
            $table->string('entity_type', 50);
            $table->jsonb('default_config'); // Template configuration
            $table->boolean('is_public')->default(false);
            $table->boolean('is_system')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('entity_type');
            $table->index('is_public');
        });

        // Create escalation_policies table
        Schema::create('cmis.escalation_policies', function (Blueprint $table) {
            $table->uuid('policy_id')->primary();
            $table->uuid('org_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->jsonb('escalation_levels'); // Array of escalation steps
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('org_id');
            $table->index('is_active');

            // Foreign key
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');
        });

        // Enable RLS for escalation_policies
        DB::statement('ALTER TABLE cmis.escalation_policies ENABLE ROW LEVEL SECURITY');

        // Create RLS policy for escalation_policies
        DB::statement("
            CREATE POLICY org_isolation ON cmis.escalation_policies
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies first
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.escalation_policies');
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.alert_notifications');
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.alert_history');
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.alert_rules');

        // Drop tables
        Schema::dropIfExists('cmis.escalation_policies');
        Schema::dropIfExists('cmis.alert_templates');
        Schema::dropIfExists('cmis.alert_notifications');
        Schema::dropIfExists('cmis.alert_history');
        Schema::dropIfExists('cmis.alert_rules');
    }
};
