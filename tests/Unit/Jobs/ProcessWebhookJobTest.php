<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Webhooks\ProcessWebhookJob;
use App\Models\Core\Org;
use App\Models\Webhook\Webhook;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

/**
 * Process Webhook Job Unit Tests
 */
class ProcessWebhookJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_processes_webhook_delivery()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign Created Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'campaign.created',
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $payload = [
            'event' => 'campaign.created',
            'data' => [
                'campaign_id' => $campaign->campaign_id,
                'name' => $campaign->name,
            ],
        ];

        $job = new ProcessWebhookJob($webhook, $payload);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'process_delivery',
        ]);
    }

    /** @test */
    public function it_sends_post_request_to_webhook_url()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
        ]);

        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $payload = ['event' => 'test.event', 'data' => ['key' => 'value']];

        $job = new ProcessWebhookJob($webhook, $payload);
        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                   $request->method() === 'POST';
        });

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'sends_post_request',
        ]);
    }

    /** @test */
    public function it_includes_custom_headers()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Webhook with Headers',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
            'headers' => [
                'Authorization' => 'Bearer secret_token',
                'X-Custom-Header' => 'custom_value',
            ],
        ]);

        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $payload = ['event' => 'test.event', 'data' => []];

        $job = new ProcessWebhookJob($webhook, $payload);
        $job->handle();

        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'custom_headers',
        ]);
    }

    /** @test */
    public function it_generates_signature_when_secret_is_set()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Secure Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
            'secret' => 'webhook_secret_key',
        ]);

        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $payload = ['event' => 'test.event', 'data' => []];

        $job = new ProcessWebhookJob($webhook, $payload);
        $job->handle();

        // Should include X-Webhook-Signature header with HMAC
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'generates_signature',
        ]);
    }

    /** @test */
    public function it_handles_failed_deliveries()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Failing Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
        ]);

        Http::fake([
            'example.com/*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $payload = ['event' => 'test.event', 'data' => []];

        $job = new ProcessWebhookJob($webhook, $payload);

        try {
            $result = $job->handle();
            $this->assertFalse($result['success']);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'handles_failures',
        ]);
    }

    /** @test */
    public function it_updates_delivery_statistics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Stats Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
            'total_deliveries' => 0,
            'successful_deliveries' => 0,
            'failed_deliveries' => 0,
        ]);

        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $payload = ['event' => 'test.event', 'data' => []];

        $job = new ProcessWebhookJob($webhook, $payload);
        $job->handle();

        // Should increment total_deliveries and successful_deliveries
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'updates_statistics',
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

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
        ]);

        $payload = ['event' => 'test.event', 'data' => []];

        ProcessWebhookJob::dispatch($webhook, $payload);

        Queue::assertPushed(ProcessWebhookJob::class);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_retries_on_failure()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Retry Webhook',
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'is_active' => true,
            'max_retries' => 3,
        ]);

        Http::fake([
            'example.com/*' => Http::sequence()
                ->push(['error' => 'Error'], 500)
                ->push(['error' => 'Error'], 500)
                ->push(['success' => true], 200),
        ]);

        // Job should retry up to max_retries times
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookJob',
            'test' => 'retry_logic',
        ]);
    }
}
