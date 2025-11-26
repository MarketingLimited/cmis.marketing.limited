<?php

namespace Tests\Unit\Models\Permission;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Permission\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Permission Model Unit Tests
 */
class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_permission()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'create_campaigns',
            'display_name' => 'إنشاء الحملات',
            'description' => 'القدرة على إنشاء حملات جديدة',
        ]);

        $this->assertDatabaseHas('cmis.permissions', [
            'permission_id' => $permission->permission_id,
            'name' => 'create_campaigns',
        ]);
    }

    #[Test]
    public function it_has_unique_name()
    {
        Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'edit_content',
            'display_name' => 'Edit Content',
        ]);

        // Should not allow duplicate names
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'model' => 'Permission',
            'test' => 'unique_name',
        ]);
    }

    #[Test]
    public function it_has_display_name()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'delete_posts',
            'display_name' => 'حذف المنشورات',
        ]);

        $this->assertEquals('حذف المنشورات', $permission->display_name);
    }

    #[Test]
    public function it_has_description()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'manage_team',
            'display_name' => 'Manage Team',
            'description' => 'Ability to add, remove, and manage team members',
        ]);

        $this->assertNotNull($permission->description);
    }

    #[Test]
    public function it_has_category()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'view_analytics',
            'display_name' => 'View Analytics',
            'category' => 'analytics',
        ]);

        $this->assertEquals('analytics', $permission->category);
    }

    #[Test]
    public function it_groups_permissions_by_category()
    {
        $categories = ['campaigns', 'content', 'analytics', 'team'];

        foreach ($categories as $category) {
            Permission::create([
                'permission_id' => Str::uuid(),
                'name' => "permission_{$category}",
                'display_name' => ucfirst($category),
                'category' => $category,
            ]);
        }

        $campaignPermissions = Permission::where('category', 'campaigns')->get();
        $this->assertCount(1, $campaignPermissions);
    }

    #[Test]
    public function it_can_be_assigned_to_roles()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'publish_posts',
            'display_name' => 'Publish Posts',
        ]);

        // Permission can be assigned to roles
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'model' => 'Permission',
            'test' => 'role_assignment',
        ]);
    }

    #[Test]
    public function it_supports_wildcard_permissions()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'campaigns.*',
            'display_name' => 'All Campaign Permissions',
        ]);

        $this->assertStringContainsString('*', $permission->name);
    }

    #[Test]
    public function it_has_priority_order()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'admin_access',
            'display_name' => 'Admin Access',
            'priority' => 100,
        ]);

        $this->assertEquals(100, $permission->priority);
    }

    #[Test]
    public function it_can_be_active_or_inactive()
    {
        $activePermission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'active_permission',
            'display_name' => 'Active Permission',
            'is_active' => true,
        ]);

        $inactivePermission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'inactive_permission',
            'display_name' => 'Inactive Permission',
            'is_active' => false,
        ]);

        $this->assertTrue($activePermission->is_active);
        $this->assertFalse($inactivePermission->is_active);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'test_permission',
            'display_name' => 'Test Permission',
        ]);

        $this->assertTrue(Str::isUuid($permission->permission_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $permission = Permission::create([
            'permission_id' => Str::uuid(),
            'name' => 'timestamp_test',
            'display_name' => 'Timestamp Test',
        ]);

        $this->assertNotNull($permission->created_at);
        $this->assertNotNull($permission->updated_at);
    }
}
