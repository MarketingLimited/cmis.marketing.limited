<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class SocialMediaPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/social';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/social');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@postsTab' => 'a[href*="social/posts"]',
            '@schedulerTab' => 'a[href*="social/scheduler"]',
            '@inboxTab' => 'a[href*="social/inbox"]',
            '@createPostButton' => '[data-test="create-post"]',
            '@postsList' => '[data-test="posts-list"]',
            '@scheduleButton' => '[data-test="schedule-button"]',
        ];
    }

    /**
     * Navigate to a social tab.
     */
    public function navigateToTab(Browser $browser, string $tab): void
    {
        $element = '@' . $tab . 'Tab';
        if (isset($this->elements()[$element])) {
            $browser->click($element);
        }
    }
}
