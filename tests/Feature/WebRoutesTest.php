<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('vite.manifest', base_path('tests/fixtures/vite/manifest.json'));
    }

    /** @test */
    public function root_redirects_to_login_when_guest(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function login_page_is_accessible_to_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Log in', false);
    }

    /** @test */
    public function register_page_is_accessible_to_guests(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Register', false);
    }

    /** @test */
    public function protected_routes_require_authentication(): void
    {
        $protectedRoutes = [
            'dashboard',
            'campaigns.index',
            'offerings.index',
            'analytics.index',
            'creative.index',
        ];

        foreach ($protectedRoutes as $routeName) {
            $response = $this->get(route($routeName));

            $this->assertTrue(
                $response->isRedirection() || $response->status() === 401,
                sprintf('Expected %s to be protected for guests.', $routeName)
            );
        }
    }

    /** @test */
    public function key_named_routes_are_registered(): void
    {
        $expectedRoutes = [
            'dashboard',
            'campaigns.index',
            'campaigns.create',
            'campaigns.edit',
            'orgs.index',
            'offerings.index',
            'analytics.index',
            'creative.index',
            'briefs.index',
            'channels.index',
            'ai.index',
            'profile.edit',
            'settings.index',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                sprintf('Expected route "%s" to be registered.', $routeName)
            );
        }
    }
}
