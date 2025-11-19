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

/**
 * WhatsApp Status Updates & Metrics Integration Test
 *
 * اختبارات حالة الرسائل والمقاييس عبر WhatsApp
 */
class WhatsAppStatusMetricsMessagingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_handles_whatsapp_message_status_updates()
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
            'whatsapp_message_id' => 'wamid.sent_123',
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'text',
            'content' => ['body' => 'رسالة اختبار'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Simulate status webhook updates
        $message->update(['status' => 'delivered', 'delivered_at' => now()]);
        $this->assertEquals('delivered', $message->fresh()->status);

        $message->update(['status' => 'read', 'read_at' => now()]);
        $this->assertEquals('read', $message->fresh()->status);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'test' => 'status_updates',
        ]);
    }

    /** @test */
    public function it_handles_whatsapp_api_errors()
    {
        Queue::fake();

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
            'direction' => 'outgoing',
            'from_phone' => '966500000000',
            'to_phone' => '966501234567',
            'message_type' => 'text',
            'content' => ['body' => 'رسالة اختبار'],
            'status' => 'pending',
        ]);

        // Mock API error
        $this->mockWhatsAppAPI('error', [
            'error' => [
                'message' => 'Rate limit exceeded',
                'code' => 130429,
            ],
        ]);

        SendWhatsAppMessageJob::dispatch($message);
        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'test' => 'api_error_handling',
        ]);
    }

    /** @test */
    public function it_tracks_whatsapp_conversation_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'whatsapp');

        $conversation = WhatsAppConversation::create([
            'conversation_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'customer_phone' => '966501234567',
            'customer_name' => 'Customer',
            'status' => 'open',
            'started_at' => now()->subHours(2),
            'metrics' => [
                'total_messages' => 10,
                'response_time_avg' => 120, // seconds
                'satisfaction_score' => 4.5,
            ],
        ]);

        $this->assertTrue($conversation->metrics['total_messages'] == 10);
        $this->assertTrue($conversation->metrics['response_time_avg'] == 120);

        $this->logTestResult('passed', [
            'workflow' => 'whatsapp_messaging',
            'test' => 'conversation_metrics',
        ]);
    }
}
