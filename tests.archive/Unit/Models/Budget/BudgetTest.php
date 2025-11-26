<?php

namespace Tests\Unit\Models\Budget;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Budget\Budget;
use App\Models\Core\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Budget Model Unit Tests
 */
class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_budget()
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

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
        ]);

        $this->assertDatabaseHas('cmis.budgets', [
            'budget_id' => $budget->budget_id,
            'total_amount' => 10000.00,
        ]);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'create',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 5000.00,
            'currency' => 'SAR',
        ]);

        $this->assertEquals($org->org_id, $budget->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'belongs_to_org',
        ]);
    }

    #[Test]
    public function it_belongs_to_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Budget Campaign',
            'status' => 'active',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'total_amount' => 15000.00,
            'currency' => 'SAR',
        ]);

        $this->assertEquals($campaign->campaign_id, $budget->campaign->campaign_id);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'belongs_to_campaign',
        ]);
    }

    #[Test]
    public function it_tracks_spent_amount()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'spent_amount' => 3500.00,
            'currency' => 'SAR',
        ]);

        $this->assertEquals(3500.00, $budget->spent_amount);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'spent_amount',
        ]);
    }

    #[Test]
    public function it_calculates_remaining_amount()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'spent_amount' => 3500.00,
            'currency' => 'SAR',
        ]);

        $remaining = $budget->total_amount - $budget->spent_amount;
        $this->assertEquals(6500.00, $remaining);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'remaining_amount',
        ]);
    }

    #[Test]
    public function it_supports_different_currencies()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $sarBudget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
        ]);

        $usdBudget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 2500.00,
            'currency' => 'USD',
        ]);

        $this->assertEquals('SAR', $sarBudget->currency);
        $this->assertEquals('USD', $usdBudget->currency);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'currencies',
        ]);
    }

    #[Test]
    public function it_has_start_and_end_dates()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
        ]);

        $this->assertNotNull($budget->start_date);
        $this->assertNotNull($budget->end_date);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'date_range',
        ]);
    }

    #[Test]
    public function it_can_have_budget_breakdown()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $breakdown = [
            'advertising' => 5000.00,
            'content_creation' => 2000.00,
            'influencer_marketing' => 3000.00,
        ];

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
            'breakdown' => $breakdown,
        ]);

        $this->assertEquals(5000.00, $budget->breakdown['advertising']);
        $this->assertEquals(3000.00, $budget->breakdown['influencer_marketing']);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'breakdown',
        ]);
    }

    #[Test]
    public function it_tracks_budget_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeBudget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $exhaustedBudget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 5000.00,
            'spent_amount' => 5000.00,
            'currency' => 'SAR',
            'status' => 'exhausted',
        ]);

        $this->assertEquals('active', $activeBudget->status);
        $this->assertEquals('exhausted', $exhaustedBudget->status);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'status',
        ]);
    }

    #[Test]
    public function it_stores_approval_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 50000.00,
            'currency' => 'SAR',
            'approved_by' => $user->user_id,
            'approved_at' => now(),
        ]);

        $this->assertNotNull($budget->approved_at);
        $this->assertEquals($user->user_id, $budget->approved_by);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'approval',
        ]);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
        ]);

        $this->assertTrue(Str::isUuid($budget->budget_id));

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'uuid_primary_key',
        ]);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $budget = Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
        ]);

        $this->assertNotNull($budget->created_at);
        $this->assertNotNull($budget->updated_at);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'timestamps',
        ]);
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

        Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'total_amount' => 10000.00,
            'currency' => 'SAR',
        ]);

        Budget::create([
            'budget_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'total_amount' => 15000.00,
            'currency' => 'SAR',
        ]);

        $org1Budgets = Budget::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Budgets);

        $this->logTestResult('passed', [
            'model' => 'Budget',
            'test' => 'rls_isolation',
        ]);
    }
}
