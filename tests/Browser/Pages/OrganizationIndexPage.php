<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class OrganizationIndexPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/orgs';
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
            '@createButton' => 'a[href*="orgs/create"]',
            '@orgsList' => '[data-test="orgs-list"]',
            '@orgCard' => '[data-test="org-card"]',
            '@selectOrgButton' => '[data-test="select-org"]',
            '@viewOrgButton' => '[data-test="view-org"]',
            '@editOrgButton' => '[data-test="edit-org"]',
        ];
    }

    /**
     * Select an organization.
     */
    public function selectOrganization(Browser $browser, int $index = 0): void
    {
        $browser->with('@orgsList', function ($list) use ($index) {
            $list->click("@selectOrgButton:nth-child({$index})");
        });
    }
}
