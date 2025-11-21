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
        // Add image and video quota columns to ai_usage_quotas table
        DB::statement("
            ALTER TABLE cmis_ai.usage_quotas
            ADD COLUMN IF NOT EXISTS image_quota_daily INTEGER DEFAULT 5,
            ADD COLUMN IF NOT EXISTS image_quota_monthly INTEGER DEFAULT 50,
            ADD COLUMN IF NOT EXISTS image_used_daily INTEGER DEFAULT 0,
            ADD COLUMN IF NOT EXISTS image_used_monthly INTEGER DEFAULT 0,
            ADD COLUMN IF NOT EXISTS video_quota_daily INTEGER DEFAULT 0,
            ADD COLUMN IF NOT EXISTS video_quota_monthly INTEGER DEFAULT 0,
            ADD COLUMN IF NOT EXISTS video_used_daily INTEGER DEFAULT 0,
            ADD COLUMN IF NOT EXISTS video_used_monthly INTEGER DEFAULT 0;
        ");

        // Update existing quotas based on subscription tiers
        DB::statement("
            UPDATE cmis.ai_usage_quotas
            SET
                image_quota_daily = CASE
                    WHEN tier = 'free' THEN 5
                    WHEN tier = 'pro' THEN 50
                    WHEN tier = 'enterprise' THEN -1
                    ELSE 5
                END,
                image_quota_monthly = CASE
                    WHEN quota_type = 'free' THEN 50
                    WHEN quota_type = 'pro' THEN 500
                    WHEN quota_type = 'enterprise' THEN -1
                    ELSE 50
                END,
                video_quota_daily = CASE
                    WHEN quota_type = 'free' THEN 0
                    WHEN quota_type = 'pro' THEN 10
                    WHEN quota_type = 'enterprise' THEN -1
                    ELSE 0
                END,
                video_quota_monthly = CASE
                    WHEN quota_type = 'free' THEN 0
                    WHEN quota_type = 'pro' THEN 100
                    WHEN quota_type = 'enterprise' THEN -1
                    ELSE 0
                END
            WHERE tier IS NOT NULL;
        ");

        // Add columns to ai_usage_logs for tracking media generation
        DB::statement("
            ALTER TABLE cmis_ai.ai_usage_logs
            ADD COLUMN IF NOT EXISTS generation_type VARCHAR(20),
            ADD COLUMN IF NOT EXISTS cost_usd DECIMAL(10,4),
            ADD COLUMN IF NOT EXISTS media_resolution VARCHAR(20),
            ADD COLUMN IF NOT EXISTS video_duration INTEGER,
            ADD COLUMN IF NOT EXISTS generation_metadata JSONB DEFAULT '{}';
        ");

        // Create index for efficient quota queries
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ai_usage_logs_generation_type
            ON cmis_ai.ai_usage_logs(generation_type);
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_ai_usage_logs_org_created
            ON cmis_ai.ai_usage_logs(org_id, created_at);
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS cmis_ai.idx_ai_usage_logs_generation_type;");
        DB::statement("DROP INDEX IF EXISTS cmis_ai.idx_ai_usage_logs_org_created;");

        DB::statement("
            ALTER TABLE cmis_ai.ai_usage_logs
            DROP COLUMN IF EXISTS generation_type,
            DROP COLUMN IF EXISTS cost_usd,
            DROP COLUMN IF EXISTS media_resolution,
            DROP COLUMN IF EXISTS video_duration,
            DROP COLUMN IF EXISTS generation_metadata;
        ");

        DB::statement("
            ALTER TABLE cmis_ai.usage_quotas
            DROP COLUMN IF EXISTS image_quota_daily,
            DROP COLUMN IF EXISTS image_quota_monthly,
            DROP COLUMN IF EXISTS image_used_daily,
            DROP COLUMN IF EXISTS image_used_monthly,
            DROP COLUMN IF EXISTS video_quota_daily,
            DROP COLUMN IF EXISTS video_quota_monthly,
            DROP COLUMN IF EXISTS video_used_daily,
            DROP COLUMN IF EXISTS video_used_monthly;
        ");
    }
};
