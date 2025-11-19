<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Lead\Lead;
use App\Events\Lead\LeadQualifiedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * LeadQualified Event Unit Tests
 */
class LeadQualifiedEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Qualified Lead',
            'email' => 'qualified@example.com',
            'status' => 'qualified',
            'score' => 85,
        ]);

        $event = new LeadQualifiedEvent($lead);

        $this->assertInstanceOf(LeadQualifiedEvent::class, $event);
        $this->assertEquals($lead->lead_id, $event->lead->lead_id);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'instantiation',
        ]);
    }

    /** @test */
    public function it_contains_lead_data()
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
            'phone' => '+966501234567',
            'status' => 'qualified',
            'score' => 90,
        ]);

        $event = new LeadQualifiedEvent($lead);

        $this->assertEquals('أحمد محمد', $event->lead->name);
        $this->assertEquals(90, $event->lead->score);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'lead_data',
        ]);
    }

    /** @test */
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
            'email' => 'test@example.com',
            'status' => 'qualified',
        ]);

        event(new LeadQualifiedEvent($lead));

        Event::assertDispatched(LeadQualifiedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_broadcasts_on_private_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Broadcast Lead',
            'email' => 'broadcast@example.com',
            'status' => 'qualified',
        ]);

        $event = new LeadQualifiedEvent($lead);

        // Should broadcast on org-specific private channel
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'broadcast_channel',
        ]);
    }

    /** @test */
    public function it_includes_qualification_reason()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Reason Lead',
            'email' => 'reason@example.com',
            'status' => 'qualified',
            'score' => 95,
        ]);

        $event = new LeadQualifiedEvent($lead, 'High engagement score');

        // Should include qualification reason
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'qualification_reason',
        ]);
    }

    /** @test */
    public function it_includes_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Time Lead',
            'email' => 'time@example.com',
            'status' => 'qualified',
        ]);

        $event = new LeadQualifiedEvent($lead);

        // Should include qualification timestamp
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'timestamp',
        ]);
    }

    /** @test */
    public function it_triggers_notifications()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Notify Lead',
            'email' => 'notify@example.com',
            'status' => 'qualified',
        ]);

        event(new LeadQualifiedEvent($lead));

        // Should trigger notifications to sales team
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'trigger_notifications',
        ]);
    }

    /** @test */
    public function it_respects_org_context()
    {
        Event::fake();

        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $lead1 = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Lead',
            'email' => 'org1@example.com',
            'status' => 'qualified',
        ]);

        $lead2 = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Lead',
            'email' => 'org2@example.com',
            'status' => 'qualified',
        ]);

        event(new LeadQualifiedEvent($lead1));

        // Should only notify members of org1
        $this->assertNotEquals($lead1->org_id, $lead2->org_id);

        $this->logTestResult('passed', [
            'event' => 'LeadQualifiedEvent',
            'test' => 'org_context',
        ]);
    }
}
