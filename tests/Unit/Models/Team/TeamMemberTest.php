<?php

namespace Tests\Unit\Models\Team;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Team\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Team Member Model Unit Tests
 */
class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_team_member()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Team Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('cmis.team_members', [
            'member_id' => $teamMember->member_id,
            'role' => 'editor',
        ]);
    }

    /** @test */
    public function it_belongs_to_org_and_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'viewer',
        ]);

        $this->assertEquals($org->org_id, $teamMember->org->org_id);
        $this->assertEquals($user->id, $teamMember->user->id);
    }

    /** @test */
    public function it_has_different_roles()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $roles = ['owner', 'admin', 'editor', 'viewer'];
        $members = [];

        foreach ($roles as $role) {
            $user = User::create([
                'user_id' => Str::uuid(),
                'name' => ucfirst($role),
                'email' => $role . '@example.com',
                'password' => bcrypt('password'),
            ]);

            $members[] = TeamMember::create([
                'member_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        $this->assertEquals('owner', $members[0]->role);
        $this->assertEquals('admin', $members[1]->role);
        $this->assertEquals('editor', $members[2]->role);
        $this->assertEquals('viewer', $members[3]->role);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeUser = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Active Member',
            'email' => 'active@example.com',
            'password' => bcrypt('password'),
        ]);

        $inactiveUser = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Inactive Member',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
        ]);

        $activeMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $activeUser->id,
            'role' => 'editor',
            'is_active' => true,
        ]);

        $inactiveMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $inactiveUser->id,
            'role' => 'viewer',
            'is_active' => false,
        ]);

        $this->assertTrue($activeMember->is_active);
        $this->assertFalse($inactiveMember->is_active);
    }

    /** @test */
    public function it_tracks_invitation_details()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Invited Member',
            'email' => 'invited@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'editor',
            'invited_at' => now(),
            'invitation_accepted_at' => now()->addHours(2),
        ]);

        $this->assertNotNull($teamMember->invited_at);
        $this->assertNotNull($teamMember->invitation_accepted_at);
    }

    /** @test */
    public function it_stores_permissions_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Custom Permissions Member',
            'email' => 'custom@example.com',
            'password' => bcrypt('password'),
        ]);

        $permissions = [
            'campaigns' => ['view', 'create', 'edit'],
            'content' => ['view', 'create'],
            'analytics' => ['view'],
        ];

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'custom',
            'custom_permissions' => $permissions,
        ]);

        $this->assertContains('create', $teamMember->custom_permissions['campaigns']);
        $this->assertContains('view', $teamMember->custom_permissions['analytics']);
    }

    /** @test */
    public function it_tracks_last_access_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'editor',
            'last_accessed_at' => now(),
        ]);

        $this->assertNotNull($teamMember->last_accessed_at);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'editor',
        ]);

        $this->assertTrue(Str::isUuid($teamMember->member_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'editor',
        ]);

        $this->assertNotNull($teamMember->created_at);
        $this->assertNotNull($teamMember->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Deletable Member',
            'email' => 'delete@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMember = TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'role' => 'editor',
        ]);

        $memberId = $teamMember->member_id;

        $teamMember->delete();

        $this->assertSoftDeleted('cmis.team_members', [
            'member_id' => $memberId,
        ]);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $user1 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'user_id' => $user1->id,
            'role' => 'editor',
        ]);

        TeamMember::create([
            'member_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'user_id' => $user2->id,
            'role' => 'viewer',
        ]);

        $org1Members = TeamMember::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Members);
        $this->assertEquals('editor', $org1Members->first()->role);
    }
}
