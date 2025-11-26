<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CampaignOrchestratorService;
use App\Models\Campaign;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Campaign Orchestrator Service Unit Tests
 */
class CampaignOrchestratorServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected CampaignOrchestratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CampaignOrchestratorService::class);
    }

    #[Test]
    public function it_can_create_complete_campaign_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaignData = [
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign 2024',
            'objective' => 'conversions',
            'status' => 'draft',
            'budget' => 5000.00,
            'currency' => 'BHD',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(37)->format('Y-m-d'),
        ];

        $result = $this->service->createCampaign($campaignData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('campaign', $result);
        $this->assertEquals('Summer Campaign 2024', $result['campaign']['name']);
    }

    #[Test]
    public function it_validates_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $invalidCampaignData = [
            'org_id' => $org->org_id,
            'name' => 'Invalid Budget Campaign',
            'budget' => -100.00, // Negative budget
        ];

        $result = $this->service->createCampaign($invalidCampaignData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    #[Test]
    public function it_validates_campaign_date_range()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $invalidCampaignData = [
            'org_id' => $org->org_id,
            'name' => 'Invalid Date Campaign',
            'start_date' => '2024-06-30',
            'end_date' => '2024-06-01', // End before start
        ];

        $result = $this->service->createCampaign($invalidCampaignData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    #[Test]
    public function it_can_activate_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $result = $this->service->activateCampaign($campaign->campaign_id);

        $this->assertTrue($result['success']);
        $this->assertEquals('active', $campaign->fresh()->status);
    }

    #[Test]
    public function it_can_pause_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Campaign',
            'status' => 'active',
        ]);

        $result = $this->service->pauseCampaign($campaign->campaign_id);

        $this->assertTrue($result['success']);
        $this->assertEquals('paused', $campaign->fresh()->status);
    }

    #[Test]
    public function it_can_complete_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Finishing Campaign',
            'status' => 'active',
        ]);

        $result = $this->service->completeCampaign($campaign->campaign_id);

        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $campaign->fresh()->status);
    }

    #[Test]
    public function it_can_generate_campaign_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $insights = $this->service->generateCampaignInsights($campaign->campaign_id);

        $this->assertIsArray($insights);
        $this->assertArrayHasKey('campaign_id', $insights);
        $this->assertArrayHasKey('metrics', $insights);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];
        $user1 = $setup1['user'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Campaign',
            'status' => 'draft',
        ]);

        $this->actingAsUserInOrg($user1, $org1);

        // User from org1 should not be able to access org2's campaign
        $result = $this->service->getCampaign($campaign->campaign_id);

        $this->assertFalse($result['success'] ?? false);
    }

    #[Test]
    public function it_can_duplicate_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $originalCampaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Original Campaign',
            'objective' => 'conversions',
            'status' => 'active',
            'budget' => 1000.00,
        ]);

        $result = $this->service->duplicateCampaign($originalCampaign->campaign_id, [
            'name' => 'Duplicated Campaign',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Duplicated Campaign', $result['campaign']['name']);
        $this->assertEquals('draft', $result['campaign']['status']); // Should be draft
    }

    #[Test]
    public function it_tracks_campaign_performance_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Performance Campaign',
            'status' => 'active',
        ]);

        $metricsData = [
            'impressions' => 10000,
            'clicks' => 500,
            'conversions' => 50,
            'spend' => 250.00,
        ];

        $result = $this->service->updateCampaignMetrics($campaign->campaign_id, $metricsData);

        $this->assertTrue($result['success']);
    }
}
