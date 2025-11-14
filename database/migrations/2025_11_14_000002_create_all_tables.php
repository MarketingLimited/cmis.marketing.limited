<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: All Database Tables
 *
 * Description: Creates all 189 tables from database/schema.sql
 *
 * AI Agent Context: This creates the complete table structure.
 * All tables are created without constraints/indexes for dependency-free creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/complete_tables.sql'));
        
        if (!empty(trim($sql))) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        // Tables will be dropped when schemas are dropped
    }
};
