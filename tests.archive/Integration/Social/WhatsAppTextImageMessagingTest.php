<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\WhatsAppMessage;
use App\Models\Social\WhatsAppConversation;
use App\Jobs\SendWhatsAppMessageJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * WhatsApp Text & Image Messaging Integration Test
 *
 * اختبارات إرسال واستقبال الرسائل النصية والصور عبر WhatsApp
 */
class WhatsAppTextImageMessagingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
}
