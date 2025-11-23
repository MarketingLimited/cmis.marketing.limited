<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class RegisterPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/register';
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
            '@name' => 'input[name="name"]',
            '@email' => 'input[name="email"]',
            '@password' => 'input[name="password"]',
            '@passwordConfirmation' => 'input[name="password_confirmation"]',
            '@submit' => 'button[type="submit"]',
            '@loginLink' => 'a[href*="login"]',
        ];
    }

    /**
     * Register with the given details.
     */
    public function registerWith(Browser $browser, string $name, string $email, string $password): void
    {
        $browser->type('@name', $name)
            ->type('@email', $email)
            ->type('@password', $password)
            ->type('@passwordConfirmation', $password)
            ->click('@submit');
    }
}
