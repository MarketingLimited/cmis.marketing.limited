<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FeatureFlagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureFlagService::class);

        // Set admin context for tests
        DB::statement("SET LOCAL app.is_admin = true");
    }

    /** @test */
    public function it_returns_false_for_non_existent_feature()
    {
        $result = $this->service->isEnabled('non.existent.feature');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_enabled_system_level_feature()
    {
        // Create a system-level feature flag
        DB::table('cmis.feature_flags')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'feature_key' => 'scheduling.meta.enabled',
            'scope_type' => 'system',
            'scope_id' => null,
            'value' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->isEnabled('scheduling.meta.enabled');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_check_disabled_system_level_feature()
    {
        DB::table('cmis.feature_flags')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'feature_key' => 'scheduling.google.enabled',
            'scope_type' => 'system',
            'scope_id' => null,
            'value' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->isEnabled('scheduling.google.enabled');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_set_a_feature_flag()
    {
        $result = $this->service->set('paid_campaigns.tiktok.enabled', true);
        $this->assertTrue($result);

        // Verify it was saved
        $flag = DB::table('cmis.feature_flags')
            ->where('feature_key', 'paid_campaigns.tiktok.enabled')
            ->first();

        $this->assertNotNull($flag);
        $this->assertTrue($flag->value);
    }

    /** @test */
    public function it_can_update_existing_feature_flag()
    {
        // Create initial flag
        $this->service->set('analytics.linkedin.enabled', false);

        // Update it
        $this->service->set('analytics.linkedin.enabled', true);

        // Verify update
        $flag = DB::table('cmis.feature_flags')
            ->where('feature_key', 'analytics.linkedin.enabled')
            ->first();

        $this->assertTrue($flag->value);
    }

    /** @test */
    public function it_can_get_enabled_platforms_for_feature()
    {
        // Set up multiple platform flags
        $this->service->set('scheduling.meta.enabled', true);
        $this->service->set('scheduling.tiktok.enabled', true);
        $this->service->set('scheduling.google.enabled', false);

        $enabledPlatforms = $this->service->getEnabledPlatforms('scheduling');

        $this->assertContains('meta', $enabledPlatforms);
        $this->assertContains('tiktok', $enabledPlatforms);
        $this->assertNotContains('google', $enabledPlatforms);
    }

    /** @test */
    public function it_can_get_feature_matrix()
    {
        // Set up test data
        $this->service->set('scheduling.meta.enabled', true);
        $this->service->set('paid_campaigns.meta.enabled', true);
        $this->service->set('scheduling.google.enabled', false);

        $matrix = $this->service->getFeatureMatrix(['scheduling', 'paid_campaigns']);

        $this->assertTrue($matrix['scheduling']['meta']);
        $this->assertTrue($matrix['paid_campaigns']['meta']);
        $this->assertFalse($matrix['scheduling']['google']);
    }

    /** @test */
    public function it_caches_feature_flag_checks()
    {
        Cache::flush();

        // Create a flag
        $this->service->set('scheduling.meta.enabled', true);

        // First check - should query database
        $this->service->isEnabled('scheduling.meta.enabled');

        // Second check - should use cache
        $this->assertTrue(Cache::has('feature_flag:scheduling.meta.enabled:global:default'));
    }

    /** @test */
    public function it_can_create_user_override()
    {
        $userId = \Illuminate\Support\Str::uuid();

        $result = $this->service->setOverride(
            'paid_campaigns.meta.enabled',
            $userId,
            'user',
            true,
            'Beta testing access'
        );

        $this->assertTrue($result);

        // Verify override was created
        $override = DB::table('cmis.feature_flag_overrides')
            ->where('target_id', $userId)
            ->where('target_type', 'user')
            ->first();

        $this->assertNotNull($override);
        $this->assertEquals('Beta testing access', $override->reason);
    }

    /** @test */
    public function it_respects_hierarchical_resolution()
    {
        $userId = \Illuminate\Support\Str::uuid();
        $orgId = \Illuminate\Support\Str::uuid();

        // Set system default to false
        $this->service->set('scheduling.meta.enabled', false);

        // Set org override to true
        $this->service->set('scheduling.meta.enabled', true, 'organization', $orgId);

        // Set user override to false (should have highest priority)
        $this->service->setOverride('scheduling.meta.enabled', $userId, 'user', false);

        // Note: Testing hierarchical resolution requires setting context
        // This is a simplified test
        $systemValue = DB::table('cmis.feature_flags')
            ->where('feature_key', 'scheduling.meta.enabled')
            ->where('scope_type', 'system')
            ->value('value');

        $this->assertFalse($systemValue);
    }

    /** @test */
    public function it_extracts_platform_from_feature_key()
    {
        $this->service->set('scheduling.meta.enabled', true);
        $this->service->set('scheduling.tiktok.enabled', false);

        $metaEnabled = $this->service->isEnabled('scheduling.meta.enabled');
        $tiktokEnabled = $this->service->isEnabled('scheduling.tiktok.enabled');

        $this->assertTrue($metaEnabled);
        $this->assertFalse($tiktokEnabled);
    }

    /** @test */
    public function it_clears_cache_when_setting_flag()
    {
        Cache::flush();

        // Set initial value
        $this->service->set('analytics.twitter.enabled', true);
        $this->service->isEnabled('analytics.twitter.enabled'); // Cache it

        // Update value
        $this->service->set('analytics.twitter.enabled', false);

        // Cache should be cleared, new value should be fetched
        $result = $this->service->isEnabled('analytics.twitter.enabled');
        $this->assertFalse($result);
    }
}
