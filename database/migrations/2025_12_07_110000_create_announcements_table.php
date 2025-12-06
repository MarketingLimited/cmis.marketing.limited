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
        // Create announcements table
        Schema::create('cmis.announcements', function (Blueprint $table) {
            $table->uuid('announcement_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('title', 255);
            $table->text('content');
            $table->string('type', 50)->default('info'); // info, warning, critical, maintenance, feature
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->string('target_audience', 50)->default('all'); // all, admins, specific_plans, specific_orgs
            $table->json('target_ids')->nullable(); // Array of plan_ids or org_ids for specific targeting
            $table->boolean('is_dismissible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('action_text', 100)->nullable();
            $table->string('action_url', 500)->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('type');
            $table->index('priority');
            $table->index('is_active');
            $table->index(['starts_at', 'ends_at']);
            $table->index('target_audience');
        });

        // Create announcement_dismissals table (tracks which users dismissed announcements)
        Schema::create('cmis.announcement_dismissals', function (Blueprint $table) {
            $table->uuid('dismissal_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('announcement_id');
            $table->uuid('user_id');
            $table->timestamp('dismissed_at')->useCurrent();

            // Foreign keys
            $table->foreign('announcement_id')
                ->references('announcement_id')
                ->on('cmis.announcements')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('user_id')
                ->on('cmis.users')
                ->onDelete('cascade');

            // Unique constraint to prevent duplicate dismissals
            $table->unique(['announcement_id', 'user_id']);
        });

        // Create announcement_views table (tracks when users viewed announcements)
        Schema::create('cmis.announcement_views', function (Blueprint $table) {
            $table->uuid('view_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('announcement_id');
            $table->uuid('user_id');
            $table->uuid('org_id')->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            // Foreign keys
            $table->foreign('announcement_id')
                ->references('announcement_id')
                ->on('cmis.announcements')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('user_id')
                ->on('cmis.users')
                ->onDelete('cascade');

            // Index for analytics
            $table->index(['announcement_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.announcement_views');
        Schema::dropIfExists('cmis.announcement_dismissals');
        Schema::dropIfExists('cmis.announcements');
    }
};
