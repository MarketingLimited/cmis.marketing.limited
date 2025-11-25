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
                WHERE table_schema = 'cmis' AND table_name = 'platform_connections' AND column_name = 'deleted_at'
            ) as exists
        ");

        if (!$hasDeletedAt->exists) {
            DB::unprepared("ALTER TABLE cmis.platform_connections ADD COLUMN deleted_at TIMESTAMP WITH TIME ZONE");
            echo "✓ Added deleted_at column to cmis.platform_connections\n";
        } else {
            echo "→ deleted_at column already exists in cmis.platform_connections\n";
        }
    }

    public function down(): void
    {
        $hasDeletedAt = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = 'cmis' AND table_name = 'platform_connections' AND column_name = 'deleted_at'
            ) as exists
        ");

        if ($hasDeletedAt->exists) {
            DB::unprepared("ALTER TABLE cmis.platform_connections DROP COLUMN deleted_at");
        }
    }
};
