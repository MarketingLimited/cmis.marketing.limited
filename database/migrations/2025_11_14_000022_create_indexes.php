<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Indexes
 *
 * Description: Create all performance indexes on tables
 *
 * AI Agent Context: Indexes improve query performance. Run after tables and constraints.
 * Consider adding indexes when: filtering, sorting, or joining on columns frequently.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/all_indexes.sql'));

        if (empty(trim($sql))) {
            return;
        }

        // Split by newline and execute each index separately
        $statements = array_filter(
            explode("\n", $sql),
            fn($stmt) => !empty(trim($stmt)) && strpos(trim($stmt), 'CREATE') === 0
        );

        $errors = [];
        foreach ($statements as $statement) {
            try {
                DB::unprepared(trim($statement));
            } catch (\Exception $e) {
                // Log but continue - some indexes may already exist
                $indexName = preg_match('/CREATE.*INDEX (\S+)/', $statement, $matches)
                    ? $matches[1]
                    : 'unknown';
                $errors[] = "Index {$indexName}: " . substr($e->getMessage(), 0, 100);
                \Log::warning("Index creation warning: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            \Log::info("Index migration completed with " . count($errors) . " warnings");
        }
    }

    public function down(): void
    {
        // Indexes are dropped when tables are dropped
    }
};
