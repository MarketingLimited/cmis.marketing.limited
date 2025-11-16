<?php

namespace Tests\Feature\GPT;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Org;
use App\Models\Strategic\Campaign;
use App\Models\Creative\ContentPlan;
use App\Services\GPTConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class GPTWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Org $org;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'current_org_id' => $this->org->org_id,
        ]);
        $this->campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_get_user_and_org_context()
    {
        $response = $this->getJson('/api/gpt/context');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $this->user->user_id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ],
                    'organization' => [
                        'id' => $this->org->org_id,
                        'name' => $this->org->name,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_list_campaigns_via_gpt()
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->getJson('/api/gpt/campaigns');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'status', 'start_date', 'end_date'],
                ],
            ]);

        $this->assertCount(4, $response->json('data')); // 3 + setUp campaign
    }

    /** @test */
    public function it_can_filter_campaigns_by_status()
    {
        Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'paused',
        ]);

        $response = $this->getJson('/api/gpt/campaigns?status=active');

        $response->assertStatus(200);

        $campaigns = $response->json('data');
        foreach ($campaigns as $campaign) {
            $this->assertEquals('active', $campaign['status']);
        }
    }

    /** @test */
    public function it_can_get_single_campaign()
    {
        $response = $this->getJson("/api/gpt/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->campaign->id,
                    'name' => $this->campaign->name,
                ],
            ]);
    }

    /** @test */
    public function it_can_create_campaign_via_gpt()
    {
        $data = [
            'name' => 'GPT Created Campaign',
            'description' => 'Campaign created through GPT interface',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 5000,
            'objectives' => ['awareness', 'engagement'],
        ];

        $response = $this->postJson('/api/gpt/campaigns', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => [
                    'name' => 'GPT Created Campaign',
                    'status' => 'draft',
                ],
            ]);

        $this->assertDatabaseHas('cmis.campaigns', [
            'name' => 'GPT Created Campaign',
            'org_id' => $this->org->org_id,
        ]);
    }

    /** @test */
    public function it_can_list_content_plans()
    {
        ContentPlan::factory()->count(2)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->getJson('/api/gpt/content-plans');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'content_type', 'status'],
                ],
            ]);
    }

    /** @test */
    public function it_can_create_content_plan()
    {
        $data = [
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Social Media Content Plan',
            'description' => 'Content for social media campaigns',
            'content_type' => 'social_post',
            'target_platforms' => ['facebook', 'instagram'],
            'tone' => 'friendly',
            'key_messages' => ['Quality products', 'Customer satisfaction'],
        ];

        $response = $this->postJson('/api/gpt/content-plans', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Content plan created successfully',
            ]);

        $this->assertDatabaseHas('cmis.content_plans', [
            'name' => 'Social Media Content Plan',
        ]);
    }

    /** @test */
    public function it_can_search_knowledge_base()
    {
        $data = [
            'query' => 'brand guidelines',
            'limit' => 5,
        ];

        $response = $this->postJson('/api/gpt/knowledge/search', $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_validates_knowledge_search_query()
    {
        $response = $this->postJson('/api/gpt/knowledge/search', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    /** @test */
    public function it_can_add_knowledge_item()
    {
        $data = [
            'title' => 'Brand Voice Guidelines',
            'content_type' => 'brand_guideline',
            'content' => 'Our brand voice is friendly, professional, and helpful.',
            'summary' => 'Guidelines for brand communication',
            'tags' => ['brand', 'voice', 'guidelines'],
        ];

        $response = $this->postJson('/api/gpt/knowledge', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Knowledge item created successfully',
            ]);
    }

    /** @test */
    public function it_can_get_ai_insights_for_campaign()
    {
        $data = [
            'context_type' => 'campaign',
            'context_id' => $this->campaign->campaign_id,
        ];

        $response = $this->postJson('/api/gpt/insights', $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'insights',
                    'recommendations',
                    'confidence',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_gpt_endpoints()
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/gpt/context');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_access_to_other_orgs_campaigns()
    {
        $otherOrg = Org::factory()->create();
        $otherCampaign = Campaign::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->getJson("/api/gpt/campaigns/{$otherCampaign->campaign_id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_create_conversation_session()
    {
        $response = $this->getJson('/api/gpt/conversation/session');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conversation session ready',
            ])
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'user_id',
                    'org_id',
                    'created_at',
                ],
            ]);
    }

    /** @test */
    public function it_can_reuse_existing_session()
    {
        $firstResponse = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $firstResponse->json('data.session_id');

        $secondResponse = $this->getJson("/api/gpt/conversation/session?session_id={$sessionId}");

        $secondResponse->assertStatus(200);
        $this->assertEquals($sessionId, $secondResponse->json('data.session_id'));
    }

    /** @test */
    public function it_can_send_message_in_conversation()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        $data = [
            'session_id' => $sessionId,
            'message' => 'What are my active campaigns?',
        ];

        $response = $this->postJson('/api/gpt/conversation/message', $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'response',
                    'session_id',
                    'tokens_used',
                ],
            ]);
    }

    /** @test */
    public function it_validates_conversation_message()
    {
        $response = $this->postJson('/api/gpt/conversation/message', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_id', 'message']);
    }

    /** @test */
    public function it_enforces_message_max_length()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        $data = [
            'session_id' => $sessionId,
            'message' => str_repeat('a', 2001), // Exceeds 2000 char limit
        ];

        $response = $this->postJson('/api/gpt/conversation/message', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_can_get_conversation_history()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        // Send a message
        $this->postJson('/api/gpt/conversation/message', [
            'session_id' => $sessionId,
            'message' => 'Hello',
        ]);

        $response = $this->getJson("/api/gpt/conversation/{$sessionId}/history");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'messages',
                    'count',
                ],
            ]);

        $this->assertGreaterThanOrEqual(2, $response->json('data.count')); // At least user + assistant message
    }

    /** @test */
    public function it_can_limit_conversation_history()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        // Send multiple messages
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/gpt/conversation/message', [
                'session_id' => $sessionId,
                'message' => "Message {$i}",
            ]);
        }

        $response = $this->getJson("/api/gpt/conversation/{$sessionId}/history?limit=5");

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(5, $response->json('data.count'));
    }

    /** @test */
    public function it_can_clear_conversation_history()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        // Send a message
        $this->postJson('/api/gpt/conversation/message', [
            'session_id' => $sessionId,
            'message' => 'Test message',
        ]);

        $response = $this->deleteJson("/api/gpt/conversation/{$sessionId}/clear");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conversation history cleared',
            ]);
    }

    /** @test */
    public function it_can_get_conversation_statistics()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        $response = $this->getJson("/api/gpt/conversation/{$sessionId}/stats");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'message_count',
                    'created_at',
                ],
            ]);
    }

    /** @test */
    public function it_handles_conversation_errors_gracefully()
    {
        // Try to get history for non-existent session
        $fakeSessionId = '00000000-0000-0000-0000-000000000000';

        $response = $this->getJson("/api/gpt/conversation/{$fakeSessionId}/history");

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_maintains_conversation_context_across_messages()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        // Send first message
        $this->postJson('/api/gpt/conversation/message', [
            'session_id' => $sessionId,
            'message' => 'My campaign name is Summer Sale 2025',
        ]);

        // Send second message referencing first
        $response = $this->postJson('/api/gpt/conversation/message', [
            'session_id' => $sessionId,
            'message' => 'What was my campaign name again?',
        ]);

        $response->assertStatus(200);
        // The AI should have context from previous messages
        $this->assertNotEmpty($response->json('data.response'));
    }
}
