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
        // Add post_type and is_queued columns to scheduled_social_posts
        Schema::table('cmis.scheduled_social_posts', function (Blueprint $table) {
            $table->string('post_type', 50)->default('feed')->after('platforms');
            $table->boolean('is_queued')->default(false)->after('status');
        });

        // Create integration_queue_settings table for Buffer-style scheduling
        Schema::create('cmis.integration_queue_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('public.gen_random_uuid()'));
            $table->uuid('org_id')->index();
            $table->uuid('integration_id')->index();
            $table->boolean('queue_enabled')->default(false);
            $table->jsonb('posting_times')->nullable()->comment('Array of times to post daily, e.g., ["09:00", "13:00", "18:00"]');
            $table->jsonb('days_enabled')->nullable()->comment('Days of week enabled, e.g., [1,2,3,4,5] for weekdays');
            $table->integer('posts_per_day')->default(3);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');

            $table->foreign('integration_id')
                ->references('integration_id')
                ->on('cmis.integrations')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['org_id', 'integration_id']);
        });

        // Enable RLS on integration_queue_settings
        DB::statement('ALTER TABLE cmis.integration_queue_settings ENABLE ROW LEVEL SECURITY');

        // Create RLS policy
        DB::statement("
            CREATE POLICY integration_queue_settings_org_isolation ON cmis.integration_queue_settings
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");

        // Add index for queue processing
        DB::statement('
            CREATE INDEX idx_scheduled_posts_queue ON cmis.scheduled_social_posts (org_id, is_queued, status, created_at)
            WHERE deleted_at IS NULL AND is_queued = true
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop index
        DB::statement('DROP INDEX IF EXISTS cmis.idx_scheduled_posts_queue');

        // Drop RLS policy
        DB::statement('DROP POLICY IF EXISTS integration_queue_settings_org_isolation ON cmis.integration_queue_settings');

        // Drop table
        Schema::dropIfExists('cmis.integration_queue_settings');

        // Remove columns from scheduled_social_posts
        Schema::table('cmis.scheduled_social_posts', function (Blueprint $table) {
            $table->dropColumn(['post_type', 'is_queued']);
        });
    }
};
