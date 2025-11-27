<?php

namespace Tests\Feature\FeatureManagement;

use App\Models\FeatureManagement\FeatureFlag;
use App\Models\FeatureManagement\FeatureFlagVariant;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

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

        // Set session org context
        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_feature_flags()
    {
        // Create flags
        FeatureFlag::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.index'));

        $response->assertOk();
        $response->assertViewIs('feature-management.flags.index');
        $response->assertViewHas('flags');
    }

    /** @test */
    public function it_can_show_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.show', $flag->flag_id));

        $response->assertOk();
        $response->assertViewIs('feature-management.flags.show');
        $response->assertViewHas('flag');
    }

    /** @test */
    public function it_can_create_a_feature_flag()
    {
        $data = [
            'key' => 'test_feature',
            'name' => 'Test Feature',
            'description' => 'A test feature flag',
            'type' => FeatureFlag::TYPE_BOOLEAN,
            'is_enabled' => true,
            'rollout_percentage' => 50,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.store'), $data);

        $this->assertDatabaseHas('cmis_features.feature_flags', [
            'org_id' => $this->org->org_id,
            'key' => 'test_feature',
            'name' => 'Test Feature',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('feature-flags.update', $flag->flag_id), $data);

        $this->assertDatabaseHas('cmis_features.feature_flags', [
            'flag_id' => $flag->flag_id,
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('feature-flags.destroy', $flag->flag_id));

        $this->assertSoftDeleted('cmis_features.feature_flags', [
            'flag_id' => $flag->flag_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_evaluate_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'type' => FeatureFlag::TYPE_BOOLEAN,
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('feature-flags.evaluate', $flag->flag_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'flag_key',
                'enabled',
                'type',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_variant_for_multivariate_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'type' => FeatureFlag::TYPE_MULTIVARIATE,
            'is_enabled' => true,
        ]);

        // Create variants
        FeatureFlagVariant::factory()->create([
            'flag_id' => $flag->flag_id,
            'org_id' => $this->org->org_id,
            'key' => 'control',
            'weight' => 50,
            'is_active' => true,
        ]);

        FeatureFlagVariant::factory()->create([
            'flag_id' => $flag->flag_id,
            'org_id' => $this->org->org_id,
            'key' => 'treatment',
            'weight' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.getVariant', $flag->flag_id), [
                'identifier' => 'test-user-123',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'flag_key',
                'variant',
            ],
        ]);
    }

    /** @test */
    public function it_can_enable_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'is_enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.enable', $flag->flag_id));

        $this->assertDatabaseHas('cmis_features.feature_flags', [
            'flag_id' => $flag->flag_id,
            'is_enabled' => true,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_disable_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.disable', $flag->flag_id));

        $this->assertDatabaseHas('cmis_features.feature_flags', [
            'flag_id' => $flag->flag_id,
            'is_enabled' => false,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_archive_a_feature_flag()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.archive', $flag->flag_id));

        $this->assertDatabaseHas('cmis_features.feature_flags', [
            'flag_id' => $flag->flag_id,
            'archived_at' => now(),
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_rollout_percentage()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'rollout_percentage' => 25,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.updateRollout', $flag->flag_id), [
                'percentage' => 75,
            ]);

        $this->assertDatabaseHas('cmis_features.feature_flags', [
            'flag_id' => $flag->flag_id,
            'rollout_percentage' => 75,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_add_users_to_whitelist()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $userIds = [
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.addToWhitelist', $flag->flag_id), [
                'user_ids' => $userIds,
            ]);

        $flag->refresh();
        $this->assertEquals($userIds, $flag->whitelist_user_ids);

        $response->assertOk();
    }

    /** @test */
    public function it_can_add_users_to_blacklist()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $userIds = [
            '33333333-3333-3333-3333-333333333333',
            '44444444-4444-4444-4444-444444444444',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.addToBlacklist', $flag->flag_id), [
                'user_ids' => $userIds,
            ]);

        $flag->refresh();
        $this->assertEquals($userIds, $flag->blacklist_user_ids);

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_analytics()
    {
        FeatureFlag::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.analytics'));

        $response->assertOk();
        $response->assertViewIs('feature-management.flags.analytics');
        $response->assertViewHas('analytics');
    }

    /** @test */
    public function it_can_get_flag_stats()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'evaluation_count' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('feature-flags.stats', $flag->flag_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_evaluations',
                'is_active',
                'rollout_percentage',
                'whitelist_count',
                'blacklist_count',
                'override_count',
                'variant_count',
            ],
        ]);
    }

    /** @test */
    public function it_can_bulk_enable_flags()
    {
        $flags = FeatureFlag::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'is_enabled' => false,
        ]);

        $flagIds = $flags->pluck('flag_id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.bulkEnable'), [
                'flag_ids' => $flagIds,
            ]);

        foreach ($flagIds as $flagId) {
            $this->assertDatabaseHas('cmis_features.feature_flags', [
                'flag_id' => $flagId,
                'is_enabled' => true,
            ]);
        }

        $response->assertOk();
    }

    /** @test */
    public function it_can_bulk_disable_flags()
    {
        $flags = FeatureFlag::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'is_enabled' => true,
        ]);

        $flagIds = $flags->pluck('flag_id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.bulkDisable'), [
                'flag_ids' => $flagIds,
            ]);

        foreach ($flagIds as $flagId) {
            $this->assertDatabaseHas('cmis_features.feature_flags', [
                'flag_id' => $flagId,
                'is_enabled' => false,
            ]);
        }

        $response->assertOk();
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        // Create another org
        $otherOrg = Org::factory()->create();

        // Create flag in other org
        $otherFlag = FeatureFlag::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's flag
        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.show', $otherFlag->flag_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key', 'name', 'type']);
    }

    /** @test */
    public function it_validates_rollout_percentage_range()
    {
        $flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        // Test invalid percentage (> 100)
        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.updateRollout', $flag->flag_id), [
                'percentage' => 150,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['percentage']);

        // Test invalid percentage (< 0)
        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.updateRollout', $flag->flag_id), [
                'percentage' => -10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['percentage']);
    }

    /** @test */
    public function it_filters_flags_by_type()
    {
        FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'type' => FeatureFlag::TYPE_BOOLEAN,
        ]);

        FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'type' => FeatureFlag::TYPE_MULTIVARIATE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.index', ['type' => FeatureFlag::TYPE_BOOLEAN]));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_active_flags_only()
    {
        FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'is_enabled' => true,
        ]);

        FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
            'is_enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.index', ['active_only' => true]));

        $response->assertOk();
    }
}
