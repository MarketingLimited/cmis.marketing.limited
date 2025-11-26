<?php

namespace Tests\Feature\Middleware;

use Tests\TestCase;
use App\Http\Middleware\CheckPlatformFeatureEnabled;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class CheckPlatformFeatureEnabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set admin context
        DB::statement("SET LOCAL app.is_admin = true");

        // Set up test routes
        Route::get('/test-campaigns/{platform}/create', function () {
            return response()->json(['success' => true]);
        })->middleware(CheckPlatformFeatureEnabled::class . ':paid_campaigns');

        Route::get('/test-scheduling/{platform}/post', function () {
            return response()->json(['success' => true]);
        })->middleware(CheckPlatformFeatureEnabled::class . ':scheduling');
    }

    /** @test */
    public function it_allows_request_when_feature_is_enabled()
    {
        // Enable feature for Meta
        DB::table('cmis.feature_flags')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'feature_key' => 'paid_campaigns.meta.enabled',
            'scope_type' => 'system',
            'scope_id' => null,
            'value' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/test-campaigns/meta/create');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_blocks_request_when_feature_is_disabled()
    {
        // Disable feature for Google
        DB::table('cmis.feature_flags')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'feature_key' => 'paid_campaigns.google.enabled',
            'scope_type' => 'system',
            'scope_id' => null,
            'value' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/test-campaigns/google/create');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Feature not available',
            ]);
    }

    /** @test */
    public function it_returns_available_platforms_in_error_response()
    {
        // Enable for some platforms, disable for others
        $platforms = [
            ['platform' => 'meta', 'enabled' => true],
            ['platform' => 'tiktok', 'enabled' => true],
            ['platform' => 'google', 'enabled' => false],
        ];

        foreach ($platforms as $platform) {
            DB::table('cmis.feature_flags')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'feature_key' => "scheduling.{$platform['platform']}.enabled",
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => $platform['enabled'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson('/test-scheduling/google/post');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'error',
                'message',
                'feature',
                'platform',
                'available_platforms'
            ]);

        $data = $response->json();
        $this->assertContains('meta', $data['available_platforms']);
        $this->assertContains('tiktok', $data['available_platforms']);
        $this->assertNotContains('google', $data['available_platforms']);
    }

    /** @test */
    public function it_handles_missing_platform_parameter()
    {
        Route::get('/test-no-platform/create', function () {
            return response()->json(['success' => true]);
        })->middleware(CheckPlatformFeatureEnabled::class . ':paid_campaigns');

        $response = $this->getJson('/test-no-platform/create');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Feature not available',
                'message' => 'Platform not specified',
            ]);
    }

    /** @test */
    public function it_blocks_non_existent_feature_by_default()
    {
        // Don't create any feature flags

        $response = $this->getJson('/test-campaigns/meta/create');

        $response->assertStatus(403);
    }
}
