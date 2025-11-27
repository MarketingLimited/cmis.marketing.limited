<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes duplicate role entries, keeping only the original seeded ones.
     */
    public function up(): void
    {
        // Delete duplicate roles, keeping one per role_id using PostgreSQL ctid
        DB::statement("
            DELETE FROM cmis.roles a USING cmis.roles b
            WHERE a.ctid < b.ctid AND a.role_id = b.role_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse deletion of duplicates
    }
};
