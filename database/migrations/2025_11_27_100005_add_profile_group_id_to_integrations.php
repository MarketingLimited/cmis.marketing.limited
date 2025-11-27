<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds profile_group_id to integrations table to link integrations to profile groups.
     */
    public function up(): void
    {
        // Add profile_group_id column if not exists
        $hasColumn = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'integrations'
                AND column_name = 'profile_group_id'
            ) as exists
        ");

        if (!$hasColumn->exists) {
            // Add the column first
            DB::statement("
                ALTER TABLE cmis.integrations
                ADD COLUMN profile_group_id UUID NULL
            ");

            // Then add the foreign key constraint
            DB::statement("
                ALTER TABLE cmis.integrations
                ADD CONSTRAINT fk_integrations_profile_group
                FOREIGN KEY (profile_group_id)
                REFERENCES cmis.profile_groups(group_id)
                ON DELETE SET NULL
            ");

            // Add index
            DB::statement("CREATE INDEX IF NOT EXISTS idx_integrations_profile_group_id ON cmis.integrations (profile_group_id)");
        }

        // Add each column individually if it doesn't exist (for richer profile display)
        $columnsToAdd = [
            'account_name' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS account_name VARCHAR(255) NULL",
            'platform_handle' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS platform_handle VARCHAR(255) NULL",
            'avatar_url' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS avatar_url TEXT NULL",
            'status' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'active'",
        ];

        foreach ($columnsToAdd as $columnName => $sql) {
            $exists = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = 'integrations'
                    AND column_name = ?
                ) as exists
            ", [$columnName]);

            if (!$exists->exists) {
                DB::statement($sql);
                echo "✓ Added {$columnName} to cmis.integrations\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint first
        DB::statement("
            ALTER TABLE cmis.integrations
            DROP CONSTRAINT IF EXISTS fk_integrations_profile_group
        ");

        // Drop index
        DB::statement("DROP INDEX IF EXISTS cmis.idx_integrations_profile_group_id");

        // Drop columns if they exist
        DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS profile_group_id");
        DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS account_name");
        DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS platform_handle");
        DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS avatar_url");
        DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS status");
    }
};
