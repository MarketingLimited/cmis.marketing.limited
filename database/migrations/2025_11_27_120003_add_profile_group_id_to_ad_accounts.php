<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make integration_id and account_external_id nullable (ad accounts can be created without integrations for profile groups)
        DB::statement("ALTER TABLE cmis.ad_accounts ALTER COLUMN integration_id DROP NOT NULL");
        DB::statement("ALTER TABLE cmis.ad_accounts ALTER COLUMN account_external_id DROP NOT NULL");

        // Add missing columns to ad_accounts table (if not exists)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'profile_group_id') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN profile_group_id UUID;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'account_name') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN account_name TEXT;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'platform') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN platform VARCHAR(50);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'platform_account_id') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN platform_account_id VARCHAR(255);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'daily_budget_limit') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN daily_budget_limit NUMERIC;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'monthly_budget_limit') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN monthly_budget_limit NUMERIC;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'is_active') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN is_active BOOLEAN DEFAULT true;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'connection_status') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN connection_status VARCHAR(50) DEFAULT 'connected';
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'balance') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN balance NUMERIC(15,2);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'daily_spend_limit') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN daily_spend_limit NUMERIC(15,2);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'connected_by') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN connected_by UUID;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'connected_at') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN connected_at TIMESTAMP;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'last_synced_at') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN last_synced_at TIMESTAMP;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'access_token_encrypted') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN access_token_encrypted TEXT;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'refresh_token_encrypted') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN refresh_token_encrypted TEXT;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND column_name = 'token_expires_at') THEN
                    ALTER TABLE cmis.ad_accounts ADD COLUMN token_expires_at TIMESTAMP;
                END IF;
            END \$\$;
        ");

        // Copy data from existing columns if they exist
        DB::statement("
            UPDATE cmis.ad_accounts SET platform = provider WHERE platform IS NULL AND provider IS NOT NULL;
        ");
        DB::statement("
            UPDATE cmis.ad_accounts SET platform_account_id = account_external_id WHERE platform_account_id IS NULL AND account_external_id IS NOT NULL;
        ");
        DB::statement("
            UPDATE cmis.ad_accounts SET account_name = name WHERE account_name IS NULL AND name IS NOT NULL;
        ");

        // Create index on profile_group_id (if not exists)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ad_accounts_profile_group_id ON cmis.ad_accounts(profile_group_id)');

        // Add foreign key constraint (if not exists)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'ad_accounts_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.ad_accounts ADD CONSTRAINT ad_accounts_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.ad_accounts DROP CONSTRAINT IF EXISTS ad_accounts_profile_group_id_foreign');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_ad_accounts_profile_group_id');
        DB::statement('ALTER TABLE cmis.ad_accounts DROP COLUMN IF EXISTS profile_group_id');
    }
};
