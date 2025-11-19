<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to integrations table
        $this->addIntegrationsColumns();

        // Add missing columns to user_orgs table
        $this->addUserOrgsColumns();

        // Add missing columns to ad_campaigns table
        $this->addAdCampaignsColumns();
    }

    private function addIntegrationsColumns(): void
    {
        $columns = [
            'account_username' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS account_username VARCHAR(255) NULL",
            'account_name' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS account_name VARCHAR(255) NULL",
            'refresh_token' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS refresh_token TEXT NULL",
            'token_expires_at' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS token_expires_at TIMESTAMP NULL",
            'expires_at' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL",
            'status' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'active'",
            'metadata' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS metadata JSONB DEFAULT '{}'::jsonb",
            'scopes' => "ALTER TABLE cmis.integrations ADD COLUMN IF NOT EXISTS scopes JSONB DEFAULT '[]'::jsonb",
        ];

        foreach ($columns as $column => $sql) {
            $exists = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = 'integrations'
                    AND column_name = ?
                ) as exists
            ", [$column]);

            if (!$exists->exists) {
                DB::statement($sql);
                echo "✓ Added {$column} to cmis.integrations\n";
            }
        }
    }

    private function addUserOrgsColumns(): void
    {
        $columns = [
            'user_org_id' => "ALTER TABLE cmis.user_orgs ADD COLUMN IF NOT EXISTS user_org_id UUID NULL",
            'invited_at' => "ALTER TABLE cmis.user_orgs ADD COLUMN IF NOT EXISTS invited_at TIMESTAMP NULL",
        ];

        foreach ($columns as $column => $sql) {
            $exists = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = 'user_orgs'
                    AND column_name = ?
                ) as exists
            ", [$column]);

            if (!$exists->exists) {
                DB::statement($sql);
                echo "✓ Added {$column} to cmis.user_orgs\n";
            }
        }
    }

    private function addAdCampaignsColumns(): void
    {
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'ad_campaigns'
                AND column_name = 'campaign_external_id'
            ) as exists
        ");

        if (!$exists->exists) {
            DB::statement("
                ALTER TABLE cmis.ad_campaigns
                ADD COLUMN IF NOT EXISTS campaign_external_id VARCHAR(255) NULL
            ");
            echo "✓ Added campaign_external_id to cmis.ad_campaigns\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns from integrations
        DB::statement("
            ALTER TABLE cmis.integrations
            DROP COLUMN IF EXISTS account_username,
            DROP COLUMN IF EXISTS account_name,
            DROP COLUMN IF EXISTS refresh_token,
            DROP COLUMN IF EXISTS token_expires_at,
            DROP COLUMN IF EXISTS expires_at,
            DROP COLUMN IF EXISTS status,
            DROP COLUMN IF EXISTS metadata,
            DROP COLUMN IF EXISTS scopes
        ");

        // Drop columns from user_orgs
        DB::statement("
            ALTER TABLE cmis.user_orgs
            DROP COLUMN IF EXISTS user_org_id,
            DROP COLUMN IF EXISTS invited_at
        ");

        // Drop columns from ad_campaigns
        DB::statement("
            ALTER TABLE cmis.ad_campaigns
            DROP COLUMN IF EXISTS campaign_external_id
        ");
    }
};
