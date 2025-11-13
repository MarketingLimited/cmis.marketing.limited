# AI Agent Guide for CMIS Repositories

> **Complete guide for AI agents, LLMs, and automated systems working with CMIS database repositories**

## 🤖 Purpose

This guide helps AI agents, language models, and automated systems understand how to:
- Discover and use repository methods
- Generate correct code using repositories
- Handle parameters and return types properly
- Follow security best practices
- Debug and troubleshoot issues

---

## 📋 Quick Start for AI Agents

### Step 1: Identify the Domain

First, determine which domain the task belongs to:

| Domain | Repository Location | Use When |
|--------|-------------------|----------|
| **Permissions & Security** | `CMIS/PermissionRepository` | User permissions, access control, authentication |
| **Campaigns** | `CMIS/CampaignRepository` | Campaign creation, management, relationships |
| **Contexts** | `CMIS/ContextRepository` | Context search and retrieval |
| **Creative Content** | `CMIS/CreativeRepository` | Briefs, creative assets, validation |
| **Cache & Cleanup** | `CMIS/CacheRepository` | Cache refresh, cleanup operations |
| **Verification** | `CMIS/VerificationRepository` | System tests, verification, analysis |
| **Analytics** | `Analytics/AnalyticsRepository` | Reports, metrics, performance |
| **AI Analytics** | `Analytics/AIAnalyticsRepository` | AI-powered recommendations |
| **Knowledge** | `Knowledge/KnowledgeRepository` | Knowledge base, search, registration |
| **Embeddings** | `Knowledge/EmbeddingRepository` | Vector embeddings, semantic search |
| **Marketing** | `Marketing/MarketingRepository` | Content generation, campaigns |
| **Dev Tasks** | `Dev/DevTaskRepository` | Task automation, orchestration |
| **Operations** | `Operations/OperationsRepository` | Operational tasks, maintenance |
| **Audit** | `Operations/AuditRepository` | Audit logs, purging |
| **Utilities** | `PublicUtilityRepository` | General utilities, cognitive functions |

### Step 2: Find the Right Method

Use this pattern matching guide:

```
I need to...                      → Use Repository → Method
────────────────────────────────────────────────────────────
check a permission                → Permission     → checkPermission()
create a campaign                 → Campaign       → createCampaignWithContext()
search contexts                   → Context        → searchContexts()
generate a brief summary          → Creative       → generateBriefSummary()
cleanup old cache                 → Cache          → cleanupOldCacheEntries()
verify system                     → Verification   → verify*()
get analytics                     → Analytics      → snapshot*()
search knowledge                  → Knowledge      → semanticSearchAdvanced()
generate embeddings               → Embedding      → generateEmbedding()
generate marketing content        → Marketing      → generateCreativeContent()
run automated task                → DevTask        → runDevTask()
cleanup operations                → Operations     → cleanup*()
purge audit logs                  → Audit          → purgeOldAuditLogs()
cognitive analysis                → PublicUtility  → cognitive*()
```

### Step 3: Generate the Code

Use this template:

```php
use App\Repositories\{Domain}\{Repository};

class YourController extends Controller
{
    public function __construct(
        private {Repository} $repository
    ) {}

    public function yourMethod()
    {
        $result = $this->repository->methodName(
            param1: $value1,
            param2: $value2
        );

        // Handle result based on return type
        // Collection, bool, string, object|null, int
    }
}
```

---

## 🎯 Pattern Recognition for AI

### Pattern 1: Permission Checking

**When to use**: Any action requiring authorization

```php
// AI Detection: Keywords like "check", "permission", "authorize", "access"
use App\Repositories\CMIS\PermissionRepository;

if (!$permissions->checkPermission($userId, $orgId, 'action_code')) {
    abort(403);
}
```

### Pattern 2: Data Retrieval

**When to use**: Fetching data, searching, querying

```php
// AI Detection: Keywords like "get", "fetch", "search", "find", "retrieve"
use App\Repositories\CMIS\CampaignRepository;

$campaigns = $campaigns->findRelatedCampaigns($campaignId, 10);

// Result is always Collection - iterate with foreach
foreach ($campaigns as $campaign) {
    // Process each item
}
```

### Pattern 3: Data Creation

**When to use**: Creating new records, generating content

```php
// AI Detection: Keywords like "create", "generate", "register", "insert"
use App\Repositories\Knowledge\KnowledgeRepository;

$knowledgeId = $knowledge->registerKnowledge(
    domain: 'marketing',
    category: 'dev',
    topic: 'Instagram API',
    content: $content,
    tier: 1,
    keywords: ['instagram', 'api', 'integration']
);

// Result is UUID string
```

### Pattern 4: Maintenance Operations

**When to use**: Cleanup, sync, refresh operations

```php
// AI Detection: Keywords like "cleanup", "sync", "refresh", "purge"
use App\Repositories\CMIS\CacheRepository;

$success = $cache->cleanupExpiredSessions();

// Result is boolean
if ($success) {
    // Operation succeeded
}
```

### Pattern 5: Analysis & Reporting

**When to use**: Analytics, reports, verification

```php
// AI Detection: Keywords like "analyze", "report", "verify", "analyze"
use App\Repositories\Analytics\AnalyticsRepository;

$metrics = $analytics->snapshotPerformanceForDays(30);

// Result is Collection
$avgEngagement = $metrics->avg('observed');
```

---

## 🔍 Method Signature Patterns

### Understanding Return Types

```php
// Pattern 1: Returns Collection (for TABLE/SETOF returns)
public function methodName(...): Collection
{
    $results = DB::select('SELECT * FROM function(...)');
    return collect($results);
}

// Pattern 2: Returns bool (for void returns or operations)
public function methodName(...): bool
{
    return DB::statement('SELECT function(...)');
}

// Pattern 3: Returns string (for scalar returns like UUID)
public function methodName(...): string
{
    $result = DB::select('SELECT function(...) as result');
    return $result[0]->result;
}

// Pattern 4: Returns object|null (for JSONB returns)
public function methodName(...): ?object
{
    $results = DB::select('SELECT function(...) as data');
    return $results[0]->data ?? null;
}

// Pattern 5: Returns int (for integer returns)
public function methodName(...): int
{
    $result = DB::select('SELECT function(...) as count');
    return $result[0]->count ?? 0;
}
```

### Parameter Patterns

```php
// String parameters (most common)
methodName(string $param1, string $param2)

// Array parameters (for PostgreSQL arrays)
methodName(array $tags)  // Converted to ARRAY['tag1','tag2']

// Boolean parameters
methodName(bool $includeInactive = false)

// Integer parameters
methodName(int $limit = 10, int $offset = 0)

// Nullable parameters
methodName(?string $domain = null)
```

---

## 🚀 AI Code Generation Examples

### Example 1: Permission Check Before Action

**AI Prompt**: "Check if user can create a campaign, then create it"

**Generated Code**:
```php
use App\Repositories\CMIS\PermissionRepository;
use App\Repositories\CMIS\CampaignRepository;

class CampaignController extends Controller
{
    public function __construct(
        private PermissionRepository $permissions,
        private CampaignRepository $campaigns
    ) {}

    public function store(Request $request)
    {
        // Step 1: Check permission
        if (!$this->permissions->checkPermission(
            auth()->id(),
            $request->input('org_id'),
            'create_campaign'
        )) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Step 2: Create campaign
        $result = $this->campaigns->createCampaignWithContext(
            orgId: $request->input('org_id'),
            offeringId: $request->input('offering_id'),
            segmentId: $request->input('segment_id'),
            campaignName: $request->input('name'),
            framework: $request->input('framework', 'AIDA'),
            tone: $request->input('tone', 'professional'),
            tags: $request->input('tags', [])
        );

        return response()->json($result);
    }
}
```

### Example 2: Knowledge Search and Display

**AI Prompt**: "Search knowledge base for Instagram marketing tips"

**Generated Code**:
```php
use App\Repositories\Knowledge\KnowledgeRepository;

class KnowledgeController extends Controller
{
    public function __construct(
        private KnowledgeRepository $knowledge
    ) {}

    public function search(Request $request)
    {
        $results = $this->knowledge->semanticSearchAdvanced(
            query: $request->input('q', 'Instagram marketing'),
            intent: 'learn',
            category: 'marketing',
            limit: $request->input('limit', 10),
            threshold: 0.3
        );

        // Format results
        return response()->json([
            'total' => $results->count(),
            'results' => $results->map(function ($item) {
                return [
                    'topic' => $item->topic,
                    'content' => substr($item->content, 0, 200) . '...',
                    'similarity' => round($item->combined_score * 100, 2) . '%',
                    'domain' => $item->domain,
                    'category' => $item->category,
                ];
            })
        ]);
    }
}
```

### Example 3: Marketing Content Generation

**AI Prompt**: "Generate 5 creative variants for a summer sale campaign"

**Generated Code**:
```php
use App\Repositories\Marketing\MarketingRepository;

class ContentGeneratorController extends Controller
{
    public function __construct(
        private MarketingRepository $marketing
    ) {}

    public function generateVariants(Request $request)
    {
        $variants = $this->marketing->generateCreativeVariants(
            topic: $request->input('topic', 'Summer Sale 2025'),
            tone: $request->input('tone', 'enthusiastic'),
            variantCount: $request->input('count', 5)
        );

        if (!$variants) {
            return response()->json(['error' => 'Generation failed'], 500);
        }

        return response()->json([
            'success' => true,
            'variants' => $variants
        ]);
    }
}
```

### Example 4: Analytics Dashboard

**AI Prompt**: "Show campaign performance for the last 30 days"

**Generated Code**:
```php
use App\Repositories\Analytics\AnalyticsRepository;

class DashboardController extends Controller
{
    public function __construct(
        private AnalyticsRepository $analytics
    ) {}

    public function performance(Request $request)
    {
        $days = $request->input('days', 30);
        $metrics = $this->analytics->snapshotPerformanceForDays($days);

        // Group by campaign
        $grouped = $metrics->groupBy('campaign_name');

        // Calculate aggregates
        $summary = $grouped->map(function ($campaignMetrics) {
            return [
                'campaign' => $campaignMetrics->first()->campaign_name,
                'metrics' => $campaignMetrics->groupBy('kpi')->map(function ($kpiMetrics, $kpi) {
                    return [
                        'kpi' => $kpi,
                        'current' => $kpiMetrics->last()->observed,
                        'average' => round($kpiMetrics->avg('observed'), 2),
                        'trend' => $kpiMetrics->last()->trend_direction,
                    ];
                })->values()
            ];
        })->values();

        return response()->json($summary);
    }
}
```

### Example 5: Scheduled Cleanup Job

**AI Prompt**: "Create a job to cleanup old data every day"

**Generated Code**:
```php
use App\Repositories\CMIS\CacheRepository;
use App\Repositories\Operations\AuditRepository;
use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    protected $signature = 'cmis:cleanup';
    protected $description = 'Cleanup old data and cache';

    public function __construct(
        private CacheRepository $cache,
        private AuditRepository $audit
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting cleanup...');

        // Cleanup expired sessions
        $this->cache->cleanupExpiredSessions();
        $this->info('✓ Cleaned expired sessions');

        // Cleanup old cache entries
        $this->cache->cleanupOldCacheEntries();
        $this->info('✓ Cleaned old cache entries');

        // Purge old audit logs (90 days retention)
        $deleted = $this->audit->purgeOldAuditLogs(90);
        $this->info("✓ Purged {$deleted} old audit logs");

        $this->info('Cleanup completed successfully!');
        return 0;
    }
}
```

---

## 🧠 AI Decision Tree

```
User Request
    │
    ├─ Contains "permission", "access", "authorize"?
    │   └─ YES → Use PermissionRepository
    │
    ├─ Contains "campaign", "create campaign"?
    │   └─ YES → Use CampaignRepository
    │
    ├─ Contains "search", "find", "lookup" + "context"?
    │   └─ YES → Use ContextRepository
    │
    ├─ Contains "brief", "creative", "asset"?
    │   └─ YES → Use CreativeRepository
    │
    ├─ Contains "cleanup", "cache", "refresh"?
    │   └─ YES → Use CacheRepository
    │
    ├─ Contains "verify", "test", "check system"?
    │   └─ YES → Use VerificationRepository
    │
    ├─ Contains "analytics", "report", "metrics", "performance"?
    │   └─ YES → Use AnalyticsRepository
    │
    ├─ Contains "knowledge", "search knowledge", "learn"?
    │   └─ YES → Use KnowledgeRepository
    │
    ├─ Contains "embedding", "vector", "semantic"?
    │   └─ YES → Use EmbeddingRepository
    │
    ├─ Contains "generate content", "marketing"?
    │   └─ YES → Use MarketingRepository
    │
    ├─ Contains "task", "dev task", "automate"?
    │   └─ YES → Use DevTaskRepository
    │
    ├─ Contains "operations", "sync", "integrate"?
    │   └─ YES → Use OperationsRepository
    │
    ├─ Contains "audit", "logs", "purge"?
    │   └─ YES → Use AuditRepository
    │
    └─ Contains "cognitive", "utility", "report by phase"?
        └─ YES → Use PublicUtilityRepository
```

---

## ⚠️ Common Pitfalls for AI

### Pitfall 1: Wrong Return Type Handling

```php
// ❌ WRONG - Collection methods don't return Collection items directly
$campaign = $repository->findRelatedCampaigns($id);
echo $campaign->name;  // ERROR!

// ✅ CORRECT - Iterate or get first
$campaigns = $repository->findRelatedCampaigns($id);
$first = $campaigns->first();
echo $first->name;  // OK!
```

### Pitfall 2: Forgetting Null Checks

```php
// ❌ WRONG - May cause null pointer error
$summary = $repository->generateBriefSummary($id);
echo $summary->title;  // ERROR if null!

// ✅ CORRECT - Always check nullable returns
$summary = $repository->generateBriefSummary($id);
if ($summary) {
    echo $summary->title;
}
```

### Pitfall 3: Array Parameter Format

```php
// ❌ WRONG - Can't pass array directly to DB
DB::select('SELECT * FROM func(?)', [$array]);

// ✅ CORRECT - Repository handles array conversion
$repository->methodWithArrayParam($array);
```

### Pitfall 4: Not Using Dependency Injection

```php
// ❌ WRONG - Don't use app() or new in controllers
$repo = app(PermissionRepository::class);
$repo = new PermissionRepository();

// ✅ CORRECT - Use constructor injection
public function __construct(
    private PermissionRepository $permissions
) {}
```

### Pitfall 5: Ignoring Boolean Returns

```php
// ❌ WRONG - Not checking success
$repository->cleanupOldCacheEntries();

// ✅ CORRECT - Check and handle failures
if (!$repository->cleanupOldCacheEntries()) {
    Log::error('Cleanup failed');
    throw new Exception('Cleanup operation failed');
}
```

---

## 📊 Success Metrics for AI

When generating code, ensure:

- ✅ **Type Safety**: All parameters have correct types
- ✅ **Null Safety**: Nullable returns are checked before use
- ✅ **Error Handling**: Try-catch blocks for database operations
- ✅ **Dependency Injection**: Repositories injected via constructor
- ✅ **Collection Handling**: Results iterated or accessed correctly
- ✅ **Security**: No raw SQL, only repository methods
- ✅ **Documentation**: PHPDoc comments on generated methods

---

## 🔧 Debugging Guide for AI

### Debug Pattern 1: Method Not Found

**Error**: `Method methodName does not exist`

**Solution**:
1. Check repository file for exact method name
2. Verify you're using the correct repository
3. Check method parameters match signature

### Debug Pattern 2: Wrong Return Type

**Error**: `Call to member function on null`

**Solution**:
1. Check if return type is `object|null`
2. Add null check before accessing properties
3. Verify function was called with correct parameters

### Debug Pattern 3: SQL Error

**Error**: `SQLSTATE[...] error`

**Solution**:
1. Check parameter types match PostgreSQL expectations
2. Verify UUIDs are valid format
3. Ensure arrays are passed correctly
4. Check if function exists in database

### Debug Pattern 4: Type Mismatch

**Error**: `Argument must be of type string, null given`

**Solution**:
1. Check for optional parameters with defaults
2. Ensure required parameters are provided
3. Verify parameter order matches method signature

---

## 🎓 Learning Resources for AI

### Understanding PostgreSQL Functions

Each repository method maps to a PostgreSQL function in `database/schema.sql`:

```sql
-- PostgreSQL function
CREATE FUNCTION cmis.check_permission(
    p_user_id uuid,
    p_org_id uuid,
    p_permission_code text
) RETURNS boolean

-- Maps to PHP method
public function checkPermission(
    string $userId,
    string $orgId,
    string $permissionCode
): bool
```

### Type Mapping Reference

| PostgreSQL Type | PHP Type | Return in PHP |
|----------------|----------|---------------|
| `uuid` | `string` | `string` |
| `text` | `string` | `string` |
| `varchar` | `string` | `string` |
| `integer` | `int` | `int` |
| `bigint` | `int` | `int` |
| `boolean` | `bool` | `bool` |
| `jsonb` | `array` | `object\|null` |
| `timestamp` | N/A | `string` (ISO 8601) |
| `RETURNS TABLE` | N/A | `Collection` |
| `RETURNS SETOF` | N/A | `Collection` |
| `RETURNS void` | N/A | `bool` |
| `text[]` | `array` | `array` |

---

## 🎯 Final Checklist for AI Generated Code

Before outputting generated code, verify:

- [ ] Correct repository imported
- [ ] Constructor injection used
- [ ] Method name matches repository
- [ ] All parameters provided with correct types
- [ ] Named parameters used for clarity
- [ ] Return type handled correctly (Collection/bool/string/object|null/int)
- [ ] Null checks for nullable returns
- [ ] Try-catch for error handling
- [ ] No direct DB calls (only repository methods)
- [ ] PHPDoc comments included
- [ ] Type hints on all parameters
- [ ] Return type declaration present

---

**AI Agent Version**: 1.0.0
**Last Updated**: 2025-01-12
**Compatibility**: Laravel 10+, PHP 8.1+
