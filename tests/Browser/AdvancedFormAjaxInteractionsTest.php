<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdvancedFormAjaxInteractionsTest extends DuskTestCase
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
     * Test form submission with AJAX.
     */
    public function test_form_submission_via_ajax(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->type('input[name="name"]', 'AJAX Test Campaign')
                ->press('Save as Draft')
                ->pause(2000)
                ->assertPresent('[data-test="success-message"]')
                ->assertPathIs('/campaigns/create'); // Stays on same page
        });
    }

    /**
     * Test real-time form validation.
     */
    public function test_realtime_form_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->type('input[name="name"]', 'a') // Too short
                ->pause(500)
                ->assertPresent('.error-message')
                ->type('input[name="name"]', 'Valid Campaign Name')
                ->pause(500)
                ->assertMissing('.error-message');
        });
    }

    /**
     * Test autocomplete functionality.
     */
    public function test_autocomplete_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->type('input[name="template"]', 'sum')
                ->pause(1000)
                ->assertPresent('[data-test="autocomplete-dropdown"]')
                ->assertSee('Summer');
        });
    }

    /**
     * Test dependent dropdowns.
     */
    public function test_dependent_dropdowns(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->select('select[name="country"]', 'US')
                ->pause(1000)
                ->assertPresent('select[name="state"] option')
                ->assertSee('California');
        });
    }

    /**
     * Test dynamic form fields.
     */
    public function test_dynamic_form_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->click('[data-test="add-audience"]')
                ->pause(1000)
                ->assertPresent('[data-test="audience-field-2"]')
                ->click('[data-test="remove-audience"]')
                ->pause(1000)
                ->assertMissing('[data-test="audience-field-2"]');
        });
    }

    /**
     * Test file upload with progress.
     */
    public function test_file_upload_with_progress(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/creative-assets')
                ->pause(1000)
                ->attach('input[type="file"]', __DIR__ . '/fixtures/test-image.jpg')
                ->pause(1000)
                ->assertPresent('[data-test="upload-progress"]')
                ->pause(3000)
                ->assertMissing('[data-test="upload-progress"]')
                ->assertSee('uploaded');
        });
    }

    /**
     * Test infinite scroll pagination.
     */
    public function test_infinite_scroll_pagination(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(2000)
                ->script('window.scrollTo(0, document.body.scrollHeight)');

            $browser->pause(2000)
                ->assertPresent('[data-test="loading-more"]')
                ->pause(2000)
                ->assertMissing('[data-test="loading-more"]');
        });
    }

    /**
     * Test debounced search.
     */
    public function test_debounced_search(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(1000)
                ->type('input[name="search"]', 'test')
                ->pause(300) // Less than debounce time
                ->assertMissing('[data-test="search-results"]')
                ->pause(700) // Wait for debounce
                ->assertPresent('[data-test="search-results"]');
        });
    }

    /**
     * Test modal form submission.
     */
    public function test_modal_form_submission(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(1000)
                ->click('[data-test="quick-create"]')
                ->pause(1000)
                ->whenAvailable('.modal', function ($modal) {
                    $modal->type('input[name="name"]', 'Quick Campaign')
                        ->press('Create')
                        ->pause(2000);
                })
                ->assertMissing('.modal')
                ->assertSee('created');
        });
    }

    /**
     * Test inline editing.
     */
    public function test_inline_editing(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(2000)
                ->click('[data-test="edit-inline"]')
                ->pause(500)
                ->assertPresent('input[data-field="name"]')
                ->type('input[data-field="name"]', 'Updated Name')
                ->keys('input[data-field="name"]', '{enter}')
                ->pause(2000)
                ->assertSee('Updated Name');
        });
    }

    /**
     * Test drag and drop functionality.
     */
    public function test_drag_and_drop(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/workflows')
                ->pause(2000)
                ->drag('[data-test="step-1"]', '[data-test="drop-zone"]')
                ->pause(1000)
                ->assertPresent('[data-test="dropped-item"]');
        });
    }

    /**
     * Test sortable lists.
     */
    public function test_sortable_lists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(2000)
                ->click('[data-sort="name"]')
                ->pause(1000)
                ->assertPresent('[data-order="asc"]')
                ->click('[data-sort="name"]')
                ->pause(1000)
                ->assertPresent('[data-order="desc"]');
        });
    }

    /**
     * Test AJAX tab loading.
     */
    public function test_ajax_tab_loading(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/analytics/enterprise')
                ->pause(2000)
                ->click('[data-tab="campaigns"]')
                ->pause(500)
                ->assertPresent('[data-test="tab-loading"]')
                ->pause(2000)
                ->assertMissing('[data-test="tab-loading"]')
                ->assertPresent('[data-test="campaigns-content"]');
        });
    }

    /**
     * Test form autosave.
     */
    public function test_form_autosave(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Autosave Test')
                ->pause(5000) // Wait for autosave
                ->assertPresent('[data-test="autosaved"]')
                ->assertSee('Draft saved');
        });
    }

    /**
     * Test copy to clipboard.
     */
    public function test_copy_to_clipboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(2000)
                ->click('[data-test="copy-link"]')
                ->pause(1000)
                ->assertSee('Copied');
        });
    }

    /**
     * Test keyboard shortcuts.
     */
    public function test_keyboard_shortcuts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns')
                ->pause(2000)
                ->keys('body', '{control}', 'n') // Ctrl+N to create
                ->pause(1000)
                ->assertPathIs('/campaigns/create');
        });
    }

    /**
     * Test tooltips on hover.
     */
    public function test_tooltips_on_hover(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000)
                ->mouseover('[data-tooltip="info"]')
                ->pause(500)
                ->assertPresent('.tooltip')
                ->assertSee('information');
        });
    }

    /**
     * Test conditional form sections.
     */
    public function test_conditional_form_sections(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->select('select[name="objective"]', 'conversions')
                ->pause(1000)
                ->assertPresent('[data-test="conversion-fields"]')
                ->select('select[name="objective"]', 'awareness')
                ->pause(1000)
                ->assertMissing('[data-test="conversion-fields"]');
        });
    }

    /**
     * Test multi-step form with validation.
     */
    public function test_multistep_form_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/wizard/create')
                ->pause(1000)
                ->press('Next')
                ->pause(1000)
                ->assertSee('required')
                ->type('input[name="name"]', 'Valid Name')
                ->press('Next')
                ->pause(1000)
                ->assertPathBeginsWith('/campaigns/wizard');
        });
    }

    /**
     * Test AJAX error handling.
     */
    public function test_ajax_error_handling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->script('
                    const original = window.fetch;
                    window.fetch = function(...args) {
                        return Promise.reject(new Error("Network Error"));
                    };
                ')
                ->press('Save')
                ->pause(2000)
                ->assertPresent('[data-test="error-alert"]')
                ->assertSee('error');
        });
    }

    /**
     * Test form dirty state detection.
     */
    public function test_form_dirty_state_detection(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/campaigns/create')
                ->pause(1000)
                ->type('input[name="name"]', 'Test')
                ->clickLink('Dashboard')
                ->pause(500)
                ->assertDialogOpened('You have unsaved changes')
                ->acceptDialog();
        });
    }
}
