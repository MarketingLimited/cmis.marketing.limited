<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\InteractsWithRLS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Campaign;
use App\Models\Creative\CreativeAsset;
use App\Models\CampaignPerformanceMetric;
use Illuminate\Support\Str;

class CampaignTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, InteractsWithRLS;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Test Marketing Campaign',
            'objective' => 'awareness',
            'status' => 'active',
        ]);

        $this->assertNotNull($campaign->campaign_id);
        $this->assertEquals('Test Marketing Campaign', $campaign->name);
        $this->assertEquals('awareness', $campaign->objective);
        $this->assertEquals('active', $campaign->status);

        $this->assertDatabaseHasWithRLS('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
        ]);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'name' => $campaign->name,
        ]);
    }

    /** @test */
    public function it_belongs_to_an_organization()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->assertNotNull($campaign->org);
        $this->assertEquals($org->org_id, $campaign->org->org_id);
        $this->assertEquals($org->name, $campaign->org->name);

        $this->logTestResult('passed');
    }

    /** @test */
    public function it_has_many_creative_assets()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $asset1 = $this->createTestCreativeAsset($org->org_id, $campaign->campaign_id);
        $asset2 = $this->createTestCreativeAsset($org->org_id, $campaign->campaign_id);

        $campaign = $campaign->fresh();

        $this->assertEquals(2, $campaign->creativeAssets()->count());
        $this->assertTrue($campaign->creativeAssets->contains($asset1));
        $this->assertTrue($campaign->creativeAssets->contains($asset2));

        $this->logTestResult('passed', [
            'assets_count' => 2,
        ]);
    }

    /** @test */
    public function it_has_many_performance_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        // Create performance metrics
        CampaignPerformanceMetric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'kpi' => 'impressions',
            'observed' => 10000,
            'target' => 15000,
            'observed_at' => now(),
        ]);

        CampaignPerformanceMetric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'kpi' => 'clicks',
            'observed' => 500,
            'target' => 750,
            'observed_at' => now(),
        ]);

        $campaign = $campaign->fresh();

        $this->assertEquals(2, $campaign->performanceMetrics()->count());

        $this->logTestResult('passed', [
            'metrics_count' => 2,
        ]);
    }

    /** @test */
    public function it_enforces_valid_status_values()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $validStatuses = ['draft', 'active', 'paused', 'completed', 'archived'];

        foreach ($validStatuses as $status) {
            $campaign = $this->createTestCampaign($org->org_id, [
                'name' => "Campaign {$status}",
                'status' => $status,
            ]);

            $this->assertEquals($status, $campaign->status);
        }

        $this->logTestResult('passed', [
            'valid_statuses' => $validStatuses,
        ]);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign1 = $this->createTestCampaign($setup1['org']->org_id, [
            'name' => 'Org 1 Campaign',
        ]);

        // User from org1 can see their campaign
        $this->assertRLSAllowsAccess(
            $setup1['user'],
            $setup1['org'],
            'cmis.campaigns',
            ['campaign_id' => $campaign1->campaign_id]
        );

        // User from org2 cannot see org1's campaign
        $this->assertRLSPreventsAccess(
            $setup2['user'],
            $setup2['org'],
            'cmis.campaigns',
            ['campaign_id' => $campaign1->campaign_id]
        );

        $this->logTestResult('passed', [
            'rls_isolation' => 'verified',
        ]);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $setup = $this->createUserWithOrg();
        $campaign = $this->createTestCampaign($setup['org']->org_id);

        $campaignId = $campaign->campaign_id;

        $campaign->delete();

        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $campaignId,
        ]);

        $this->logTestResult('passed');
    }

    /** @test */
    public function it_validates_budget_format()
    {
        $setup = $this->createUserWithOrg();

        $campaign = $this->createTestCampaign($setup['org']->org_id, [
            'budget' => 1250.50,
            'currency' => 'USD',
        ]);

        $this->assertEquals(1250.50, $campaign->budget);
        $this->assertEquals('USD', $campaign->currency);

        $this->logTestResult('passed', [
            'budget' => $campaign->budget,
            'currency' => $campaign->currency,
        ]);
    }

    /** @test */
    public function it_validates_date_range()
    {
        $setup = $this->createUserWithOrg();

        $startDate = now()->addDays(1);
        $endDate = now()->addDays(30);

        $campaign = $this->createTestCampaign($setup['org']->org_id, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $this->assertEquals($startDate->format('Y-m-d'), $campaign->start_date);
        $this->assertEquals($endDate->format('Y-m-d'), $campaign->end_date);

        $this->logTestResult('passed');
    }

    /** @test */
    public function it_cascades_delete_to_creative_assets()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);
        $asset = $this->createTestCreativeAsset($org->org_id, $campaign->campaign_id);

        $assetId = $asset->asset_id;

        // Delete campaign
        $campaign->forceDelete();

        // Verify asset is also deleted (CASCADE)
        $this->assertNull(CreativeAsset::find($assetId));

        $this->logTestResult('passed', [
            'cascade_delete' => 'verified',
        ]);
    }
}
