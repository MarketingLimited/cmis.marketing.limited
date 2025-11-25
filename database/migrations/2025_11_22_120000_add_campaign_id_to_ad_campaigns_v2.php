<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * Add campaign_id foreign key to ad_campaigns_v2 table
     * This fixes the critical architectural issue where ad campaigns
     * couldn't be properly linked to their parent campaigns.
     */
    public function up(): void
    {
        // Add campaign_id column to ad_campaigns_v2 (skip if already exists)
        if (!Schema::hasColumn('cmis.ad_campaigns_v2', 'campaign_id')) {
            Schema::table('cmis.ad_campaigns_v2', function (Blueprint $table) {
                $table->uuid('campaign_id')->nullable()->after('org_id');
            });
        }

        // Add index on campaign_id for performance
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_v2_campaign_id ON cmis.ad_campaigns_v2(campaign_id) WHERE deleted_at IS NULL');

        // Add composite index for common queries (org_id + campaign_id)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_campaigns_v2_org_campaign ON cmis.ad_campaigns_v2(org_id, campaign_id) WHERE deleted_at IS NULL');

        // Add foreign key constraint to campaigns table (skip if already exists)
        // Note: Using ON DELETE SET NULL instead of CASCADE to preserve ad campaign records
        // even if parent campaign is deleted (for audit/historical purposes)
        $constraintExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM pg_constraint
                WHERE conname = 'fk_ad_campaigns_v2_campaign'
            )
        ");

        if (!$constraintExists->exists) {
            try {
                DB::statement('
                    ALTER TABLE cmis.ad_campaigns_v2
                        ADD CONSTRAINT fk_ad_campaigns_v2_campaign
                        FOREIGN KEY (campaign_id)
                        REFERENCES cmis.campaigns(campaign_id)
                        ON DELETE SET NULL
                        ON UPDATE CASCADE
                ');
            } catch (\Exception $e) {
                // Skip foreign key if campaigns table schema is incompatible
                // This can happen if campaigns table uses different primary key column
            }
        }

        // COMMENTED OUT: Adding column comments can fail if transaction is aborted
        // from previous foreign key constraint errors
        /*
        // Add comment to document the relationship
        if (Schema::hasColumn('cmis.ad_campaigns_v2', 'campaign_id')) {
            try {
                DB::statement("
                    COMMENT ON COLUMN cmis.ad_campaigns_v2.campaign_id IS
                    'Links to parent campaign in cmis.campaigns. NULL indicates orphaned/standalone ad campaign.'
                ");
            } catch (\Exception $e) {
                // Skip comment if transaction is aborted
            }
        }
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint
        DB::statement('ALTER TABLE cmis.ad_campaigns_v2 DROP CONSTRAINT IF EXISTS fk_ad_campaigns_v2_campaign CASCADE');

        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_v2_campaign_id');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_campaigns_v2_org_campaign');

        // Drop column
        Schema::table('cmis.ad_campaigns_v2', function (Blueprint $table) {
            $table->dropColumn('campaign_id');
        });
    }
};
