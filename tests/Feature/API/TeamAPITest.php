<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\User;
use App\Models\Core\UserPermission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * Team Management API Feature Tests
 */
class TeamAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_invite_team_member()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/team/invite', [
            'email' => 'newmember@example.com',
            'name' => 'أحمد محمد',
            'role' => 'editor',
            'permissions' => ['campaigns.view', 'campaigns.edit', 'content.create'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.email', 'newmember@example.com');

        $this->assertDatabaseHas('cmis.users', [
            'email' => 'newmember@example.com',
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/team/invite',
        ]);
    }

    /** @test */
    public function it_can_list_team_members()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        // Create additional team members
        User::create([
            'user_id' => Str::uuid(),
            'name' => 'Team Member 1',
            'email' => 'member1@example.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'user_id' => Str::uuid(),
            'name' => 'Team Member 2',
            'email' => 'member2@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/team/members');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['user_id', 'name', 'email', 'role'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/team/members',
        ]);
    }

    /** @test */
    public function it_can_get_team_member_details()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'testmember@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/team/members/{$member->user_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', 'testmember@example.com');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/team/members/{id}',
        ]);
    }

    /** @test */
    public function it_can_update_team_member_role()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'testmember@example.com',
            'password' => Hash::make('password'),
            'metadata' => ['role' => 'viewer'],
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/team/members/{$member->user_id}", [
            'role' => 'editor',
        ]);

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'endpoint' => 'PUT /api/team/members/{id}',
        ]);
    }

    /** @test */
    public function it_can_update_team_member_permissions()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'testmember@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/team/members/{$member->user_id}/permissions", [
            'permissions' => [
                'campaigns.view',
                'campaigns.edit',
                'content.create',
                'content.publish',
            ],
        ]);

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'endpoint' => 'PUT /api/team/members/{id}/permissions',
        ]);
    }

    /** @test */
    public function it_can_remove_team_member()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'testmember@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/team/members/{$member->user_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.users', [
            'user_id' => $member->user_id,
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'DELETE /api/team/members/{id}',
        ]);
    }

    /** @test */
    public function it_can_get_team_activity_log()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/team/activity');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['action', 'user', 'timestamp'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/team/activity',
        ]);
    }

    /** @test */
    public function it_can_get_team_statistics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/team/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['total_members', 'active_members', 'roles_distribution'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/team/statistics',
        ]);
    }

    /** @test */
    public function it_validates_email_on_invite()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/team/invite', [
            'email' => 'invalid-email',
            'name' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/team/invite',
            'validation' => 'email_format',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_email_invites()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        User::create([
            'user_id' => Str::uuid(),
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/team/invite', [
            'email' => 'existing@example.com',
            'name' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/team/invite',
            'validation' => 'unique_email',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $member1 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Org 1 Member',
            'email' => 'org1member@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/team/members/{$member1->user_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/team/members/{id}',
            'test' => 'org_isolation',
        ]);
    }

    /** @test */
    public function it_can_resend_invitation()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Pending Member',
            'email' => 'pending@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => null,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/team/members/{$member->user_id}/resend-invite");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/team/members/{id}/resend-invite',
        ]);
    }
}
