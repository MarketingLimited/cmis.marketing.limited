<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // API tokens for external integrations
        Schema::create('cmis.api_tokens', function (Blueprint $table) {
            $table->uuid('token_id')->primary();
            $table->uuid('org_id');
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('token_hash'); // Hashed token
            $table->text('token_prefix', 16); // First 16 chars for identification
            $table->jsonb('scopes'); // Permissions: ['analytics:read', 'campaigns:read']
            $table->jsonb('rate_limits')->nullable(); // Custom rate limits
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('org_id');
            $table->index('token_prefix');
            $table->index(['is_active', 'expires_at']);

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE cmis.api_tokens ENABLE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY org_isolation ON cmis.api_tokens USING (org_id = current_setting('app.current_org_id')::uuid)");

        // Data export configurations
        Schema::create('cmis.data_export_configs', function (Blueprint $table) {
            $table->uuid('config_id')->primary();
            $table->uuid('org_id');
            $table->uuid('created_by');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('export_type', 50); // analytics, campaigns, metrics, custom
            $table->enum('format', ['json', 'csv', 'xlsx', 'parquet'])->default('json');
            $table->enum('delivery_method', ['download', 'webhook', 'sftp', 's3'])->default('download');
            $table->jsonb('data_config'); // What data to export
            $table->jsonb('delivery_config'); // Where/how to deliver
            $table->jsonb('schedule')->nullable(); // Cron-like schedule
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_export_at')->nullable();
            $table->integer('export_count')->default(0);
            $table->timestamps();

            $table->index('org_id');
            $table->index('export_type');
            $table->index(['is_active', 'last_export_at']);

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE cmis.data_export_configs ENABLE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY org_isolation ON cmis.data_export_configs USING (org_id = current_setting('app.current_org_id')::uuid)");

        // Export execution logs
        Schema::create('cmis.data_export_logs', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->uuid('config_id')->nullable(); // NULL for manual exports
            $table->uuid('org_id');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('format', 20);
            $table->integer('records_count')->default(0);
            $table->bigInteger('file_size')->default(0); // bytes
            $table->text('file_path')->nullable();
            $table->text('file_url')->nullable();
            $table->text('delivery_url')->nullable(); // Webhook/S3 URL
            $table->text('error_message')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('config_id');
            $table->index('org_id');
            $table->index('status');
            $table->index('started_at');

            $table->foreign('config_id')->references('config_id')->on('cmis.data_export_configs')->onDelete('set null');
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE cmis.data_export_logs ENABLE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY org_isolation ON cmis.data_export_logs USING (org_id = current_setting('app.current_org_id')::uuid)");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.data_export_logs');
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.data_export_configs');
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.api_tokens');

        Schema::dropIfExists('cmis.data_export_logs');
        Schema::dropIfExists('cmis.data_export_configs');
        Schema::dropIfExists('cmis.api_tokens');
    }
};
