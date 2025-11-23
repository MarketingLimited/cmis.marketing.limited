<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\OrganizationIndexPage;
use Tests\DuskTestCase;

class OrganizationManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Test user can view organizations list.
     */
    public function test_user_can_view_organizations_list(): void
    {
        Organization::factory()->count(3)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new OrganizationIndexPage)
                ->assertSee('Organizations')
                ->assertPresent('@orgsList');
        });
    }

    /**
     * Test user can create new organization.
     */
    public function test_user_can_create_organization(): void
    {
        $this->browse(function (Browser $browser) {
            $orgName = 'Test Organization ' . time();

            $browser->loginAs($this->user)
                ->visit(new OrganizationIndexPage)
                ->click('@createButton')
                ->pause(1000)
                ->assertPathIs('/orgs/create')
                ->type('input[name="name"]', $orgName)
                ->type('input[name="domain"]', 'testorg.com')
                ->press('Create')
                ->pause(2000)
                ->assertPathIs('/orgs');
        });
    }

    /**
     * Test user can select an organization.
     */
    public function test_user_can_select_organization(): void
    {
        $org = Organization::factory()->create();

        $this->browse(function (Browser $browser) use ($org) {
            $browser->loginAs($this->user)
                ->visit(new OrganizationIndexPage)
                ->pause(1000)
                ->click("[data-org-id='{$org->id}']")
                ->pause(2000)
                ->assertPathIs('/dashboard');
        });
    }

    /**
     * Test user can view organization details.
     */
    public function test_user_can_view_organization_details(): void
    {
        $org = Organization::factory()->create([
            'name' => 'View Test Org',
        ]);

        $this->browse(function (Browser $browser) use ($org) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$org->id}")
                ->pause(1000)
                ->assertSee($org->name)
                ->assertPresent('[data-test="org-details"]');
        });
    }

    /**
     * Test user can edit organization.
     */
    public function test_user_can_edit_organization(): void
    {
        $org = Organization::factory()->create();

        $this->browse(function (Browser $browser) use ($org) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$org->id}/edit")
                ->pause(1000)
                ->type('input[name="name"]', 'Updated Org Name')
                ->press('Update')
                ->pause(2000)
                ->assertPathIs("/orgs/{$org->id}");
        });

        $this->assertDatabaseHas('cmis_core.organizations', [
            'id' => $org->id,
            'name' => 'Updated Org Name',
        ]);
    }

    /**
     * Test user can view organization campaigns.
     */
    public function test_user_can_view_organization_campaigns(): void
    {
        $org = Organization::factory()->create();

        $this->browse(function (Browser $browser) use ($org) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$org->id}/campaigns")
                ->pause(1000)
                ->assertSee('Campaigns')
                ->assertPresent('[data-test="campaigns-list"]');
        });
    }

    /**
     * Test user can invite team member.
     */
    public function test_user_can_invite_team_member(): void
    {
        $org = Organization::factory()->create();

        $this->browse(function (Browser $browser) use ($org) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$org->id}/team")
                ->pause(1000)
                ->click('[data-test="invite-member"]')
                ->pause(1000)
                ->type('input[name="email"]', 'newmember@example.com')
                ->select('select[name="role"]', 'member')
                ->press('Send Invitation')
                ->pause(2000)
                ->assertSee('Invitation sent');
        });
    }

    /**
     * Test organization switcher functionality.
     */
    public function test_organization_switcher(): void
    {
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        $this->browse(function (Browser $browser) use ($org1, $org2) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="org-switcher"]')
                ->pause(500)
                ->assertSee($org1->name)
                ->assertSee($org2->name);
        });
    }

    /**
     * Test organization comparison export.
     */
    public function test_organization_campaign_comparison_export(): void
    {
        $org = Organization::factory()->create();

        $this->browse(function (Browser $browser) use ($org) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$org->id}/campaigns/compare")
                ->pause(1000)
                ->click('[data-test="export-pdf"]')
                ->pause(2000);
            // Verify download initiated
        });
    }

    /**
     * Test organization validation.
     */
    public function test_organization_creation_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/orgs/create')
                ->pause(1000)
                ->press('Create')
                ->pause(1000)
                ->assertPathIs('/orgs/create')
                ->assertSee('name');
        });
    }
}
