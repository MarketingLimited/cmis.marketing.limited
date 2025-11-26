<?php

namespace Tests\Unit\Models\Campaign;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Campaign\CampaignBudget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Campaign Budget Model Unit Tests
 */
class CampaignBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_campaign_budget()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign',
            'status' => 'active',
        ]);

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'allocated_budget' => 0.00,
            'spent_budget' => 0.00,
            'currency' => 'SAR',
        ]);

        $this->assertDatabaseHas('cmis.campaign_budgets', [
            'budget_id' => $budget->budget_id,
            'total_budget' => 10000.00,
        ]);
    }

    #[Test]
    public function it_belongs_to_campaign_and_org()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 5000.00,
            'currency' => 'SAR',
        ]);

        $this->assertEquals($campaign->campaign_id, $budget->campaign->campaign_id);
        $this->assertEquals($org->org_id, $budget->org->org_id);
    }

    #[Test]
    public function it_can_allocate_budget()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'allocated_budget' => 0.00,
            'currency' => 'SAR',
        ]);

        $budget->update(['allocated_budget' => 3000.00]);

        $this->assertEquals(3000.00, $budget->fresh()->allocated_budget);
        $this->assertEquals(7000.00, $budget->remaining_budget);
    }

    #[Test]
    public function it_can_track_spent_budget()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'spent_budget' => 0.00,
            'currency' => 'SAR',
        ]);

        $budget->increment('spent_budget', 2500.00);

        $this->assertEquals(2500.00, $budget->fresh()->spent_budget);
    }

    #[Test]
    public function it_calculates_remaining_budget()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'spent_budget' => 3500.00,
            'currency' => 'SAR',
        ]);

        $remaining = $budget->total_budget - $budget->spent_budget;

        $this->assertEquals(6500.00, $remaining);
    }

    #[Test]
    public function it_calculates_budget_utilization_percentage()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'spent_budget' => 7500.00,
            'currency' => 'SAR',
        ]);

        // Utilization = (spent / total) * 100 = (7500 / 10000) * 100 = 75%
        $utilization = ($budget->spent_budget / $budget->total_budget) * 100;

        $this->assertEquals(75.0, $utilization);
    }

    #[Test]
    public function it_stores_budget_allocation_by_channel()
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

        $channelAllocation = [
            'facebook' => 3000.00,
            'instagram' => 2500.00,
            'google_ads' => 4000.00,
            'twitter' => 500.00,
        ];

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'channel_allocation' => $channelAllocation,
            'currency' => 'SAR',
        ]);

        $this->assertEquals(3000.00, $budget->channel_allocation['facebook']);
        $this->assertEquals(4000.00, $budget->channel_allocation['google_ads']);
    }

    #[Test]
    public function it_tracks_daily_budget_cap()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 30000.00,
            'daily_budget_cap' => 1000.00,
            'currency' => 'SAR',
        ]);

        $this->assertEquals(1000.00, $budget->daily_budget_cap);
    }

    #[Test]
    public function it_supports_multiple_currencies()
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

        $sarBudget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 10000.00,
            'currency' => 'SAR',
        ]);

        $usdBudget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 2500.00,
            'currency' => 'USD',
        ]);

        $this->assertEquals('SAR', $sarBudget->currency);
        $this->assertEquals('USD', $usdBudget->currency);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 5000.00,
            'currency' => 'SAR',
        ]);

        $this->assertTrue(Str::isUuid($budget->budget_id));
    }

    #[Test]
    public function it_has_timestamps()
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

        $budget = CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'total_budget' => 5000.00,
            'currency' => 'SAR',
        ]);

        $this->assertNotNull($budget->created_at);
        $this->assertNotNull($budget->updated_at);
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

        $campaign1 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Campaign 1',
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Campaign 2',
            'status' => 'active',
        ]);

        CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign1->campaign_id,
            'org_id' => $org1->org_id,
            'total_budget' => 5000.00,
            'currency' => 'SAR',
        ]);

        CampaignBudget::create([
            'budget_id' => Str::uuid(),
            'campaign_id' => $campaign2->campaign_id,
            'org_id' => $org2->org_id,
            'total_budget' => 8000.00,
            'currency' => 'SAR',
        ]);

        $org1Budgets = CampaignBudget::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Budgets);
        $this->assertEquals(5000.00, $org1Budgets->first()->total_budget);
    }
}
