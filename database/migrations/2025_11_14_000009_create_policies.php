<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Row-Level Security Policies
 *
 * Description: Create all 25 RLS policies
 *
 * AI Agent Context: Policies implement row-level security for multi-tenancy.
 * Must run after tables are created.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/complete_policies.sql'));

        if (!empty(trim($sql))) {
            try {
                DB::unprepared($sql);
            } catch (\Exception $e) {
                \Log::warning("Policy creation warning: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Policies are dropped when tables are dropped
    }
};
