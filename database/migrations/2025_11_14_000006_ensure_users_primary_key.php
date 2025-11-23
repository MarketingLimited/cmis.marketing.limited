<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Integrity - Ensure Users Primary Key
 *
 * Description: Ensures cmis.users has a primary key constraint before foreign key migrations run.
 *
 * This migration runs immediately after table creation (2025_11_14_000005) and BEFORE any
 * foreign key migrations (2025_11_18_*) to prevent SQLSTATE[42830] errors when creating FKs.
 *
 * Issue: complete_alters.sql in migration 2025_11_14_000005 might fail silently,
 * leaving users table without a primary key, which blocks foreign key creation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if primary key already exists
        $hasPrimaryKey = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.table_constraints
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND constraint_type = 'PRIMARY KEY'
            ) as exists
        ");

        if (!$hasPrimaryKey->exists) {
            echo "\n🔧 Adding primary key to cmis.users(user_id)...\n";

            try {
                DB::statement("
                    ALTER TABLE cmis.users
                    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id)
                ");

                echo "✅ Primary key users_pkey created successfully\n\n";
            } catch (\Exception $e) {
                echo "❌ Failed to create primary key: {$e->getMessage()}\n\n";
                throw $e; // Fail loudly - this is critical
            }
        } else {
            echo "\n✓ Primary key users_pkey already exists\n\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasPrimaryKey = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.table_constraints
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND constraint_type = 'PRIMARY KEY'
            ) as exists
        ");

        if ($hasPrimaryKey->exists) {
            DB::statement("ALTER TABLE cmis.users DROP CONSTRAINT IF EXISTS users_pkey");
        }
    }
};
