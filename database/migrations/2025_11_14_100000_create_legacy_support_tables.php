<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE IF NOT EXISTS cmis.reports (
                report_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                user_id UUID NULL,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                format TEXT NULL,
                file_path TEXT NULL,
                parameters JSONB NOT NULL DEFAULT '{}'::jsonb,
                generated_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );

            CREATE TABLE IF NOT EXISTS cmis.scheduled_posts (
                post_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                platform TEXT NOT NULL,
                content TEXT NULL,
                status TEXT NOT NULL DEFAULT 'scheduled',
                scheduled_time TIMESTAMPTZ NULL,
                processed_at TIMESTAMPTZ NULL,
                payload JSONB NOT NULL DEFAULT '{}'::jsonb,
                last_error TEXT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );

            CREATE INDEX IF NOT EXISTS reports_org_id_idx ON cmis.reports (org_id);
            CREATE INDEX IF NOT EXISTS scheduled_posts_org_id_idx ON cmis.scheduled_posts (org_id);
            CREATE INDEX IF NOT EXISTS scheduled_posts_status_idx ON cmis.scheduled_posts (status);
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.scheduled_posts CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.reports CASCADE');
    }
};
