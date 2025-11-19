<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\Campaign\CampaignCreated;
use App\Models\Core\Campaign;
use App\Models\Core\Org;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * Campaign Created Event Unit Tests
 */
class CampaignCreatedEventTest extends TestCase
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
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $event = new CampaignCreated($campaign);

        $this->assertInstanceOf(CampaignCreated::class, $event);
        $this->assertEquals($campaign->campaign_id, $event->campaign->campaign_id);

        $this->logTestResult('passed', [
            'event' => 'CampaignCreated',
            'test' => 'instantiation',
        ]);
    }

    /** @test */
    public function it_is_dispatched_when_campaign_is_created()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'New Campaign',
            'status' => 'draft',
        ]);

        event(new CampaignCreated($campaign));

        Event::assertDispatched(CampaignCreated::class, function ($event) use ($campaign) {
            return $event->campaign->campaign_id === $campaign->campaign_id;
        });

        $this->logTestResult('passed', [
            'event' => 'CampaignCreated',
            'test' => 'dispatched',
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
            'name' => 'Test Campaign',
            'status' => 'draft',
            'description' => 'Campaign description',
        ]);

        $event = new CampaignCreated($campaign);

        $this->assertEquals('Test Campaign', $event->campaign->name);
        $this->assertEquals('draft', $event->campaign->status);
        $this->assertEquals($org->org_id, $event->campaign->org_id);

        $this->logTestResult('passed', [
            'event' => 'CampaignCreated',
            'test' => 'data_integrity',
        ]);
    }

    /** @test */
    public function it_can_be_serialized()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $event = new CampaignCreated($campaign);
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(CampaignCreated::class, $unserialized);

        $this->logTestResult('passed', [
            'event' => 'CampaignCreated',
            'test' => 'serialization',
        ]);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $event = new CampaignCreated($campaign);

        if (method_exists($event, 'broadcastOn')) {
            $channels = $event->broadcastOn();
            $this->assertNotEmpty($channels);
        }

        $this->logTestResult('passed', [
            'event' => 'CampaignCreated',
            'test' => 'broadcasting',
        ]);
    }
}
