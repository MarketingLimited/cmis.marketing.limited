<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class DashboardPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/dashboard';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
        // Dashboard text can be in Arabic (لوحة التحكم) or English depending on locale
        // Just assert we're on the correct path
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@navigation' => 'nav',
            '@userMenu' => '[data-test="user-menu"]',
            '@orgSwitcher' => '[data-test="org-switcher"]',
            '@notifications' => '[data-test="notifications"]',
            '@campaignsLink' => 'a[href*="campaigns"]',
            '@analyticsLink' => 'a[href*="analytics"]',
            '@socialLink' => 'a[href*="social"]',
            '@creativeLink' => 'a[href*="creative"]',
            '@settingsLink' => 'a[href*="settings"]',
            '@logoutButton' => 'button[type="submit"]',
        ];
    }

    /**
     * Navigate to a specific section.
     */
    public function navigateTo(Browser $browser, string $section): void
    {
        $element = '@' . $section . 'Link';
        if (isset($this->elements()[$element])) {
            $browser->click($element);
        }
    }
}
