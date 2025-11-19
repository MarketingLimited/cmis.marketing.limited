<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Integration\WebhookHandler;
use App\Models\Core\Org;
use Illuminate\Support\Str;

/**
 * Webhook Handler Unit Tests
 */
class WebhookHandlerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected WebhookHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = app(WebhookHandler::class);
    }

    /** @test */
    public function it_can_process_facebook_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_123',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_456'],
                            'recipient' => ['id' => 'page_123'],
                            'message' => ['text' => 'مرحباً'],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->handler->processFacebookWebhook($org->org_id, $payload);

        $this->assertTrue($result['success']);
        $this->assertEquals('message_received', $result['event_type']);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'processFacebookWebhook',
        ]);
    }

    /** @test */
    public function it_can_process_instagram_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_123',
                    'time' => time(),
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'media_id' => 'media_456',
                                'comment_id' => 'comment_789',
                                'text' => 'منتج رائع!',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->handler->processInstagramWebhook($org->org_id, $payload);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'processInstagramWebhook',
        ]);
    }

    /** @test */
    public function it_can_process_twitter_webhook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $payload = [
            'tweet_create_events' => [
                [
                    'id' => 'tweet_123',
                    'text' => '@username شكراً لكم',
                    'user' => ['screen_name' => 'customer'],
                ],
            ],
        ];

        $result = $this->handler->processTwitterWebhook($org->org_id, $payload);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'processTwitterWebhook',
        ]);
    }

    /** @test */
    public function it_can_verify_webhook_signature()
    {
        $payload = '{"test": "data"}';
        $secret = 'webhook_secret_key';
        $signature = hash_hmac('sha256', $payload, $secret);

        $result = $this->handler->verifySignature($payload, $signature, $secret);

        $this->assertTrue($result);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'verifySignature',
        ]);
    }

    /** @test */
    public function it_rejects_invalid_signature()
    {
        $payload = '{"test": "data"}';
        $secret = 'webhook_secret_key';
        $invalidSignature = 'invalid_signature';

        $result = $this->handler->verifySignature($payload, $invalidSignature, $secret);

        $this->assertFalse($result);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'test' => 'signature_validation',
        ]);
    }

    /** @test */
    public function it_stores_webhook_events()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $event = [
            'platform' => 'facebook',
            'event_type' => 'message_received',
            'payload' => ['test' => 'data'],
        ];

        $result = $this->handler->storeWebhookEvent($org->org_id, $event);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.webhook_events', [
            'org_id' => $org->org_id,
            'platform' => 'facebook',
        ]);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'storeWebhookEvent',
        ]);
    }

    /** @test */
    public function it_can_retry_failed_webhooks()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $failedEvent = [
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'event_type' => 'comment_added',
            'payload' => ['test' => 'data'],
            'status' => 'failed',
            'retry_count' => 0,
        ];

        $result = $this->handler->retryFailedWebhook($failedEvent);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'retryFailedWebhook',
        ]);
    }

    /** @test */
    public function it_processes_whatsapp_webhooks()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '966501234567',
                                        'type' => 'text',
                                        'text' => ['body' => 'مرحباً'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->handler->processWhatsAppWebhook($org->org_id, $payload);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'processWhatsAppWebhook',
        ]);
    }

    /** @test */
    public function it_handles_subscription_verification()
    {
        $challenge = 'test_challenge_string';
        $verifyToken = 'my_verify_token';

        $result = $this->handler->verifySubscription($challenge, $verifyToken, 'my_verify_token');

        $this->assertTrue($result['success']);
        $this->assertEquals($challenge, $result['challenge']);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'method' => 'verifySubscription',
        ]);
    }

    /** @test */
    public function it_validates_webhook_payload_structure()
    {
        $validPayload = [
            'object' => 'page',
            'entry' => [['id' => '123', 'time' => time()]],
        ];

        $invalidPayload = [
            'invalid' => 'structure',
        ];

        $this->assertTrue($this->handler->validatePayloadStructure($validPayload, 'facebook'));
        $this->assertFalse($this->handler->validatePayloadStructure($invalidPayload, 'facebook'));

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'test' => 'payload_validation',
        ]);
    }

    /** @test */
    public function it_handles_webhook_processing_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $malformedPayload = [
            'invalid' => 'data',
        ];

        $result = $this->handler->processFacebookWebhook($org->org_id, $malformedPayload);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_tracks_webhook_processing_time()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $payload = [
            'object' => 'page',
            'entry' => [
                ['id' => 'page_123', 'time' => time()],
            ],
        ];

        $startTime = microtime(true);
        $result = $this->handler->processFacebookWebhook($org->org_id, $payload);
        $processingTime = microtime(true) - $startTime;

        $this->assertTrue($result['success']);
        $this->assertLessThan(1, $processingTime); // Should process in less than 1 second

        $this->logTestResult('passed', [
            'service' => 'WebhookHandler',
            'test' => 'performance',
        ]);
    }
}
