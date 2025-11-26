<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class FeatureControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set admin context
        DB::statement("SET LOCAL app.is_admin = true");

        // Seed some test data
        $this->seedTestFeatureFlags();
    }

    protected function seedTestFeatureFlags()
    {
        $flags = [
            ['feature_key' => 'scheduling.meta.enabled', 'value' => true],
            ['feature_key' => 'scheduling.tiktok.enabled', 'value' => true],
            ['feature_key' => 'scheduling.google.enabled', 'value' => false],
            ['feature_key' => 'paid_campaigns.meta.enabled', 'value' => true],
            ['feature_key' => 'paid_campaigns.google.enabled', 'value' => false],
        ];

        foreach ($flags as $flag) {
            DB::table('cmis.feature_flags')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'feature_key' => $flag['feature_key'],
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => $flag['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @test */
    public function it_returns_available_platforms()
    {
        $response = $this->getJson('/api/features/available-platforms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'platforms' => [
                    '*' => [
                        'key',
                        'name',
                        'display_name',
                        'logo',
                        'enabled',
                        'features',
                    ]
                ],
                'timestamp'
            ]);

        $data = $response->json();

        // Meta should be enabled (has enabled features)
        $meta = collect($data['platforms'])->firstWhere('key', 'meta');
        $this->assertTrue($meta['enabled']);
        $this->assertTrue($meta['features']['scheduling']['enabled']);
        $this->assertTrue($meta['features']['paid_campaigns']['enabled']);

        // Google should be disabled (no enabled features in our test data)
        $google = collect($data['platforms'])->firstWhere('key', 'google');
        $this->assertFalse($google['features']['scheduling']['enabled']);
        $this->assertFalse($google['features']['paid_campaigns']['enabled']);
    }

    /** @test */
    public function it_returns_feature_matrix()
    {
        $response = $this->getJson('/api/features/matrix');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'matrix',
                'features',
                'platforms',
                'timestamp'
            ]);

        $data = $response->json();

        // Verify specific feature states
        $this->assertTrue($data['matrix']['scheduling']['meta']);
        $this->assertTrue($data['matrix']['scheduling']['tiktok']);
        $this->assertFalse($data['matrix']['scheduling']['google']);
        $this->assertTrue($data['matrix']['paid_campaigns']['meta']);
    }

    /** @test */
    public function it_returns_enabled_platforms_for_feature_category()
    {
        $response = $this->getJson('/api/features/enabled-platforms/scheduling');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'feature_category',
                'enabled_platforms',
                'count',
                'timestamp'
            ]);

        $data = $response->json();

        $this->assertEquals('scheduling', $data['feature_category']);
        $this->assertContains('meta', $data['enabled_platforms']);
        $this->assertContains('tiktok', $data['enabled_platforms']);
        $this->assertNotContains('google', $data['enabled_platforms']);
        $this->assertEquals(2, $data['count']);
    }

    /** @test */
    public function it_can_check_specific_feature()
    {
        $response = $this->getJson('/api/features/check/scheduling.meta.enabled');

        $response->assertStatus(200)
            ->assertJson([
                'feature_key' => 'scheduling.meta.enabled',
                'enabled' => true,
            ]);
    }

    /** @test */
    public function it_returns_false_for_disabled_feature()
    {
        $response = $this->getJson('/api/features/check/scheduling.google.enabled');

        $response->assertStatus(200)
            ->assertJson([
                'feature_key' => 'scheduling.google.enabled',
                'enabled' => false,
            ]);
    }

    /** @test */
    public function it_returns_false_for_non_existent_feature()
    {
        $response = $this->getJson('/api/features/check/non.existent.feature');

        $response->assertStatus(200)
            ->assertJson([
                'feature_key' => 'non.existent.feature',
                'enabled' => false,
            ]);
    }
}
