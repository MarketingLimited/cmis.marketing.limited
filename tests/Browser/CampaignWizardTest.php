<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CampaignWizardTest extends DuskTestCase
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
     * Test user can access campaign wizard.
     */
    public function test_user_can_access_campaign_wizard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->assertSee('Campaign Wizard')
                ->assertPresent('form');
        });
    }

    /**
     * Test wizard step 1 - Basic information.
     */
    public function test_wizard_step1_basic_information(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->assertSee('Step 1')
                ->type('input[name="name"]', 'Test Campaign Wizard')
                ->type('textarea[name="description"]', 'Test Description')
                ->press('Next')
                ->pause(1000)
                ->assertSee('Step 2');
        });
    }

    /**
     * Test wizard step 2 - Targeting.
     */
    public function test_wizard_step2_targeting(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Test Campaign')
                ->press('Next')
                ->pause(1000)
                ->assertSee('Step 2')
                ->assertPresent('[name="audience"]')
                ->press('Next')
                ->pause(1000)
                ->assertSee('Step 3');
        });
    }

    /**
     * Test wizard step 3 - Budget and schedule.
     */
    public function test_wizard_step3_budget_schedule(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Test Campaign')
                ->press('Next')
                ->pause(1000)
                ->press('Next')
                ->pause(1000)
                ->assertSee('Step 3')
                ->type('input[name="budget"]', '10000')
                ->type('input[name="start_date"]', now()->format('Y-m-d'))
                ->press('Next')
                ->pause(1000)
                ->assertSee('Step 4');
        });
    }

    /**
     * Test wizard step 4 - Review and confirm.
     */
    public function test_wizard_step4_review_confirm(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Test Campaign Final')
                ->press('Next')
                ->pause(1000)
                ->press('Next')
                ->pause(1000)
                ->type('input[name="budget"]', '10000')
                ->press('Next')
                ->pause(1000)
                ->assertSee('Review')
                ->assertSee('Test Campaign Final');
        });
    }

    /**
     * Test wizard navigation - back button.
     */
    public function test_wizard_back_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Test Campaign')
                ->press('Next')
                ->pause(1000)
                ->assertSee('Step 2')
                ->press('Back')
                ->pause(1000)
                ->assertSee('Step 1')
                ->assertInputValue('input[name="name"]', 'Test Campaign');
        });
    }

    /**
     * Test wizard save as draft.
     */
    public function test_wizard_save_as_draft(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Draft Campaign')
                ->press('Save Draft')
                ->pause(2000)
                ->assertSee('Draft saved');
        });
    }

    /**
     * Test wizard cancel action.
     */
    public function test_wizard_cancel_action(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Cancel Test')
                ->press('Cancel')
                ->pause(500)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->press('Confirm');
                })
                ->pause(1000)
                ->assertPathIs('/campaigns');
        });
    }

    /**
     * Test wizard validation on each step.
     */
    public function test_wizard_validates_each_step(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->press('Next')
                ->pause(1000)
                ->assertSee('name')
                ->assertSee('required');
        });
    }

    /**
     * Test wizard complete flow.
     */
    public function test_wizard_complete_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $campaignName = 'Complete Wizard Test ' . time();

            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                // Step 1
                ->type('input[name="name"]', $campaignName)
                ->type('textarea[name="description"]', 'Full wizard test')
                ->press('Next')
                ->pause(1000)
                // Step 2
                ->press('Next')
                ->pause(1000)
                // Step 3
                ->type('input[name="budget"]', '15000')
                ->type('input[name="start_date"]', now()->format('Y-m-d'))
                ->press('Next')
                ->pause(1000)
                // Step 4 - Review and submit
                ->press('Create Campaign')
                ->pause(3000)
                ->assertPathIs('/campaigns');
        });

        $this->assertDatabaseHas('cmis.campaigns', [
            'name' => $campaignName,
        ]);
    }

    /**
     * Test wizard progress indicator.
     */
    public function test_wizard_progress_indicator(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->assertPresent('[data-test="progress-indicator"]')
                ->assertSee('1 of 4');
        });
    }

    /**
     * Test wizard session persistence.
     */
    public function test_wizard_session_persistence(): void
    {
        $this->browse(function (Browser $browser) {
            $sessionId = null;

            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Persistent Test')
                ->press('Next')
                ->pause(1000)
                ->with('body', function ($body) use (&$sessionId) {
                    // Extract session ID from URL or data attribute
                    $sessionId = time(); // Simplified for test
                })
                ->visit('/campaigns')
                ->pause(500)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->assertPresent('[data-test="resume-draft"]');
        });
    }
}
