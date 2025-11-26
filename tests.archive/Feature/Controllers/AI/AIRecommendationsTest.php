<?php

namespace Tests\Feature\Controllers\AI;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Org;
use App\Models\Campaign\ContentItem;
use App\Models\AdPlatform\AdCampaign;
use App\Services\AI\AIRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

/**
 * AI Recommendations Controller Tests (Phase 3 - Advanced AI Analytics)
 *
 * Tests AI-powered recommendation features:
 * - Similar content discovery
 * - Campaign content recommendations
 * - Best performing content
 * - Optimal posting times
 * - Audience targeting recommendations
 */
class AIRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Org $org;
    private AdCampaign $campaign;
    private ContentItem $content;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->org = Org::factory()->create();

        // Create test user with Sanctum token
        $this->user = User::factory()->create([
            'org_id' => $this->org->org_id,
        ]);
        $this->actingAs($this->user, 'sanctum');

        // Set RLS context
        DB::statement("SET app.current_org_id = '{$this->org->org_id}'");

        // Create test campaign
        $this->campaign = AdCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        // Create test content
        $this->content = ContentItem::factory()->create([
            'org_id' => $this->org->org_id,
            'title' => 'Test Content',
            'status' => 'published',
        ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);

        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_type' => 'content',
            'reference_id' => $this->content->content_id,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_get_similar_content()
    {
        // Mock the AIRecommendationService
        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getSimilarHighPerformingContent')
            ->once()
            ->with(
                $this->org->org_id,
                'content',
                $this->content->content_id,
                10
            )
            ->andReturn([
                'success' => true,
                'similar_items' => [
                    [
                        'id' => 'test-id-1',
                        'title' => 'Similar Content 1',
                        'similarity_score' => 0.95,
                        'performance_score' => 85.5,
                    ],
                    [
                        'id' => 'test-id-2',
                        'title' => 'Similar Content 2',
                        'similarity_score' => 0.88,
                        'performance_score' => 78.2,
                    ],
                ],
            ]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_type' => 'content',
            'reference_id' => $this->content->content_id,
            'limit' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'similar_items' => [
                '*' => ['id', 'title', 'similarity_score', 'performance_score']
            ],
        ]);
    }

    #[Test]
    public function it_validates_similar_content_request()
    {
        // Missing reference_type
        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_id' => $this->content->content_id,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_type']);

        // Invalid reference_type
        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_type' => 'invalid',
            'reference_id' => $this->content->content_id,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_type']);

        // Invalid reference_id format
        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_type' => 'content',
            'reference_id' => 'not-a-uuid',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_id']);

        // Invalid limit
        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_type' => 'content',
            'reference_id' => $this->content->content_id,
            'limit' => 100, // Max is 50
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    #[Test]
    public function it_can_get_campaign_content_recommendations()
    {
        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getContentRecommendationsForCampaign')
            ->once()
            ->with(
                $this->campaign->campaign_id,
                [
                    'content_type' => 'image',
                    'platform' => 'facebook',
                    'limit' => '10',
                ]
            )
            ->andReturn([
                'success' => true,
                'recommendations' => [
                    [
                        'content_id' => 'test-content-1',
                        'title' => 'Recommended Content 1',
                        'similarity' => 0.92,
                        'engagement_rate' => 5.2,
                    ],
                ],
            ]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        $response = $this->getJson("/api/ai/recommendations/campaign/{$this->campaign->campaign_id}/content?content_type=image&platform=facebook&limit=10");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'recommendations' => [
                '*' => ['content_id', 'title', 'similarity', 'engagement_rate']
            ],
        ]);
    }

    #[Test]
    public function it_validates_campaign_content_recommendations_request()
    {
        // Invalid content_type
        $response = $this->getJson("/api/ai/recommendations/campaign/{$this->campaign->campaign_id}/content?" . http_build_query([
            'content_type' => str_repeat('a', 51), // Max 50 chars
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content_type']);

        // Invalid limit
        $response = $this->getJson("/api/ai/recommendations/campaign/{$this->campaign->campaign_id}/content?" . http_build_query([
            'limit' => 0, // Min is 1
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    #[Test]
    public function it_can_get_best_performing_content()
    {
        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getBestPerformingContent')
            ->once()
            ->with(
                $this->org->org_id,
                [
                    'content_type' => 'video',
                    'platform' => 'instagram',
                    'date_range' => '2024-01-01',
                ],
                20
            )
            ->andReturn([
                'success' => true,
                'content' => [
                    [
                        'content_id' => 'best-1',
                        'title' => 'Best Performing Video',
                        'engagement_rate' => 12.5,
                        'impressions' => 50000,
                    ],
                ],
            ]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/ai/recommendations/best-performing?" . http_build_query([
            'content_type' => 'video',
            'platform' => 'instagram',
            'date_range' => '2024-01-01',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'content' => [
                '*' => ['content_id', 'title', 'engagement_rate', 'impressions']
            ],
        ]);
    }

    #[Test]
    public function it_validates_best_performing_content_request()
    {
        // Invalid date_range format
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/ai/recommendations/best-performing?" . http_build_query([
            'date_range' => 'not-a-date',
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_range']);

        // Invalid limit
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/ai/recommendations/best-performing?" . http_build_query([
            'limit' => 150, // Max is 100
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    #[Test]
    public function it_can_get_optimal_posting_times()
    {
        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getOptimalPostingTimes')
            ->once()
            ->with($this->org->org_id, 'facebook')
            ->andReturn([
                'success' => true,
                'optimal_times' => [
                    [
                        'day' => 'Monday',
                        'hour' => 10,
                        'engagement_score' => 8.5,
                    ],
                    [
                        'day' => 'Wednesday',
                        'hour' => 14,
                        'engagement_score' => 9.2,
                    ],
                ],
            ]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/ai/recommendations/optimal-times?platform=facebook");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'optimal_times' => [
                '*' => ['day', 'hour', 'engagement_score']
            ],
        ]);
    }

    #[Test]
    public function it_validates_optimal_posting_times_request()
    {
        // Invalid platform
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/ai/recommendations/optimal-times?platform=invalid");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platform']);
    }

    #[Test]
    public function it_can_get_audience_recommendations()
    {
        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getAudienceTargetingRecommendations')
            ->once()
            ->with($this->campaign->campaign_id)
            ->andReturn([
                'success' => true,
                'recommendations' => [
                    [
                        'segment' => 'Age 25-34',
                        'engagement_rate' => 6.8,
                        'conversion_rate' => 4.2,
                        'recommendation' => 'High engagement segment - increase budget allocation',
                    ],
                ],
            ]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        $response = $this->getJson("/api/ai/recommendations/campaign/{$this->campaign->campaign_id}/audience");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'recommendations' => [
                '*' => ['segment', 'engagement_rate', 'conversion_rate', 'recommendation']
            ],
        ]);
    }

    #[Test]
    public function it_enforces_rate_limiting_on_ai_endpoints()
    {
        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getSimilarHighPerformingContent')
            ->andReturn(['success' => true, 'similar_items' => []]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        // Make multiple requests to trigger rate limit
        // Note: This depends on your throttle.ai middleware configuration
        // Assuming it's set to 10 requests per minute
        for ($i = 0; $i < 12; $i++) {
            $response = $this->postJson('/api/ai/recommendations/similar', [
                'reference_type' => 'content',
                'reference_id' => $this->content->content_id,
            ]);

            if ($i < 10) {
                $response->assertStatus(200);
            } else {
                // Should be rate limited after 10 requests
                $response->assertStatus(429);
            }
        }
    }

    #[Test]
    public function it_respects_multi_tenancy_for_similar_content()
    {
        // Create another organization and content
        $otherOrg = Org::factory()->create();
        $otherContent = ContentItem::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $mockService = Mockery::mock(AIRecommendationService::class);
        $mockService->shouldReceive('getSimilarHighPerformingContent')
            ->once()
            ->with(
                $this->org->org_id, // Should use current user's org
                'content',
                $otherContent->content_id,
                10
            )
            ->andReturn([
                'success' => true,
                'similar_items' => [],
            ]);

        $this->app->instance(AIRecommendationService::class, $mockService);

        // User can search for similar content but results will be filtered to their org
        $response = $this->postJson('/api/ai/recommendations/similar', [
            'reference_type' => 'content',
            'reference_id' => $otherContent->content_id,
        ]);

        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
