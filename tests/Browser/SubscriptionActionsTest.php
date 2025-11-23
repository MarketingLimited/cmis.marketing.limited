<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubscriptionActionsTest extends DuskTestCase
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
     * Test user can view subscription plans.
     */
    public function test_user_can_view_subscription_plans(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/plans')
                ->pause(2000)
                ->assertSee('Plans')
                ->assertPresent('[data-test="plan-card"]');
        });
    }

    /**
     * Test subscription plans show features.
     */
    public function test_subscription_plans_show_features(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/plans')
                ->pause(2000)
                ->assertPresent('[data-test="plan-features"]')
                ->assertSee('feature');
        });
    }

    /**
     * Test subscription plans show pricing.
     */
    public function test_subscription_plans_show_pricing(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/plans')
                ->pause(2000)
                ->assertPresent('[data-test="plan-price"]')
                ->assertSee('$');
        });
    }

    /**
     * Test user can view current subscription status.
     */
    public function test_user_can_view_subscription_status(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->assertSee('Subscription Status')
                ->assertPresent('[data-test="current-plan"]');
        });
    }

    /**
     * Test subscription status shows billing details.
     */
    public function test_subscription_status_shows_billing_details(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->assertPresent('[data-test="billing-info"]');
        });
    }

    /**
     * Test user can navigate to upgrade page.
     */
    public function test_user_can_navigate_to_upgrade_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->click('[data-test="upgrade-plan"]')
                ->pause(1000)
                ->assertPathIs('/subscription/upgrade')
                ->assertSee('Upgrade');
        });
    }

    /**
     * Test upgrade page shows available plans.
     */
    public function test_upgrade_page_shows_plans(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/upgrade')
                ->pause(2000)
                ->assertPresent('[data-test="upgrade-options"]')
                ->assertPresent('[data-test="plan-card"]');
        });
    }

    /**
     * Test user can select plan for upgrade.
     */
    public function test_user_can_select_plan_for_upgrade(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/upgrade')
                ->pause(2000)
                ->click('[data-test="select-plan"]')
                ->pause(1000)
                ->assertPresent('[data-test="payment-form"]');
        });
    }

    /**
     * Test upgrade shows payment form.
     */
    public function test_upgrade_shows_payment_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/upgrade')
                ->pause(2000)
                ->click('[data-test="select-plan"]')
                ->pause(1000)
                ->assertPresent('input[name="card_number"]')
                ->assertPresent('input[name="expiry"]')
                ->assertPresent('input[name="cvv"]');
        });
    }

    /**
     * Test upgrade validates payment details.
     */
    public function test_upgrade_validates_payment_details(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/upgrade')
                ->pause(2000)
                ->click('[data-test="select-plan"]')
                ->pause(1000)
                ->press('Confirm Upgrade')
                ->pause(1000)
                ->assertSee('required');
        });
    }

    /**
     * Test user can cancel subscription.
     */
    public function test_user_can_cancel_subscription(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->click('[data-test="cancel-subscription"]')
                ->pause(1000)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->assertSee('Are you sure')
                        ->press('Confirm Cancellation');
                })
                ->pause(3000)
                ->assertSee('cancelled');
        });
    }

    /**
     * Test cancellation shows confirmation modal.
     */
    public function test_cancellation_shows_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->click('[data-test="cancel-subscription"]')
                ->pause(1000)
                ->assertPresent('.modal')
                ->assertSee('confirm');
        });
    }

    /**
     * Test user can view billing history.
     */
    public function test_user_can_view_billing_history(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->click('[data-test="billing-history"]')
                ->pause(1000)
                ->assertPresent('[data-test="invoice-list"]');
        });
    }

    /**
     * Test subscription shows renewal date.
     */
    public function test_subscription_shows_renewal_date(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/status')
                ->pause(2000)
                ->assertPresent('[data-test="renewal-date"]');
        });
    }

    /**
     * Test plan comparison feature.
     */
    public function test_plan_comparison_feature(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/subscription/plans')
                ->pause(2000)
                ->click('[data-test="compare-plans"]')
                ->pause(1000)
                ->assertPresent('[data-test="comparison-table"]');
        });
    }
}
