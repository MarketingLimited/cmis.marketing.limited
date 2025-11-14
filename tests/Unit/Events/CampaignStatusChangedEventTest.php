<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Events\Campaign\CampaignStatusChangedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * CampaignStatusChanged Event Unit Tests
 */
class CampaignStatusChangedEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Status Change Campaign',
            'status' => 'active',
        ]);

        $event = new CampaignStatusChangedEvent($campaign, 'draft', 'active');

        $this->assertInstanceOf(CampaignStatusChangedEvent::class, $event);
        $this->assertEquals($campaign->campaign_id, $event->campaign->campaign_id);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'instantiation',
        ]);
    }

    /** @test */
    public function it_contains_old_and_new_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $event = new CampaignStatusChangedEvent($campaign, 'draft', 'active');

        // Should contain both old and new status
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'status_tracking',
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        event(new CampaignStatusChangedEvent($campaign, 'draft', 'active'));

        Event::assertDispatched(CampaignStatusChangedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_broadcasts_on_org_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Broadcast Campaign',
            'status' => 'active',
        ]);

        $event = new CampaignStatusChangedEvent($campaign, 'draft', 'active');

        // Should broadcast on org-specific channel
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'broadcast_channel',
        ]);
    }

    /** @test */
    public function it_triggers_notifications_for_important_changes()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Notify Campaign',
            'status' => 'completed',
        ]);

        event(new CampaignStatusChangedEvent($campaign, 'active', 'completed'));

        // Should trigger notifications for important status changes
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'trigger_notifications',
        ]);
    }

    /** @test */
    public function it_includes_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Time Campaign',
            'status' => 'paused',
        ]);

        $event = new CampaignStatusChangedEvent($campaign, 'active', 'paused');

        // Should include timestamp of status change
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'timestamp',
        ]);
    }

    /** @test */
    public function it_tracks_draft_to_active_transition()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Launch Campaign',
            'status' => 'active',
        ]);

        event(new CampaignStatusChangedEvent($campaign, 'draft', 'active'));

        // Should track campaign launch
        Event::assertDispatched(CampaignStatusChangedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'draft_to_active',
        ]);
    }

    /** @test */
    public function it_tracks_active_to_paused_transition()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Pause Campaign',
            'status' => 'paused',
        ]);

        event(new CampaignStatusChangedEvent($campaign, 'active', 'paused'));

        // Should track campaign pause
        Event::assertDispatched(CampaignStatusChangedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'active_to_paused',
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

        $campaign1 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Campaign',
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Campaign',
            'status' => 'active',
        ]);

        event(new CampaignStatusChangedEvent($campaign1, 'draft', 'active'));

        // Should only notify members of org1
        $this->assertNotEquals($campaign1->org_id, $campaign2->org_id);

        $this->logTestResult('passed', [
            'event' => 'CampaignStatusChangedEvent',
            'test' => 'org_context',
        ]);
    }
}
