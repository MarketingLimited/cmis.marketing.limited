<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use App\Models\AI\GeneratedMedia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AiContentGenerationTest extends TestCase
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

        // Fake storage and queue
        Storage::fake('public');
        Queue::fake();

        // Set test API key
        config(['services.google.ai_api_key' => 'test-api-key']);
    }

    /** @test */
    public function it_requires_authentication_for_ai_endpoints()
    {
        // Log out user
        auth()->logout();

        $response = $this->postJson('/api/ai/generate-ad-copy', [
            'objective' => 'awareness',
            'target_audience' => 'professionals',
            'product_description' => 'innovative product'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_generates_ad_copy_successfully()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "HEADLINES:\n- Amazing Product\n- Best Deal\n- Shop Now\n\nDESCRIPTIONS:\n- Get 50% off\n- Limited offer\n- Free shipping\n\nPRIMARY_TEXT:\nDiscover our innovative product with amazing features.\n\nCTAs:\n- Shop Now\n- Learn More\n- Get Started"]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 300
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/ai/generate-ad-copy', [
            'objective' => 'awareness',
            'target_audience' => 'young professionals aged 25-35',
            'product_description' => 'Smart fitness tracker with AI coaching',
            'requirements' => ['Professional tone', 'Focus on benefits']
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ad_copy' => [
                'headlines',
                'descriptions',
                'call_to_actions',
                'primary_text',
                'tokens_used',
                'cost'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['ad_copy']['headlines']);
        $this->assertNotEmpty($data['ad_copy']['headlines']);
        $this->assertGreaterThan(0, $data['ad_copy']['tokens_used']);
        $this->assertGreaterThan(0, $data['ad_copy']['cost']);
    }

    /** @test */
    public function it_generates_ad_designs_and_stores_them()
    {
        $base64Image = base64_encode('fake-image-data');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'data' => $base64Image,
                                        'mimeType' => 'image/png'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 200
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/ai/generate-ad-design', [
            'campaign_id' => null,
            'objective' => 'awareness',
            'brand_guidelines' => 'Modern, minimalist, blue color scheme',
            'design_requirements' => [
                'Include product image',
                'Add compelling headline',
                'Use gradient background'
            ],
            'variation_count' => 3,
            'resolution' => 'high'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'designs' => [
                '*' => [
                    'id',
                    'url',
                    'storage_path',
                    'variation',
                    'tokens_used',
                    'cost'
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(3, $data['designs']);

        // Verify designs were stored in database
        $this->assertDatabaseCount('cmis_ai.generated_media', 3);

        $media = GeneratedMedia::first();
        $this->assertEquals($this->org->id, $media->org_id);
        $this->assertEquals('image', $media->media_type);
        $this->assertEquals('completed', $media->status');
        $this->assertNotNull($media->media_url);
    }

    /** @test */
    public function it_validates_required_fields_for_ad_copy()
    {
        $response = $this->postJson('/api/ai/generate-ad-copy', [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'objective',
            'target_audience',
            'product_description'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_ad_design()
    {
        $response = $this->postJson('/api/ai/generate-ad-design', [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'objective',
            'brand_guidelines',
            'design_requirements'
        ]);
    }

    /** @test */
    public function it_limits_variation_count_for_designs()
    {
        $response = $this->postJson('/api/ai/generate-ad-design', [
            'objective' => 'awareness',
            'brand_guidelines' => 'Modern',
            'design_requirements' => ['Test'],
            'variation_count' => 10 // Too many
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['variation_count']);
    }

    /** @test */
    public function it_generates_video_and_creates_async_job()
    {
        Queue::fake();

        $response = $this->postJson('/api/ai/generate-video', [
            'campaign_id' => null,
            'prompt' => 'Create a promotional video showcasing our product features',
            'duration' => 10,
            'aspect_ratio' => '16:9',
            'use_fast_model' => true
        ]);

        $response->assertStatus(202); // Accepted for async processing
        $response->assertJsonStructure([
            'success',
            'message',
            'media' => [
                'id',
                'status'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('processing', $data['media']['status']);

        // Verify job was dispatched
        Queue::assertPushed(\App\Jobs\GenerateVideoJob::class);

        // Verify media record was created
        $this->assertDatabaseHas('cmis_ai.generated_media', [
            'org_id' => $this->org->id,
            'media_type' => 'video',
            'status' => 'processing'
        ]);
    }

    /** @test */
    public function it_checks_video_generation_status()
    {
        $media = GeneratedMedia::factory()->create([
            'org_id' => $this->org->id,
            'media_type' => 'video',
            'status' => 'processing',
            'prompt_text' => 'Test video'
        ]);

        $response = $this->getJson("/api/ai/video-status/{$media->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'media' => [
                'id',
                'status',
                'media_url',
                'created_at'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('processing', $data['media']['status']);
    }

    /** @test */
    public function it_prevents_access_to_other_org_generated_media()
    {
        // Create media for different organization
        $otherOrg = Organization::factory()->create();
        $media = GeneratedMedia::factory()->create([
            'org_id' => $otherOrg->id,
            'media_type' => 'video',
            'status' => 'completed'
        ]);

        $response = $this->getJson("/api/ai/video-status/{$media->id}");

        // Should not find it (RLS will filter it out)
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'message' => 'API quota exceeded',
                    'code' => 429
                ]
            ], 429)
        ]);

        $response = $this->postJson('/api/ai/generate-ad-copy', [
            'objective' => 'awareness',
            'target_audience' => 'professionals',
            'product_description' => 'product'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false
        ]);
    }

    /** @test */
    public function it_validates_video_duration_constraints()
    {
        $response = $this->postJson('/api/ai/generate-video', [
            'prompt' => 'Test video',
            'duration' => 100, // Too long (max 90 based on validation)
            'aspect_ratio' => '16:9'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['duration']);
    }

    /** @test */
    public function it_validates_aspect_ratio_options()
    {
        $response = $this->postJson('/api/ai/generate-video', [
            'prompt' => 'Test video',
            'duration' => 10,
            'aspect_ratio' => '21:9' // Invalid ratio
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['aspect_ratio']);
    }
}
