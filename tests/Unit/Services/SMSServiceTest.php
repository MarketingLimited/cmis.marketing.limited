<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Communication\SMSService;

/**
 * SMS Service Unit Tests
 */
class SMSServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected SMSService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->service = app(SMSService::class);
    }

    /** @test */
    public function it_can_send_sms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'sid' => 'SM123456789',
            'status' => 'sent',
        ]);

        $result = $this->service->sendSMS([
            'to' => '+966501234567',
            'message' => 'مرحباً! لديك عرض خاص اليوم',
            'from' => '+966500000000',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('SM123456789', $result['message_id']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'method' => 'sendSMS',
        ]);
    }

    /** @test */
    public function it_can_send_bulk_sms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'sid' => 'SM123456789',
            'status' => 'sent',
        ]);

        $recipients = [
            '+966501234567',
            '+966509876543',
            '+966505555555',
        ];

        $result = $this->service->sendBulkSMS([
            'recipients' => $recipients,
            'message' => 'عرض خاص لجميع عملائنا!',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['sent_count']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'method' => 'sendBulkSMS',
        ]);
    }

    /** @test */
    public function it_can_send_otp_sms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'sid' => 'SM123456789',
            'status' => 'sent',
        ]);

        $result = $this->service->sendOTP([
            'to' => '+966501234567',
            'code' => '123456',
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('123456', $result['message']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'method' => 'sendOTP',
        ]);
    }

    /** @test */
    public function it_can_send_personalized_sms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'sid' => 'SM123456789',
            'status' => 'sent',
        ]);

        $result = $this->service->sendPersonalizedSMS([
            'to' => '+966501234567',
            'template' => 'مرحباً {{name}}، لديك خصم {{discount}}%',
            'variables' => [
                'name' => 'أحمد',
                'discount' => '20',
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'feature' => 'personalization',
        ]);
    }

    /** @test */
    public function it_can_schedule_sms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $result = $this->service->scheduleSMS([
            'to' => '+966501234567',
            'message' => 'رسالة مجدولة',
            'send_at' => now()->addHours(2),
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scheduled_id', $result);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'method' => 'scheduleSMS',
        ]);
    }

    /** @test */
    public function it_can_get_message_status()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'sid' => 'SM123456789',
            'status' => 'delivered',
            'date_sent' => '2024-01-15T10:30:00Z',
        ]);

        $result = $this->service->getMessageStatus('SM123456789');

        $this->assertTrue($result['success']);
        $this->assertEquals('delivered', $result['status']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'method' => 'getMessageStatus',
        ]);
    }

    /** @test */
    public function it_can_get_delivery_report()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'messages' => [
                ['sid' => 'SM111', 'status' => 'delivered', 'to' => '+966501234567'],
                ['sid' => 'SM222', 'status' => 'sent', 'to' => '+966509876543'],
                ['sid' => 'SM333', 'status' => 'failed', 'to' => '+966505555555'],
            ],
        ]);

        $result = $this->service->getDeliveryReport([
            'campaign_id' => 'camp_123',
            'date' => '2024-01-15',
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['messages']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'method' => 'getDeliveryReport',
        ]);
    }

    /** @test */
    public function it_validates_phone_number()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $result = $this->service->sendSMS([
            'to' => 'invalid-phone',
            'message' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('phone', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_validates_message_length()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $longMessage = str_repeat('test ', 200); // Very long message

        $result = $this->service->sendSMS([
            'to' => '+966501234567',
            'message' => $longMessage,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('segments', $result); // Multi-part SMS

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'test' => 'message_length',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('error');

        $result = $this->service->sendSMS([
            'to' => '+966501234567',
            'message' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Http::fake([
            'api.twilio.com/*' => Http::response([
                'code' => 20429,
                'message' => 'Too Many Requests',
            ], 429),
        ]);

        $result = $this->service->sendSMS([
            'to' => '+966501234567',
            'message' => 'Test',
        ]);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'test' => 'rate_limiting',
        ]);
    }

    /** @test */
    public function it_can_send_unicode_sms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $this->mockTwilioAPI('success', [
            'sid' => 'SM123456789',
            'status' => 'sent',
        ]);

        $result = $this->service->sendSMS([
            'to' => '+966501234567',
            'message' => 'مرحباً بكم في متجرنا! 🎉',
            'encoding' => 'unicode',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'SMSService',
            'feature' => 'unicode_support',
        ]);
    }
}
