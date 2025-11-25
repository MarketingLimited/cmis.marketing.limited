<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds comprehensive platform-specific fields for Meta, Google, TikTok, Snapchat, X, LinkedIn
     */
    public function up(): void
    {
        try {
            // Ensure campaigns table has a primary key (should already exist, but verify)
            DB::statement('
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint
                        WHERE conname = \'campaigns_pkey\'
                        AND conrelid = \'cmis.campaigns\'::regclass
                    ) THEN
                        ALTER TABLE cmis.campaigns ADD PRIMARY KEY (campaign_id);
                    END IF;
                END $$;
            ');
        } catch (\Exception $e) {
            \Log::error('Error checking campaigns primary key: ' . $e->getMessage());
            throw $e;
        }

        // Add new columns to campaigns table using raw SQL (Laravel Schema doesn't handle qualified names well)
        $columns = [
            "ADD COLUMN IF NOT EXISTS platform varchar(50)",
            "ADD COLUMN IF NOT EXISTS campaign_type varchar(100)",
            "ADD COLUMN IF NOT EXISTS buying_type varchar(50) DEFAULT 'AUCTION'",
            "ADD COLUMN IF NOT EXISTS budget_type varchar(20) DEFAULT 'daily'",
            "ADD COLUMN IF NOT EXISTS daily_budget decimal(12,2)",
            "ADD COLUMN IF NOT EXISTS lifetime_budget decimal(12,2)",
            "ADD COLUMN IF NOT EXISTS bid_strategy varchar(50) DEFAULT 'lowest_cost'",
            "ADD COLUMN IF NOT EXISTS bid_amount decimal(12,4)",
            "ADD COLUMN IF NOT EXISTS optimization_goal varchar(100)",
            "ADD COLUMN IF NOT EXISTS is_advantage_plus boolean DEFAULT false",
            "ADD COLUMN IF NOT EXISTS is_smart_campaign boolean DEFAULT false",
            "ADD COLUMN IF NOT EXISTS is_performance_max boolean DEFAULT false",
            "ADD COLUMN IF NOT EXISTS attribution_spec varchar(100)",
            "ADD COLUMN IF NOT EXISTS external_campaign_id varchar(255)",
            "ADD COLUMN IF NOT EXISTS last_synced_at timestamp with time zone",
            "ADD COLUMN IF NOT EXISTS sync_status varchar(20) DEFAULT 'pending'",
            "ADD COLUMN IF NOT EXISTS platform_settings jsonb",
            "ADD COLUMN IF NOT EXISTS targeting_summary jsonb"
        ];

        foreach ($columns as $column) {
            try {
                DB::statement("ALTER TABLE cmis.campaigns {$column}");
            } catch (\Exception $e) {
                \Log::error("Error adding column {$column}: " . $e->getMessage());
                throw $e;
            }
        }

        // Add comments
        DB::statement("COMMENT ON COLUMN cmis.campaigns.platform IS 'meta, google, tiktok, snapchat, twitter, linkedin'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.campaign_type IS 'awareness, traffic, engagement, leads, conversions, app_installs, video_views, reach, sales'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.buying_type IS 'AUCTION, RESERVED, FIXED_PRICE'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.budget_type IS 'daily, lifetime'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.bid_strategy IS 'lowest_cost, cost_cap, bid_cap, target_cost, manual'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.optimization_goal IS 'LINK_CLICKS, LANDING_PAGE_VIEWS, IMPRESSIONS, REACH, CONVERSIONS, etc.'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.is_advantage_plus IS 'Meta Advantage+ campaigns'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.is_smart_campaign IS 'TikTok Smart+, Google Smart campaigns'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.is_performance_max IS 'Google Performance Max'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.attribution_spec IS '7d_click_1d_view, 28d_click, etc.'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.external_campaign_id IS 'Campaign ID from ad platform'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.sync_status IS 'pending, synced, error, not_synced'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.platform_settings IS 'Platform-specific settings JSON'");
        DB::statement("COMMENT ON COLUMN cmis.campaigns.targeting_summary IS 'Summary of targeting settings'");

        // Create campaign_ad_sets table using raw SQL to avoid Laravel schema builder FK issues
        \Log::info('Creating campaign_ad_sets table');
        try {
            DB::statement("
                CREATE TABLE IF NOT EXISTS cmis.campaign_ad_sets (
                ad_set_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id uuid NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                campaign_id uuid NOT NULL REFERENCES cmis.campaigns(campaign_id) ON DELETE CASCADE,

                -- Basic info
                name varchar(255) NOT NULL,
                description text,
                status varchar(20) NOT NULL DEFAULT 'draft',

                -- Budget & Bidding
                budget_type varchar(20) DEFAULT 'daily',
                daily_budget decimal(12,2),
                lifetime_budget decimal(12,2),
                bid_strategy varchar(50),
                bid_amount decimal(12,4),
                billing_event varchar(50),

                -- Schedule
                start_time timestamp with time zone,
                end_time timestamp with time zone,
                schedule jsonb,

                -- Optimization
                optimization_goal varchar(100),
                conversion_event varchar(100),
                pixel_id varchar(255),
                app_id varchar(255),

                -- Targeting
                targeting jsonb,
                locations jsonb,
                age_range jsonb,
                genders jsonb,
                interests jsonb,
                behaviors jsonb,
                custom_audiences jsonb,
                lookalike_audiences jsonb,
                excluded_audiences jsonb,

                -- Placements
                placements jsonb,
                automatic_placements boolean DEFAULT true,

                -- Device targeting
                device_platforms jsonb,
                publisher_platforms jsonb,

                -- External sync
                external_ad_set_id varchar(255),
                last_synced_at timestamp with time zone,
                sync_status varchar(20) DEFAULT 'pending',

                -- Platform-specific
                platform_settings jsonb,

                -- Timestamps
                created_by uuid REFERENCES cmis.users(user_id) ON DELETE SET NULL,
                created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
                deleted_at timestamp with time zone,
                deleted_by uuid
            )
            ");
            \Log::info('campaign_ad_sets table created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating campaign_ad_sets table: ' . $e->getMessage());
            throw $e;
        }

        // Add indexes
        \Log::info('Adding indexes to campaign_ad_sets');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ad_sets_org_campaign ON cmis.campaign_ad_sets(org_id, campaign_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ad_sets_org_status ON cmis.campaign_ad_sets(org_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ad_sets_external ON cmis.campaign_ad_sets(external_ad_set_id)');

        // Create campaign_ads table using raw SQL
        \Log::info('Creating campaign_ads table');
        try {
            DB::statement("
                CREATE TABLE IF NOT EXISTS cmis.campaign_ads (
                ad_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id uuid NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                campaign_id uuid NOT NULL REFERENCES cmis.campaigns(campaign_id) ON DELETE CASCADE,
                ad_set_id uuid NOT NULL REFERENCES cmis.campaign_ad_sets(ad_set_id) ON DELETE CASCADE,

                -- Basic info
                name varchar(255) NOT NULL,
                description text,
                status varchar(20) NOT NULL DEFAULT 'draft',

                -- Ad format
                ad_format varchar(50),

                -- Creative content
                primary_text text,
                headline varchar(255),
                description_text text,
                call_to_action varchar(50),

                -- Media
                media jsonb,
                image_url varchar(2048),
                video_url varchar(2048),
                thumbnail_url varchar(2048),

                -- Carousel specific
                carousel_cards jsonb,

                -- Link settings
                destination_url varchar(2048),
                display_url varchar(255),
                url_parameters jsonb,

                -- Tracking
                tracking_pixel_id varchar(255),
                tracking_specs jsonb,

                -- Dynamic creative
                is_dynamic_creative boolean DEFAULT false,
                dynamic_creative_assets jsonb,

                -- External sync
                external_ad_id varchar(255),
                external_creative_id varchar(255),
                last_synced_at timestamp with time zone,
                sync_status varchar(20) DEFAULT 'pending',

                -- Review status
                review_status varchar(50),
                review_feedback text,

                -- Platform-specific
                platform_settings jsonb,

                -- Performance preview
                preview_urls jsonb,

                -- Timestamps
                created_by uuid REFERENCES cmis.users(user_id) ON DELETE SET NULL,
                created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
                deleted_at timestamp with time zone,
                deleted_by uuid
            )
            ");
            \Log::info('campaign_ads table created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating campaign_ads table: ' . $e->getMessage());
            throw $e;
        }

        // Add indexes for campaign_ads
        \Log::info('Adding indexes to campaign_ads');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ads_org_campaign ON cmis.campaign_ads(org_id, campaign_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ads_org_ad_set ON cmis.campaign_ads(org_id, ad_set_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ads_org_status ON cmis.campaign_ads(org_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_ads_external ON cmis.campaign_ads(external_ad_id)');

        // Enable RLS on new tables
        DB::statement('ALTER TABLE cmis.campaign_ad_sets ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.campaign_ads ENABLE ROW LEVEL SECURITY');

        // Create RLS policies for campaign_ad_sets
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_select ON cmis.campaign_ad_sets');
        DB::statement("
            CREATE POLICY rls_campaign_ad_sets_select ON cmis.campaign_ad_sets
            FOR SELECT USING (
                (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
                AND org_id = cmis.get_current_org_id()
            )
        ");

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_insert ON cmis.campaign_ad_sets');
        DB::statement("
            CREATE POLICY rls_campaign_ad_sets_insert ON cmis.campaign_ad_sets
            FOR INSERT WITH CHECK (org_id = cmis.get_current_org_id())
        ");

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_update ON cmis.campaign_ad_sets');
        DB::statement("
            CREATE POLICY rls_campaign_ad_sets_update ON cmis.campaign_ad_sets
            FOR UPDATE USING (
                (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
                AND org_id = cmis.get_current_org_id()
            )
        ");

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_delete ON cmis.campaign_ad_sets');
        DB::statement("
            CREATE POLICY rls_campaign_ad_sets_delete ON cmis.campaign_ad_sets
            FOR DELETE USING (org_id = cmis.get_current_org_id())
        ");

        // Create RLS policies for campaign_ads
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_select ON cmis.campaign_ads');
        DB::statement("
            CREATE POLICY rls_campaign_ads_select ON cmis.campaign_ads
            FOR SELECT USING (
                (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
                AND org_id = cmis.get_current_org_id()
            )
        ");

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_insert ON cmis.campaign_ads');
        DB::statement("
            CREATE POLICY rls_campaign_ads_insert ON cmis.campaign_ads
            FOR INSERT WITH CHECK (org_id = cmis.get_current_org_id())
        ");

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_update ON cmis.campaign_ads');
        DB::statement("
            CREATE POLICY rls_campaign_ads_update ON cmis.campaign_ads
            FOR UPDATE USING (
                (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
                AND org_id = cmis.get_current_org_id()
            )
        ");

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_delete ON cmis.campaign_ads');
        DB::statement("
            CREATE POLICY rls_campaign_ads_delete ON cmis.campaign_ads
            FOR DELETE USING (org_id = cmis.get_current_org_id())
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies first
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_select ON cmis.campaign_ads');
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_insert ON cmis.campaign_ads');
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_update ON cmis.campaign_ads');
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ads_delete ON cmis.campaign_ads');

        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_select ON cmis.campaign_ad_sets');
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_insert ON cmis.campaign_ad_sets');
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_update ON cmis.campaign_ad_sets');
        DB::statement('DROP POLICY IF EXISTS rls_campaign_ad_sets_delete ON cmis.campaign_ad_sets');

        // Drop tables (foreign keys are dropped automatically with CASCADE)
        DB::statement('DROP TABLE IF EXISTS cmis.campaign_ads CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.campaign_ad_sets CASCADE');

        // Remove columns from campaigns table using raw SQL
        $columns = [
            'platform',
            'campaign_type',
            'buying_type',
            'budget_type',
            'daily_budget',
            'lifetime_budget',
            'bid_strategy',
            'bid_amount',
            'optimization_goal',
            'is_advantage_plus',
            'is_smart_campaign',
            'is_performance_max',
            'attribution_spec',
            'external_campaign_id',
            'last_synced_at',
            'sync_status',
            'platform_settings',
            'targeting_summary',
        ];

        foreach ($columns as $column) {
            DB::statement("ALTER TABLE cmis.campaigns DROP COLUMN IF EXISTS {$column}");
        }
    }
};
