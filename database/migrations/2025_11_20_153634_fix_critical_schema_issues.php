<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix 1: Add status column to user_orgs (CRITICAL - causes 500 errors)
        if (!Schema::hasColumn('cmis.user_orgs', 'status')) {
            DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
            echo "✓ Added status column to cmis.user_orgs\n";
        }

        // Fix 2: Add ad_account_id to ad_campaigns (without FK for now - will add later)
        if (!Schema::hasColumn('cmis.ad_campaigns', 'ad_account_id')) {
            DB::statement("ALTER TABLE cmis.ad_campaigns ADD COLUMN ad_account_id UUID");
            echo "✓ Added ad_account_id to cmis.ad_campaigns\n";
        }

        // Fix 3: Add updated_at to markets (public schema)
        if (!Schema::hasColumn('public.markets', 'updated_at')) {
            DB::statement("ALTER TABLE public.markets ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()");
            echo "✓ Added updated_at to public.markets\n";
        }

        // Fix 4: Create personal_access_tokens table for Laravel Sanctum
        if (!Schema::hasTable('personal_access_tokens')) {
            DB::statement("
                CREATE TABLE personal_access_tokens (
                    id BIGSERIAL PRIMARY KEY,
                    tokenable_type VARCHAR(255) NOT NULL,
                    tokenable_id UUID NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    abilities TEXT,
                    last_used_at TIMESTAMP WITH TIME ZONE,
                    expires_at TIMESTAMP WITH TIME ZONE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                )
            ");

            DB::statement("CREATE INDEX idx_tokenable ON personal_access_tokens(tokenable_type, tokenable_id)");
            echo "✓ Created personal_access_tokens table\n";
        }

        // Fix 5: Make role_code nullable in roles table
        DB::statement("ALTER TABLE cmis.roles ALTER COLUMN role_code DROP NOT NULL");
        echo "✓ Made role_code nullable in cmis.roles\n";

        // Fix 6: Skip audit_id - it's a PRIMARY KEY and cannot be nullable
        // (factories should always provide this value)

        // Fix 7: Make audience_id nullable in audience_segments table
        if (Schema::hasTable('cmis.audience_segments') && Schema::hasColumn('cmis.audience_segments', 'audience_id')) {
            DB::statement("ALTER TABLE cmis.audience_segments ALTER COLUMN audience_id DROP NOT NULL");
            echo "✓ Made audience_id nullable in cmis.audience_segments\n";
        }

        // Fix 8: Update cmis.markets view to match actual table structure
        DB::statement("DROP VIEW IF EXISTS cmis.markets CASCADE");
        DB::statement("
            CREATE OR REPLACE VIEW cmis.markets AS
            SELECT
                market_id,
                market_name AS name,
                language_code AS code,
                currency_code,
                text_direction,
                updated_at
            FROM public.markets
        ");
        echo "✓ Updated cmis.markets view with correct columns\n";

        echo "\n✅ All critical schema fixes applied successfully!\n";
    }

    public function down(): void
    {
        // Remove status from user_orgs
        if (Schema::hasColumn('cmis.user_orgs', 'status')) {
            Schema::table('cmis.user_orgs', function ($table) {
                $table->dropColumn('status');
            });
        }

        // Remove ad_account_id from ad_campaigns
        if (Schema::hasColumn('cmis.ad_campaigns', 'ad_account_id')) {
            DB::statement("ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS fk_ad_campaigns_ad_account");
            Schema::table('cmis.ad_campaigns', function ($table) {
                $table->dropColumn('ad_account_id');
            });
        }

        // Remove updated_at from markets
        if (Schema::hasColumn('public.markets', 'updated_at')) {
            Schema::table('public.markets', function ($table) {
                $table->dropColumn('updated_at');
            });
        }

        // Drop personal_access_tokens
        Schema::dropIfExists('personal_access_tokens');

        // Restore NOT NULL constraints
        DB::statement("ALTER TABLE cmis.roles ALTER COLUMN role_code SET NOT NULL");

        if (Schema::hasTable('cmis.audit_logs') && Schema::hasColumn('cmis.audit_logs', 'audit_id')) {
            DB::statement("ALTER TABLE cmis.audit_logs ALTER COLUMN audit_id SET NOT NULL");
        }

        if (Schema::hasTable('cmis.audience_segments') && Schema::hasColumn('cmis.audience_segments', 'audience_id')) {
            DB::statement("ALTER TABLE cmis.audience_segments ALTER COLUMN audience_id SET NOT NULL");
        }
    }
};
