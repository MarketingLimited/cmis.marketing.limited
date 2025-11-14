<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Dynamic Fields & Metadata
 *
 * Description: Field definitions, values, aliases, metadata, modules
 *
 * AI Agent Context: Meta-model for dynamic content fields
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/domain_fields_metadata.sql'));
        
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
