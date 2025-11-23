<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserManagementTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->admin = User::factory()->create([
            'current_org_id' => $this->org->id,
            'role' => 'admin',
        ]);
    }

    /**
     * Test admin can view users index.
     */
    public function test_admin_can_view_users_index(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users')
                ->pause(1000)
                ->assertSee('Users')
                ->assertPresent('[data-test="users-list"]');
        });
    }

    /**
     * Test users list displays all users.
     */
    public function test_users_list_displays_users(): void
    {
        User::factory()->count(5)->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users')
                ->pause(1000)
                ->assertPresent('[data-test="user-row"]');
        });
    }

    /**
     * Test admin can navigate to create user page.
     */
    public function test_admin_can_navigate_to_create_user(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users')
                ->pause(1000)
                ->click('[data-test="create-user"]')
                ->pause(1000)
                ->assertPathIs('/users/create')
                ->assertSee('Create User');
        });
    }

    /**
     * Test admin can view create user form.
     */
    public function test_admin_can_view_create_user_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users/create')
                ->pause(1000)
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="email"]')
                ->assertPresent('select[name="role"]')
                ->assertPresent('button[type="submit"]');
        });
    }

    /**
     * Test admin can create a new user.
     */
    public function test_admin_can_create_user(): void
    {
        $this->browse(function (Browser $browser) {
            $userEmail = 'newuser' . time() . '@example.com';

            $browser->loginAs($this->admin)
                ->visit('/users/create')
                ->pause(1000)
                ->type('input[name="name"]', 'New User')
                ->type('input[name="email"]', $userEmail)
                ->select('select[name="role"]', 'member')
                ->press('Create User')
                ->pause(2000)
                ->assertPathIs('/users');
        });
    }

    /**
     * Test user creation validates required fields.
     */
    public function test_user_creation_validates_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users/create')
                ->pause(1000)
                ->press('Create User')
                ->pause(1000)
                ->assertSee('required');
        });
    }

    /**
     * Test admin can view user details.
     */
    public function test_admin_can_view_user_details(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
            'name' => 'Test User Detail',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}")
                ->pause(1000)
                ->assertSee($user->name)
                ->assertSee($user->email)
                ->assertPresent('[data-test="user-details"]');
        });
    }

    /**
     * Test admin can navigate to edit user page.
     */
    public function test_admin_can_navigate_to_edit_user(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}")
                ->pause(1000)
                ->click('[data-test="edit-user"]')
                ->pause(1000)
                ->assertPathIs("/users/{$user->id}/edit");
        });
    }

    /**
     * Test admin can edit user details.
     */
    public function test_admin_can_edit_user(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}/edit")
                ->pause(1000)
                ->type('input[name="name"]', 'Updated User Name')
                ->press('Update User')
                ->pause(2000)
                ->assertPathIs("/users/{$user->id}");
        });
    }

    /**
     * Test admin can change user role.
     */
    public function test_admin_can_change_user_role(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
            'role' => 'member',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}/edit")
                ->pause(1000)
                ->select('select[name="role"]', 'admin')
                ->press('Update User')
                ->pause(2000);
        });
    }

    /**
     * Test admin can deactivate user.
     */
    public function test_admin_can_deactivate_user(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}")
                ->pause(1000)
                ->press('Deactivate')
                ->pause(500)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->press('Confirm');
                })
                ->pause(2000)
                ->assertSee('deactivated');
        });
    }

    /**
     * Test admin can search users.
     */
    public function test_admin_can_search_users(): void
    {
        User::factory()->create([
            'current_org_id' => $this->org->id,
            'name' => 'Searchable User',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users')
                ->pause(1000)
                ->type('input[name="search"]', 'Searchable')
                ->pause(1000)
                ->assertSee('Searchable User');
        });
    }

    /**
     * Test admin can filter users by role.
     */
    public function test_admin_can_filter_users_by_role(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users')
                ->pause(1000)
                ->select('[name="role_filter"]', 'admin')
                ->pause(1000)
                ->assertPresent('[data-test="users-list"]');
        });
    }

    /**
     * Test user list pagination.
     */
    public function test_users_list_pagination(): void
    {
        User::factory()->count(25)->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/users')
                ->pause(1000)
                ->assertPresent('nav[role="navigation"]');
        });
    }

    /**
     * Test user details shows activity history.
     */
    public function test_user_details_shows_activity(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}")
                ->pause(1000)
                ->assertPresent('[data-test="activity-log"]');
        });
    }

    /**
     * Test user details shows assigned campaigns.
     */
    public function test_user_details_shows_campaigns(): void
    {
        $user = User::factory()->create([
            'current_org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                ->visit("/users/{$user->id}")
                ->pause(1000)
                ->assertPresent('[data-test="user-campaigns"]');
        });
    }
}
