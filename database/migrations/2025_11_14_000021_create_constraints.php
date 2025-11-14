<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Constraints
 *
 * Description: Add all primary keys, foreign keys, unique constraints, and check constraints
 *
 * AI Agent Context: Constraints enforce data integrity. Must run after all tables are created.
 * Foreign keys create dependencies between tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/all_constraints.sql'));

        if (empty(trim($sql))) {
            return;
        }

        // Split by semicolon and execute each constraint separately
        // This allows partial success and better error reporting
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && strpos($stmt, 'ALTER TABLE') === 0
        );

        $errors = [];
        foreach ($statements as $statement) {
            try {
                DB::unprepared($statement . ';');
            } catch (\Exception $e) {
                // Log but continue - some constraints may already exist
                $tableName = preg_match('/ALTER TABLE ONLY (\S+)/', $statement, $matches)
                    ? $matches[1]
                    : 'unknown';
                $errors[] = "Table {$tableName}: " . substr($e->getMessage(), 0, 100);
                \Log::warning("Constraint creation warning: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            \Log::info("Constraint migration completed with " . count($errors) . " warnings");
        }
    }

    public function down(): void
    {
        // Constraints are dropped when tables are dropped
    }
};
