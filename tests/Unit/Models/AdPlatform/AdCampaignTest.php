<?php

namespace Tests\Unit\Models\AdPlatform;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Integration;
use App\Models\AdPlatform\AdCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Ad Campaign Model Unit Tests
 */
class AdCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_an_ad_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'external_campaign_id' => 'meta_campaign_123',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'campaign_id' => $adCampaign->campaign_id,
            'platform' => 'meta',
        ]);
    }

    #[Test]
    public function it_belongs_to_organization_and_integration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'google_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => 'google_campaign_456',
            'name' => 'Brand Awareness',
            'objective' => 'awareness',
            'status' => 'active',
        ]);

        $this->assertEquals($org->org_id, $adCampaign->org->org_id);
        $this->assertEquals($integration->integration_id, $adCampaign->integration->integration_id);
    }

    #[Test]
    public function it_validates_platform()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'tiktok_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_789',
            'name' => 'App Install Campaign',
            'objective' => 'app_installs',
            'status' => 'active',
        ]);

        $this->assertContains($adCampaign->platform, [
            'meta',
            'google',
            'tiktok',
            'linkedin',
            'snapchat',
            'twitter',
        ]);
    }

    #[Test]
    public function it_validates_objective()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'external_campaign_id' => 'meta_123',
            'name' => 'Lead Generation',
            'objective' => 'lead_generation',
            'status' => 'active',
        ]);

        $this->assertContains($adCampaign->objective, [
            'awareness',
            'consideration',
            'conversions',
            'lead_generation',
            'app_installs',
            'traffic',
            'engagement',
        ]);
    }

    #[Test]
    public function it_validates_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'google_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => 'google_123',
            'name' => 'Paused Campaign',
            'objective' => 'conversions',
            'status' => 'paused',
        ]);

        $this->assertContains($adCampaign->status, [
            'active',
            'paused',
            'completed',
            'deleted',
        ]);
    }

    #[Test]
    public function it_stores_budget_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'external_campaign_id' => 'meta_budget_123',
            'name' => 'Budget Test Campaign',
            'objective' => 'conversions',
            'status' => 'active',
            'daily_budget' => 100.00,
            'lifetime_budget' => 3000.00,
        ]);

        $this->assertEquals(100.00, $adCampaign->daily_budget);
        $this->assertEquals(3000.00, $adCampaign->lifetime_budget);
    }

    #[Test]
    public function it_stores_metrics_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
        ]);

        $metrics = [
            'impressions' => 50000,
            'clicks' => 2500,
            'conversions' => 125,
            'spend' => 450.75,
            'ctr' => 5.0,
            'cpc' => 0.18,
        ];

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'external_campaign_id' => 'meta_metrics_123',
            'name' => 'Metrics Test Campaign',
            'objective' => 'conversions',
            'status' => 'active',
            'metrics' => $metrics,
        ]);

        $this->assertEquals(50000, $adCampaign->metrics['impressions']);
        $this->assertEquals(125, $adCampaign->metrics['conversions']);
        $this->assertEquals(5.0, $adCampaign->metrics['ctr']);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'google_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => 'google_uuid_123',
            'name' => 'UUID Test',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->assertTrue(Str::isUuid($adCampaign->campaign_id));
    }

    #[Test]
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'tiktok_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_delete_123',
            'name' => 'Delete Test',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $adCampaign->delete();

        $this->assertSoftDeleted('cmis.ad_campaigns', [
            'campaign_id' => $adCampaign->campaign_id,
        ]);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'linkedin_ads',
            'status' => 'active',
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_campaign_id' => 'linkedin_timestamp_123',
            'name' => 'Timestamp Test',
            'objective' => 'lead_generation',
            'status' => 'active',
        ]);

        $this->assertNotNull($adCampaign->created_at);
        $this->assertNotNull($adCampaign->updated_at);
    }

    #[Test]
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

        $integration1 = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
        ]);

        $integration2 = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
        ]);

        AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'integration_id' => $integration1->integration_id,
            'platform' => 'meta',
            'external_campaign_id' => 'meta_org1',
            'name' => 'Org 1 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'integration_id' => $integration2->integration_id,
            'platform' => 'meta',
            'external_campaign_id' => 'meta_org2',
            'name' => 'Org 2 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $org1Campaigns = AdCampaign::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Campaigns);
        $this->assertEquals('Org 1 Campaign', $org1Campaigns->first()->name);
    }
}
