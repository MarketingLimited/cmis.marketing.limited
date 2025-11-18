<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: User Notifications
 *
 * Description: Create notifications table for user notifications system
 * with RLS policies for user-specific access control.
 */
return new class extends Migration
{
    /**
     * Disable transactions for direct SQL execution
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create notifications table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.notifications (
                notification_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL,
                org_id UUID,
                type VARCHAR(50) NOT NULL DEFAULT 'system',
                title VARCHAR(255),
                message TEXT NOT NULL,
                data JSONB DEFAULT '{}'::jsonb,
                read BOOLEAN DEFAULT false,
                read_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT chk_notification_type CHECK (
                    type IN ('campaign', 'analytics', 'integration', 'user',
                            'creative', 'system', 'workflow', 'report', 'welcome')
                )
            )
        ");

        // Create indexes
        DB::statement("CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON cmis.notifications (user_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_notifications_org_id ON cmis.notifications (org_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_notifications_read ON cmis.notifications (read)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON cmis.notifications (created_at DESC)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_notifications_user_unread ON cmis.notifications (user_id, read) WHERE read = false");

        // Foreign keys will be created by 2025_11_18_000004_create_user_foreign_keys_direct.php
        // to avoid transaction visibility issues

        // Create or replace get_current_user_id function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.get_current_user_id()
            RETURNS UUID AS $$
            BEGIN
              RETURN current_setting('app.current_user_id', true)::uuid;
            EXCEPTION
              WHEN OTHERS THEN
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql STABLE;
        ");

        // Create updated_at trigger function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.update_notifications_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Create trigger
        DB::statement("DROP TRIGGER IF EXISTS trigger_update_notifications_updated_at ON cmis.notifications");
        DB::statement("
            CREATE TRIGGER trigger_update_notifications_updated_at
            BEFORE UPDATE ON cmis.notifications
            FOR EACH ROW
            EXECUTE FUNCTION cmis.update_notifications_updated_at()
        ");

        // Enable RLS
        DB::statement("ALTER TABLE cmis.notifications ENABLE ROW LEVEL SECURITY");

        // Create RLS policies
        DB::statement("DROP POLICY IF EXISTS notifications_insert_policy ON cmis.notifications");
        DB::statement("
            CREATE POLICY notifications_insert_policy ON cmis.notifications
            FOR INSERT
            WITH CHECK (true)
        ");

        DB::statement("DROP POLICY IF EXISTS notifications_select_policy ON cmis.notifications");
        DB::statement("
            CREATE POLICY notifications_select_policy ON cmis.notifications
            FOR SELECT
            USING (user_id = cmis.get_current_user_id() OR cmis.get_current_user_id() IS NULL)
        ");

        DB::statement("DROP POLICY IF EXISTS notifications_update_policy ON cmis.notifications");
        DB::statement("
            CREATE POLICY notifications_update_policy ON cmis.notifications
            FOR UPDATE
            USING (user_id = cmis.get_current_user_id() OR cmis.get_current_user_id() IS NULL)
        ");

        echo "✓ Notifications table created with RLS policies\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.notifications CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS cmis.update_notifications_updated_at() CASCADE");
    }
};
