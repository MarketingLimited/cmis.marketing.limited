<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Core\UserOrg;
use App\Models\Security\Permission;
use App\Models\Security\UserPermission;
use Illuminate\Support\Str;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_user_with_uuid()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($user->user_id);
        $this->assertIsString($user->user_id);
        $this->assertDatabaseHas('cmis.users', [
            'email' => 'john@example.com',
        ]);

        $this->logTestResult('passed', ['user_id' => $user->user_id]);
    }

    /** @test */
    public function it_has_many_organizations()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->orgs);
        $this->assertTrue($user->orgs->contains($org));
        $this->assertEquals(1, $user->orgs->count());

        $this->logTestResult('passed');
    }

    /** @test */
    public function it_can_check_organization_membership()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create another org that user doesn't belong to
        $otherOrg = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Other Org',
        ]);

        $this->assertTrue($user->belongsToOrg($org->org_id));
        $this->assertFalse($user->belongsToOrg($otherOrg->org_id));

        $this->logTestResult('passed');
    }

    /** @test */
    public function it_can_check_permissions()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create a permission
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'permission_code' => 'campaigns.view',
            'permission_name' => 'View Campaigns',
            'category' => 'campaigns',
        ]);

        // Grant permission to user
        UserPermission::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'permission_id' => $permission->permission_id,
            'org_id' => $org->org_id,
            'granted_at' => now(),
        ]);

        $hasPermission = $user->hasPermission('campaigns.view', $org->org_id);

        $this->assertTrue($hasPermission);

        $this->logTestResult('passed', [
            'permission_code' => 'campaigns.view',
            'has_permission' => $hasPermission,
        ]);
    }

    /** @test */
    public function it_belongs_to_multiple_organizations()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Multi Org User',
            'email' => 'multi@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create two organizations
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        // Associate user with both orgs
        UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'org_id' => $org1->org_id,
            'is_active' => true,
        ]);

        UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'org_id' => $org2->org_id,
            'is_active' => true,
        ]);

        $user = $user->fresh();

        $this->assertEquals(2, $user->orgs->count());
        $this->assertTrue($user->belongsToOrg($org1->org_id));
        $this->assertTrue($user->belongsToOrg($org2->org_id));

        $this->logTestResult('passed', [
            'org_count' => $user->orgs->count(),
        ]);
    }

    /** @test */
    public function it_uses_uuid_as_primary_identifier()
    {
        $userId = Str::uuid()->toString();

        $user = User::create([
            'user_id' => $userId,
            'name' => 'UUID User',
            'email' => 'uuid@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertEquals($userId, $user->user_id);
        $this->assertIsString($user->user_id);

        // Verify UUID format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $user->user_id
        );

        $this->logTestResult('passed');
    }

    /** @test */
    public function it_enforces_unique_email()
    {
        User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 1',
            'email' => 'duplicate@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 2',
            'email' => 'duplicate@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Deletable User',
            'email' => 'deletable@example.com',
            'password' => bcrypt('password'),
        ]);

        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('cmis.users', [
            'id' => $userId,
        ]);

        // Verify user is not returned in normal queries
        $this->assertNull(User::find($userId));

        // Verify user can be found with trashed
        $this->assertNotNull(User::withTrashed()->find($userId));

        $this->logTestResult('passed');
    }
}
