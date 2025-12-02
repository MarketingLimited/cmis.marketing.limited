<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add timezone support to organizations and social accounts.
     *
     * TIMEZONE INHERITANCE HIERARCHY:
     * 1. Organization has default timezone (e.g., 'UTC', 'Asia/Dubai')
     * 2. Profile Group inherits from org, but can override
     * 3. Social Account inherits from profile group, but can override
     *
     * This migration adds timezone columns to orgs and social_accounts tables.
     * profile_groups already has timezone column.
     */
    public function up(): void
    {
        // Add timezone to orgs table (if not exists)
        if (!Schema::hasColumn('cmis.orgs', 'timezone')) {
            Schema::table('cmis.orgs', function (Blueprint $table) {
                $table->string('timezone', 100)->default('UTC')->after('currency');
            });
            // Set default timezone for existing orgs (can be customized per org)
            DB::statement("UPDATE cmis.orgs SET timezone = 'UTC' WHERE timezone IS NULL");
            // Add helpful comment
            DB::statement("COMMENT ON COLUMN cmis.orgs.timezone IS 'Default timezone for organization (e.g., UTC, Asia/Dubai). Inherited by profile groups and social accounts unless overridden.'");
        }

        // Add timezone to social_accounts table (if not exists)
        if (!Schema::hasColumn('cmis.social_accounts', 'timezone')) {
            Schema::table('cmis.social_accounts', function (Blueprint $table) {
                $table->string('timezone', 100)->nullable()->after('provider');
            });
            // Add helpful comment
            DB::statement("COMMENT ON COLUMN cmis.social_accounts.timezone IS 'Optional timezone override for this social account. If NULL, inherits from profile group.'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cmis.orgs', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });

        Schema::table('cmis.social_accounts', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
