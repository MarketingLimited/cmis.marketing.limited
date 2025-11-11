<?php

namespace Tests\Feature\Orgs;

use Tests\TestCase;
use App\Models\Core\{User, Org, UserOrg, Role};
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrgManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->org = Org::factory()->create();

        // Create owner role
        $ownerRole = Role::factory()->create([
            'role_code' => 'owner',
            'role_name' => 'Owner',
        ]);

        // Associate user with org
        UserOrg::factory()->create([
            'user_id' => $this->user->user_id,
            'org_id' => $this->org->org_id,
            'role_id' => $ownerRole->role_id,
            'is_active' => true,
        ]);
    }

    public function test_user_can_list_their_organizations()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user/orgs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'orgs' => [
                    '*' => ['org_id', 'org_name', 'role']
                ]
            ]);
    }

    public function test_user_can_create_new_organization()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orgs', [
                'org_name' => 'New Test Org',
                'org_domain' => 'newtest.com',
                'industry' => 'Technology',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'org' => ['org_id', 'org_name'],
                'message',
            ]);

        $this->assertDatabaseHas('cmis.orgs', [
            'org_name' => 'New Test Org',
            'org_domain' => 'newtest.com',
        ]);
    }

    public function test_user_can_view_organization_details()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->org->org_id}");

        $response->assertStatus(200)
            ->assertJson([
                'org_id' => $this->org->org_id,
                'org_name' => $this->org->org_name,
            ]);
    }

    public function test_owner_can_update_organization()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/orgs/{$this->org->org_id}", [
                'org_name' => 'Updated Org Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cmis.orgs', [
            'org_id' => $this->org->org_id,
            'org_name' => 'Updated Org Name',
        ]);
    }

    public function test_owner_can_soft_delete_organization()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orgs/{$this->org->org_id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Organization deleted successfully',
            ]);

        $this->assertSoftDeleted('cmis.orgs', [
            'org_id' => $this->org->org_id,
        ]);
    }

    public function test_user_cannot_access_org_without_membership()
    {
        $anotherUser = User::factory()->create();
        $anotherOrg = Org::factory()->create();

        $response = $this->actingAs($anotherUser, 'sanctum')
            ->getJson("/api/orgs/{$anotherOrg->org_id}");

        $response->assertStatus(403);
    }

    public function test_user_can_view_org_statistics()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->org->org_id}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_campaigns',
                    'active_campaigns',
                    'total_assets',
                    'total_channels',
                ]
            ]);
    }

    public function test_non_owner_cannot_delete_organization()
    {
        $memberRole = Role::factory()->create([
            'role_code' => 'member',
            'role_name' => 'Member',
        ]);

        $member = User::factory()->create();
        UserOrg::factory()->create([
            'user_id' => $member->user_id,
            'org_id' => $this->org->org_id,
            'role_id' => $memberRole->role_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/orgs/{$this->org->org_id}");

        $response->assertStatus(403);
    }
}
