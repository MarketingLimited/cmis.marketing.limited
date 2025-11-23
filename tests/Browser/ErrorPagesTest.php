<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ErrorPagesTest extends DuskTestCase
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
     * Test 404 page displays for non-existent routes.
     */
    public function test_404_page_displays_for_nonexistent_routes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/this-route-does-not-exist')
                ->pause(2000)
                ->assertSee('404')
                ->assertPresent('[data-test="error-404"]');
        });
    }

    /**
     * Test 404 page has home link.
     */
    public function test_404_page_has_home_link(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/nonexistent-page')
                ->pause(2000)
                ->assertPresent('a[href*="dashboard"]')
                ->clickLink('Home')
                ->pause(1000)
                ->assertPathIs('/dashboard');
        });
    }

    /**
     * Test 404 page shows helpful message.
     */
    public function test_404_page_shows_helpful_message(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/missing-resource')
                ->pause(2000)
                ->assertSee('not found')
                ->assertSee('page');
        });
    }

    /**
     * Test 403 forbidden page for unauthorized access.
     */
    public function test_403_page_for_unauthorized_access(): void
    {
        // Create a route that requires admin permission
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/restricted-area')
                ->pause(2000)
                ->assertSee('403');
        });
    }

    /**
     * Test 500 error page displays on server error.
     */
    public function test_500_error_page_displays(): void
    {
        $this->browse(function (Browser $browser) {
            // This would need a route that intentionally throws an error
            $browser->loginAs($this->user)
                ->visit('/test-500-error')
                ->pause(2000)
                ->assertSee('500');
        });
    }

    /**
     * Test 503 maintenance mode page.
     */
    public function test_503_maintenance_page(): void
    {
        // Enable maintenance mode
        \Artisan::call('down', ['--render' => 'errors.503']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(2000)
                ->assertSee('503')
                ->assertSee('maintenance');
        });

        // Disable maintenance mode
        \Artisan::call('up');
    }

    /**
     * Test error pages maintain branding.
     */
    public function test_error_pages_maintain_branding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/nonexistent')
                ->pause(2000)
                ->assertPresent('[data-test="logo"]');
        });
    }

    /**
     * Test error pages are responsive.
     */
    public function test_error_pages_responsive(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // Mobile
                ->visit('/nonexistent')
                ->pause(2000)
                ->assertPresent('[data-test="error-404"]')
                ->resize(1920, 1080); // Desktop
        });
    }

    /**
     * Test error page shows contact support option.
     */
    public function test_error_page_shows_contact_support(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/missing-page')
                ->pause(2000)
                ->assertPresent('[data-test="contact-support"]');
        });
    }

    /**
     * Test error page shows search functionality.
     */
    public function test_error_page_shows_search(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/not-found')
                ->pause(2000)
                ->assertPresent('input[name="search"]');
        });
    }

    /**
     * Test error logging for critical errors.
     */
    public function test_error_logging(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/trigger-error')
                ->pause(2000);

            // Verify error was logged
            $this->assertFileExists(storage_path('logs/laravel.log'));
        });
    }

    /**
     * Test unauthorized access redirects appropriately.
     */
    public function test_unauthorized_access_redirects(): void
    {
        // Test as guest
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                ->pause(1000)
                ->assertPathIs('/login');
        });
    }

    /**
     * Test CSRF token mismatch handling.
     */
    public function test_csrf_token_mismatch_handling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->script('document.querySelector(\'input[name="_token"]\').value = "invalid-token"')
                ->press('Create')
                ->pause(2000)
                ->assertSee('419');
        });
    }
}
