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
     * Webhook Configurations allow organizations to receive forwarded
     * webhook events at their own endpoints. Similar to Meta's webhook
     * subscription model with callback URL and verify token.
     */
    public function up(): void
    {
        Schema::create('cmis.webhook_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id');

            // Webhook endpoint configuration
            $table->string('name', 100); // User-friendly name for this webhook
            $table->string('callback_url', 500); // URL to send webhook events to
            $table->string('verify_token', 100); // Token for endpoint verification
            $table->string('secret_key', 100); // Secret for signing payloads (HMAC)

            // Event subscription
            $table->json('subscribed_events')->nullable(); // Array of event types to forward
            $table->string('platform', 50)->nullable(); // Filter by platform (null = all)

            // Status and verification
            $table->boolean('is_active')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();

            // Delivery settings
            $table->integer('timeout_seconds')->default(30);
            $table->integer('max_retries')->default(3);
            $table->string('content_type', 50)->default('application/json');
            $table->json('custom_headers')->nullable(); // Custom headers to include

            // Statistics
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');

            // Indexes
            $table->index('org_id');
            $table->index('is_active');
            $table->index('platform');
            $table->index(['org_id', 'is_active']);
        });

        // Create webhook delivery log table
        Schema::create('cmis.webhook_delivery_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('webhook_config_id');
            $table->uuid('webhook_event_id')->nullable(); // Link to original event
            $table->uuid('org_id');

            // Request details
            $table->string('callback_url', 500);
            $table->string('event_type', 100);
            $table->json('payload');
            $table->json('request_headers')->nullable();

            // Response details
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->integer('response_time_ms')->nullable();

            // Delivery status
            $table->string('status', 20)->default('pending'); // pending, success, failed, retrying
            $table->integer('attempt_number')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('webhook_config_id')
                ->references('id')
                ->on('cmis.webhook_configurations')
                ->onDelete('cascade');

            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');

            // Indexes
            $table->index('webhook_config_id');
            $table->index('org_id');
            $table->index('status');
            $table->index(['webhook_config_id', 'status']);
            $table->index('created_at');
        });

        // Enable RLS on webhook_configurations
        DB::unprepared("
            ALTER TABLE cmis.webhook_configurations ENABLE ROW LEVEL SECURITY;
            ALTER TABLE cmis.webhook_configurations FORCE ROW LEVEL SECURITY;

            DROP POLICY IF EXISTS webhook_configurations_org_isolation ON cmis.webhook_configurations;
            CREATE POLICY webhook_configurations_org_isolation ON cmis.webhook_configurations
                USING (org_id::text = current_setting('app.current_org_id', true));
        ");

        // Enable RLS on webhook_delivery_logs
        DB::unprepared("
            ALTER TABLE cmis.webhook_delivery_logs ENABLE ROW LEVEL SECURITY;
            ALTER TABLE cmis.webhook_delivery_logs FORCE ROW LEVEL SECURITY;

            DROP POLICY IF EXISTS webhook_delivery_logs_org_isolation ON cmis.webhook_delivery_logs;
            CREATE POLICY webhook_delivery_logs_org_isolation ON cmis.webhook_delivery_logs
                USING (org_id::text = current_setting('app.current_org_id', true));
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable RLS first
        DB::unprepared("
            DROP POLICY IF EXISTS webhook_configurations_org_isolation ON cmis.webhook_configurations;
            DROP POLICY IF EXISTS webhook_delivery_logs_org_isolation ON cmis.webhook_delivery_logs;
        ");

        Schema::dropIfExists('cmis.webhook_delivery_logs');
        Schema::dropIfExists('cmis.webhook_configurations');
    }
};
