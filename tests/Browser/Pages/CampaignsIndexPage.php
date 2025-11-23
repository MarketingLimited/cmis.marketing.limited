<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class CampaignsIndexPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/campaigns';
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
            '@createButton' => 'a[href*="campaigns/create"]',
            '@searchInput' => 'input[name="search"]',
            '@filterStatus' => 'select[name="status"]',
            '@campaignsList' => '[data-test="campaigns-list"]',
            '@campaignRow' => '[data-test="campaign-row"]',
            '@viewButton' => '[data-test="view-campaign"]',
            '@editButton' => '[data-test="edit-campaign"]',
            '@deleteButton' => '[data-test="delete-campaign"]',
            '@performanceDashboard' => 'a[href*="performance-dashboard"]',
        ];
    }

    /**
     * Search for campaigns.
     */
    public function searchCampaigns(Browser $browser, string $query): void
    {
        $browser->type('@searchInput', $query)
            ->pause(500); // Wait for search results
    }

    /**
     * Filter campaigns by status.
     */
    public function filterByStatus(Browser $browser, string $status): void
    {
        $browser->select('@filterStatus', $status)
            ->pause(500); // Wait for filter results
    }
}
