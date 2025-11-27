<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: This migration was made into a no-op because all planned tables already exist:
     * - OAuth tokens: Use cmis.integrations or cmis.platform_connections
     * - Publishing queue: Use cmis.scheduled_social_posts
     * - Post analytics: Use cmis.social_post_metrics
     */
    public function up(): void
    {
        // All tables already exist - no action needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No tables created - nothing to drop
    }
};
