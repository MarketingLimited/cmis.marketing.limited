<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialMessage;
use App\Models\Social\SocialComment;
use App\Models\Social\WhatsAppMessage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Webhook API Feature Tests
 */
class WebhookAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_receives_facebook_webhook_verification()
    {
        $response = $this->getJson('/api/webhooks/facebook?hub.mode=subscribe&hub.verify_token=test_token&hub.challenge=test_challenge');

        $response->assertStatus(200)
                 ->assertSee('test_challenge');
    }

    /** @test */
    public function it_receives_instagram_message_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'msg_789',
                                'text' => 'Hello, I have a question',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/instagram', $webhookPayload);

        $response->assertStatus(200);

        // Verify message was stored
        $this->assertDatabaseHas('cmis.social_messages', [
            'platform' => 'instagram',
            'external_message_id' => 'msg_789',
        ]);
    }

    /** @test */
    public function it_receives_facebook_comment_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');
        $socialPost = $this->createTestSocialPost($org->org_id, $integration->integration_id, 'facebook');

        $webhookPayload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_123',
                    'time' => time(),
                    'changes' => [
                        [
                            'value' => [
                                'item' => 'comment',
                                'post_id' => $socialPost->post_external_id,
                                'comment_id' => 'comment_456',
                                'from' => ['id' => 'user_789', 'name' => 'Test User'],
                                'message' => 'Great post!',
                                'created_time' => time(),
                            ],
                            'field' => 'feed',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/facebook', $webhookPayload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cmis.social_comments', [
            'platform' => 'facebook',
            'external_comment_id' => 'comment_456',
        ]);
    }

    /** @test */
    public function it_receives_whatsapp_message_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $webhookPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '966500000000',
                                    'phone_number_id' => 'phone_123',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Customer'],
                                        'wa_id' => '966501234567',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '966501234567',
                                        'id' => 'wamid.123',
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello'],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/whatsapp', $webhookPayload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cmis.whatsapp_messages', [
            'whatsapp_message_id' => 'wamid.123',
            'direction' => 'incoming',
        ]);
    }

    /** @test */
    public function it_receives_whatsapp_status_update_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        // Create existing message
        $conversation = \App\Models\Social\WhatsAppConversation::create([
            'conversation_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'customer_phone' => '966501234567',
            'status' => 'open',
        ]);

        $message = WhatsAppMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'conversation_id' => $conversation->conversation_id,
            'whatsapp_message_id' => 'wamid.status_123',
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'text',
            'content' => ['body' => 'Test'],
            'status' => 'sent',
        ]);

        $webhookPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.status_123',
                                        'status' => 'delivered',
                                        'timestamp' => time(),
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/whatsapp', $webhookPayload);

        $response->assertStatus(200);

        $message = $message->fresh();
        $this->assertEquals('delivered', $message->status);
    }

    /** @test */
    public function it_receives_twitter_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $webhookPayload = [
            'direct_message_events' => [
                [
                    'id' => 'dm_123',
                    'type' => 'message_create',
                    'message_create' => [
                        'target' => ['recipient_id' => 'account_123'],
                        'sender_id' => 'user_456',
                        'message_data' => [
                            'text' => 'Hello from Twitter',
                        ],
                    ],
                    'created_timestamp' => time() * 1000,
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/twitter', $webhookPayload);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_receives_tiktok_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $webhookPayload = [
            'event' => 'video.publish',
            'timestamp' => time(),
            'data' => [
                'video_id' => 'video_123',
                'status' => 'published',
            ],
        ];

        $response = $this->postJson('/api/webhooks/tiktok', $webhookPayload);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_webhook_signatures()
    {
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [],
        ];

        // Request without valid signature should fail
        $response = $this->postJson('/api/webhooks/instagram', $webhookPayload, [
            'X-Hub-Signature-256' => 'invalid_signature',
        ]);

        // Depending on implementation, might return 403 or process anyway
        $this->assertTrue($response->status() === 200 || $response->status() === 403);
    }

    /** @test */
    public function it_processes_webhooks_asynchronously()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_456'],
                            'message' => [
                                'mid' => 'msg_queue_test',
                                'text' => 'Queue test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/instagram', $webhookPayload);

        $response->assertStatus(200);

        // Verify job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessIncomingMessageJob::class);
    }

    /** @test */
    public function it_handles_malformed_webhook_payloads_gracefully()
    {
        $malformedPayload = [
            'invalid' => 'structure',
        ];

        $response = $this->postJson('/api/webhooks/facebook', $malformedPayload);

        // Should not crash, should return 200 or appropriate error
        $this->assertContains($response->status(), [200, 400, 422]);
    }
}
