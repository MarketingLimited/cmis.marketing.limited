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
     * Run the migrations (Phase 22: Social Media Publishing & Scheduling).
     */
    public function up(): void
    {
        // ===== Scheduled Posts Table =====
        Schema::create('cmis.scheduled_posts', function (Blueprint $table) {
            $table->uuid('post_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('created_by');
            $table->uuid('content_library_id')->nullable(); // Link to content library
            $table->string('title', 255)->nullable();
            $table->text('content');
            $table->jsonb('media_urls')->nullable(); // Array of media URLs
            $table->jsonb('media_metadata')->nullable(); // Media details (dimensions, type, etc.)
            $table->string('post_type', 30); // text, image, video, link, carousel, story, reel
            $table->jsonb('platforms'); // Array: facebook, instagram, twitter, linkedin, tiktok, youtube
            $table->jsonb('platform_specific_content')->nullable(); // Platform-specific overrides
            $table->string('status', 30)->default('draft'); // draft, scheduled, publishing, published, failed, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->jsonb('targeting')->nullable(); // Audience targeting if applicable
            $table->jsonb('hashtags')->nullable(); // Array of hashtags
            $table->boolean('first_comment')->default(false); // Post hashtags as first comment
            $table->string('priority', 20)->default('normal'); // low, normal, high
            $table->jsonb('approval_workflow')->nullable(); // Approval chain
            $table->string('approval_status', 30)->nullable(); // pending, approved, rejected
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'status']);
            $table->index('scheduled_at');
            $table->index('published_at');
        });

        $this->enableRLS('cmis.scheduled_posts');

        // ===== Platform Posts Table =====
        Schema::create('cmis.platform_posts', function (Blueprint $table) {
            $table->uuid('platform_post_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('scheduled_post_id');
            $table->string('platform', 50); // facebook, instagram, twitter, linkedin, tiktok, youtube
            $table->string('platform_post_id_external', 255)->nullable(); // ID on platform
            $table->string('platform_url', 500)->nullable(); // URL to post on platform
            $table->string('status', 30)->default('pending'); // pending, publishing, published, failed
            $table->jsonb('platform_response')->nullable(); // Full response from platform API
            $table->timestamp('published_at')->nullable();
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('views')->default(0);
            $table->integer('engagement')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('scheduled_post_id')->references('post_id')->on('cmis.scheduled_posts')->onDelete('cascade');

            $table->unique(['scheduled_post_id', 'platform']);
            $table->index(['org_id', 'platform']);
            $table->index('published_at');
        });

        $this->enableRLS('cmis.platform_posts');

        // ===== Content Library Table =====
        Schema::create('cmis.content_library', function (Blueprint $table) {
            $table->uuid('library_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('created_by');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('content_type', 50); // text, image, video, template, hashtag_set
            $table->text('content')->nullable(); // Text content or template
            $table->jsonb('media_files')->nullable(); // Array of media files
            $table->jsonb('tags')->nullable(); // Array of tags for organization
            $table->string('category', 100)->nullable(); // Content category
            $table->jsonb('metadata')->nullable(); // Additional metadata
            $table->boolean('is_template')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            $table->index(['org_id', 'content_type']);
            $table->index('category');
        });

        $this->enableRLS('cmis.content_library');

        // ===== Publishing Queue Table =====
        Schema::create('cmis.publishing_queue', function (Blueprint $table) {
            $table->uuid('queue_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('scheduled_post_id');
            $table->string('platform', 50);
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('scheduled_for')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('execution_data')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('scheduled_post_id')->references('post_id')->on('cmis.scheduled_posts')->onDelete('cascade');

            $table->index(['status', 'scheduled_for']);
            $table->index(['org_id', 'status']);
        });

        $this->enableRLS('cmis.publishing_queue');

        // ===== Best Time Recommendations Table =====
        Schema::create('cmis.best_time_recommendations', function (Blueprint $table) {
            $table->uuid('recommendation_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->string('platform', 50);
            $table->string('day_of_week', 20); // monday, tuesday, etc.
            $table->integer('hour_of_day'); // 0-23
            $table->decimal('engagement_score', 5, 2); // 0-100
            $table->integer('sample_size')->default(0); // Number of posts analyzed
            $table->decimal('avg_engagement_rate', 5, 2)->default(0);
            $table->jsonb('performance_data')->nullable();
            $table->timestamp('calculated_at')->default(DB::raw('NOW()'));
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');

            $table->unique(['org_id', 'platform', 'day_of_week', 'hour_of_day']);
            $table->index(['org_id', 'platform']);
        });

        $this->enableRLS('cmis.best_time_recommendations');

        // ===== Publishing Performance View =====
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_publishing_performance AS
            SELECT
                sp.org_id,
                sp.post_id,
                sp.title,
                sp.post_type,
                sp.scheduled_at,
                sp.published_at,
                sp.status,
                COUNT(pp.platform_post_id) as platform_count,
                SUM(pp.likes) as total_likes,
                SUM(pp.comments) as total_comments,
                SUM(pp.shares) as total_shares,
                SUM(pp.views) as total_views,
                SUM(pp.engagement) as total_engagement,
                AVG(pp.engagement_rate) as avg_engagement_rate
            FROM cmis.scheduled_posts sp
            LEFT JOIN cmis.platform_posts pp ON sp.post_id = pp.scheduled_post_id
            WHERE sp.status = 'published'
            GROUP BY sp.org_id, sp.post_id, sp.title, sp.post_type, sp.scheduled_at, sp.published_at, sp.status
        ");

        // ===== Content Calendar View =====
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_content_calendar AS
            SELECT
                sp.post_id,
                sp.org_id,
                sp.title,
                sp.content,
                sp.post_type,
                sp.platforms,
                sp.status,
                sp.scheduled_at,
                sp.published_at,
                sp.created_by,
                u.name as creator_name,
                COUNT(pp.platform_post_id) as platform_count,
                COALESCE(SUM(pp.engagement), 0) as total_engagement
            FROM cmis.scheduled_posts sp
            LEFT JOIN cmis.users u ON sp.created_by = u.user_id
            LEFT JOIN cmis.platform_posts pp ON sp.post_id = pp.scheduled_post_id
            GROUP BY sp.post_id, sp.org_id, sp.title, sp.content, sp.post_type,
                     sp.platforms, sp.status, sp.scheduled_at, sp.published_at,
                     sp.created_by, u.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS cmis.v_content_calendar");
        DB::statement("DROP VIEW IF EXISTS cmis.v_publishing_performance");
        Schema::dropIfExists('cmis.best_time_recommendations');
        Schema::dropIfExists('cmis.publishing_queue');
        Schema::dropIfExists('cmis.content_library');
        Schema::dropIfExists('cmis.platform_posts');
        Schema::dropIfExists('cmis.scheduled_posts');
    }
};
