<?php

namespace Tests\Unit\Services\Platform;

use Tests\TestCase;
use App\Services\Platform\MetaPostsService;
use App\Models\Platform\MetaAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetaPostsServiceTest extends TestCase
{
    use RefreshDatabase;

    private MetaPostsService $service;
    private string $testAccessToken = 'test-access-token-12345';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MetaPostsService();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_fetches_facebook_posts_and_caches_result()
    {
        $pageId = 'page_123456';

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_1',
                        'message' => 'Test post 1',
                        'created_time' => '2025-01-01T12:00:00+0000',
                        'likes' => ['summary' => ['total_count' => 100]],
                        'comments' => ['summary' => ['total_count' => 20]],
                        'shares' => ['count' => 10]
                    ]
                ],
                'paging' => [
                    'next' => 'next-page-url'
                ]
            ], 200)
        ]);

        // First call - should hit API
        $result1 = $this->service->fetchFacebookPosts(
            $pageId,
            $this->testAccessToken,
            25
        );

        $this->assertIsArray($result1);
        $this->assertArrayHasKey('posts', $result1);
        $this->assertArrayHasKey('paging', $result1);
        $this->assertArrayHasKey('count', $result1);
        $this->assertCount(1, $result1['posts']);

        // Verify HTTP was called once
        Http::assertSentCount(1);

        // Second call - should use cache
        $result2 = $this->service->fetchFacebookPosts(
            $pageId,
            $this->testAccessToken,
            25
        );

        // Should still be same data
        $this->assertEquals($result1, $result2);

        // HTTP should not be called again (still 1)
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_transforms_facebook_posts_to_standard_format()
    {
        $pageId = 'page_123456';

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_123',
                        'message' => 'Amazing product launch!',
                        'full_picture' => 'https://example.com/image.jpg',
                        'created_time' => '2025-01-15T10:30:00+0000',
                        'updated_time' => '2025-01-15T11:00:00+0000',
                        'permalink_url' => 'https://facebook.com/post/123',
                        'type' => 'photo',
                        'is_published' => true,
                        'likes' => ['summary' => ['total_count' => 250]],
                        'comments' => ['summary' => ['total_count' => 45]],
                        'shares' => ['count' => 30],
                        'reactions' => ['summary' => ['total_count' => 300]],
                        'insights' => [
                            'data' => [
                                ['name' => 'post_impressions', 'values' => [['value' => 5000]]],
                                ['name' => 'post_engaged_users', 'values' => [['value' => 350]]]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->fetchFacebookPosts(
            $pageId,
            $this->testAccessToken
        );

        $post = $result['posts'][0];

        // Verify standard format
        $this->assertEquals('post_123', $post['id']);
        $this->assertEquals('facebook', $post['platform']);
        $this->assertEquals('Amazing product launch!', $post['message']);
        $this->assertEquals('https://example.com/image.jpg', $post['media_url']);
        $this->assertEquals('photo', $post['media_type']);
        $this->assertEquals('https://facebook.com/post/123', $post['permalink']);
        $this->assertTrue($post['is_published']);

        // Verify engagement metrics
        $this->assertEquals(250, $post['engagement']['likes']);
        $this->assertEquals(45, $post['engagement']['comments']);
        $this->assertEquals(30, $post['engagement']['shares']);
        $this->assertEquals(300, $post['engagement']['reactions']);

        // Verify insights
        $this->assertEquals(5000, $post['insights']['post_impressions']);
        $this->assertEquals(350, $post['insights']['post_engaged_users']);
    }

    /** @test */
    public function it_fetches_instagram_posts_correctly()
    {
        $instagramAccountId = 'ig_123456';

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'ig_post_1',
                        'caption' => 'Check out our new collection! #fashion',
                        'media_type' => 'IMAGE',
                        'media_url' => 'https://example.com/ig-image.jpg',
                        'permalink' => 'https://instagram.com/p/abc123',
                        'timestamp' => '2025-01-10T15:00:00+0000',
                        'username' => 'test_brand',
                        'like_count' => 500,
                        'comments_count' => 75,
                        'insights' => [
                            'data' => [
                                ['name' => 'impressions', 'values' => [['value' => 8000]]],
                                ['name' => 'reach', 'values' => [['value' => 6500]]],
                                ['name' => 'engagement', 'values' => [['value' => 600]]]
                            ]
                        ]
                    ]
                ],
                'paging' => []
            ], 200)
        ]);

        $result = $this->service->fetchInstagramPosts(
            $instagramAccountId,
            $this->testAccessToken,
            25
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('posts', $result);
        $this->assertCount(1, $result['posts']);

        $post = $result['posts'][0];

        // Verify Instagram-specific format
        $this->assertEquals('ig_post_1', $post['id']);
        $this->assertEquals('instagram', $post['platform']);
        $this->assertEquals('Check out our new collection! #fashion', $post['message']);
        $this->assertEquals('image', strtolower($post['media_type']));
        $this->assertEquals('test_brand', $post['username']);
        $this->assertEquals(500, $post['engagement']['likes']);
        $this->assertEquals(75, $post['engagement']['comments']);
        $this->assertEquals(0, $post['engagement']['shares']); // Instagram doesn't provide shares
    }

    /** @test */
    public function it_aggregates_posts_from_multiple_meta_accounts()
    {
        // Create test organization
        $org = \App\Models\Core\Organization::factory()->create();

        // Create multiple Meta accounts
        $account1 = MetaAccount::factory()->create([
            'org_id' => $org->id,
            'page_id' => 'page_1',
            'page_name' => 'Brand Page',
            'access_token' => 'token_1',
            'status' => 'active'
        ]);

        $account2 = MetaAccount::factory()->create([
            'org_id' => $org->id,
            'instagram_account_id' => 'ig_1',
            'instagram_username' => 'brand_instagram',
            'access_token' => 'token_2',
            'status' => 'active'
        ]);

        Http::fake([
            '*/page_1/*' => Http::response([
                'data' => [
                    ['id' => 'fb_post_1', 'message' => 'Facebook post', 'created_time' => '2025-01-15T12:00:00+0000']
                ]
            ], 200),
            '*/ig_1/*' => Http::response([
                'data' => [
                    ['id' => 'ig_post_1', 'caption' => 'Instagram post', 'timestamp' => '2025-01-14T12:00:00+0000']
                ]
            ], 200)
        ]);

        $result = $this->service->fetchAllOrganizationPosts(
            $org->id,
            'all',
            50
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('facebook', $result);
        $this->assertArrayHasKey('instagram', $result);
        $this->assertArrayHasKey('total_count', $result);

        // Verify posts were fetched from both platforms
        $this->assertNotEmpty($result['facebook']);
        $this->assertNotEmpty($result['instagram']);
        $this->assertEquals(2, $result['total_count']);

        // Verify account attribution
        $this->assertEquals('Brand Page', $result['facebook'][0]['account_name']);
        $this->assertEquals($account1->id, $result['facebook'][0]['account_id']);

        $this->assertEquals('brand_instagram', $result['instagram'][0]['account_name']);
        $this->assertEquals($account2->id, $result['instagram'][0]['account_id']);
    }

    /** @test */
    public function it_sorts_posts_by_date_descending()
    {
        $org = \App\Models\Core\Organization::factory()->create();

        MetaAccount::factory()->create([
            'org_id' => $org->id,
            'page_id' => 'page_1',
            'access_token' => 'token',
            'status' => 'active'
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    ['id' => 'post_1', 'message' => 'Oldest', 'created_time' => '2025-01-01T10:00:00+0000'],
                    ['id' => 'post_2', 'message' => 'Middle', 'created_time' => '2025-01-10T10:00:00+0000'],
                    ['id' => 'post_3', 'message' => 'Newest', 'created_time' => '2025-01-20T10:00:00+0000']
                ]
            ], 200)
        ]);

        $result = $this->service->fetchAllOrganizationPosts($org->id);

        $posts = $result['facebook'];

        // Verify sorted by most recent first
        $this->assertEquals('Newest', $posts[0]['message']);
        $this->assertEquals('Middle', $posts[1]['message']);
        $this->assertEquals('Oldest', $posts[2]['message']);
    }

    /** @test */
    public function it_filters_posts_by_platform()
    {
        $org = \App\Models\Core\Organization::factory()->create();

        MetaAccount::factory()->create([
            'org_id' => $org->id,
            'page_id' => 'page_1',
            'access_token' => 'token',
            'status' => 'active'
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [['id' => 'fb_post_1', 'message' => 'FB']]
            ], 200)
        ]);

        // Fetch only Facebook
        $result = $this->service->fetchAllOrganizationPosts(
            $org->id,
            'facebook',
            50
        );

        $this->assertNotEmpty($result['facebook']);
        $this->assertEmpty($result['instagram']);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'code' => 190
                ]
            ], 400)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Facebook API error/i');

        $this->service->fetchFacebookPosts(
            'invalid_page',
            'invalid_token'
        );
    }

    /** @test */
    public function it_skips_failed_accounts_in_organization_fetch()
    {
        $org = \App\Models\Core\Organization::factory()->create();

        // Account 1 - will succeed
        MetaAccount::factory()->create([
            'org_id' => $org->id,
            'page_id' => 'page_good',
            'access_token' => 'good_token',
            'status' => 'active'
        ]);

        // Account 2 - will fail
        MetaAccount::factory()->create([
            'org_id' => $org->id,
            'page_id' => 'page_bad',
            'access_token' => 'bad_token',
            'status' => 'active'
        ]);

        Http::fake([
            '*/page_good/*' => Http::response([
                'data' => [['id' => 'post_1', 'message' => 'Success']]
            ], 200),
            '*/page_bad/*' => Http::response([
                'error' => ['message' => 'Token expired']
            ], 400)
        ]);

        // Should not throw exception, just skip bad account
        $result = $this->service->fetchAllOrganizationPosts($org->id);

        // Should still get posts from good account
        $this->assertNotEmpty($result['facebook']);
        $this->assertEquals(1, $result['total_count']);
    }

    /** @test */
    public function it_gets_post_details_by_id()
    {
        $postId = 'post_123456';

        Http::fake([
            "graph.facebook.com/*/v19.0/{$postId}*" => Http::response([
                'id' => $postId,
                'message' => 'Detailed post content',
                'created_time' => '2025-01-15T10:00:00+0000',
                'likes' => ['summary' => ['total_count' => 300]],
                'comments' => ['summary' => ['total_count' => 50]]
            ], 200)
        ]);

        $result = $this->service->getPostDetails(
            $postId,
            'facebook',
            $this->testAccessToken
        );

        $this->assertIsArray($result);
        $this->assertEquals($postId, $result['id']);
        $this->assertEquals('facebook', $result['platform']);
        $this->assertEquals('Detailed post content', $result['message']);
    }

    /** @test */
    public function it_clears_cache_for_specific_identifier()
    {
        $identifier = 'page_123';

        // Put something in cache
        Cache::put("meta_fb_posts_{$identifier}_" . md5('first'), ['test' => 'data'], 300);
        Cache::put("meta_ig_posts_{$identifier}_" . md5('first'), ['test' => 'data'], 300);

        // Verify cache exists
        $this->assertTrue(Cache::has("meta_fb_posts_{$identifier}_" . md5('first')));
        $this->assertTrue(Cache::has("meta_ig_posts_{$identifier}_" . md5('first')));

        // Clear cache
        $this->service->clearCache($identifier);

        // Verify cache cleared
        $this->assertFalse(Cache::has("meta_fb_posts_{$identifier}_" . md5('first')));
        $this->assertFalse(Cache::has("meta_ig_posts_{$identifier}_" . md5('first')));
    }
}
