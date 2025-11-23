<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\CampaignsIndexPage;
use Tests\Browser\Pages\CampaignCreatePage;
use Tests\DuskTestCase;

class CampaignManagementTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test that user can view campaigns index page.
     */
    public function test_user_can_view_campaigns_index(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->assertSee('Campaigns')
                ->assertPresent('@createButton');
        });
    }

    /**
     * Test that campaigns list displays existing campaigns.
     */
    public function test_campaigns_list_displays_campaigns(): void
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->pause(1000)
                ->assertPresent('@campaignsList');
        });
    }

    /**
     * Test that user can navigate to create campaign page.
     */
    public function test_user_can_navigate_to_create_campaign(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->click('@createButton')
                ->pause(1000)
                ->assertPathIs('/campaigns/create');
        });
    }

    /**
     * Test that user can view campaign creation form.
     */
    public function test_user_can_view_campaign_creation_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignCreatePage)
                ->assertPresent('@name')
                ->assertPresent('@description')
                ->assertPresent('@startDate')
                ->assertPresent('@endDate')
                ->assertPresent('@budget')
                ->assertPresent('@submitButton');
        });
    }

    /**
     * Test that user can create a new campaign with valid data.
     */
    public function test_user_can_create_campaign_with_valid_data(): void
    {
        $this->browse(function (Browser $browser) {
            $campaignData = [
                'name' => 'Test Campaign ' . time(),
                'description' => 'This is a test campaign description',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'budget' => '10000',
                'status' => 'draft',
            ];

            $browser->loginAs($this->user)
                ->visit(new CampaignCreatePage)
                ->fillCampaignForm($browser, $campaignData)
                ->submitForm($browser)
                ->pause(2000)
                ->assertPathIs('/campaigns');
        });
    }

    /**
     * Test campaign name validation.
     */
    public function test_campaign_creation_validates_name(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignCreatePage)
                ->type('@description', 'Test Description')
                ->click('@submitButton')
                ->pause(1000)
                ->assertPathIs('/campaigns/create')
                ->assertSee('name');
        });
    }

    /**
     * Test campaign budget validation.
     */
    public function test_campaign_creation_validates_budget(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignCreatePage)
                ->type('@name', 'Test Campaign')
                ->type('@budget', 'invalid')
                ->click('@submitButton')
                ->pause(1000)
                ->assertPathIs('/campaigns/create');
        });
    }

    /**
     * Test date range validation.
     */
    public function test_campaign_validates_date_range(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignCreatePage)
                ->type('@name', 'Test Campaign')
                ->type('@startDate', now()->addDays(30)->format('Y-m-d'))
                ->type('@endDate', now()->format('Y-m-d'))
                ->click('@submitButton')
                ->pause(1000)
                ->assertPathIs('/campaigns/create');
        });
    }

    /**
     * Test that user can view a campaign.
     */
    public function test_user_can_view_campaign_details(): void
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->id,
            'name' => 'View Test Campaign',
        ]);

        $this->browse(function (Browser $browser) use ($campaign) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$campaign->id}")
                ->pause(1000)
                ->assertSee($campaign->name)
                ->assertSee($campaign->description);
        });
    }

    /**
     * Test that user can edit a campaign.
     */
    public function test_user_can_edit_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($campaign) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$campaign->id}/edit")
                ->pause(1000)
                ->assertInputValue('@name', $campaign->name)
                ->type('@name', 'Updated Campaign Name')
                ->click('@submitButton')
                ->pause(2000)
                ->assertPathIs("/campaigns/{$campaign->id}");
        });

        $this->assertDatabaseHas('cmis.campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign Name',
        ]);
    }

    /**
     * Test that user can delete a campaign.
     */
    public function test_user_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) use ($campaign) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$campaign->id}")
                ->pause(1000)
                ->press('Delete')
                ->pause(500)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->press('Confirm');
                })
                ->pause(2000)
                ->assertPathIs('/campaigns');
        });

        $this->assertSoftDeleted('cmis.campaigns', [
            'id' => $campaign->id,
        ]);
    }

    /**
     * Test campaign search functionality.
     */
    public function test_user_can_search_campaigns(): void
    {
        Campaign::factory()->create([
            'org_id' => $this->org->id,
            'name' => 'Searchable Campaign',
        ]);

        Campaign::factory()->create([
            'org_id' => $this->org->id,
            'name' => 'Another Campaign',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->searchCampaigns($browser, 'Searchable')
                ->pause(1000)
                ->assertSee('Searchable Campaign');
        });
    }

    /**
     * Test campaign status filter.
     */
    public function test_user_can_filter_campaigns_by_status(): void
    {
        Campaign::factory()->create([
            'org_id' => $this->org->id,
            'status' => 'active',
        ]);

        Campaign::factory()->create([
            'org_id' => $this->org->id,
            'status' => 'draft',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->filterByStatus($browser, 'active')
                ->pause(1000)
                ->assertPresent('@campaignRow');
        });
    }

    /**
     * Test campaign performance dashboard access.
     */
    public function test_user_can_access_performance_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->click('@performanceDashboard')
                ->pause(1000)
                ->assertPathIs('/campaigns/performance-dashboard');
        });
    }

    /**
     * Test campaign status change.
     */
    public function test_user_can_change_campaign_status(): void
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->id,
            'status' => 'draft',
        ]);

        $this->browse(function (Browser $browser) use ($campaign) {
            $browser->loginAs($this->user)
                ->visit("/campaigns/{$campaign->id}/edit")
                ->pause(1000)
                ->select('@status', 'active')
                ->click('@submitButton')
                ->pause(2000);
        });

        $this->assertDatabaseHas('cmis.campaigns', [
            'id' => $campaign->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test campaign bulk actions.
     */
    public function test_user_can_perform_bulk_actions(): void
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->pause(1000)
                ->check('input[type="checkbox"][name="select_all"]')
                ->pause(500)
                ->click('[data-test="bulk-actions"]')
                ->pause(500)
                ->assertPresent('[data-test="bulk-delete"]');
        });
    }

    /**
     * Test campaign sorting.
     */
    public function test_user_can_sort_campaigns(): void
    {
        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->pause(1000)
                ->click('[data-sort="name"]')
                ->pause(500)
                ->assertPresent('@campaignRow');
        });
    }

    /**
     * Test campaign pagination.
     */
    public function test_campaigns_pagination_works(): void
    {
        Campaign::factory()->count(25)->create([
            'org_id' => $this->org->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CampaignsIndexPage)
                ->pause(1000)
                ->assertPresent('nav[role="navigation"]')
                ->clickLink('2')
                ->pause(1000)
                ->assertQueryStringHas('page', '2');
        });
    }
}
