<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Core Identity & Access Control
 *
 * Description: Users, orgs, roles, permissions, and their relationships
 *
 * AI Agent Context: Foundation for multi-tenancy and RBAC. All other domains depend on this.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/domain_core_identity.sql'));
        
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
