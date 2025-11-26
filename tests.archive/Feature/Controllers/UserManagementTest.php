<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

/**
 * User Management Tests (Phase 2 - Option 4)
 *
 * Tests comprehensive user management functionality:
 * - User listing with filters
 * - User invitations
 * - Role management
 * - User activation/deactivation
 * - User removal
 * - Activity logging
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_list_users_in_organization()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create additional users in the same org
        for ($i = 1; $i <= 5; $i++) {
            $otherUser = $this->createTestUser("user{$i}@example.com");
            $this->addUserToOrg($otherUser->user_id, $org->org_id);
        }

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'users' => [
                '*' => [
                    'user_id',
                    'email',
                    'name',
                    'role_name',
                    'is_active',
                    'joined_at',
                ]
            ],
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);

        // Should include the creator and 5 additional users
        $this->assertGreaterThanOrEqual(6, $response->json('pagination.total'));

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'GET /api/orgs/{org_id}/users',
        ]);
    }

    #[Test]
    public function it_can_filter_users_by_search_query()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $testUser = $this->createTestUser('testuser@example.com');
        $this->addUserToOrg($testUser->user_id, $org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users?search=testuser");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'user_list_search_filter',
        ]);
    }

    #[Test]
    public function it_can_get_single_user_details()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/{$user->user_id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'user_id',
                'email',
                'name',
                'role_id',
                'role_name',
                'permissions',
                'is_active',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'GET /api/orgs/{org_id}/users/{user_id}',
        ]);
    }

    #[Test]
    public function it_can_invite_new_user()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];
        $role = $this->createTestRole($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/orgs/{$org->org_id}/users/invite", [
            'email' => 'newuser@example.com',
            'role_id' => $role->role_id,
            'message' => 'Welcome to the team!',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'invitation' => [
                'invitation_id',
                'email',
                'expires_at',
            ],
        ]);

        // Verify invitation was created in database
        $this->assertDatabaseHas('cmis.user_invitations', [
            'email' => 'newuser@example.com',
            'org_id' => $org->org_id,
            'status' => 'pending',
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'POST /api/orgs/{org_id}/users/invite',
        ]);
    }

    #[Test]
    public function it_validates_invite_user_input()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        // Test without email
        $response = $this->postJson("/api/orgs/{$org->org_id}/users/invite", [
            'role_id' => Str::uuid()->toString(),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        // Test without role_id
        $response = $this->postJson("/api/orgs/{$org->org_id}/users/invite", [
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role_id');

        // Test with invalid email
        $response = $this->postJson("/api/orgs/{$org->org_id}/users/invite", [
            'email' => 'invalid-email',
            'role_id' => Str::uuid()->toString(),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'invite_user_validation',
        ]);
    }

    #[Test]
    public function it_prevents_duplicate_invitations()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];
        $role = $this->createTestRole($org->org_id);

        // Create initial invitation
        DB::table('cmis.user_invitations')->insert([
            'invitation_id' => Str::uuid()->toString(),
            'org_id' => $org->org_id,
            'email' => 'duplicate@example.com',
            'role_id' => $role->role_id,
            'invited_by' => $user->user_id,
            'invitation_token' => hash('sha256', Str::random(64)),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum');

        // Try to send another invitation to same email
        $response = $this->postJson("/api/orgs/{$org->org_id}/users/invite", [
            'email' => 'duplicate@example.com',
            'role_id' => $role->role_id,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'prevent_duplicate_invitations',
        ]);
    }

    #[Test]
    public function it_can_update_user_role()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $targetUser = $this->createTestUser('roletest@example.com');
        $this->addUserToOrg($targetUser->user_id, $org->org_id);

        $newRole = $this->createTestRole($org->org_id, 'Editor');

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/orgs/{$org->org_id}/users/{$targetUser->user_id}/role", [
            'role_id' => $newRole->role_id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify role was updated
        $this->assertDatabaseHas('cmis.user_orgs', [
            'user_id' => $targetUser->user_id,
            'org_id' => $org->org_id,
            'role_id' => $newRole->role_id,
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'PUT /api/orgs/{org_id}/users/{user_id}/role',
        ]);
    }

    #[Test]
    public function it_can_activate_and_deactivate_user()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $targetUser = $this->createTestUser('statustest@example.com');
        $this->addUserToOrg($targetUser->user_id, $org->org_id);

        $this->actingAs($user, 'sanctum');

        // Deactivate user
        $response = $this->putJson("/api/orgs/{$org->org_id}/users/{$targetUser->user_id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify user was deactivated
        $this->assertDatabaseHas('cmis.user_orgs', [
            'user_id' => $targetUser->user_id,
            'org_id' => $org->org_id,
            'is_active' => false,
        ]);

        // Reactivate user
        $response = $this->putJson("/api/orgs/{$org->org_id}/users/{$targetUser->user_id}/status", [
            'is_active' => true,
        ]);

        $response->assertStatus(200);

        // Verify user was reactivated
        $this->assertDatabaseHas('cmis.user_orgs', [
            'user_id' => $targetUser->user_id,
            'org_id' => $org->org_id,
            'is_active' => true,
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'PUT /api/orgs/{org_id}/users/{user_id}/status',
        ]);
    }

    #[Test]
    public function it_prevents_self_deactivation()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/orgs/{$org->org_id}/users/{$user->user_id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'prevent_self_deactivation',
        ]);
    }

    #[Test]
    public function it_can_remove_user_from_organization()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $targetUser = $this->createTestUser('removetest@example.com');
        $this->addUserToOrg($targetUser->user_id, $org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/orgs/{$org->org_id}/users/{$targetUser->user_id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify user was soft deleted (is_active = false and deleted_at set)
        $userOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $targetUser->user_id)
            ->where('org_id', $org->org_id)
            ->first();

        $this->assertFalse($userOrg->is_active);
        $this->assertNotNull($userOrg->deleted_at);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'DELETE /api/orgs/{org_id}/users/{user_id}',
        ]);
    }

    #[Test]
    public function it_prevents_self_removal()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/orgs/{$org->org_id}/users/{$user->user_id}");

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'prevent_self_removal',
        ]);
    }

    #[Test]
    public function it_can_get_user_activity_log()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create some activity logs
        for ($i = 0; $i < 5; $i++) {
            DB::table('cmis.audit_logs')->insert([
                'log_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'user_id' => $user->user_id,
                'action_type' => 'test_action_' . $i,
                'entity_type' => 'test',
                'metadata' => json_encode(['test' => 'data']),
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/{$user->user_id}/activity");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'activities' => [
                '*' => [
                    'log_id',
                    'action_type',
                    'entity_type',
                    'created_at',
                ]
            ],
            'total',
        ]);

        $this->assertGreaterThanOrEqual(5, $response->json('total'));

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'GET /api/orgs/{org_id}/users/{user_id}/activity',
        ]);
    }

    #[Test]
    public function it_can_get_pending_invitations()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];
        $role = $this->createTestRole($org->org_id);

        // Create pending invitations
        for ($i = 1; $i <= 3; $i++) {
            DB::table('cmis.user_invitations')->insert([
                'invitation_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'email' => "pending{$i}@example.com",
                'role_id' => $role->role_id,
                'invited_by' => $user->user_id,
                'invitation_token' => hash('sha256', Str::random(64)),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/invitations");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'invitations' => [
                '*' => [
                    'invitation_id',
                    'email',
                    'role_name',
                    'invited_by_name',
                    'created_at',
                    'expires_at',
                ]
            ],
            'total',
        ]);

        $this->assertEquals(3, $response->json('total'));

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'GET /api/orgs/{org_id}/users/invitations',
        ]);
    }

    #[Test]
    public function it_can_cancel_invitation()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];
        $role = $this->createTestRole($org->org_id);

        $invitationId = Str::uuid()->toString();

        DB::table('cmis.user_invitations')->insert([
            'invitation_id' => $invitationId,
            'org_id' => $org->org_id,
            'email' => 'cancel@example.com',
            'role_id' => $role->role_id,
            'invited_by' => $user->user_id,
            'invitation_token' => hash('sha256', Str::random(64)),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/orgs/{$org->org_id}/users/invitations/{$invitationId}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify invitation was cancelled
        $this->assertDatabaseHas('cmis.user_invitations', [
            'invitation_id' => $invitationId,
            'status' => 'cancelled',
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'endpoint' => 'DELETE /api/orgs/{org_id}/users/invitations/{invitation_id}',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_user_management()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $user1 = $setup1['user'];
        $org1 = $setup1['org'];
        $org2 = $setup2['org'];

        $this->actingAs($user1, 'sanctum');

        // Should not be able to list users from another org
        $response = $this->getJson("/api/orgs/{$org2->org_id}/users");
        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'user_management_org_isolation',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_user_management()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        // Test all endpoints without authentication
        $response = $this->getJson("/api/orgs/{$org->org_id}/users");
        $response->assertStatus(401);

        $response = $this->postJson("/api/orgs/{$org->org_id}/users/invite");
        $response->assertStatus(401);

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/invitations");
        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserManagementController',
            'test' => 'user_management_authentication_required',
        ]);
    }

    /**
     * Helper: Add user to organization
     */
    private function addUserToOrg(string $userId, string $orgId, ?string $roleId = null): void
    {
        if (!$roleId) {
            $role = $this->createTestRole($orgId);
            $roleId = $role->role_id;
        }

        DB::table('cmis.user_orgs')->insert([
            'user_id' => $userId,
            'org_id' => $orgId,
            'role_id' => $roleId,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Helper: Create test role
     */
    private function createTestRole(string $orgId, string $name = 'Test Role')
    {
        $roleId = Str::uuid()->toString();

        DB::table('cmis.roles')->insert([
            'role_id' => $roleId,
            'org_id' => $orgId,
            'role_name' => $name,
            'permissions' => json_encode(['test' => true]),
            'is_system_role' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object)[
            'role_id' => $roleId,
            'role_name' => $name,
        ];
    }
}
