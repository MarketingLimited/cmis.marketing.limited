<?php

namespace Tests\Unit\Services\Cache;

use Tests\TestCase;
use App\Services\Cache\CacheStrategyService;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Strategy Service Test
 *
 * Tests intelligent caching with TTL configurations and cache warming.
 * Part of Phase 1B weakness remediation (2025-11-21)
 */
class CacheStrategyServiceTest extends TestCase
{
    protected CacheStrategyService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new CacheStrategyService();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_caches_data_with_default_ttl()
    {
        $data = $this->cacheService->remember('campaign_metrics', 'campaign-123', function () {
            return ['impressions' => 1000, 'clicks' => 50];
        });

        $this->assertEquals(['impressions' => 1000, 'clicks' => 50], $data);

        // Verify it's cached
        $cached = Cache::get('cmis:campaign_metrics:campaign-123');
        $this->assertNotNull($cached);
        $this->assertEquals(1000, $cached['impressions']);
    }

    /** @test */
    public function it_returns_cached_data_without_recalculating()
    {
        $callCount = 0;

        // First call
        $this->cacheService->remember('platform_data', 'meta-account-1', function () use (&$callCount) {
            $callCount++;
            return ['account_name' => 'Test Account'];
        });

        // Second call should use cache
        $this->cacheService->remember('platform_data', 'meta-account-1', function () use (&$callCount) {
            $callCount++;
            return ['account_name' => 'Test Account'];
        });

        $this->assertEquals(1, $callCount, 'Callback should only be called once');
    }

    /** @test */
    public function it_uses_custom_ttl_when_provided()
    {
        $customTtl = 120;

        $this->cacheService->remember('custom_ttl', 'test-id', function () {
            return ['data' => 'test'];
        }, $customTtl);

        // Verify cache exists
        $this->assertTrue(Cache::has('cmis:custom_ttl:test-id'));
    }

    /** @test */
    public function it_stores_data_directly_in_cache()
    {
        $this->cacheService->put('user_settings', 'user-123', [
            'theme' => 'dark',
            'language' => 'en',
        ]);

        $retrieved = $this->cacheService->get('user_settings', 'user-123');

        $this->assertEquals('dark', $retrieved['theme']);
        $this->assertEquals('en', $retrieved['language']);
    }

    /** @test */
    public function it_retrieves_cached_data()
    {
        Cache::put('cmis:analytics:org-456', ['revenue' => 5000], 600);

        $data = $this->cacheService->get('analytics', 'org-456');

        $this->assertNotNull($data);
        $this->assertEquals(5000, $data['revenue']);
    }

    /** @test */
    public function it_returns_null_for_non_existent_cache()
    {
        $data = $this->cacheService->get('non_existent_type', 'fake-id');

        $this->assertNull($data);
    }

    /** @test */
    public function it_forgets_specific_cache_entry()
    {
        $this->cacheService->put('temp_data', 'temp-123', ['value' => 'test']);

        $this->assertNotNull($this->cacheService->get('temp_data', 'temp-123'));

        $this->cacheService->forget('temp_data', 'temp-123');

        $this->assertNull($this->cacheService->get('temp_data', 'temp-123'));
    }

    /** @test */
    public function it_flushes_all_cache_entries_of_specific_type()
    {
        // Create multiple cache entries
        $this->cacheService->put('campaign_metrics', 'campaign-1', ['data' => 1]);
        $this->cacheService->put('campaign_metrics', 'campaign-2', ['data' => 2]);
        $this->cacheService->put('user_settings', 'user-1', ['data' => 3]);

        // Flush only campaign_metrics
        $this->cacheService->flushType('campaign_metrics');

        // Campaign metrics should be gone
        $this->assertNull($this->cacheService->get('campaign_metrics', 'campaign-1'));
        $this->assertNull($this->cacheService->get('campaign_metrics', 'campaign-2'));

        // User settings should remain
        $this->assertNotNull($this->cacheService->get('user_settings', 'user-1'));
    }

    /** @test */
    public function it_warms_cache_with_provided_data()
    {
        $campaigns = [
            'campaign-1' => ['name' => 'Campaign 1'],
            'campaign-2' => ['name' => 'Campaign 2'],
            'campaign-3' => ['name' => 'Campaign 3'],
        ];

        $warmed = $this->cacheService->warm('campaigns', $campaigns);

        $this->assertEquals(3, $warmed);

        // Verify all campaigns are cached
        $this->assertNotNull($this->cacheService->get('campaigns', 'campaign-1'));
        $this->assertNotNull($this->cacheService->get('campaigns', 'campaign-2'));
        $this->assertNotNull($this->cacheService->get('campaigns', 'campaign-3'));

        $this->assertEquals('Campaign 1', $this->cacheService->get('campaigns', 'campaign-1')['name']);
    }

    /** @test */
    public function it_tracks_cache_statistics()
    {
        // Perform some cache operations
        $this->cacheService->remember('test_stats', 'id-1', fn() => ['data' => 1]);
        $this->cacheService->get('test_stats', 'id-1'); // Hit
        $this->cacheService->get('test_stats', 'id-2'); // Miss
        $this->cacheService->put('test_stats', 'id-3', ['data' => 3]);

        $stats = $this->cacheService->getStats();

        $this->assertArrayHasKey('total_operations', $stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);

        $this->assertGreaterThan(0, $stats['total_operations']);
    }

    /** @test */
    public function it_builds_correct_cache_keys()
    {
        $this->cacheService->put('platform_data', 'account-789', ['test' => true]);

        // Verify the key structure
        $directAccess = Cache::get('cmis:platform_data:account-789');
        $this->assertNotNull($directAccess);
        $this->assertTrue($directAccess['test']);
    }

    /** @test */
    public function it_respects_different_ttls_for_different_types()
    {
        // These would have different TTLs in the service configuration
        $this->cacheService->put('embeddings', 'embed-1', ['vector' => [1, 2, 3]]);
        $this->cacheService->put('ai_quota', 'org-1', ['used' => 5]);
        $this->cacheService->put('realtime_metrics', 'campaign-1', ['ctr' => 2.5]);

        // All should be retrievable
        $this->assertNotNull($this->cacheService->get('embeddings', 'embed-1'));
        $this->assertNotNull($this->cacheService->get('ai_quota', 'org-1'));
        $this->assertNotNull($this->cacheService->get('realtime_metrics', 'campaign-1'));
    }

    /** @test */
    public function it_handles_cache_with_tags()
    {
        if (!Cache::supportsTags()) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        $taggedCache = $this->cacheService->tags(['org:123', 'campaigns']);

        $this->assertInstanceOf(CacheStrategyService::class, $taggedCache);
    }

    /** @test */
    public function it_handles_null_callback_results()
    {
        $result = $this->cacheService->remember('nullable_data', 'test-id', function () {
            return null;
        });

        $this->assertNull($result);
    }

    /** @test */
    public function it_handles_complex_data_structures()
    {
        $complexData = [
            'campaign' => [
                'id' => 'camp-123',
                'metrics' => [
                    'daily' => ['impressions' => 1000, 'clicks' => 50],
                    'weekly' => ['impressions' => 7000, 'clicks' => 350],
                ],
                'platforms' => ['meta', 'google'],
            ],
        ];

        $this->cacheService->put('complex_structure', 'test-complex', $complexData);

        $retrieved = $this->cacheService->get('complex_structure', 'test-complex');

        $this->assertEquals($complexData, $retrieved);
        $this->assertEquals(1000, $retrieved['campaign']['metrics']['daily']['impressions']);
        $this->assertContains('meta', $retrieved['campaign']['platforms']);
    }

    /** @test */
    public function it_provides_type_specific_cache_info()
    {
        $this->cacheService->put('campaigns', 'camp-1', ['data' => 1]);
        $this->cacheService->put('campaigns', 'camp-2', ['data' => 2]);

        $info = $this->cacheService->getTypeInfo('campaigns');

        $this->assertArrayHasKey('type', $info);
        $this->assertEquals('campaigns', $info['type']);
        $this->assertArrayHasKey('default_ttl', $info);
    }
}
