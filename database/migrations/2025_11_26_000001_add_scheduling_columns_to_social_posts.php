<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds scheduling and publishing workflow columns to social_posts table.
     */
    public function up(): void
    {
        // Add new columns for scheduling workflow
        DB::statement("
            ALTER TABLE cmis.social_posts
            ADD COLUMN IF NOT EXISTS platform text,
            ADD COLUMN IF NOT EXISTS account_id text,
            ADD COLUMN IF NOT EXISTS account_username text,
            ADD COLUMN IF NOT EXISTS content text,
            ADD COLUMN IF NOT EXISTS media jsonb DEFAULT '[]'::jsonb,
            ADD COLUMN IF NOT EXISTS post_type text DEFAULT 'text',
            ADD COLUMN IF NOT EXISTS targeting jsonb DEFAULT '{}'::jsonb,
            ADD COLUMN IF NOT EXISTS options jsonb DEFAULT '{}'::jsonb,
            ADD COLUMN IF NOT EXISTS status text DEFAULT 'draft',
            ADD COLUMN IF NOT EXISTS scheduled_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS published_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS failed_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS error_message text,
            ADD COLUMN IF NOT EXISTS requires_approval boolean DEFAULT false,
            ADD COLUMN IF NOT EXISTS created_by uuid,
            ADD COLUMN IF NOT EXISTS approved_by uuid,
            ADD COLUMN IF NOT EXISTS approved_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS approval_notes text,
            ADD COLUMN IF NOT EXISTS campaign_id uuid,
            ADD COLUMN IF NOT EXISTS tags jsonb DEFAULT '[]'::jsonb,
            ADD COLUMN IF NOT EXISTS impressions_cache integer DEFAULT 0,
            ADD COLUMN IF NOT EXISTS engagement_cache integer DEFAULT 0,
            ADD COLUMN IF NOT EXISTS metrics_updated_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS metadata jsonb DEFAULT '{}'::jsonb,
            ADD COLUMN IF NOT EXISTS retry_count integer DEFAULT 0
        ");

        // Make integration_id nullable (not all posts need an integration initially)
        DB::statement("ALTER TABLE cmis.social_posts ALTER COLUMN integration_id DROP NOT NULL");

        // Make post_external_id nullable (we get it after publishing)
        DB::statement("ALTER TABLE cmis.social_posts ALTER COLUMN post_external_id DROP NOT NULL");

        // Add index for status queries
        DB::statement("CREATE INDEX IF NOT EXISTS idx_social_posts_status ON cmis.social_posts(status)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_social_posts_scheduled_at ON cmis.social_posts(scheduled_at) WHERE status = 'scheduled'");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_social_posts_platform ON cmis.social_posts(platform)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement("DROP INDEX IF EXISTS cmis.idx_social_posts_status");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_social_posts_scheduled_at");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_social_posts_platform");

        // Drop added columns
        DB::statement("
            ALTER TABLE cmis.social_posts
            DROP COLUMN IF EXISTS platform,
            DROP COLUMN IF EXISTS account_id,
            DROP COLUMN IF EXISTS account_username,
            DROP COLUMN IF EXISTS content,
            DROP COLUMN IF EXISTS media,
            DROP COLUMN IF EXISTS post_type,
            DROP COLUMN IF EXISTS targeting,
            DROP COLUMN IF EXISTS options,
            DROP COLUMN IF EXISTS status,
            DROP COLUMN IF EXISTS scheduled_at,
            DROP COLUMN IF EXISTS published_at,
            DROP COLUMN IF EXISTS failed_at,
            DROP COLUMN IF EXISTS error_message,
            DROP COLUMN IF EXISTS requires_approval,
            DROP COLUMN IF EXISTS created_by,
            DROP COLUMN IF EXISTS approved_by,
            DROP COLUMN IF EXISTS approved_at,
            DROP COLUMN IF EXISTS approval_notes,
            DROP COLUMN IF EXISTS campaign_id,
            DROP COLUMN IF EXISTS tags,
            DROP COLUMN IF EXISTS impressions_cache,
            DROP COLUMN IF EXISTS engagement_cache,
            DROP COLUMN IF EXISTS metrics_updated_at,
            DROP COLUMN IF EXISTS metadata,
            DROP COLUMN IF EXISTS retry_count
        ");
    }
};
