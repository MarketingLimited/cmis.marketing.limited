<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardAjaxFeaturesTest extends DuskTestCase
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
     * Test dashboard loads data via AJAX.
     */
    public function test_dashboard_loads_data_via_ajax(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->assertPresent('[data-test="dashboard-loading"]')
                ->pause(2000)
                ->assertMissing('[data-test="dashboard-loading"]')
                ->assertPresent('[data-test="dashboard-data"]');
        });
    }

    /**
     * Test dashboard data endpoint returns JSON.
     */
    public function test_dashboard_data_endpoint_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000)
                ->script('
                    fetch("/dashboard/data")
                        .then(r => r.json())
                        .then(data => {
                            window.dashboardData = data;
                        });
                ');

            $browser->pause(2000)
                ->assertScript('return window.dashboardData !== undefined', true);
        });
    }

    /**
     * Test notifications can be fetched.
     */
    public function test_notifications_can_be_fetched(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="notifications-toggle"]')
                ->pause(2000)
                ->assertPresent('[data-test="notifications-dropdown"]');
        });
    }

    /**
     * Test latest notifications endpoint.
     */
    public function test_latest_notifications_endpoint_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->script('
                    fetch("/notifications/latest")
                        .then(r => r.json())
                        .then(data => {
                            window.notifications = data;
                        });
                ');

            $browser->pause(2000)
                ->assertScript('return window.notifications !== undefined', true);
        });
    }

    /**
     * Test user can mark notification as read.
     */
    public function test_user_can_mark_notification_as_read(): void
    {
        // Create a notification for the user
        \DB::table('cmis_core.notifications')->insert([
            'id' => \Str::uuid(),
            'user_id' => $this->user->id,
            'type' => 'info',
            'title' => 'Test Notification',
            'message' => 'This is a test',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="notifications-toggle"]')
                ->pause(1000)
                ->click('[data-test="mark-notification-read"]')
                ->pause(2000)
                ->assertMissing('.notification.unread');
        });
    }

    /**
     * Test dashboard auto-refreshes data.
     */
    public function test_dashboard_auto_refreshes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->script('window.refreshCount = 0;')
                ->script('
                    const original = window.fetch;
                    window.fetch = function(...args) {
                        if (args[0].includes("/dashboard/data")) {
                            window.refreshCount++;
                        }
                        return original.apply(this, args);
                    };
                ')
                ->pause(65000) // Wait for auto-refresh (typically 60s)
                ->assertScript('return window.refreshCount > 0', true);
        });
    }

    /**
     * Test dashboard shows loading skeleton.
     */
    public function test_dashboard_shows_loading_skeleton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->assertPresent('[data-test="skeleton-loader"]')
                ->pause(3000)
                ->assertMissing('[data-test="skeleton-loader"]');
        });
    }

    /**
     * Test dashboard handles API errors gracefully.
     */
    public function test_dashboard_handles_api_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->script('
                    const original = window.fetch;
                    window.fetch = function(...args) {
                        if (args[0].includes("/dashboard/data")) {
                            return Promise.reject(new Error("API Error"));
                        }
                        return original.apply(this, args);
                    };
                ')
                ->pause(2000)
                ->assertPresent('[data-test="error-message"]');
        });
    }

    /**
     * Test real-time notification updates.
     */
    public function test_realtime_notification_updates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->assertPresent('[data-test="notification-badge"]');
        });
    }

    /**
     * Test dashboard widget can be refreshed individually.
     */
    public function test_dashboard_widget_individual_refresh(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000)
                ->click('[data-test="refresh-widget"]')
                ->pause(1000)
                ->assertPresent('[data-test="widget-loading"]')
                ->pause(2000)
                ->assertMissing('[data-test="widget-loading"]');
        });
    }

    /**
     * Test dashboard filters update data without page reload.
     */
    public function test_dashboard_filters_work_via_ajax(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000)
                ->select('[name="date_range"]', 'last_7_days')
                ->pause(2000)
                ->assertPresent('[data-test="dashboard-data"]');
        });
    }

    /**
     * Test notification dropdown shows recent notifications.
     */
    public function test_notification_dropdown_shows_recent(): void
    {
        // Create multiple notifications
        for ($i = 0; $i < 5; $i++) {
            \DB::table('cmis_core.notifications')->insert([
                'id' => \Str::uuid(),
                'user_id' => $this->user->id,
                'type' => 'info',
                'title' => "Notification {$i}",
                'message' => 'Test message',
                'read_at' => null,
                'created_at' => now()->subMinutes($i),
                'updated_at' => now()->subMinutes($i),
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="notifications-toggle"]')
                ->pause(1000)
                ->assertPresent('[data-test="notification-item"]');
        });
    }

    /**
     * Test mark all notifications as read.
     */
    public function test_mark_all_notifications_as_read(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[data-test="notifications-toggle"]')
                ->pause(1000)
                ->click('[data-test="mark-all-read"]')
                ->pause(2000)
                ->assertMissing('.notification.unread');
        });
    }

    /**
     * Test notification count badge updates.
     */
    public function test_notification_count_badge_updates(): void
    {
        \DB::table('cmis_core.notifications')->insert([
            'id' => \Str::uuid(),
            'user_id' => $this->user->id,
            'type' => 'info',
            'title' => 'Unread Notification',
            'message' => 'Test',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000)
                ->assertPresent('[data-test="notification-badge"]')
                ->assertSeeIn('[data-test="notification-count"]', '1');
        });
    }
}
