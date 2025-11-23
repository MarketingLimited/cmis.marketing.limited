<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create generated_media table in cmis_ai schema
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_ai.generated_media (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                campaign_id UUID,
                user_id UUID,
                media_type VARCHAR(20) NOT NULL CHECK (media_type IN ('image', 'video')),
                ai_model VARCHAR(100) NOT NULL,
                prompt_text TEXT NOT NULL,
                media_url TEXT,
                storage_path TEXT,
                resolution VARCHAR(20),
                duration_seconds INTEGER,
                aspect_ratio VARCHAR(10),
                file_size_bytes BIGINT,
                generation_cost DECIMAL(10,4),
                status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
                error_message TEXT,
                metadata JSONB DEFAULT '{}',
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW(),
                deleted_at TIMESTAMP
            );
        ");

        // Add indexes
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_generated_media_org_id ON cmis_ai.generated_media(org_id);
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_generated_media_campaign_id ON cmis_ai.generated_media(campaign_id);
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_generated_media_user_id ON cmis_ai.generated_media(user_id);
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_generated_media_media_type ON cmis_ai.generated_media(media_type);
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_generated_media_status ON cmis_ai.generated_media(status);
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_generated_media_created_at ON cmis_ai.generated_media(created_at);
        ");

        // Enable Row Level Security
        $this->enableRLS('cmis_ai.generated_media');

        // Add foreign key constraint to orgs (if table has primary key)
        $orgPkExists = DB::select("
            SELECT 1 FROM information_schema.table_constraints
            WHERE table_schema = 'cmis' AND table_name = 'orgs'
            AND constraint_type = 'PRIMARY KEY'
        ");

        if (!empty($orgPkExists)) {
            DB::statement("
                ALTER TABLE cmis_ai.generated_media
                ADD CONSTRAINT fk_generated_media_org
                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
            ");
        }

        // Create updated_at trigger
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis_ai.update_generated_media_timestamp()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER set_generated_media_timestamp
            BEFORE UPDATE ON cmis_ai.generated_media
            FOR EACH ROW
            EXECUTE FUNCTION cmis_ai.update_generated_media_timestamp();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS set_generated_media_timestamp ON cmis_ai.generated_media;");
        DB::statement("DROP FUNCTION IF EXISTS cmis_ai.update_generated_media_timestamp();");
        $this->disableRLS('cmis_ai.generated_media');
        DB::statement("DROP TABLE IF EXISTS cmis_ai.generated_media CASCADE;");
    }
};
