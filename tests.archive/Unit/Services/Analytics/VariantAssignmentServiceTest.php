<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Analytics\VariantAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VariantAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VariantAssignmentService $service;
    protected Org $org;
    protected User $user;
    protected Experiment $experiment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new VariantAssignmentService();

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

        // Create experiment with variants
        $this->experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
            'traffic_allocation' => 'hash',
        ]);

        // Control variant - 50%
        ExperimentVariant::factory()->create([
            'experiment_id' => $this->experiment->experiment_id,
            'name' => 'Control',
            'is_control' => true,
            'traffic_percentage' => 50.00,
            'status' => 'active',
        ]);

        // Test variant - 50%
        ExperimentVariant::factory()->create([
            'experiment_id' => $this->experiment->experiment_id,
            'name' => 'Variant A',
            'is_control' => false,
            'traffic_percentage' => 50.00,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_assigns_variant_using_hash_algorithm()
    {
        $userId = 'test-user-123';

        $variant = $this->service->assignVariant($this->experiment, $userId, 'hash');

        $this->assertInstanceOf(ExperimentVariant::class, $variant);
        $this->assertEquals($this->experiment->experiment_id, $variant->experiment_id);
    }

    /** @test */
    public function it_assigns_same_variant_for_same_user_with_hash()
    {
        $userId = 'test-user-123';

        $variant1 = $this->service->assignVariant($this->experiment, $userId, 'hash');
        $variant2 = $this->service->assignVariant($this->experiment, $userId, 'hash');

        $this->assertEquals($variant1->variant_id, $variant2->variant_id);
    }

    /** @test */
    public function it_caches_variant_assignment()
    {
        $userId = 'test-user-456';

        $variant = $this->service->assignVariant($this->experiment, $userId, 'hash');

        // Check cache
        $cacheKey = "experiment:{$this->experiment->experiment_id}:user:{$userId}";
        $cachedVariantId = Cache::get($cacheKey);

        $this->assertEquals($variant->variant_id, $cachedVariantId);
    }

    /** @test */
    public function it_returns_cached_assignment_on_subsequent_calls()
    {
        $userId = 'test-user-789';

        // First assignment
        $variant1 = $this->service->assignVariant($this->experiment, $userId, 'hash');

        // Manually change cache to a different variant
        $otherVariant = $this->experiment->variants()
            ->where('variant_id', '!=', $variant1->variant_id)
            ->first();

        $cacheKey = "experiment:{$this->experiment->experiment_id}:user:{$userId}";
        Cache::put($cacheKey, $otherVariant->variant_id, 60);

        // Second assignment should return cached variant
        $variant2 = $this->service->assignVariant($this->experiment, $userId, 'hash');

        $this->assertEquals($otherVariant->variant_id, $variant2->variant_id);
        $this->assertNotEquals($variant1->variant_id, $variant2->variant_id);
    }

    /** @test */
    public function it_assigns_variant_using_random_algorithm()
    {
        $userId = 'test-user-random';

        $variant = $this->service->assignVariant($this->experiment, $userId, 'random');

        $this->assertInstanceOf(ExperimentVariant::class, $variant);
        $this->assertTrue(
            $variant->is_control || $variant->name === 'Variant A'
        );
    }

    /** @test */
    public function it_respects_traffic_percentage_distribution()
    {
        // Create experiment with unequal traffic split
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $control = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'name' => 'Control',
            'is_control' => true,
            'traffic_percentage' => 80.00,
            'status' => 'active',
        ]);

        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'name' => 'Variant',
            'is_control' => false,
            'traffic_percentage' => 20.00,
            'status' => 'active',
        ]);

        // Assign to many users
        $assignments = [];
        for ($i = 0; $i < 1000; $i++) {
            $userId = "user-{$i}";
            $assigned = $this->service->assignVariant($experiment, $userId, 'hash');
            $assignments[$assigned->variant_id] = ($assignments[$assigned->variant_id] ?? 0) + 1;
        }

        // Check distribution is roughly 80/20
        $controlPercentage = ($assignments[$control->variant_id] / 1000) * 100;
        $variantPercentage = ($assignments[$variant->variant_id] / 1000) * 100;

        // Allow 10% margin of error
        $this->assertGreaterThan(70, $controlPercentage);
        $this->assertLessThan(90, $controlPercentage);
        $this->assertGreaterThan(10, $variantPercentage);
        $this->assertLessThan(30, $variantPercentage);
    }

    /** @test */
    public function it_assigns_variant_using_adaptive_algorithm()
    {
        // Set up variants with performance data
        $control = $this->experiment->variants()->where('is_control', true)->first();
        $control->update([
            'impressions' => 1000,
            'conversions' => 100, // 10% conversion
        ]);

        $variant = $this->experiment->variants()->where('is_control', false)->first();
        $variant->update([
            'impressions' => 1000,
            'conversions' => 150, // 15% conversion (better)
        ]);

        $userId = 'test-user-adaptive';

        $assigned = $this->service->assignVariant($this->experiment, $userId, 'adaptive');

        $this->assertInstanceOf(ExperimentVariant::class, $assigned);
    }

    /** @test */
    public function it_throws_exception_when_no_active_variants()
    {
        // Deactivate all variants
        $this->experiment->variants()->update(['status' => 'paused']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active variants available');

        $this->service->assignVariant($this->experiment, 'test-user');
    }

    /** @test */
    public function it_clears_assignment_for_user()
    {
        $userId = 'test-user-clear';

        // Make assignment
        $variant = $this->service->assignVariant($this->experiment, $userId);

        // Verify cached
        $cacheKey = "experiment:{$this->experiment->experiment_id}:user:{$userId}";
        $this->assertNotNull(Cache::get($cacheKey));

        // Clear assignment
        $this->service->clearAssignment($this->experiment, $userId);

        // Verify cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_gets_assignment_statistics()
    {
        // Create some impressions
        $control = $this->experiment->variants()->where('is_control', true)->first();
        $control->update(['impressions' => 500]);

        $variant = $this->experiment->variants()->where('is_control', false)->first();
        $variant->update(['impressions' => 300]);

        $stats = $this->service->getAssignmentStats($this->experiment);

        $this->assertIsArray($stats);
        $this->assertCount(2, $stats);

        // Check control stats
        $controlStats = collect($stats)->firstWhere('is_control', true);
        $this->assertEquals(50.00, $controlStats['traffic_percentage']);
        $this->assertGreaterThan(0, $controlStats['actual_percentage']);
    }

    /** @test */
    public function it_calculates_actual_traffic_percentage()
    {
        // Set impressions
        $control = $this->experiment->variants()->where('is_control', true)->first();
        $control->update(['impressions' => 700]);

        $variant = $this->experiment->variants()->where('is_control', false)->first();
        $variant->update(['impressions' => 300]);

        $stats = $this->service->getAssignmentStats($this->experiment);

        $controlStats = collect($stats)->firstWhere('is_control', true);
        $variantStats = collect($stats)->firstWhere('is_control', false);

        // Control should have ~70% actual traffic
        $this->assertEqualsWithDelta(70, $controlStats['actual_percentage'], 0.1);

        // Variant should have ~30% actual traffic
        $this->assertEqualsWithDelta(30, $variantStats['actual_percentage'], 0.1);
    }

    /** @test */
    public function it_assigns_and_records_impression()
    {
        $userId = 'test-user-impression';

        $variant = $this->service->assignAndRecordImpression(
            $this->experiment,
            $userId,
            ['session_id' => 'session-123']
        );

        $this->assertInstanceOf(ExperimentVariant::class, $variant);

        // Verify impression was recorded
        $this->assertDatabaseHas('cmis.experiment_events', [
            'experiment_id' => $this->experiment->experiment_id,
            'variant_id' => $variant->variant_id,
            'event_type' => 'impression',
            'user_id' => $userId,
        ]);
    }

    /** @test */
    public function hash_assignment_distributes_users_evenly()
    {
        $assignments = [
            'control' => 0,
            'variant' => 0,
        ];

        // Assign 1000 different users
        for ($i = 0; $i < 1000; $i++) {
            $userId = "user-{$i}";
            $variant = $this->service->assignVariant($this->experiment, $userId, 'hash');

            if ($variant->is_control) {
                $assignments['control']++;
            } else {
                $assignments['variant']++;
            }
        }

        // Both should be roughly 50% (allow 10% margin)
        $controlPercentage = ($assignments['control'] / 1000) * 100;
        $variantPercentage = ($assignments['variant'] / 1000) * 100;

        $this->assertGreaterThan(40, $controlPercentage);
        $this->assertLessThan(60, $controlPercentage);
        $this->assertGreaterThan(40, $variantPercentage);
        $this->assertLessThan(60, $variantPercentage);
    }

    /** @test */
    public function it_uses_experiment_traffic_allocation_setting()
    {
        $this->experiment->update(['traffic_allocation' => 'random']);

        $userId = 'test-user-setting';

        // Should use random algorithm based on experiment setting
        $variant = $this->service->assignVariant($this->experiment, $userId);

        $this->assertInstanceOf(ExperimentVariant::class, $variant);
    }
}
