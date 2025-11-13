<?php

namespace Tests\Integration\Team;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Core\UserOrg;
use App\Models\Security\Permission;
use App\Models\Security\UserPermission;
use Illuminate\Support\Str;

/**
 * Complete User Onboarding & Team Collaboration Workflow
 */
class UserOnboardingWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_completes_new_user_onboarding_workflow()
    {
        $setup = $this->createUserWithOrg();
        $admin = $setup['user'];
        $org = $setup['org'];

        $this->actingAsUserInOrg($admin, $org);

        // Step 1: Admin invites new user
        $newUserEmail = 'newuser@example.com';

        // Create invitation (in real app, this would send email)
        $invitation = [
            'email' => $newUserEmail,
            'role' => 'editor',
            'invited_by' => $admin->user_id,
            'invited_at' => now(),
        ];

        // Step 2: New user registers
        $newUser = User::create([
            'user_id' => Str::uuid(),
            'name' => 'New User',
            'email' => $newUserEmail,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Step 3: Associate user with organization
        $role = \App\Models\Core\Role::firstOrCreate([
            'org_id' => $org->org_id,
            'role_code' => 'editor',
        ], [
            'role_id' => Str::uuid(),
            'role_name' => 'Editor',
            'is_system' => false,
        ]);

        UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $newUser->id,
            'org_id' => $org->org_id,
            'role_id' => $role->role_id,
            'is_active' => true,
            'joined_at' => now(),
            'invited_by' => $admin->user_id,
        ]);

        // Step 4: Grant basic permissions
        $permissions = ['campaigns.view', 'campaigns.create', 'creatives.view'];

        foreach ($permissions as $permissionCode) {
            $permission = Permission::firstOrCreate([
                'permission_code' => $permissionCode,
            ], [
                'permission_id' => Str::uuid(),
                'permission_name' => ucfirst(str_replace('.', ' ', $permissionCode)),
                'category' => 'campaigns',
            ]);

            UserPermission::create([
                'id' => Str::uuid(),
                'user_id' => $newUser->id,
                'permission_id' => $permission->permission_id,
                'org_id' => $org->org_id,
                'granted_at' => now(),
                'granted_by' => $admin->user_id,
            ]);
        }

        // Step 5: Verify user can access organization
        $this->assertTrue($newUser->belongsToOrg($org->org_id));

        // Step 6: Verify user has correct permissions
        foreach ($permissions as $permissionCode) {
            $hasPermission = $newUser->hasPermission($permissionCode, $org->org_id);
            $this->assertTrue($hasPermission, "User should have {$permissionCode} permission");
        }

        $this->logTestResult('passed', [
            'workflow' => 'user_onboarding',
            'steps_completed' => 6,
            'permissions_granted' => count($permissions),
        ]);
    }

    /** @test */
    public function it_handles_team_member_role_change()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        // Create team member with viewer role
        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Team Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
        ]);

        $viewerRole = \App\Models\Core\Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'role_code' => 'viewer',
            'role_name' => 'Viewer',
        ]);

        $userOrg = UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $member->id,
            'org_id' => $org->org_id,
            'role_id' => $viewerRole->role_id,
            'is_active' => true,
        ]);

        // Verify initial role
        $this->assertEquals('viewer', $userOrg->role->role_code);

        // Promote to editor
        $editorRole = \App\Models\Core\Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'role_code' => 'editor',
            'role_name' => 'Editor',
        ]);

        $userOrg->update(['role_id' => $editorRole->role_id]);

        // Verify role change
        $userOrg = $userOrg->fresh();
        $this->assertEquals('editor', $userOrg->role->role_code);

        $this->logTestResult('passed', [
            'workflow' => 'team_collaboration',
            'step' => 'role_change',
        ]);
    }

    /** @test */
    public function it_handles_team_member_deactivation()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $member = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Inactive Member',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
        ]);

        $userOrg = UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $member->id,
            'org_id' => $org->org_id,
            'is_active' => true,
        ]);

        // Deactivate member
        $userOrg->update(['is_active' => false]);

        // Verify deactivation
        $userOrg = $userOrg->fresh();
        $this->assertFalse($userOrg->is_active);

        $this->logTestResult('passed', [
            'workflow' => 'team_collaboration',
            'step' => 'member_deactivation',
        ]);
    }

    /** @test */
    public function it_handles_user_belongs_to_multiple_organizations()
    {
        // Create user
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Multi Org User',
            'email' => 'multiorg@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create three organizations
        $orgs = [];
        for ($i = 1; $i <= 3; $i++) {
            $org = \App\Models\Core\Org::create([
                'org_id' => Str::uuid(),
                'name' => "Organization {$i}",
            ]);

            UserOrg::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'org_id' => $org->org_id,
                'is_active' => true,
            ]);

            $orgs[] = $org;
        }

        // Verify user belongs to all organizations
        $user = $user->fresh();
        $this->assertEquals(3, $user->orgs->count());

        foreach ($orgs as $org) {
            $this->assertTrue($user->belongsToOrg($org->org_id));
        }

        $this->logTestResult('passed', [
            'workflow' => 'user_onboarding',
            'step' => 'multi_org_membership',
            'org_count' => 3,
        ]);
    }
}
