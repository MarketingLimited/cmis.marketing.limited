<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql')->table('cmis.scheduled_social_posts', function (Blueprint $table) {
            // Add integration_ids as JSONB array
            $table->jsonb('integration_ids')->nullable()->after('content');

            // Add media URLs as JSONB array
            $table->jsonb('media_urls')->nullable()->after('integration_ids');

            // Add publish results as JSONB object
            $table->jsonb('publish_results')->nullable()->after('scheduled_at');

            // Add published_at timestamp
            $table->timestamp('published_at')->nullable()->after('publish_results');

            // Add error_message for failed posts
            $table->text('error_message')->nullable()->after('published_at');

            // Add index for scheduled posts query optimization
            $table->index(['status', 'scheduled_at'], 'idx_scheduled_posts_status_time');

            // Add index for org_id (for multi-tenancy)
            $table->index('org_id', 'idx_scheduled_posts_org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->table('cmis.scheduled_social_posts', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_scheduled_posts_status_time');
            $table->dropIndex('idx_scheduled_posts_org_id');

            // Drop columns
            $table->dropColumn([
                'integration_ids',
                'media_urls',
                'publish_results',
                'published_at',
                'error_message'
            ]);
        });
    }
};
