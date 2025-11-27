<?php

namespace Tests\Feature\FeatureManagement;

use App\Models\FeatureManagement\FeatureFlag;
use App\Models\FeatureManagement\FeatureFlagOverride;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagOverrideControllerTest extends TestCase
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

        // Create a feature flag
        $this->flag = FeatureFlag::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        // Set session org context
        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_overrides_for_a_flag()
    {
        // Create overrides
        FeatureFlagOverride::factory()->count(3)->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.overrides.index', $this->flag->flag_id));

        $response->assertOk();
        $response->assertViewIs('feature-management.overrides.index');
        $response->assertViewHas('overrides');
    }

    /** @test */
    public function it_can_show_an_override()
    {
        $override = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.overrides.show', [$this->flag->flag_id, $override->override_id]));

        $response->assertOk();
        $response->assertViewIs('feature-management.overrides.show');
        $response->assertViewHas('override');
    }

    /** @test */
    public function it_can_create_an_override()
    {
        $data = [
            'override_type' => FeatureFlagOverride::TYPE_USER,
            'override_id_value' => '11111111-1111-1111-1111-111111111111',
            'value' => true,
            'reason' => 'Testing override functionality',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.overrides.store', $this->flag->flag_id), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'override_type' => FeatureFlagOverride::TYPE_USER,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_an_override()
    {
        $override = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'value' => false,
        ]);

        $data = [
            'value' => true,
            'reason' => 'Updated reason',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('feature-flags.overrides.update', [$this->flag->flag_id, $override->override_id]), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'override_id' => $override->override_id,
            'value' => true,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_an_override()
    {
        $override = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('feature-flags.overrides.destroy', [$this->flag->flag_id, $override->override_id]));

        $this->assertSoftDeleted('cmis_features.feature_flag_overrides', [
            'override_id' => $override->override_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_activate_an_override()
    {
        $override = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.overrides.activate', [$this->flag->flag_id, $override->override_id]));

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'override_id' => $override->override_id,
            'is_active' => true,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_deactivate_an_override()
    {
        $override = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('feature-flags.overrides.deactivate', [$this->flag->flag_id, $override->override_id]));

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'override_id' => $override->override_id,
            'is_active' => false,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_create_override_for_user()
    {
        $targetUserId = '22222222-2222-2222-2222-222222222222';

        $data = [
            'user_id' => $targetUserId,
            'value' => true,
            'reason' => 'Enable feature for this specific user',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.createForUser', $this->flag->flag_id), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'flag_id' => $this->flag->flag_id,
            'override_type' => FeatureFlagOverride::TYPE_USER,
            'override_id_value' => $targetUserId,
            'value' => true,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_create_override_for_organization()
    {
        $targetOrgId = '33333333-3333-3333-3333-333333333333';

        $data = [
            'target_org_id' => $targetOrgId,
            'value' => false,
            'reason' => 'Disable feature for this organization',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.createForOrganization', $this->flag->flag_id), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'flag_id' => $this->flag->flag_id,
            'override_type' => FeatureFlagOverride::TYPE_ORGANIZATION,
            'override_id_value' => $targetOrgId,
            'value' => false,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_create_override_for_role()
    {
        $roleId = '44444444-4444-4444-4444-444444444444';

        $data = [
            'role_id' => $roleId,
            'value' => true,
            'reason' => 'Enable feature for this role',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.createForRole', $this->flag->flag_id), $data);

        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'flag_id' => $this->flag->flag_id,
            'override_type' => FeatureFlagOverride::TYPE_ROLE,
            'override_id_value' => $roleId,
            'value' => true,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_get_user_overrides()
    {
        $targetUserId = '55555555-5555-5555-5555-555555555555';

        // Create multiple overrides for the user
        FeatureFlagOverride::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'override_type' => FeatureFlagOverride::TYPE_USER,
            'override_id_value' => $targetUserId,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('feature-flags.overrides.getUserOverrides', $targetUserId));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_can_bulk_activate_overrides()
    {
        $overrides = FeatureFlagOverride::factory()->count(3)->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $overrideIds = $overrides->pluck('override_id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.bulkActivate', $this->flag->flag_id), [
                'override_ids' => $overrideIds,
            ]);

        foreach ($overrideIds as $overrideId) {
            $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
                'override_id' => $overrideId,
                'is_active' => true,
            ]);
        }

        $response->assertOk();
    }

    /** @test */
    public function it_can_bulk_deactivate_overrides()
    {
        $overrides = FeatureFlagOverride::factory()->count(3)->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        $overrideIds = $overrides->pluck('override_id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.bulkDeactivate', $this->flag->flag_id), [
                'override_ids' => $overrideIds,
            ]);

        foreach ($overrideIds as $overrideId) {
            $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
                'override_id' => $overrideId,
                'is_active' => false,
            ]);
        }

        $response->assertOk();
    }

    /** @test */
    public function it_can_cleanup_expired_overrides()
    {
        // Create expired override
        $expiredOverride = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'expires_at' => now()->subDays(1),
        ]);

        // Create active override
        $activeOverride = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.cleanupExpired', $this->flag->flag_id));

        // Expired override should be deleted
        $this->assertSoftDeleted('cmis_features.feature_flag_overrides', [
            'override_id' => $expiredOverride->override_id,
        ]);

        // Active override should still exist
        $this->assertDatabaseHas('cmis_features.feature_flag_overrides', [
            'override_id' => $activeOverride->override_id,
            'deleted_at' => null,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_filters_overrides_by_type()
    {
        FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'override_type' => FeatureFlagOverride::TYPE_USER,
        ]);

        FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'override_type' => FeatureFlagOverride::TYPE_ORGANIZATION,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.overrides.index', [
                'flag_id' => $this->flag->flag_id,
                'type' => FeatureFlagOverride::TYPE_USER,
            ]));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_active_overrides_only()
    {
        FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.overrides.index', [
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

        // Create override in other org
        $otherOverride = FeatureFlagOverride::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's override
        $response = $this->actingAs($this->user)
            ->get(route('feature-flags.overrides.show', [$this->flag->flag_id, $otherOverride->override_id]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.store', $this->flag->flag_id), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['override_type', 'override_id_value', 'value']);
    }

    /** @test */
    public function it_validates_user_id_on_user_override_creation()
    {
        $data = [
            'user_id' => 'invalid-uuid',
            'value' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('feature-flags.overrides.createForUser', $this->flag->flag_id), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    /** @test */
    public function it_respects_override_expiration()
    {
        $override = FeatureFlagOverride::factory()->create([
            'flag_id' => $this->flag->flag_id,
            'org_id' => $this->org->org_id,
            'is_active' => true,
            'expires_at' => now()->subHours(1),
        ]);

        // Override should be considered expired and inactive
        $this->assertFalse($override->fresh()->isActive());
    }
}
