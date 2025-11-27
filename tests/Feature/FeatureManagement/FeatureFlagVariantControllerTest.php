<?php

namespace Tests\Feature\FeatureManagement;

use App\Models\FeatureManagement\FeatureFlag;
use App\Models\FeatureManagement\FeatureFlagVariant;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected FeatureFlag $flag;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization and user
        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        // Associate user with organization
        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create a multivariate flag
        $this->flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'type' => FeatureFlag::TYPE_MULTIVARIATE,
        ]);

        // Set session org context
        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_variants_for_a_flag()
    {
        // Create variants
        FeatureFlagVariant::factory()->count(3)->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.variants.index', $this->flag->flag_id));

        $response->assertOk();
        $response->assertViewIs('feature-management.variants.index');
        $response->assertViewHas('variants');
    }

    /** @test */
    public function it_can_show_a_variant()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.variants.show', [$this->flag->flag_id, $variant->variant_id]));

        $response->assertOk();
        $response->assertViewIs('feature-management.variants.show');
        $response->assertViewHas('variant');
    }

    /** @test */
    public function it_can_create_a_variant()
    {
        $data = [
            'key' => 'treatment_a',
            'name' => 'Treatment A',
            'description' => 'First treatment variant',
            'value' => true,
            'weight' => 50,
            'is_control' => false,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.variants.store', $this->flag->flag_id), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_variants', [
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'key' => 'treatment_a',
            'name' => 'Treatment A',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_a_variant()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'weight' => 50,
        ]);

        $data = [
            'weight' => 75,
            'name' => 'Updated Variant Name',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('feature-flags.variants.update', [$this->flag->flag_id, $variant->variant_id]), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_variants', [
            'variant_id' => $variant->variant_id,
            'weight' => 75,
            'name' => 'Updated Variant Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_variant()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('feature-flags.variants.destroy', [$this->flag->flag_id, $variant->variant_id]));

        $this->assertSoftDeleted('cmis_features.feature_flag_variants', [
            'variant_id' => $variant->variant_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_activate_a_variant()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.variants.activate', [$this->flag->flag_id, $variant->variant_id]));

        $this->assertDatabaseHas('cmis_features.feature_flag_variants', [
            'variant_id' => $variant->variant_id,
            'is_active' => true,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_deactivate_a_variant()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.variants.deactivate', [$this->flag->flag_id, $variant->variant_id]));

        $this->assertDatabaseHas('cmis_features.feature_flag_variants', [
            'variant_id' => $variant->variant_id,
            'is_active' => false,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_record_conversion()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'conversions' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.variants.recordConversion', [$this->flag->flag_id, $variant->variant_id]));

        $this->assertDatabaseHas('cmis_features.feature_flag_variants', [
            'variant_id' => $variant->variant_id,
            'conversions' => 11,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_variant_stats()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'exposures' => 100,
            'conversions' => 25,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('feature-flags.variants.stats', [$this->flag->flag_id, $variant->variant_id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'key',
                'exposures',
                'conversions',
                'conversion_rate',
                'is_control',
                'is_active',
                'weight',
                'performance_score',
            ],
        ]);
    }

    /** @test */
    public function it_can_compare_variants()
    {
        // Create multiple variants
        FeatureFlagVariant::factory()->count(3)->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
            'exposures' => 100,
            'conversions' => 25,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('feature-flags.variants.compare', $this->flag->flag_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'flag_key',
                'variants',
                'best_performer',
            ],
        ]);
    }

    /** @test */
    public function it_filters_active_variants_only()
    {
        FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.variants.index', [
                'flag_id' => $this->flag->flag_id,
                'active_only' => true,
            ]));

        $response->assertOk();
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        // Create another org
        $otherOrg = Org::factory()->create();

        // Create variant in other org
        $otherVariant = FeatureFlagVariant::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's variant
        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.variants.show', [$this->flag->flag_id, $otherVariant->variant_id]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.variants.store', $this->flag->flag_id), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key', 'name', 'weight']);
    }

    /** @test */
    public function it_validates_weight_range()
    {
        $data = [
            'key' => 'test_variant',
            'name' => 'Test Variant',
            'weight' => 150, // Invalid weight > 100
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.variants.store', $this->flag->flag_id), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['weight']);
    }

    /** @test */
    public function it_calculates_conversion_rate_correctly()
    {
        $variant = FeatureFlagVariant::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'exposures' => 100,
            'conversions' => 25,
        ]);

        $conversionRate = $variant->getConversionRate();

        $this->assertEquals(0.25, $conversionRate);
    }
}
