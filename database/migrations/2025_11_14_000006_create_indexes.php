<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Indexes
 *
 * Description: Create all 171 performance indexes
 *
 * AI Agent Context: Indexes improve query performance. Run after tables and constraints.
 */
return new class extends Migration
{
    /**
     * Index creation should not run inside a single transaction; if one index
     * fails, we still want the remaining statements to execute and be logged.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/all_indexes.sql'));

        if (empty(trim($sql))) {
            return;
        }

        $statements = array_filter(
            explode("\n", $sql),
            fn($stmt) => !empty(trim($stmt)) && strpos(trim($stmt), 'CREATE') === 0
        );

        foreach ($statements as $statement) {
            try {
                DB::unprepared(trim($statement));
            } catch (\Exception $e) {
                \Log::warning("Index creation warning: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Indexes are dropped when tables are dropped
    }
};
