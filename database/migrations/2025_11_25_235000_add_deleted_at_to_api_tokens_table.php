<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if deleted_at column already exists using direct SQL
        $hasDeletedAt = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = 'cmis' AND table_name = 'api_tokens' AND column_name = 'deleted_at'
            ) as exists
        ");

        if (!$hasDeletedAt->exists) {
            DB::unprepared("ALTER TABLE cmis.api_tokens ADD COLUMN deleted_at TIMESTAMP WITH TIME ZONE");
            echo "✓ Added deleted_at column to cmis.api_tokens\n";
        } else {
            echo "→ deleted_at column already exists in cmis.api_tokens\n";
        }
    }

    public function down(): void
    {
        $hasDeletedAt = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = 'cmis' AND table_name = 'api_tokens' AND column_name = 'deleted_at'
            ) as exists
        ");

        if ($hasDeletedAt->exists) {
            DB::unprepared("ALTER TABLE cmis.api_tokens DROP COLUMN deleted_at");
        }
    }
};
