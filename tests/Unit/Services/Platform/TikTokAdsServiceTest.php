<?php

namespace Tests\Unit\Services\Platform;

use App\Services\Platform\TikTokAdsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokAdsServiceTest extends TestCase
{
    private TikTokAdsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TikTokAdsService();
        Cache::flush();
    }

    public function test_fetch_campaigns_returns_formatted_data()
    {
        // Mock TikTok API response
        Http::fake([
            'https://business-api.tiktok.com/open_api/v1.3/campaign/get/*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => [
                    'list' => [
                        [
                            'campaign_id' => '123456',
                            'campaign_name' => 'Test Campaign',
                            'objective_type' => 'TRAFFIC',
                            'budget_mode' => 'BUDGET_MODE_DAY',
                            'budget' => 10000, // In cents
                            'status' => 'CAMPAIGN_STATUS_ENABLE'
                        ]
                    ],
                    'page_info' => [
                        'total_number' => 1,
                        'page' => 1,
                        'page_size' => 50
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns('advertiser-123', 'test-token', 1, 50);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('campaigns', $result);
        $this->assertArrayHasKey('paging', $result);
        $this->assertCount(1, $result['campaigns']);

        // Check transformation
        $campaign = $result['campaigns'][0];
        $this->assertEquals('123456', $campaign['id']);
        $this->assertEquals('Test Campaign', $campaign['name']);
        $this->assertEquals(100.00, $campaign['budget']); // Converted from cents
        $this->assertEquals('tiktok', $campaign['platform']);
    }

    public function test_fetch_campaigns_uses_cache()
    {
        Http::fake([
            'https://business-api.tiktok.com/*' => Http::response([
                'code' => 0,
                'data' => ['list' => [], 'page_info' => []]
            ], 200)
        ]);

        // First call
        $this->service->fetchCampaigns('advertiser-123', 'test-token');

        // Second call should use cache
        $this->service->fetchCampaigns('advertiser-123', 'test-token');

        // Should only hit API once
        Http::assertSentCount(1);
    }

    public function test_create_campaign_sends_correct_data()
    {
        Http::fake([
            'https://business-api.tiktok.com/open_api/v1.3/campaign/create/*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => [
                    'campaign_id' => '789012'
                ]
            ], 200)
        ]);

        $campaignData = [
            'name' => 'New Campaign',
            'objective' => 'TRAFFIC',
            'budget_mode' => 'BUDGET_MODE_DAY',
            'budget' => 50.00
        ];

        $result = $this->service->createCampaign('advertiser-123', 'test-token', $campaignData);

        $this->assertArrayHasKey('campaign_id', $result);
        $this->assertEquals('789012', $result['campaign_id']);

        // Verify request payload
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return $body['budget'] === 5000; // Converted to cents
        });
    }

    public function test_get_campaign_details_returns_full_data()
    {
        Http::fake([
            'https://business-api.tiktok.com/open_api/v1.3/campaign/get/*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'campaign_id' => '123',
                            'campaign_name' => 'Detail Test',
                            'objective_type' => 'CONVERSIONS',
                            'budget' => 20000,
                            'status' => 'CAMPAIGN_STATUS_ENABLE'
                        ]
                    ]
                ]
            ], 200),
            'https://business-api.tiktok.com/open_api/v1.3/reports/integrated/get/*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'metrics' => [
                                'spend' => 15000,
                                'impressions' => 50000,
                                'clicks' => 1000,
                                'conversions' => 50
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getCampaignDetails('advertiser-123', '123', 'test-token');

        $this->assertArrayHasKey('campaign', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(200.00, $result['campaign']['budget']);
        $this->assertEquals(150.00, $result['metrics']['spend']);
    }

    public function test_get_campaign_metrics_calculates_kpis()
    {
        Http::fake([
            'https://business-api.tiktok.com/open_api/v1.3/reports/integrated/get/*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'metrics' => [
                                'spend' => 10000, // $100
                                'impressions' => 100000,
                                'clicks' => 2000,
                                'conversions' => 50
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getCampaignMetrics('advertiser-123', '123', 'test-token');

        $this->assertArrayHasKey('ctr', $result);
        $this->assertArrayHasKey('cpc', $result);
        $this->assertArrayHasKey('cpa', $result);
        $this->assertArrayHasKey('conversion_rate', $result);

        $this->assertEquals(0.02, $result['ctr']); // 2%
        $this->assertEquals(0.05, $result['cpc']); // $0.05
        $this->assertEquals(2.00, $result['cpa']); // $2.00
        $this->assertEquals(0.025, $result['conversion_rate']); // 2.5%
    }

    public function test_clear_cache_removes_advertiser_data()
    {
        // Populate cache
        Cache::put('tiktok_campaigns_advertiser-123_1_50', ['data'], 300);

        $this->assertTrue(Cache::has('tiktok_campaigns_advertiser-123_1_50'));

        // Clear cache
        $this->service->clearCache('advertiser-123');

        $this->assertFalse(Cache::has('tiktok_campaigns_advertiser-123_1_50'));
    }

    public function test_handles_api_errors_gracefully()
    {
        Http::fake([
            'https://business-api.tiktok.com/*' => Http::response([
                'code' => 40001,
                'message' => 'Invalid access token'
            ], 401)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TikTok API Error');

        $this->service->fetchCampaigns('advertiser-123', 'invalid-token');
    }

    public function test_fetch_ad_groups_returns_hierarchy()
    {
        Http::fake([
            'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'adgroup_id' => 'ag-123',
                            'adgroup_name' => 'Test Ad Group',
                            'campaign_id' => 'c-123',
                            'budget' => 5000,
                            'status' => 'ADGROUP_STATUS_ENABLE'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->fetchAdGroups('advertiser-123', 'c-123', 'test-token');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('ag-123', $result[0]['id']);
        $this->assertEquals(50.00, $result[0]['budget']);
    }

    public function test_fetch_ads_returns_creative_data()
    {
        Http::fake([
            'https://business-api.tiktok.com/open_api/v1.3/ad/get/*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'ad_id' => 'ad-456',
                            'ad_name' => 'Test Ad',
                            'adgroup_id' => 'ag-123',
                            'creative_id' => 'cr-789',
                            'status' => 'AD_STATUS_ENABLE'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->fetchAds('advertiser-123', 'ag-123', 'test-token');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('ad-456', $result[0]['id']);
        $this->assertEquals('active', $result[0]['status']);
    }
}
