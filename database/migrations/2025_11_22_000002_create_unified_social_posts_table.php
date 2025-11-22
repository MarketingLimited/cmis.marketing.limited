<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

/**
 * Create Unified Social Posts Table
 *
 * Consolidates 5 social post tables into a single unified table
 * that handles both published and scheduled posts.
 *
 * Tables being consolidated:
 * 1. social_posts (18 columns) - Full featured
 * 2. social_posts_v2 (9 columns) - Band-aid fix
 * 3. posts (9 columns) - Generic version
 * 4. scheduled_social_posts (19 columns) - Scheduling version
 * 5. scheduled_social_posts_v2 (8 columns) - Simplified band-aid
 *
 * Benefits:
 * - 80% reduction in post tables (5 → 1)
 * - Single source of truth for all social posts
 * - Unified workflow for draft → scheduled → published
 * - Metrics tracked via unified metrics table
 * - Support for all post types (text, image, video, carousel, etc.)
 *
 * @package Database\Migrations
 */
return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cmis.social_posts', function (Blueprint $table) {
            // ==================================================================
            // Primary & Organization
            // ==================================================================
            $table->uuid('id')->primary();
            $table->uuid('org_id')->index();

            // ==================================================================
            // Integration & Platform
            // ==================================================================
            $table->uuid('integration_id')->nullable()->index();
            $table->string('platform', 50)->index(); // meta, instagram, linkedin, twitter, tiktok, etc.
            $table->string('account_id')->nullable(); // Platform account ID
            $table->string('account_username')->nullable(); // @username

            // ==================================================================
            // External References
            // ==================================================================
            $table->string('post_external_id')->nullable()->index(); // Platform's post ID
            $table->text('permalink')->nullable(); // Direct link to post on platform

            // ==================================================================
            // Content
            // ==================================================================
            $table->text('content'); // Post text/caption
            $table->jsonb('media')->nullable(); // Array of media objects [{type, url, thumbnail, width, height, duration}]
            $table->string('post_type', 50)->default('text'); // text, image, video, carousel, story, reel, etc.

            // ==================================================================
            // Targeting & Options
            // ==================================================================
            $table->jsonb('targeting')->nullable(); // Audience targeting options
            $table->jsonb('options')->nullable(); // Platform-specific options (hashtags, mentions, location, etc.)

            // ==================================================================
            // Publishing Workflow
            // ==================================================================
            $table->string('status', 50)->default('draft')->index();
            // draft, pending_approval, approved, scheduled, publishing, published, failed, cancelled

            $table->timestamp('scheduled_at')->nullable()->index(); // When to publish
            $table->timestamp('published_at')->nullable()->index(); // When actually published
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable(); // If publishing failed

            // ==================================================================
            // Approval Workflow
            // ==================================================================
            $table->boolean('requires_approval')->default(false);
            $table->uuid('created_by')->nullable(); // User who created
            $table->uuid('approved_by')->nullable(); // User who approved
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // ==================================================================
            // Campaign & Tags
            // ==================================================================
            $table->uuid('campaign_id')->nullable()->index();
            $table->jsonb('tags')->nullable(); // Array of tags for organization

            // ==================================================================
            // Performance Tracking (denormalized for quick access)
            // ==================================================================
            // Note: Full metrics are in unified metrics table
            // These are cached/denormalized for dashboard display
            $table->bigInteger('impressions_cache')->default(0);
            $table->bigInteger('engagement_cache')->default(0); // likes + comments + shares
            $table->timestamp('metrics_updated_at')->nullable();

            // ==================================================================
            // Metadata
            // ==================================================================
            $table->jsonb('metadata')->nullable(); // Additional platform-specific data
            $table->integer('retry_count')->default(0); // For failed posts

            // ==================================================================
            // Audit
            // ==================================================================
            $table->timestamps();
            $table->softDeletes();

            // ==================================================================
            // Foreign Keys
            // ==================================================================
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('integration_id')->references('integration_id')->on('cmis.integrations')->onDelete('set null');
            $table->foreign('campaign_id')->references('id')->on('cmis.campaigns')->onDelete('set null');
        });

        // ==================================================================
        // Indexes for Performance
        // ==================================================================
        DB::statement("CREATE INDEX idx_social_posts_status_scheduled ON cmis.social_posts (status, scheduled_at) WHERE status = 'scheduled';");
        DB::statement("CREATE INDEX idx_social_posts_platform_status ON cmis.social_posts (platform, status, created_at DESC);");
        DB::statement("CREATE INDEX idx_social_posts_campaign ON cmis.social_posts (campaign_id, published_at DESC) WHERE campaign_id IS NOT NULL;");
        DB::statement("CREATE INDEX idx_social_posts_org_published ON cmis.social_posts (org_id, published_at DESC) WHERE published_at IS NOT NULL;");

        // JSONB indexes
        DB::statement("CREATE INDEX idx_social_posts_media_gin ON cmis.social_posts USING GIN (media);");
        DB::statement("CREATE INDEX idx_social_posts_options_gin ON cmis.social_posts USING GIN (options);");
        DB::statement("CREATE INDEX idx_social_posts_tags_gin ON cmis.social_posts USING GIN (tags);");

        // ==================================================================
        // Enable Row-Level Security
        // ==================================================================
        $this->enableRLS('cmis.social_posts');

        // ==================================================================
        // Create Helper Views
        // ==================================================================

        // View for published posts only
        DB::statement("
            CREATE VIEW cmis.published_posts AS
            SELECT *
            FROM cmis.social_posts
            WHERE status = 'published'
            AND published_at IS NOT NULL
            ORDER BY published_at DESC;
        ");

        // View for scheduled posts
        DB::statement("
            CREATE VIEW cmis.scheduled_posts AS
            SELECT *
            FROM cmis.social_posts
            WHERE status = 'scheduled'
            AND scheduled_at IS NOT NULL
            AND scheduled_at > NOW()
            ORDER BY scheduled_at ASC;
        ");

        // View for pending approval
        DB::statement("
            CREATE VIEW cmis.posts_pending_approval AS
            SELECT *
            FROM cmis.social_posts
            WHERE status = 'pending_approval'
            AND requires_approval = true
            ORDER BY created_at ASC;
        ");

        // View for failed posts
        DB::statement("
            CREATE VIEW cmis.failed_posts AS
            SELECT *
            FROM cmis.social_posts
            WHERE status = 'failed'
            ORDER BY failed_at DESC;
        ");

        // ==================================================================
        // Create Post History Table (Audit Trail)
        // ==================================================================
        Schema::create('cmis.social_post_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id')->index();
            $table->uuid('org_id');
            $table->string('action', 50); // created, updated, scheduled, published, failed, approved, cancelled
            $table->uuid('user_id')->nullable(); // Who performed the action
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->jsonb('changes')->nullable(); // What changed
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->default(DB::raw('NOW()'));

            $table->foreign('post_id')->references('id')->on('cmis.social_posts')->onDelete('cascade');
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        DB::statement("CREATE INDEX idx_post_history_post ON cmis.social_post_history (post_id, created_at DESC);");

        $this->enableRLS('cmis.social_post_history');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement("DROP VIEW IF EXISTS cmis.failed_posts CASCADE;");
        DB::statement("DROP VIEW IF EXISTS cmis.posts_pending_approval CASCADE;");
        DB::statement("DROP VIEW IF EXISTS cmis.scheduled_posts CASCADE;");
        DB::statement("DROP VIEW IF EXISTS cmis.published_posts CASCADE;");

        // Disable RLS
        $this->disableRLS('cmis.social_post_history');
        $this->disableRLS('cmis.social_posts');

        // Drop tables
        Schema::dropIfExists('cmis.social_post_history');
        Schema::dropIfExists('cmis.social_posts');
    }
};
