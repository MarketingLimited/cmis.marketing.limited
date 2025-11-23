<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\CreativePage;
use Tests\DuskTestCase;

class CreativeManagementTest extends DuskTestCase
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
     * Test user can access creative index.
     */
    public function test_user_can_access_creative_index(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CreativePage)
                ->assertSee('Creative')
                ->assertPresent('@assetsTab');
        });
    }

    /**
     * Test user can view creative assets.
     */
    public function test_user_can_view_creative_assets(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->assertSee('Assets')
                ->assertPresent('@uploadButton');
        });
    }

    /**
     * Test user can upload an asset.
     */
    public function test_user_can_upload_asset(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->click('@uploadButton')
                ->pause(1000)
                ->assertPresent('input[type="file"]')
                ->attach('input[type="file"]', __DIR__ . '/fixtures/test-image.jpg')
                ->pause(1000)
                ->press('Upload')
                ->pause(3000)
                ->assertSee('Asset uploaded');
        });
    }

    /**
     * Test user can view creative templates.
     */
    public function test_user_can_view_templates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CreativePage)
                ->navigateToTab($browser, 'templates')
                ->pause(1000)
                ->assertPathIs('/creative/templates')
                ->assertSee('Templates');
        });
    }

    /**
     * Test user can view ads.
     */
    public function test_user_can_view_ads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new CreativePage)
                ->navigateToTab($browser, 'ads')
                ->pause(1000)
                ->assertPathIs('/creative/ads')
                ->assertSee('Ads');
        });
    }

    /**
     * Test user can create creative brief.
     */
    public function test_user_can_create_creative_brief(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/briefs')
                ->pause(1000)
                ->click('[data-test="create-brief"]')
                ->pause(1000)
                ->type('input[name="title"]', 'Test Creative Brief')
                ->type('textarea[name="description"]', 'Brief description')
                ->press('Create')
                ->pause(2000)
                ->assertPathBeginsWith('/briefs');
        });
    }

    /**
     * Test user can view brief details.
     */
    public function test_user_can_view_brief_details(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/briefs')
                ->pause(1000)
                ->click('[data-test="view-brief"]')
                ->pause(1000)
                ->assertPresent('[data-test="brief-details"]')
                ->assertSee('Title')
                ->assertSee('Description');
        });
    }

    /**
     * Test user can approve brief.
     */
    public function test_user_can_approve_brief(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/briefs')
                ->pause(1000)
                ->click('[data-test="view-brief"]')
                ->pause(1000)
                ->press('Approve')
                ->pause(2000)
                ->assertSee('Brief approved');
        });
    }

    /**
     * Test asset filtering by type.
     */
    public function test_asset_filtering_by_type(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->select('[name="asset_type"]', 'image')
                ->pause(1000)
                ->assertPresent('@assetsList');
        });
    }

    /**
     * Test asset search functionality.
     */
    public function test_asset_search_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->type('input[name="search"]', 'test')
                ->pause(1000)
                ->assertPresent('@assetsList');
        });
    }

    /**
     * Test asset preview.
     */
    public function test_asset_preview(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->click('[data-test="asset-item"]')
                ->pause(1000)
                ->assertPresent('[data-test="asset-preview"]')
                ->assertPresent('img, video');
        });
    }

    /**
     * Test asset deletion.
     */
    public function test_asset_deletion(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->click('[data-test="delete-asset"]')
                ->pause(500)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->press('Confirm');
                })
                ->pause(2000)
                ->assertSee('Asset deleted');
        });
    }

    /**
     * Test asset grid and list view toggle.
     */
    public function test_asset_view_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->click('[data-view="grid"]')
                ->pause(500)
                ->assertPresent('.grid-view')
                ->click('[data-view="list"]')
                ->pause(500)
                ->assertPresent('.list-view');
        });
    }
}
