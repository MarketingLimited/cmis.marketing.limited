<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE cmis.integrations
            ADD COLUMN IF NOT EXISTS sync_status TEXT DEFAULT 'idle',
            ADD COLUMN IF NOT EXISTS last_synced_at TIMESTAMPTZ NULL,
            ADD COLUMN IF NOT EXISTS sync_metadata JSONB DEFAULT '{}'::jsonb;
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE cmis.integrations
            DROP COLUMN IF EXISTS sync_metadata,
            DROP COLUMN IF EXISTS last_synced_at,
            DROP COLUMN IF EXISTS sync_status;
        SQL);
    }
};
