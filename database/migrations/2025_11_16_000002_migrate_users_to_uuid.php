<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ⚠️ CRITICAL WARNING ⚠️
     * This migration converts users.user_id from BIGINT to UUID.
     * This is a destructive operation that will:
     * 1. Generate new UUIDs for all existing users
     * 2. Update all foreign key references across the database
     * 3. Cannot be reversed without data loss
     *
     * PREREQUISITES:
     * - Full database backup created
     * - Migration tested on staging environment
     * - Downtime window scheduled
     * - All users notified (tokens will be invalidated)
     *
     * ESTIMATED TIME: 5-30 minutes depending on data size
     */
    public function up(): void
    {
        echo "\n⚠️  CRITICAL MIGRATION: Converting users.user_id from BIGINT to UUID\n";
        echo "This will invalidate all existing user sessions and API tokens.\n";
        echo "Press Ctrl+C within 10 seconds to abort...\n\n";
        sleep(10);

        DB::beginTransaction();

        try {
            echo "Step 1/10: Checking current schema...\n";
            $currentType = DB::selectOne("
                SELECT data_type
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'user_id'
            ");

            if (!$currentType) {
                echo "✗ Cannot find users.user_id column. Migration cannot proceed.\n";
                DB::rollback();
                return;
            }

            if ($currentType->data_type === 'uuid') {
                echo "✓ Users table already uses UUID. Skipping migration.\n";
                DB::commit();
                return;
            }

            echo "Current type: {$currentType->data_type}\n";

            echo "Step 2/10: Creating UUID mapping table...\n";
            DB::statement("
                CREATE TEMP TABLE user_id_mapping (
                    old_id BIGINT,
                    new_id UUID,
                    PRIMARY KEY (old_id)
                )
            ");

            echo "Step 3/10: Generating UUIDs for existing users...\n";
            $users = DB::select("SELECT user_id FROM cmis.users ORDER BY user_id");

            foreach ($users as $user) {
                $newUuid = (string) Str::uuid();
                DB::statement("INSERT INTO user_id_mapping (old_id, new_id) VALUES (?, ?)",
                    [$user->user_id, $newUuid]);
            }

            echo "✓ Generated " . count($users) . " UUIDs\n";

            echo "Step 4/10: Adding new UUID column to users table...\n";
            DB::statement("ALTER TABLE cmis.users ADD COLUMN user_id_uuid UUID");

            echo "Step 5/10: Populating UUID column...\n";
            DB::statement("
                UPDATE cmis.users u
                SET user_id_uuid = m.new_id
                FROM user_id_mapping m
                WHERE u.user_id = m.old_id
            ");

            // Find all tables with foreign keys to users
            echo "Step 6/10: Finding all foreign key references...\n";
            $foreignKeys = DB::select("
                SELECT
                    tc.table_schema,
                    tc.table_name,
                    kcu.column_name,
                    tc.constraint_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                AND ccu.table_name = 'users'
                AND ccu.column_name = 'user_id'
            ");

            echo "✓ Found " . count($foreignKeys) . " foreign key references\n";

            echo "Step 7/10: Updating foreign key references...\n";
            foreach ($foreignKeys as $fk) {
                $table = $fk->table_schema . '.' . $fk->table_name;
                $column = $fk->column_name;
                $constraint = $fk->constraint_name;

                echo "  - Updating {$table}.{$column}...\n";

                // Add new UUID column
                DB::statement("ALTER TABLE {$table} ADD COLUMN {$column}_uuid UUID");

                // Drop foreign key constraint
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");

                // Copy UUIDs using mapping
                DB::statement("
                    UPDATE {$table} t
                    SET {$column}_uuid = m.new_id
                    FROM user_id_mapping m
                    WHERE t.{$column} = m.old_id
                ");
            }

            echo "Step 8/10: Dropping old columns and renaming new ones...\n";

            // Drop old user_id column (this will cascade to foreign keys)
            DB::statement("ALTER TABLE cmis.users DROP COLUMN user_id CASCADE");

            // Rename new column
            DB::statement("ALTER TABLE cmis.users RENAME COLUMN user_id_uuid TO user_id");

            // Add primary key
            DB::statement("ALTER TABLE cmis.users ADD PRIMARY KEY (user_id)");

            echo "Step 9/10: Re-creating foreign key constraints...\n";
            foreach ($foreignKeys as $fk) {
                $table = $fk->table_schema . '.' . $fk->table_name;
                $column = $fk->column_name;

                // Drop old column
                DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS {$column}");

                // Rename new column
                DB::statement("ALTER TABLE {$table} RENAME COLUMN {$column}_uuid TO {$column}");

                // Create new foreign key
                $newConstraint = "fk_" . $fk->table_name . "_" . $column;
                DB::statement("
                    ALTER TABLE {$table}
                    ADD CONSTRAINT {$newConstraint}
                    FOREIGN KEY ({$column})
                    REFERENCES cmis.users(user_id)
                    ON DELETE CASCADE
                ");
            }

            echo "Step 10/10: Cleaning up...\n";

            // Truncate tokens table (all tokens will be invalid)
            DB::statement("TRUNCATE TABLE personal_access_tokens CASCADE");
            echo "✓ Invalidated all API tokens (users must re-login)\n";

            DB::commit();

            echo "\n✓ Migration completed successfully!\n";
            echo "✓ Users table now uses UUID\n";
            echo "✓ All foreign keys updated\n";
            echo "⚠️  All users must re-login\n\n";

        } catch (\Exception $e) {
            DB::rollBack();

            echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
            echo "❌ All changes have been rolled back\n";
            echo "Please restore from backup and investigate the error\n\n";

            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * ⚠️ THIS MIGRATION CANNOT BE REVERSED ⚠️
     *
     * UUIDs cannot be converted back to bigint without data loss.
     * If you need to rollback, restore from backup.
     */
    public function down(): void
    {
        throw new \Exception(
            "This migration cannot be reversed. UUID to BIGINT conversion is not supported. " .
            "Restore from backup if you need to rollback."
        );
    }
};
