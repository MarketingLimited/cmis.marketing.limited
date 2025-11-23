<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MiscellaneousFeaturesTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test API documentation is accessible.
     */
    public function test_api_documentation_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/api/documentation')
                ->pause(1000)
                ->assertSee('API Documentation')
                ->assertPresent('[data-test="api-reference"]');
        });
    }

    /**
     * Test channels index.
     */
    public function test_user_can_view_channels(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/channels')
                ->pause(1000)
                ->assertSee('Channels')
                ->assertPresent('[data-test="channels-list"]');
        });
    }

    /**
     * Test channel details.
     */
    public function test_user_can_view_channel_details(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/channels')
                ->pause(1000)
                ->click('[data-test="view-channel"]')
                ->pause(1000)
                ->assertPresent('[data-test="channel-details"]');
        });
    }

    /**
     * Test offerings index.
     */
    public function test_user_can_view_offerings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/offerings')
                ->pause(1000)
                ->assertSee('Offerings')
                ->assertPresent('[data-test="offerings-list"]');
        });
    }

    /**
     * Test products index.
     */
    public function test_user_can_view_products(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/products')
                ->pause(1000)
                ->assertSee('Products')
                ->assertPresent('[data-test="products-list"]');
        });
    }

    /**
     * Test services index.
     */
    public function test_user_can_view_services(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/services')
                ->pause(1000)
                ->assertSee('Services')
                ->assertPresent('[data-test="services-list"]');
        });
    }

    /**
     * Test bundles view.
     */
    public function test_user_can_view_bundles(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/bundles')
                ->pause(1000)
                ->assertSee('Bundles')
                ->assertPresent('[data-test="bundles-list"]');
        });
    }

    /**
     * Test subscription plans.
     */
    public function test_user_can_view_subscription_plans(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/plans')
                ->pause(1000)
                ->assertSee('Plans')
                ->assertPresent('[data-test="plans-list"]');
        });
    }

    /**
     * Test subscription status.
     */
    public function test_user_can_view_subscription_status(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(1000)
                ->assertSee('Subscription')
                ->assertPresent('[data-test="subscription-details"]');
        });
    }

    /**
     * Test unified inbox.
     */
    public function test_user_can_access_unified_inbox(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(1000)
                ->assertSee('Inbox')
                ->assertPresent('[data-test="inbox-items"]');
        });
    }

    /**
     * Test profile page access.
     */
    public function test_user_can_view_profile_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/profile')
                ->pause(1000)
                ->assertSee($this->user->name)
                ->assertPresent('[data-test="profile-info"]');
        });
    }

    /**
     * Test error page handling.
     */
    public function test_404_error_page_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/nonexistent-page')
                ->pause(1000)
                ->assertSee('404');
        });
    }

    /**
     * Test responsive menu on mobile.
     */
    public function test_responsive_mobile_menu(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="mobile-menu-toggle"]')
                ->pause(500)
                ->assertPresent('[data-test="mobile-menu"]')
                ->resize(1920, 1080);
        });
    }

    /**
     * Test dark mode toggle.
     */
    public function test_dark_mode_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="theme-toggle"]')
                ->pause(500)
                ->assertPresent('[data-theme="dark"]');
        });
    }
}
