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
        // Create scheduled_reports table
        Schema::create('cmis.scheduled_reports', function (Blueprint $table) {
            $table->uuid('schedule_id')->primary();
            $table->uuid('org_id');
            $table->uuid('user_id'); // Report owner/creator
            $table->string('name', 255); // Schedule name
            $table->string('report_type', 50); // campaign, organization, comparison, attribution
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly']); // Schedule frequency
            $table->jsonb('config'); // Report configuration (format, recipients, filters)
            $table->jsonb('recipients'); // Email addresses to send to
            $table->enum('format', ['pdf', 'xlsx', 'csv', 'json'])->default('pdf');
            $table->string('timezone', 50)->default('UTC');
            $table->time('delivery_time')->default('09:00:00'); // Time to send report
            $table->integer('day_of_week')->nullable(); // 1-7 for weekly (1=Monday)
            $table->integer('day_of_month')->nullable(); // 1-31 for monthly
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('org_id');
            $table->index('user_id');
            $table->index(['is_active', 'next_run_at']);
            $table->index('frequency');

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('cmis.users')
                ->onDelete('cascade');
        });

        // Enable RLS
        DB::statement('ALTER TABLE cmis.scheduled_reports ENABLE ROW LEVEL SECURITY');

        // Create RLS policy
        DB::statement("
            CREATE POLICY org_isolation ON cmis.scheduled_reports
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create report execution logs table
        Schema::create('cmis.report_execution_logs', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->uuid('schedule_id');
            $table->uuid('org_id');
            $table->timestamp('executed_at');
            $table->enum('status', ['success', 'failed', 'partial']); // Execution status
            $table->text('file_path')->nullable(); // Generated report path
            $table->text('file_url')->nullable(); // Public URL
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->integer('recipients_count')->default(0); // Number of recipients
            $table->integer('emails_sent')->default(0); // Successfully sent emails
            $table->integer('emails_failed')->default(0); // Failed emails
            $table->text('error_message')->nullable(); // Error details if failed
            $table->jsonb('metadata')->nullable(); // Additional execution metadata
            $table->integer('execution_time_ms')->nullable(); // Time taken to generate
            $table->timestamps();

            // Indexes
            $table->index('schedule_id');
            $table->index('org_id');
            $table->index('status');
            $table->index('executed_at');

            // Foreign key
            $table->foreign('schedule_id')
                ->references('schedule_id')
                ->on('cmis.scheduled_reports')
                ->onDelete('cascade');

            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');
        });

        // Enable RLS for logs
        DB::statement('ALTER TABLE cmis.report_execution_logs ENABLE ROW LEVEL SECURITY');

        // Create RLS policy for logs
        DB::statement("
            CREATE POLICY org_isolation ON cmis.report_execution_logs
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create report templates table (pre-built configurations)
        Schema::create('cmis.report_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary();
            $table->uuid('created_by')->nullable(); // User who created (NULL = system template)
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('report_type', 50);
            $table->jsonb('default_config'); // Template configuration
            $table->enum('category', ['marketing', 'sales', 'executive', 'custom'])->default('custom');
            $table->boolean('is_public')->default(false); // Available to all orgs
            $table->boolean('is_system')->default(false); // System template (cannot be deleted)
            $table->integer('usage_count')->default(0); // How many times used
            $table->timestamps();

            // Indexes
            $table->index('created_by');
            $table->index('is_public');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies first
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.report_execution_logs');
        DB::statement('DROP POLICY IF EXISTS org_isolation ON cmis.scheduled_reports');

        // Drop tables
        Schema::dropIfExists('cmis.report_templates');
        Schema::dropIfExists('cmis.report_execution_logs');
        Schema::dropIfExists('cmis.scheduled_reports');
    }
};
