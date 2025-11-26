<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Core\{UserOrg, Role};

use PHPUnit\Framework\Attributes\Test;
/**
 * User Controller Authorization Tests
 * Tests authentication and authorization for all UserController endpoints
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_requires_authentication_for_listing_users()
    {
        $response = $this->getJson('/api/orgs/org-123/users');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'index',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_list_users_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'index',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_showing_user()
    {
        $response = $this->getJson('/api/orgs/org-123/users/user-123');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'show',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_show_user_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/{$user->user_id}");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'show',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_inviting_user()
    {
        $response = $this->postJson('/api/orgs/org-123/users/invite', [
            'email' => 'newuser@example.com',
            'role_id' => 'role-123',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'inviteUser',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_updating_role()
    {
        $response = $this->putJson('/api/orgs/org-123/users/user-123/role', [
            'role_id' => 'role-123',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'updateRole',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_deactivating_user()
    {
        $response = $this->postJson('/api/orgs/org-123/users/user-123/deactivate');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'deactivate',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_removing_user()
    {
        $response = $this->deleteJson('/api/orgs/org-123/users/user-123');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'remove',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_viewing_activities()
    {
        $response = $this->getJson('/api/orgs/org-123/users/user-123/activities');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'activities',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_view_activities_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/{$user->user_id}/activities");

        $response->assertStatus(200);
        $response->assertJsonStructure(['activities']);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'activities',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_viewing_permissions()
    {
        $response = $this->getJson('/api/orgs/org-123/users/user-123/permissions');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'permissions',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_view_permissions_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/users/{$user->user_id}/permissions");

        $response->assertStatus(200);
        $response->assertJsonStructure(['permissions', 'role']);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'permissions',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_user_list()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's users while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/users");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'index',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_user_details()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's user while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/users/{$setup1['user']->user_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'show',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_prevents_user_from_deactivating_themselves()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/orgs/{$org->org_id}/users/{$user->user_id}/deactivate");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Bad Request',
            'message' => 'You cannot deactivate yourself',
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'deactivate',
            'test' => 'self_deactivation_prevented',
        ]);
    }

    #[Test]
    public function it_prevents_user_from_removing_themselves()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/orgs/{$org->org_id}/users/{$user->user_id}");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Bad Request',
            'message' => 'You cannot remove yourself',
        ]);

        $this->logTestResult('passed', [
            'controller' => 'UserController',
            'method' => 'remove',
            'test' => 'self_removal_prevented',
        ]);
    }
}
