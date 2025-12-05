<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

/**
 * Super Admin Dashboard Schema Migration
 *
 * Creates the necessary database structure for the CMIS Super Admin Dashboard:
 * 1. Plans table for subscription tiers
 * 2. Updates to subscriptions table for proper plan_id FK
 * 3. Organization status fields (suspend/block)
 * 4. User super admin and status fields
 *
 * @see docs/features/super-admin-dashboard.md
 */
return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // 1. Create Plans Table (Shared - No RLS)
        // =====================================================
        if (!Schema::hasTable('cmis.plans')) {
            DB::statement("
                CREATE TABLE cmis.plans (
                    plan_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    name VARCHAR(100) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    description TEXT,
                    price_monthly DECIMAL(10,2) DEFAULT 0,
                    price_yearly DECIMAL(10,2) DEFAULT 0,
                    currency VARCHAR(3) DEFAULT 'USD',
                    max_users INTEGER,
                    max_orgs INTEGER DEFAULT 1,
                    max_api_calls_per_month INTEGER,
                    max_storage_gb INTEGER,
                    features JSONB DEFAULT '{}'::jsonb,
                    is_active BOOLEAN DEFAULT true,
                    is_default BOOLEAN DEFAULT false,
                    sort_order INTEGER DEFAULT 0,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                )
            ");

            // Public RLS - plans are visible to all
            $this->enablePublicRLS('cmis.plans');

            // Create indexes
            DB::statement('CREATE INDEX idx_plans_code ON cmis.plans(code)');
            DB::statement('CREATE INDEX idx_plans_is_active ON cmis.plans(is_active)');

            // Seed default plans
            $this->seedDefaultPlans();
        }

        // =====================================================
        // 2. Update Subscriptions Table
        // =====================================================
        // Add plan_id if it doesn't exist (currently has 'plan' as VARCHAR)
        if (!$this->columnExists('cmis.subscriptions', 'plan_id')) {
            DB::statement('ALTER TABLE cmis.subscriptions ADD COLUMN plan_id UUID REFERENCES cmis.plans(plan_id)');
        }

        // Add trial_ends_at if it doesn't exist
        if (!$this->columnExists('cmis.subscriptions', 'trial_ends_at')) {
            DB::statement('ALTER TABLE cmis.subscriptions ADD COLUMN trial_ends_at TIMESTAMP WITH TIME ZONE');
        }

        // Add cancelled_at if it doesn't exist
        if (!$this->columnExists('cmis.subscriptions', 'cancelled_at')) {
            DB::statement('ALTER TABLE cmis.subscriptions ADD COLUMN cancelled_at TIMESTAMP WITH TIME ZONE');
        }

        // Add cancellation_reason if it doesn't exist
        if (!$this->columnExists('cmis.subscriptions', 'cancellation_reason')) {
            DB::statement('ALTER TABLE cmis.subscriptions ADD COLUMN cancellation_reason TEXT');
        }

        // Create index on plan_id
        DB::statement('CREATE INDEX IF NOT EXISTS idx_subscriptions_plan_id ON cmis.subscriptions(plan_id)');

        // =====================================================
        // 3. Add Organization Status Fields
        // =====================================================
        if (!$this->columnExists('cmis.orgs', 'status')) {
            DB::statement("ALTER TABLE cmis.orgs ADD COLUMN status VARCHAR(50) DEFAULT 'active'");
        }

        if (!$this->columnExists('cmis.orgs', 'suspended_at')) {
            DB::statement('ALTER TABLE cmis.orgs ADD COLUMN suspended_at TIMESTAMP WITH TIME ZONE');
        }

        if (!$this->columnExists('cmis.orgs', 'suspended_by')) {
            DB::statement('ALTER TABLE cmis.orgs ADD COLUMN suspended_by UUID');
        }

        if (!$this->columnExists('cmis.orgs', 'suspension_reason')) {
            DB::statement('ALTER TABLE cmis.orgs ADD COLUMN suspension_reason TEXT');
        }

        if (!$this->columnExists('cmis.orgs', 'blocked_at')) {
            DB::statement('ALTER TABLE cmis.orgs ADD COLUMN blocked_at TIMESTAMP WITH TIME ZONE');
        }

        if (!$this->columnExists('cmis.orgs', 'blocked_by')) {
            DB::statement('ALTER TABLE cmis.orgs ADD COLUMN blocked_by UUID');
        }

        if (!$this->columnExists('cmis.orgs', 'block_reason')) {
            DB::statement('ALTER TABLE cmis.orgs ADD COLUMN block_reason TEXT');
        }

        // Create index on org status
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orgs_status ON cmis.orgs(status)');

        // =====================================================
        // 4. Add User Super Admin and Status Fields
        // =====================================================
        if (!$this->columnExists('cmis.users', 'is_super_admin')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN is_super_admin BOOLEAN DEFAULT false');
        }

        if (!$this->columnExists('cmis.users', 'is_suspended')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN is_suspended BOOLEAN DEFAULT false');
        }

        if (!$this->columnExists('cmis.users', 'suspended_at')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN suspended_at TIMESTAMP WITH TIME ZONE');
        }

        if (!$this->columnExists('cmis.users', 'suspended_by')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN suspended_by UUID');
        }

        if (!$this->columnExists('cmis.users', 'suspension_reason')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN suspension_reason TEXT');
        }

        if (!$this->columnExists('cmis.users', 'is_blocked')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN is_blocked BOOLEAN DEFAULT false');
        }

        if (!$this->columnExists('cmis.users', 'blocked_at')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN blocked_at TIMESTAMP WITH TIME ZONE');
        }

        if (!$this->columnExists('cmis.users', 'blocked_by')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN blocked_by UUID');
        }

        if (!$this->columnExists('cmis.users', 'block_reason')) {
            DB::statement('ALTER TABLE cmis.users ADD COLUMN block_reason TEXT');
        }

        // Create indexes on user status fields
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_is_super_admin ON cmis.users(is_super_admin)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_is_suspended ON cmis.users(is_suspended)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_is_blocked ON cmis.users(is_blocked)');

        // =====================================================
        // 5. Create Super Admin Audit Log Table (optional enhancement)
        // =====================================================
        if (!Schema::hasTable('cmis.super_admin_actions')) {
            DB::statement("
                CREATE TABLE cmis.super_admin_actions (
                    action_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    admin_user_id UUID NOT NULL REFERENCES cmis.users(user_id),
                    action_type VARCHAR(100) NOT NULL,
                    target_type VARCHAR(50) NOT NULL,
                    target_id UUID,
                    target_name VARCHAR(255),
                    details JSONB DEFAULT '{}'::jsonb,
                    ip_address INET,
                    user_agent TEXT,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                )
            ");

            // Public RLS - super admin actions are not org-scoped
            $this->enablePublicRLS('cmis.super_admin_actions');

            // Create indexes
            DB::statement('CREATE INDEX idx_super_admin_actions_admin ON cmis.super_admin_actions(admin_user_id)');
            DB::statement('CREATE INDEX idx_super_admin_actions_type ON cmis.super_admin_actions(action_type)');
            DB::statement('CREATE INDEX idx_super_admin_actions_target ON cmis.super_admin_actions(target_type, target_id)');
            DB::statement('CREATE INDEX idx_super_admin_actions_created ON cmis.super_admin_actions(created_at DESC)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop super admin actions table
        DB::statement('DROP POLICY IF EXISTS allow_all ON cmis.super_admin_actions');
        Schema::dropIfExists('cmis.super_admin_actions');

        // Remove user status columns
        if ($this->columnExists('cmis.users', 'block_reason')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN block_reason');
        }
        if ($this->columnExists('cmis.users', 'blocked_by')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN blocked_by');
        }
        if ($this->columnExists('cmis.users', 'blocked_at')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN blocked_at');
        }
        if ($this->columnExists('cmis.users', 'is_blocked')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN is_blocked');
        }
        if ($this->columnExists('cmis.users', 'suspension_reason')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN suspension_reason');
        }
        if ($this->columnExists('cmis.users', 'suspended_by')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN suspended_by');
        }
        if ($this->columnExists('cmis.users', 'suspended_at')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN suspended_at');
        }
        if ($this->columnExists('cmis.users', 'is_suspended')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN is_suspended');
        }
        if ($this->columnExists('cmis.users', 'is_super_admin')) {
            DB::statement('ALTER TABLE cmis.users DROP COLUMN is_super_admin');
        }

        // Remove org status columns
        if ($this->columnExists('cmis.orgs', 'block_reason')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN block_reason');
        }
        if ($this->columnExists('cmis.orgs', 'blocked_by')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN blocked_by');
        }
        if ($this->columnExists('cmis.orgs', 'blocked_at')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN blocked_at');
        }
        if ($this->columnExists('cmis.orgs', 'suspension_reason')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN suspension_reason');
        }
        if ($this->columnExists('cmis.orgs', 'suspended_by')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN suspended_by');
        }
        if ($this->columnExists('cmis.orgs', 'suspended_at')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN suspended_at');
        }
        if ($this->columnExists('cmis.orgs', 'status')) {
            DB::statement('ALTER TABLE cmis.orgs DROP COLUMN status');
        }

        // Remove subscription columns
        if ($this->columnExists('cmis.subscriptions', 'cancellation_reason')) {
            DB::statement('ALTER TABLE cmis.subscriptions DROP COLUMN cancellation_reason');
        }
        if ($this->columnExists('cmis.subscriptions', 'cancelled_at')) {
            DB::statement('ALTER TABLE cmis.subscriptions DROP COLUMN cancelled_at');
        }
        if ($this->columnExists('cmis.subscriptions', 'trial_ends_at')) {
            DB::statement('ALTER TABLE cmis.subscriptions DROP COLUMN trial_ends_at');
        }
        if ($this->columnExists('cmis.subscriptions', 'plan_id')) {
            DB::statement('ALTER TABLE cmis.subscriptions DROP COLUMN plan_id');
        }

        // Drop plans table
        DB::statement('DROP POLICY IF EXISTS allow_all ON cmis.plans');
        Schema::dropIfExists('cmis.plans');
    }

    /**
     * Check if a column exists in a table.
     */
    private function columnExists(string $table, string $column): bool
    {
        $parts = explode('.', $table);
        $schema = $parts[0] ?? 'public';
        $tableName = $parts[1] ?? $table;

        $result = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = ?
                AND table_name = ?
                AND column_name = ?
            ) as exists
        ", [$schema, $tableName, $column]);

        return $result->exists ?? false;
    }

    /**
     * Seed default subscription plans.
     */
    private function seedDefaultPlans(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'code' => 'free',
                'description' => 'Basic features for getting started',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_users' => 2,
                'max_orgs' => 1,
                'max_api_calls_per_month' => 1000,
                'max_storage_gb' => 1,
                'features' => json_encode([
                    'social_publishing' => true,
                    'basic_analytics' => true,
                    'ai_features' => false,
                    'advanced_scheduling' => false,
                    'team_collaboration' => false,
                    'api_access' => false,
                    'priority_support' => false,
                ]),
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Starter',
                'code' => 'starter',
                'description' => 'Essential features for small teams',
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
                'max_users' => 5,
                'max_orgs' => 1,
                'max_api_calls_per_month' => 10000,
                'max_storage_gb' => 10,
                'features' => json_encode([
                    'social_publishing' => true,
                    'basic_analytics' => true,
                    'ai_features' => true,
                    'advanced_scheduling' => true,
                    'team_collaboration' => false,
                    'api_access' => false,
                    'priority_support' => false,
                ]),
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional',
                'code' => 'pro',
                'description' => 'Advanced features for growing businesses',
                'price_monthly' => 79.00,
                'price_yearly' => 790.00,
                'max_users' => 15,
                'max_orgs' => 3,
                'max_api_calls_per_month' => 50000,
                'max_storage_gb' => 50,
                'features' => json_encode([
                    'social_publishing' => true,
                    'basic_analytics' => true,
                    'ai_features' => true,
                    'advanced_scheduling' => true,
                    'team_collaboration' => true,
                    'api_access' => true,
                    'priority_support' => false,
                ]),
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'description' => 'Unlimited features for large organizations',
                'price_monthly' => 199.00,
                'price_yearly' => 1990.00,
                'max_users' => null, // Unlimited
                'max_orgs' => null, // Unlimited
                'max_api_calls_per_month' => null, // Unlimited
                'max_storage_gb' => null, // Unlimited
                'features' => json_encode([
                    'social_publishing' => true,
                    'basic_analytics' => true,
                    'ai_features' => true,
                    'advanced_scheduling' => true,
                    'team_collaboration' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_integrations' => true,
                    'sso' => true,
                    'dedicated_support' => true,
                ]),
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('cmis.plans')->insert(array_merge($plan, [
                'plan_id' => \Illuminate\Support\Str::uuid(),
                'currency' => 'USD',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
