<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // حذف الجداول القديمة إن وجدت
        DB::statement("DROP TABLE IF EXISTS cmis.team_account_access CASCADE;");
        DB::statement("DROP TABLE IF EXISTS cmis.team_invitations CASCADE;");

        // إنشاء جدول الدعوات الخاصة بالفريق
        DB::statement("CREATE TABLE IF NOT EXISTS cmis.team_invitations (
            invitation_id UUID PRIMARY KEY,
            org_id UUID NOT NULL,
            invited_email VARCHAR(255) NOT NULL,
            role_id UUID,
            invited_by UUID,
            status VARCHAR(20) DEFAULT 'pending',
            sent_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
            accepted_at TIMESTAMPTZ,
            expires_at TIMESTAMPTZ,
            FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES cmis.roles(role_id) ON DELETE SET NULL,
            FOREIGN KEY (invited_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL
        );");

        // إنشاء جدول صلاحيات حسابات الفريق
        DB::statement("CREATE TABLE IF NOT EXISTS cmis.team_account_access (
            access_id UUID PRIMARY KEY,
            org_user_id UUID NOT NULL,
            social_account_id UUID NOT NULL,
            created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (org_user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (social_account_id) REFERENCES cmis.social_accounts(id) ON DELETE CASCADE,
            UNIQUE(org_user_id, social_account_id)
        );");

        // إضافة الأعمدة الناقصة إلى user_orgs
        DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN IF NOT EXISTS role_id UUID;");
        DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN IF NOT EXISTS joined_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;");

        // إنشاء الفهارس لتحسين الأداء
        DB::statement("CREATE INDEX IF NOT EXISTS idx_team_invitations_org_status ON cmis.team_invitations(org_id, status);");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_team_invitations_email ON cmis.team_invitations(invited_email);");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_team_account_access_user_social ON cmis.team_account_access(org_user_id, social_account_id);");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_user_orgs_role ON cmis.user_orgs(role_id);");
    }

    public function down(): void {
        DB::statement("DROP TABLE IF EXISTS cmis.team_account_access CASCADE;");
        DB::statement("DROP TABLE IF EXISTS cmis.team_invitations CASCADE;");
    }
};
