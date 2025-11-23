<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class CreativePage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/creative';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/creative');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@assetsTab' => 'a[href*="creative-assets"]',
            '@adsTab' => 'a[href*="creative/ads"]',
            '@templatesTab' => 'a[href*="creative/templates"]',
            '@briefsTab' => 'a[href*="briefs"]',
            '@uploadButton' => '[data-test="upload-button"]',
            '@assetsList' => '[data-test="assets-list"]',
        ];
    }

    /**
     * Navigate to a creative tab.
     */
    public function navigateToTab(Browser $browser, string $tab): void
    {
        $element = '@' . $tab . 'Tab';
        if (isset($this->elements()[$element])) {
            $browser->click($element);
        }
    }
}
