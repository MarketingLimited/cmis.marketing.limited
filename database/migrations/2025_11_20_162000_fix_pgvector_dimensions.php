<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        echo "Fixing pgvector dimension mismatch (1536 → 768)...\n";

        // Fix 1: Update knowledge_index table if it exists
        if (Schema::hasTable('cmis.knowledge_index')) {
            // Check if column exists and needs fixing
            $result = DB::select("
                SELECT atttypmod as dimension
                FROM pg_attribute
                WHERE attrelid = 'cmis.knowledge_index'::regclass
                AND attname = 'embedding'
                LIMIT 1
            ");

            if (!empty($result) && $result[0]->dimension == 1536) {
                DB::statement("ALTER TABLE cmis.knowledge_index ALTER COLUMN embedding TYPE vector(768)");
                echo "✓ Updated cmis.knowledge_index.embedding to vector(768)\n";
            } else {
                echo "✓ cmis.knowledge_index.embedding already correct or doesn't exist\n";
            }
        } else {
            echo "✓ cmis.knowledge_index table doesn't exist yet (will be created with correct dimension)\n";
        }

        // Fix 2: Fix the embedding column in 2025_11_19_144828_create_missing_tables.php
        // We need to modify the migration file to use 768 instead of 1536
        $migrationPath = database_path('migrations/2025_11_19_144828_create_missing_tables.php');
        if (file_exists($migrationPath)) {
            $content = file_get_contents($migrationPath);
            $updated = str_replace('embedding vector(1536)', 'embedding vector(768)', $content);

            if ($content !== $updated) {
                file_put_contents($migrationPath, $updated);
                echo "✓ Fixed 2025_11_19_144828_create_missing_tables.php to use vector(768)\n";
            }
        }

        // Fix 3: Fix the other migration file as well
        $migrationPath2 = database_path('migrations/2025_11_19_151700_create_final_missing_tables.php');
        if (file_exists($migrationPath2)) {
            $content = file_get_contents($migrationPath2);
            $updated = str_replace('embedding vector(1536)', 'embedding vector(768)', $content);

            if ($content !== $updated) {
                file_put_contents($migrationPath2, $updated);
                echo "✓ Fixed 2025_11_19_151700_create_final_missing_tables.php to use vector(768)\n";
            }
        }

        echo "\n✅ All pgvector dimension fixes applied successfully!\n";
        echo "Note: Gemini text-embedding-004 produces 768-dimensional vectors.\n";
    }

    public function down(): void
    {
        // Restore to 1536 dimensions if needed
        if (Schema::hasTable('cmis.knowledge_index')) {
            $result = DB::select("
                SELECT atttypmod as dimension
                FROM pg_attribute
                WHERE attrelid = 'cmis.knowledge_index'::regclass
                AND attname = 'embedding'
                LIMIT 1
            ");

            if (!empty($result) && $result[0]->dimension == 768) {
                DB::statement("ALTER TABLE cmis.knowledge_index ALTER COLUMN embedding TYPE vector(1536)");
            }
        }
    }
};
