<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CampaignPerformanceRangeTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Organization $org;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
        $this->campaign = Campaign::factory()->create([
            'org_id' => $this->org->id,
        ]);
    }

    /**
     * Test user can view daily performance.
     */
    public function test_user_can_view_daily_performance(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(2000)
                ->assertSee('Daily Performance')
                ->assertPresent('[data-test="performance-chart"]');
        });
    }

    /**
     * Test user can view weekly performance.
     */
    public function test_user_can_view_weekly_performance(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/weekly")
                ->pause(2000)
                ->assertSee('Weekly Performance')
                ->assertPresent('[data-test="performance-chart"]');
        });
    }

    /**
     * Test user can view monthly performance.
     */
    public function test_user_can_view_monthly_performance(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/monthly")
                ->pause(2000)
                ->assertSee('Monthly Performance')
                ->assertPresent('[data-test="performance-chart"]');
        });
    }

    /**
     * Test user can view yearly performance.
     */
    public function test_user_can_view_yearly_performance(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/yearly")
                ->pause(2000)
                ->assertSee('Yearly Performance')
                ->assertPresent('[data-test="performance-chart"]');
        });
    }

    /**
     * Test performance view shows key metrics.
     */
    public function test_performance_view_shows_metrics(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(2000)
                ->assertPresent('[data-metric="impressions"]')
                ->assertPresent('[data-metric="clicks"]')
                ->assertPresent('[data-metric="conversions"]')
                ->assertPresent('[data-metric="spend"]');
        });
    }

    /**
     * Test user can switch between performance ranges.
     */
    public function test_user_can_switch_performance_ranges(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}")
                ->pause(1000)
                ->click('[data-test="performance-tab"]')
                ->pause(1000)
                ->click('[data-range="daily"]')
                ->pause(2000)
                ->assertPathIs("/campaigns/{$this->campaign->id}/performance/daily")
                ->click('[data-range="weekly"]')
                ->pause(2000)
                ->assertPathIs("/campaigns/{$this->campaign->id}/performance/weekly");
        });
    }

    /**
     * Test performance chart displays data.
     */
    public function test_performance_chart_displays_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(3000)
                ->assertPresent('canvas')
                ->assertScript('return document.querySelector("canvas") !== null', true);
        });
    }

    /**
     * Test performance view shows trend indicators.
     */
    public function test_performance_shows_trend_indicators(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(2000)
                ->assertPresent('[data-test="trend-indicator"]');
        });
    }

    /**
     * Test performance view can be exported.
     */
    public function test_performance_can_be_exported(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(2000)
                ->click('[data-test="export-performance"]')
                ->pause(1000)
                ->assertPresent('[data-test="export-options"]');
        });
    }

    /**
     * Test performance view shows comparison with previous period.
     */
    public function test_performance_shows_period_comparison(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/weekly")
                ->pause(2000)
                ->assertPresent('[data-test="comparison-data"]')
                ->assertSee('%');
        });
    }

    /**
     * Test performance filters work.
     */
    public function test_performance_filters_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(2000)
                ->select('[name="metric"]', 'clicks')
                ->pause(2000)
                ->assertPresent('[data-test="performance-chart"]');
        });
    }

    /**
     * Test performance date range can be customized.
     */
    public function test_performance_custom_date_range(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$this->campaign->id}/performance/daily")
                ->pause(2000)
                ->click('[data-test="custom-date-range"]')
                ->pause(1000)
                ->type('input[name="start_date"]', now()->subDays(7)->format('Y-m-d'))
                ->type('input[name="end_date"]', now()->format('Y-m-d'))
                ->press('Apply')
                ->pause(2000)
                ->assertPresent('[data-test="performance-chart"]');
        });
    }
}
