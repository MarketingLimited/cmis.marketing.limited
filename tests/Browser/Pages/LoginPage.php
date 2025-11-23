<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class LoginPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/login';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@email' => 'input[name="email"]',
            '@password' => 'input[name="password"]',
            '@remember' => 'input[name="remember"]',
            '@submit' => 'button[type="submit"]',
            '@registerLink' => 'a[href*="register"]',
            '@forgotPasswordLink' => 'a[href*="forgot-password"]',
        ];
    }

    /**
     * Login with the given credentials.
     */
    public function loginWith(Browser $browser, string $email, string $password, bool $remember = false): void
    {
        $browser->type('@email', $email)
            ->type('@password', $password);

        if ($remember) {
            $browser->check('@remember');
        }

        $browser->click('@submit');
    }
}
