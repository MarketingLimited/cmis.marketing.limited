# خطة التنفيذ التفصيلية لتحسينات الذكاء الاصطناعي
## Detailed Implementation Plan for AI Improvements

---

## 📅 المرحلة 1: الأساسيات (الأسبوع 1-2)

### الأسبوع 1: Unified Embedding Service

#### اليوم 1-2: التحليل والتصميم
**المهام:**
- [ ] مراجعة جميع استخدامات embeddings الحالية
- [ ] تصميم schema للـ unified cache
- [ ] تحديد migration strategy

**الملفات المتأثرة:**
```
database/migrations/2025_XX_XX_create_unified_embeddings_cache.php
app/Models/AI/UnifiedEmbeddingCache.php
config/ai.php
```

**Migration SQL:**
```sql
-- إنشاء جدول موحد للـ embeddings cache
CREATE TABLE IF NOT EXISTS cmis.unified_embeddings_cache (
    cache_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    content_hash TEXT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    task_type VARCHAR(50) DEFAULT 'RETRIEVAL_DOCUMENT',
    embedding vector(768) NOT NULL,
    embedding_dim INTEGER NOT NULL,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    access_count INTEGER DEFAULT 1,
    hits_in_memory INTEGER DEFAULT 0,
    hits_in_redis INTEGER DEFAULT 0,
    hits_in_db INTEGER DEFAULT 0,
    UNIQUE(content_hash, provider, task_type)
);

-- Indexes للأداء
CREATE INDEX idx_unified_cache_hash ON cmis.unified_embeddings_cache(content_hash);
CREATE INDEX idx_unified_cache_provider ON cmis.unified_embeddings_cache(provider);
CREATE INDEX idx_unified_cache_accessed ON cmis.unified_embeddings_cache(last_accessed);

-- Vector index
CREATE INDEX idx_unified_cache_embedding ON cmis.unified_embeddings_cache
USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);
```

#### اليوم 3-4: تطوير UnifiedEmbeddingService
**المهام:**
- [ ] إنشاء UnifiedEmbeddingService class
- [ ] تطبيق multi-level caching
- [ ] إضافة batch processing
- [ ] كتابة unit tests

**الملفات:**
```php
app/Services/AI/UnifiedEmbeddingService.php
app/Services/AI/Providers/EmbeddingProviderInterface.php
app/Services/AI/Providers/GeminiProvider.php
app/Services/AI/Providers/OpenAIProvider.php
tests/Unit/Services/AI/UnifiedEmbeddingServiceTest.php
```

**Tests الأساسية:**
```php
<?php
// tests/Unit/Services/AI/UnifiedEmbeddingServiceTest.php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\UnifiedEmbeddingService;

class UnifiedEmbeddingServiceTest extends TestCase
{
    public function test_embed_uses_memory_cache()
    {
        $service = app(UnifiedEmbeddingService::class);

        // First call - generates embedding
        $embedding1 = $service->embed('test content');

        // Second call - should use memory cache
        $embedding2 = $service->embed('test content');

        $this->assertEquals($embedding1, $embedding2);
        // Verify only one API call was made
    }

    public function test_batch_embed_deduplicates()
    {
        $service = app(UnifiedEmbeddingService::class);

        $texts = [
            'text 1',
            'text 2',
            'text 1', // duplicate
            'text 3',
        ];

        $embeddings = $service->batchEmbed($texts);

        $this->assertCount(4, $embeddings);
        $this->assertEquals($embeddings[0], $embeddings[2]);
        // Verify only 3 API calls (not 4)
    }

    public function test_embed_stores_in_all_cache_levels()
    {
        $service = app(UnifiedEmbeddingService::class);

        $embedding = $service->embed('new content');

        // Verify in Redis
        $this->assertNotNull(Cache::get('embedding:...'));

        // Verify in DB
        $this->assertDatabaseHas('cmis.unified_embeddings_cache', [
            'content_hash' => md5('new content'),
        ]);
    }
}
```

#### اليوم 5: Integration والـ Testing
**المهام:**
- [ ] Integrate مع GeminiEmbeddingService
- [ ] Migrate existing embeddings
- [ ] Performance testing
- [ ] Documentation

**Migration Script:**
```php
<?php
// app/Console/Commands/MigrateToUnifiedCache.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateToUnifiedCache extends Command
{
    protected $signature = 'ai:migrate-unified-cache
                            {--batch-size=1000 : Number of records per batch}
                            {--dry-run : Run without making changes}';

    protected $description = 'Migrate existing embeddings to unified cache';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        $this->info('Starting migration to unified embeddings cache...');

        // Migrate from old EmbeddingsCache
        $this->migrateFromOldCache($batchSize, $dryRun);

        // Migrate from Laravel Cache
        $this->migrateFromLaravelCache($batchSize, $dryRun);

        $this->info('Migration completed!');
    }

    private function migrateFromOldCache(int $batchSize, bool $dryRun)
    {
        $total = DB::table('cmis.embeddings_cache')->count();
        $this->info("Migrating {$total} records from old cache...");

        DB::table('cmis.embeddings_cache')
            ->orderBy('cache_id')
            ->chunk($batchSize, function ($records) use ($dryRun) {
                foreach ($records as $record) {
                    if (!$dryRun) {
                        DB::table('cmis.unified_embeddings_cache')->insert([
                            'content_hash' => $record->content_hash,
                            'provider' => $record->provider ?? 'gemini',
                            'model_name' => $record->model_name,
                            'task_type' => 'RETRIEVAL_DOCUMENT',
                            'embedding' => $record->embedding,
                            'embedding_dim' => $record->embedding_dim,
                            'created_at' => $record->cached_at,
                            'last_accessed' => $record->last_accessed,
                            'access_count' => $record->access_count,
                        ]);
                    }
                }

                $this->info("Migrated {$records->count()} records");
            });
    }
}
```

### الأسبوع 2: Cache Optimization & Monitoring

#### اليوم 1-2: Cache Warming & Cleanup
**المهام:**
- [ ] تطوير cache warming job
- [ ] تطوير cache cleanup job
- [ ] إعداد scheduling

**الملفات:**
```php
app/Jobs/AI/WarmEmbeddingsCacheJob.php
app/Jobs/AI/CleanupStaleEmbeddingsJob.php
app/Console/Kernel.php (update schedule)
```

**Cache Warming Job:**
```php
<?php
// app/Jobs/AI/WarmEmbeddingsCacheJob.php

namespace App\Jobs\AI;

use App\Services\AI\UnifiedEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class WarmEmbeddingsCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(UnifiedEmbeddingService $embeddingService)
    {
        // Get popular search queries from last 7 days
        $popularQueries = DB::table('cmis_knowledge.search_analytics')
            ->where('searched_at', '>=', now()->subDays(7))
            ->groupBy('query')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(100)
            ->pluck('query');

        Log::info("Warming cache for {$popularQueries->count()} popular queries");

        foreach ($popularQueries as $query) {
            try {
                // This will cache if not already cached
                $embeddingService->embed($query, [
                    'task_type' => 'RETRIEVAL_QUERY'
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to warm cache for query: {$query}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Cache warming completed');
    }
}
```

**Cleanup Job:**
```php
<?php
// app/Jobs/AI/CleanupStaleEmbeddingsJob.php

namespace App\Jobs\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CleanupStaleEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $daysOld = 90; // Remove embeddings not accessed in 90 days

        $deleted = DB::table('cmis.unified_embeddings_cache')
            ->where('last_accessed', '<', now()->subDays($daysOld))
            ->where('access_count', '<', 5) // Keep if accessed 5+ times
            ->delete();

        Log::info("Cleaned up {$deleted} stale embeddings");

        // Archive instead of delete for important ones
        $archived = DB::table('cmis.unified_embeddings_cache')
            ->where('last_accessed', '<', now()->subDays($daysOld))
            ->where('access_count', '>=', 5)
            ->update(['archived' => true]);

        Log::info("Archived {$archived} old but frequently used embeddings");
    }
}
```

**Scheduling:**
```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // ... existing schedules

    // Warm cache every 6 hours
    $schedule->job(new WarmEmbeddingsCacheJob)
        ->everyFourHours()
        ->withoutOverlapping();

    // Cleanup stale embeddings weekly
    $schedule->job(new CleanupStaleEmbeddingsJob)
        ->weekly()
        ->sundays()
        ->at('02:00');
}
```

#### اليوم 3-4: Monitoring & Metrics
**المهام:**
- [ ] تطوير metrics collection
- [ ] إنشاء monitoring dashboard
- [ ] إعداد alerts

**الملفات:**
```php
app/Services/AI/EmbeddingMetricsCollector.php
app/Http/Controllers/Admin/AIMetricsDashboardController.php
resources/views/admin/ai-metrics.blade.php
```

**Metrics Collector:**
```php
<?php
// app/Services/AI/EmbeddingMetricsCollector.php

namespace App\Services\AI;

class EmbeddingMetricsCollector
{
    /**
     * Collect cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $total = DB::table('cmis.unified_embeddings_cache')->count();

        $hitsByLevel = [
            'memory' => DB::table('cmis.unified_embeddings_cache')->sum('hits_in_memory'),
            'redis' => DB::table('cmis.unified_embeddings_cache')->sum('hits_in_redis'),
            'db' => DB::table('cmis.unified_embeddings_cache')->sum('hits_in_db'),
        ];

        $totalHits = array_sum($hitsByLevel);
        $totalMisses = DB::table('cmis_knowledge.search_analytics')
            ->where('searched_at', '>=', now()->subDay())
            ->count() - $totalHits;

        $cacheHitRate = $totalHits / ($totalHits + $totalMisses) * 100;

        return [
            'total_cached' => $total,
            'hits_by_level' => $hitsByLevel,
            'cache_hit_rate' => round($cacheHitRate, 2),
            'total_size_mb' => $this->calculateCacheSize(),
        ];
    }

    /**
     * Collect API usage metrics
     */
    public function getAPIMetrics(): array
    {
        $today = now()->startOfDay();

        return [
            'requests_today' => DB::table('cmis.ai_api_logs')
                ->where('created_at', '>=', $today)
                ->count(),

            'cost_today' => DB::table('cmis.ai_api_logs')
                ->where('created_at', '>=', $today)
                ->sum('cost'),

            'avg_response_time' => DB::table('cmis.ai_api_logs')
                ->where('created_at', '>=', $today)
                ->avg('response_time_ms'),

            'error_rate' => $this->calculateErrorRate($today),

            'by_provider' => DB::table('cmis.ai_api_logs')
                ->where('created_at', '>=', $today)
                ->groupBy('provider')
                ->select('provider', DB::raw('COUNT(*) as count'), DB::raw('SUM(cost) as cost'))
                ->get(),
        ];
    }

    /**
     * Collect search quality metrics
     */
    public function getSearchMetrics(): array
    {
        $week = now()->subWeek();

        return [
            'total_searches' => DB::table('cmis_knowledge.search_analytics')
                ->where('searched_at', '>=', $week)
                ->count(),

            'avg_results' => DB::table('cmis_knowledge.search_analytics')
                ->where('searched_at', '>=', $week)
                ->avg('results_count'),

            'zero_result_rate' => $this->calculateZeroResultRate($week),

            'popular_queries' => DB::table('cmis_knowledge.search_analytics')
                ->where('searched_at', '>=', $week)
                ->groupBy('query')
                ->select('query', DB::raw('COUNT(*) as count'))
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];
    }

    private function calculateCacheSize(): float
    {
        // 768 dimensions × 4 bytes per float + overhead
        $bytesPerEmbedding = 768 * 4 + 500; // ~3.5KB
        $count = DB::table('cmis.unified_embeddings_cache')->count();

        return round($count * $bytesPerEmbedding / 1024 / 1024, 2); // MB
    }
}
```

#### اليوم 5: Documentation & Review
**المهام:**
- [ ] كتابة documentation كاملة
- [ ] Code review
- [ ] Performance benchmarking
- [ ] Prepare for Phase 2

---

## 📅 المرحلة 2: التحسينات الأساسية (الأسبوع 3-5)

### الأسبوع 3: Multi-Provider AI Gateway

#### اليوم 1-2: Provider Abstraction
**المهام:**
- [ ] تصميم provider interface
- [ ] تطوير provider adapters
- [ ] تطبيق health checking

**الملفات:**
```php
app/Services/AI/Providers/AIProviderInterface.php
app/Services/AI/Providers/OpenAIProvider.php
app/Services/AI/Providers/AnthropicProvider.php
app/Services/AI/Providers/GeminiProvider.php
app/Services/AI/ProviderHealthChecker.php
```

#### اليوم 3-4: Gateway Implementation
**المهام:**
- [ ] تطوير AIGateway service
- [ ] تطبيق failover logic
- [ ] تطبيق cost optimization
- [ ] Testing

#### اليوم 5: Integration & Monitoring
**المهام:**
- [ ] Integrate مع existing services
- [ ] Setup monitoring
- [ ] Documentation

### الأسبوع 4-5: Advanced Semantic Search

#### التفاصيل في القسم التالي...

---

## 📊 القياسات والمؤشرات

### المؤشرات الأساسية (KPIs)

| المؤشر | القيمة الحالية | الهدف | الطريقة |
|--------|----------------|--------|---------|
| **Cache Hit Rate** | 40% | 95%+ | Monitor daily |
| **API Cost/Month** | $800 | <$350 | Track weekly |
| **Search Speed** | 1-3s | <300ms | P95 latency |
| **Error Rate** | 2-5% | <0.1% | Monitor hourly |
| **Uptime** | 99.5% | 99.99% | Weekly report |

### أدوات القياس

```bash
# Daily metrics collection
php artisan ai:collect-metrics --daily

# Generate weekly report
php artisan ai:report --week

# Performance benchmark
php artisan ai:benchmark --comprehensive
```

---

## 🚨 المخاطر والتخفيف

### المخاطر المحتملة:

1. **Migration Downtime**
   - **الخطر**: توقف الخدمة أثناء migration
   - **التخفيف**: Blue-green deployment
   - **الخطة B**: Rollback script جاهز

2. **Performance Degradation**
   - **الخطر**: أداء أسوأ بعد التحديثات
   - **التخفيف**: Extensive testing before deployment
   - **الخطة B**: Feature flags للتراجع السريع

3. **Cost Overrun**
   - **الخطر**: تكاليف أعلى من المتوقع
   - **التخفيف**: Budget alerts + automatic throttling
   - **الخطة B**: Fallback to cheaper providers

---

## ✅ Checklist للإطلاق

### قبل Production:
- [ ] All tests passing (unit, integration, e2e)
- [ ] Performance benchmarks meet targets
- [ ] Documentation complete
- [ ] Monitoring & alerts configured
- [ ] Rollback plan documented
- [ ] Team training completed
- [ ] Stakeholder approval obtained

### يوم الإطلاق:
- [ ] Backup current system
- [ ] Deploy to staging first
- [ ] Smoke tests pass
- [ ] Gradual rollout (10% → 50% → 100%)
- [ ] Monitor metrics closely
- [ ] Team on standby

### بعد الإطلاق:
- [ ] Monitor for 48 hours
- [ ] Collect user feedback
- [ ] Review metrics vs targets
- [ ] Document lessons learned
- [ ] Plan next iteration

---

## 📞 جهات الاتصال

### الفريق الفني:
- **Lead Developer**: [Name]
- **DevOps**: [Name]
- **QA**: [Name]

### في حالة الطوارئ:
- **On-Call Engineer**: [Phone]
- **System Admin**: [Phone]

---

**آخر تحديث**: 2025-11-18
**الإصدار**: 1.0
**الحالة**: Ready for Implementation
