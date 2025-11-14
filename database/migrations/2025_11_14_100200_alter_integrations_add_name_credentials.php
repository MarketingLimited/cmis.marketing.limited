<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE cmis.integrations
            ADD COLUMN IF NOT EXISTS name TEXT,
            ADD COLUMN IF NOT EXISTS credentials JSONB DEFAULT '{}'::jsonb;
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE cmis.integrations
            DROP COLUMN IF EXISTS credentials,
            DROP COLUMN IF EXISTS name;
        SQL);
    }
};
