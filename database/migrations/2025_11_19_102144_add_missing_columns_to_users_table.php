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
        // Check and add current_org_id column
        $hasCurrentOrgId = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'current_org_id'
            ) as exists
        ");

        if (!$hasCurrentOrgId->exists) {
            DB::statement("
                ALTER TABLE cmis.users
                ADD COLUMN current_org_id UUID NULL
            ");

            DB::statement("
                ALTER TABLE cmis.users
                ADD CONSTRAINT fk_users_current_org
                FOREIGN KEY (current_org_id)
                REFERENCES cmis.orgs(org_id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
            ");

            echo "✓ Added current_org_id to cmis.users\n";
        }

        // Check and add status column
        $hasStatus = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'status'
            ) as exists
        ");

        if (!$hasStatus->exists) {
            DB::statement("
                ALTER TABLE cmis.users
                ADD COLUMN status VARCHAR(50) DEFAULT 'active'
            ");

            echo "✓ Added status to cmis.users\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasCurrentOrgId = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'current_org_id'
            ) as exists
        ");

        if ($hasCurrentOrgId->exists) {
            DB::statement("ALTER TABLE cmis.users DROP COLUMN IF EXISTS current_org_id CASCADE");
        }

        $hasStatus = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'status'
            ) as exists
        ");

        if ($hasStatus->exists) {
            DB::statement("ALTER TABLE cmis.users DROP COLUMN IF EXISTS status");
        }
    }
};
