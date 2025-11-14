<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: AI & Analytics
 *
 * Description: AI models, actions, cognitive analytics, performance metrics
 *
 * AI Agent Context: Depends on: campaigns, orgs. AI/ML features
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/domain_ai_analytics.sql'));
        
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
