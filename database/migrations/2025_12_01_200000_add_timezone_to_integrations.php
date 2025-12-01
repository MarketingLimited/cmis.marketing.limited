<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Add timezone column to integrations table for profile-level timezone override.
     *
     * TIMEZONE INHERITANCE HIERARCHY:
     * 1. Profile/Integration timezone (if set)
     * 2. Profile Group timezone (if profile is in a group and group has timezone)
     * 3. Organization timezone (fallback)
     * 4. UTC (ultimate fallback)
     */
    public function up(): void
    {
        // Add timezone column to integrations table
        DB::statement("
            ALTER TABLE cmis.integrations
            ADD COLUMN IF NOT EXISTS timezone VARCHAR(100)
        ");

        // Add helpful comment
        DB::statement("
            COMMENT ON COLUMN cmis.integrations.timezone IS
            'Optional timezone override for this profile. If NULL, inherits from profile group, then organization.'
        ");

        echo "Added timezone column to cmis.integrations\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS timezone");
    }
};
