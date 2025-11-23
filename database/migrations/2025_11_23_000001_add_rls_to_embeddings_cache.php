<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * CRITICAL FIX: Add RLS policies to embeddings_cache table
     *
     * This table was created without RLS policies in migration 2025_11_19_151700,
     * which violates CMIS multi-tenancy requirements. This migration adds:
     * - org_id column (if missing)
     * - RLS policies for organization isolation
     * - Indexes for performance
     */
    public function up(): void
    {
        // Check if org_id column exists
        $columnExists = DB::selectOne("
            SELECT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'embeddings_cache'
                AND column_name = 'org_id'
            ) as exists
        ");

        // Add org_id column if it doesn't exist
        if (!$columnExists->exists) {
            DB::statement("
                ALTER TABLE cmis.embeddings_cache
                ADD COLUMN org_id UUID;
            ");

            echo "✓ Added org_id column to embeddings_cache\n";

            // Backfill org_id from current session (if any records exist)
            // This is a one-time operation for existing data
            DB::statement("
                UPDATE cmis.embeddings_cache
                SET org_id = current_setting('app.current_org_id', true)::UUID
                WHERE org_id IS NULL
                AND current_setting('app.current_org_id', true) IS NOT NULL;
            ");
        }

        // Check if columns match model expectations
        $modelColumnExists = DB::selectOne("
            SELECT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'embeddings_cache'
                AND column_name = 'model_name'
            ) as exists
        ");

        // Rename 'model' column to 'model_name' if needed
        if (!$modelColumnExists->exists) {
            $oldColumnExists = DB::selectOne("
                SELECT EXISTS (
                    SELECT FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = 'embeddings_cache'
                    AND column_name = 'model'
                ) as exists
            ");

            if ($oldColumnExists->exists) {
                DB::statement("
                    ALTER TABLE cmis.embeddings_cache
                    RENAME COLUMN model TO model_name;
                ");
                echo "✓ Renamed column 'model' to 'model_name'\n";
            }
        }

        // Add missing columns expected by model
        $columnsToAdd = [
            'content_type' => "VARCHAR(100) DEFAULT 'text'",
            'embedding_dim' => "INTEGER DEFAULT 768",
            'cached_at' => "TIMESTAMP WITH TIME ZONE DEFAULT now()",
            'last_accessed' => "TIMESTAMP WITH TIME ZONE DEFAULT now()",
            'access_count' => "INTEGER DEFAULT 1",
            'metadata' => "JSONB",
            'provider' => "VARCHAR(100) DEFAULT 'gemini'",
        ];

        foreach ($columnsToAdd as $columnName => $columnDef) {
            $exists = DB::selectOne("
                SELECT EXISTS (
                    SELECT FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = 'embeddings_cache'
                    AND column_name = ?
                ) as exists
            ", [$columnName]);

            if (!$exists->exists) {
                DB::statement("
                    ALTER TABLE cmis.embeddings_cache
                    ADD COLUMN {$columnName} {$columnDef};
                ");
                echo "✓ Added column {$columnName}\n";
            }
        }

        // Enable RLS on the table
        $this->enableRLS('cmis.embeddings_cache');

        // Create index on org_id for performance
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_embeddings_cache_org_id
            ON cmis.embeddings_cache(org_id);
        ");

        // Create index on content_hash for faster lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_embeddings_cache_content_hash
            ON cmis.embeddings_cache(content_hash);
        ");

        echo "\n✅ RLS policies and indexes added to embeddings_cache table\n";
        echo "   Multi-tenancy compliance restored!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable RLS
        $this->disableRLS('cmis.embeddings_cache');

        // Drop indexes
        DB::statement("DROP INDEX IF EXISTS cmis.idx_embeddings_cache_org_id;");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_embeddings_cache_content_hash;");

        // Note: We don't remove columns to avoid data loss
        echo "✓ RLS policies and indexes removed from embeddings_cache\n";
    }
};
