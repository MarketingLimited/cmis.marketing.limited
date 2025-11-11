<?php

namespace Tests\Unit\Services\Social;

use Tests\TestCase;
use App\Services\Social\FacebookSyncService;
use App\Models\{Integration, SocialAccount, SocialPost};
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FacebookSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FacebookSyncService $service;
    protected Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test integration
        $this->integration = Integration::factory()->create([
            'platform' => 'facebook',
            'status' => 'active',
            'access_token' => 'test_token_123',
        ]);

        $this->service = new FacebookSyncService($this->integration);
    }

    public function test_get_configuration_returns_correct_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getConfiguration');
        $method->setAccessible(true);
        $config = $method->invoke($this->service);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('api_base', $config);
        $this->assertArrayHasKey('api_version', $config);
        $this->assertArrayHasKey('fields', $config);
        $this->assertEquals('https://graph.facebook.com', $config['api_base']);
        $this->assertEquals('v18.0', $config['api_version']);
    }

    public function test_sync_account_with_invalid_token()
    {
        $this->integration->update(['access_token' => null]);

        $result = $this->service->syncAccount();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid or expired token', $result['error']);
    }

    public function test_sync_account_successfully()
    {
        Http::fake([
            'graph.facebook.com/*/me*' => Http::response([
                'id' => '123456789',
                'name' => 'Test Page',
                'username' => 'testpage',
                'link' => 'https://facebook.com/testpage',
                'fan_count' => 1000,
                'followers_count' => 1200,
                'verification_status' => 'blue_verified',
                'picture' => [
                    'data' => [
                        'url' => 'https://example.com/picture.jpg'
                    ]
                ]
            ], 200),
        ]);

        $result = $this->service->syncAccount();

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(SocialAccount::class, $result['account']);
        $this->assertEquals('Test Page', $result['account']->account_name);
        $this->assertEquals('testpage', $result['account']->account_username);
        $this->assertEquals(1000, $result['account']->followers_count);
        $this->assertTrue($result['account']->is_verified);
    }

    public function test_sync_account_with_api_failure()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 500),
        ]);

        $result = $this->service->syncAccount();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Failed to fetch page data', $result['error']);
    }

    public function test_sync_posts_successfully()
    {
        Http::fake([
            'graph.facebook.com/*/me/posts*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_123',
                        'message' => 'Test post content',
                        'created_time' => '2024-01-01T12:00:00+0000',
                        'permalink_url' => 'https://facebook.com/post/123',
                        'likes' => [
                            'summary' => ['total_count' => 50]
                        ],
                        'comments' => [
                            'summary' => ['total_count' => 10]
                        ],
                        'shares' => [
                            'count' => 5
                        ]
                    ]
                ]
            ], 200),
        ]);

        $result = $this->service->syncPosts(null, null, 25);

        $this->assertArrayHasKey('posts', $result);
        $this->assertCount(1, $result['posts']);

        $post = $result['posts'][0];
        $this->assertEquals('Test post content', $post->content_text);
        $this->assertEquals(50, $post->likes_count);
        $this->assertEquals(10, $post->comments_count);
        $this->assertEquals(5, $post->shares_count);
    }

    public function test_sync_posts_with_empty_response()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => []], 200),
        ]);

        $result = $this->service->syncPosts(null, null);

        $this->assertArrayHasKey('posts', $result);
        $this->assertEmpty($result['posts']);
    }

    public function test_sync_posts_filters_by_date_range()
    {
        $from = now()->subDays(10);
        $to = now();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_old',
                        'message' => 'Old post',
                        'created_time' => now()->subDays(20)->toIso8601String(),
                        'permalink_url' => 'https://facebook.com/post/old',
                        'likes' => ['summary' => ['total_count' => 10]],
                        'comments' => ['summary' => ['total_count' => 2]],
                    ],
                    [
                        'id' => 'post_recent',
                        'message' => 'Recent post',
                        'created_time' => now()->subDays(5)->toIso8601String(),
                        'permalink_url' => 'https://facebook.com/post/recent',
                        'likes' => ['summary' => ['total_count' => 20]],
                        'comments' => ['summary' => ['total_count' => 5]],
                    ]
                ]
            ], 200),
        ]);

        $result = $this->service->syncPosts($from, $to);

        // Should only sync the recent post within date range
        $this->assertCount(1, $result['posts']);
        $this->assertEquals('Recent post', $result['posts'][0]->content_text);
    }

    public function test_refresh_token_returns_true()
    {
        // Facebook token refresh is handled via OAuth flow
        $result = $this->service->refreshToken();

        $this->assertTrue($result);
    }
}
