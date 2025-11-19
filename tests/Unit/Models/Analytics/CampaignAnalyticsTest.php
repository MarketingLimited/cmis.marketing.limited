<?php

namespace Tests\Unit\Models\Analytics;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Analytics\CampaignAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Campaign Analytics Model Unit Tests
 */
class CampaignAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_campaign_analytics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'impressions' => 10000,
            'clicks' => 500,
            'conversions' => 25,
            'spend' => 150.00,
            'revenue' => 750.00,
        ]);

        $this->assertDatabaseHas('cmis.campaign_analytics', [
            'analytics_id' => $analytics->analytics_id,
            'impressions' => 10000,
        ]);
    }

    /** @test */
    public function it_belongs_to_campaign_and_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'impressions' => 5000,
        ]);

        $this->assertEquals($campaign->campaign_id, $analytics->campaign->campaign_id);
        $this->assertEquals($org->org_id, $analytics->org->org_id);
    }

    /** @test */
    public function it_can_calculate_ctr()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'impressions' => 10000,
            'clicks' => 500,
        ]);

        // CTR = (clicks / impressions) * 100 = (500 / 10000) * 100 = 5%
        $ctr = ($analytics->clicks / $analytics->impressions) * 100;

        $this->assertEquals(5.0, $ctr);
    }

    /** @test */
    public function it_can_calculate_conversion_rate()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'clicks' => 500,
            'conversions' => 25,
        ]);

        // Conversion Rate = (conversions / clicks) * 100 = (25 / 500) * 100 = 5%
        $conversionRate = ($analytics->conversions / $analytics->clicks) * 100;

        $this->assertEquals(5.0, $conversionRate);
    }

    /** @test */
    public function it_can_calculate_roi()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'spend' => 100.00,
            'revenue' => 500.00,
        ]);

        // ROI = ((revenue - spend) / spend) * 100 = ((500 - 100) / 100) * 100 = 400%
        $roi = (($analytics->revenue - $analytics->spend) / $analytics->spend) * 100;

        $this->assertEquals(400.0, $roi);
    }

    /** @test */
    public function it_can_calculate_cpc()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'spend' => 150.00,
            'clicks' => 500,
        ]);

        // CPC = spend / clicks = 150 / 500 = 0.30
        $cpc = $analytics->spend / $analytics->clicks;

        $this->assertEquals(0.30, $cpc);
    }

    /** @test */
    public function it_can_calculate_cpm()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'spend' => 150.00,
            'impressions' => 100000,
        ]);

        // CPM = (spend / impressions) * 1000 = (150 / 100000) * 1000 = 1.50
        $cpm = ($analytics->spend / $analytics->impressions) * 1000;

        $this->assertEquals(1.50, $cpm);
    }

    /** @test */
    public function it_stores_detailed_metrics_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $detailedMetrics = [
            'engagement' => [
                'likes' => 450,
                'comments' => 75,
                'shares' => 125,
            ],
            'demographics' => [
                'age_18_24' => 30,
                'age_25_34' => 45,
                'age_35_44' => 15,
            ],
            'locations' => [
                'manama' => 60,
                'riffa' => 25,
                'muharraq' => 15,
            ],
        ];

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'impressions' => 10000,
            'detailed_metrics' => $detailedMetrics,
        ]);

        $this->assertEquals(450, $analytics->detailed_metrics['engagement']['likes']);
        $this->assertEquals(45, $analytics->detailed_metrics['demographics']['age_25_34']);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'impressions' => 5000,
        ]);

        $this->assertTrue(Str::isUuid($analytics->analytics_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $analytics = CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->toDateString(),
            'impressions' => 5000,
        ]);

        $this->assertNotNull($analytics->created_at);
        $this->assertNotNull($analytics->updated_at);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $campaign1 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Campaign 1',
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Campaign 2',
            'status' => 'active',
        ]);

        CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign1->campaign_id,
            'org_id' => $org1->org_id,
            'date' => now()->toDateString(),
            'impressions' => 5000,
        ]);

        CampaignAnalytics::create([
            'analytics_id' => Str::uuid(),
            'campaign_id' => $campaign2->campaign_id,
            'org_id' => $org2->org_id,
            'date' => now()->toDateString(),
            'impressions' => 8000,
        ]);

        $org1Analytics = CampaignAnalytics::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Analytics);
        $this->assertEquals(5000, $org1Analytics->first()->impressions);
    }
}
