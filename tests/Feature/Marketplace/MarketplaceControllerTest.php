<?php

namespace Tests\Feature\Marketplace;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Marketplace\MarketplaceApp;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;
    protected MarketplaceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create(['org_id' => $this->org->org_id]);
        $this->service = app(MarketplaceService::class);
    }

    /** @test */
    public function it_displays_marketplace_index_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('orgs.marketplace.index', ['org' => $this->org->org_id]));

        $response->assertStatus(200);
        $response->assertViewIs('marketplace.index');
        $response->assertViewHas('categories');
        $response->assertViewHas('enabledSlugs');
        $response->assertViewHas('usageStats');
    }

    /** @test */
    public function it_can_enable_an_app()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.enable', [
                'org' => $this->org->org_id,
                'app' => $app->slug,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_disable_an_app()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // First enable the app
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.disable', [
                'org' => $this->org->org_id,
                'app' => $app->slug,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function it_returns_error_when_disabling_core_app()
    {
        $coreApp = MarketplaceApp::where('is_core', true)->first();

        if (!$coreApp) {
            $this->markTestSkipped('No core apps available');
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.disable', [
                'org' => $this->org->org_id,
                'app' => $coreApp->slug,
            ]));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function it_can_bulk_enable_apps()
    {
        $apps = MarketplaceApp::where('is_core', false)->take(2)->get();

        if ($apps->count() < 2) {
            $this->markTestSkipped('Not enough non-core apps available');
        }

        $slugs = $apps->pluck('slug')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.bulk-enable', ['org' => $this->org->org_id]), [
                'slugs' => $slugs,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.enabled', count($slugs));
    }

    /** @test */
    public function it_can_bulk_disable_apps()
    {
        $apps = MarketplaceApp::where('is_core', false)->take(2)->get();

        if ($apps->count() < 2) {
            $this->markTestSkipped('Not enough non-core apps available');
        }

        $slugs = $apps->pluck('slug')->toArray();

        // First enable them
        $this->service->bulkEnable($this->org->org_id, $slugs, $this->user->user_id);

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.bulk-disable', ['org' => $this->org->org_id]), [
                'slugs' => $slugs,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.disabled', count($slugs));
    }

    /** @test */
    public function it_validates_bulk_enable_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.bulk-enable', ['org' => $this->org->org_id]), [
                'slugs' => [],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_get_marketplace_status()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('orgs.marketplace.status', ['org' => $this->org->org_id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_apps',
                'enabled_count',
                'enabled_apps',
                'has_premium',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_app_details()
    {
        $app = MarketplaceApp::first();

        if (!$app) {
            $this->markTestSkipped('No apps available');
        }

        $response = $this->actingAs($this->user)
            ->getJson(route('orgs.marketplace.show', [
                'org' => $this->org->org_id,
                'app' => $app->slug,
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'app' => [
                    'slug',
                    'name',
                    'description',
                    'icon',
                    'category',
                    'is_core',
                    'is_premium',
                    'is_enabled',
                ],
                'dependencies',
                'dependents',
            ],
        ]);
    }

    /** @test */
    public function it_returns_404_for_unknown_app()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('orgs.marketplace.show', [
                'org' => $this->org->org_id,
                'app' => 'non-existent-app',
            ]));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_get_app_settings()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // Enable the app first
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        $response = $this->actingAs($this->user)
            ->getJson(route('orgs.marketplace.settings.get', [
                'org' => $this->org->org_id,
                'app' => $app->slug,
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'settings',
                'usage',
            ],
        ]);
    }

    /** @test */
    public function it_can_update_app_settings()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // Enable the app first
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        $settings = ['theme' => 'dark', 'limit' => 100];

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.settings.update', [
                'org' => $this->org->org_id,
                'app' => $app->slug,
            ]), [
                'settings' => $settings,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.settings', $settings);
    }

    /** @test */
    public function it_validates_settings_update_request()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('orgs.marketplace.settings.update', [
                'org' => $this->org->org_id,
                'app' => $app->slug,
            ]), []);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson(route('orgs.marketplace.index', ['org' => $this->org->org_id]));

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_valid_org_access()
    {
        $otherOrg = Org::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('orgs.marketplace.index', ['org' => $otherOrg->org_id]));

        // Should be redirected or forbidden depending on middleware
        $this->assertContains($response->status(), [302, 403]);
    }
}
