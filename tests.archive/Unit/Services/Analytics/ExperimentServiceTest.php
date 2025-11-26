<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use App\Models\Analytics\ExperimentEvent;
use App\Models\Analytics\ExperimentResult;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Analytics\ExperimentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExperimentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExperimentService $service;
    protected Org $org;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExperimentService();

        // Create test organization and user
        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->org_id
        ]);

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $this->user->user_id,
            $this->org->org_id
        ]);
    }

    /** @test */
    public function it_creates_experiment_with_control_variant()
    {
        $data = [
            'name' => 'Test Campaign Experiment',
            'description' => 'Testing different ad creatives',
            'experiment_type' => 'campaign',
            'metric' => 'conversion_rate',
            'hypothesis' => 'New creative will increase conversions by 10%',
            'duration_days' => 14,
            'sample_size_per_variant' => 1000,
            'confidence_level' => 95.00,
            'minimum_detectable_effect' => 5.00,
            'control_config' => ['creative_id' => 'original'],
        ];

        $experiment = $this->service->createExperiment($this->org->org_id, $this->user->user_id, $data);

        $this->assertNotNull($experiment);
        $this->assertEquals('Test Campaign Experiment', $experiment->name);
        $this->assertEquals('draft', $experiment->status);
        $this->assertEquals($this->org->org_id, $experiment->org_id);

        // Check control variant was created
        $controlVariant = $experiment->variants()->where('is_control', true)->first();
        $this->assertNotNull($controlVariant);
        $this->assertEquals('Control', $controlVariant->name);
        $this->assertTrue($controlVariant->is_control);
        $this->assertEquals(50.00, $controlVariant->traffic_percentage);
    }

    /** @test */
    public function it_adds_variant_to_draft_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
        ]);

        $variantData = [
            'name' => 'Variant A',
            'description' => 'New creative design',
            'traffic_percentage' => 50.00,
            'config' => ['creative_id' => 'new_design'],
        ];

        $variant = $this->service->addVariant($experiment, $variantData);

        $this->assertNotNull($variant);
        $this->assertEquals('Variant A', $variant->name);
        $this->assertFalse($variant->is_control);
        $this->assertEquals(50.00, $variant->traffic_percentage);
    }

    /** @test */
    public function it_throws_exception_when_adding_variant_to_running_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can only add variants to draft experiments');

        $this->service->addVariant($experiment, [
            'name' => 'Variant B',
            'config' => [],
        ]);
    }

    /** @test */
    public function it_records_experiment_event()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
        ]);

        $event = $this->service->recordEvent(
            $experiment->experiment_id,
            $variant->variant_id,
            'impression',
            ['user_id' => 'test-user-123', 'session_id' => 'session-456']
        );

        $this->assertInstanceOf(ExperimentEvent::class, $event);
        $this->assertEquals('impression', $event->event_type);
        $this->assertEquals('test-user-123', $event->user_id);

        // Check variant impressions incremented
        $variant->refresh();
        $this->assertEquals(1, $variant->impressions);
    }

    /** @test */
    public function it_updates_variant_metrics_on_event()
    {
        $variant = ExperimentVariant::factory()->create([
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
        ]);

        // Record impression
        $this->service->recordEvent(
            $variant->experiment_id,
            $variant->variant_id,
            'impression'
        );

        $variant->refresh();
        $this->assertEquals(1, $variant->impressions);

        // Record click
        $this->service->recordEvent(
            $variant->experiment_id,
            $variant->variant_id,
            'click'
        );

        $variant->refresh();
        $this->assertEquals(1, $variant->clicks);

        // Record conversion
        $this->service->recordEvent(
            $variant->experiment_id,
            $variant->variant_id,
            'conversion',
            ['value' => 100.00]
        );

        $variant->refresh();
        $this->assertEquals(1, $variant->conversions);
        $this->assertEquals(100.00, $variant->revenue);
    }

    /** @test */
    public function it_calculates_statistical_significance()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'confidence_level' => 95.00,
        ]);

        // Control variant: 100 conversions out of 1000 impressions (10%)
        $control = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
            'impressions' => 1000,
            'conversions' => 100,
        ]);

        // Test variant: 150 conversions out of 1000 impressions (15%)
        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => false,
            'impressions' => 1000,
            'conversions' => 150,
        ]);

        $results = $this->service->calculateStatisticalSignificance($experiment);

        $this->assertArrayHasKey($variant->variant_id, $results);
        $variantResult = $results[$variant->variant_id];

        $this->assertArrayHasKey('p_value', $variantResult);
        $this->assertArrayHasKey('z_score', $variantResult);
        $this->assertArrayHasKey('is_significant', $variantResult);
        $this->assertArrayHasKey('improvement', $variantResult);

        // 50% improvement should be significant
        $this->assertTrue($variantResult['is_significant']);
        $this->assertGreaterThan(0, $variantResult['improvement']);
    }

    /** @test */
    public function it_determines_winner_variant()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'minimum_detectable_effect' => 5.00,
        ]);

        // Control: 10% conversion rate
        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
            'impressions' => 1000,
            'conversions' => 100,
        ]);

        // Variant A: 11% conversion rate (not significant enough)
        $variantA = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'name' => 'Variant A',
            'is_control' => false,
            'impressions' => 1000,
            'conversions' => 110,
        ]);

        // Variant B: 15% conversion rate (significant improvement)
        $variantB = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'name' => 'Variant B',
            'is_control' => false,
            'impressions' => 1000,
            'conversions' => 150,
        ]);

        $winner = $this->service->determineWinner($experiment);

        // Variant B should win
        $this->assertNotNull($winner);
        $this->assertEquals($variantB->variant_id, $winner->variant_id);
    }

    /** @test */
    public function it_aggregates_daily_results()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
        ]);

        $today = now()->startOfDay();

        // Create events for today
        ExperimentEvent::factory()->count(100)->create([
            'experiment_id' => $experiment->experiment_id,
            'variant_id' => $variant->variant_id,
            'event_type' => 'impression',
            'occurred_at' => $today->addHours(2),
        ]);

        ExperimentEvent::factory()->count(10)->create([
            'experiment_id' => $experiment->experiment_id,
            'variant_id' => $variant->variant_id,
            'event_type' => 'click',
            'occurred_at' => $today->addHours(3),
        ]);

        ExperimentEvent::factory()->count(2)->create([
            'experiment_id' => $experiment->experiment_id,
            'variant_id' => $variant->variant_id,
            'event_type' => 'conversion',
            'value' => 50.00,
            'occurred_at' => $today->addHours(4),
        ]);

        $this->service->aggregateDailyResults($experiment);

        $result = ExperimentResult::where('experiment_id', $experiment->experiment_id)
            ->where('variant_id', $variant->variant_id)
            ->whereDate('date', $today)
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals(100, $result->impressions);
        $this->assertEquals(10, $result->clicks);
        $this->assertEquals(2, $result->conversions);
        $this->assertEquals(100.00, $result->revenue);
    }

    /** @test */
    public function it_gets_performance_summary()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
            'started_at' => now()->subDays(5),
            'duration_days' => 14,
        ]);

        $control = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
            'impressions' => 1000,
            'conversions' => 100,
        ]);

        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => false,
            'impressions' => 1000,
            'conversions' => 120,
        ]);

        $summary = $this->service->getPerformanceSummary($experiment);

        $this->assertArrayHasKey('experiment', $summary);
        $this->assertArrayHasKey('variants', $summary);
        $this->assertArrayHasKey('winner', $summary);

        $this->assertEquals($experiment->experiment_id, $summary['experiment']['id']);
        $this->assertCount(2, $summary['variants']);
    }

    /** @test */
    public function it_respects_multi_tenancy()
    {
        // Create experiment for org1
        $org1 = Org::factory()->create();
        $user1 = User::factory()->create(['org_id' => $org1->org_id]);

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user1->user_id,
            $org1->org_id
        ]);

        $experiment1 = $this->service->createExperiment($org1->org_id, $user1->user_id, [
            'name' => 'Org 1 Experiment',
            'experiment_type' => 'campaign',
            'metric' => 'conversion_rate',
        ]);

        // Create experiment for org2
        $org2 = Org::factory()->create();
        $user2 = User::factory()->create(['org_id' => $org2->org_id]);

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user2->user_id,
            $org2->org_id
        ]);

        $experiment2 = $this->service->createExperiment($org2->org_id, $user2->user_id, [
            'name' => 'Org 2 Experiment',
            'experiment_type' => 'campaign',
            'metric' => 'conversion_rate',
        ]);

        // Verify isolation: org1 can only see their experiment
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user1->user_id,
            $org1->org_id
        ]);

        $org1Experiments = Experiment::all();
        $this->assertCount(1, $org1Experiments);
        $this->assertEquals($experiment1->experiment_id, $org1Experiments->first()->experiment_id);

        // Verify org2 can only see their experiment
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user2->user_id,
            $org2->org_id
        ]);

        $org2Experiments = Experiment::all();
        $this->assertCount(1, $org2Experiments);
        $this->assertEquals($experiment2->experiment_id, $org2Experiments->first()->experiment_id);
    }
}
