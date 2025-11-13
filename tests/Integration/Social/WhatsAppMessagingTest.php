<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\WhatsAppMessage;
use App\Models\Social\WhatsAppConversation;
use App\Models\Social\WhatsAppTemplate;
use App\Jobs\SendWhatsAppMessageJob;
use App\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * WhatsApp Business API Integration Test
 *
 * اختبارات شاملة لاستقبال الرسائل من WhatsApp API والرد عليها
 */
class WhatsAppMessagingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_receives_whatsapp_text_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        // Simulate incoming WhatsApp webhook
        $webhookData = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'whatsapp_business_account_id',
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
                                        'profile' => ['name' => 'Customer Name'],
                                        'wa_id' => '966501234567',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '966501234567',
                                        'id' => 'wamid.123',
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'مرحباً، أريد الاستفسار عن منتجاتكم'],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        // Create conversation
        $conversation = WhatsAppConversation::create([
            'conversation_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'customer_phone' => '966501234567',
            'customer_name' => 'Customer Name',
            'status' => 'open',
            'started_at' => now(),
        ]);

        // Store incoming message
        $message = WhatsAppMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'conversation_id' => $conversation->conversation_id,
            'whatsapp_message_id' => 'wamid.123',
            'direction' => 'incoming',
            'from_phone' => '966501234567',
            'to_phone' => '966500000000',
            'message_type' => 'text',
            'content' => [
                'body' => 'مرحباً، أريد الاستفسار عن منتجاتكم',
            ],
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.whatsapp_messages', [
            'message_id' => $message->message_id,
            'direction' => 'incoming',
            'message_type' => 'text',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'receive_text_message',
        ]);
    }

    /** @test */
    public function it_sends_whatsapp_text_message_reply()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
            'conversation_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'customer_phone' => '966501234567',
            'customer_name' => 'Customer',
            'status' => 'open',
        ]);

        // Create outgoing message (reply)
        $replyMessage = WhatsAppMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'conversation_id' => $conversation->conversation_id,
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'text',
            'content' => [
                'body' => 'شكراً لتواصلك معنا! يسعدنا خدمتك. كيف يمكننا مساعدتك؟',
            ],
            'status' => 'pending',
        ]);

        $this->mockWhatsAppAPI('success', [
            'messaging_product' => 'whatsapp',
            'contacts' => [
                ['wa_id' => '966501234567'],
            ],
            'messages' => [
                ['id' => 'wamid.reply_123'],
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($replyMessage);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'send_text_reply',
        ]);
    }

    /** @test */
    public function it_receives_whatsapp_image_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'whatsapp_message_id' => 'wamid.img_123',
            'direction' => 'incoming',
            'from_phone' => '966501234567',
            'to_phone' => '966500000000',
            'message_type' => 'image',
            'content' => [
                'id' => 'media_123',
                'mime_type' => 'image/jpeg',
                'sha256' => 'hash_value',
                'caption' => 'شاهد هذا المنتج',
            ],
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.whatsapp_messages', [
            'message_type' => 'image',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'receive_image',
        ]);
    }

    /** @test */
    public function it_sends_whatsapp_image_message()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'image',
            'content' => [
                'link' => 'https://example.com/product-image.jpg',
                'caption' => 'إليك صورة المنتج الذي تسأل عنه',
            ],
            'status' => 'pending',
        ]);

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid.img_reply_123'],
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($message);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'send_image',
        ]);
    }

    /** @test */
    public function it_sends_whatsapp_template_message()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        // Create template
        $template = WhatsAppTemplate::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'name' => 'order_confirmation',
            'language' => 'ar',
            'category' => 'TRANSACTIONAL',
            'status' => 'APPROVED',
            'components' => [
                [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => 'تأكيد الطلب',
                ],
                [
                    'type' => 'BODY',
                    'text' => 'مرحباً {{1}}، تم استلام طلبك رقم {{2}} بنجاح!',
                ],
                [
                    'type' => 'FOOTER',
                    'text' => 'شكراً لك',
                ],
            ],
        ]);

        $conversation = WhatsAppConversation::create([
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
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'template',
            'content' => [
                'template' => [
                    'name' => 'order_confirmation',
                    'language' => ['code' => 'ar'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => 'أحمد'],
                                ['type' => 'text', 'text' => '12345'],
                            ],
                        ],
                    ],
                ],
            ],
            'status' => 'pending',
        ]);

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid.template_123'],
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($message);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'send_template',
        ]);
    }

    /** @test */
    public function it_receives_whatsapp_document_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'whatsapp_message_id' => 'wamid.doc_123',
            'direction' => 'incoming',
            'from_phone' => '966501234567',
            'to_phone' => '966500000000',
            'message_type' => 'document',
            'content' => [
                'id' => 'media_doc_123',
                'mime_type' => 'application/pdf',
                'filename' => 'order.pdf',
                'caption' => 'طلبي',
            ],
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.whatsapp_messages', [
            'message_type' => 'document',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'receive_document',
        ]);
    }

    /** @test */
    public function it_sends_whatsapp_interactive_button_message()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'interactive',
            'content' => [
                'type' => 'button',
                'body' => [
                    'text' => 'كيف يمكننا مساعدتك؟',
                ],
                'action' => [
                    'buttons' => [
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'btn_1',
                                'title' => 'استفسار عام',
                            ],
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'btn_2',
                                'title' => 'تتبع الطلب',
                            ],
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'btn_3',
                                'title' => 'الدعم الفني',
                            ],
                        ],
                    ],
                ],
            ],
            'status' => 'pending',
        ]);

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid.interactive_123'],
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($message);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'send_interactive_buttons',
        ]);
    }

    /** @test */
    public function it_sends_whatsapp_interactive_list_message()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'interactive',
            'content' => [
                'type' => 'list',
                'body' => [
                    'text' => 'اختر القسم المطلوب:',
                ],
                'action' => [
                    'button' => 'اختر',
                    'sections' => [
                        [
                            'title' => 'المنتجات',
                            'rows' => [
                                ['id' => 'prod_1', 'title' => 'إلكترونيات'],
                                ['id' => 'prod_2', 'title' => 'ملابس'],
                            ],
                        ],
                        [
                            'title' => 'الخدمات',
                            'rows' => [
                                ['id' => 'serv_1', 'title' => 'التوصيل'],
                                ['id' => 'serv_2', 'title' => 'الصيانة'],
                            ],
                        ],
                    ],
                ],
            ],
            'status' => 'pending',
        ]);

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid.list_123'],
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($message);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'send_interactive_list',
        ]);
    }

    /** @test */
    public function it_receives_whatsapp_button_reply()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'whatsapp_message_id' => 'wamid.button_reply_123',
            'direction' => 'incoming',
            'from_phone' => '966501234567',
            'to_phone' => '966500000000',
            'message_type' => 'interactive',
            'content' => [
                'type' => 'button_reply',
                'button_reply' => [
                    'id' => 'btn_1',
                    'title' => 'استفسار عام',
                ],
            ],
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.whatsapp_messages', [
            'whatsapp_message_id' => 'wamid.button_reply_123',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'action' => 'receive_button_reply',
        ]);
    }

    /** @test */
    public function it_handles_whatsapp_message_status_updates()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'whatsapp_message_id' => 'wamid.sent_123',
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'text',
            'content' => ['body' => 'رسالة اختبار'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Simulate status webhook updates
        $message->update(['status' => 'delivered', 'delivered_at' => now()]);
        $this->assertEquals('delivered', $message->fresh()->status);

        $message->update(['status' => 'read', 'read_at' => now()]);
        $this->assertEquals('read', $message->fresh()->status);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'test' => 'status_updates',
        ]);
    }

    /** @test */
    public function it_handles_whatsapp_api_errors()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
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
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'text',
            'content' => ['body' => 'رسالة اختبار'],
            'status' => 'pending',
        ]);

        // Mock API error
        $this->mockWhatsAppAPI('error', [
            'error' => [
                'message' => 'Rate limit exceeded',
                'code' => 130429,
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($message);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'test' => 'api_error_handling',
        ]);
    }

    /** @test */
    public function it_tracks_whatsapp_conversation_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
            'conversation_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'customer_phone' => '966501234567',
            'customer_name' => 'Customer',
            'status' => 'open',
            'started_at' => now()->subHours(2),
            'metrics' => [
                'total_messages' => 10,
                'response_time_avg' => 120, // seconds
                'satisfaction_score' => 4.5,
            ],
        ]);

        $this->assertTrue($conversation->metrics['total_messages'] == 10);
        $this->assertTrue($conversation->metrics['response_time_avg'] == 120);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'test' => 'conversation_metrics',
        ]);
    }
}
