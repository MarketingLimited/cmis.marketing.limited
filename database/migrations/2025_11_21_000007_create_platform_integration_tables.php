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
     * Run the migrations (Phase 18: Platform Integration & API Orchestration).
     */
    public function up(): void
    {
        // ===== Platform Connections Table =====
        Schema::create('cmis.platform_connections', function (Blueprint $table) {
            $table->uuid('connection_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->string('platform', 50); // meta, google, tiktok, linkedin, twitter, snapchat
            $table->string('account_id', 255); // Platform-specific account ID
            $table->string('account_name', 255)->nullable();
            $table->string('status', 30)->default('active'); // active, expired, revoked, error
            $table->text('access_token')->nullable(); // Encrypted
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->jsonb('scopes')->nullable(); // OAuth scopes granted
            $table->jsonb('account_metadata')->nullable(); // Platform-specific data
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->boolean('auto_sync')->default(true);
            $table->integer('sync_frequency_minutes')->default(15);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->unique(['org_id', 'platform', 'account_id']);
            $table->index(['platform', 'status']);
        });

        $this->enableRLS('cmis.platform_connections');

        // ===== Platform Sync Logs Table =====
        Schema::create('cmis.platform_sync_logs', function (Blueprint $table) {
            $table->uuid('sync_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('connection_id');
            $table->string('sync_type', 50); // full, incremental, entity_specific
            $table->string('entity_type', 50)->nullable(); // campaigns, ad_sets, ads, audiences
            $table->string('direction', 20); // import, export, bidirectional
            $table->string('status', 30); // running, completed, failed, partial
            $table->timestamp('started_at')->default(DB::raw('NOW()'));
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('entities_processed')->default(0);
            $table->integer('entities_created')->default(0);
            $table->integer('entities_updated')->default(0);
            $table->integer('entities_failed')->default(0);
            $table->jsonb('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('error_details')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('connection_id')->references('connection_id')->on('cmis.platform_connections')->onDelete('cascade');

            $table->index(['org_id', 'connection_id']);
            $table->index('started_at');
            $table->index('status');
        });

        $this->enableRLS('cmis.platform_sync_logs');

        // ===== Platform API Calls Table =====
        Schema::create('cmis.platform_api_calls', function (Blueprint $table) {
            $table->uuid('call_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('connection_id');
            $table->string('platform', 50);
            $table->string('endpoint', 500);
            $table->string('method', 10); // GET, POST, PUT, DELETE
            $table->string('action_type', 100)->nullable(); // create_campaign, update_budget, etc.
            $table->integer('http_status')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_data')->nullable();
            $table->integer('rate_limit_remaining')->nullable();
            $table->timestamp('rate_limit_reset_at')->nullable();
            $table->timestamp('called_at')->default(DB::raw('NOW()'));
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('connection_id')->references('connection_id')->on('cmis.platform_connections')->onDelete('cascade');

            $table->index(['org_id', 'platform']);
            $table->index(['connection_id', 'called_at']);
            $table->index('success');
        });

        $this->enableRLS('cmis.platform_api_calls');

        // ===== Platform Rate Limits Table =====
        Schema::create('cmis.platform_rate_limits', function (Blueprint $table) {
            $table->uuid('limit_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('connection_id');
            $table->string('platform', 50);
            $table->string('limit_type', 50); // hourly, daily, per_call, burst
            $table->integer('limit_max');
            $table->integer('limit_current')->default(0);
            $table->timestamp('window_start')->default(DB::raw('NOW()'));
            $table->timestamp('window_end');
            $table->timestamp('resets_at');
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('connection_id')->references('connection_id')->on('cmis.platform_connections')->onDelete('cascade');

            $table->index(['connection_id', 'limit_type']);
            $table->index('resets_at');
        });

        $this->enableRLS('cmis.platform_rate_limits');

        // ===== Platform Webhooks Table =====
        Schema::create('cmis.platform_webhooks', function (Blueprint $table) {
            $table->uuid('webhook_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('connection_id');
            $table->string('platform', 50);
            $table->string('event_type', 100); // campaign.update, ad.status_change, etc.
            $table->string('platform_webhook_id', 255)->nullable();
            $table->text('callback_url');
            $table->text('verify_token')->nullable();
            $table->string('status', 30)->default('active'); // active, paused, failed
            $table->jsonb('event_filters')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('connection_id')->references('connection_id')->on('cmis.platform_connections')->onDelete('cascade');

            $table->index(['org_id', 'connection_id']);
            $table->index(['platform', 'event_type']);
        });

        $this->enableRLS('cmis.platform_webhooks');

        // ===== Platform Entity Mappings Table =====
        Schema::create('cmis.platform_entity_mappings', function (Blueprint $table) {
            $table->uuid('mapping_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('connection_id');
            $table->string('platform', 50);
            $table->uuid('cmis_entity_id'); // ID in CMIS
            $table->string('cmis_entity_type', 50); // campaign, ad_set, ad, audience
            $table->string('platform_entity_id', 255); // ID on platform
            $table->string('platform_entity_type', 50)->nullable();
            $table->timestamp('first_synced_at')->default(DB::raw('NOW()'));
            $table->timestamp('last_synced_at')->default(DB::raw('NOW()'));
            $table->jsonb('sync_metadata')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('connection_id')->references('connection_id')->on('cmis.platform_connections')->onDelete('cascade');

            $table->unique(['connection_id', 'cmis_entity_id', 'cmis_entity_type']);
            $table->index(['platform', 'platform_entity_id']);
            $table->index(['cmis_entity_type', 'cmis_entity_id']);
        });

        $this->enableRLS('cmis.platform_entity_mappings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.platform_entity_mappings');
        Schema::dropIfExists('cmis.platform_webhooks');
        Schema::dropIfExists('cmis.platform_rate_limits');
        Schema::dropIfExists('cmis.platform_api_calls');
        Schema::dropIfExists('cmis.platform_sync_logs');
        Schema::dropIfExists('cmis.platform_connections');
    }
};
