<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OnboardingWorkflowTest extends DuskTestCase
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
     * Test user can access onboarding.
     */
    public function test_user_can_access_onboarding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(1000)
                ->assertSee('Welcome')
                ->assertPresent('[data-test="onboarding-step"]');
        });
    }

    /**
     * Test onboarding progress tracking.
     */
    public function test_onboarding_tracks_progress(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(1000)
                ->assertPresent('[data-test="progress-bar"]')
                ->assertSee('0%');
        });
    }

    /**
     * Test user can complete onboarding step.
     */
    public function test_user_can_complete_onboarding_step(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(1000)
                ->press('Complete Step')
                ->pause(2000)
                ->assertSee('Step completed');
        });
    }

    /**
     * Test user can skip onboarding step.
     */
    public function test_user_can_skip_onboarding_step(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(1000)
                ->press('Skip')
                ->pause(1000)
                ->assertPathBeginsWith('/onboarding/step');
        });
    }

    /**
     * Test user can view onboarding tips.
     */
    public function test_user_can_view_onboarding_tips(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(1000)
                ->click('[data-test="view-tips"]')
                ->pause(1000)
                ->assertPresent('[data-test="tips-modal"]');
        });
    }

    /**
     * Test workflows index access.
     */
    public function test_user_can_access_workflows(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/workflows')
                ->pause(1000)
                ->assertSee('Workflows')
                ->assertPresent('[data-test="workflows-list"]');
        });
    }

    /**
     * Test workflow initialization.
     */
    public function test_user_can_initialize_workflow(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/workflows')
                ->pause(1000)
                ->click('[data-test="initialize-campaign-workflow"]')
                ->pause(2000)
                ->assertSee('Workflow initialized');
        });
    }

    /**
     * Test workflow step completion.
     */
    public function test_user_can_complete_workflow_step(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/workflows')
                ->pause(1000)
                ->click('[data-test="view-workflow"]')
                ->pause(1000)
                ->press('Complete Step')
                ->pause(2000)
                ->assertSee('Step completed');
        });
    }

    /**
     * Test workflow step assignment.
     */
    public function test_user_can_assign_workflow_step(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/workflows')
                ->pause(1000)
                ->click('[data-test="view-workflow"]')
                ->pause(1000)
                ->click('[data-test="assign-step"]')
                ->pause(500)
                ->select('select[name="assignee"]', $this->user->id)
                ->press('Assign')
                ->pause(2000)
                ->assertSee('Step assigned');
        });
    }

    /**
     * Test workflow comments.
     */
    public function test_user_can_add_workflow_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/workflows')
                ->pause(1000)
                ->click('[data-test="view-workflow"]')
                ->pause(1000)
                ->type('textarea[name="comment"]', 'Test workflow comment')
                ->press('Add Comment')
                ->pause(2000)
                ->assertSee('Comment added');
        });
    }
}
