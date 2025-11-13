<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Post Approvals (Sprint 2.4)
 * Enables approval workflow: Creator → Reviewer → Publisher
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql')->create('cmis.post_approvals', function (Blueprint $table) {
            $table->uuid('approval_id')->primary();
            $table->uuid('post_id')->index();
            $table->uuid('requested_by');
            $table->uuid('assigned_to')->nullable()->index();

            // Status: pending, approved, rejected
            $table->string('status', 20)->default('pending')->index();

            // Feedback
            $table->text('comments')->nullable();

            // Timestamps
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign keys
            $table->foreign('post_id')->references('id')->on('cmis.social_posts')->onDelete('cascade');
            $table->foreign('requested_by')->references('user_id')->on('cmis.users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('user_id')->on('cmis.users')->onDelete('set null');

            // Indexes
            $table->index(['status', 'assigned_to'], 'post_approvals_status_assignee_idx');
            $table->index(['post_id', 'status'], 'post_approvals_post_status_idx');
        });

        DB::statement("COMMENT ON TABLE cmis.post_approvals IS 'Post approval workflow system'");
        DB::statement("COMMENT ON COLUMN cmis.post_approvals.status IS 'pending, approved, or rejected'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('cmis.post_approvals');
    }
};
