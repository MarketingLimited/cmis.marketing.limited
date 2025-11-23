<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Concerns\HasRLSPolicies;

/**
 * Add Row-Level Security (RLS) policies to CRM tables
 *
 * CRITICAL: This migration fixes a security vulnerability where contacts and leads
 * tables were not protected by RLS policies, allowing cross-organization data access.
 */
class AddRlsToCrmTables extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable RLS on contacts table
        $this->enableRLS('cmis.contacts');

        // Enable RLS on leads table
        $this->enableRLS('cmis.leads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable RLS on leads table
        $this->disableRLS('cmis.leads');

        // Disable RLS on contacts table
        $this->disableRLS('cmis.contacts');
    }
}
