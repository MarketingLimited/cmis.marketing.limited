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
        // Skip if required tables don't exist yet (migration ordering)
        if (!Schema::hasTable('cmis.orgs') || !Schema::hasTable('cmis.users')) {
            return;
        }

        // Create comments table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.comments (
                comment_id UUID PRIMARY KEY,
                entity_type VARCHAR(50) NOT NULL, -- post, campaign, ad, content
                entity_id UUID NOT NULL,
                user_id UUID NOT NULL,
                parent_comment_id UUID, -- For threaded replies
                comment_text TEXT NOT NULL,
                mentions JSONB, -- Array of mentioned usernames
                is_edited BOOLEAN DEFAULT false,
                is_deleted BOOLEAN DEFAULT false,
                deleted_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (parent_comment_id) REFERENCES cmis.comments(comment_id) ON DELETE CASCADE
            )
        ");

        // Create index on entity for fast comment lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_comments_entity
            ON cmis.comments(entity_type, entity_id)
        ");

        // Create index on user_id for user's comments
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_comments_user
            ON cmis.comments(user_id)
        ");

        // Create index on parent_comment_id for threaded replies
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_comments_parent
            ON cmis.comments(parent_comment_id)
        ");

        // Create index on created_at for sorting
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_comments_created_at
            ON cmis.comments(created_at DESC)
        ");

        // Create comment_reactions table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.comment_reactions (
                reaction_id UUID PRIMARY KEY,
                comment_id UUID NOT NULL,
                user_id UUID NOT NULL,
                reaction_type VARCHAR(20) NOT NULL, -- like, love, celebrate, insightful, support
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (comment_id) REFERENCES cmis.comments(comment_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE,

                UNIQUE(comment_id, user_id)
            )
        ");

        // Create index on comment_id for reaction lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_comment_reactions_comment
            ON cmis.comment_reactions(comment_id)
        ");

        // Create comment_history table for edit tracking
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.comment_history (
                history_id UUID PRIMARY KEY,
                comment_id UUID NOT NULL,
                previous_text TEXT NOT NULL,
                edited_by UUID NOT NULL,
                edited_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (comment_id) REFERENCES cmis.comments(comment_id) ON DELETE CASCADE,
                FOREIGN KEY (edited_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on comment_id for history lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_comment_history_comment
            ON cmis.comment_history(comment_id)
        ");

        // Create collaboration_activity table for activity feed
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.collaboration_activity (
                activity_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                user_id UUID NOT NULL,
                activity_type VARCHAR(50) NOT NULL, -- comment_added, comment_updated, comment_deleted, mention, etc.
                entity_type VARCHAR(50) NOT NULL,
                entity_id UUID NOT NULL,
                metadata JSONB,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for activity feed
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_collaboration_activity_org
            ON cmis.collaboration_activity(org_id, created_at DESC)
        ");

        // Create index on user_id for user activity
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_collaboration_activity_user
            ON cmis.collaboration_activity(user_id)
        ");

        // Create index on entity for entity-specific activity
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_collaboration_activity_entity
            ON cmis.collaboration_activity(entity_type, entity_id)
        ");

        // Add comments to tables
        DB::statement("
            COMMENT ON TABLE cmis.comments IS 'Comments and collaboration on various entities - Sprint 5.3'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.comment_reactions IS 'Reactions (like, love, etc.) on comments - Sprint 5.3'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.comment_history IS 'Edit history for comments - Sprint 5.3'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.collaboration_activity IS 'Activity feed for collaboration features - Sprint 5.3'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.collaboration_activity CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.comment_history CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.comment_reactions CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.comments CASCADE");
    }
};
