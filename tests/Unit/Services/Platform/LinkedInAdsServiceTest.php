<?php

namespace Tests\Unit\Services\Platform;

use App\Services\Platform\LinkedInAdsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkedInAdsServiceTest extends TestCase
{
    private LinkedInAdsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LinkedInAdsService();
        Cache::flush();
    }

    public function test_fetch_campaigns_returns_formatted_data()
    {
        // Mock LinkedIn API response
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2*' => Http::response([
                'elements' => [
                    [
                        'id' => 123456,
                        'name' => 'Test LinkedIn Campaign',
                        'account' => 'urn:li:sponsoredAccount:789',
                        'status' => 'ACTIVE',
                        'objectiveType' => 'BRAND_AWARENESS',
                        'costType' => 'CPM',
                        'dailyBudget' => [
                            'amount' => '100.00',
                            'currencyCode' => 'USD'
                        ],
                        'createdAt' => 1699876543000 // Milliseconds
                    ]
                ],
                'paging' => [
                    'total' => 1,
                    'count' => 1,
                    'start' => 0
                ]
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns('789', 'test-token', 0, 50);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('campaigns', $result);
        $this->assertArrayHasKey('paging', $result);
        $this->assertCount(1, $result['campaigns']);

        // Check transformation
        $campaign = $result['campaigns'][0];
        $this->assertEquals('123456', $campaign['id']);
        $this->assertEquals('Test LinkedIn Campaign', $campaign['name']);
        $this->assertEquals('linkedin', $campaign['platform']);
        $this->assertEquals('active', $campaign['status']);
        $this->assertArrayHasKey('created_at', $campaign);
    }

    public function test_fetch_campaigns_uses_cache()
    {
        Http::fake([
            'https://api.linkedin.com/*' => Http::response([
                'elements' => [],
                'paging' => []
            ], 200)
        ]);

        // First call
        $this->service->fetchCampaigns('account-123', 'test-token');

        // Second call should use cache
        $this->service->fetchCampaigns('account-123', 'test-token');

        // Should only hit API once
        Http::assertSentCount(1);
    }

    public function test_create_campaign_sends_correct_b2b_data()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2' => Http::response([
                'id' => 999888
            ], 201)
        ]);

        $campaignData = [
            'name' => 'B2B Lead Gen Campaign',
            'objective' => 'LEAD_GENERATION',
            'cost_type' => 'CPC',
            'daily_budget' => 200.00,
            'currency' => 'USD'
        ];

        $result = $this->service->createCampaign('account-456', 'test-token', $campaignData);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('999888', $result['id']);

        // Verify LinkedIn-specific request structure
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['account']) &&
                   isset($body['objectiveType']) &&
                   $body['objectiveType'] === 'LEAD_GENERATION';
        });
    }

    public function test_get_campaign_details_includes_metrics()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2*' => Http::response([
                'elements' => [
                    [
                        'id' => 111,
                        'name' => 'Detail Test',
                        'status' => 'ACTIVE',
                        'objectiveType' => 'WEBSITE_CONVERSIONS'
                    ]
                ]
            ], 200),
            'https://api.linkedin.com/rest/adAnalytics*' => Http::response([
                'elements' => [
                    [
                        'impressions' => 75000,
                        'clicks' => 1500,
                        'costInLocalCurrency' => '500.00',
                        'externalWebsiteConversions' => 60
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getCampaignDetails('account-123', '111', 'test-token');

        $this->assertArrayHasKey('campaign', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(75000, $result['metrics']['impressions']);
        $this->assertEquals(1500, $result['metrics']['clicks']);
    }

    public function test_get_campaign_metrics_with_engagement()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adAnalytics*' => Http::response([
                'elements' => [
                    [
                        'impressions' => 50000,
                        'clicks' => 1000,
                        'costInLocalCurrency' => '250.00',
                        'likes' => 150,
                        'comments' => 45,
                        'shares' => 30,
                        'follows' => 25,
                        'videoViews' => 5000
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getCampaignMetrics('account-123', '123', 'test-token');

        // Check basic metrics
        $this->assertEquals(50000, $result['impressions']);
        $this->assertEquals(1000, $result['clicks']);

        // Check engagement metrics
        $this->assertArrayHasKey('engagement', $result);
        $this->assertEquals(150, $result['engagement']['reactions']);
        $this->assertEquals(45, $result['engagement']['comments']);
        $this->assertEquals(30, $result['engagement']['shares']);
        $this->assertEquals(25, $result['engagement']['follows']);
        $this->assertEquals(5000, $result['engagement']['video_views']);

        // Check calculated KPIs
        $this->assertEquals(0.02, $result['ctr']); // 2%
        $this->assertEquals(0.25, $result['cpc']); // $0.25
    }

    public function test_fetch_creatives_returns_ad_content()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adCreativesV2*' => Http::response([
                'elements' => [
                    [
                        'id' => 'creative-789',
                        'campaign' => 'urn:li:sponsoredCampaign:123',
                        'type' => 'SPONSORED_UPDATE',
                        'status' => 'ACTIVE',
                        'content' => [
                            'title' => 'Test Ad Creative',
                            'description' => 'This is a test',
                            'landingPage' => 'https://example.com'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->fetchCreatives('account-123', '123', 'test-token');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('creative-789', $result[0]['id']);
        $this->assertEquals('SPONSORED_UPDATE', $result[0]['type']);
        $this->assertArrayHasKey('content', $result[0]);
    }

    public function test_clear_cache_removes_account_data()
    {
        // Populate cache
        Cache::put('linkedin_campaigns_acc-123_0_50', ['data'], 300);

        $this->assertTrue(Cache::has('linkedin_campaigns_acc-123_0_50'));

        // Clear cache
        $this->service->clearCache('acc-123');

        $this->assertFalse(Cache::has('linkedin_campaigns_acc-123_0_50'));
    }

    public function test_handles_linkedin_api_errors()
    {
        Http::fake([
            'https://api.linkedin.com/*' => Http::response([
                'status' => 401,
                'message' => 'Unauthorized'
            ], 401)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('LinkedIn API Error');

        $this->service->fetchCampaigns('account-123', 'invalid-token');
    }

    public function test_timestamp_conversion_from_milliseconds()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2*' => Http::response([
                'elements' => [
                    [
                        'id' => 123,
                        'name' => 'Test',
                        'createdAt' => 1700000000000, // Milliseconds timestamp
                        'status' => 'ACTIVE'
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns('account-123', 'test-token');

        // Should convert to standard Y-m-d H:i:s format
        $campaign = $result['campaigns'][0];
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $campaign['created_at']);
    }

    public function test_supports_multiple_b2b_objectives()
    {
        $b2bObjectives = [
            'BRAND_AWARENESS',
            'WEBSITE_VISITS',
            'ENGAGEMENT',
            'VIDEO_VIEWS',
            'LEAD_GENERATION',
            'WEBSITE_CONVERSIONS',
            'JOB_APPLICANTS'
        ];

        foreach ($b2bObjectives as $objective) {
            Http::fake([
                'https://api.linkedin.com/rest/adCampaignsV2' => Http::response(['id' => 999], 201)
            ]);

            $campaignData = [
                'name' => "Test {$objective}",
                'objective' => $objective,
                'daily_budget' => 100.00
            ];

            $result = $this->service->createCampaign('account-123', 'test-token', $campaignData);

            $this->assertArrayHasKey('id', $result);
        }
    }

    public function test_status_normalization()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2*' => Http::response([
                'elements' => [
                    ['id' => 1, 'status' => 'ACTIVE', 'name' => 'Test 1'],
                    ['id' => 2, 'status' => 'PAUSED', 'name' => 'Test 2'],
                    ['id' => 3, 'status' => 'ARCHIVED', 'name' => 'Test 3'],
                    ['id' => 4, 'status' => 'DRAFT', 'name' => 'Test 4']
                ]
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns('account-123', 'test-token');

        $this->assertEquals('active', $result['campaigns'][0]['status']);
        $this->assertEquals('paused', $result['campaigns'][1]['status']);
        $this->assertEquals('archived', $result['campaigns'][2]['status']);
        $this->assertEquals('draft', $result['campaigns'][3]['status']);
    }

    public function test_budget_control_daily_and_total()
    {
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2' => Http::response(['id' => 123], 201)
        ]);

        // Test daily budget
        $campaignData = [
            'name' => 'Daily Budget Campaign',
            'objective' => 'WEBSITE_VISITS',
            'daily_budget' => 150.00
        ];

        $this->service->createCampaign('account-123', 'test-token', $campaignData);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['dailyBudget']) &&
                   $body['dailyBudget']['amount'] === '150.00';
        });

        // Test total budget
        Http::fake([
            'https://api.linkedin.com/rest/adCampaignsV2' => Http::response(['id' => 456], 201)
        ]);

        $campaignData = [
            'name' => 'Total Budget Campaign',
            'objective' => 'CONVERSIONS',
            'total_budget' => 5000.00
        ];

        $this->service->createCampaign('account-123', 'test-token', $campaignData);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['totalBudget']) &&
                   $body['totalBudget']['amount'] === '5000.00';
        });
    }
}
