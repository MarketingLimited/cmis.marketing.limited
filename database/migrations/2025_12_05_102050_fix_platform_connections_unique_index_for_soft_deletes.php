<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix the unique index on platform_connections to be a partial index
 * that excludes soft-deleted records.
 *
 * Root Cause: The original unique index on (org_id, platform, account_id)
 * did not account for soft deletes. When a record was soft-deleted
 * (deleted_at IS NOT NULL), Laravel's updateOrCreate would try to insert
 * a new record, but PostgreSQL would reject it due to the unique constraint
 * still seeing the soft-deleted row.
 *
 * Fix: Create a partial unique index with WHERE deleted_at IS NULL,
 * so soft-deleted records don't block new records with the same key.
 */
return new class extends Migration
{
    public function up(): void
    {
        // First drop the constraint (which will also drop its backing index)
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM pg_constraint
                    WHERE conname = 'uq_platform_connections'
                    AND conrelid = 'cmis.platform_connections'::regclass
                ) THEN
                    ALTER TABLE cmis.platform_connections DROP CONSTRAINT uq_platform_connections;
                END IF;
            END $$;
        ");

        // Drop any standalone indexes that might exist
        DB::statement('DROP INDEX IF EXISTS cmis.idx_platform_connections_unique');

        // Create new partial unique index that excludes soft-deleted records
        DB::statement('
            CREATE UNIQUE INDEX idx_platform_connections_unique
            ON cmis.platform_connections (org_id, platform, account_id)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        // Drop the partial index
        DB::statement('DROP INDEX IF EXISTS cmis.idx_platform_connections_unique');

        // Restore the original full unique index (non-partial)
        DB::statement('
            CREATE UNIQUE INDEX idx_platform_connections_unique
            ON cmis.platform_connections (org_id, platform, account_id)
        ');
    }
};
