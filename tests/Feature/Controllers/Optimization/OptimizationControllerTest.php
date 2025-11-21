<?php

namespace Tests\Feature\Controllers\Optimization;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use App\Services\Optimization\DatabaseQueryOptimizer;
use App\Services\Optimization\MultiLayerCacheService;
use App\Services\Optimization\PerformanceProfiler;
use App\Services\Optimization\HealthCheckService;
use Mockery;

class OptimizationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;
    protected string $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization and user
        $this->org = Organization::factory()->create();
        $this->orgId = $this->org->org_id;

        $this->user = User::factory()->create([
            'org_id' => $this->orgId
        ]);
    }

    // =========================================================================
    // HEALTH CHECK TESTS (Public Endpoints)
    // =========================================================================

    public function test_liveness_probe_returns_healthy()
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('liveness')
            ->once()
            ->andReturn([
                'status' => 'healthy',
                'message' => 'Application is alive',
                'uptime_seconds' => 3600
            ]);

        $this->app->instance(HealthCheckService::class, $mockService);

        $response = $this->getJson('/api/health/live');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'message' => 'Application is alive'
            ]);
    }

    public function test_readiness_probe_returns_healthy()
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('readiness')
            ->once()
            ->andReturn([
                'status' => 'healthy',
                'message' => 'Application is ready to serve traffic',
                'checks' => [
                    'database' => ['status' => 'healthy'],
                    'redis' => ['status' => 'healthy'],
                    'filesystem' => ['status' => 'healthy']
                ]
            ]);

        $this->app->instance(HealthCheckService::class, $mockService);

        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy'
            ]);
    }

    public function test_readiness_probe_returns_unhealthy()
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('readiness')
            ->once()
            ->andReturn([
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'checks' => [
                    'database' => ['status' => 'unhealthy', 'error' => 'Connection refused']
                ]
            ]);

        $this->app->instance(HealthCheckService::class, $mockService);

        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy'
            ]);
    }

    public function test_health_check_comprehensive()
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('health')
            ->once()
            ->andReturn([
                'status' => 'healthy',
                'message' => 'All health checks passed',
                'checks' => [
                    'database' => ['status' => 'healthy'],
                    'redis' => ['status' => 'healthy'],
                    'filesystem' => ['status' => 'healthy'],
                    'external_apis' => ['status' => 'healthy'],
                    'system' => ['status' => 'healthy'],
                    'application' => ['status' => 'healthy']
                ],
                'failed_checks' => []
            ]);

        $this->app->instance(HealthCheckService::class, $mockService);

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'checks',
                'failed_checks'
            ]);
    }

    // =========================================================================
    // DATABASE OPTIMIZATION TESTS
    // =========================================================================

    public function test_analyze_query_success()
    {
        $mockService = Mockery::mock(DatabaseQueryOptimizer::class);
        $mockService->shouldReceive('analyzeQuery')
            ->once()
            ->with(Mockery::type('string'), [])
            ->andReturn([
                'success' => true,
                'execution_time_ms' => 150.5,
                'severity' => 'low',
                'analysis' => [
                    'issues' => [],
                    'statistics' => [
                        'seq_scans' => 0,
                        'index_scans' => 2
                    ]
                ],
                'recommendations' => []
            ]);

        $this->app->instance(DatabaseQueryOptimizer::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/optimization/analyze-query', [
                'query' => 'SELECT * FROM cmis.campaigns WHERE org_id = ?',
                'bindings' => []
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'execution_time_ms' => 150.5
            ]);
    }

    public function test_analyze_query_validation_fails()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/optimization/analyze-query', [
                'bindings' => []
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    public function test_get_missing_indexes_success()
    {
        $mockService = Mockery::mock(DatabaseQueryOptimizer::class);
        $mockService->shouldReceive('getMissingIndexes')
            ->once()
            ->with('campaigns')
            ->andReturn([
                'success' => true,
                'table' => 'campaigns',
                'recommendations' => [
                    [
                        'column' => 'status',
                        'priority' => 'high',
                        'suggested_index' => 'CREATE INDEX idx_campaigns_status ON cmis.campaigns (status);'
                    ]
                ],
                'count' => 1
            ]);

        $this->app->instance(DatabaseQueryOptimizer::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/missing-indexes/campaigns');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'table' => 'campaigns'
            ]);
    }

    public function test_get_database_statistics_success()
    {
        $mockService = Mockery::mock(DatabaseQueryOptimizer::class);
        $mockService->shouldReceive('getDatabaseStatistics')
            ->once()
            ->andReturn([
                'success' => true,
                'database_size' => '10 GB',
                'cache_hit_ratio' => 95.5,
                'connections' => [
                    'total' => 10,
                    'active' => 2,
                    'idle' => 8
                ]
            ]);

        $this->app->instance(DatabaseQueryOptimizer::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/database-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'database_size',
                'cache_hit_ratio',
                'connections'
            ]);
    }

    public function test_optimize_table_success()
    {
        $mockService = Mockery::mock(DatabaseQueryOptimizer::class);
        $mockService->shouldReceive('optimizeTable')
            ->once()
            ->with('campaigns')
            ->andReturn([
                'success' => true,
                'table' => 'campaigns',
                'execution_time_ms' => 250.0,
                'statistics' => [
                    'live_tuples' => 10000,
                    'dead_tuples' => 100
                ]
            ]);

        $this->app->instance(DatabaseQueryOptimizer::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/optimization/optimize-table/campaigns');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'table' => 'campaigns'
            ]);
    }

    // =========================================================================
    // CACHE MANAGEMENT TESTS
    // =========================================================================

    public function test_get_cache_statistics_success()
    {
        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('getStatistics')
            ->once()
            ->andReturn([
                'success' => true,
                'statistics' => [
                    'hits' => 1000,
                    'misses' => 50,
                    'hit_rate' => 95.24
                ],
                'redis' => [
                    'used_memory' => '100M',
                    'key_count' => 500
                ]
            ]);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/cache/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics',
                'redis'
            ]);
    }

    public function test_invalidate_organization_cache_success()
    {
        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('invalidateOrganization')
            ->once()
            ->with($this->orgId)
            ->andReturn(25);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/optimization/cache/invalidate/organization/{$this->orgId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'keys_deleted' => 25
            ]);
    }

    public function test_invalidate_campaign_cache_success()
    {
        $campaignId = 'campaign-123';

        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('invalidateCampaign')
            ->once()
            ->with($campaignId)
            ->andReturn(10);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/optimization/cache/invalidate/campaign/{$campaignId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'keys_deleted' => 10
            ]);
    }

    public function test_invalidate_cache_pattern_success()
    {
        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('invalidatePattern')
            ->once()
            ->with('*:campaigns:*')
            ->andReturn(50);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/optimization/cache/invalidate-pattern', [
                'pattern' => '*:campaigns:*'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'keys_deleted' => 50
            ]);
    }

    public function test_warmup_cache_success()
    {
        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('warmup')
            ->once()
            ->with($this->orgId)
            ->andReturn([
                'success' => true,
                'org_id' => $this->orgId,
                'keys_warmed' => 3,
                'execution_time_ms' => 150.5
            ]);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/optimization/cache/warmup/{$this->orgId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'keys_warmed' => 3
            ]);
    }

    public function test_get_cache_health_success()
    {
        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('getHealthStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'healthy' => true,
                'redis_connected' => true,
                'cache_working' => true
            ]);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/cache/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'healthy' => true
            ]);
    }

    public function test_flush_cache_success()
    {
        $mockService = Mockery::mock(MultiLayerCacheService::class);
        $mockService->shouldReceive('flushAll')
            ->once()
            ->andReturn(true);

        $this->app->instance(MultiLayerCacheService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/optimization/cache/flush');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All caches flushed successfully'
            ]);
    }

    // =========================================================================
    // PERFORMANCE PROFILING TESTS
    // =========================================================================

    public function test_get_performance_profiles_success()
    {
        $mockService = Mockery::mock(PerformanceProfiler::class);
        $mockService->shouldReceive('getRecentProfiles')
            ->once()
            ->with(20)
            ->andReturn([
                'success' => true,
                'profiles' => [
                    [
                        'request_id' => 'req-123',
                        'total_time_ms' => 250.5,
                        'memory_used_mb' => 25.3,
                        'performance_rating' => ['overall' => 'good']
                    ]
                ],
                'count' => 1
            ]);

        $this->app->instance(PerformanceProfiler::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/performance/profiles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'profiles',
                'count'
            ]);
    }

    public function test_get_performance_summary_success()
    {
        $mockService = Mockery::mock(PerformanceProfiler::class);
        $mockService->shouldReceive('getSummaryStatistics')
            ->once()
            ->andReturn([
                'success' => true,
                'statistics' => [
                    'total_requests' => 100,
                    'time_stats' => [
                        'avg' => 250.5,
                        'min' => 50.0,
                        'max' => 1500.0,
                        'p95' => 800.0,
                        'p99' => 1200.0
                    ],
                    'slow_requests' => 5,
                    'fast_requests' => 85
                ]
            ]);

        $this->app->instance(PerformanceProfiler::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/performance/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'time_stats',
                    'slow_requests',
                    'fast_requests'
                ]
            ]);
    }

    public function test_get_system_resources_success()
    {
        $mockService = Mockery::mock(PerformanceProfiler::class);
        $mockService->shouldReceive('getSystemResources')
            ->once()
            ->andReturn([
                'success' => true,
                'cpu' => [
                    'load_1min' => 1.5,
                    'load_5min' => 1.2,
                    'load_15min' => 1.0
                ],
                'memory' => [
                    'current_usage_mb' => 128.5,
                    'peak_usage_mb' => 256.8,
                    'limit_mb' => '512M'
                ]
            ]);

        $this->app->instance(PerformanceProfiler::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/performance/resources');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'cpu',
                'memory'
            ]);
    }

    public function test_get_health_metrics_success()
    {
        $mockService = Mockery::mock(PerformanceProfiler::class);
        $mockService->shouldReceive('getHealthMetrics')
            ->once()
            ->andReturn([
                'success' => true,
                'health' => 'healthy',
                'metrics' => [
                    'avg_response_time_ms' => 250.5,
                    'critical_requests' => 0,
                    'poor_requests' => 5,
                    'total_requests' => 100
                ]
            ]);

        $this->app->instance(PerformanceProfiler::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/optimization/performance/health-metrics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'health' => 'healthy'
            ]);
    }

    public function test_clear_performance_data_success()
    {
        $mockService = Mockery::mock(PerformanceProfiler::class);
        $mockService->shouldReceive('clearData')
            ->once()
            ->andReturn(true);

        $this->app->instance(PerformanceProfiler::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/optimization/performance/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Performance data cleared successfully'
            ]);
    }

    // =========================================================================
    // AUTHENTICATION TESTS
    // =========================================================================

    public function test_authenticated_endpoints_require_auth()
    {
        // Test a few endpoints
        $response = $this->getJson('/api/optimization/diagnostics');
        $response->assertStatus(401);

        $response = $this->getJson('/api/optimization/database-stats');
        $response->assertStatus(401);

        $response = $this->postJson('/api/optimization/cache/flush');
        $response->assertStatus(401);
    }

    public function test_health_check_endpoints_are_public()
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('liveness')
            ->andReturn(['status' => 'healthy']);
        $mockService->shouldReceive('readiness')
            ->andReturn(['status' => 'healthy']);
        $mockService->shouldReceive('health')
            ->andReturn(['status' => 'healthy']);

        $this->app->instance(HealthCheckService::class, $mockService);

        // Should not require authentication
        $response = $this->getJson('/api/health/live');
        $response->assertStatus(200);

        $response = $this->getJson('/api/health/ready');
        $response->assertStatus(200);

        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
