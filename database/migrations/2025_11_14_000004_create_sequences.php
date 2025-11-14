<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Sequences
 *
 * Description: Create all 30 sequences for auto-incrementing columns
 *
 * AI Agent Context: Sequences must be created before constraints that reference them
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/all_sequences.sql'));

        if (!empty(trim($sql))) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        // Sequences are dropped when tables are dropped
    }
};
