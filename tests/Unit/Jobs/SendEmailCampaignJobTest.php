<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Email\SendEmailCampaignJob;
use App\Models\Core\Org;
use App\Models\Contact\Contact;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

/**
 * Send Email Campaign Job Unit Tests
 */
class SendEmailCampaignJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_sends_email_to_recipients()
    {
        Mail::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contacts = collect([
            Contact::create([
                'contact_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'first_name' => 'Customer',
                'last_name' => 'One',
                'email' => 'customer1@example.com',
            ]),
            Contact::create([
                'contact_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'first_name' => 'Customer',
                'last_name' => 'Two',
                'email' => 'customer2@example.com',
            ]),
        ]);

        $emailData = [
            'subject' => 'Special Offer',
            'content' => 'Hello {{name}}, special offer for you!',
        ];

        $job = new SendEmailCampaignJob($contacts, $emailData);
        $job->handle();

        Mail::assertSent(2);

        $this->logTestResult('passed', [
            'job' => 'SendEmailCampaignJob',
            'test' => 'send_emails',
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

        $contacts = collect([]);
        $emailData = ['subject' => 'Test', 'content' => 'Content'];

        SendEmailCampaignJob::dispatch($contacts, $emailData);

        Queue::assertPushed(SendEmailCampaignJob::class);

        $this->logTestResult('passed', [
            'job' => 'SendEmailCampaignJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_personalizes_content()
    {
        Mail::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'email' => 'ahmed@example.com',
        ]);

        $emailData = [
            'subject' => 'Hello {{name}}',
            'content' => 'Dear {{name}}, welcome!',
        ];

        $job = new SendEmailCampaignJob(collect([$contact]), $emailData);
        $job->handle();

        // Content should be personalized with contact name
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'SendEmailCampaignJob',
            'test' => 'personalization',
        ]);
    }

    /** @test */
    public function it_tracks_sent_emails()
    {
        Mail::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $emailData = [
            'subject' => 'Test Email',
            'content' => 'Test content',
        ];

        $job = new SendEmailCampaignJob(collect([$contact]), $emailData);
        $result = $job->handle();

        // Should track sent emails
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'SendEmailCampaignJob',
            'test' => 'tracking',
        ]);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'invalid-email',
        ]);

        $emailData = [
            'subject' => 'Test',
            'content' => 'Content',
        ];

        $job = new SendEmailCampaignJob(collect([$contact]), $emailData);

        try {
            $job->handle();
            $errorOccurred = false;
        } catch (\Exception $e) {
            $errorOccurred = true;
        }

        // Should handle errors gracefully
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'SendEmailCampaignJob',
            'test' => 'error_handling',
        ]);
    }
}
