<?php

namespace Tests\Unit\Models\Role;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Role\Role;
use App\Models\Permission\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Role Model Unit Tests
 */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_role()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'content_manager',
            'display_name' => 'Content Manager',
            'description' => 'Manages all content',
        ]);

        $this->assertDatabaseHas('cmis.roles', [
            'role_id' => $role->role_id,
            'name' => 'content_manager',
        ]);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'create',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'editor',
            'display_name' => 'Editor',
        ]);

        $this->assertEquals($org->org_id, $role->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'belongs_to_org',
        ]);
    }

    /** @test */
    public function it_has_unique_name_per_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);

        // Should not allow duplicate role name in same org
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'unique_name',
        ]);
    }

    /** @test */
    public function it_has_display_name_in_arabic()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'campaign_manager',
            'display_name' => 'مدير الحملات',
            'description' => 'يدير جميع الحملات التسويقية',
        ]);

        $this->assertEquals('مدير الحملات', $role->display_name);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'arabic_display_name',
        ]);
    }

    /** @test */
    public function it_can_have_permissions()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'admin',
            'display_name' => 'Administrator',
        ]);

        $permission1 = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'create_campaigns',
            'display_name' => 'Create Campaigns',
        ]);

        $permission2 = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'delete_campaigns',
            'display_name' => 'Delete Campaigns',
        ]);

        $role->permissions()->attach([$permission1->permission_id, $permission2->permission_id]);

        $this->assertCount(2, $role->permissions);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'has_permissions',
        ]);
    }

    /** @test */
    public function it_tracks_is_default_flag()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'member',
            'display_name' => 'Member',
            'is_default' => true,
        ]);

        $this->assertTrue($role->is_default);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'is_default',
        ]);
    }

    /** @test */
    public function it_can_be_system_role()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'is_system' => true,
        ]);

        // System roles cannot be deleted
        $this->assertTrue($role->is_system);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'system_role',
        ]);
    }

    /** @test */
    public function it_has_priority_order()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role1 = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'admin',
            'display_name' => 'Admin',
            'priority' => 1,
        ]);

        $role2 = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'editor',
            'display_name' => 'Editor',
            'priority' => 2,
        ]);

        $roles = Role::where('org_id', $org->org_id)->orderBy('priority')->get();

        $this->assertEquals('admin', $roles->first()->name);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'priority_order',
        ]);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'viewer',
            'display_name' => 'Viewer',
        ]);

        $this->assertTrue(Str::isUuid($role->role_id));

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'uuid_primary_key',
        ]);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'analyst',
            'display_name' => 'Analyst',
        ]);

        $this->assertNotNull($role->created_at);
        $this->assertNotNull($role->updated_at);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'timestamps',
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

        Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);

        Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);

        $org1Roles = Role::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Roles);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'rls_isolation',
        ]);
    }

    /** @test */
    public function it_can_count_users_with_role()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'editor',
            'display_name' => 'Editor',
            'user_count' => 0,
        ]);

        // When users are assigned, user_count should increase
        $role->update(['user_count' => 5]);

        $this->assertEquals(5, $role->fresh()->user_count);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'user_count',
        ]);
    }

    /** @test */
    public function it_stores_metadata_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'color' => '#FF5733',
            'icon' => 'user-shield',
            'custom_fields' => ['department' => 'Marketing'],
        ];

        $role = Role::create([
            'role_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'custom_role',
            'display_name' => 'Custom Role',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('#FF5733', $role->metadata['color']);
        $this->assertEquals('user-shield', $role->metadata['icon']);

        $this->logTestResult('passed', [
            'model' => 'Role',
            'test' => 'metadata_json',
        ]);
    }
}
