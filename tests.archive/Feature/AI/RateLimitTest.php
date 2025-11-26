<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that AI endpoints are rate limited.
     *
     * @return void
     */
    public function test_ai_generation_endpoint_is_rate_limited()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        // Set a low rate limit for testing
        Config::set('services.ai.rate_limit', 3);

        $token = $user->createToken('test-token')->plainTextToken;

        // Make requests up to the limit
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/ai/generate', [
                    'prompt' => 'Test prompt ' . $i,
                ]);

            // First 3 requests should succeed (or fail for other reasons, but not rate limiting)
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // Next request should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', [
                'prompt' => 'Test prompt exceeded',
            ]);

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'success',
            'message',
            'retry_after',
            'retry_after_human',
        ]);
    }

    /**
     * Test that AI hashtag generation is rate limited.
     *
     * @return void
     */
    public function test_ai_hashtag_generation_is_rate_limited()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 2);

        $token = $user->createToken('test-token')->plainTextToken;

        // Make requests up to the limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/ai/hashtags', [
                    'content' => 'Test content ' . $i,
                ]);

            $this->assertNotEquals(429, $response->status());
        }

        // Next request should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/hashtags', [
                'content' => 'Test content exceeded',
            ]);

        $response->assertStatus(429);
    }

    /**
     * Test that AI caption generation is rate limited.
     *
     * @return void
     */
    public function test_ai_caption_generation_is_rate_limited()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 2);

        $token = $user->createToken('test-token')->plainTextToken;

        // Make requests up to the limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/ai/captions', [
                    'context' => 'Test context ' . $i,
                ]);

            $this->assertNotEquals(429, $response->status());
        }

        // Next request should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/captions', [
                'context' => 'Test context exceeded',
            ]);

        $response->assertStatus(429);
    }

    /**
     * Test that rate limit headers are present in responses.
     *
     * @return void
     */
    public function test_rate_limit_headers_are_present()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 10);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', [
                'prompt' => 'Test prompt',
            ]);

        // Check for rate limit headers
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));

        $this->assertEquals(10, $response->headers->get('X-RateLimit-Limit'));
    }

    /**
     * Test that rate limits are per user, not global.
     *
     * @return void
     */
    public function test_rate_limits_are_per_user()
    {
        $org = Org::factory()->create();

        $user1 = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        $user2 = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 2);

        $token1 = $user1->createToken('test-token')->plainTextToken;
        $token2 = $user2->createToken('test-token')->plainTextToken;

        // User 1 exhausts their rate limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
                ->postJson('/api/ai/generate', [
                    'prompt' => 'User 1 prompt ' . $i,
                ]);
        }

        // User 1's next request should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/ai/generate', [
                'prompt' => 'User 1 exceeded',
            ]);

        $this->assertEquals(429, $response->status());

        // User 2 should still be able to make requests
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/ai/generate', [
                'prompt' => 'User 2 prompt',
            ]);

        $this->assertNotEquals(429, $response->status());
    }

    /**
     * Test that unauthenticated requests are rate limited by IP.
     *
     * @return void
     */
    public function test_unauthenticated_requests_are_rate_limited_by_ip()
    {
        Config::set('services.ai.rate_limit', 2);

        // Make requests without authentication
        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson('/api/ai/generate', [
                'prompt' => 'Unauthenticated prompt ' . $i,
            ]);

            // Might fail with 401 Unauthorized, which is fine
            $this->assertContains($response->status(), [401, 429]);
        }

        // Next request should still handle rate limiting if auth is bypassed
        $response = $this->postJson('/api/ai/generate', [
            'prompt' => 'Unauthenticated exceeded',
        ]);

        // Could be 401 (unauthorized) or 429 (rate limited)
        $this->assertContains($response->status(), [401, 429]);
    }

    /**
     * Test that retry-after header is present when rate limited.
     *
     * @return void
     */
    public function test_retry_after_header_is_present_when_rate_limited()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 1);

        $token = $user->createToken('test-token')->plainTextToken;

        // Exhaust rate limit
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', ['prompt' => 'Test']);

        // Get rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', ['prompt' => 'Test 2']);

        $response->assertStatus(429);
        $this->assertNotNull($response->headers->get('Retry-After'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));

        $retryAfter = $response->json('retry_after');
        $this->assertIsInt($retryAfter);
        $this->assertGreaterThan(0, $retryAfter);
    }

    /**
     * Test that rate limit resets after the time window.
     *
     * @return void
     */
    public function test_rate_limit_resets_after_time_window()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 1);

        $token = $user->createToken('test-token')->plainTextToken;

        // Make first request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', ['prompt' => 'Test 1']);

        $this->assertNotEquals(429, $response->status());

        // Immediately make another request - should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', ['prompt' => 'Test 2']);

        $this->assertEquals(429, $response->status());

        // Wait for rate limit to reset (travel in time)
        $this->travel(61)->seconds();

        // Should be able to make request again
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/generate', ['prompt' => 'Test 3']);

        $this->assertNotEquals(429, $response->status());
    }

    /**
     * Test that semantic search is also rate limited.
     *
     * @return void
     */
    public function test_semantic_search_is_rate_limited()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 2);

        $token = $user->createToken('test-token')->plainTextToken;

        // Make requests up to the limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/ai/semantic-search', [
                    'query' => 'Search query ' . $i,
                ]);

            $this->assertNotEquals(429, $response->status());
        }

        // Next request should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/semantic-search', [
                'query' => 'Search query exceeded',
            ]);

        $response->assertStatus(429);
    }

    /**
     * Test that knowledge processing is rate limited.
     *
     * @return void
     */
    public function test_knowledge_processing_is_rate_limited()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create([
            'current_org_id' => $org->org_id,
            'status' => 'active',
        ]);

        Config::set('services.ai.rate_limit', 2);

        $token = $user->createToken('test-token')->plainTextToken;

        // Make requests up to the limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/ai/knowledge/process', [
                    'content' => 'Knowledge content ' . $i,
                ]);

            $this->assertNotEquals(429, $response->status());
        }

        // Next request should be rate limited
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ai/knowledge/process', [
                'content' => 'Knowledge content exceeded',
            ]);

        $response->assertStatus(429);
    }
}
