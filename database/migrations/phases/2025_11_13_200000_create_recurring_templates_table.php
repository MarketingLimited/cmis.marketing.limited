<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create recurring_post_templates table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.recurring_post_templates (
                template_id UUID PRIMARY KEY,
                social_account_id UUID NOT NULL,
                template_name VARCHAR(255) NOT NULL,
                content_template TEXT NOT NULL,
                media_urls JSONB,
                hashtags JSONB,
                recurrence_pattern VARCHAR(20) NOT NULL, -- daily, weekly, monthly
                recurrence_interval INTEGER DEFAULT 1,
                days_of_week JSONB, -- For weekly: [0,1,2,3,4,5,6] (Sunday-Saturday)
                time_of_day TIME NOT NULL,
                timezone VARCHAR(50) DEFAULT 'UTC',
                start_date DATE NOT NULL,
                end_date DATE,
                is_active BOOLEAN DEFAULT true,
                created_by UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (social_account_id) REFERENCES cmis.social_accounts(social_account_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on social_account_id for template lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_recurring_templates_account
            ON cmis.recurring_post_templates(social_account_id)
        ");

        // Create index on is_active for filtering active templates
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_recurring_templates_active
            ON cmis.recurring_post_templates(is_active)
        ");

        // Add columns to social_posts for advanced scheduling
        DB::statement("
            ALTER TABLE cmis.social_posts
            ADD COLUMN IF NOT EXISTS recurring_template_id UUID,
            ADD COLUMN IF NOT EXISTS is_recycled BOOLEAN DEFAULT false,
            ADD COLUMN IF NOT EXISTS original_post_id UUID
        ");

        // Add foreign key for recurring_template_id
        DB::statement("
            ALTER TABLE cmis.social_posts
            ADD CONSTRAINT fk_social_posts_recurring_template
            FOREIGN KEY (recurring_template_id)
            REFERENCES cmis.recurring_post_templates(template_id)
            ON DELETE SET NULL
        ");

        // Add foreign key for original_post_id (self-referencing for recycled posts)
        DB::statement("
            ALTER TABLE cmis.social_posts
            ADD CONSTRAINT fk_social_posts_original_post
            FOREIGN KEY (original_post_id)
            REFERENCES cmis.social_posts(post_id)
            ON DELETE SET NULL
        ");

        // Create index on recurring_template_id
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_social_posts_recurring_template
            ON cmis.social_posts(recurring_template_id)
        ");

        // Create index on original_post_id for recycled posts
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_social_posts_original
            ON cmis.social_posts(original_post_id)
        ");

        // Add comments
        DB::statement("
            COMMENT ON TABLE cmis.recurring_post_templates IS 'Templates for recurring post schedules - Sprint 6.3'
        ");

        DB::statement("
            COMMENT ON COLUMN cmis.social_posts.recurring_template_id IS 'Link to recurring template if post was generated from one'
        ");

        DB::statement("
            COMMENT ON COLUMN cmis.social_posts.is_recycled IS 'Indicates if post is a recycled version of a previous post'
        ");

        DB::statement("
            COMMENT ON COLUMN cmis.social_posts.original_post_id IS 'Original post ID if this is a recycled post'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE cmis.social_posts DROP CONSTRAINT IF EXISTS fk_social_posts_original_post");
        DB::statement("ALTER TABLE cmis.social_posts DROP CONSTRAINT IF EXISTS fk_social_posts_recurring_template");
        DB::statement("ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS original_post_id");
        DB::statement("ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS is_recycled");
        DB::statement("ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS recurring_template_id");
        DB::statement("DROP TABLE IF EXISTS cmis.recurring_post_templates CASCADE");
    }
};
