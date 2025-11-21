<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes remaining schema issues that were causing test failures:
     * 1. Add ad_account_id to ad_campaigns for proper relationship
     * 2. Ensure user_id columns exist and are properly constrained
     */
    public function up(): void
    {
        echo "\n🔧 Fixing remaining schema issues for tests...\n\n";

        // Fix 1: Add ad_account_id to ad_campaigns if it doesn't exist
        if (!$this->columnExists('cmis', 'ad_campaigns', 'ad_account_id')) {
            DB::statement("ALTER TABLE cmis.ad_campaigns ADD COLUMN ad_account_id UUID");
            echo "✓ Added ad_account_id to cmis.ad_campaigns\n";

            // Add foreign key constraint
            DB::statement("
                ALTER TABLE cmis.ad_campaigns
                ADD CONSTRAINT fk_ad_campaigns_ad_account
                FOREIGN KEY (ad_account_id)
                REFERENCES cmis.ad_accounts(id)
                ON DELETE SET NULL
            ");
            echo "✓ Added foreign key constraint for ad_account_id\n";
        } else {
            echo "⊘ ad_account_id already exists in cmis.ad_campaigns\n";
        }

        // Fix 2: Ensure user_id in various tables has proper constraints
        // Check if users.user_id has a unique constraint (it should via PRIMARY KEY)
        $userPrimaryKey = DB::select("
            SELECT constraint_name
            FROM information_schema.table_constraints
            WHERE table_schema = 'cmis'
            AND table_name = 'users'
            AND constraint_type = 'PRIMARY KEY'
        ");

        if (empty($userPrimaryKey)) {
            echo "⚠️  WARNING: users table doesn't have a primary key on user_id\n";
            echo "   Foreign key constraints may fail. Consider adding:\n";
            echo "   ALTER TABLE cmis.users ADD PRIMARY KEY (user_id);\n";
        } else {
            echo "✓ users.user_id has primary key constraint\n";
        }

        // Fix 3: Verify personal_access_tokens table exists (needed for Sanctum)
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
            echo "✓ Created personal_access_tokens table\n";
        } else {
            echo "⊘ personal_access_tokens table already exists\n";
        }

        // Fix 4: Ensure user_orgs.status column exists
        if (!$this->columnExists('cmis', 'user_orgs', 'status')) {
            DB::statement("ALTER TABLE cmis.user_orgs ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
            echo "✓ Added status column to cmis.user_orgs\n";
        } else {
            echo "⊘ status column already exists in cmis.user_orgs\n";
        }

        // Fix 5: Ensure markets.updated_at exists (in public schema)
        if (!$this->columnExists('public', 'markets', 'updated_at')) {
            DB::statement("ALTER TABLE public.markets ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()");
            echo "✓ Added updated_at to public.markets\n";
        } else {
            echo "⊘ updated_at already exists in public.markets\n";
        }

        echo "\n✅ All schema fixes completed successfully!\n";
        echo "   You can now run the test suite.\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n🔄 Reversing schema fixes...\n\n";

        // Remove ad_account_id and its constraint
        if ($this->columnExists('cmis', 'ad_campaigns', 'ad_account_id')) {
            DB::statement("ALTER TABLE cmis.ad_campaigns DROP CONSTRAINT IF EXISTS fk_ad_campaigns_ad_account");
            DB::statement("ALTER TABLE cmis.ad_campaigns DROP COLUMN ad_account_id");
            echo "✓ Removed ad_account_id from cmis.ad_campaigns\n";
        }

        // Remove status from user_orgs
        if ($this->columnExists('cmis', 'user_orgs', 'status')) {
            DB::statement("ALTER TABLE cmis.user_orgs DROP COLUMN status");
            echo "✓ Removed status from cmis.user_orgs\n";
        }

        // Remove updated_at from markets
        if ($this->columnExists('public', 'markets', 'updated_at')) {
            DB::statement("ALTER TABLE public.markets DROP COLUMN updated_at");
            echo "✓ Removed updated_at from public.markets\n";
        }

        // Note: We don't drop personal_access_tokens table in down()
        // as it's a core Laravel Sanctum table

        echo "\n✅ Schema fixes reversed successfully!\n\n";
    }

    /**
     * Check if a column exists in a table
     */
    private function columnExists(string $schema, string $table, string $column): bool
    {
        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_schema = ?
                AND table_name = ?
                AND column_name = ?
            )
        ", [$schema, $table, $column]);

        return $result[0]->exists ?? false;
    }
};
