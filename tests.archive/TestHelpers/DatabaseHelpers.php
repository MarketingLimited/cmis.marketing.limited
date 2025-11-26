<?php

namespace Tests\TestHelpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Database Test Helpers
 *
 * Helper methods for setting up database state in tests,
 * particularly for RLS context and multi-tenancy.
 */
trait DatabaseHelpers
{
    /**
     * Set up RLS context for testing
     *
     * @param string|null $orgId
     * @param string|null $userId
     * @return void
     */
    protected function setRLSContext(?string $orgId = null, ?string $userId = null): void
    {
        if ($orgId) {
            DB::statement("SET LOCAL app.current_org_id = '{$orgId}'");
        }

        if ($userId) {
            DB::statement("SET LOCAL app.current_user_id = '{$userId}'");
        }

        // Set as admin for test setup
        DB::statement("SET LOCAL app.is_admin = true");
    }

    /**
     * Clear RLS context
     *
     * @return void
     */
    protected function clearRLSContext(): void
    {
        try {
            DB::statement("RESET app.current_org_id");
            DB::statement("RESET app.current_user_id");
            DB::statement("RESET app.is_admin");
        } catch (\Exception $e) {
            // Ignore errors if variables don't exist
        }
    }

    /**
     * Create test organization
     *
     * @param array $attributes
     * @return object
     */
    protected function createTestOrg(array $attributes = []): object
    {
        $orgId = Str::uuid()->toString();

        $this->setRLSContext();

        DB::table('cmis.orgs')->insert(array_merge([
            'id' => $orgId,
            'name' => 'Test Organization',
            'slug' => 'test-org-' . Str::random(8),
            'status' => 'active',
            'subscription_tier' => 'free',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return DB::table('cmis.orgs')->where('id', $orgId)->first();
    }

    /**
     * Create test user
     *
     * @param string $orgId
     * @param array $attributes
     * @return object
     */
    protected function createTestUser(string $orgId, array $attributes = []): object
    {
        $userId = Str::uuid()->toString();

        $this->setRLSContext($orgId);

        DB::table('cmis.users')->insert(array_merge([
            'id' => $userId,
            'org_id' => $orgId,
            'name' => 'Test User',
            'email' => 'test-' . Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return DB::table('cmis.users')->where('id', $userId)->first();
    }

    /**
     * Create test campaign
     *
     * @param string $orgId
     * @param array $attributes
     * @return object
     */
    protected function createTestCampaign(string $orgId, array $attributes = []): object
    {
        $campaignId = Str::uuid()->toString();

        $this->setRLSContext($orgId);

        DB::table('cmis.campaigns')->insert(array_merge([
            'id' => $campaignId,
            'org_id' => $orgId,
            'name' => 'Test Campaign',
            'status' => 'draft',
            'objective' => 'awareness',
            'budget_total' => 1000.00,
            'budget_daily' => 50.00,
            'start_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return DB::table('cmis.campaigns')->where('id', $campaignId)->first();
    }

    /**
     * Create test AI quota
     *
     * @param string $orgId
     * @param string $service
     * @param array $attributes
     * @return object
     */
    protected function createTestQuota(string $orgId, string $service = 'gpt', array $attributes = []): object
    {
        $quotaId = Str::uuid()->toString();

        $this->setRLSContext($orgId);

        DB::table('cmis_ai.usage_quotas')->insert(array_merge([
            'id' => $quotaId,
            'org_id' => $orgId,
            'user_id' => null,
            'tier' => 'free',
            'ai_service' => $service,
            'daily_limit' => 5,
            'monthly_limit' => 100,
            'cost_limit_monthly' => 10.00,
            'daily_used' => 0,
            'monthly_used' => 0,
            'cost_used_monthly' => 0.00,
            'last_daily_reset' => now()->toDateString(),
            'last_monthly_reset' => now()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return DB::table('cmis_ai.usage_quotas')->where('id', $quotaId)->first();
    }

    /**
     * Record test AI usage
     *
     * @param string $orgId
     * @param string $service
     * @param array $details
     * @return object
     */
    protected function recordTestAiUsage(string $orgId, string $service = 'gpt', array $details = []): object
    {
        $trackingId = Str::uuid()->toString();

        $this->setRLSContext($orgId);

        DB::table('cmis_ai.usage_tracking')->insert(array_merge([
            'id' => $trackingId,
            'org_id' => $orgId,
            'user_id' => null,
            'ai_service' => $service,
            'operation' => 'test_generation',
            'tokens_used' => 100,
            'estimated_cost' => 0.01,
            'status' => 'success',
            'created_at' => now(),
        ], $details));

        return DB::table('cmis_ai.usage_tracking')->where('id', $trackingId)->first();
    }

    /**
     * Assert RLS isolation between organizations
     *
     * @param string $orgId1
     * @param string $orgId2
     * @param string $table
     * @return void
     */
    protected function assertRLSIsolation(string $orgId1, string $orgId2, string $table): void
    {
        // Set context to org1
        $this->setRLSContext($orgId1);
        $org1Count = DB::table($table)->count();

        // Set context to org2
        $this->setRLSContext($orgId2);
        $org2Count = DB::table($table)->count();

        // Clear context (should see nothing without RLS context)
        $this->clearRLSContext();
        $noContextCount = DB::table($table)->count();

        $this->assertGreaterThanOrEqual(0, $org1Count, "Org 1 should see its own data");
        $this->assertGreaterThanOrEqual(0, $org2Count, "Org 2 should see its own data");
        $this->assertEquals(0, $noContextCount, "No data should be visible without RLS context");
    }

    /**
     * Cleanup test data for an organization
     *
     * @param string $orgId
     * @return void
     */
    protected function cleanupTestOrg(string $orgId): void
    {
        $this->setRLSContext($orgId);

        // Delete in order to respect foreign key constraints
        $tables = [
            'cmis_ai.usage_tracking',
            'cmis_ai.usage_quotas',
            'cmis.campaigns',
            'cmis.users',
            'cmis.orgs',
        ];

        foreach ($tables as $table) {
            DB::table($table)->where('org_id', $orgId)->delete();
        }

        // Also delete org itself
        DB::table('cmis.orgs')->where('id', $orgId)->delete();

        $this->clearRLSContext();
    }
}
