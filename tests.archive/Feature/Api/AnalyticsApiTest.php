<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization and user
        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->id
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);

        // Clear cache
        Cache::flush();

        // Set up test data
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        // Create test AI usage logs
        DB::table('cmis_ai.ai_usage_logs')->insert([
            [
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'org_id' => $this->org->id,
                'user_id' => $this->user->id,
                'generation_type' => 'text',
                'tokens_used' => 1000,
                'cost_usd' => 0.007,
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5)
            ],
            [
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'org_id' => $this->org->id,
                'user_id' => $this->user->id,
                'generation_type' => 'image',
                'tokens_used' => 200,
                'cost_usd' => 0.10,
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3)
            ],
            [
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'org_id' => $this->org->id,
                'user_id' => $this->user->id,
                'generation_type' => 'video',
                'tokens_used' => 0,
                'cost_usd' => 1.05,
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1)
            ]
        ]);

        // Create test quota
        DB::table('cmis.ai_usage_quotas')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'org_id' => $this->org->id,
            'quota_type' => 'premium',
            'gpt_quota_daily' => 100,
            'gpt_quota_monthly' => 3000,
            'gpt_used_daily' => 10,
            'gpt_used_monthly' => 250,
            'image_quota_daily' => 20,
            'image_quota_monthly' => 600,
            'image_used_daily' => 5,
            'image_used_monthly' => 150,
            'video_quota_daily' => 10,
            'video_quota_monthly' => 300,
            'video_used_daily' => 2,
            'video_used_monthly' => 50,
            'reset_date' => Carbon::now()->addDays(15)->toDateString(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_analytics_endpoints()
    {
        auth()->logout();

        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_fetches_usage_summary_successfully()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'summary' => [
                'period' => ['start', 'end', 'days'],
                'summary' => ['total_requests', 'total_tokens', 'total_cost'],
                'by_type' => [
                    '*' => ['type', 'count', 'tokens', 'cost']
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['summary']['summary']['total_requests']);
        $this->assertGreaterThan(0, $data['summary']['summary']['total_cost']);
    }

    /** @test */
    public function it_accepts_date_range_for_usage_summary()
    {
        $startDate = Carbon::now()->subDays(7)->toDateString();
        $endDate = Carbon::now()->toDateString();

        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals($startDate, $data['summary']['period']['start']);
        $this->assertEquals($endDate, $data['summary']['period']['end']);
    }

    /** @test */
    public function it_validates_date_range_format()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary?start_date=invalid-date");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date']);
    }

    /** @test */
    public function it_fetches_daily_trend_successfully()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/daily-trend?days=30");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'trend' => [
                '*' => ['date', 'requests', 'tokens', 'cost']
            ],
            'period'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(30, $data['period']);
    }

    /** @test */
    public function it_validates_days_parameter()
    {
        // Too few days
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/daily-trend?days=3");
        $response->assertStatus(422);

        // Too many days
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/daily-trend?days=100");
        $response->assertStatus(422);
    }

    /** @test */
    public function it_fetches_quota_status_successfully()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/quota-status");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'quota' => [
                'quota_type',
                'text' => ['daily', 'monthly', 'used_daily', 'used_monthly', 'percentage_daily', 'percentage_monthly'],
                'image' => ['daily', 'monthly', 'used_daily', 'used_monthly', 'percentage_daily', 'percentage_monthly'],
                'video' => ['daily', 'monthly', 'used_daily', 'used_monthly', 'percentage_daily', 'percentage_monthly'],
                'health' => ['text', 'image', 'video', 'overall'],
                'reset_date'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('premium', $data['quota']['quota_type']);
        $this->assertEquals('healthy', $data['quota']['health']['overall']);
    }

    /** @test */
    public function it_calculates_quota_percentages_correctly()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/quota-status");

        $data = $response->json();
        $textQuota = $data['quota']['text'];

        // 10/100 = 10%
        $this->assertEquals(10.0, $textQuota['percentage_daily']);

        // 250/3000 = 8.33%
        $this->assertEquals(8.33, $textQuota['percentage_monthly']);
    }

    /** @test */
    public function it_fetches_quota_alerts()
    {
        // Update quota to trigger warning alert (75%)
        DB::table('cmis.ai_usage_quotas')
            ->where('org_id', $this->org->id)
            ->update(['gpt_used_monthly' => 2250]); // 2250/3000 = 75%

        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/quota-alerts");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'alerts' => [
                '*' => ['type', 'level', 'scope', 'percentage', 'message']
            ],
            'count'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, $data['count']);

        // Should have at least one warning alert
        $hasWarning = false;
        foreach ($data['alerts'] as $alert) {
            if ($alert['level'] === 'warning') {
                $hasWarning = true;
                break;
            }
        }
        $this->assertTrue($hasWarning);
    }

    /** @test */
    public function it_fetches_cost_by_campaign()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/cost-by-campaign");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'campaigns' => [
                '*' => ['campaign_id', 'campaign_name', 'media_count', 'total_cost', 'avg_cost']
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_fetches_media_stats()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/media-stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'stats' => [
                'by_type' => [
                    '*' => ['type', 'total']
                ],
                'by_model' => [
                    '*' => ['model', 'count', 'total_cost']
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_fetches_top_performing_media()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/top-performing-media?limit=10");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'media' => [
                '*' => ['id', 'media_type', 'ai_model', 'media_url', 'generation_cost', 'created_at']
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_validates_top_performing_limit()
    {
        // Too few
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/top-performing-media?limit=2");
        $response->assertStatus(422);

        // Too many
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/top-performing-media?limit=100");
        $response->assertStatus(422);
    }

    /** @test */
    public function it_fetches_monthly_comparison()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/monthly-comparison?months=6");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'comparison' => [
                '*' => ['month', 'month_name', 'cost']
            ],
            'period'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(6, $data['comparison']);
        $this->assertEquals(6, $data['period']);
    }

    /** @test */
    public function it_fetches_comprehensive_dashboard()
    {
        $response = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/dashboard");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'dashboard' => [
                'summary',
                'quota',
                'daily_trend',
                'media_stats',
                'top_campaigns',
                'monthly_comparison'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('summary', $data['dashboard']);
        $this->assertArrayHasKey('quota', $data['dashboard']);
    }

    /** @test */
    public function it_exports_analytics_data()
    {
        $response = $this->postJson("/api/orgs/{$this->org->id}/analytics/ai/export", [
            'type' => 'usage'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'export_type'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('usage', $data['export_type']);
    }

    /** @test */
    public function it_validates_export_type()
    {
        $response = $this->postJson("/api/orgs/{$this->org->id}/analytics/ai/export", [
            'type' => 'invalid_type'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_clears_analytics_cache()
    {
        // First, populate cache
        $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary");

        // Verify cache exists
        $cacheKey = "ai_usage_summary_{$this->org->id}__";
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        $response = $this->postJson("/api/orgs/{$this->org->id}/analytics/ai/clear-cache");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Analytics cache cleared successfully'
        ]);
    }

    /** @test */
    public function it_prevents_access_to_other_org_analytics()
    {
        // Create another organization
        $otherOrg = Organization::factory()->create();

        // Try to access other org's analytics
        $response = $this->getJson("/api/orgs/{$otherOrg->id}/analytics/ai/usage-summary");

        // Should be forbidden or not found (depends on middleware)
        $this->assertContains($response->status(), [403, 404]);
    }

    /** @test */
    public function it_caches_analytics_responses()
    {
        // First request
        $response1 = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary");
        $data1 = $response1->json();

        // Add more data
        DB::table('cmis_ai.ai_usage_logs')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'org_id' => $this->org->id,
            'user_id' => $this->user->id,
            'generation_type' => 'text',
            'tokens_used' => 5000,
            'cost_usd' => 0.035,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // Second request (should return cached data)
        $response2 = $this->getJson("/api/orgs/{$this->org->id}/analytics/ai/usage-summary");
        $data2 = $response2->json();

        // Should be same due to caching (5 min TTL)
        $this->assertEquals($data1['summary']['summary']['total_requests'], $data2['summary']['summary']['total_requests']);
    }
}
