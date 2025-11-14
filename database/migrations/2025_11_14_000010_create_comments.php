<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Comments & Documentation
 *
 * Description: Add all 55 comments to database objects
 *
 * AI Agent Context: Comments provide documentation for tables, columns, and functions.
 * Should run last after all objects are created.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/complete_comments.sql'));

        if (!empty(trim($sql))) {
            try {
                DB::unprepared($sql);
            } catch (\Exception $e) {
                \Log::warning("Comment creation warning: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Comments are dropped when objects are dropped
    }
};
