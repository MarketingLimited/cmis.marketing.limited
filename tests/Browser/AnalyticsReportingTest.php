<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\AnalyticsPage;
use Tests\DuskTestCase;

class AnalyticsReportingTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test user can access analytics dashboard.
     */
    public function test_user_can_access_analytics_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->assertSee('Analytics')
                ->assertPresent('@metricsCards');
        });
    }

    /**
     * Test analytics displays key metrics.
     */
    public function test_analytics_displays_key_metrics(): void
    {
        Campaign::factory()->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->pause(2000)
                ->assertPresent('[data-metric="impressions"]')
                ->assertPresent('[data-metric="clicks"]')
                ->assertPresent('[data-metric="conversions"]')
                ->assertPresent('[data-metric="spend"]');
        });
    }

    /**
     * Test user can navigate to realtime analytics.
     */
    public function test_user_can_access_realtime_analytics(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/analytics/enterprise')
                ->pause(1000)
                ->click('@realtimeLink')
                ->pause(1000)
                ->assertPathIs('/analytics/realtime')
                ->assertSee('Real-Time');
        });
    }

    /**
     * Test user can view campaign analytics.
     */
    public function test_user_can_view_campaign_analytics(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/analytics/enterprise')
                ->pause(1000)
                ->click('@campaignsLink')
                ->pause(1000)
                ->assertPathIs('/analytics/campaigns');
        });
    }

    /**
     * Test user can view KPIs dashboard.
     */
    public function test_user_can_view_kpis_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/analytics/enterprise')
                ->pause(1000)
                ->click('@kpisLink')
                ->pause(1000)
                ->assertPathIs('/analytics/kpis');
        });
    }

    /**
     * Test date range picker functionality.
     */
    public function test_date_range_picker_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->pause(1000)
                ->selectDateRange(
                    $browser,
                    now()->subDays(7)->format('Y-m-d'),
                    now()->format('Y-m-d')
                )
                ->pause(2000)
                ->assertPresent('@metricsCards');
        });
    }

    /**
     * Test analytics export functionality.
     */
    public function test_user_can_export_analytics_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->pause(1000)
                ->click('@exportButton')
                ->pause(500)
                ->assertPresent('[data-test="export-options"]')
                ->assertSee('PDF')
                ->assertSee('Excel');
        });
    }

    /**
     * Test analytics refresh functionality.
     */
    public function test_user_can_refresh_analytics_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->pause(1000)
                ->click('@refreshButton')
                ->pause(2000)
                ->assertPresent('@metricsCards');
        });
    }

    /**
     * Test analytics charts are displayed.
     */
    public function test_analytics_charts_are_displayed(): void
    {
        Campaign::factory()->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->pause(2000)
                ->assertPresent('@chart')
                ->assertPresent('canvas');
        });
    }

    /**
     * Test analytics filtering by campaign.
     */
    public function test_analytics_filter_by_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->id,
            'name' => 'Filter Test Campaign',
        ]);

        $this->browse(function (Browser $browser) use ($campaign) {
            $browser->loginAs($this->user)
                ->visit('/analytics/campaigns')
                ->pause(1000)
                ->select('[name="campaign_id"]', $campaign->id)
                ->pause(2000)
                ->assertSee($campaign->name);
        });
    }

    /**
     * Test analytics comparison view.
     */
    public function test_analytics_comparison_view(): void
    {
        Campaign::factory()->count(2)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/analytics/campaigns')
                ->pause(1000)
                ->check('input[name="compare"]')
                ->pause(1000)
                ->assertPresent('[data-test="comparison-chart"]');
        });
    }

    /**
     * Test analytics metric tooltips.
     */
    public function test_analytics_metric_tooltips(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->pause(1000)
                ->mouseover('[data-metric="impressions"]')
                ->pause(500)
                ->assertPresent('[data-test="tooltip"]');
        });
    }

    /**
     * Test analytics responsive layout.
     */
    public function test_analytics_responsive_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // Mobile
                ->visit(new AnalyticsPage)
                ->pause(1000)
                ->assertPresent('@metricsCards')
                ->resize(1920, 1080); // Desktop
        });
    }

    /**
     * Test analytics data loading states.
     */
    public function test_analytics_loading_states(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AnalyticsPage)
                ->assertPresent('[data-test="loading"]')
                ->pause(3000)
                ->assertMissing('[data-test="loading"]');
        });
    }

    /**
     * Test analytics empty state.
     */
    public function test_analytics_empty_state(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/analytics/campaigns')
                ->pause(2000)
                ->assertSee('No data available');
        });
    }
}
