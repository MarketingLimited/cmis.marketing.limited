<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds all indexes, primary keys, foreign keys, and constraints
     * to the database tables created in the previous migration.
     */
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/constraints_and_indexes.sql'));

        // Split by semicolons and execute each statement separately
        // This helps with error handling
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && $stmt !== '--'
        );

        foreach ($statements as $statement) {
            if (empty(trim($statement))) {
                continue;
            }

            try {
                DB::unprepared($statement . ';');
            } catch (\Exception $e) {
                // Log constraint errors but continue
                // Some constraints may already exist or conflict
                \Log::warning("Constraint/Index creation warning: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Constraints and indexes are dropped automatically when tables are dropped
        // No explicit action needed here
    }
};
