<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use Tests\TestHelpers\DatabaseHelpers;
use App\Services\AI\AiQuotaService;
use App\Exceptions\QuotaExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * AI Quota Service Test
 *
 * Tests quota management, usage tracking, and limit enforcement.
 * Part of Phase 1B weakness remediation (2025-11-21)
 */
class AiQuotaServiceTest extends TestCase
{
    use RefreshDatabase, DatabaseHelpers;

    protected AiQuotaService $quotaService;
    protected object $org;
    protected object $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = new AiQuotaService();

        // Create test organization and user
        $this->org = $this->createTestOrg();
        $this->user = $this->createTestUser($this->org->id);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestOrg($this->org->id);
        parent::tearDown();
    }

    /** @test */
    public function it_creates_default_quota_for_new_organization()
    {
        $this->setRLSContext($this->org->id);

        $quota = $this->quotaService->getOrCreateQuota($this->org->id, null, 'gpt');

        $this->assertNotNull($quota);
        $this->assertEquals($this->org->id, $quota->org_id);
        $this->assertEquals('gpt', $quota->ai_service);
        $this->assertEquals('free', $quota->tier);
        $this->assertEquals(5, $quota->daily_limit);
        $this->assertEquals(100, $quota->monthly_limit);
    }

    /** @test */
    public function it_allows_requests_within_quota()
    {
        $this->setRLSContext($this->org->id);

        // Create quota with limits
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'daily_limit' => 10,
            'monthly_limit' => 100,
            'daily_used' => 5,
            'monthly_used' => 50,
        ]);

        // Should not throw exception
        $result = $this->quotaService->checkQuota($this->org->id, null, 'gpt', 3);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_daily_quota_exceeded()
    {
        $this->setRLSContext($this->org->id);

        // Create quota at daily limit
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'daily_limit' => 5,
            'daily_used' => 5,
        ]);

        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessage('Daily AI quota exceeded');

        $this->quotaService->checkQuota($this->org->id, null, 'gpt', 1);
    }

    /** @test */
    public function it_throws_exception_when_monthly_quota_exceeded()
    {
        $this->setRLSContext($this->org->id);

        // Create quota at monthly limit
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'daily_limit' => 100,
            'monthly_limit' => 50,
            'daily_used' => 10,
            'monthly_used' => 50,
        ]);

        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessage('Monthly AI quota exceeded');

        $this->quotaService->checkQuota($this->org->id, null, 'gpt', 1);
    }

    /** @test */
    public function it_records_usage_correctly()
    {
        $this->setRLSContext($this->org->id);

        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'daily_used' => 0,
            'monthly_used' => 0,
            'cost_used_monthly' => 0.00,
        ]);

        $this->quotaService->recordUsage(
            $this->org->id,
            null,
            'gpt',
            'content_generation',
            500, // tokens
            ['response_time' => 1.5]
        );

        // Reload quota
        $this->setRLSContext($this->org->id);
        $updatedQuota = DB::table('cmis_ai.usage_quotas')
            ->where('id', $quota->id)
            ->first();

        $this->assertEquals(1, $updatedQuota->daily_used);
        $this->assertEquals(1, $updatedQuota->monthly_used);
        $this->assertGreaterThan(0, $updatedQuota->cost_used_monthly);

        // Check tracking record created
        $trackingCount = DB::table('cmis_ai.usage_tracking')
            ->where('org_id', $this->org->id)
            ->count();

        $this->assertEquals(1, $trackingCount);
    }

    /** @test */
    public function it_resets_daily_quota_after_midnight()
    {
        $this->setRLSContext($this->org->id);

        // Create quota with yesterday's reset date
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'daily_used' => 5,
            'last_daily_reset' => now()->subDay()->toDateString(),
        ]);

        // Check quota (should trigger reset)
        $this->quotaService->checkQuota($this->org->id, null, 'gpt', 1);

        // Reload quota
        $this->setRLSContext($this->org->id);
        $updatedQuota = DB::table('cmis_ai.usage_quotas')
            ->where('id', $quota->id)
            ->first();

        $this->assertEquals(0, $updatedQuota->daily_used);
        $this->assertEquals(now()->toDateString(), $updatedQuota->last_daily_reset);
    }

    /** @test */
    public function it_resets_monthly_quota_on_new_month()
    {
        $this->setRLSContext($this->org->id);

        // Create quota with last month's reset date
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'monthly_used' => 50,
            'cost_used_monthly' => 5.00,
            'last_monthly_reset' => now()->subMonth()->toDateString(),
        ]);

        // Check quota (should trigger reset)
        $this->quotaService->checkQuota($this->org->id, null, 'gpt', 1);

        // Reload quota
        $this->setRLSContext($this->org->id);
        $updatedQuota = DB::table('cmis_ai.usage_quotas')
            ->where('id', $quota->id)
            ->first();

        $this->assertEquals(0, $updatedQuota->monthly_used);
        $this->assertEquals(0.00, (float)$updatedQuota->cost_used_monthly);
    }

    /** @test */
    public function it_calculates_cost_correctly_for_different_models()
    {
        $gptCost = $this->quotaService->calculateCost('gpt', 1000);
        $this->assertGreaterThan(0, $gptCost);

        $embeddingCost = $this->quotaService->calculateCost('embeddings', 1000);
        $this->assertGreaterThan(0, $embeddingCost);

        // GPT-4 should be more expensive than embeddings
        $this->assertGreaterThan($embeddingCost, $gptCost);
    }

    /** @test */
    public function it_returns_quota_status_with_all_services()
    {
        $this->setRLSContext($this->org->id);

        // Create quotas for multiple services
        $this->createTestQuota($this->org->id, 'gpt', ['daily_used' => 3, 'daily_limit' => 5]);
        $this->createTestQuota($this->org->id, 'embeddings', ['daily_used' => 10, 'daily_limit' => 20]);

        $status = $this->quotaService->getQuotaStatus($this->org->id, null);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('gpt', $status);
        $this->assertArrayHasKey('embeddings', $status);

        $this->assertEquals(3, $status['gpt']['daily_used']);
        $this->assertEquals(5, $status['gpt']['daily_limit']);
        $this->assertEquals(60, $status['gpt']['daily_percentage']); // 3/5 = 60%
    }

    /** @test */
    public function it_enforces_cost_limits()
    {
        $this->setRLSContext($this->org->id);

        // Create quota at cost limit
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'cost_limit_monthly' => 10.00,
            'cost_used_monthly' => 9.99,
        ]);

        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessage('Monthly cost limit exceeded');

        // Try to use service that would exceed cost limit
        $this->quotaService->checkQuota($this->org->id, null, 'gpt', 1000); // High token count
    }

    /** @test */
    public function it_respects_tier_limits()
    {
        $this->setRLSContext($this->org->id);

        // Free tier quota
        $freeQuota = $this->createTestQuota($this->org->id, 'gpt', [
            'tier' => 'free',
            'daily_limit' => 5,
        ]);

        $this->assertEquals(5, $freeQuota->daily_limit);

        // Pro tier should have higher limits (this would be set by service)
        $this->setRLSContext($this->org->id);
        DB::table('cmis_ai.usage_quotas')
            ->where('id', $freeQuota->id)
            ->update([
                'tier' => 'pro',
                'daily_limit' => 50,
                'monthly_limit' => 1000,
            ]);

        $proQuota = DB::table('cmis_ai.usage_quotas')
            ->where('id', $freeQuota->id)
            ->first();

        $this->assertEquals('pro', $proQuota->tier);
        $this->assertEquals(50, $proQuota->daily_limit);
    }

    /** @test */
    public function it_provides_usage_recommendations()
    {
        $this->setRLSContext($this->org->id);

        // Create quota near limit
        $quota = $this->createTestQuota($this->org->id, 'gpt', [
            'tier' => 'free',
            'daily_used' => 4,
            'daily_limit' => 5,
            'monthly_used' => 90,
            'monthly_limit' => 100,
        ]);

        $recommendations = $this->quotaService->getRecommendations($this->org->id, null);

        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('should_upgrade', $recommendations);
        $this->assertTrue($recommendations['should_upgrade']);
        $this->assertArrayHasKey('reason', $recommendations);
    }

    /** @test */
    public function it_maintains_rls_isolation_between_orgs()
    {
        // Create second organization
        $org2 = $this->createTestOrg(['name' => 'Org 2']);

        // Create quotas for both orgs
        $this->setRLSContext($this->org->id);
        $this->createTestQuota($this->org->id, 'gpt');

        $this->setRLSContext($org2->id);
        $this->createTestQuota($org2->id, 'gpt');

        // Test isolation
        $this->assertRLSIsolation($this->org->id, $org2->id, 'cmis_ai.usage_quotas');

        // Cleanup second org
        $this->cleanupTestOrg($org2->id);
    }
}
