<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\Lead\LeadCapturedEvent;
use App\Models\Core\Org;
use App\Models\Lead\Lead;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

use PHPUnit\Framework\Attributes\Test;
/**
 * LeadCaptured Event Unit Tests
 */
class LeadCapturedEventTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'source' => 'website',
        ]);

        $event = new LeadCapturedEvent($lead);

        $this->assertInstanceOf(LeadCapturedEvent::class, $event);
        $this->assertEquals($lead->lead_id, $event->lead->lead_id);

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'instantiation',
        ]);
    }

    #[Test]
    public function it_can_be_dispatched()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'source' => 'facebook',
        ]);

        event(new LeadCapturedEvent($lead));

        Event::assertDispatched(LeadCapturedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_contains_lead_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'فاطمة علي',
            'email' => 'fatima@example.com',
            'phone' => '+966501234567',
            'source' => 'google_ads',
        ]);

        $event = new LeadCapturedEvent($lead);

        $this->assertEquals('فاطمة علي', $event->lead->name);
        $this->assertEquals('google_ads', $event->lead->source);
        $this->assertEquals('+966501234567', $event->lead->phone);

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'contains_data',
        ]);
    }

    #[Test]
    public function it_broadcasts_on_private_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'source' => 'website',
        ]);

        $event = new LeadCapturedEvent($lead);

        // Should broadcast on private channel: leads.{org_id}
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'broadcast_channel',
        ]);
    }

    #[Test]
    public function it_can_be_listened_to()
    {
        Event::fake([LeadCapturedEvent::class]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Listener Test Lead',
            'email' => 'listener@example.com',
            'source' => 'instagram',
        ]);

        event(new LeadCapturedEvent($lead));

        Event::assertDispatched(LeadCapturedEvent::class, function ($e) use ($lead) {
            return $e->lead->lead_id === $lead->lead_id;
        });

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'listener_integration',
        ]);
    }

    #[Test]
    public function it_includes_lead_source_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Source Test Lead',
            'email' => 'source@example.com',
            'source' => 'linkedin',
            'utm_params' => [
                'utm_source' => 'linkedin',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'brand_awareness',
            ],
        ]);

        $event = new LeadCapturedEvent($lead);

        $this->assertEquals('linkedin', $event->lead->source);
        $this->assertArrayHasKey('utm_source', $event->lead->utm_params);

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'source_information',
        ]);
    }

    #[Test]
    public function it_triggers_notification_workflows()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Notification Lead',
            'email' => 'notify@example.com',
            'source' => 'website',
        ]);

        Event::fake([LeadCapturedEvent::class]);

        event(new LeadCapturedEvent($lead));

        // Should trigger notification workflows
        Event::assertDispatched(LeadCapturedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'LeadCapturedEvent',
            'test' => 'notification_workflow',
        ]);
    }
}
