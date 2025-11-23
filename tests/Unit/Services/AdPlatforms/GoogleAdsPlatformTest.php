<?php

namespace Tests\Unit\Services\AdPlatforms;

use Tests\TestCase;
use App\Services\AdPlatforms\Google\GoogleAdsPlatform;
use App\Models\Core\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;

/**
 * Characterization Tests for GoogleAdsPlatform
 *
 * These tests document the current behavior of GoogleAdsPlatform
 * to enable safe refactoring. Total: 42 tests
 */
class GoogleAdsPlatformTest extends TestCase
{
    use RefreshDatabase;

    private GoogleAdsPlatform $service;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test integration
        $this->integration = Integration::factory()->create([
            'platform' => 'google',
            'account_id' => '123-456-7890',
            'access_token' => encrypt('test_access_token'),
            'refresh_token' => encrypt('test_refresh_token'),
            'metadata' => ['developer_token' => 'test_dev_token'],
        ]);

        $this->service = new GoogleAdsPlatform($this->integration);
    }

    // ==========================================
    // CAMPAIGN OPERATIONS (7 tests)
    // ==========================================

    /** @test */
    public function it_creates_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/campaigns/123456']
                ]
            ], 200)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
            'campaign_type' => 'SEARCH',
            'status' => 'PAUSED',
            'daily_budget' => 100,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('123456', $result['external_id']);
        $this->assertArrayHasKey('resource_name', $result);
    }

    /** @test */
    public function it_handles_campaign_creation_failure()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Invalid request'], 400)
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
            '*' => Http::response(['results' => [[]]], 200)
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
                'results' => [[
                    'campaign' => ['id' => '123456', 'name' => 'Test Campaign']
                ]]
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
            '*' => Http::response(['results' => [[]]], 200)
        ]);

        $result = $this->service->deleteCampaign('123456');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_fetches_campaigns_with_filters()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['campaign' => ['id' => '1']],
                    ['campaign' => ['id' => '2']],
                ]
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns(['status' => 'ACTIVE']);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['campaigns']);
    }

    /** @test */
    public function it_gets_campaign_metrics()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[
                    'metrics' => ['impressions' => 1000, 'clicks' => 50]
                ]]
            ], 200)
        ]);

        $result = $this->service->getCampaignMetrics('123456', '2024-01-01', '2024-01-31');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('metrics', $result);
    }

    // ==========================================
    // AD GROUP OPERATIONS (1 test)
    // ==========================================

    /** @test */
    public function it_creates_ad_set_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/adGroups/789']
                ]
            ], 200)
        ]);

        $result = $this->service->createAdSet('123456', [
            'name' => 'Test Ad Group',
            'status' => 'ENABLED',
            'cpc_bid_micros' => 1000000,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('789', $result['external_id']);
    }

    // ==========================================
    // KEYWORDS OPERATIONS (4 tests)
    // ==========================================

    /** @test */
    public function it_adds_keywords_to_ad_group()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[], []]
            ], 200)
        ]);

        $result = $this->service->addKeywords('789', [
            ['text' => 'buy shoes', 'match_type' => 'EXACT'],
            ['text' => 'running shoes', 'match_type' => 'PHRASE'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['keywords_added']);
    }

    /** @test */
    public function it_adds_negative_keywords_to_campaign()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addNegativeKeywords('123456', [
            ['text' => 'free', 'match_type' => 'BROAD'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['negative_keywords_added']);
    }

    /** @test */
    public function it_removes_keywords()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->removeKeywords([
            'customers/1234567890/adGroupCriteria/123~456'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['keywords_removed']);
    }

    /** @test */
    public function it_gets_keywords_for_ad_group()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['ad_group_criterion' => ['keyword' => ['text' => 'test']]]
                ]
            ], 200)
        ]);

        $result = $this->service->getKeywords('789');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('keywords', $result);
    }

    // ==========================================
    // AD OPERATIONS (1 test)
    // ==========================================

    /** @test */
    public function it_creates_responsive_search_ad()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/ads/999']
                ]
            ], 200)
        ]);

        $result = $this->service->createAd('789', [
            'headlines' => ['Headline 1', 'Headline 2', 'Headline 3'],
            'descriptions' => ['Description 1', 'Description 2'],
            'final_url' => 'https://example.com',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('999', $result['external_id']);
    }

    // ==========================================
    // EXTENSION OPERATIONS (9 tests)
    // ==========================================

    /** @test */
    public function it_adds_sitelink_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addSitelinkExtensions('123456', [
            ['link_text' => 'Shop Now', 'final_url' => 'https://example.com/shop'],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_callout_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addCalloutExtensions('123456', [
            ['text' => 'Free Shipping'],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_structured_snippet_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addStructuredSnippetExtensions('123456', [
            ['header' => 'Services', 'values' => ['Consulting', 'Training']],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_call_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addCallExtensions('123456', [
            ['country_code' => 'US', 'phone_number' => '1234567890'],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_price_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addPriceExtensions('123456', [
            ['offerings' => [
                ['header' => 'Basic', 'description' => 'Basic plan', 'price' => 10, 'final_url' => 'https://example.com']
            ]],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_promotion_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addPromotionExtensions('123456', [
            ['target' => 'Sale', 'percent_off' => 20, 'final_url' => 'https://example.com'],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_image_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $tempImage = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($tempImage, 'fake image data');

        $result = $this->service->addImageExtensions('123456', [
            ['image_path' => $tempImage],
        ]);

        unlink($tempImage);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_adds_lead_form_extensions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addLeadFormExtensions('123456', [
            [
                'business_name' => 'Test Business',
                'headline' => 'Sign Up',
                'description' => 'Get started',
                'privacy_policy_url' => 'https://example.com/privacy',
                'cta_description' => 'Submit',
            ],
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_extension_creation_failure()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Invalid extension'], 400)
        ]);

        $result = $this->service->addSitelinkExtensions('123456', [
            ['link_text' => 'Test'],
        ]);

        $this->assertFalse($result['success']);
    }

    // ==========================================
    // TARGETING OPERATIONS (10 tests)
    // ==========================================

    /** @test */
    public function it_adds_topic_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addTopicTargeting('789', [100, 200]);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['topics_added']);
    }

    /** @test */
    public function it_adds_placement_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addPlacements('789', ['youtube.com']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['placements_added']);
    }

    /** @test */
    public function it_adds_demographic_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[], []]
            ], 200)
        ]);

        $result = $this->service->addDemographicTargeting('789', [
            'age_ranges' => ['AGE_RANGE_25_34'],
            'genders' => ['MALE'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['demographics_added']);
    }

    /** @test */
    public function it_adds_location_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addLocationTargeting('123456', [
            ['geo_target_id' => '1023191'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['locations_added']);
    }

    /** @test */
    public function it_adds_proximity_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addProximityTargeting('123456', [
            ['latitude' => 37.7749, 'longitude' => -122.4194, 'radius' => 10],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['proximities_added']);
    }

    /** @test */
    public function it_adds_language_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addLanguageTargeting('123456', [1000, 1001]);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['languages_added']);
    }

    /** @test */
    public function it_adds_device_bid_modifiers()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addDeviceBidModifiers('123456', [
            ['type' => 'MOBILE', 'bid_modifier' => 1.5],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['device_modifiers_added']);
    }

    /** @test */
    public function it_adds_ad_schedule()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addAdSchedule('123456', [
            ['day' => 'MONDAY', 'start_hour' => 9, 'end_hour' => 17],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['schedules_added']);
    }

    /** @test */
    public function it_adds_parental_status_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addParentalStatusTargeting('789', ['PARENT']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['parental_statuses_added']);
    }

    /** @test */
    public function it_adds_household_income_targeting()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addHouseholdIncomeTargeting('789', ['INCOME_RANGE_50_60']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['income_ranges_added']);
    }

    // ==========================================
    // AUDIENCE OPERATIONS (7 tests)
    // ==========================================

    /** @test */
    public function it_adds_in_market_audience()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addInMarketAudience('789', [100]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['audiences_added']);
    }

    /** @test */
    public function it_adds_affinity_audience()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addAffinityAudience('789', [200]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['audiences_added']);
    }

    /** @test */
    public function it_creates_custom_audience()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/customAudiences/999']
                ]
            ], 200)
        ]);

        $result = $this->service->createCustomAudience([
            'name' => 'Test Audience',
            'keywords' => ['keyword1', 'keyword2'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('999', $result['audience_id']);
    }

    /** @test */
    public function it_adds_custom_audience_to_ad_group()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addCustomAudience('789', [999]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['audiences_added']);
    }

    /** @test */
    public function it_creates_remarketing_list()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/userLists/888']
                ]
            ], 200)
        ]);

        $result = $this->service->createRemarketingList([
            'name' => 'Website Visitors',
            'membership_days' => 30,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('888', $result['user_list_id']);
    }

    /** @test */
    public function it_uploads_customer_match_data()
    {
        Http::fake([
            '*' => Http::response(['results' => [[]]], 200)
        ]);

        $result = $this->service->uploadCustomerMatch('888', [
            ['email' => 'test@example.com', 'phone' => '1234567890'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['uploaded_count']);
    }

    /** @test */
    public function it_adds_remarketing_audience_to_ad_group()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->addRemarketingAudience('789', [888]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['audiences_added']);
    }

    // ==========================================
    // BIDDING OPERATIONS (2 tests)
    // ==========================================

    /** @test */
    public function it_creates_bidding_strategy()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/biddingStrategies/777']
                ]
            ], 200)
        ]);

        $result = $this->service->createBiddingStrategy([
            'name' => 'Test Strategy',
            'type' => 'TARGET_CPA',
            'target_cpa' => 10,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('777', $result['strategy_id']);
    }

    /** @test */
    public function it_assigns_bidding_strategy_to_campaign()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[]]
            ], 200)
        ]);

        $result = $this->service->assignBiddingStrategy('123456', '777');

        $this->assertTrue($result['success']);
    }

    // ==========================================
    // CONVERSION OPERATIONS (3 tests)
    // ==========================================

    /** @test */
    public function it_creates_conversion_action()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/conversionActions/666']
                ]
            ], 200)
        ]);

        $result = $this->service->createConversionAction([
            'name' => 'Purchase',
            'category' => 'PURCHASE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('666', $result['conversion_action_id']);
    }

    /** @test */
    public function it_uploads_offline_conversions()
    {
        Http::fake([
            '*' => Http::response(['results' => [[]]], 200)
        ]);

        $result = $this->service->uploadOfflineConversions([
            ['gclid' => 'test_gclid', 'conversion_action_id' => '666', 'conversion_time' => '2024-01-01 12:00:00', 'value' => 100],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['conversions_uploaded']);
    }

    /** @test */
    public function it_gets_conversion_actions()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['conversion_action' => ['id' => '666', 'name' => 'Purchase']]
                ]
            ], 200)
        ]);

        $result = $this->service->getConversionActions();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('conversion_actions', $result);
    }

    // ==========================================
    // HELPER METHODS (3 tests)
    // ==========================================

    /** @test */
    public function it_gets_available_objectives()
    {
        $objectives = $this->service->getAvailableObjectives();

        $this->assertIsArray($objectives);
        $this->assertContains('MAXIMIZE_CONVERSIONS', $objectives);
        $this->assertContains('TARGET_CPA', $objectives);
    }

    /** @test */
    public function it_gets_available_campaign_types()
    {
        $campaignTypes = $this->service->getAvailableCampaignTypes();

        $this->assertIsArray($campaignTypes);
        $this->assertArrayHasKey('SEARCH', $campaignTypes);
        $this->assertArrayHasKey('DISPLAY', $campaignTypes);
        $this->assertArrayHasKey('VIDEO', $campaignTypes);
    }

    /** @test */
    public function it_syncs_account_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'results' => [[
                    'customer' => ['id' => '1234567890', 'descriptive_name' => 'Test Account']
                ]]
            ], 200)
        ]);

        $result = $this->service->syncAccount();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('account', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
