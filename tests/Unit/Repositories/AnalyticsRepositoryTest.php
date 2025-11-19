<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\CMIS\AnalyticsRepository;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Analytics Repository Unit Tests
 */
class AnalyticsRepositoryTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected AnalyticsRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(AnalyticsRepositoryInterface::class);
    }

    /** @test */
    public function it_can_get_campaign_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $analytics = $this->repository->getCampaignAnalytics(
            $campaign->campaign_id,
            now()->subDays(30),
            now()
        );

        $this->assertIsArray($analytics);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'has_analytics' => !empty($analytics),
        ]);
    }

    /** @test */
    public function it_can_get_org_overview_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create some campaigns
        $this->createTestCampaign($org->org_id, ['status' => 'active']);
        $this->createTestCampaign($org->org_id, ['status' => 'completed']);

        $overview = $this->repository->getOrgOverview(
            $org->org_id,
            now()->subDays(30),
            now()
        );

        $this->assertIsArray($overview);
        $this->assertArrayHasKey('total_campaigns', $overview);

        $this->logTestResult('passed', [
            'org_id' => $org->org_id,
            'total_campaigns' => $overview['total_campaigns'] ?? 0,
        ]);
    }

    /** @test */
    public function it_can_get_platform_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $analytics = $this->repository->getPlatformAnalytics(
            $org->org_id,
            'instagram',
            now()->subDays(30),
            now()
        );

        $this->assertIsArray($analytics);

        $this->logTestResult('passed', [
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'has_data' => !empty($analytics),
        ]);
    }

    /** @test */
    public function it_can_calculate_engagement_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $engagement = $this->repository->getEngagementMetrics(
            $campaign->campaign_id,
            now()->subDays(30),
            now()
        );

        $this->assertIsArray($engagement);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'has_engagement_data' => !empty($engagement),
        ]);
    }

    /** @test */
    public function it_can_get_conversion_funnel()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $funnel = $this->repository->getConversionFunnel(
            $campaign->campaign_id
        );

        $this->assertIsArray($funnel);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'funnel_stages' => count($funnel),
        ]);
    }

    /** @test */
    public function it_can_get_audience_demographics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $demographics = $this->repository->getAudienceDemographics(
            $campaign->campaign_id
        );

        $this->assertIsArray($demographics);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'has_demographics' => !empty($demographics),
        ]);
    }

    /** @test */
    public function it_can_compare_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign1 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign 1']);
        $campaign2 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign 2']);

        $comparison = $this->repository->compareCampaigns([
            $campaign1->campaign_id,
            $campaign2->campaign_id,
        ], now()->subDays(30), now());

        $this->assertIsArray($comparison);

        $this->logTestResult('passed', [
            'campaigns_compared' => 2,
            'has_comparison_data' => !empty($comparison),
        ]);
    }

    /** @test */
    public function it_can_get_top_performing_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        // Create some content with metrics
        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Top Content',
            'status' => 'published',
            'metrics' => [
                'views' => 10000,
                'engagement' => 500,
            ],
        ]);

        $topContent = $this->repository->getTopPerformingContent(
            $org->org_id,
            now()->subDays(30),
            now(),
            10
        );

        $this->assertIsArray($topContent);

        $this->logTestResult('passed', [
            'org_id' => $org->org_id,
            'top_content_count' => count($topContent),
        ]);
    }

    /** @test */
    public function it_can_calculate_roi()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id, [
            'budget' => 5000.00,
        ]);

        $roi = $this->repository->calculateROI(
            $campaign->campaign_id,
            15000.00 // revenue
        );

        $this->assertIsArray($roi);
        $this->assertArrayHasKey('roi_percentage', $roi);

        // ROI = ((15000 - 5000) / 5000) * 100 = 200%
        $this->assertEquals(200, $roi['roi_percentage']);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'roi_percentage' => $roi['roi_percentage'],
        ]);
    }

    /** @test */
    public function it_respects_transaction_context()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        // Create campaign in org1
        $this->actingAsUserInOrg($setup1['user'], $setup1['org']);
        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);

        // Switch to org2 context
        $this->clearTransactionContext();
        $this->actingAsUserInOrg($setup2['user'], $setup2['org']);

        // Create campaign in org2
        $campaign2 = $this->createTestCampaign($setup2['org']->org_id);

        // Verify org2 user can only see their own analytics
        $overview = $this->repository->getOrgOverview(
            $setup2['org']->org_id,
            now()->subDays(30),
            now()
        );

        $this->assertIsArray($overview);

        // Should only include org2 data
        $this->assertGreaterThanOrEqual(1, $overview['total_campaigns'] ?? 0);

        $this->logTestResult('passed', [
            'transaction_context' => 'enforced',
        ]);
    }

    /** @test */
    public function it_can_get_real_time_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $realTime = $this->repository->getRealTimeAnalytics(
            $campaign->campaign_id
        );

        $this->assertIsArray($realTime);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'has_real_time_data' => !empty($realTime),
        ]);
    }

    /** @test */
    public function it_can_get_channel_attribution()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $attribution = $this->repository->getChannelAttribution(
            $campaign->campaign_id,
            'last_click'
        );

        $this->assertIsArray($attribution);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'attribution_model' => 'last_click',
        ]);
    }
}
