<?php

namespace Tests\Feature\ABTesting;

use App\Models\ABTesting\ABTest;
use App\Models\ABTesting\ABTestVariant;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ABTestVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected ABTest $test;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        session(['current_org_id' => $this->org->org_id]);

        $this->test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
        ]);
    }

    /** @test */
    public function it_can_list_variants()
    {
        ABTestVariant::factory()->count(3)->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.variants.index', $this->test->test_id));

        $response->assertOk();
        $response->assertViewIs('ab-testing.variants.index');
        $response->assertViewHas('variants');
    }

    /** @test */
    public function it_can_create_a_variant()
    {
        $data = [
            'variant_name' => 'Variant A',
            'traffic_split' => 50,
            'is_control' => false,
            'config' => ['color' => 'blue'],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('ab-testing.variants.store', $this->test->test_id), $data);

        $this->assertDatabaseHas('cmis_ab_testing.ab_test_variants', [
            'test_id' => $this->test->test_id,
            'variant_name' => 'Variant A',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_validates_traffic_split_does_not_exceed_100()
    {
        ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'traffic_split' => 70,
        ]);

        $data = [
            'variant_name' => 'Variant B',
            'traffic_split' => 50, // Total would be 120%
            'is_control' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.store', $this->test->test_id), $data);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_show_a_variant()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.variants.show', [$this->test->test_id, $variant->variant_id]));

        $response->assertOk();
        $response->assertViewIs('ab-testing.variants.show');
        $response->assertViewHas('variant');
    }

    /** @test */
    public function it_can_update_a_variant()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'variant_name' => 'Original Name',
            'traffic_split' => 50,
        ]);

        $data = [
            'variant_name' => 'Updated Name',
            'traffic_split' => 60,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('ab-testing.variants.update', [$this->test->test_id, $variant->variant_id]), $data);

        $this->assertDatabaseHas('cmis_ab_testing.ab_test_variants', [
            'variant_id' => $variant->variant_id,
            'variant_name' => 'Updated Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_non_control_variant()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'is_control' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('ab-testing.variants.destroy', [$this->test->test_id, $variant->variant_id]));

        $this->assertSoftDeleted('cmis_ab_testing.ab_test_variants', [
            'variant_id' => $variant->variant_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_cannot_delete_control_variant()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'is_control' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('ab-testing.variants.destroy', [$this->test->test_id, $variant->variant_id]));

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_record_impression()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'impressions' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.recordImpression', [$this->test->test_id, $variant->variant_id]));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_test_variants', [
            'variant_id' => $variant->variant_id,
            'impressions' => 101,
        ]);
    }

    /** @test */
    public function it_can_record_conversion()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'conversions' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.recordConversion', [$this->test->test_id, $variant->variant_id]), [
                'conversion_value' => 25.00,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_test_variants', [
            'variant_id' => $variant->variant_id,
            'conversions' => 11,
        ]);
    }

    /** @test */
    public function it_can_get_variant_statistics()
    {
        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'impressions' => 1000,
            'conversions' => 50,
            'total_revenue' => 500,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ab-testing.variants.statistics', [$this->test->test_id, $variant->variant_id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'variant_id',
                'impressions',
                'conversions',
                'conversion_rate',
                'total_revenue',
            ],
        ]);
    }

    /** @test */
    public function it_can_compare_variants()
    {
        $variant1 = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $variant2 = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.compare', $this->test->test_id), [
                'variant_ids' => [$variant1->variant_id, $variant2->variant_id],
            ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_set_variant_as_control()
    {
        $this->test->update(['status' => 'draft']);

        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'is_control' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.setAsControl', [$this->test->test_id, $variant->variant_id]));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_test_variants', [
            'variant_id' => $variant->variant_id,
            'is_control' => true,
        ]);
    }

    /** @test */
    public function it_cannot_change_control_for_non_draft_test()
    {
        $this->test->update(['status' => 'running']);

        $variant = ABTestVariant::factory()->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
            'is_control' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.setAsControl', [$this->test->test_id, $variant->variant_id]));

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_auto_balance_traffic()
    {
        ABTestVariant::factory()->count(4)->create([
            'test_id' => $this->test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.variants.autoBalanceTraffic', $this->test->test_id));

        $response->assertOk();

        $variants = ABTestVariant::where('test_id', $this->test->test_id)->get();
        $totalSplit = $variants->sum('traffic_split');

        $this->assertEquals(100, $totalSplit);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        $otherTest = ABTest::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $otherVariant = ABTestVariant::factory()->create([
            'test_id' => $otherTest->test_id,
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.variants.show', [$otherTest->test_id, $otherVariant->variant_id]));

        $response->assertNotFound();
    }
}
