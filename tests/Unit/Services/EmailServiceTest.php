<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Communication\EmailService;
use Illuminate\Support\Facades\Mail;

/**
 * Email Service Unit Tests
 */
class EmailServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected EmailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EmailService::class);
    }

    /** @test */
    public function it_can_send_campaign_email()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendCampaignEmail([
            'to' => 'customer@example.com',
            'subject' => 'عرض خاص لك!',
            'html_content' => '<h1>خصومات الصيف</h1><p>احصل على خصم 50%</p>',
            'from_name' => 'فريق التسويق',
            'from_email' => 'marketing@example.com',
        ]);

        $this->assertTrue($result['success']);
        Mail::assertSent(function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'method' => 'sendCampaignEmail',
        ]);
    }

    /** @test */
    public function it_can_send_bulk_emails()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $recipients = [
            ['email' => 'customer1@example.com', 'name' => 'أحمد'],
            ['email' => 'customer2@example.com', 'name' => 'فاطمة'],
            ['email' => 'customer3@example.com', 'name' => 'محمد'],
        ];

        $result = $this->service->sendBulkEmails([
            'recipients' => $recipients,
            'subject' => 'رسالة جماعية',
            'html_content' => '<p>مرحباً {{name}}</p>',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['sent_count']);
        Mail::assertSent(3);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'method' => 'sendBulkEmails',
        ]);
    }

    /** @test */
    public function it_can_send_template_email()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendTemplateEmail([
            'to' => 'customer@example.com',
            'template_id' => 'welcome_email',
            'variables' => [
                'customer_name' => 'أحمد',
                'discount_code' => 'WELCOME20',
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'method' => 'sendTemplateEmail',
        ]);
    }

    /** @test */
    public function it_can_send_transactional_email()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendTransactionalEmail([
            'to' => 'customer@example.com',
            'subject' => 'تأكيد الطلب #12345',
            'html_content' => '<p>تم استلام طلبك بنجاح</p>',
            'type' => 'order_confirmation',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'method' => 'sendTransactionalEmail',
        ]);
    }

    /** @test */
    public function it_can_send_email_with_attachments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendEmailWithAttachments([
            'to' => 'customer@example.com',
            'subject' => 'الفاتورة الشهرية',
            'html_content' => '<p>مرفق الفاتورة</p>',
            'attachments' => [
                [
                    'path' => '/path/to/invoice.pdf',
                    'name' => 'فاتورة.pdf',
                    'mime' => 'application/pdf',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'method' => 'sendEmailWithAttachments',
        ]);
    }

    /** @test */
    public function it_can_track_email_opens()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendCampaignEmail([
            'to' => 'customer@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
            'track_opens' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('tracking_id', $result);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'feature' => 'email_tracking',
        ]);
    }

    /** @test */
    public function it_can_track_email_clicks()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendCampaignEmail([
            'to' => 'customer@example.com',
            'subject' => 'Test',
            'html_content' => '<p><a href="https://example.com">انقر هنا</a></p>',
            'track_clicks' => true,
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'feature' => 'click_tracking',
        ]);
    }

    /** @test */
    public function it_can_schedule_email()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $result = $this->service->scheduleEmail([
            'to' => 'customer@example.com',
            'subject' => 'رسالة مجدولة',
            'html_content' => '<p>سيتم إرسالها لاحقاً</p>',
            'send_at' => now()->addHours(2),
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scheduled_id', $result);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'method' => 'scheduleEmail',
        ]);
    }

    /** @test */
    public function it_validates_email_address()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $result = $this->service->sendCampaignEmail([
            'to' => 'invalid-email',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $result = $this->service->sendCampaignEmail([
            'to' => 'customer@example.com',
        ]);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'test' => 'required_fields',
        ]);
    }

    /** @test */
    public function it_handles_send_failures()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::shouldReceive('send')->andThrow(new \Exception('SMTP Error'));

        $result = $this->service->sendCampaignEmail([
            'to' => 'customer@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_can_personalize_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Mail::fake();

        $result = $this->service->sendCampaignEmail([
            'to' => 'customer@example.com',
            'subject' => 'مرحباً {{name}}',
            'html_content' => '<p>عزيزي {{name}}، لديك خصم {{discount}}%</p>',
            'variables' => [
                'name' => 'أحمد',
                'discount' => '20',
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'EmailService',
            'feature' => 'personalization',
        ]);
    }
}
