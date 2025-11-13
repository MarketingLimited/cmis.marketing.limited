<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS cmis.notifications (
                notification_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL,
                org_id UUID,
                type VARCHAR(50) NOT NULL DEFAULT \'system\',
                title VARCHAR(255),
                message TEXT NOT NULL,
                data JSONB DEFAULT \'{}\'::jsonb,
                read BOOLEAN DEFAULT false,
                read_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
                    REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_notifications_org FOREIGN KEY (org_id)
                    REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                CONSTRAINT chk_notification_type CHECK (type IN (
                    \'campaign\', \'analytics\', \'integration\', \'user\',
                    \'creative\', \'system\', \'workflow\', \'report\'
                ))
            );
        ');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON cmis.notifications(user_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_org_id ON cmis.notifications(org_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_read ON cmis.notifications(read);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON cmis.notifications(created_at DESC);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_user_unread ON cmis.notifications(user_id, read) WHERE read = false;');

        DB::statement('ALTER TABLE cmis.notifications ENABLE ROW LEVEL SECURITY;');

        DB::statement('DROP POLICY IF EXISTS notifications_select_policy ON cmis.notifications;');
        DB::statement('CREATE POLICY notifications_select_policy ON cmis.notifications FOR SELECT USING (user_id = cmis.get_current_user_id());');

        DB::statement('DROP POLICY IF EXISTS notifications_update_policy ON cmis.notifications;');
        DB::statement('CREATE POLICY notifications_update_policy ON cmis.notifications FOR UPDATE USING (user_id = cmis.get_current_user_id());');

        DB::statement('DROP POLICY IF EXISTS notifications_insert_policy ON cmis.notifications;');
        DB::statement('CREATE POLICY notifications_insert_policy ON cmis.notifications FOR INSERT WITH CHECK (true);');

        DB::statement('
            CREATE OR REPLACE FUNCTION cmis.update_notifications_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        DB::statement('DROP TRIGGER IF EXISTS trigger_update_notifications_updated_at ON cmis.notifications');

        DB::statement('
            CREATE TRIGGER trigger_update_notifications_updated_at
            BEFORE UPDATE ON cmis.notifications
            FOR EACH ROW
            EXECUTE FUNCTION cmis.update_notifications_updated_at()
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION cmis.mark_notification_as_read(p_notification_id UUID)
            RETURNS BOOLEAN AS $$
            BEGIN
                UPDATE cmis.notifications
                SET read = true, read_at = CURRENT_TIMESTAMP
                WHERE notification_id = p_notification_id
                  AND user_id = cmis.get_current_user_id()
                  AND read = false;

                RETURN FOUND;
            END;
            $$ LANGUAGE plpgsql SECURITY DEFINER;
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION cmis.get_unread_notifications_count(p_user_id UUID DEFAULT NULL)
            RETURNS INTEGER AS $$
            DECLARE
                v_user_id UUID;
            BEGIN
                v_user_id := COALESCE(p_user_id, cmis.get_current_user_id());

                RETURN (
                    SELECT COUNT(*)::INTEGER
                    FROM cmis.notifications
                    WHERE user_id = v_user_id
                      AND read = false
                );
            END;
            $$ LANGUAGE plpgsql SECURITY DEFINER;
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION cmis.create_notification(
                p_user_id UUID,
                p_org_id UUID DEFAULT NULL,
                p_type VARCHAR DEFAULT \'system\',
                p_title VARCHAR DEFAULT NULL,
                p_message TEXT DEFAULT \'\',
                p_data JSONB DEFAULT \'{}\'::jsonb
            )
            RETURNS UUID AS $$
            DECLARE
                v_notification_id UUID;
            BEGIN
                INSERT INTO cmis.notifications (user_id, org_id, type, title, message, data)
                VALUES (p_user_id, p_org_id, p_type, p_title, p_message, p_data)
                RETURNING notification_id INTO v_notification_id;

                RETURN v_notification_id;
            END;
            $$ LANGUAGE plpgsql SECURITY DEFINER;
        ');

        DB::statement('COMMENT ON TABLE cmis.notifications IS \'نظام الإشعارات للمستخدمين في النظام\';');
        DB::statement('COMMENT ON COLUMN cmis.notifications.type IS \'نوع الإشعار: campaign, analytics, integration, user, creative, system, workflow, report\';');
        DB::statement('COMMENT ON COLUMN cmis.notifications.data IS \'بيانات إضافية عن الإشعار بصيغة JSON\';');
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS cmis.create_notification(UUID, UUID, VARCHAR, VARCHAR, TEXT, JSONB);');
        DB::statement('DROP FUNCTION IF EXISTS cmis.get_unread_notifications_count(UUID);');
        DB::statement('DROP FUNCTION IF EXISTS cmis.mark_notification_as_read(UUID);');
        DB::statement('DROP FUNCTION IF EXISTS cmis.update_notifications_updated_at() CASCADE;');
        DB::statement('DROP TABLE IF EXISTS cmis.notifications CASCADE;');
    }
};