<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class CampaignCreatePage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/campaigns/create';
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
            '@description' => 'textarea[name="description"]',
            '@startDate' => 'input[name="start_date"]',
            '@endDate' => 'input[name="end_date"]',
            '@budget' => 'input[name="budget"]',
            '@status' => 'select[name="status"]',
            '@platform' => 'select[name="platform"]',
            '@objective' => 'select[name="objective"]',
            '@submitButton' => 'button[type="submit"]',
            '@cancelButton' => 'a[href*="campaigns"]',
        ];
    }

    /**
     * Fill campaign form with the given data.
     */
    public function fillCampaignForm(Browser $browser, array $data): void
    {
        if (isset($data['name'])) {
            $browser->type('@name', $data['name']);
        }
        if (isset($data['description'])) {
            $browser->type('@description', $data['description']);
        }
        if (isset($data['start_date'])) {
            $browser->type('@startDate', $data['start_date']);
        }
        if (isset($data['end_date'])) {
            $browser->type('@endDate', $data['end_date']);
        }
        if (isset($data['budget'])) {
            $browser->type('@budget', $data['budget']);
        }
        if (isset($data['status'])) {
            $browser->select('@status', $data['status']);
        }
        if (isset($data['platform'])) {
            $browser->select('@platform', $data['platform']);
        }
        if (isset($data['objective'])) {
            $browser->select('@objective', $data['objective']);
        }
    }

    /**
     * Submit the campaign form.
     */
    public function submitForm(Browser $browser): void
    {
        $browser->click('@submitButton');
    }
}
