<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Org;
use App\Models\Strategic\Campaign;
use App\Models\Creative\ContentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use PHPUnit\Framework\Attributes\Test;
class GPTPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'current_org_id' => $this->org->org_id,
        ]);
        $this->actingAs($this->user);

        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function it_responds_to_context_request_within_acceptable_time()
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/gpt/context');

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Context endpoint took {$duration}ms (expected < 200ms)");
    }

    #[Test]
    public function it_lists_campaigns_efficiently_with_cache()
    {
        Campaign::factory()->count(50)->create(['org_id' => $this->org->org_id]);

        // First request (uncached)
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/gpt/campaigns');
        $duration1 = (microtime(true) - $startTime) * 1000;

        $response1->assertStatus(200);
        $this->assertLessThan(1000, $duration1, "Uncached campaign list took {$duration1}ms (expected < 1000ms)");

        // Second request (should be faster if caching is implemented)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/gpt/campaigns');
        $duration2 = (microtime(true) - $startTime) * 1000;

        $response2->assertStatus(200);
        $this->assertLessThan(500, $duration2, "Second campaign list took {$duration2}ms (expected < 500ms)");
    }

    #[Test]
    public function it_handles_concurrent_conversation_requests()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        $times = [];
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = $this->postJson('/api/gpt/conversation/message', [
                'session_id' => $sessionId,
                'message' => "Test message {$i}",
            ]);

            $duration = (microtime(true) - $startTime) * 1000;
            $times[] = $duration;

            $response->assertStatus(200);
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);

        $this->assertLessThan(5000, $avgTime, "Average conversation response time: {$avgTime}ms (expected < 5000ms)");
        $this->assertLessThan(10000, $maxTime, "Max conversation response time: {$maxTime}ms (expected < 10000ms)");
    }

    #[Test]
    public function it_handles_bulk_operations_efficiently()
    {
        $plans = ContentPlan::factory()->count(20)->create([
            'org_id' => $this->org->org_id,
            'status' => 'pending',
        ]);

        $startTime = microtime(true);

        $response = $this->postJson('/api/gpt/bulk-operation', [
            'operation' => 'approve',
            'resource_type' => 'content_plans',
            'resource_ids' => $plans->pluck('plan_id')->toArray(),
        ]);

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(3000, $duration, "Bulk operation took {$duration}ms (expected < 3000ms for 20 items)");

        // Verify average time per item
        $avgTimePerItem = $duration / 20;
        $this->assertLessThan(150, $avgTimePerItem, "Average time per item: {$avgTimePerItem}ms (expected < 150ms)");
    }

    #[Test]
    public function it_searches_efficiently_across_resources()
    {
        Campaign::factory()->count(30)->create(['org_id' => $this->org->org_id]);
        ContentPlan::factory()->count(30)->create(['org_id' => $this->org->org_id]);

        $startTime = microtime(true);

        $response = $this->postJson('/api/gpt/search', [
            'query' => 'campaign',
            'resources' => ['campaigns', 'content_plans'],
            'limit' => 20,
        ]);

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(1000, $duration, "Smart search took {$duration}ms (expected < 1000ms)");
    }

    #[Test]
    public function it_maintains_acceptable_memory_usage()
    {
        $memoryBefore = memory_get_usage(true);

        Campaign::factory()->count(100)->create(['org_id' => $this->org->org_id]);
        $this->getJson('/api/gpt/campaigns?limit=100');

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $this->assertLessThan(50, $memoryUsed, "Memory usage: {$memoryUsed}MB (expected < 50MB)");
    }

    #[Test]
    public function it_handles_rapid_sequential_requests()
    {
        $times = [];
        $iterations = 20;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = $this->getJson('/api/gpt/context');

            $duration = (microtime(true) - $startTime) * 1000;
            $times[] = $duration;

            $response->assertStatus(200);
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);

        $this->assertLessThan(300, $avgTime, "Average time for rapid requests: {$avgTime}ms (expected < 300ms)");

        // Check consistency (max shouldn't be more than 5x min)
        $consistency = $maxTime / ($minTime ?: 1);
        $this->assertLessThan(5, $consistency, "Response time consistency ratio: {$consistency} (expected < 5)");
    }

    #[Test]
    public function it_handles_large_query_results()
    {
        Campaign::factory()->count(500)->create(['org_id' => $this->org->org_id]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/gpt/campaigns?limit=100');

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(2000, $duration, "Large query took {$duration}ms (expected < 2000ms for 100 results)");
    }

    #[Test]
    public function it_handles_content_plan_creation_efficiently()
    {
        $campaign = Campaign::factory()->create(['org_id' => $this->org->org_id]);

        $data = [
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Performance Test Plan',
            'content_type' => 'social_post',
            'target_platforms' => ['facebook', 'instagram'],
        ];

        $startTime = microtime(true);

        $response = $this->postJson('/api/gpt/content-plans', $data);

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(500, $duration, "Content plan creation took {$duration}ms (expected < 500ms)");
    }

    #[Test]
    public function it_handles_conversation_session_creation_efficiently()
    {
        $times = [];
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            // Clear cache to force new session creation
            Cache::flush();

            $startTime = microtime(true);

            $response = $this->getJson('/api/gpt/conversation/session');

            $duration = (microtime(true) - $startTime) * 1000;
            $times[] = $duration;

            $response->assertStatus(200);
        }

        $avgTime = array_sum($times) / count($times);

        $this->assertLessThan(300, $avgTime, "Average session creation time: {$avgTime}ms (expected < 300ms)");
    }

    #[Test]
    public function it_handles_search_with_varying_query_lengths()
    {
        Campaign::factory()->count(50)->create(['org_id' => $this->org->org_id]);

        $queries = [
            'ca' => 2,           // Short query
            'campaign' => 8,     // Medium query
            'marketing campaign for social media' => 35, // Long query
        ];

        foreach ($queries as $query => $length) {
            $startTime = microtime(true);

            $response = $this->postJson('/api/gpt/search', [
                'query' => $query,
                'resources' => ['campaigns'],
                'limit' => 10,
            ]);

            $duration = (microtime(true) - $startTime) * 1000;

            $response->assertStatus(200);
            $this->assertLessThan(800, $duration, "Search with {$length} char query took {$duration}ms (expected < 800ms)");
        }
    }

    #[Test]
    public function it_scales_with_increasing_data_volume()
    {
        $dataVolumes = [10, 50, 100];
        $results = [];

        foreach ($dataVolumes as $volume) {
            // Clear and create fresh data
            Campaign::query()->delete();
            Campaign::factory()->count($volume)->create(['org_id' => $this->org->org_id]);

            $startTime = microtime(true);
            $this->getJson('/api/gpt/campaigns');
            $duration = (microtime(true) - $startTime) * 1000;

            $results[$volume] = $duration;
        }

        // Verify scaling is sub-linear (not exponential)
        $scalingFactor = $results[100] / ($results[10] ?: 1);
        $this->assertLessThan(15, $scalingFactor, "Scaling factor from 10 to 100 items: {$scalingFactor}x (expected < 15x)");
    }

    #[Test]
    public function it_handles_error_conditions_efficiently()
    {
        $startTime = microtime(true);

        // Try to access non-existent resource
        $response = $this->getJson('/api/gpt/campaigns/non-existent-id');

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(404);
        $this->assertLessThan(200, $duration, "Error response took {$duration}ms (expected < 200ms)");
    }

    #[Test]
    public function it_handles_validation_errors_quickly()
    {
        $startTime = microtime(true);

        // Send invalid bulk operation request
        $response = $this->postJson('/api/gpt/bulk-operation', [
            'operation' => 'invalid',
            'resource_type' => 'content_plans',
            'resource_ids' => [],
        ]);

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(422);
        $this->assertLessThan(100, $duration, "Validation error response took {$duration}ms (expected < 100ms)");
    }

    #[Test]
    public function it_maintains_performance_under_mixed_load()
    {
        Campaign::factory()->count(50)->create(['org_id' => $this->org->org_id]);
        ContentPlan::factory()->count(50)->create(['org_id' => $this->org->org_id]);

        $operations = [
            fn() => $this->getJson('/api/gpt/context'),
            fn() => $this->getJson('/api/gpt/campaigns'),
            fn() => $this->getJson('/api/gpt/content-plans'),
            fn() => $this->postJson('/api/gpt/search', ['query' => 'test', 'limit' => 10]),
        ];

        $times = [];

        // Execute mixed operations
        for ($i = 0; $i < 20; $i++) {
            $operation = $operations[$i % count($operations)];

            $startTime = microtime(true);
            $operation();
            $duration = (microtime(true) - $startTime) * 1000;

            $times[] = $duration;
        }

        $avgTime = array_sum($times) / count($times);

        $this->assertLessThan(1000, $avgTime, "Average time under mixed load: {$avgTime}ms (expected < 1000ms)");
    }
}
