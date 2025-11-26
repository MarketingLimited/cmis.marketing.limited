<?php

namespace Tests\Unit\Services\Ads;

use Tests\TestCase;
use App\Services\Ads\MetaAdsService;
use App\Models\{Integration, SocialAccount, AdCampaign};
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetaAdsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MetaAdsService $service;
    protected Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = Integration::factory()->create([
            'platform' => 'meta_ads',
            'status' => 'active',
            'access_token' => 'test_token_123',
            'metadata' => ['ad_account_id' => '123456789'],
        ]);

        $this->service = new MetaAdsService($this->integration);
    }

    public function test_get_configuration_returns_correct_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getConfiguration');
        $method->setAccessible(true);
        $config = $method->invoke($this->service);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('api_base', $config);
        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('account', $config['fields']);
        $this->assertArrayHasKey('campaigns', $config['fields']);
        $this->assertArrayHasKey('adsets', $config['fields']);
        $this->assertArrayHasKey('ads', $config['fields']);
    }

    public function test_sync_account_without_ad_account_id()
    {
        $this->integration->update(['metadata' => []]);

        $result = $this->service->syncAccount();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Ad account ID not configured', $result['error']);
    }

    public function test_sync_account_successfully()
    {
        Http::fake([
            'graph.facebook.com/*/act_*' => Http::response([
                'id' => 'act_123456789',
                'name' => 'Test Ad Account',
                'account_status' => 1,
                'currency' => 'USD',
                'timezone_name' => 'America/Los_Angeles',
                'amount_spent' => '1000.50',
                'balance' => '5000.00',
            ], 200),
        ]);

        $result = $this->service->syncAccount();

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(SocialAccount::class, $result['account']);
        $this->assertEquals('Test Ad Account', $result['account']->account_name);
        $this->assertTrue($result['account']->is_active);
        $this->assertEquals('USD', $result['account']->metadata['currency']);
    }

    public function test_sync_campaigns_successfully()
    {
        Http::fake([
            'graph.facebook.com/*/act_*/campaigns*' => Http::response([
                'data' => [
                    [
                        'id' => 'campaign_123',
                        'name' => 'Summer Campaign',
                        'status' => 'ACTIVE',
                        'objective' => 'CONVERSIONS',
                        'daily_budget' => '10000',
                        'start_time' => '2024-01-01T00:00:00+0000',
                        'spend' => '500.00',
                    ]
                ]
            ], 200),
            'graph.facebook.com/*/campaign_*/adsets*' => Http::response([
                'data' => []
            ], 200),
        ]);

        $result = $this->service->syncCampaigns();

        $this->assertArrayHasKey('campaigns', $result);
        $this->assertCount(1, $result['campaigns']);

        $campaign = $result['campaigns'][0];
        $this->assertEquals('Summer Campaign', $campaign->campaign_name);
        $this->assertEquals('active', $campaign->status);
        $this->assertEquals('CONVERSIONS', $campaign->objective);
    }

    public function test_sync_campaigns_with_empty_ad_account()
    {
        $this->integration->update(['metadata' => []]);

        $result = $this->service->syncCampaigns();

        $this->assertArrayHasKey('campaigns', $result);
        $this->assertEmpty($result['campaigns']);
    }

    public function test_sync_metrics_for_campaigns()
    {
        $campaign = AdCampaign::factory()->create([
            'org_id' => $this->integration->org_id,
            'platform' => 'meta_ads',
            'platform_campaign_id' => 'campaign_123',
        ]);

        Http::fake([
            'graph.facebook.com/*/campaign_*/insights*' => Http::response([
                'data' => [
                    [
                        'impressions' => '10000',
                        'clicks' => '500',
                        'spend' => '100.50',
                        'reach' => '8000',
                        'cpc' => '0.20',
                        'cpm' => '10.05',
                        'ctr' => '5.0',
                    ]
                ]
            ], 200),
        ]);

        $result = $this->service->syncMetrics([$campaign->id]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($campaign->id, $result);
        $this->assertEquals('10000', $result[$campaign->id]['impressions']);
        $this->assertEquals('500', $result[$campaign->id]['clicks']);
    }

    public function test_validate_token_with_expired_integration()
    {
        $this->integration->update([
            'access_token' => null,
        ]);

        $result = $this->service->syncAccount();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid or expired token', $result['error']);
    }
}
