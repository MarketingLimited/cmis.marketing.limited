<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\SocialMediaPage;
use Tests\DuskTestCase;

class SocialMediaTest extends DuskTestCase
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
     * Test user can access social media index.
     */
    public function test_user_can_access_social_media_index(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SocialMediaPage)
                ->assertSee('Social')
                ->assertPresent('@postsTab');
        });
    }

    /**
     * Test user can view social posts.
     */
    public function test_user_can_view_social_posts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->assertSee('Posts')
                ->assertPresent('@createPostButton');
        });
    }

    /**
     * Test user can navigate to scheduler.
     */
    public function test_user_can_access_scheduler(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SocialMediaPage)
                ->navigateToTab($browser, 'scheduler')
                ->pause(1000)
                ->assertPathIs('/social/scheduler')
                ->assertSee('Scheduler');
        });
    }

    /**
     * Test user can access social inbox.
     */
    public function test_user_can_access_inbox(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(new SocialMediaPage)
                ->navigateToTab($browser, 'inbox')
                ->pause(1000)
                ->assertPathIs('/social/inbox')
                ->assertSee('Inbox');
        });
    }

    /**
     * Test user can create a new post.
     */
    public function test_user_can_create_social_post(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->click('@createPostButton')
                ->pause(1000)
                ->assertPresent('form')
                ->type('textarea[name="content"]', 'Test social media post')
                ->press('Publish')
                ->pause(2000)
                ->assertSee('Post created');
        });
    }

    /**
     * Test user can schedule a post.
     */
    public function test_user_can_schedule_post(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/scheduler')
                ->pause(1000)
                ->click('[data-test="new-scheduled-post"]')
                ->pause(1000)
                ->type('textarea[name="content"]', 'Scheduled post content')
                ->type('input[name="scheduled_at"]', now()->addHours(2)->format('Y-m-d H:i'))
                ->click('@scheduleButton')
                ->pause(2000)
                ->assertSee('Post scheduled');
        });
    }

    /**
     * Test user can view post calendar.
     */
    public function test_user_can_view_post_calendar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/scheduler')
                ->pause(1000)
                ->assertPresent('[data-test="calendar-view"]')
                ->click('[data-view="calendar"]')
                ->pause(1000)
                ->assertPresent('.calendar');
        });
    }

    /**
     * Test user can filter posts by platform.
     */
    public function test_user_can_filter_posts_by_platform(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->select('[name="platform"]', 'facebook')
                ->pause(1000)
                ->assertPresent('@postsList');
        });
    }

    /**
     * Test user can view post analytics.
     */
    public function test_user_can_view_post_analytics(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->click('[data-test="post-analytics"]')
                ->pause(1000)
                ->assertPresent('[data-test="engagement-metrics"]')
                ->assertSee('Likes')
                ->assertSee('Comments')
                ->assertSee('Shares');
        });
    }

    /**
     * Test user can reply to comments.
     */
    public function test_user_can_reply_to_comments(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->assertPresent('[data-test="comment-item"]')
                ->click('[data-test="reply-button"]')
                ->pause(500)
                ->type('textarea[name="reply"]', 'Thank you for your comment!')
                ->press('Send Reply')
                ->pause(2000)
                ->assertSee('Reply sent');
        });
    }

    /**
     * Test user can delete a post.
     */
    public function test_user_can_delete_post(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->click('[data-test="delete-post"]')
                ->pause(500)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->press('Confirm');
                })
                ->pause(2000)
                ->assertSee('Post deleted');
        });
    }

    /**
     * Test user can edit scheduled post.
     */
    public function test_user_can_edit_scheduled_post(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/scheduler')
                ->pause(1000)
                ->click('[data-test="edit-scheduled-post"]')
                ->pause(1000)
                ->type('textarea[name="content"]', 'Updated scheduled content')
                ->press('Update')
                ->pause(2000)
                ->assertSee('Post updated');
        });
    }

    /**
     * Test post character counter.
     */
    public function test_post_character_counter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->click('@createPostButton')
                ->pause(1000)
                ->type('textarea[name="content"]', 'Test content')
                ->pause(500)
                ->assertPresent('[data-test="character-count"]');
        });
    }

    /**
     * Test bulk post actions.
     */
    public function test_bulk_post_actions(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/social/posts')
                ->pause(1000)
                ->check('input[type="checkbox"][name="select_all"]')
                ->pause(500)
                ->click('[data-test="bulk-actions"]')
                ->pause(500)
                ->assertPresent('[data-test="bulk-delete"]');
        });
    }
}
