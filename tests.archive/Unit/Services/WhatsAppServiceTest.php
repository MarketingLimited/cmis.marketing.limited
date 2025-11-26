<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\WhatsAppService;

use PHPUnit\Framework\Attributes\Test;
/**
 * WhatsApp Service Unit Tests
 */
class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WhatsAppService::class);
    }

    #[Test]
    public function it_can_send_text_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid_123'],
            ],
        ]);

        $result = $this->service->sendTextMessage($integration, '966501234567', 'مرحباً! كيف يمكنني مساعدتك؟');

        $this->assertTrue($result['success']);
        $this->assertEquals('wamid_123', $result['message_id']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'sendTextMessage',
        ]);
    }

    #[Test]
    public function it_can_send_template_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid_template_456'],
            ],
        ]);

        $result = $this->service->sendTemplateMessage($integration, '966501234567', [
            'name' => 'order_confirmation',
            'language' => 'ar',
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'أحمد'],
                        ['type' => 'text', 'text' => '12345'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'sendTemplateMessage',
        ]);
    }

    #[Test]
    public function it_can_send_image_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid_image_789'],
            ],
        ]);

        $result = $this->service->sendImageMessage($integration, '966501234567', [
            'link' => 'https://example.com/image.jpg',
            'caption' => 'صورة المنتج',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'sendImageMessage',
        ]);
    }

    #[Test]
    public function it_can_send_document_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid_doc_111'],
            ],
        ]);

        $result = $this->service->sendDocumentMessage($integration, '966501234567', [
            'link' => 'https://example.com/catalog.pdf',
            'filename' => 'catalog.pdf',
            'caption' => 'كتالوج المنتجات',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'sendDocumentMessage',
        ]);
    }

    #[Test]
    public function it_can_send_interactive_button_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid_button_222'],
            ],
        ]);

        $result = $this->service->sendInteractiveButtonMessage($integration, '966501234567', [
            'body' => 'كيف يمكنني مساعدتك؟',
            'buttons' => [
                ['id' => 'btn_1', 'title' => 'استفسار عام'],
                ['id' => 'btn_2', 'title' => 'تتبع الطلب'],
                ['id' => 'btn_3', 'title' => 'الدعم الفني'],
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'sendInteractiveButtonMessage',
        ]);
    }

    #[Test]
    public function it_can_send_interactive_list_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'messages' => [
                ['id' => 'wamid_list_333'],
            ],
        ]);

        $result = $this->service->sendInteractiveListMessage($integration, '966501234567', [
            'body' => 'اختر من القائمة:',
            'button' => 'عرض الخيارات',
            'sections' => [
                [
                    'title' => 'المنتجات',
                    'rows' => [
                        ['id' => 'prod_1', 'title' => 'منتج 1', 'description' => 'وصف المنتج 1'],
                        ['id' => 'prod_2', 'title' => 'منتج 2', 'description' => 'وصف المنتج 2'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'sendInteractiveListMessage',
        ]);
    }

    #[Test]
    public function it_can_mark_message_as_read()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'success' => true,
        ]);

        $result = $this->service->markMessageAsRead($integration, 'wamid_123');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'markMessageAsRead',
        ]);
    }

    #[Test]
    public function it_can_get_media_url()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('success', [
            'url' => 'https://example.com/media/image.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $result = $this->service->getMediaUrl($integration, 'media_123');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('image.jpg', $result['url']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'method' => 'getMediaUrl',
        ]);
    }

    #[Test]
    public function it_validates_phone_number_format()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $result = $this->service->sendTextMessage($integration, 'invalid-phone', 'Test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('phone', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'test' => 'validation',
        ]);
    }

    #[Test]
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $this->mockWhatsAppAPI('error');

        $result = $this->service->sendTextMessage($integration, '966501234567', 'Test');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'test' => 'error_handling',
        ]);
    }

    #[Test]
    public function it_handles_rate_limiting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 130429,
                    'message' => 'Rate limit hit',
                ],
            ], 429),
        ]);

        $result = $this->service->sendTextMessage($integration, '966501234567', 'Test');

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'WhatsAppService',
            'test' => 'rate_limiting',
        ]);
    }
}
