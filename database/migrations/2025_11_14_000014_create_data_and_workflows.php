<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Data Management & Workflows
 *
 * Description: Data feeds, datasets, flows, SQL snippets, org mappings
 *
 * AI Agent Context: ETL and workflow automation
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/domain_data_workflows.sql'));
        
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
