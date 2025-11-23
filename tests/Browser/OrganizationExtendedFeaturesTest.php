<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrganizationExtendedFeaturesTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test user can view organization products.
     */
    public function test_user_can_view_organization_products(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/products")
                ->pause(2000)
                ->assertSee('Products')
                ->assertPresent('[data-test="products-list"]');
        });
    }

    /**
     * Test user can view organization services.
     */
    public function test_user_can_view_organization_services(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/services")
                ->pause(2000)
                ->assertSee('Services')
                ->assertPresent('[data-test="services-list"]');
        });
    }

    /**
     * Test user can access campaign comparison page.
     */
    public function test_user_can_access_campaign_comparison(): void
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->assertSee('Compare Campaigns')
                ->assertPresent('[data-test="comparison-table"]');
        });
    }

    /**
     * Test campaign comparison allows selection.
     */
    public function test_campaign_comparison_allows_selection(): void
    {
        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->check('input[name="campaigns[]"]:first')
                ->check('input[name="campaigns[]"]:nth-child(2)')
                ->pause(1000)
                ->press('Compare Selected')
                ->pause(2000)
                ->assertPresent('[data-test="comparison-results"]');
        });
    }

    /**
     * Test user can export comparison to PDF.
     */
    public function test_user_can_export_comparison_to_pdf(): void
    {
        Campaign::factory()->count(2)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->check('input[name="campaigns[]"]:first')
                ->check('input[name="campaigns[]"]:nth-child(2)')
                ->pause(1000)
                ->click('[data-test="export-pdf"]')
                ->pause(3000);
            // PDF download should be initiated
        });
    }

    /**
     * Test user can export comparison to Excel.
     */
    public function test_user_can_export_comparison_to_excel(): void
    {
        Campaign::factory()->count(2)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->check('input[name="campaigns[]"]:first')
                ->check('input[name="campaigns[]"]:nth-child(2)')
                ->pause(1000)
                ->click('[data-test="export-excel"]')
                ->pause(3000);
            // Excel download should be initiated
        });
    }

    /**
     * Test comparison shows key metrics.
     */
    public function test_comparison_shows_key_metrics(): void
    {
        Campaign::factory()->count(2)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->check('input[name="campaigns[]"]:first')
                ->check('input[name="campaigns[]"]:nth-child(2)')
                ->pause(1000)
                ->press('Compare Selected')
                ->pause(2000)
                ->assertSee('Impressions')
                ->assertSee('Clicks')
                ->assertSee('Conversions')
                ->assertSee('CTR')
                ->assertSee('CPC');
        });
    }

    /**
     * Test comparison validates minimum selection.
     */
    public function test_comparison_validates_minimum_selection(): void
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->press('Compare Selected')
                ->pause(1000)
                ->assertSee('select at least');
        });
    }

    /**
     * Test comparison shows charts.
     */
    public function test_comparison_shows_visual_charts(): void
    {
        Campaign::factory()->count(2)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->check('input[name="campaigns[]"]:first')
                ->check('input[name="campaigns[]"]:nth-child(2)')
                ->pause(1000)
                ->press('Compare Selected')
                ->pause(2000)
                ->assertPresent('canvas')
                ->assertPresent('[data-test="comparison-chart"]');
        });
    }

    /**
     * Test user can filter campaigns for comparison.
     */
    public function test_user_can_filter_campaigns_for_comparison(): void
    {
        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/orgs/{$this->org->id}/campaigns/compare")
                ->pause(2000)
                ->select('[name="status_filter"]', 'active')
                ->pause(2000)
                ->assertPresent('[data-test="comparison-table"]');
        });
    }
}
