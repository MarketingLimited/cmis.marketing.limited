<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Links integrations table to platform_connections for unified platform management.
     */
    public function up(): void
    {
        // Add platform_connection_id to integrations table
        DB::statement("
            ALTER TABLE cmis.integrations
            ADD COLUMN IF NOT EXISTS platform_connection_id uuid,
            ADD COLUMN IF NOT EXISTS selected_assets jsonb DEFAULT '{}'::jsonb,
            ADD COLUMN IF NOT EXISTS token_expires_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS last_sync_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS last_error_at timestamp with time zone,
            ADD COLUMN IF NOT EXISTS last_error_message text,
            ADD COLUMN IF NOT EXISTS account_metadata jsonb DEFAULT '{}'::jsonb
        ");

        // Add foreign key constraint
        DB::statement("
            ALTER TABLE cmis.integrations
            DROP CONSTRAINT IF EXISTS fk_integrations_platform_connection
        ");

        DB::statement("
            ALTER TABLE cmis.integrations
            ADD CONSTRAINT fk_integrations_platform_connection
            FOREIGN KEY (platform_connection_id)
            REFERENCES cmis.platform_connections(connection_id)
            ON DELETE SET NULL
        ");

        // Add index for platform_connection_id
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_integrations_platform_connection_id
            ON cmis.integrations(platform_connection_id)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint
        DB::statement("
            ALTER TABLE cmis.integrations
            DROP CONSTRAINT IF EXISTS fk_integrations_platform_connection
        ");

        // Drop index
        DB::statement("
            DROP INDEX IF EXISTS cmis.idx_integrations_platform_connection_id
        ");

        // Drop columns
        DB::statement("
            ALTER TABLE cmis.integrations
            DROP COLUMN IF EXISTS platform_connection_id,
            DROP COLUMN IF EXISTS selected_assets,
            DROP COLUMN IF EXISTS token_expires_at,
            DROP COLUMN IF EXISTS last_sync_at,
            DROP COLUMN IF EXISTS last_error_at,
            DROP COLUMN IF EXISTS last_error_message,
            DROP COLUMN IF EXISTS account_metadata
        ");
    }
};
