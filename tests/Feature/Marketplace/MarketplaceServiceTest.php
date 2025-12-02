<?php

namespace Tests\Feature\Marketplace;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Marketplace\AppCategory;
use App\Models\Marketplace\MarketplaceApp;
use App\Models\Marketplace\OrganizationApp;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MarketplaceService $service;
    protected Org $org;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MarketplaceService::class);

        // Create test organization and user
        $this->org = Org::factory()->create();
        $this->user = User::factory()->create(['org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_get_available_apps()
    {
        $apps = $this->service->getAvailableApps();

        $this->assertNotEmpty($apps);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $apps);
    }

    /** @test */
    public function it_can_get_categories_with_apps()
    {
        $categories = $this->service->getCategoriesWithApps();

        $this->assertNotEmpty($categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf(AppCategory::class, $category);
            $this->assertTrue($category->relationLoaded('apps'));
        }
    }

    /** @test */
    public function it_can_enable_an_app()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        $result = $this->service->enableApp(
            $this->org->org_id,
            $app->slug,
            $this->user->user_id
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($this->service->isAppEnabled($this->org->org_id, $app->slug));
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

        // Then disable it
        $result = $this->service->disableApp(
            $this->org->org_id,
            $app->slug,
            $this->user->user_id
        );

        $this->assertTrue($result['success']);
        $this->assertFalse($this->service->isAppEnabled($this->org->org_id, $app->slug));
    }

    /** @test */
    public function it_cannot_disable_core_apps()
    {
        $coreApp = MarketplaceApp::where('is_core', true)->first();

        if (!$coreApp) {
            $this->markTestSkipped('No core apps available');
        }

        $result = $this->service->disableApp(
            $this->org->org_id,
            $coreApp->slug,
            $this->user->user_id
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Core apps cannot be modified', $result['message']);
    }

    /** @test */
    public function it_can_get_enabled_app_slugs()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // Enable an app
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        $enabledSlugs = $this->service->getEnabledAppSlugs($this->org->org_id);

        $this->assertContains($app->slug, $enabledSlugs);
    }

    /** @test */
    public function it_can_bulk_enable_apps()
    {
        $apps = MarketplaceApp::where('is_core', false)->take(3)->get();

        if ($apps->count() < 2) {
            $this->markTestSkipped('Not enough non-core apps available');
        }

        $slugs = $apps->pluck('slug')->toArray();

        $result = $this->service->bulkEnable(
            $this->org->org_id,
            $slugs,
            $this->user->user_id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(count($slugs), $result['enabled']);
    }

    /** @test */
    public function it_can_bulk_disable_apps()
    {
        $apps = MarketplaceApp::where('is_core', false)->take(3)->get();

        if ($apps->count() < 2) {
            $this->markTestSkipped('Not enough non-core apps available');
        }

        $slugs = $apps->pluck('slug')->toArray();

        // First enable them
        $this->service->bulkEnable($this->org->org_id, $slugs, $this->user->user_id);

        // Then disable them
        $result = $this->service->bulkDisable(
            $this->org->org_id,
            $slugs,
            $this->user->user_id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(count($slugs), $result['disabled']);
    }

    /** @test */
    public function it_can_get_app_usage_stats()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // Enable an app
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        $stats = $this->service->getAppUsageStats($this->org->org_id);

        $this->assertArrayHasKey($app->slug, $stats);
        $this->assertArrayHasKey('enabled_at', $stats[$app->slug]);
        $this->assertArrayHasKey('enabled_by_name', $stats[$app->slug]);
        $this->assertTrue($stats[$app->slug]['is_enabled']);
    }

    /** @test */
    public function it_can_get_and_update_app_settings()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // Enable the app first
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        // Update settings
        $settings = ['theme' => 'dark', 'notifications' => true];
        $result = $this->service->updateAppSettings($this->org->org_id, $app->slug, $settings);

        $this->assertTrue($result['success']);
        $this->assertEquals($settings, $result['settings']);

        // Get settings
        $retrievedSettings = $this->service->getAppSettings($this->org->org_id, $app->slug);
        $this->assertEquals($settings, $retrievedSettings);
    }

    /** @test */
    public function it_returns_error_when_updating_settings_for_disabled_app()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        $result = $this->service->updateAppSettings(
            $this->org->org_id,
            $app->slug,
            ['test' => 'value']
        );

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_can_get_marketplace_status()
    {
        $status = $this->service->getStatus($this->org->org_id);

        $this->assertArrayHasKey('total_apps', $status);
        $this->assertArrayHasKey('enabled_count', $status);
        $this->assertArrayHasKey('enabled_apps', $status);
        $this->assertArrayHasKey('has_premium', $status);
    }

    /** @test */
    public function it_enables_dependencies_automatically()
    {
        // Find an app with dependencies
        $appWithDeps = MarketplaceApp::whereNotNull('dependencies')
            ->where('dependencies', '!=', '[]')
            ->first();

        if (!$appWithDeps || empty($appWithDeps->dependencies)) {
            $this->markTestSkipped('No apps with dependencies available');
        }

        $result = $this->service->enableApp(
            $this->org->org_id,
            $appWithDeps->slug,
            $this->user->user_id
        );

        $this->assertTrue($result['success']);

        // Check that dependencies were also enabled
        foreach ($appWithDeps->dependencies as $depSlug) {
            $this->assertTrue(
                $this->service->isAppEnabled($this->org->org_id, $depSlug),
                "Dependency {$depSlug} should be enabled"
            );
        }
    }

    /** @test */
    public function it_clears_cache_when_enabling_or_disabling()
    {
        $app = MarketplaceApp::where('is_core', false)->first();

        if (!$app) {
            $this->markTestSkipped('No non-core apps available');
        }

        // Get initial cached value
        $initialSlugs = $this->service->getEnabledAppSlugs($this->org->org_id);

        // Enable app
        $this->service->enableApp($this->org->org_id, $app->slug, $this->user->user_id);

        // Get updated value (should include newly enabled app)
        $updatedSlugs = $this->service->getEnabledAppSlugs($this->org->org_id);

        $this->assertContains($app->slug, $updatedSlugs);
    }
}
