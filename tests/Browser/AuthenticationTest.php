<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\LoginPage;
use Tests\Browser\Pages\RegisterPage;
use Tests\Browser\Pages\DashboardPage;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test that a user can view the login page.
     */
    public function test_user_can_view_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->assertSee('Login')
                ->assertPresent('@email')
                ->assertPresent('@password')
                ->assertPresent('@submit');
        });
    }

    /**
     * Test that a user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->loginWith($browser, 'test@example.com', 'password123')
                ->pause(1000)
                ->assertPathIs('/dashboard');
        });
    }

    /**
     * Test that login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->loginWith($browser, 'invalid@example.com', 'wrongpassword')
                ->pause(1000)
                ->assertPathIs('/login')
                ->assertSee('credentials');
        });
    }

    /**
     * Test that email validation works on login.
     */
    public function test_login_validates_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->type('@email', 'invalid-email')
                ->type('@password', 'password123')
                ->click('@submit')
                ->pause(500)
                ->assertPathIs('/login');
        });
    }

    /**
     * Test that password field is required.
     */
    public function test_login_requires_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->type('@email', 'test@example.com')
                ->click('@submit')
                ->pause(500)
                ->assertPathIs('/login');
        });
    }

    /**
     * Test that user can view registration page.
     */
    public function test_user_can_view_registration_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                ->assertSee('Register')
                ->assertPresent('@name')
                ->assertPresent('@email')
                ->assertPresent('@password')
                ->assertPresent('@passwordConfirmation')
                ->assertPresent('@submit');
        });
    }

    /**
     * Test that a user can register successfully.
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                ->registerWith($browser, 'Test User', 'newuser@example.com', 'password123')
                ->pause(1000)
                ->assertPathBeginsWith('/');
        });

        $this->assertDatabaseHas('cmis_core.users', [
            'email' => 'newuser@example.com',
        ]);
    }

    /**
     * Test that registration validates duplicate email.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                ->registerWith($browser, 'Test User', 'existing@example.com', 'password123')
                ->pause(1000)
                ->assertPathIs('/register')
                ->assertSee('email');
        });
    }

    /**
     * Test that password confirmation must match.
     */
    public function test_registration_requires_password_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                ->type('@name', 'Test User')
                ->type('@email', 'test@example.com')
                ->type('@password', 'password123')
                ->type('@passwordConfirmation', 'differentpassword')
                ->click('@submit')
                ->pause(1000)
                ->assertPathIs('/register')
                ->assertSee('password');
        });
    }

    /**
     * Test that user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new DashboardPage)
                ->assertAuthenticated()
                ->press('Logout')
                ->pause(1000)
                ->assertGuest()
                ->assertPathIs('/login');
        });
    }

    /**
     * Test that remember me checkbox works.
     */
    public function test_remember_me_functionality(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->loginWith($browser, 'remember@example.com', 'password123', true)
                ->pause(1000)
                ->assertPathIs('/dashboard');
        });
    }

    /**
     * Test that unauthenticated users are redirected to login.
     */
    public function test_unauthenticated_users_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                ->pause(500)
                ->assertPathIs('/login');
        });
    }

    /**
     * Test navigation between login and register pages.
     */
    public function test_navigation_between_auth_pages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->assertPresent('@registerLink')
                ->click('@registerLink')
                ->pause(500)
                ->assertPathIs('/register')
                ->assertPresent('@loginLink')
                ->click('@loginLink')
                ->pause(500)
                ->assertPathIs('/login');
        });
    }
}
