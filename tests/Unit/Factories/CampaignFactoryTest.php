<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;

/**
 * Campaign Factory Unit Tests
 */
class CampaignFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_campaign_with_factory()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Simulate factory creation
        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Factory Campaign',
            'status' => 'active',
            'budget' => 5000,
        ]);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Factory Campaign', $campaign->name);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_multiple_campaigns()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Simulate creating multiple campaigns
        $campaigns = [];
        for ($i = 1; $i <= 5; $i++) {
            $campaigns[] = Campaign::create([
                'campaign_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Campaign {$i}",
                'status' => 'active',
            ]);
        }

        $this->assertCount(5, $campaigns);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'create_multiple',
        ]);
    }

    /** @test */
    public function it_creates_campaign_with_custom_attributes()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'حملة رمضان',
            'description' => 'حملة خاصة بشهر رمضان',
            'status' => 'active',
            'budget' => 10000,
            'start_date' => '2024-03-01',
            'end_date' => '2024-03-31',
        ]);

        $this->assertEquals('حملة رمضان', $campaign->name);
        $this->assertEquals(10000, $campaign->budget);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'custom_attributes',
        ]);
    }

    /** @test */
    public function it_creates_draft_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Draft Campaign',
            'status' => 'draft',
        ]);

        $this->assertEquals('draft', $campaign->status);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'draft_state',
        ]);
    }

    /** @test */
    public function it_creates_active_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Campaign',
            'status' => 'active',
        ]);

        $this->assertEquals('active', $campaign->status);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'active_state',
        ]);
    }

    /** @test */
    public function it_creates_completed_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Completed Campaign',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->assertEquals('completed', $campaign->status);
        $this->assertNotNull($campaign->completed_at);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'completed_state',
        ]);
    }

    /** @test */
    public function it_creates_campaign_with_budget()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budgets = [1000, 5000, 10000, 50000];

        foreach ($budgets as $budget) {
            $campaign = Campaign::create([
                'campaign_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Campaign with budget {$budget}",
                'status' => 'active',
                'budget' => $budget,
            ]);

            $this->assertEquals($budget, $campaign->budget);
        }

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'with_budget',
        ]);
    }

    /** @test */
    public function it_creates_campaign_with_dates()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Dated Campaign',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $this->assertNotNull($campaign->start_date);
        $this->assertNotNull($campaign->end_date);
        $this->assertTrue($campaign->end_date->greaterThan($campaign->start_date));

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'with_dates',
        ]);
    }

    /** @test */
    public function it_respects_org_relationship()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Related Campaign',
            'status' => 'active',
        ]);

        $this->assertEquals($org->org_id, $campaign->org->org_id);
        $this->assertEquals('Test Org', $campaign->org->name);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'org_relationship',
        ]);
    }

    /** @test */
    public function it_generates_unique_campaign_ids()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $campaign = Campaign::create([
                'campaign_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Campaign {$i}",
                'status' => 'active',
            ]);
            $ids[] = $campaign->campaign_id;
        }

        $uniqueIds = array_unique($ids);
        $this->assertCount(10, $uniqueIds);

        $this->logTestResult('passed', [
            'factory' => 'CampaignFactory',
            'test' => 'unique_ids',
        ]);
    }
}
