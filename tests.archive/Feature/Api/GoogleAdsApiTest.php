<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GoogleAdsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $org;
    private PlatformIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization and user
        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->id
        ]);

        // Create Google Ads integration
        $this->integration = PlatformIntegration::factory()->create([
            'org_id' => $this->org->id,
            'platform' => 'google_ads',
            'platform_account_id' => '1234567890',
            'account_name' => 'Test Google Ads Account',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'status' => 'active'
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);

        // Clear cache
        Cache::flush();

        // Set config
        config(['services.google.ads_developer_token' => 'test-dev-token']);
    }

    /** @test */
    public function it_requires_authentication_for_google_ads_endpoints()
    {
        auth()->logout();

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$this->integration->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_fetches_google_ads_campaigns_successfully()
    {
        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                [
                    'campaign' => [
                        'id' => '12345',
                        'name' => 'Summer Sale Campaign',
                        'status' => 'ENABLED',
                        'advertisingChannelType' => 'SEARCH',
                        'startDate' => '2025-01-01',
                        'endDate' => '2025-12-31'
                    ],
                    'metrics' => [
                        'impressions' => 10000,
                        'clicks' => 500,
                        'costMicros' => 250000000, // $250
                        'conversions' => 25,
                        'ctr' => 0.05,
                        'averageCpc' => 500000 // $0.50
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$this->integration->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'campaigns' => [
                '*' => [
                    'id',
                    'name',
                    'status',
                    'channel_type',
                    'start_date',
                    'end_date',
                    'metrics' => [
                        'impressions',
                        'clicks',
                        'cost',
                        'conversions',
                        'ctr',
                        'average_cpc'
                    ],
                    'platform'
                ]
            ],
            'count'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('google_ads', $data['campaigns'][0]['platform']);
        $this->assertEquals('Summer Sale Campaign', $data['campaigns'][0]['name']);
        $this->assertEquals(250.0, $data['campaigns'][0]['metrics']['cost']); // Converted from micros
    }

    /** @test */
    public function it_validates_integration_id()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id=invalid-uuid");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['integration_id']);
    }

    /** @test */
    public function it_returns_404_for_non_existent_integration()
    {
        $fakeUuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$fakeUuid}");

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Google Ads integration not found'
        ]);
    }

    /** @test */
    public function it_fetches_campaign_details_successfully()
    {
        $campaignId = '12345';

        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                [
                    'campaign' => [
                        'id' => $campaignId,
                        'name' => 'Detailed Campaign',
                        'status' => 'ENABLED',
                        'advertisingChannelType' => 'SEARCH',
                        'startDate' => '2025-01-01',
                        'endDate' => '2025-12-31',
                        'optimizationScore' => 0.85
                    ],
                    'campaignBudget' => [
                        'amountMicros' => 100000000 // $100
                    ],
                    'metrics' => [
                        'impressions' => 50000,
                        'clicks' => 2500,
                        'costMicros' => 1250000000, // $1250
                        'conversions' => 125,
                        'conversionsValue' => 12500.00,
                        'ctr' => 0.05,
                        'averageCpc' => 500000, // $0.50
                        'costPerConversion' => 10000000 // $10
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns/{$campaignId}?integration_id={$this->integration->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'campaign' => [
                'id',
                'name',
                'status',
                'channel_type',
                'optimization_score',
                'budget' => ['amount'],
                'metrics' => [
                    'impressions',
                    'clicks',
                    'cost',
                    'conversions',
                    'conversions_value',
                    'ctr',
                    'average_cpc',
                    'cost_per_conversion'
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(0.85, $data['campaign']['optimization_score']);
        $this->assertEquals(100.0, $data['campaign']['budget']['amount']);
    }

    /** @test */
    public function it_accepts_date_range_for_campaign_details()
    {
        $campaignId = '12345';
        $startDate = '2025-01-01';
        $endDate = '2025-01-31';

        Http::fake([
            '*/googleAds:searchStream' => Http::response([[
                'campaign' => ['id' => $campaignId, 'name' => 'Test'],
                'metrics' => ['impressions' => 1000]
            ]], 200)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns/{$campaignId}?integration_id={$this->integration->id}&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_fetches_ad_groups_for_campaign()
    {
        $campaignId = '12345';

        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                [
                    'adGroup' => [
                        'id' => '67890',
                        'name' => 'Ad Group 1',
                        'status' => 'ENABLED',
                        'type' => 'SEARCH_STANDARD',
                        'cpcBidMicros' => 750000 // $0.75
                    ],
                    'campaign' => [
                        'id' => $campaignId,
                        'name' => 'Parent Campaign'
                    ],
                    'metrics' => [
                        'impressions' => 5000,
                        'clicks' => 250,
                        'costMicros' => 187500000, // $187.50
                        'ctr' => 0.05
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns/{$campaignId}/ad-groups?integration_id={$this->integration->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ad_groups' => [
                '*' => [
                    'id',
                    'name',
                    'status',
                    'type',
                    'cpc_bid',
                    'campaign_id',
                    'campaign_name',
                    'metrics'
                ]
            ],
            'count'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(0.75, $data['ad_groups'][0]['cpc_bid']);
    }

    /** @test */
    public function it_fetches_ads_for_ad_group()
    {
        $adGroupId = '67890';

        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                [
                    'adGroupAd' => [
                        'ad' => [
                            'id' => '111222',
                            'name' => 'Test Ad',
                            'type' => 'RESPONSIVE_SEARCH_AD',
                            'finalUrls' => ['https://example.com'],
                            'responsiveSearchAd' => [
                                'headlines' => [
                                    ['text' => 'Headline 1'],
                                    ['text' => 'Headline 2']
                                ],
                                'descriptions' => [
                                    ['text' => 'Description 1']
                                ]
                            ]
                        ],
                        'status' => 'ENABLED'
                    ],
                    'adGroup' => [
                        'id' => $adGroupId,
                        'name' => 'Parent Ad Group'
                    ],
                    'metrics' => [
                        'impressions' => 1000,
                        'clicks' => 50,
                        'costMicros' => 25000000, // $25
                        'conversions' => 5
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/ad-groups/{$adGroupId}/ads?integration_id={$this->integration->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ads' => [
                '*' => [
                    'id',
                    'name',
                    'status',
                    'type',
                    'final_urls',
                    'headlines',
                    'descriptions',
                    'ad_group_id',
                    'ad_group_name',
                    'metrics'
                ]
            ],
            'count'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('Test Ad', $data['ads'][0]['name']);
    }

    /** @test */
    public function it_creates_google_ads_campaign()
    {
        Http::fake([
            '*/CampaignBudgetService:mutate' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/campaignBudgets/999']
                ]
            ], 200),
            '*/CampaignService:mutate' => Http::response([
                'results' => [
                    ['resourceName' => 'customers/1234567890/campaigns/888']
                ]
            ], 200)
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->id}/google-ads/campaigns", [
            'integration_id' => $this->integration->id,
            'name' => 'New Test Campaign',
            'status' => 'PAUSED',
            'channel_type' => 'SEARCH',
            'bidding_strategy' => 'MAXIMIZE_CLICKS',
            'budget_amount' => 50.00,
            'budget_delivery' => 'STANDARD'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'campaign'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_validates_campaign_creation_fields()
    {
        $response = $this->postJson("/api/orgs/{$this->org->id}/google-ads/campaigns", [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'integration_id',
            'name',
            'budget_amount'
        ]);
    }

    /** @test */
    public function it_validates_campaign_status_values()
    {
        $response = $this->postJson("/api/orgs/{$this->org->id}/google-ads/campaigns", [
            'integration_id' => $this->integration->id,
            'name' => 'Test',
            'status' => 'INVALID_STATUS',
            'budget_amount' => 50
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_validates_budget_minimum()
    {
        $response = $this->postJson("/api/orgs/{$this->org->id}/google-ads/campaigns", [
            'integration_id' => $this->integration->id,
            'name' => 'Test',
            'budget_amount' => 0.50 // Below minimum
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['budget_amount']);
    }

    /** @test */
    public function it_fetches_campaign_metrics()
    {
        $campaignId = '12345';

        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                [
                    'segments' => ['date' => '2025-01-15'],
                    'metrics' => [
                        'impressions' => 1000,
                        'clicks' => 50,
                        'costMicros' => 25000000, // $25
                        'conversions' => 5,
                        'conversionsValue' => 250.00,
                        'ctr' => 0.05,
                        'averageCpc' => 500000, // $0.50
                        'costPerConversion' => 5000000 // $5
                    ]
                ],
                [
                    'segments' => ['date' => '2025-01-16'],
                    'metrics' => [
                        'impressions' => 1200,
                        'clicks' => 60,
                        'costMicros' => 30000000, // $30
                        'conversions' => 6,
                        'conversionsValue' => 300.00,
                        'ctr' => 0.05,
                        'averageCpc' => 500000, // $0.50
                        'costPerConversion' => 5000000 // $5
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns/{$campaignId}/metrics?integration_id={$this->integration->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'metrics' => [
                '*' => [
                    'date',
                    'impressions',
                    'clicks',
                    'cost',
                    'conversions',
                    'conversions_value',
                    'ctr',
                    'average_cpc',
                    'cost_per_conversion'
                ]
            ],
            'period'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['metrics']);
        $this->assertEquals('2025-01-15', $data['metrics'][0]['date']);
    }

    /** @test */
    public function it_refreshes_google_ads_cache()
    {
        $response = $this->postJson("/api/orgs/{$this->org->id}/google-ads/refresh-cache", [
            'integration_id' => $this->integration->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Google Ads cache cleared successfully'
        ]);
    }

    /** @test */
    public function it_handles_google_ads_api_errors_gracefully()
    {
        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid customer ID',
                    'status' => 'INVALID_ARGUMENT'
                ]
            ], 400)
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$this->integration->id}");

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false
        ]);
    }

    /** @test */
    public function it_prevents_access_to_other_org_integrations()
    {
        // Create another organization
        $otherOrg = Organization::factory()->create();
        $otherIntegration = PlatformIntegration::factory()->create([
            'org_id' => $otherOrg->id,
            'platform' => 'google_ads'
        ]);

        // Try to access other org's integration
        $response = $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$otherIntegration->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_caches_campaigns_response()
    {
        Http::fake([
            '*/googleAds:searchStream' => Http::response([
                [
                    'campaign' => ['id' => '12345', 'name' => 'Test'],
                    'metrics' => ['impressions' => 1000]
                ]
            ], 200)
        ]);

        // First request
        $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$this->integration->id}");

        // Verify HTTP was called once
        Http::assertSentCount(1);

        // Second request (should use cache)
        $this->getJson("/api/orgs/{$this->org->id}/google-ads/campaigns?integration_id={$this->integration->id}");

        // Still only one HTTP call (cached)
        Http::assertSentCount(1);
    }
}
