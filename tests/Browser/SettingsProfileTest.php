<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\SettingsPage;
use Tests\DuskTestCase;

class SettingsProfileTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'current_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test user can access settings page.
     */
    public function test_user_can_access_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SettingsPage)
                ->assertSee('Settings')
                ->assertPresent('@profileTab');
        });
    }

    /**
     * Test user can view profile settings.
     */
    public function test_user_can_view_profile_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertSee('Profile')
                ->assertInputValue('input[name="name"]', $this->user->name)
                ->assertInputValue('input[name="email"]', $this->user->email);
        });
    }

    /**
     * Test user can update profile.
     */
    public function test_user_can_update_profile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/profile')
                ->pause(1000)
                ->type('input[name="name"]', 'Updated Name')
                ->click('@saveButton')
                ->pause(2000)
                ->assertSee('Profile updated');
        });

        $this->assertDatabaseHas('cmis_core.users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test user can change password.
     */
    public function test_user_can_change_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/security')
                ->pause(1000)
                ->type('input[name="current_password"]', 'password')
                ->type('input[name="new_password"]', 'newpassword123')
                ->type('input[name="new_password_confirmation"]', 'newpassword123')
                ->press('Update Password')
                ->pause(2000)
                ->assertSee('Password updated');
        });
    }

    /**
     * Test notification settings.
     */
    public function test_user_can_update_notification_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SettingsPage)
                ->navigateToTab($browser, 'notifications')
                ->pause(1000)
                ->assertPathIs('/settings/notifications')
                ->check('input[name="email_notifications"]')
                ->check('input[name="campaign_alerts"]')
                ->click('@saveButton')
                ->pause(2000)
                ->assertSee('Settings updated');
        });
    }

    /**
     * Test security settings.
     */
    public function test_user_can_view_security_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SettingsPage)
                ->navigateToTab($browser, 'security')
                ->pause(1000)
                ->assertPathIs('/settings/security')
                ->assertSee('Security')
                ->assertPresent('input[name="current_password"]');
        });
    }

    /**
     * Test integrations settings.
     */
    public function test_user_can_view_integrations_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SettingsPage)
                ->navigateToTab($browser, 'integrations')
                ->pause(1000)
                ->assertPathIs('/settings/integrations')
                ->assertSee('Integrations');
        });
    }

    /**
     * Test platform connection.
     */
    public function test_user_can_connect_platform(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/integrations')
                ->pause(1000)
                ->click('[data-platform="facebook"]')
                ->pause(2000);
            // Would redirect to OAuth flow
        });
    }

    /**
     * Test profile validation.
     */
    public function test_profile_update_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/profile')
                ->pause(1000)
                ->clear('input[name="name"]')
                ->click('@saveButton')
                ->pause(1000)
                ->assertSee('name');
        });
    }

    /**
     * Test email uniqueness validation.
     */
    public function test_email_uniqueness_validation(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/profile')
                ->pause(1000)
                ->type('input[name="email"]', 'existing@example.com')
                ->click('@saveButton')
                ->pause(1000)
                ->assertSee('email');
        });
    }

    /**
     * Test password confirmation requirement.
     */
    public function test_password_confirmation_requirement(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/security')
                ->pause(1000)
                ->type('input[name="current_password"]', 'password')
                ->type('input[name="new_password"]', 'newpassword123')
                ->type('input[name="new_password_confirmation"]', 'differentpassword')
                ->press('Update Password')
                ->pause(1000)
                ->assertSee('password');
        });
    }

    /**
     * Test cancel button functionality.
     */
    public function test_settings_cancel_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/profile')
                ->pause(1000)
                ->type('input[name="name"]', 'Changed Name')
                ->click('@cancelButton')
                ->pause(1000)
                ->assertPathIs('/dashboard');
        });
    }
}
