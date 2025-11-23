<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add Foreign Key Constraints and Performance Indexes to Ad Platform Tables
     */
    public function up(): void
    {
        // Primary key constraints are already added by migration 2025_11_14_000005_create_all_alters_and_constraints
        // Skip adding them again to avoid "multiple primary keys" errors

        // Check and add orgs primary key only if truly missing (defensive check)
        try {
            $constraintExists = DB::selectOne("
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE constraint_schema = 'cmis'
                AND table_name = 'orgs'
                AND constraint_type = 'PRIMARY KEY'
                LIMIT 1
            ");

            if (!$constraintExists) {
                DB::statement('ALTER TABLE cmis.orgs ADD CONSTRAINT orgs_pkey PRIMARY KEY (org_id)');
            }
        } catch (\Exception $e) {
            // If constraint already exists, ignore
            if (!str_contains($e->getMessage(), 'already exists') &&
                !str_contains($e->getMessage(), 'multiple primary keys')) {
                throw $e;
            }
        }

        // Check and add integrations primary key only if truly missing (defensive check)
        try {
            $constraintExists = DB::selectOne("
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE constraint_schema = 'cmis'
                AND table_name = 'integrations'
                AND constraint_type = 'PRIMARY KEY'
                LIMIT 1
            ");

            if (!$constraintExists) {
                DB::statement('ALTER TABLE cmis.integrations ADD CONSTRAINT integrations_pkey PRIMARY KEY (integration_id)');
            }
        } catch (\Exception $e) {
            // If constraint already exists, ignore
            if (!str_contains($e->getMessage(), 'already exists') &&
                !str_contains($e->getMessage(), 'multiple primary keys')) {
                throw $e;
            }
        }

        // Ensure ad_campaigns table has constraint on campaign_external_id (for foreign key relationships)
        try {
            $constraintExists = DB::selectOne("
                SELECT kcu.constraint_name
                FROM information_schema.key_column_usage kcu
                JOIN information_schema.table_constraints tc
                    ON kcu.constraint_name = tc.constraint_name
                    AND kcu.constraint_schema = tc.constraint_schema
                WHERE kcu.constraint_schema = 'cmis'
                AND kcu.table_name = 'ad_campaigns'
                AND kcu.column_name = 'campaign_external_id'
                AND tc.constraint_type IN ('PRIMARY KEY', 'UNIQUE')
                LIMIT 1
            ");

            if (!$constraintExists) {
                DB::statement('ALTER TABLE cmis.ad_campaigns ADD CONSTRAINT ad_campaigns_campaign_external_id_key UNIQUE (campaign_external_id)');
            }
        } catch (\Exception $e) {
            // If constraint already exists, ignore
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        // Ensure ad_sets table has constraint on adset_external_id (for foreign key relationships)
        try {
            $constraintExists = DB::selectOne("
                SELECT kcu.constraint_name
                FROM information_schema.key_column_usage kcu
                JOIN information_schema.table_constraints tc
                    ON kcu.constraint_name = tc.constraint_name
                    AND kcu.constraint_schema = tc.constraint_schema
                WHERE kcu.constraint_schema = 'cmis'
                AND kcu.table_name = 'ad_sets'
                AND kcu.column_name = 'adset_external_id'
                AND tc.constraint_type IN ('PRIMARY KEY', 'UNIQUE')
                LIMIT 1
            ");

            if (!$constraintExists) {
                DB::statement('ALTER TABLE cmis.ad_sets ADD CONSTRAINT ad_sets_adset_external_id_key UNIQUE (adset_external_id)');
            }
        } catch (\Exception $e) {
            // If constraint already exists, ignore
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }

        // Add Foreign Key Constraints

        // ad_campaigns foreign keys
        DB::statement('ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS fk_ad_campaigns_org CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_campaigns
                ADD CONSTRAINT fk_ad_campaigns_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS fk_ad_campaigns_integration CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_campaigns
                ADD CONSTRAINT fk_ad_campaigns_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        // ad_accounts foreign keys
        DB::statement('ALTER TABLE cmis.ad_accounts DROP CONSTRAINT IF EXISTS fk_ad_accounts_org CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_accounts
                ADD CONSTRAINT fk_ad_accounts_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_accounts DROP CONSTRAINT IF EXISTS fk_ad_accounts_integration CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_accounts
                ADD CONSTRAINT fk_ad_accounts_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        // ad_sets foreign keys
        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS fk_ad_sets_org CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_sets
                ADD CONSTRAINT fk_ad_sets_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS fk_ad_sets_integration CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_sets
                ADD CONSTRAINT fk_ad_sets_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS fk_ad_sets_campaign CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_sets
                ADD CONSTRAINT fk_ad_sets_campaign
                FOREIGN KEY (campaign_external_id)
                REFERENCES cmis.ad_campaigns(campaign_external_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        // ad_entities foreign keys
        DB::statement('ALTER TABLE cmis.ad_entities DROP CONSTRAINT IF EXISTS fk_ad_entities_org CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_entities
                ADD CONSTRAINT fk_ad_entities_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_entities DROP CONSTRAINT IF EXISTS fk_ad_entities_integration CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_entities
                ADD CONSTRAINT fk_ad_entities_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_entities DROP CONSTRAINT IF EXISTS fk_ad_entities_adset CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_entities
                ADD CONSTRAINT fk_ad_entities_adset
                FOREIGN KEY (adset_external_id)
                REFERENCES cmis.ad_sets(adset_external_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        // ad_metrics foreign keys
        DB::statement('ALTER TABLE cmis.ad_metrics DROP CONSTRAINT IF EXISTS fk_ad_metrics_org CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_metrics
                ADD CONSTRAINT fk_ad_metrics_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        DB::statement('ALTER TABLE cmis.ad_metrics DROP CONSTRAINT IF EXISTS fk_ad_metrics_integration CASCADE');
        DB::statement('
            ALTER TABLE cmis.ad_metrics
                ADD CONSTRAINT fk_ad_metrics_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        // Add Performance Indexes

        // ad_campaigns indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_org_status ON cmis.ad_campaigns(org_id, status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_integration ON cmis.ad_campaigns(integration_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_org_created ON cmis.ad_campaigns(org_id, created_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_external_id ON cmis.ad_campaigns(campaign_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_provider_status ON cmis.ad_campaigns(provider, status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_dates ON cmis.ad_campaigns(start_date, end_date) WHERE deleted_at IS NULL');
        // Composite index for common queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_lookup ON cmis.ad_campaigns(org_id, integration_id, status, created_at DESC) WHERE deleted_at IS NULL');

        // ad_accounts indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_accounts_org ON cmis.ad_accounts(org_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_accounts_integration ON cmis.ad_accounts(integration_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_accounts_external_id ON cmis.ad_accounts(account_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_accounts_status ON cmis.ad_accounts(status) WHERE deleted_at IS NULL');

        // ad_sets indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_sets_org ON cmis.ad_sets(org_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_sets_integration ON cmis.ad_sets(integration_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_sets_campaign ON cmis.ad_sets(campaign_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_sets_external_id ON cmis.ad_sets(adset_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_sets_status ON cmis.ad_sets(status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_sets_dates ON cmis.ad_sets(start_date, end_date) WHERE deleted_at IS NULL');

        // ad_entities indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_entities_org ON cmis.ad_entities(org_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_entities_integration ON cmis.ad_entities(integration_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_entities_adset ON cmis.ad_entities(adset_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_entities_external_id ON cmis.ad_entities(ad_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_entities_status ON cmis.ad_entities(status) WHERE deleted_at IS NULL');

        // ad_metrics indexes (critical for performance)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_metrics_org_date ON cmis.ad_metrics(org_id, date_start, date_stop)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_metrics_integration_date ON cmis.ad_metrics(integration_id, date_start)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_metrics_entity_date ON cmis.ad_metrics(entity_level, entity_external_id, date_start DESC)');
        // Composite index for aggregation queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_metrics_aggregation ON cmis.ad_metrics(org_id, entity_level, date_start, date_stop) WHERE deleted_at IS NULL');

        // Add unique constraints
        // Unique constraints to prevent duplicate records
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_campaigns_external ON cmis.ad_campaigns(org_id, integration_id, campaign_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_accounts_external ON cmis.ad_accounts(org_id, integration_id, account_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_sets_external ON cmis.ad_sets(org_id, integration_id, adset_external_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_entities_external ON cmis.ad_entities(org_id, integration_id, ad_external_id) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Foreign Keys
        DB::statement('ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS fk_ad_campaigns_org CASCADE');
        DB::statement('ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS fk_ad_campaigns_integration CASCADE');
        DB::statement('ALTER TABLE cmis.ad_accounts DROP CONSTRAINT IF EXISTS fk_ad_accounts_org CASCADE');
        DB::statement('ALTER TABLE cmis.ad_accounts DROP CONSTRAINT IF EXISTS fk_ad_accounts_integration CASCADE');
        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS fk_ad_sets_org CASCADE');
        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS fk_ad_sets_integration CASCADE');
        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS fk_ad_sets_campaign CASCADE');
        DB::statement('ALTER TABLE cmis.ad_entities DROP CONSTRAINT IF EXISTS fk_ad_entities_org CASCADE');
        DB::statement('ALTER TABLE cmis.ad_entities DROP CONSTRAINT IF EXISTS fk_ad_entities_integration CASCADE');
        DB::statement('ALTER TABLE cmis.ad_entities DROP CONSTRAINT IF EXISTS fk_ad_entities_adset CASCADE');
        DB::statement('ALTER TABLE cmis.ad_metrics DROP CONSTRAINT IF EXISTS fk_ad_metrics_org CASCADE');
        DB::statement('ALTER TABLE cmis.ad_metrics DROP CONSTRAINT IF EXISTS fk_ad_metrics_integration CASCADE');

        // Drop Indexes
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_org_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_integration');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_org_created');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_external_id');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_provider_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_dates');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_lookup');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_accounts_org');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_accounts_integration');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_accounts_external_id');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_accounts_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_sets_org');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_sets_integration');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_sets_campaign');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_sets_external_id');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_sets_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_sets_dates');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_entities_org');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_entities_integration');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_entities_adset');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_entities_external_id');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_entities_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_metrics_org_date');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_metrics_integration_date');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_metrics_entity_date');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_metrics_aggregation');
        DB::statement('DROP INDEX IF EXISTS cmis.uniq_ad_campaigns_external');
        DB::statement('DROP INDEX IF EXISTS cmis.uniq_ad_accounts_external');
        DB::statement('DROP INDEX IF EXISTS cmis.uniq_ad_sets_external');
        DB::statement('DROP INDEX IF EXISTS cmis.uniq_ad_entities_external');

        // Drop unique constraints we may have added
        DB::statement('ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS ad_campaigns_campaign_external_id_key CASCADE');
        DB::statement('ALTER TABLE cmis.ad_sets DROP CONSTRAINT IF EXISTS ad_sets_adset_external_id_key CASCADE');
    }
};