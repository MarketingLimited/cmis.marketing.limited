<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class AnalyticsPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/analytics/enterprise';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/analytics');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@dateRangePicker' => '[data-test="date-range-picker"]',
            '@realtimeLink' => 'a[href*="realtime"]',
            '@campaignsLink' => 'a[href*="campaigns"]',
            '@kpisLink' => 'a[href*="kpis"]',
            '@exportButton' => '[data-test="export-button"]',
            '@refreshButton' => '[data-test="refresh-button"]',
            '@metricsCards' => '[data-test="metrics-card"]',
            '@chart' => '[data-test="chart"]',
        ];
    }

    /**
     * Select a date range.
     */
    public function selectDateRange(Browser $browser, string $startDate, string $endDate): void
    {
        $browser->click('@dateRangePicker')
            ->type('input[name="start_date"]', $startDate)
            ->type('input[name="end_date"]', $endDate)
            ->pause(500);
    }
}
