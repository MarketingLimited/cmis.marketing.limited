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
        // Add Foreign Key Constraints
        DB::statement('
            -- ad_campaigns foreign keys
            ALTER TABLE cmis.ad_campaigns
                DROP CONSTRAINT IF EXISTS fk_ad_campaigns_org CASCADE;

            ALTER TABLE cmis.ad_campaigns
                ADD CONSTRAINT fk_ad_campaigns_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_campaigns
                DROP CONSTRAINT IF EXISTS fk_ad_campaigns_integration CASCADE;

            ALTER TABLE cmis.ad_campaigns
                ADD CONSTRAINT fk_ad_campaigns_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            -- ad_accounts foreign keys
            ALTER TABLE cmis.ad_accounts
                DROP CONSTRAINT IF EXISTS fk_ad_accounts_org CASCADE;

            ALTER TABLE cmis.ad_accounts
                ADD CONSTRAINT fk_ad_accounts_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_accounts
                DROP CONSTRAINT IF EXISTS fk_ad_accounts_integration CASCADE;

            ALTER TABLE cmis.ad_accounts
                ADD CONSTRAINT fk_ad_accounts_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            -- ad_sets foreign keys
            ALTER TABLE cmis.ad_sets
                DROP CONSTRAINT IF EXISTS fk_ad_sets_org CASCADE;

            ALTER TABLE cmis.ad_sets
                ADD CONSTRAINT fk_ad_sets_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_sets
                DROP CONSTRAINT IF EXISTS fk_ad_sets_integration CASCADE;

            ALTER TABLE cmis.ad_sets
                ADD CONSTRAINT fk_ad_sets_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_sets
                DROP CONSTRAINT IF EXISTS fk_ad_sets_campaign CASCADE;

            ALTER TABLE cmis.ad_sets
                ADD CONSTRAINT fk_ad_sets_campaign
                FOREIGN KEY (campaign_external_id)
                REFERENCES cmis.ad_campaigns(campaign_external_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            -- ad_entities foreign keys
            ALTER TABLE cmis.ad_entities
                DROP CONSTRAINT IF EXISTS fk_ad_entities_org CASCADE;

            ALTER TABLE cmis.ad_entities
                ADD CONSTRAINT fk_ad_entities_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_entities
                DROP CONSTRAINT IF EXISTS fk_ad_entities_integration CASCADE;

            ALTER TABLE cmis.ad_entities
                ADD CONSTRAINT fk_ad_entities_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_entities
                DROP CONSTRAINT IF EXISTS fk_ad_entities_adset CASCADE;

            ALTER TABLE cmis.ad_entities
                ADD CONSTRAINT fk_ad_entities_adset
                FOREIGN KEY (adset_external_id)
                REFERENCES cmis.ad_sets(adset_external_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            -- ad_metrics foreign keys
            ALTER TABLE cmis.ad_metrics
                DROP CONSTRAINT IF EXISTS fk_ad_metrics_org CASCADE;

            ALTER TABLE cmis.ad_metrics
                ADD CONSTRAINT fk_ad_metrics_org
                FOREIGN KEY (org_id)
                REFERENCES cmis.orgs(org_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        DB::statement('
            ALTER TABLE cmis.ad_metrics
                DROP CONSTRAINT IF EXISTS fk_ad_metrics_integration CASCADE;

            ALTER TABLE cmis.ad_metrics
                ADD CONSTRAINT fk_ad_metrics_integration
                FOREIGN KEY (integration_id)
                REFERENCES cmis.integrations(integration_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
        ');

        // Add Performance Indexes
        DB::statement('
            -- ad_campaigns indexes
            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_org_status
                ON cmis.ad_campaigns(org_id, status)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_integration
                ON cmis.ad_campaigns(integration_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_org_created
                ON cmis.ad_campaigns(org_id, created_at DESC)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_external_id
                ON cmis.ad_campaigns(campaign_external_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_provider_status
                ON cmis.ad_campaigns(provider, status)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_dates
                ON cmis.ad_campaigns(start_date, end_date)
                WHERE deleted_at IS NULL;

            -- Composite index for common queries
            CREATE INDEX IF NOT EXISTS idx_ad_campaigns_lookup
                ON cmis.ad_campaigns(org_id, integration_id, status, created_at DESC)
                WHERE deleted_at IS NULL;
        ');

        DB::statement('
            -- ad_accounts indexes
            CREATE INDEX IF NOT EXISTS idx_ad_accounts_org
                ON cmis.ad_accounts(org_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_accounts_integration
                ON cmis.ad_accounts(integration_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_accounts_external_id
                ON cmis.ad_accounts(account_external_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_accounts_status
                ON cmis.ad_accounts(status)
                WHERE deleted_at IS NULL;
        ');

        DB::statement('
            -- ad_sets indexes
            CREATE INDEX IF NOT EXISTS idx_ad_sets_org
                ON cmis.ad_sets(org_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_sets_integration
                ON cmis.ad_sets(integration_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_sets_campaign
                ON cmis.ad_sets(campaign_external_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_sets_external_id
                ON cmis.ad_sets(adset_external_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_sets_status
                ON cmis.ad_sets(status)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_sets_dates
                ON cmis.ad_sets(start_date, end_date)
                WHERE deleted_at IS NULL;
        ');

        DB::statement('
            -- ad_entities indexes
            CREATE INDEX IF NOT EXISTS idx_ad_entities_org
                ON cmis.ad_entities(org_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_entities_integration
                ON cmis.ad_entities(integration_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_entities_adset
                ON cmis.ad_entities(adset_external_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_entities_external_id
                ON cmis.ad_entities(ad_external_id)
                WHERE deleted_at IS NULL;

            CREATE INDEX IF NOT EXISTS idx_ad_entities_status
                ON cmis.ad_entities(status)
                WHERE deleted_at IS NULL;
        ');

        DB::statement('
            -- ad_metrics indexes (critical for performance)
            CREATE INDEX IF NOT EXISTS idx_ad_metrics_org_date
                ON cmis.ad_metrics(org_id, date_start, date_stop);

            CREATE INDEX IF NOT EXISTS idx_ad_metrics_integration_date
                ON cmis.ad_metrics(integration_id, date_start);

            CREATE INDEX IF NOT EXISTS idx_ad_metrics_entity_date
                ON cmis.ad_metrics(entity_level, entity_external_id, date_start DESC);

            -- Composite index for aggregation queries
            CREATE INDEX IF NOT EXISTS idx_ad_metrics_aggregation
                ON cmis.ad_metrics(org_id, entity_level, date_start, date_stop)
                WHERE deleted_at IS NULL;
        ');

        // Add unique constraints
        DB::statement('
            -- Unique constraints to prevent duplicate records
            CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_campaigns_external
                ON cmis.ad_campaigns(org_id, integration_id, campaign_external_id)
                WHERE deleted_at IS NULL;

            CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_accounts_external
                ON cmis.ad_accounts(org_id, integration_id, account_external_id)
                WHERE deleted_at IS NULL;

            CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_sets_external
                ON cmis.ad_sets(org_id, integration_id, adset_external_id)
                WHERE deleted_at IS NULL;

            CREATE UNIQUE INDEX IF NOT EXISTS uniq_ad_entities_external
                ON cmis.ad_entities(org_id, integration_id, ad_external_id)
                WHERE deleted_at IS NULL;
        ');
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
    }
};
