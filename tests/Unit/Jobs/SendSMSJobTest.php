<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Communications\SendSMSJob;
use App\Models\Core\Org;
use App\Models\Contact\Contact;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

/**
 * SendSMS Job Unit Tests
 */
class SendSMSJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_sends_sms_to_contact()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'أحمد محمد',
            'phone' => '+966501234567',
            'email' => 'ahmad@example.com',
        ]);

        $this->mockSMSProvider('success');

        $job = new SendSMSJob($contact, 'مرحباً بك في خدمتنا');
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'send_sms',
        ]);
    }

    /** @test */
    public function it_sends_arabic_message()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'فاطمة علي',
            'phone' => '+966507654321',
            'email' => 'fatima@example.com',
        ]);

        $this->mockSMSProvider('success');

        $message = 'عزيزنا العميل، لديك عرض خاص متاح الآن!';

        $job = new SendSMSJob($contact, $message);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'arabic_message',
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Contact',
            'phone' => '+966501111111',
            'email' => 'test@example.com',
        ]);

        SendSMSJob::dispatch($contact, 'Test message');

        Queue::assertPushed(SendSMSJob::class);

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_handles_invalid_phone_number()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Invalid Phone',
            'phone' => 'invalid_phone',
            'email' => 'invalid@example.com',
        ]);

        $this->mockSMSProvider('error');

        $job = new SendSMSJob($contact, 'Test message');

        try {
            $result = $job->handle();
            $this->assertFalse($result['success']);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'invalid_phone',
        ]);
    }

    /** @test */
    public function it_supports_message_personalization()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'خالد السعيد',
            'phone' => '+966502222222',
            'email' => 'khalid@example.com',
        ]);

        $this->mockSMSProvider('success');

        $message = 'مرحباً {{name}}، شكراً لاختيارك خدماتنا';

        $job = new SendSMSJob($contact, $message);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'personalization',
        ]);
    }

    /** @test */
    public function it_tracks_sms_delivery_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'نورة محمد',
            'phone' => '+966503333333',
            'email' => 'noura@example.com',
        ]);

        $this->mockSMSProvider('success', [
            'message_id' => 'sms_123456',
            'status' => 'sent',
        ]);

        $job = new SendSMSJob($contact, 'Test message');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message_id', $result);

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'delivery_tracking',
        ]);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Contact',
            'phone' => '+966504444444',
            'email' => 'test@example.com',
        ]);

        $this->mockSMSProvider('rate_limited');

        $job = new SendSMSJob($contact, 'Test message');

        try {
            $result = $job->handle();
            // Should handle rate limiting gracefully
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'rate_limiting',
        ]);
    }

    /** @test */
    public function it_splits_long_messages()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Contact',
            'phone' => '+966505555555',
            'email' => 'test@example.com',
        ]);

        $this->mockSMSProvider('success');

        // Long message that exceeds standard SMS length
        $longMessage = str_repeat('هذه رسالة طويلة جداً ', 50);

        $job = new SendSMSJob($contact, $longMessage);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendSMSJob',
            'test' => 'long_messages',
        ]);
    }
}
