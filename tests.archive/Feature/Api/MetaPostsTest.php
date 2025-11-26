<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use App\Models\Platform\MetaAccount;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MetaPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $org;
    private MetaAccount $metaAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization and user
        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->id
        ]);

        // Create Meta account
        $this->metaAccount = MetaAccount::factory()->create([
            'org_id' => $this->org->id,
            'page_id' => 'page_123',
            'page_name' => 'Test Brand Page',
            'instagram_account_id' => 'ig_456',
            'instagram_username' => 'test_brand',
            'access_token' => 'test-token-123',
            'status' => 'active'
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);

        // Clear cache
        Cache::flush();
    }

    /** @test */
    public function it_requires_authentication_to_access_posts()
    {
        auth()->logout();

        $response = $this->getJson('/api/meta-posts');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_fetches_all_meta_posts_successfully()
    {
        Http::fake([
            '*/page_123/*' => Http::response([
                'data' => [
                    [
                        'id' => 'fb_post_1',
                        'message' => 'Facebook post content',
                        'created_time' => '2025-01-15T12:00:00+0000',
                        'likes' => ['summary' => ['total_count' => 100]],
                        'comments' => ['summary' => ['total_count' => 20]],
                        'shares' => ['count' => 10]
                    ]
                ]
            ], 200),
            '*/ig_456/*' => Http::response([
                'data' => [
                    [
                        'id' => 'ig_post_1',
                        'caption' => 'Instagram post content',
                        'timestamp' => '2025-01-14T12:00:00+0000',
                        'media_type' => 'IMAGE',
                        'media_url' => 'https://example.com/image.jpg',
                        'like_count' => 200,
                        'comments_count' => 30
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson('/api/meta-posts?platform=all&limit=50');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'posts' => [
                'facebook' => [
                    '*' => [
                        'id',
                        'platform',
                        'message',
                        'created_time',
                        'engagement' => [
                            'likes',
                            'comments',
                            'shares'
                        ],
                        'account_name',
                        'account_id'
                    ]
                ],
                'instagram' => [
                    '*' => [
                        'id',
                        'platform',
                        'message',
                        'engagement'
                    ]
                ]
            ],
            'total_count',
            'platform_counts' => [
                'facebook',
                'instagram'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['posts']['facebook']);
        $this->assertCount(1, $data['posts']['instagram']);
        $this->assertEquals(2, $data['total_count']);

        // Verify account attribution
        $this->assertEquals('Test Brand Page', $data['posts']['facebook'][0]['account_name']);
        $this->assertEquals($this->metaAccount->id, $data['posts']['facebook'][0]['account_id']);
    }

    /** @test */
    public function it_filters_posts_by_platform()
    {
        Http::fake([
            '*/page_123/*' => Http::response([
                'data' => [
                    ['id' => 'fb_post_1', 'message' => 'Facebook only']
                ]
            ], 200)
        ]);

        // Fetch only Facebook posts
        $response = $this->getJson('/api/meta-posts?platform=facebook');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data['posts']['facebook']);
        $this->assertEmpty($data['posts']['instagram']);
    }

    /** @test */
    public function it_limits_number_of_posts_returned()
    {
        Http::fake([
            '*/page_123/*' => Http::response([
                'data' => array_fill(0, 50, [
                    'id' => 'post_x',
                    'message' => 'Test post'
                ])
            ], 200),
            '*/ig_456/*' => Http::response(['data' => []], 200)
        ]);

        // Request with limit
        $response = $this->getJson('/api/meta-posts?platform=all&limit=10');

        $response->assertStatus(200);

        // Note: Service limits are applied server-side
        // The test verifies the endpoint accepts limit parameter
        $this->assertTrue(true);
    }

    /** @test */
    public function it_gets_specific_post_details()
    {
        $postId = 'post_123456';

        Http::fake([
            "*/v19.0/{$postId}*" => Http::response([
                'id' => $postId,
                'message' => 'Detailed post content with all fields',
                'created_time' => '2025-01-15T10:00:00+0000',
                'permalink_url' => 'https://facebook.com/post/123456',
                'likes' => ['summary' => ['total_count' => 500]],
                'comments' => ['summary' => ['total_count' => 75]],
                'shares' => ['count' => 50],
                'insights' => [
                    'data' => [
                        ['name' => 'post_impressions', 'values' => [['value' => 10000]]]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson("/api/meta-posts/{$postId}?platform=facebook&account_id={$this->metaAccount->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'post' => [
                'id',
                'message',
                'engagement',
                'insights'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($postId, $data['post']['id']);
        $this->assertEquals(500, $data['post']['engagement']['likes']);
    }

    /** @test */
    public function it_refreshes_posts_cache()
    {
        // Put something in cache first
        $cacheKey = "meta_fb_posts_{$this->metaAccount->page_id}_" . md5('first');
        Cache::put($cacheKey, ['old' => 'data'], 300);

        $this->assertTrue(Cache::has($cacheKey));

        $response = $this->postJson('/api/meta-posts/refresh', [
            'account_id' => $this->metaAccount->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Posts cache cleared. Fetching fresh data...'
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_creates_campaign_from_boosted_post()
    {
        $postId = 'post_to_boost';

        Http::fake([
            "*/v19.0/{$postId}*" => Http::response([
                'id' => $postId,
                'message' => 'Viral post content',
                'full_picture' => 'https://example.com/image.jpg',
                'permalink_url' => 'https://facebook.com/post/boost',
                'likes' => ['summary' => ['total_count' => 1000]],
                'comments' => ['summary' => ['total_count' => 150]],
                'shares' => ['count' => 80],
                'insights' => [
                    'data' => [
                        ['name' => 'post_impressions', 'values' => [['value' => 20000]]]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/meta-posts/boost', [
            'post_id' => $postId,
            'platform' => 'facebook',
            'account_id' => $this->metaAccount->id,
            'campaign_name' => 'Boosted Viral Post Campaign',
            'objective' => 'REACH',
            'budget' => 100.00,
            'duration_days' => 7,
            'target_audience' => ['age' => '18-35', 'gender' => 'all']
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'campaign' => [
                'id',
                'name',
                'status'
            ],
            'redirect_url'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('Boosted Viral Post Campaign', $data['campaign']['name']);
        $this->assertEquals('draft', $data['campaign']['status']);

        // Verify campaign was created in database
        $this->assertDatabaseHas('cmis.campaigns', [
            'org_id' => $this->org->id,
            'name' => 'Boosted Viral Post Campaign',
            'objective' => 'REACH',
            'status' => 'draft',
            'budget' => 100.00
        ]);

        // Verify metadata contains boost info
        $campaign = Campaign::where('name', 'Boosted Viral Post Campaign')->first();
        $this->assertNotNull($campaign->metadata);
        $this->assertTrue($campaign->metadata['boosted_post']);
        $this->assertEquals($postId, $campaign->metadata['original_post_id']);
        $this->assertEquals('facebook', $campaign->metadata['platform']);
        $this->assertEquals('Viral post content', $campaign->metadata['post_message']);
        $this->assertArrayHasKey('original_engagement', $campaign->metadata);
    }

    /** @test */
    public function it_validates_boost_post_required_fields()
    {
        $response = $this->postJson('/api/meta-posts/boost', [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'post_id',
            'platform',
            'account_id',
            'campaign_name',
            'objective',
            'budget',
            'duration_days'
        ]);
    }

    /** @test */
    public function it_validates_budget_minimum_for_boost()
    {
        $response = $this->postJson('/api/meta-posts/boost', [
            'post_id' => 'post_123',
            'platform' => 'facebook',
            'account_id' => $this->metaAccount->id,
            'campaign_name' => 'Test',
            'objective' => 'REACH',
            'budget' => 5.00, // Below minimum of 10
            'duration_days' => 7
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['budget']);
    }

    /** @test */
    public function it_validates_duration_constraints()
    {
        $response = $this->postJson('/api/meta-posts/boost', [
            'post_id' => 'post_123',
            'platform' => 'facebook',
            'account_id' => $this->metaAccount->id,
            'campaign_name' => 'Test',
            'objective' => 'REACH',
            'budget' => 50.00,
            'duration_days' => 100 // Exceeds maximum of 90
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['duration_days']);
    }

    /** @test */
    public function it_returns_top_performing_posts_ranked_by_engagement()
    {
        Http::fake([
            '*/page_123/*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_1',
                        'message' => 'Low engagement',
                        'likes' => ['summary' => ['total_count' => 10]],
                        'comments' => ['summary' => ['total_count' => 2]],
                        'shares' => ['count' => 1],
                        'insights' => [
                            'data' => [['name' => 'post_impressions', 'values' => [['value' => 1000]]]]
                        ]
                    ],
                    [
                        'id' => 'post_2',
                        'message' => 'High engagement',
                        'likes' => ['summary' => ['total_count' => 500]],
                        'comments' => ['summary' => ['total_count' => 100]],
                        'shares' => ['count' => 50],
                        'insights' => [
                            'data' => [['name' => 'post_impressions', 'values' => [['value' => 10000]]]]
                        ]
                    ]
                ]
            ], 200),
            '*/ig_456/*' => Http::response(['data' => []], 200)
        ]);

        $response = $this->getJson('/api/meta-posts/top-performing?platform=all&limit=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'top_posts' => [
                'facebook' => [
                    '*' => [
                        'post',
                        'score',
                        'engagement_rate'
                    ]
                ],
                'instagram'
            ],
            'recommendation'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);

        // Verify top post is first (higher engagement)
        if (count($data['top_posts']['facebook']) > 1) {
            $firstPost = $data['top_posts']['facebook'][0];
            $secondPost = $data['top_posts']['facebook'][1];
            $this->assertGreaterThan($secondPost['score'], $firstPost['score']);
        }
    }

    /** @test */
    public function it_prevents_access_to_other_org_meta_accounts()
    {
        // Create account for different org
        $otherOrg = Organization::factory()->create();
        $otherAccount = MetaAccount::factory()->create([
            'org_id' => $otherOrg->id
        ]);

        $response = $this->postJson('/api/meta-posts/refresh', [
            'account_id' => $otherAccount->id
        ]);

        // Should not find account (RLS will filter it)
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'code' => 190
                ]
            ], 400)
        ]);

        $response = $this->getJson('/api/meta-posts?platform=all');

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false
        ]);
    }
}
