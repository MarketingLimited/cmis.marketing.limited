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
        // Check if the table exists and only add columns if they don't exist
        $connection = Schema::connection('pgsql');

        if (!$connection->hasColumn('cmis.scheduled_social_posts', 'integration_ids')) {
            $connection->table('cmis.scheduled_social_posts', function (Blueprint $table) {
                $table->jsonb('integration_ids')->nullable();
            });
        }

        if (!$connection->hasColumn('cmis.scheduled_social_posts', 'media_urls')) {
            $connection->table('cmis.scheduled_social_posts', function (Blueprint $table) {
                $table->jsonb('media_urls')->nullable();
            });
        }

        if (!$connection->hasColumn('cmis.scheduled_social_posts', 'publish_results')) {
            $connection->table('cmis.scheduled_social_posts', function (Blueprint $table) {
                $table->jsonb('publish_results')->nullable();
            });
        }

        if (!$connection->hasColumn('cmis.scheduled_social_posts', 'published_at')) {
            $connection->table('cmis.scheduled_social_posts', function (Blueprint $table) {
                $table->timestamp('published_at')->nullable();
            });
        }

        if (!$connection->hasColumn('cmis.scheduled_social_posts', 'error_message')) {
            $connection->table('cmis.scheduled_social_posts', function (Blueprint $table) {
                $table->text('error_message')->nullable();
            });
        }

        // Add indexes if they don't exist
        $connection->table('cmis.scheduled_social_posts', function (Blueprint $table) use ($connection) {
            if (!$this->indexExists('idx_scheduled_posts_status_time')) {
                $table->index(['status', 'scheduled_at'], 'idx_scheduled_posts_status_time');
            }
            if (!$this->indexExists('idx_scheduled_posts_org_id')) {
                $table->index('org_id', 'idx_scheduled_posts_org_id');
            }
        });
    }

    private function indexExists($indexName): bool
    {
        $exists = DB::connection('pgsql')->selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM pg_indexes
                WHERE indexname = ?
            ) as exists
        ", [$indexName]);

        return $exists->exists ?? false;
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
