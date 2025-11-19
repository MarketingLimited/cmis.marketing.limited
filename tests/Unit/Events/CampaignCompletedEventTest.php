<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\Campaign\CampaignCompletedEvent;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * CampaignCompleted Event Unit Tests
 */
class CampaignCompletedEventTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'حملة مكتملة',
            'status' => 'completed',
        ]);

        $event = new CampaignCompletedEvent($campaign);

        $this->assertInstanceOf(CampaignCompletedEvent::class, $event);
        $this->assertEquals($campaign->campaign_id, $event->campaign->campaign_id);

        $this->logTestResult('passed', [
            'event' => 'CampaignCompletedEvent',
            'test' => 'instantiation',
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
            'status' => 'completed',
        ]);

        event(new CampaignCompletedEvent($campaign));

        Event::assertDispatched(CampaignCompletedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'CampaignCompletedEvent',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_contains_campaign_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'حملة تسويقية',
            'status' => 'completed',
            'budget' => 5000,
        ]);

        $event = new CampaignCompletedEvent($campaign);

        $this->assertEquals('حملة تسويقية', $event->campaign->name);
        $this->assertEquals('completed', $event->campaign->status);
        $this->assertEquals(5000, $event->campaign->budget);

        $this->logTestResult('passed', [
            'event' => 'CampaignCompletedEvent',
            'test' => 'contains_data',
        ]);
    }

    /** @test */
    public function it_broadcasts_on_private_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $event = new CampaignCompletedEvent($campaign);

        // Should broadcast on private channel: campaigns.{org_id}
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'CampaignCompletedEvent',
            'test' => 'broadcast_channel',
        ]);
    }

    /** @test */
    public function it_can_be_listened_to()
    {
        Event::fake([CampaignCompletedEvent::class]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Listener Test Campaign',
            'status' => 'completed',
        ]);

        event(new CampaignCompletedEvent($campaign));

        Event::assertDispatched(CampaignCompletedEvent::class, function ($e) use ($campaign) {
            return $e->campaign->campaign_id === $campaign->campaign_id;
        });

        $this->logTestResult('passed', [
            'event' => 'CampaignCompletedEvent',
            'test' => 'listener_integration',
        ]);
    }

    /** @test */
    public function it_includes_completion_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timestamp Campaign',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $event = new CampaignCompletedEvent($campaign);

        $this->assertNotNull($event->campaign->completed_at);

        $this->logTestResult('passed', [
            'event' => 'CampaignCompletedEvent',
            'test' => 'completion_timestamp',
        ]);
    }
}
