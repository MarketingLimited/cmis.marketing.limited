<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Advertising Platforms
 *
 * Description: Ad accounts, campaigns, ad sets, entities, metrics, audiences
 *
 * AI Agent Context: Depends on: integrations, orgs. Meta/Google Ads integration
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/domain_ad_platforms.sql'));
        
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
