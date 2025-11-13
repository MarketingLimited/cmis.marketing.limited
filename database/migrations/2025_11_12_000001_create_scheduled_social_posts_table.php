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
        // Set schema to cmis
        DB::statement('SET search_path TO cmis');

        Schema::create('cmis.scheduled_social_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('user_id')->nullable();
            $table->uuid('campaign_id')->nullable();
            $table->jsonb('platforms'); // ['facebook', 'instagram', 'twitter', etc.]
            $table->text('content');
            $table->jsonb('media')->nullable(); // array of media URLs
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('status', 50)->default('draft')->index(); // draft, scheduled, publishing, published, failed
            $table->timestamp('published_at')->nullable();
            $table->jsonb('published_ids')->nullable(); // {platform: external_post_id}
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('campaign_id')->references('campaign_id')->on('cmis.campaigns')->onDelete('set null');

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['org_id', 'scheduled_at']);
            $table->index(['org_id', 'created_at']);
        });

        // Enable RLS on scheduled_social_posts
        DB::statement('ALTER TABLE cmis.scheduled_social_posts ENABLE ROW LEVEL SECURITY');

        // Create RLS policy for scheduled_social_posts
        DB::statement("
            CREATE POLICY scheduled_social_posts_org_isolation ON cmis.scheduled_social_posts
            USING (org_id = current_setting('app.current_org_id', true)::UUID)
        ");

        // Grant access to app role
        DB::statement('GRANT ALL ON cmis.scheduled_social_posts TO cmis_app');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET search_path TO cmis');
        Schema::dropIfExists('cmis.scheduled_social_posts');
    }
};
