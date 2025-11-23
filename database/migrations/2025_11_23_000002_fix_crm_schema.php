<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix CRM schema by adding missing columns, foreign keys, and indexes
 *
 * This migration addresses schema mismatches between models, tests, and the database.
 * It adds all expected fields to align with test expectations and business requirements.
 */
class FixCrmSchema extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix contacts table
        Schema::table('cmis.contacts', function (Blueprint $table) {
            // Add name fields (splitting full name into first/last)
            $table->string('first_name', 255)->nullable()->after('name');
            $table->string('last_name', 255)->nullable()->after('first_name');

            // Add source tracking
            $table->string('source', 100)->nullable()->after('company');

            // Add JSONB fields for flexible data
            $table->jsonb('segments')->nullable()->after('metadata');
            $table->jsonb('custom_fields')->nullable()->after('segments');
            $table->jsonb('social_profiles')->nullable()->after('custom_fields');

            // Add subscription and engagement tracking
            $table->boolean('is_subscribed')->default(true)->after('social_profiles');
            $table->timestamp('last_engaged_at')->nullable()->after('is_subscribed');

            // Add soft deletes
            $table->softDeletes()->after('updated_at');
        });

        // Fix leads table
        Schema::table('cmis.leads', function (Blueprint $table) {
            // Add campaign relationship
            $table->uuid('campaign_id')->nullable()->after('org_id');

            // Add lead scoring
            $table->integer('score')->default(0)->after('status');

            // Add flexible data fields
            $table->jsonb('additional_data')->nullable()->after('metadata');
            $table->jsonb('utm_parameters')->nullable()->after('additional_data');

            // Add lead value and assignment
            $table->decimal('estimated_value', 12, 2)->nullable()->after('utm_parameters');
            $table->uuid('assigned_to')->nullable()->after('estimated_value');

            // Add lifecycle timestamps
            $table->timestamp('last_contacted_at')->nullable()->after('assigned_to');
            $table->timestamp('converted_at')->nullable()->after('last_contacted_at');

            // Add soft deletes
            $table->softDeletes()->after('updated_at');
        });

        // Add foreign keys for leads table
        Schema::table('cmis.leads', function (Blueprint $table) {
            $table->foreign('org_id', 'fk_leads_org')
                  ->references('org_id')
                  ->on('cmis.orgs')
                  ->onDelete('cascade');

            $table->foreign('campaign_id', 'fk_leads_campaign')
                  ->references('campaign_id')
                  ->on('cmis.campaigns')
                  ->onDelete('set null');

            $table->foreign('assigned_to', 'fk_leads_assigned_user')
                  ->references('user_id')
                  ->on('cmis.users')
                  ->onDelete('set null');
        });

        // Add foreign keys for contacts table
        Schema::table('cmis.contacts', function (Blueprint $table) {
            $table->foreign('org_id', 'fk_contacts_org')
                  ->references('org_id')
                  ->on('cmis.orgs')
                  ->onDelete('cascade');
        });

        // Add performance indexes
        DB::statement('CREATE INDEX idx_contacts_org_id ON cmis.contacts(org_id)');
        DB::statement('CREATE INDEX idx_contacts_email ON cmis.contacts(email)');
        DB::statement('CREATE INDEX idx_contacts_source ON cmis.contacts(source)');
        DB::statement('CREATE INDEX idx_contacts_is_subscribed ON cmis.contacts(is_subscribed)');
        DB::statement('CREATE INDEX idx_contacts_deleted_at ON cmis.contacts(deleted_at)');

        DB::statement('CREATE INDEX idx_leads_org_id ON cmis.leads(org_id)');
        DB::statement('CREATE INDEX idx_leads_campaign_id ON cmis.leads(campaign_id)');
        DB::statement('CREATE INDEX idx_leads_status ON cmis.leads(status)');
        DB::statement('CREATE INDEX idx_leads_email ON cmis.leads(email)');
        DB::statement('CREATE INDEX idx_leads_score ON cmis.leads(score)');
        DB::statement('CREATE INDEX idx_leads_assigned_to ON cmis.leads(assigned_to)');
        DB::statement('CREATE INDEX idx_leads_deleted_at ON cmis.leads(deleted_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for leads
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_deleted_at');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_assigned_to');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_score');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_email');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_campaign_id');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_leads_org_id');

        // Drop indexes for contacts
        DB::statement('DROP INDEX IF EXISTS cmis.idx_contacts_deleted_at');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_contacts_is_subscribed');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_contacts_source');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_contacts_email');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_contacts_org_id');

        // Drop foreign keys for contacts
        Schema::table('cmis.contacts', function (Blueprint $table) {
            $table->dropForeign('fk_contacts_org');
        });

        // Drop foreign keys for leads
        Schema::table('cmis.leads', function (Blueprint $table) {
            $table->dropForeign('fk_leads_assigned_user');
            $table->dropForeign('fk_leads_campaign');
            $table->dropForeign('fk_leads_org');
        });

        // Remove added columns from leads table
        Schema::table('cmis.leads', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'converted_at',
                'last_contacted_at',
                'assigned_to',
                'estimated_value',
                'utm_parameters',
                'additional_data',
                'score',
                'campaign_id',
            ]);
        });

        // Remove added columns from contacts table
        Schema::table('cmis.contacts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'last_engaged_at',
                'is_subscribed',
                'social_profiles',
                'custom_fields',
                'segments',
                'source',
                'last_name',
                'first_name',
            ]);
        });
    }
}
