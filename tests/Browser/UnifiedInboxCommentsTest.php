<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UnifiedInboxCommentsTest extends DuskTestCase
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
     * Test user can access unified inbox.
     */
    public function test_user_can_access_unified_inbox(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(1000)
                ->assertSee('Inbox')
                ->assertPresent('[data-test="inbox-items"]');
        });
    }

    /**
     * Test unified inbox displays messages.
     */
    public function test_inbox_displays_messages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(2000)
                ->assertPresent('[data-test="inbox-list"]');
        });
    }

    /**
     * Test user can filter inbox messages.
     */
    public function test_user_can_filter_inbox_messages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(1000)
                ->select('[name="filter"]', 'unread')
                ->pause(1000)
                ->assertPresent('[data-test="inbox-items"]');
        });
    }

    /**
     * Test user can mark message as read.
     */
    public function test_user_can_mark_message_as_read(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(1000)
                ->click('[data-test="mark-read"]')
                ->pause(2000)
                ->assertSee('marked as read');
        });
    }

    /**
     * Test user can access comments section.
     */
    public function test_user_can_access_comments_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->assertSee('Comments')
                ->assertPresent('[data-test="comments-list"]');
        });
    }

    /**
     * Test comments list displays all comments.
     */
    public function test_comments_list_displays_comments(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(2000)
                ->assertPresent('[data-test="comment-item"]');
        });
    }

    /**
     * Test user can reply to a comment.
     */
    public function test_user_can_reply_to_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->click('[data-test="reply-button"]')
                ->pause(500)
                ->whenAvailable('[data-test="reply-form"]', function ($form) {
                    $form->type('textarea[name="reply"]', 'This is a test reply')
                        ->press('Send Reply');
                })
                ->pause(2000)
                ->assertSee('Reply sent');
        });
    }

    /**
     * Test reply validation.
     */
    public function test_reply_validates_empty_content(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->click('[data-test="reply-button"]')
                ->pause(500)
                ->whenAvailable('[data-test="reply-form"]', function ($form) {
                    $form->press('Send Reply');
                })
                ->pause(1000)
                ->assertSee('required');
        });
    }

    /**
     * Test user can filter comments by platform.
     */
    public function test_user_can_filter_comments_by_platform(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->select('[name="platform"]', 'facebook')
                ->pause(1000)
                ->assertPresent('[data-test="comments-list"]');
        });
    }

    /**
     * Test user can filter comments by status.
     */
    public function test_user_can_filter_comments_by_status(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->select('[name="status"]', 'pending')
                ->pause(1000)
                ->assertPresent('[data-test="comments-list"]');
        });
    }

    /**
     * Test user can search comments.
     */
    public function test_user_can_search_comments(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->type('input[name="search"]', 'test comment')
                ->pause(1000)
                ->assertPresent('[data-test="comments-list"]');
        });
    }

    /**
     * Test comment pagination.
     */
    public function test_comments_pagination_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->assertPresent('nav[role="navigation"]');
        });
    }

    /**
     * Test user can view comment details.
     */
    public function test_user_can_view_comment_details(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox/comments')
                ->pause(1000)
                ->click('[data-test="view-comment"]')
                ->pause(1000)
                ->assertPresent('[data-test="comment-details"]');
        });
    }

    /**
     * Test inbox shows unread count.
     */
    public function test_inbox_shows_unread_count(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(1000)
                ->assertPresent('[data-test="unread-count"]');
        });
    }

    /**
     * Test user can bulk mark as read.
     */
    public function test_user_can_bulk_mark_as_read(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/inbox')
                ->pause(1000)
                ->check('input[name="select_all"]')
                ->pause(500)
                ->click('[data-test="bulk-mark-read"]')
                ->pause(2000)
                ->assertSee('marked as read');
        });
    }
}
