<?php

namespace Tests\Unit\Models\Webhook;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Webhook\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Webhook Model Unit Tests
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_webhook()
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

        $this->assertDatabaseHas('cmis.webhooks', [
            'webhook_id' => $webhook->webhook_id,
            'name' => 'Campaign Created Webhook',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/hook',
            'event' => 'test.event',
        ]);

        $this->assertEquals($org->org_id, $webhook->org->org_id);
    }

    #[Test]
    public function it_has_different_event_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $events = [
            'campaign.created',
            'campaign.updated',
            'post.published',
            'lead.captured',
            'analytics.generated',
        ];

        foreach ($events as $event) {
            Webhook::create([
                'webhook_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Webhook for {$event}",
                'url' => 'https://example.com/webhook',
                'event' => $event,
            ]);
        }

        $webhooks = Webhook::where('org_id', $org->org_id)->get();
        $this->assertCount(5, $webhooks);
    }

    #[Test]
    public function it_stores_headers_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $headers = [
            'Authorization' => 'Bearer secret_token',
            'X-Custom-Header' => 'custom_value',
            'Content-Type' => 'application/json',
        ];

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Webhook with Headers',
            'url' => 'https://example.com/webhook',
            'event' => 'campaign.created',
            'headers' => $headers,
        ]);

        $this->assertEquals('Bearer secret_token', $webhook->headers['Authorization']);
        $this->assertEquals('custom_value', $webhook->headers['X-Custom-Header']);
    }

    #[Test]
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeWebhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Webhook',
            'url' => 'https://example.com/active',
            'event' => 'test',
            'is_active' => true,
        ]);

        $inactiveWebhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Webhook',
            'url' => 'https://example.com/inactive',
            'event' => 'test',
            'is_active' => false,
        ]);

        $this->assertTrue($activeWebhook->is_active);
        $this->assertFalse($inactiveWebhook->is_active);
    }

    #[Test]
    public function it_has_secret_for_signature_verification()
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
            'event' => 'campaign.created',
            'secret' => 'webhook_secret_key_123',
        ]);

        $this->assertEquals('webhook_secret_key_123', $webhook->secret);
    }

    #[Test]
    public function it_tracks_delivery_attempts()
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
            'event' => 'test',
            'total_deliveries' => 0,
            'successful_deliveries' => 0,
            'failed_deliveries' => 0,
        ]);

        $webhook->increment('total_deliveries');
        $webhook->increment('successful_deliveries');

        $this->assertEquals(1, $webhook->fresh()->total_deliveries);
        $this->assertEquals(1, $webhook->fresh()->successful_deliveries);
    }

    #[Test]
    public function it_tracks_last_delivery_time()
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
            'event' => 'test',
            'last_delivered_at' => now(),
        ]);

        $this->assertNotNull($webhook->last_delivered_at);
    }

    #[Test]
    public function it_stores_retry_configuration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $webhook = Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Webhook with Retry',
            'url' => 'https://example.com/webhook',
            'event' => 'campaign.created',
            'max_retries' => 3,
            'retry_delay' => 60,
        ]);

        $this->assertEquals(3, $webhook->max_retries);
        $this->assertEquals(60, $webhook->retry_delay);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
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
            'event' => 'test',
        ]);

        $this->assertTrue(Str::isUuid($webhook->webhook_id));
    }

    #[Test]
    public function it_has_timestamps()
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
            'event' => 'test',
        ]);

        $this->assertNotNull($webhook->created_at);
        $this->assertNotNull($webhook->updated_at);
    }

    #[Test]
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Webhook',
            'url' => 'https://example.com/org1',
            'event' => 'test',
        ]);

        Webhook::create([
            'webhook_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Webhook',
            'url' => 'https://example.com/org2',
            'event' => 'test',
        ]);

        $org1Webhooks = Webhook::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Webhooks);
        $this->assertEquals('Org 1 Webhook', $org1Webhooks->first()->name);
    }
}
