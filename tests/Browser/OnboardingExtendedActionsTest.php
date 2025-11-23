<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OnboardingExtendedActionsTest extends DuskTestCase
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
     * Test user can view onboarding progress.
     */
    public function test_user_can_view_onboarding_progress(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/progress')
                ->pause(2000)
                ->assertJson();
        });
    }

    /**
     * Test onboarding progress shows percentage.
     */
    public function test_onboarding_progress_shows_percentage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(2000)
                ->assertPresent('[data-test="progress-percentage"]')
                ->assertSee('%');
        });
    }

    /**
     * Test user can reset onboarding.
     */
    public function test_user_can_reset_onboarding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(2000)
                ->click('[data-test="reset-onboarding"]')
                ->pause(1000)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->press('Confirm Reset');
                })
                ->pause(2000)
                ->assertSee('reset')
                ->assertSee('0%');
        });
    }

    /**
     * Test user can dismiss onboarding.
     */
    public function test_user_can_dismiss_onboarding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(2000)
                ->click('[data-test="dismiss-onboarding"]')
                ->pause(2000)
                ->assertPathIs('/dashboard');
        });
    }

    /**
     * Test onboarding tips are accessible.
     */
    public function test_onboarding_tips_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/tips')
                ->pause(2000)
                ->assertPresent('[data-test="tips-list"]');
        });
    }

    /**
     * Test onboarding tips show helpful content.
     */
    public function test_onboarding_tips_show_content(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding')
                ->pause(2000)
                ->click('[data-test="show-tips"]')
                ->pause(1000)
                ->whenAvailable('[data-test="tips-modal"]', function ($modal) {
                    $modal->assertPresent('[data-test="tip-item"]');
                });
        });
    }

    /**
     * Test onboarding steps are sequential.
     */
    public function test_onboarding_steps_sequential(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(2000)
                ->assertSee('Step 1')
                ->press('Next')
                ->pause(2000)
                ->assertPathIs('/onboarding/step/2')
                ->assertSee('Step 2');
        });
    }

    /**
     * Test user cannot skip to future steps.
     */
    public function test_user_cannot_skip_to_future_steps(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/5')
                ->pause(2000)
                ->assertPathIs('/onboarding/step/1');
        });
    }

    /**
     * Test onboarding completion redirects to dashboard.
     */
    public function test_onboarding_completion_redirects(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(1000);

            // Complete all steps
            for ($i = 1; $i <= 5; $i++) {
                $browser->press('Complete Step')
                    ->pause(2000);
            }

            $browser->assertPathIs('/dashboard');
        });
    }

    /**
     * Test onboarding shows video tutorials.
     */
    public function test_onboarding_shows_video_tutorials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(2000)
                ->assertPresent('[data-test="tutorial-video"]');
        });
    }

    /**
     * Test onboarding allows feedback.
     */
    public function test_onboarding_allows_feedback(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(2000)
                ->click('[data-test="provide-feedback"]')
                ->pause(1000)
                ->assertPresent('textarea[name="feedback"]');
        });
    }

    /**
     * Test onboarding progress persists across sessions.
     */
    public function test_onboarding_progress_persists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/onboarding/step/1')
                ->pause(2000)
                ->press('Complete Step')
                ->pause(2000)
                ->visit('/dashboard')
                ->pause(1000)
                ->visit('/onboarding')
                ->pause(2000)
                ->assertPathIs('/onboarding/step/2');
        });
    }
}
