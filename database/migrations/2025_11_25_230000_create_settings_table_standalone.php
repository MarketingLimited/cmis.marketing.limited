<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists using direct SQL (Schema::hasTable doesn't work with cross-schema names)
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis' AND table_name = 'settings'
            ) as exists
        ");

        if (!$exists->exists) {
            DB::unprepared("
                CREATE TABLE cmis.settings (
                    setting_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    key VARCHAR(255) NOT NULL,
                    value JSONB,
                    type VARCHAR(50) DEFAULT 'string',
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                    deleted_at TIMESTAMP WITH TIME ZONE,
                    UNIQUE(org_id, key)
                )
            ");
            DB::unprepared("CREATE INDEX idx_settings_org_id ON cmis.settings(org_id)");
            DB::unprepared("CREATE INDEX idx_settings_key ON cmis.settings(key)");
            DB::unprepared("ALTER TABLE cmis.settings ENABLE ROW LEVEL SECURITY");
            DB::unprepared("CREATE POLICY settings_org_isolation ON cmis.settings FOR ALL USING (org_id::text = current_setting('app.current_org_id', true)) WITH CHECK (org_id::text = current_setting('app.current_org_id', true))");

            echo "✓ Created cmis.settings table with RLS\n";
        } else {
            // Ensure deleted_at column exists for soft deletes
            $hasDeletedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = 'cmis' AND table_name = 'settings' AND column_name = 'deleted_at'
                ) as exists
            ");

            if (!$hasDeletedAt->exists) {
                DB::unprepared("ALTER TABLE cmis.settings ADD COLUMN deleted_at TIMESTAMP WITH TIME ZONE");
                echo "✓ Added deleted_at column to cmis.settings\n";
            }
        }
    }

    public function down(): void
    {
        DB::unprepared("DROP POLICY IF EXISTS settings_org_isolation ON cmis.settings");
        DB::unprepared("DROP TABLE IF EXISTS cmis.settings CASCADE");
    }
};
