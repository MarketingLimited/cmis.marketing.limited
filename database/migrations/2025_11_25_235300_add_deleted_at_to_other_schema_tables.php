<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables in other schemas that need deleted_at column.
     */
    private array $tables = [
        'cmis_ai.usage_summary',
        'cmis_ai.usage_tracking',
        'cmis_twitter.pixel_events',
    ];

    public function up(): void
    {
        foreach ($this->tables as $fullTable) {
            [$schema, $table] = explode('.', $fullTable);

            $hasDeletedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = ? AND table_name = ? AND column_name = 'deleted_at'
                ) as exists
            ", [$schema, $table]);

            if (!$hasDeletedAt->exists) {
                DB::unprepared("ALTER TABLE {$fullTable} ADD COLUMN deleted_at TIMESTAMP WITH TIME ZONE");
                echo "✓ Added deleted_at to {$fullTable}\n";
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $fullTable) {
            [$schema, $table] = explode('.', $fullTable);

            $hasDeletedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = ? AND table_name = ? AND column_name = 'deleted_at'
                ) as exists
            ", [$schema, $table]);

            if ($hasDeletedAt->exists) {
                DB::unprepared("ALTER TABLE {$fullTable} DROP COLUMN deleted_at");
            }
        }
    }
};
