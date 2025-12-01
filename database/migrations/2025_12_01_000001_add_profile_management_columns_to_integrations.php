<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     * Adds profile management columns to integrations table for VistaSocial-like profile management.
     */
    public function up(): void
    {
        // Add profile management columns to integrations table
        $columns = [
            // Industry/Category for the profile
            'industry' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS industry VARCHAR(100)",

            // Custom fields JSONB for flexible per-profile settings
            'custom_fields' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS custom_fields JSONB DEFAULT '{}'",

            // Enable/disable profile for publishing
            'is_enabled' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS is_enabled BOOLEAN DEFAULT true",

            // Display name override (different from account_name synced from platform)
            'display_name' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS display_name VARCHAR(255)",

            // Bio/description override
            'bio' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS bio TEXT",

            // Website URL
            'website_url' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS website_url TEXT",

            // Profile type (business, personal, creator)
            'profile_type' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS profile_type VARCHAR(50) DEFAULT 'business'",

            // Connected by user ID
            'connected_by' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS connected_by UUID",

            // Auto-boost enabled for this profile
            'auto_boost_enabled' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS auto_boost_enabled BOOLEAN DEFAULT false",
        ];

        foreach ($columns as $name => $sql) {
            try {
                DB::statement($sql);
                echo "✓ Added column '{$name}' to cmis.integrations\n";
            } catch (\Exception $e) {
                // Column might already exist, skip
                echo "- Column '{$name}' already exists or error: {$e->getMessage()}\n";
            }
        }

        // Add foreign key for connected_by if users table exists
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'fk_integrations_connected_by'
                ) THEN
                    ALTER TABLE cmis.integrations
                    ADD CONSTRAINT fk_integrations_connected_by
                    FOREIGN KEY (connected_by)
                    REFERENCES cmis.users(user_id)
                    ON DELETE SET NULL;
                END IF;
            EXCEPTION
                WHEN OTHERS THEN
                    -- Ignore if constraint can't be created
                    NULL;
            END \$\$;
        ");

        // Create indexes for new columns
        DB::statement("CREATE INDEX IF NOT EXISTS idx_integrations_industry ON cmis.integrations(industry)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_integrations_is_enabled ON cmis.integrations(is_enabled)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_integrations_profile_type ON cmis.integrations(profile_type)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_integrations_connected_by ON cmis.integrations(connected_by)");

        echo "✓ Profile management columns added successfully\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first
        DB::statement("DROP INDEX IF EXISTS cmis.idx_integrations_industry");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_integrations_is_enabled");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_integrations_profile_type");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_integrations_connected_by");

        // Drop foreign key
        DB::statement("ALTER TABLE cmis.integrations DROP CONSTRAINT IF EXISTS fk_integrations_connected_by");

        // Drop columns
        $columns = [
            'industry',
            'custom_fields',
            'is_enabled',
            'display_name',
            'bio',
            'website_url',
            'profile_type',
            'connected_by',
            'auto_boost_enabled',
        ];

        foreach ($columns as $column) {
            DB::statement("ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS {$column}");
        }
    }
};
