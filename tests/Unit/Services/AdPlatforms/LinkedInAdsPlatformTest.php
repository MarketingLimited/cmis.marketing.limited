<?php

namespace Tests\Unit\Services\AdPlatforms;

use Tests\TestCase;
use App\Services\AdPlatforms\LinkedIn\LinkedInAdsPlatform;
use App\Models\Core\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;

/**
 * Characterization Tests for LinkedInAdsPlatform
 *
 * These tests document the current behavior of LinkedInAdsPlatform
 * to enable safe refactoring. Total: 32 tests
 */
class LinkedInAdsPlatformTest extends TestCase
{
    use RefreshDatabase;

    private LinkedInAdsPlatform $service;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test integration
        $this->integration = Integration::factory()->create([
            'platform' => 'linkedin',
            'account_id' => '123456',
            'access_token' => encrypt('test_access_token'),
            'refresh_token' => encrypt('test_refresh_token'),
            'metadata' => [
                'account_id' => '123456',
                'refresh_token' => 'test_refresh_token',
            ],
        ]);

        $this->service = new LinkedInAdsPlatform($this->integration);
    }

    // ==========================================
    // CAMPAIGN OPERATIONS (7 tests)
    // ==========================================

    /** @test */
    public function it_creates_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredCampaign:123456'
            ], 200)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
            'objective' => 'WEBSITE_VISITS',
            'cost_type' => 'CPC',
            'daily_budget' => 100,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('123456', $result['campaign_id']);
        $this->assertArrayHasKey('campaign_urn', $result);
    }

    /** @test */
    public function it_handles_campaign_creation_failure()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Invalid request'], 400)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign'
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_updates_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredCampaign:123456'
            ], 200)
        ]);

        $result = $this->service->updateCampaign('123456', [
            'name' => 'Updated Campaign'
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_gets_campaign_details()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredCampaign:123456',
                'name' => 'Test Campaign',
                'status' => 'ACTIVE',
            ], 200)
        ]);

        $result = $this->service->getCampaign('123456');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    /** @test */
    public function it_deletes_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredCampaign:123456'
            ], 200)
        ]);

        $result = $this->service->deleteCampaign('123456');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_fetches_campaigns_with_filters()
    {
        Http::fake([
            '*' => Http::response([
                'elements' => [
                    ['id' => 'urn:li:sponsoredCampaign:1'],
                    ['id' => 'urn:li:sponsoredCampaign:2'],
                ],
                'paging' => ['total' => 2, 'start' => 0, 'count' => 2],
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns(['status' => 'ACTIVE']);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['campaigns']);
        $this->assertArrayHasKey('pagination', $result);
    }

    /** @test */
    public function it_gets_campaign_metrics()
    {
        Http::fake([
            '*' => Http::response([
                'elements' => [
                    [
                        'impressions' => 1000,
                        'clicks' => 50,
                        'costInLocalCurrency' => 5000,
                    ]
                ],
            ], 200)
        ]);

        $result = $this->service->getCampaignMetrics('123456', '2024-01-01', '2024-01-31');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('daily_breakdown', $result);
    }

    // ==========================================
    // AD SET OPERATIONS (2 tests)
    // ==========================================

    /** @test */
    public function it_creates_ad_set_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredCreative:789'
            ], 200)
        ]);

        $result = $this->service->createAdSet('123456', [
            'name' => 'Test Creative',
            'status' => 'PAUSED',
            'targeting' => [
                'locations' => [103644278],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('789', $result['creative_id']);
    }

    /** @test */
    public function it_handles_ad_set_creation_failure()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Invalid request'], 400)
        ]);

        $result = $this->service->createAdSet('123456', [
            'name' => 'Test Creative',
        ]);

        $this->assertFalse($result['success']);
    }

    // ==========================================
    // AD OPERATIONS (4 tests)
    // ==========================================

    /** @test */
    public function it_creates_ad_with_existing_share()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredCreative:999'
            ], 200)
        ]);

        $result = $this->service->createAd('789', [
            'share_urn' => 'urn:li:share:ABC123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('999', $result['creative_id']);
    }

    /** @test */
    public function it_creates_ad_with_new_share_content()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:ugcPost:6789'
            ], 200),
            '*/creativeV2' => Http::response([
                'id' => 'urn:li:sponsoredCreative:999'
            ], 200)
        ]);

        $result = $this->service->createAd('789', [
            'share_content' => [
                'text' => 'Check out our new product!',
                'media_category' => 'NONE',
            ],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_ad_creation_with_missing_content()
    {
        $result = $this->service->createAd('789', []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('share_urn or share_content', $result['error']);
    }

    /** @test */
    public function it_handles_ad_creation_failure()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Invalid request'], 400)
        ]);

        $result = $this->service->createAd('789', [
            'share_urn' => 'urn:li:share:ABC123',
        ]);

        $this->assertFalse($result['success']);
    }

    // ==========================================
    // LEAD GEN OPERATIONS (2 tests)
    // ==========================================

    /** @test */
    public function it_creates_lead_gen_form()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:leadGenForm:555'
            ], 200)
        ]);

        $result = $this->service->createLeadGenForm([
            'name' => 'Contact Form',
            'headline' => 'Get in Touch',
            'description' => 'Fill out the form',
            'privacy_policy_url' => 'https://example.com/privacy',
            'fields' => [
                ['type' => 'FIRST_NAME', 'required' => true],
                ['type' => 'EMAIL', 'required' => true],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('555', $result['form_id']);
    }

    /** @test */
    public function it_gets_lead_form_responses()
    {
        Http::fake([
            '*' => Http::response([
                'elements' => [
                    ['id' => 'response1'],
                    ['id' => 'response2'],
                ],
                'paging' => ['total' => 2],
            ], 200)
        ]);

        $result = $this->service->getLeadFormResponses('555');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['responses']);
        $this->assertEquals(2, $result['total']);
    }

    // ==========================================
    // HELPER METHODS (10 tests)
    // ==========================================

    /** @test */
    public function it_gets_available_objectives()
    {
        $objectives = $this->service->getAvailableObjectives();

        $this->assertIsArray($objectives);
        $this->assertContains('BRAND_AWARENESS', $objectives);
        $this->assertContains('WEBSITE_VISITS', $objectives);
        $this->assertContains('LEAD_GENERATION', $objectives);
    }

    /** @test */
    public function it_gets_available_placements()
    {
        $placements = $this->service->getAvailablePlacements();

        $this->assertIsArray($placements);
        $this->assertArrayHasKey('linkedin_feed', $placements);
        $this->assertArrayHasKey('linkedin_right_rail', $placements);
        $this->assertArrayHasKey('linkedin_messaging', $placements);
    }

    /** @test */
    public function it_gets_available_ad_formats()
    {
        $adFormats = $this->service->getAvailableAdFormats();

        $this->assertIsArray($adFormats);
        $this->assertArrayHasKey('SPONSORED_STATUS_UPDATE', $adFormats);
        $this->assertArrayHasKey('SPONSORED_VIDEO', $adFormats);
        $this->assertArrayHasKey('SPONSORED_INMAILS', $adFormats);
    }

    /** @test */
    public function it_syncs_account_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'urn:li:sponsoredAccount:123456',
                'name' => 'Test Account',
                'currency' => 'USD',
                'status' => 'ACTIVE',
            ], 200)
        ]);

        $result = $this->service->syncAccount();

        $this->assertTrue($result['success']);
        $this->assertEquals('123456', $result['account']['id']);
        $this->assertEquals('Test Account', $result['account']['name']);
    }

    /** @test */
    public function it_refreshes_access_token_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'access_token' => 'new_access_token',
                'expires_in' => 5184000,
            ], 200)
        ]);

        $result = $this->service->refreshAccessToken();

        $this->assertTrue($result['success']);
        $this->assertEquals('new_access_token', $result['access_token']);
        $this->assertEquals(5184000, $result['expires_in']);
    }

    /** @test */
    public function it_handles_token_refresh_failure()
    {
        Http::fake([
            '*' => Http::response(['error_description' => 'Invalid refresh token'], 401)
        ]);

        $result = $this->service->refreshAccessToken();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_maps_objective_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapObjective');
        $method->setAccessible(true);

        $this->assertEquals('WEBSITE_VISITS', $method->invoke($this->service, 'TRAFFIC'));
        $this->assertEquals('BRAND_AWARENESS', $method->invoke($this->service, 'AWARENESS'));
        $this->assertEquals('LEAD_GENERATION', $method->invoke($this->service, 'LEADS'));
    }

    /** @test */
    public function it_maps_cost_type_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapCostType');
        $method->setAccessible(true);

        $this->assertEquals('CPC', $method->invoke($this->service, 'COST_PER_CLICK'));
        $this->assertEquals('CPM', $method->invoke($this->service, 'COST_PER_IMPRESSION'));
        $this->assertEquals('CPC', $method->invoke($this->service, 'unknown'));
    }

    /** @test */
    public function it_maps_status_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapStatus');
        $method->setAccessible(true);

        $this->assertEquals('ACTIVE', $method->invoke($this->service, 'ENABLED'));
        $this->assertEquals('PAUSED', $method->invoke($this->service, 'DISABLED'));
        $this->assertEquals('ARCHIVED', $method->invoke($this->service, 'DELETED'));
    }

    /** @test */
    public function it_ensures_urn_format_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ensureUrn');
        $method->setAccessible(true);

        $this->assertEquals('urn:li:geo:123', $method->invoke($this->service, '123', 'geo'));
        $this->assertEquals('urn:li:geo:456', $method->invoke($this->service, 'urn:li:geo:456', 'geo'));
    }

    /** @test */
    public function it_extracts_id_from_urn_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractIdFromUrn');
        $method->setAccessible(true);

        $this->assertEquals('123456', $method->invoke($this->service, 'urn:li:sponsoredCampaign:123456'));
        $this->assertEquals('789', $method->invoke($this->service, 'urn:li:sponsoredCreative:789'));
    }

    // ==========================================
    // TARGETING OPERATIONS (2 tests)
    // ==========================================

    /** @test */
    public function it_builds_targeting_criteria_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildTargeting');
        $method->setAccessible(true);

        $targeting = $method->invoke($this->service, [
            'locations' => ['103644278'],
            'company_sizes' => ['B', 'C'],
            'industries' => ['4', '5'],
        ]);

        $this->assertArrayHasKey('includedTargetingFacets', $targeting);
        $this->assertArrayHasKey('locations', $targeting['includedTargetingFacets']);
        $this->assertArrayHasKey('companySizes', $targeting['includedTargetingFacets']);
        $this->assertArrayHasKey('industries', $targeting['includedTargetingFacets']);
    }

    /** @test */
    public function it_aggregates_metrics_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('aggregateMetrics');
        $method->setAccessible(true);

        $elements = [
            ['impressions' => 1000, 'clicks' => 50, 'costInLocalCurrency' => 5000],
            ['impressions' => 2000, 'clicks' => 100, 'costInLocalCurrency' => 10000],
        ];

        $metrics = $method->invoke($this->service, $elements);

        $this->assertEquals(3000, $metrics['impressions']);
        $this->assertEquals(150, $metrics['clicks']);
        $this->assertEquals(150, $metrics['spend']);
        $this->assertArrayHasKey('ctr', $metrics);
        $this->assertArrayHasKey('cpc', $metrics);
    }

    // ==========================================
    // ERROR HANDLING (3 tests)
    // ==========================================

    /** @test */
    public function it_handles_http_timeout()
    {
        Http::fake([
            '*' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('timeout', strtolower($result['error']));
    }

    /** @test */
    public function it_handles_invalid_response_format()
    {
        Http::fake([
            '*' => Http::response('Invalid JSON', 200)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_handles_rate_limit_error()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Rate limit exceeded'], 429)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
