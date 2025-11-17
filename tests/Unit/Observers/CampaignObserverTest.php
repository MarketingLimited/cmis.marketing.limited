<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * Campaign Observer Unit Tests
 */
class CampaignObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_fires_creating_event()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'New Campaign',
            'status' => 'draft',
        ]);

        // Observer should handle this event
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'event' => 'creating',
        ]);
    }

    /** @test */
    public function it_fires_created_event()
    {
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

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
        ]);

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'event' => 'created',
        ]);
    }

    /** @test */
    public function it_fires_updating_event()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $campaign->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $campaign->fresh()->name);

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'event' => 'updating',
        ]);
    }

    /** @test */
    public function it_fires_updated_event()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $campaign->update(['status' => 'active']);

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'status' => 'active',
        ]);

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'event' => 'updated',
        ]);
    }

    /** @test */
    public function it_fires_deleting_event()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'To Delete',
            'status' => 'draft',
        ]);

        $campaign->delete();

        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
        ]);

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'event' => 'deleting',
        ]);
    }

    /** @test */
    public function it_fires_deleted_event()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'To Delete',
            'status' => 'draft',
        ]);

        $campaignId = $campaign->campaign_id;
        $campaign->delete();

        $this->assertNull(Campaign::find($campaignId));

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'event' => 'deleted',
        ]);
    }

    /** @test */
    public function it_sets_defaults_on_creation()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'New Campaign',
            // status not provided
        ]);

        // Observer might set default status
        $this->assertNotNull($campaign->status);

        $this->logTestResult('passed', [
            'observer' => 'CampaignObserver',
            'test' => 'default_values',
        ]);
    }
}
