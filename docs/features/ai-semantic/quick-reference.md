# مرجع سريع: تحسينات الذكاء الاصطناعي
## Quick Reference Guide for Developers

---

## 🚀 البدء السريع

### المشكلة؟ ابحث هنا:

| المشكلة | الصفحة | الحل السريع |
|---------|--------|-------------|
| 💸 **تكلفة API عالية** | `ANALYSIS...md` #4 | Implement unified cache |
| 🐌 **بحث دلالي بطيء** | `ANALYSIS...md` #2 | Add vector indexes + optimize query |
| ❌ **API failures** | `ANALYSIS...md` #3 | Implement multi-provider gateway |
| 🔄 **Duplicate embeddings** | `ANALYSIS...md` #7 | Use UnifiedEmbeddingService |
| ⏱️ **Token limit errors** | `ANALYSIS...md` #6 | Implement ContextManager |

---

## 📝 Cheat Sheet: الكود الأكثر احتياجاً

### 1. استخدام Unified Embedding Service

**قبل:**
```php
// ❌ Old way - multiple cache systems
$embedding = $geminiService->generateEmbedding($text);
```

**بعد:**
```php
// ✅ New way - unified caching
use App\Services\AI\UnifiedEmbeddingService;

$embeddingService = app(UnifiedEmbeddingService::class);

// Single embedding
$embedding = $embeddingService->embed($text);

// Batch embeddings (with automatic deduplication)
$embeddings = $embeddingService->batchEmbed($texts);

// With options
$embedding = $embeddingService->embed($text, [
    'provider' => 'openai',  // or 'gemini'
    'task_type' => 'RETRIEVAL_QUERY'
]);
```

---

### 2. استخدام AI Gateway

**قبل:**
```php
// ❌ Direct OpenAI call - no failover
$response = Http::withHeaders([...])->post('openai.com/...', [...]);
```

**بعد:**
```php
// ✅ Gateway with automatic failover
use App\Services\AI\AIGateway;

$gateway = app(AIGateway::class);

// Generate content with automatic provider selection
$result = $gateway->generate($prompt, 'text', [
    'strategy' => 'cost_optimized',  // or 'quality_first', 'fast_first'
    'temperature' => 0.7,
    'max_tokens' => 2000,
]);

// Result includes:
// - content: generated text
// - provider: which provider was used
// - cost: calculated cost
// - tokens: usage statistics
```

---

### 3. استخدام Advanced Semantic Search

**قبل:**
```php
// ❌ Simple vector search
$embedding = $service->generateEmbedding($query);
$results = DB::select("SELECT ... WHERE similarity >= ? ...");
```

**بعد:**
```php
// ✅ Advanced search with re-ranking
use App\Services\AI\SemanticSearchV2;

$search = app(SemanticSearchV2::class);

$results = $search->search($query, [
    'limit' => 10,
    'threshold' => 0.7,
    'rerank' => true,      // Enable re-ranking
    'expand' => true,      // Enable query expansion
]);

// Results include:
// - results: ranked results
// - total: total candidates
// - query_expansion: expanded queries used
// - search_time_ms: performance metric
```

---

### 4. استخدام Context Manager

**قبل:**
```php
// ❌ No token management - may overflow
$prompt = $this->buildPrompt($instruction, $contexts);
$result = $this->callAPI($prompt);
```

**بعد:**
```php
// ✅ Smart context management
use App\Services\AI\ContextManager;

$contextManager = app(ContextManager::class);

$optimizedPrompt = $contextManager->buildOptimizedPrompt(
    instruction: $instruction,
    contexts: $contexts,
    options: [
        'max_context_tokens' => 6000,
        'model' => 'gpt-4',
    ]
);

// Automatically:
// - Counts tokens
// - Prioritizes important contexts
// - Compresses if needed
// - Never exceeds limit
```

---

## 🔧 Commands المفيدة

### Development:
```bash
# Generate embeddings for new content
php artisan ai:embed {content_id}

# Batch process embeddings
php artisan ai:batch-embed --limit=100

# Warm cache with popular queries
php artisan ai:warm-cache

# Cleanup stale embeddings
php artisan ai:cleanup-cache --days=90

# Migrate to unified cache
php artisan ai:migrate-unified-cache
```

### Monitoring:
```bash
# Collect metrics
php artisan ai:collect-metrics

# Generate report
php artisan ai:report --week

# Performance benchmark
php artisan ai:benchmark

# Check provider health
php artisan ai:check-health
```

### Testing:
```bash
# Test embeddings
php artisan test --filter=EmbeddingTest

# Test search
php artisan test --filter=SemanticSearchTest

# Test gateway
php artisan test --filter=AIGatewayTest

# Full AI test suite
php artisan test tests/Feature/AI
```

---

## 🐛 Troubleshooting

### مشكلة: "Cache miss rate عالي"

**التشخيص:**
```bash
# Check cache stats
php artisan ai:cache-stats

# Sample output:
# Total cached: 10,000
# Hit rate: 45%  ← LOW!
# Memory hits: 5%
# Redis hits: 15%
# DB hits: 25%
```

**الحلول:**
1. تأكد من استخدام `UnifiedEmbeddingService`
2. Run cache warming: `php artisan ai:warm-cache`
3. Check Redis connection: `php artisan redis:ping`
4. Review cache TTL settings in `config/ai.php`

---

### مشكلة: "Slow semantic search"

**التشخيص:**
```bash
# Check if vector indexes exist
php artisan ai:check-indexes
```

**الحلول:**
```sql
-- Create missing indexes
CREATE INDEX CONCURRENTLY idx_knowledge_topic_embedding
ON cmis_knowledge.index
USING ivfflat (topic_embedding vector_cosine_ops)
WITH (lists = 100);

-- Or use HNSW for better performance (slower build)
CREATE INDEX CONCURRENTLY idx_knowledge_topic_embedding_hnsw
ON cmis_knowledge.index
USING hnsw (topic_embedding vector_cosine_ops)
WITH (m = 16, ef_construction = 64);
```

---

### مشكلة: "API rate limit exceeded"

**التشخيص:**
```bash
# Check API usage
php artisan ai:api-usage --today
```

**الحلول:**
1. Enable caching (should prevent most API calls)
2. Increase rate limits in config
3. Use cheaper provider for non-critical tasks
4. Implement request queuing

**Code fix:**
```php
// ✅ Add to .env
AI_RATE_LIMIT_PER_MINUTE=60
AI_RATE_LIMIT_PER_HOUR=500

// ✅ Or configure per-provider
config/ai.php:
'providers' => [
    'gemini' => [
        'rate_limit' => [
            'per_minute' => 60,
            'per_hour' => 500,
        ],
    ],
],
```

---

### مشكلة: "All providers failing"

**التشخيص:**
```bash
# Check provider health
php artisan ai:check-health

# Sample output:
# OpenAI: ❌ DOWN (5 failures in last 5 min)
# Anthropic: ❌ DOWN (API key invalid)
# Gemini: ✅ HEALTHY
```

**الحلول:**
1. Check API keys in `.env`
2. Verify network connectivity
3. Check provider status pages
4. Review error logs: `storage/logs/laravel.log`

---

## 📊 Performance Benchmarks

### الأهداف المتوقعة بعد التحسينات:

| العملية | قبل | بعد | الهدف |
|---------|-----|-----|--------|
| **Single embedding (cached)** | 200-500ms | <10ms | <10ms |
| **Single embedding (new)** | 200-500ms | 150-300ms | <300ms |
| **Batch embeddings (100 items)** | 20-30s | 2-5s | <5s |
| **Semantic search** | 1-3s | 100-300ms | <300ms |
| **Content generation** | 5-10s | 1-3s | <3s |

### كيفية القياس:
```bash
# Run comprehensive benchmark
php artisan ai:benchmark --comprehensive

# Benchmark specific operation
php artisan ai:benchmark --operation=embedding
php artisan ai:benchmark --operation=search
php artisan ai:benchmark --operation=generation
```

---

## 🔐 Security Checklist

### قبل Production:

- [ ] API keys encrypted في database
- [ ] Rate limiting enabled لكل endpoint
- [ ] Input validation للـ user queries
- [ ] Output sanitization للـ AI responses
- [ ] Logging لكل API calls (without exposing keys)
- [ ] Monitoring و alerts configured
- [ ] Backup strategy للـ embeddings cache

### Code Examples:

```php
// ✅ Encrypt API keys
DB::table('ai_configs')->insert([
    'api_key' => encrypt($apiKey),  // NOT plain text!
]);

// ✅ Validate input
public function search(Request $request)
{
    $validated = $request->validate([
        'query' => 'required|string|max:1000',
        'limit' => 'integer|min:1|max:100',
    ]);

    // Sanitize
    $query = strip_tags($validated['query']);

    // ...
}

// ✅ Sanitize AI output
$generatedContent = $aiService->generate($prompt);
$sanitized = strip_tags($generatedContent, '<p><br><strong><em>');
```

---

## 📚 المراجع السريعة

### الملفات المهمة:

```
app/Services/AI/
├── UnifiedEmbeddingService.php      ← Main embedding service
├── AIGateway.php                     ← Multi-provider gateway
├── SemanticSearchV2.php              ← Advanced search
├── ContextManager.php                ← Context optimization
├── Providers/
│   ├── GeminiProvider.php
│   ├── OpenAIProvider.php
│   └── AnthropicProvider.php
└── Metrics/
    └── EmbeddingMetricsCollector.php ← Monitoring

config/
├── ai.php                            ← Main AI config
└── ai-providers.php                  ← Provider configs

database/migrations/
└── 2025_XX_XX_create_unified_embeddings_cache.php
```

### التكوينات:

```php
// config/ai.php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    'providers' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => 'gemini-embedding-001',
            'dimensions' => 768,
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'embedding_model' => 'text-embedding-ada-002',
            'generation_model' => 'gpt-4',
        ],
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => [
            'memory' => 3600,    // 1 hour
            'redis' => 86400,    // 24 hours
            'database' => null,  // permanent
        ],
    ],

    'rate_limiting' => [
        'enabled' => true,
        'per_minute' => 60,
        'per_hour' => 500,
    ],
];
```

---

## 🆘 Need Help?

### Documentation:
- **Full Analysis**: `ANALYSIS_AI_SEMANTIC_FEATURES.md`
- **Code Examples**: `AI_IMPROVEMENTS_EXAMPLES.md`
- **Implementation Plan**: `AI_IMPLEMENTATION_PLAN.md`
- **Executive Summary**: `AI_EXECUTIVE_SUMMARY.md`

### Expert Prompts:
```
.claude/prompts/CMIS_AI_SEMANTIC_EXPERT.md
```

### Knowledge Base:
```
.claude/knowledge/META_COGNITIVE_FRAMEWORK.md
.claude/knowledge/DISCOVERY_PROTOCOLS.md
```

### Contacts:
- **Technical Lead**: [Name]
- **On-Call**: [Phone]
- **Slack**: #cmis-ai-support

---

**Last Updated**: 2025-11-18
**Version**: 1.0
**Status**: Active
