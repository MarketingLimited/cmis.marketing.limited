<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // OAuth tokens table for all platforms
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_platform.oauth_tokens (
                token_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                platform TEXT NOT NULL,
                account_id TEXT,
                account_name TEXT,
                access_token TEXT NOT NULL,
                refresh_token TEXT,
                token_type TEXT DEFAULT 'Bearer',
                expires_at TIMESTAMPTZ,
                scope TEXT[],
                metadata JSONB DEFAULT '{}'::jsonb,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_oauth_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE
            );
        ");

        // Enable RLS on oauth_tokens
        $this->enableRLS('cmis_platform.oauth_tokens');

        // Create index on org_id and platform
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_oauth_tokens_org_platform
            ON cmis_platform.oauth_tokens(org_id, platform);
        ");

        // Publishing queue table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.publishing_queue (
                queue_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                post_id UUID NOT NULL,
                platform TEXT NOT NULL,
                platform_account_id TEXT,
                scheduled_for TIMESTAMPTZ NOT NULL,
                status TEXT DEFAULT 'pending',
                priority INT DEFAULT 5,
                attempts INT DEFAULT 0,
                max_attempts INT DEFAULT 3,
                last_attempt_at TIMESTAMPTZ,
                error_message TEXT,
                published_at TIMESTAMPTZ,
                external_post_id TEXT,
                external_url TEXT,
                metadata JSONB DEFAULT '{}'::jsonb,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_queue_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                CONSTRAINT fk_queue_post FOREIGN KEY (post_id) REFERENCES cmis.social_posts(post_id) ON DELETE CASCADE,
                CONSTRAINT chk_status CHECK (status IN ('pending', 'processing', 'published', 'failed', 'cancelled'))
            );
        ");

        // Enable RLS on publishing_queue
        $this->enableRLS('cmis.publishing_queue');

        // Create indexes on publishing_queue
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_queue_org_status
            ON cmis.publishing_queue(org_id, status);
        ");
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_queue_scheduled
            ON cmis.publishing_queue(scheduled_for)
            WHERE status = 'pending';
        ");
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_queue_post
            ON cmis.publishing_queue(post_id);
        ");

        // Post analytics table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.post_analytics (
                analytics_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                post_id UUID NOT NULL,
                platform TEXT NOT NULL,
                external_post_id TEXT NOT NULL,
                synced_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

                -- Common metrics across platforms
                impressions BIGINT DEFAULT 0,
                reach BIGINT DEFAULT 0,
                engagement BIGINT DEFAULT 0,
                likes BIGINT DEFAULT 0,
                comments BIGINT DEFAULT 0,
                shares BIGINT DEFAULT 0,
                saves BIGINT DEFAULT 0,
                clicks BIGINT DEFAULT 0,
                video_views BIGINT DEFAULT 0,

                -- Platform-specific metrics in JSONB
                metrics JSONB DEFAULT '{}'::jsonb,

                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_analytics_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                CONSTRAINT fk_analytics_post FOREIGN KEY (post_id) REFERENCES cmis.social_posts(post_id) ON DELETE CASCADE,
                CONSTRAINT uq_analytics_post_platform_synced UNIQUE(post_id, platform, synced_at)
            );
        ");

        // Enable RLS on post_analytics
        $this->enableRLS('cmis.post_analytics');

        // Create indexes on post_analytics
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_analytics_org
            ON cmis.post_analytics(org_id);
        ");
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_analytics_post
            ON cmis.post_analytics(post_id);
        ");
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_analytics_synced
            ON cmis.post_analytics(synced_at DESC);
        ");

        // Platform configurations table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_platform.platform_configs (
                config_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                platform TEXT NOT NULL,
                enabled BOOLEAN DEFAULT true,
                settings JSONB DEFAULT '{}'::jsonb,
                rate_limit_per_hour INT DEFAULT 100,
                rate_limit_per_day INT DEFAULT 1000,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_config_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                CONSTRAINT uq_config_org_platform UNIQUE(org_id, platform)
            );
        ");

        // Enable RLS on platform_configs
        $this->enableRLS('cmis_platform.platform_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable RLS and drop tables
        $this->disableRLS('cmis_platform.platform_configs');
        DB::statement('DROP TABLE IF EXISTS cmis_platform.platform_configs CASCADE;');

        $this->disableRLS('cmis.post_analytics');
        DB::statement('DROP TABLE IF EXISTS cmis.post_analytics CASCADE;');

        $this->disableRLS('cmis.publishing_queue');
        DB::statement('DROP TABLE IF EXISTS cmis.publishing_queue CASCADE;');

        $this->disableRLS('cmis_platform.oauth_tokens');
        DB::statement('DROP TABLE IF EXISTS cmis_platform.oauth_tokens CASCADE;');
    }
};
