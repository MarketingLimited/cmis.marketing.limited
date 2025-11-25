<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            DB::statement("
                ALTER TABLE cmis.users
                ADD CONSTRAINT users_pkey PRIMARY KEY (user_id)
            ");

            echo "✓ Added primary key to cmis.users(user_id)\n";
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
