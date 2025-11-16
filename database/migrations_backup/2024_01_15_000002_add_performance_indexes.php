<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Add composite indexes for performance
     */
    public function up(): void
    {
        // Ad Campaigns - frequently queried by org + status
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ad_campaigns_org_status
            ON ad_campaigns (org_id, status, created_at DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ad_campaigns_integration
            ON ad_campaigns (integration_id, status)');

        // Ad Metrics - time-series queries
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ad_metrics_campaign_date
            ON ad_metrics (campaign_id, created_at DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ad_metrics_date
            ON ad_metrics (created_at DESC) WHERE deleted_at IS NULL');

        // Social Posts - scheduling queries
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_social_posts_org_status
            ON social_posts (org_id, status, scheduled_for)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_social_posts_scheduled
            ON social_posts (scheduled_for) WHERE status = \'scheduled\'');

        // Integrations - org lookup with active filter
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_integrations_org_active
            ON cmis.integrations (org_id, is_active, last_synced_at DESC)');

        // Campaigns - main campaign table
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_campaigns_org_type_status
            ON campaigns (org_id, type, status, created_at DESC)');

        // User Organizations - frequently joined
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_user_orgs_user
            ON user_orgs (user_id, is_active)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_user_orgs_org
            ON user_orgs (org_id, is_active)');
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_ad_campaigns_org_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_ad_campaigns_integration');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_ad_metrics_campaign_date');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_ad_metrics_date');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_social_posts_org_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_social_posts_scheduled');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_integrations_org_active');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_campaigns_org_type_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_user_orgs_user');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_user_orgs_org');
    }
};
