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
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.brand_voices (
                voice_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                profile_group_id UUID,
                name VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                tone VARCHAR(50) DEFAULT 'friendly',
                personality_traits JSONB DEFAULT '[]',
                inspired_by JSONB DEFAULT '[]',
                target_audience TEXT,
                keywords_to_use JSONB DEFAULT '[]',
                keywords_to_avoid JSONB DEFAULT '[]',
                emojis_preference VARCHAR(20) DEFAULT 'moderate',
                hashtag_strategy VARCHAR(20) DEFAULT 'moderate',
                example_posts JSONB DEFAULT '[]',
                primary_language VARCHAR(10) DEFAULT 'ar',
                secondary_languages JSONB DEFAULT '[]',
                dialect_preference VARCHAR(50),
                ai_system_prompt TEXT,
                temperature DECIMAL(3, 2) DEFAULT 0.70,
                created_by UUID NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP
            )
        ");

        // Create indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_voices_org_id ON cmis.brand_voices(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_voices_profile_group_id ON cmis.brand_voices(profile_group_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_voices_created_by ON cmis.brand_voices(created_by)');

        // Create foreign keys (skip if exist)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'brand_voices_org_id_foreign') THEN
                    ALTER TABLE cmis.brand_voices ADD CONSTRAINT brand_voices_org_id_foreign FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'brand_voices_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.brand_voices ADD CONSTRAINT brand_voices_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'brand_voices_created_by_foreign') THEN
                    ALTER TABLE cmis.brand_voices ADD CONSTRAINT brand_voices_created_by_foreign FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE RESTRICT;
                END IF;
            END \$\$;
        ");

        // Enable RLS
        DB::statement('ALTER TABLE cmis.brand_voices ENABLE ROW LEVEL SECURITY');

        // Create RLS policies (skip if exist)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_voices' AND policyname = 'brand_voices_select_policy') THEN
                    CREATE POLICY brand_voices_select_policy ON cmis.brand_voices FOR SELECT USING (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_voices' AND policyname = 'brand_voices_insert_policy') THEN
                    CREATE POLICY brand_voices_insert_policy ON cmis.brand_voices FOR INSERT WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_voices' AND policyname = 'brand_voices_update_policy') THEN
                    CREATE POLICY brand_voices_update_policy ON cmis.brand_voices FOR UPDATE USING (org_id = current_setting('app.current_org_id', true)::uuid) WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_voices' AND policyname = 'brand_voices_delete_policy') THEN
                    CREATE POLICY brand_voices_delete_policy ON cmis.brand_voices FOR DELETE USING (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END \$\$;
        ");

        // Add foreign key from profile_groups to brand_voices
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'profile_groups_brand_voice_id_foreign') THEN
                    ALTER TABLE cmis.profile_groups ADD CONSTRAINT profile_groups_brand_voice_id_foreign FOREIGN KEY (brand_voice_id) REFERENCES cmis.brand_voices(voice_id) ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.profile_groups DROP CONSTRAINT IF EXISTS profile_groups_brand_voice_id_foreign');
        DB::statement('DROP TABLE IF EXISTS cmis.brand_voices CASCADE');
    }
};
