<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Ads\MetaAdsService;
use App\Models\AdPlatform\AdAccount;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;

/**
 * Meta Ads Platform Complete Workflow Test
 */
class MetaAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected MetaAdsService $metaAdsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metaAdsService = app(MetaAdsService::class);
    }

    /** @test */
    public function it_creates_meta_ad_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'campaign_123456789',
            'name' => 'Test Meta Campaign',
            'objective' => 'CONVERSIONS',
            'status' => 'ACTIVE',
        ]);

        $result = $this->metaAdsService->createCampaign($integration, [
            'name' => 'Test Meta Campaign',
            'objective' => 'CONVERSIONS',
            'status' => 'PAUSED',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('campaign_id', $result);

        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'org_id' => $org->org_id,
            'campaign_external_id' => 'campaign_123456789',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_ad_set_with_targeting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');
        $adCampaign = $this->createTestAdCampaign($org->org_id, $integration->integration_id);

        $this->mockMetaAPI('success', [
            'id' => 'adset_987654321',
            'name' => 'Test Ad Set',
            'targeting' => [
                'geo_locations' => ['countries' => ['BH']],
                'age_min' => 25,
                'age_max' => 45,
            ],
            'daily_budget' => '5000',
        ]);

        $result = $this->metaAdsService->createAdSet($integration, [
            'campaign_id' => $adCampaign->campaign_external_id,
            'name' => 'Test Ad Set',
            'targeting' => [
                'geo_locations' => ['countries' => ['BH']],
                'age_min' => 25,
                'age_max' => 45,
            ],
            'daily_budget' => 50.00,
        ]);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('cmis.ad_sets', [
            'org_id' => $org->org_id,
            'adset_external_id' => 'adset_987654321',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'create_adset',
        ]);
    }

    /** @test */
    public function it_creates_ad_creative_with_media()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'creative_456789123',
            'name' => 'Test Creative',
            'object_story_spec' => [
                'page_id' => '123456',
                'link_data' => [
                    'message' => 'Check out our amazing product!',
                    'link' => 'https://example.com',
                    'image_hash' => 'abc123',
                ],
            ],
        ]);

        $result = $this->metaAdsService->createCreative($integration, [
            'name' => 'Test Creative',
            'message' => 'Check out our amazing product!',
            'link' => 'https://example.com',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'create_creative',
        ]);
    }

    /** @test */
    public function it_syncs_ad_campaign_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');
        $adCampaign = $this->createTestAdCampaign($org->org_id, $integration->integration_id);

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'campaign_id' => $adCampaign->campaign_external_id,
                    'impressions' => '50000',
                    'clicks' => '2500',
                    'spend' => '125.50',
                    'conversions' => '150',
                    'date_start' => '2024-01-01',
                    'date_stop' => '2024-01-01',
                ],
            ],
        ]);

        $result = $this->metaAdsService->syncMetrics($integration);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('cmis.ad_metrics', [
            'org_id' => $org->org_id,
            'entity_level' => 'campaign',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'sync_metrics',
        ]);
    }

    /** @test */
    public function it_updates_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');
        $adCampaign = $this->createTestAdCampaign($org->org_id, $integration->integration_id);

        $this->mockMetaAPI('success', [
            'success' => true,
        ]);

        $result = $this->metaAdsService->updateBudget(
            $integration,
            $adCampaign->campaign_external_id,
            150.00
        );

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'update_budget',
        ]);
    }

    /** @test */
    public function it_pauses_and_resumes_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');
        $adCampaign = $this->createTestAdCampaign($org->org_id, $integration->integration_id);

        $this->mockMetaAPI('success', ['success' => true]);

        // Pause campaign
        $pauseResult = $this->metaAdsService->updateStatus(
            $integration,
            $adCampaign->campaign_external_id,
            'PAUSED'
        );

        $this->assertTrue($pauseResult['success']);

        // Resume campaign
        $resumeResult = $this->metaAdsService->updateStatus(
            $integration,
            $adCampaign->campaign_external_id,
            'ACTIVE'
        );

        $this->assertTrue($resumeResult['success']);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'pause_resume_campaign',
        ]);
    }

    /** @test */
    public function it_creates_lookalike_audience()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'lookalike_123',
            'name' => 'Lookalike Audience - Converters',
            'subtype' => 'LOOKALIKE',
        ]);

        $result = $this->metaAdsService->createLookalikeAudience($integration, [
            'name' => 'Lookalike Audience - Converters',
            'source_audience_id' => 'source_456',
            'country' => 'BH',
            'ratio' => 0.01,
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'meta_ads',
            'step' => 'create_lookalike_audience',
        ]);
    }
}
