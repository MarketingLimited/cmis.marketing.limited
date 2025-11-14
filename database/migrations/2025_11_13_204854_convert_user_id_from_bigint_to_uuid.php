<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if users table exists
        $usersExists = DB::select("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
            ) as exists
        ");

        if (!$usersExists[0]->exists) {
            return; // Skip if users table doesn't exist
        }

        // Check if user_id column already exists and is already UUID
        $column = DB::select("
            SELECT data_type
            FROM information_schema.columns
            WHERE table_schema = 'cmis'
            AND table_name = 'users'
            AND column_name = 'user_id'
        ");

        if (!empty($column) && $column[0]->data_type === 'uuid') {
            return; // Already converted, skip
        }

        // Step 1: Create a temporary mapping table to store old_id -> new_uuid
        DB::statement('
            CREATE TEMP TABLE user_id_mapping (
                old_id BIGINT PRIMARY KEY,
                new_uuid UUID NOT NULL
            )
        ');

        // Step 2: Add new user_id column to users table (only if it doesn't exist)
        if (empty($column)) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN user_id UUID');
        }

        // Step 3: Generate UUIDs for all existing users and populate mapping table
        DB::statement("
            INSERT INTO user_id_mapping (old_id, new_uuid)
            SELECT id, gen_random_uuid()
            FROM cmis.users
        ");

        // Step 4: Update users table with new UUIDs
        DB::statement('
            UPDATE cmis.users u
            SET user_id = m.new_uuid
            FROM user_id_mapping m
            WHERE u.id = m.old_id
        ');

        // Step 5: Update sessions table
        DB::statement('ALTER TABLE cmis.sessions ADD COLUMN new_user_id UUID');
        DB::statement('
            UPDATE cmis.sessions s
            SET new_user_id = m.new_uuid
            FROM user_id_mapping m
            WHERE s.user_id = m.old_id
        ');
        DB::statement('ALTER TABLE cmis.sessions DROP COLUMN user_id');
        DB::statement('ALTER TABLE cmis.sessions RENAME COLUMN new_user_id TO user_id');

        // Step 6: Update user_orgs table if it exists
        $userOrgsExists = DB::select("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis'
                AND table_name = 'user_orgs'
            ) as exists
        ");

        if ($userOrgsExists[0]->exists) {
            DB::statement('ALTER TABLE cmis.user_orgs ADD COLUMN new_user_id UUID');
            DB::statement('
                UPDATE cmis.user_orgs uo
                SET new_user_id = m.new_uuid
                FROM user_id_mapping m
                WHERE uo.user_id = m.old_id
            ');
            DB::statement('ALTER TABLE cmis.user_orgs DROP COLUMN user_id CASCADE');
            DB::statement('ALTER TABLE cmis.user_orgs RENAME COLUMN new_user_id TO user_id');
        }

        // Step 7: Update user_permissions table if it exists
        $userPermissionsExists = DB::select("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis'
                AND table_name = 'user_permissions'
            ) as exists
        ");

        if ($userPermissionsExists[0]->exists) {
            // Check if user_id is already UUID
            $column = DB::select("
                SELECT data_type
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'user_permissions'
                AND column_name = 'user_id'
            ");

            if (!empty($column) && $column[0]->data_type != 'uuid') {
                DB::statement('ALTER TABLE cmis.user_permissions ADD COLUMN new_user_id UUID');
                DB::statement('
                    UPDATE cmis.user_permissions up
                    SET new_user_id = m.new_uuid
                    FROM user_id_mapping m
                    WHERE up.user_id = m.old_id
                ');
                DB::statement('ALTER TABLE cmis.user_permissions DROP COLUMN user_id CASCADE');
                DB::statement('ALTER TABLE cmis.user_permissions RENAME COLUMN new_user_id TO user_id');
            }
        }

        // Step 8: Update notifications table if it exists
        $notificationsExists = DB::select("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis'
                AND table_name = 'notifications'
            ) as exists
        ");

        if ($notificationsExists[0]->exists) {
            DB::statement('ALTER TABLE cmis.notifications ADD COLUMN new_user_id UUID');
            DB::statement('
                UPDATE cmis.notifications n
                SET new_user_id = m.new_uuid
                FROM user_id_mapping m
                WHERE n.user_id = m.old_id
            ');
            DB::statement('ALTER TABLE cmis.notifications DROP COLUMN user_id CASCADE');
            DB::statement('ALTER TABLE cmis.notifications RENAME COLUMN new_user_id TO user_id');
        }

        // Step 9: Update scheduled_social_posts table if it exists
        $scheduledPostsExists = DB::select("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis'
                AND table_name = 'scheduled_social_posts'
            ) as exists
        ");

        if ($scheduledPostsExists[0]->exists) {
            // This table might already have UUID user_id or might need conversion
            $column = DB::select("
                SELECT data_type
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'scheduled_social_posts'
                AND column_name = 'user_id'
            ");

            if (!empty($column) && $column[0]->data_type == 'bigint') {
                DB::statement('ALTER TABLE cmis.scheduled_social_posts ADD COLUMN new_user_id UUID');
                DB::statement('
                    UPDATE cmis.scheduled_social_posts ssp
                    SET new_user_id = m.new_uuid
                    FROM user_id_mapping m
                    WHERE ssp.user_id = m.old_id
                ');
                DB::statement('ALTER TABLE cmis.scheduled_social_posts DROP COLUMN user_id CASCADE');
                DB::statement('ALTER TABLE cmis.scheduled_social_posts RENAME COLUMN new_user_id TO user_id');
            }
        }

        // Step 10: Update any other tables with user_id foreign keys
        // Get all tables with user_id columns
        $tables = DB::select("
            SELECT DISTINCT table_name
            FROM information_schema.columns
            WHERE table_schema = 'cmis'
            AND column_name IN ('user_id', 'created_by', 'uploaded_by', 'used_by', 'requested_by', 'assigned_to', 'invited_by', 'edited_by')
            AND table_name NOT IN ('users', 'sessions', 'user_orgs', 'user_permissions', 'notifications', 'scheduled_social_posts')
        ");

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            // Get columns that reference users
            $columns = DB::select("
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = ?
                AND column_name IN ('user_id', 'created_by', 'uploaded_by', 'used_by', 'requested_by', 'assigned_to', 'invited_by', 'edited_by')
            ", [$tableName]);

            foreach ($columns as $column) {
                $columnName = $column->column_name;

                // Skip if already UUID
                if ($column->data_type == 'uuid') {
                    continue;
                }

                $newColumnName = 'new_' . $columnName;

                DB::statement("ALTER TABLE cmis.{$tableName} ADD COLUMN {$newColumnName} UUID");
                DB::statement("
                    UPDATE cmis.{$tableName} t
                    SET {$newColumnName} = m.new_uuid
                    FROM user_id_mapping m
                    WHERE t.{$columnName} = m.old_id
                ");
                DB::statement("ALTER TABLE cmis.{$tableName} DROP COLUMN {$columnName} CASCADE");
                DB::statement("ALTER TABLE cmis.{$tableName} RENAME COLUMN {$newColumnName} TO {$columnName}");
            }
        }

        // Step 11: Drop old id column and constraints from users table
        DB::statement('ALTER TABLE cmis.users DROP COLUMN id CASCADE');

        // Step 12: Set user_id as primary key
        DB::statement('ALTER TABLE cmis.users ADD PRIMARY KEY (user_id)');

        // Step 13: Recreate foreign key constraints
        DB::statement('
            ALTER TABLE cmis.sessions
            ADD CONSTRAINT fk_sessions_user_id
            FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE SET NULL
        ');

        if ($userOrgsExists[0]->exists) {
            DB::statement('
                ALTER TABLE cmis.user_orgs
                ADD CONSTRAINT fk_user_orgs_user_id
                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            ');
        }

        if ($userPermissionsExists[0]->exists) {
            DB::statement('
                ALTER TABLE cmis.user_permissions
                ADD CONSTRAINT fk_user_permissions_user_id
                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            ');
        }

        if ($notificationsExists[0]->exists) {
            DB::statement('
                ALTER TABLE cmis.notifications
                ADD CONSTRAINT fk_notifications_user_id
                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            ');
        }

        // Recreate foreign keys for all other tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            $columns = DB::select("
                SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = ?
                AND column_name IN ('user_id', 'created_by', 'uploaded_by', 'used_by', 'requested_by', 'assigned_to', 'invited_by', 'edited_by')
            ", [$tableName]);

            foreach ($columns as $column) {
                $columnName = $column->column_name;
                $constraintName = "fk_{$tableName}_{$columnName}";

                try {
                    DB::statement("
                        ALTER TABLE cmis.{$tableName}
                        ADD CONSTRAINT {$constraintName}
                        FOREIGN KEY ({$columnName}) REFERENCES cmis.users(user_id) ON DELETE CASCADE
                    ");
                } catch (\Exception $e) {
                    // Some constraints might need ON DELETE SET NULL instead
                    try {
                        DB::statement("
                            ALTER TABLE cmis.{$tableName}
                            ADD CONSTRAINT {$constraintName}
                            FOREIGN KEY ({$columnName}) REFERENCES cmis.users(user_id) ON DELETE SET NULL
                        ");
                    } catch (\Exception $e2) {
                        // Log but don't fail
                        \Log::warning("Could not create constraint {$constraintName}: " . $e2->getMessage());
                    }
                }
            }
        }

        // Drop the temp mapping table
        DB::statement('DROP TABLE user_id_mapping');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive migration and cannot be easily reversed
        // as we lose the original bigint IDs
        throw new \Exception('This migration cannot be reversed as it would result in data loss.');
    }
};
