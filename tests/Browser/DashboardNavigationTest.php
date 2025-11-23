<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\DashboardPage;
use Tests\DuskTestCase;

class DashboardNavigationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test that authenticated user can view dashboard.
     */
    public function test_authenticated_user_can_view_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->assertSee('Dashboard')
                ->assertPresent('@navigation');
        });
    }

    /**
     * Test dashboard displays user information.
     */
    public function test_dashboard_displays_user_info(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->assertSee($this->user->name);
        });
    }

    /**
     * Test navigation to campaigns page.
     */
    public function test_can_navigate_to_campaigns(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->clickLink('Campaigns')
                ->pause(1000)
                ->assertPathBeginsWith('/campaigns');
        });
    }

    /**
     * Test navigation to analytics page.
     */
    public function test_can_navigate_to_analytics(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->clickLink('Analytics')
                ->pause(1000)
                ->assertPathBeginsWith('/analytics');
        });
    }

    /**
     * Test navigation to social media page.
     */
    public function test_can_navigate_to_social(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->clickLink('Social')
                ->pause(1000)
                ->assertPathBeginsWith('/social');
        });
    }

    /**
     * Test navigation to creative page.
     */
    public function test_can_navigate_to_creative(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->clickLink('Creative')
                ->pause(1000)
                ->assertPathBeginsWith('/creative');
        });
    }

    /**
     * Test navigation to settings page.
     */
    public function test_can_navigate_to_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->clickLink('Settings')
                ->pause(1000)
                ->assertPathBeginsWith('/settings');
        });
    }

    /**
     * Test that notifications are accessible.
     */
    public function test_notifications_are_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->waitFor('[data-test="notifications"]', 5)
                ->assertPresent('[data-test="notifications"]');
        });
    }

    /**
     * Test organization switcher is visible.
     */
    public function test_org_switcher_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->waitFor('[data-test="org-switcher"]', 5)
                ->assertPresent('[data-test="org-switcher"]');
        });
    }

    /**
     * Test user menu is accessible.
     */
    public function test_user_menu_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->waitFor('[data-test="user-menu"]', 5)
                ->assertPresent('[data-test="user-menu"]');
        });
    }

    /**
     * Test breadcrumb navigation.
     */
    public function test_breadcrumb_navigation_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->clickLink('Campaigns')
                ->pause(1000)
                ->assertPresent('nav[aria-label="breadcrumb"]');
        });
    }

    /**
     * Test responsive navigation menu.
     */
    public function test_responsive_navigation_menu(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // Mobile size
                ->visit(new DashboardPage)
                ->assertPresent('button[data-toggle="sidebar"]')
                ->resize(1920, 1080); // Reset to desktop
        });
    }

    /**
     * Test home link returns to dashboard.
     */
    public function test_home_link_returns_to_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(500)
                ->clickLink('Dashboard')
                ->pause(500)
                ->assertPathIs('/dashboard');
        });
    }

    /**
     * Test active navigation highlighting.
     */
    public function test_active_navigation_highlighting(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(500)
                ->assertPresent('a.active[href*="campaigns"]');
        });
    }

    /**
     * Test keyboard navigation accessibility.
     */
    public function test_keyboard_navigation_accessibility(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new DashboardPage)
                ->keys('body', '{tab}')
                ->pause(200)
                ->assertFocused('a');
        });
    }
}
