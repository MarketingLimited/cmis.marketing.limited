<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AIFeaturesTest extends DuskTestCase
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
     * Test user can access AI dashboard.
     */
    public function test_user_can_access_ai_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai')
                ->pause(1000)
                ->assertSee('AI')
                ->assertPresent('[data-test="ai-features"]');
        });
    }

    /**
     * Test AI campaigns view.
     */
    public function test_user_can_view_ai_campaigns(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai/campaigns')
                ->pause(1000)
                ->assertSee('AI Campaigns')
                ->assertPresent('[data-test="ai-campaign-list"]');
        });
    }

    /**
     * Test AI recommendations.
     */
    public function test_user_can_view_ai_recommendations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai/recommendations')
                ->pause(1000)
                ->assertSee('Recommendations')
                ->assertPresent('[data-test="recommendations-list"]');
        });
    }

    /**
     * Test AI models view.
     */
    public function test_user_can_view_ai_models(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai/models')
                ->pause(1000)
                ->assertSee('Models')
                ->assertPresent('[data-test="models-list"]');
        });
    }

    /**
     * Test AI quota widget display.
     */
    public function test_ai_quota_widget_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai')
                ->pause(1000)
                ->assertPresent('[data-test="ai-quota-widget"]')
                ->assertSee('Usage');
        });
    }

    /**
     * Test AI content generation.
     */
    public function test_user_can_generate_ai_content(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai')
                ->pause(1000)
                ->click('[data-test="generate-content"]')
                ->pause(1000)
                ->type('textarea[name="prompt"]', 'Create a campaign description for a new product')
                ->press('Generate')
                ->pause(3000)
                ->assertPresent('[data-test="generated-content"]');
        });
    }

    /**
     * Test semantic search functionality.
     */
    public function test_semantic_search_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/ai')
                ->pause(1000)
                ->type('input[name="semantic_query"]', 'campaigns similar to summer sales')
                ->press('Search')
                ->pause(3000)
                ->assertPresent('[data-test="semantic-results"]');
        });
    }
}
