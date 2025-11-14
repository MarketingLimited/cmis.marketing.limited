<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Offerings & Export Bundles
 *
 * Description: Product offerings, bundles, export packages
 *
 * AI Agent Context: Product catalog and export management
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/domain_offerings.sql'));
        
        if (!empty(trim($sql))) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        // Tables will be dropped when schemas are dropped
        // Individual table drops can be added here if needed
    }
};
