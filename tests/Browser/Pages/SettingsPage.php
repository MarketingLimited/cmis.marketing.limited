<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class SettingsPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/settings';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/settings');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@profileTab' => 'a[href*="settings/profile"]',
            '@notificationsTab' => 'a[href*="settings/notifications"]',
            '@securityTab' => 'a[href*="settings/security"]',
            '@integrationsTab' => 'a[href*="settings/integrations"]',
            '@saveButton' => 'button[type="submit"]',
            '@cancelButton' => '[data-test="cancel-button"]',
        ];
    }

    /**
     * Navigate to a settings tab.
     */
    public function navigateToTab(Browser $browser, string $tab): void
    {
        $element = '@' . $tab . 'Tab';
        if (isset($this->elements()[$element])) {
            $browser->click($element);
        }
    }
}
