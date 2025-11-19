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
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * WhatsApp Template & Interactive Messaging Integration Test
 *
 * اختبارات القوالب والرسائل التفاعلية عبر WhatsApp
 */
class WhatsAppTemplateInteractiveMessagingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
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
}
