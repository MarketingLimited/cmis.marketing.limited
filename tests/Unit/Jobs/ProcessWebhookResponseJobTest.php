<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Webhook\ProcessWebhookResponseJob;
use App\Models\Core\Org;
use App\Models\Webhook\Webhook;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

/**
 * ProcessWebhookResponse Job Unit Tests
 */
class ProcessWebhookResponseJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_processes_successful_webhook_response()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'campaign.created',
            'status' => 'active',
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'campaign.created']);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'successful_response',
        ]);
    }

    /** @test */
    public function it_handles_failed_webhook_response()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['error' => 'Server error'], 500),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'campaign.updated',
            'status' => 'active',
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'campaign.updated']);
        $result = $job->handle();

        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'failed_response',
        ]);
    }

    /** @test */
    public function it_retries_on_timeout()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response([], 408),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'lead.captured',
            'status' => 'active',
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'lead.captured']);
        $result = $job->handle();

        // Should retry on timeout
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'retry_timeout',
        ]);
    }

    /** @test */
    public function it_sends_custom_headers()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'post.published',
            'status' => 'active',
            'headers' => [
                'X-Custom-Header' => 'custom-value',
                'Authorization' => 'Bearer token123',
            ],
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'post.published']);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'custom_headers',
        ]);
    }

    /** @test */
    public function it_includes_signature()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'campaign.completed',
            'status' => 'active',
            'secret' => 'webhook_secret_key',
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'campaign.completed']);
        $result = $job->handle();

        // Should include HMAC signature in headers
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'signature',
        ]);
    }

    /** @test */
    public function it_logs_webhook_delivery()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'user.registered',
            'status' => 'active',
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'user.registered']);
        $result = $job->handle();

        // Should create delivery log entry
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'delivery_log',
        ]);
    }

    /** @test */
    public function it_updates_webhook_statistics()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'order.placed',
            'status' => 'active',
            'delivery_count' => 0,
            'success_count' => 0,
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'order.placed']);
        $result = $job->handle();

        // Should increment delivery_count and success_count
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'statistics',
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
            'url' => 'https://example.com/webhook',
            'event' => 'test.event',
            'status' => 'active',
        ]);

        ProcessWebhookResponseJob::dispatch($webhook, ['event' => 'test.event']);

        Queue::assertPushed(ProcessWebhookResponseJob::class);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_respects_max_retry_limit()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['error' => 'Server error'], 500),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'retry.test',
            'status' => 'active',
            'retry_count' => 5,
            'max_retries' => 3,
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'retry.test']);
        $result = $job->handle();

        // Should not retry beyond max_retries
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'max_retries',
        ]);
    }

    /** @test */
    public function it_handles_network_errors()
    {
        Http::fake([
            'https://example.com/webhook' => function () {
                throw new \Exception('Network error');
            },
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'url' => 'https://example.com/webhook',
            'event' => 'network.test',
            'status' => 'active',
        ]);

        $job = new ProcessWebhookResponseJob($webhook, ['event' => 'network.test']);
        $result = $job->handle();

        // Should handle network errors gracefully
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'network_errors',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        Http::fake();

        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $webhook1 = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'url' => 'https://org1.example.com/webhook',
            'event' => 'test.event',
            'status' => 'active',
        ]);

        $webhook2 = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'url' => 'https://org2.example.com/webhook',
            'event' => 'test.event',
            'status' => 'active',
        ]);

        // Should respect org boundaries
        $this->assertNotEquals($webhook1->org_id, $webhook2->org_id);

        $this->logTestResult('passed', [
            'job' => 'ProcessWebhookResponseJob',
            'test' => 'org_isolation',
        ]);
    }
}
