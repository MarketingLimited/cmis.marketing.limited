<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class KnowledgeBaseTest extends DuskTestCase
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
     * Test user can access knowledge base.
     */
    public function test_user_can_access_knowledge_base(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/knowledge')
                ->pause(1000)
                ->assertSee('Knowledge Base')
                ->assertPresent('[data-test="search-box"]');
        });
    }

    /**
     * Test knowledge base search.
     */
    public function test_user_can_search_knowledge_base(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/knowledge')
                ->pause(1000)
                ->type('input[name="query"]', 'campaign')
                ->press('Search')
                ->pause(2000)
                ->assertPresent('[data-test="search-results"]');
        });
    }

    /**
     * Test knowledge base domains.
     */
    public function test_user_can_view_knowledge_domains(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/knowledge/domains')
                ->pause(1000)
                ->assertPresent('[data-test="domains-list"]')
                ->assertSee('Domain');
        });
    }

    /**
     * Test knowledge base categories.
     */
    public function test_user_can_view_knowledge_categories(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/knowledge/domains')
                ->pause(1000)
                ->click('[data-test="view-domain"]')
                ->pause(1000)
                ->assertPresent('[data-test="categories-list"]');
        });
    }

    /**
     * Test user can create knowledge entry.
     */
    public function test_user_can_create_knowledge_entry(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/knowledge')
                ->pause(1000)
                ->click('[data-test="create-entry"]')
                ->pause(1000)
                ->type('input[name="title"]', 'Test Knowledge Entry')
                ->type('textarea[name="content"]', 'Test content for knowledge base')
                ->press('Create')
                ->pause(2000)
                ->assertSee('Entry created');
        });
    }

    /**
     * Test AI knowledge search.
     */
    public function test_ai_knowledge_search_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/knowledge')
                ->pause(1000)
                ->type('input[name="query"]', 'How to create a campaign?')
                ->check('input[name="ai_search"]')
                ->press('Search')
                ->pause(3000)
                ->assertPresent('[data-test="ai-results"]');
        });
    }
}
