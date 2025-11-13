# Quick Reference Guide - CMIS Repositories

> **Fast lookup table for all repository methods**

## 📑 Table of Contents

- [By Use Case](#by-use-case)
- [By Repository](#by-repository)
- [By Return Type](#by-return-type)
- [Common Patterns](#common-patterns)

---

## 🎯 By Use Case

### Authentication & Authorization

| Task | Repository | Method |
|------|------------|--------|
| Check user permission | Permission | `checkPermission($userId, $orgId, $code)` |
| Check permission (TX) | Permission | `checkPermissionWithTransaction($permission)` |
| Init transaction context | Permission | `initTransactionContext($userId, $orgId)` |
| Validate context | Permission | `validateTransactionContext()` |
| Get current user ID | Permission | `getCurrentUserId()` |
| Get current org ID | Permission | `getCurrentOrgId()` |

### Campaign Management

| Task | Repository | Method |
|------|------------|--------|
| Create campaign | Campaign | `createCampaignWithContext(...)` |
| Find related campaigns | Campaign | `findRelatedCampaigns($id, $limit)` |
| Get campaign contexts | Campaign | `getCampaignContexts($id, $includeInactive)` |

### Content & Creative

| Task | Repository | Method |
|------|------------|--------|
| Generate brief summary | Creative | `generateBriefSummary($briefId)` |
| Validate brief | Creative | `validateBriefStructure($brief)` |
| Link brief to content | Creative | `linkBriefToContent($briefId, $contentId)` |
| Refresh creative index | Creative | `refreshCreativeIndex()` |
| Delete unapproved assets | Creative | `autoDeleteUnapprovedAssets()` |
| Search contexts | Context | `searchContexts($query, $type, $limit)` |

### Cache & Maintenance

| Task | Repository | Method |
|------|------------|--------|
| Cleanup sessions | Cache | `cleanupExpiredSessions()` |
| Cleanup cache | Cache | `cleanupOldCacheEntries()` |
| Refresh fields cache | Cache | `refreshRequiredFieldsCache()` |
| Refresh dashboard | Cache | `refreshDashboardMetrics()` |
| Sync social metrics | Cache | `syncSocialMetrics()` |
| Verify cache | Cache | `verifyCacheAutomation()` |

### Analytics & Reporting

| Task | Repository | Method |
|------|------------|--------|
| Get migrations | Analytics | `reportMigrations()` |
| Run AI query | Analytics | `runAiQuery($orgId, $prompt)` |
| Performance snapshot | Analytics | `snapshotPerformance()` |
| Performance (N days) | Analytics | `snapshotPerformanceForDays($days)` |
| AI recommendations | AIAnalytics | `recommendFocus()` |

### Knowledge Management

| Task | Repository | Method |
|------|------------|--------|
| Register knowledge | Knowledge | `registerKnowledge(...)` |
| Analyze knowledge | Knowledge | `autoAnalyzeKnowledge($query, ...)` |
| Retrieve knowledge | Knowledge | `autoRetrieveKnowledge($query, ...)` |
| Smart context load | Knowledge | `smartContextLoader($query, ...)` |
| System report | Knowledge | `generateSystemReport()` |
| Semantic analysis | Knowledge | `semanticAnalysis()` |
| Advanced search | Knowledge | `semanticSearchAdvanced(...)` |
| Cleanup embeddings | Knowledge | `cleanupOldEmbeddings()` |
| Verify system | Knowledge | `verifyInstallation()` |

### Vector Embeddings

| Task | Repository | Method |
|------|------------|--------|
| Generate embedding | Embedding | `generateEmbedding($text)` |
| Generate mock | Embedding | `generateMockEmbedding($text)` |
| Batch update | Embedding | `batchUpdateEmbeddings($size, $category)` |
| Update single | Embedding | `updateSingleEmbedding($knowledgeId)` |

### Marketing Content

| Task | Repository | Method |
|------|------------|--------|
| Generate assets | Marketing | `generateCampaignAssets($taskId)` |
| Generate content | Marketing | `generateCreativeContent(...)` |
| Generate variants | Marketing | `generateCreativeVariants(...)` |
| Video scenario | Marketing | `generateVideoScenario($taskId)` |
| Visual concepts | Marketing | `generateVisualConcepts($taskId)` |
| Visual scenarios | Marketing | `generateVisualScenarios($topic, $tone)` |
| Voice script | Marketing | `generateVoiceScript($scenarioId)` |

### Development Tasks

| Task | Repository | Method |
|------|------------|--------|
| Create task | DevTask | `createDevTask(...)` |
| Auto context loader | DevTask | `autoContextTaskLoader(...)` |
| Prepare execution | DevTask | `prepareContextExecution(...)` |
| Run dev task | DevTask | `runDevTask($prompt)` |
| Run marketing task | DevTask | `runMarketingTask($prompt)` |
| Search marketing KB | DevTask | `searchMarketingKnowledge($prompt)` |

### Operations

| Task | Repository | Method |
|------|------------|--------|
| Cleanup assets | Operations | `cleanupStaleAssets()` |
| Generate AI summary | Operations | `generateAiSummary()` |
| Normalize metrics | Operations | `normalizeMetrics()` |
| Refresh AI insights | Operations | `refreshAiInsights()` |
| Sync integrations | Operations | `syncIntegrations()` |
| Purge audit logs | Audit | `purgeOldAuditLogs($days)` |

### System Verification

| Task | Repository | Method |
|------|------------|--------|
| Verify improvements | Verification | `verifyOptionalImprovements()` |
| Verify phase 1 | Verification | `verifyPhase1Fixes()` |
| Verify phase 2 | Verification | `verifyPhase2Permissions()` |
| Verify RBAC | Verification | `verifyRbacPolicies()` |
| Verify RLS | Verification | `verifyRlsFixes()` |
| Analyze tables | Verification | `analyzeTableSizes()` |

### Cognitive Functions

| Task | Repository | Method |
|------|------------|--------|
| Cognitive report | PublicUtility | `cognitiveConsoleReport($mode)` |
| Cognitive feedback | PublicUtility | `cognitiveFeedbackLoop()` |
| Cognitive learning | PublicUtility | `cognitiveLearningLoop()` |
| Health report | PublicUtility | `generateCognitiveHealthReport()` |
| Update trends | PublicUtility | `updateCognitiveTrends()` |
| Log vitality | PublicUtility | `logCognitiveVitality()` |

### Report Management

| Task | Repository | Method |
|------|------------|--------|
| All summaries | PublicUtility | `getAllReportSummaries($length)` |
| Latest official | PublicUtility | `getLatestOfficialReport($domain)` |
| Latest by phases | PublicUtility | `getLatestReportsByAllPhases()` |
| Official reports | PublicUtility | `getOfficialReports()` |
| Summary by phase | PublicUtility | `getReportSummaryByPhase($phase, $length)` |
| Reports by phase | PublicUtility | `getReportsByPhase($phase)` |

### Knowledge Utilities

| Task | Repository | Method |
|------|------------|--------|
| Load by priority | PublicUtility | `loadContextByPriority($domain, ...)` |
| Reconstruct | PublicUtility | `reconstructKnowledge($parentId)` |
| Register chunked | PublicUtility | `registerChunkedKnowledge(...)` |
| Register knowledge | PublicUtility | `registerKnowledge(...)` |
| Update chunk | PublicUtility | `updateKnowledgeChunk(...)` |
| Search cognitive | PublicUtility | `searchCognitiveKnowledge(...)` |
| Search simple | PublicUtility | `searchCognitiveKnowledgeSimple(...)` |

---

## 📦 By Repository

### PermissionRepository
```php
App\Repositories\CMIS\PermissionRepository

checkPermission(string $userId, string $orgId, string $code): bool
checkPermissionWithTransaction(string $permission): bool
initTransactionContext(string $userId, string $orgId): bool
validateTransactionContext(): Collection
getCurrentUserId(): ?string
getCurrentUserIdFromTransaction(): ?string
getCurrentOrgId(): ?string
getCurrentOrgIdFromTransaction(): ?string
refreshPermissionsCache(): bool
testSecurityContext(): Collection
```

### CampaignRepository
```php
App\Repositories\CMIS\CampaignRepository

createCampaignWithContext(
    string $orgId,
    string $offeringId,
    string $segmentId,
    string $campaignName,
    string $framework,
    string $tone,
    array $tags
): Collection

findRelatedCampaigns(string $campaignId, int $limit = 10): Collection
getCampaignContexts(string $campaignId, bool $includeInactive = false): Collection
```

### ContextRepository
```php
App\Repositories\CMIS\ContextRepository

searchContexts(
    string $searchQuery,
    ?string $contextType = null,
    int $limit = 20
): Collection
```

### CreativeRepository
```php
App\Repositories\CMIS\CreativeRepository

generateBriefSummary(string $briefId): ?object
validateBriefStructure(array $brief): bool
linkBriefToContent(string $briefId, string $contentId): bool
refreshCreativeIndex(): bool
autoDeleteUnapprovedAssets(): bool
```

### CacheRepository
```php
App\Repositories\CMIS\CacheRepository

cleanupExpiredSessions(): bool
cleanupOldCacheEntries(): bool
refreshRequiredFieldsCache(): bool
refreshRequiredFieldsCacheWithMetrics(): bool
verifyCacheAutomation(): Collection
refreshDashboardMetrics(): bool
syncSocialMetrics(): bool
```

### VerificationRepository
```php
App\Repositories\CMIS\VerificationRepository

verifyOptionalImprovements(): string
verifyPhase1Fixes(): Collection
verifyPhase2Permissions(): Collection
verifyRbacPolicies(): Collection
verifyRlsFixes(): Collection
analyzeTableSizes(): Collection
```

### AnalyticsRepository
```php
App\Repositories\Analytics\AnalyticsRepository

reportMigrations(): Collection
runAiQuery(string $orgId, string $prompt): bool
snapshotPerformance(): Collection
snapshotPerformanceForDays(int $snapshotDays = 30): Collection
```

### AIAnalyticsRepository
```php
App\Repositories\Analytics\AIAnalyticsRepository

recommendFocus(): ?object
```

### KnowledgeRepository
```php
App\Repositories\Knowledge\KnowledgeRepository

registerKnowledge(
    string $domain,
    string $category,
    string $topic,
    string $content,
    int $tier = 2,
    array $keywords = []
): string

autoAnalyzeKnowledge(
    string $query,
    ?string $domain = null,
    string $category = 'dev',
    int $maxBatches = 5,
    int $batchLimit = 20
): ?object

autoRetrieveKnowledge(
    string $query,
    ?string $domain = null,
    string $category = 'dev',
    int $maxBatches = 5,
    int $batchLimit = 20
): Collection

smartContextLoader(
    string $query,
    ?string $domain = null,
    string $category = 'dev',
    int $tokenLimit = 5000
): ?object

generateSystemReport(): ?object
semanticAnalysis(): Collection

semanticSearchAdvanced(
    string $query,
    ?string $intent = null,
    ?string $direction = null,
    ?string $purpose = null,
    ?string $category = null,
    int $limit = 10,
    float $threshold = 0.3
): Collection

cleanupOldEmbeddings(): bool
verifyInstallation(): ?object
```

### EmbeddingRepository
```php
App\Repositories\Knowledge\EmbeddingRepository

generateEmbedding(string $text): string
generateMockEmbedding(string $text): string
batchUpdateEmbeddings(int $batchSize = 100, ?string $category = null): ?object
updateSingleEmbedding(string $knowledgeId): ?object
```

### MarketingRepository
```php
App\Repositories\Marketing\MarketingRepository

generateCampaignAssets(string $taskId): ?object

generateCreativeContent(
    string $topic,
    string $goal = 'awareness',
    string $tone = 'ملهم',
    int $length = 3
): ?object

generateCreativeVariants(
    string $topic,
    string $tone,
    int $variantCount = 3
): ?object

generateVideoScenario(string $taskId): ?object
generateVisualConcepts(string $taskId): ?object
generateVisualScenarios(string $topic, string $tone): ?object
generateVoiceScript(string $scenarioId): ?object
```

### DevTaskRepository
```php
App\Repositories\Dev\DevTaskRepository

createDevTask(
    string $name,
    string $description,
    string $scopeCode,
    array $executionPlan,
    int $priority = 3
): string

autoContextTaskLoader(
    string $prompt,
    ?string $domain = null,
    string $category = 'dev',
    string $scopeCode = 'system_dev',
    int $priority = 3,
    int $tokenLimit = 5000
): ?object

prepareContextExecution(
    string $prompt,
    ?string $domain = null,
    string $category = 'dev',
    string $scopeCode = 'system_dev',
    int $priority = 3
): ?object

runDevTask(string $prompt): ?object
runMarketingTask(string $prompt): ?object
runMarketingTaskImproved(string $prompt): ?object
searchMarketingKnowledge(string $prompt): ?object
```

### OperationsRepository
```php
App\Repositories\Operations\OperationsRepository

cleanupStaleAssets(): bool
generateAiSummary(): Collection
normalizeMetrics(): bool
refreshAiInsights(): bool
syncIntegrations(): bool
```

### AuditRepository
```php
App\Repositories\Operations\AuditRepository

purgeOldAuditLogs(int $retentionDays = 90): int
```

### StagingRepository
```php
App\Repositories\Staging\StagingRepository

generateBriefSummaryLegacy(string $briefId): ?object
refreshCreativeIndexLegacy(): bool
validateBriefStructureLegacy(array $brief): bool
```

### PublicUtilityRepository
```php
App\Repositories\PublicUtilityRepository

autoAnalyzeKnowledge(): bool
autoSnapshotDiff(): bool
cognitiveConsoleReport(string $mode = 'summary'): Collection
cognitiveFeedbackLoop(): bool
cognitiveLearningLoop(): bool
computeEpistemicDelta(): bool

createDevTask(
    string $name,
    string $description,
    string $scopeCode,
    array $executionPlan,
    int $priority = 3
): string

generateCognitiveHealthReport(): bool
getAllReportSummaries(int $length = 500): Collection
getLatestOfficialReport(string $domain): Collection
getLatestReportsByAllPhases(): Collection
getOfficialReports(): Collection
getReportSummaryByPhase(string $phase, int $length = 500): Collection
getReportsByPhase(string $phase): Collection

loadContextByPriority(
    string $domain,
    ?string $category = null,
    int $maxTokens = 5000
): Collection

logCognitiveVitality(): bool
reconstructKnowledge(string $parentId): string

registerChunkedKnowledge(
    string $domain,
    string $category,
    string $topic,
    string $content,
    int $chunkSize = 2000
): string

registerKnowledge(
    string $domain,
    string $category,
    string $topic,
    string $content,
    int $tier = 2,
    array $keywords = []
): string

runAutoPredictiveTrigger(): bool
scheduledCognitiveTrendUpdate(): bool

searchCognitiveKnowledge(
    string $query,
    ?string $domain = null,
    string $category = 'dev',
    int $batchLimit = 20,
    int $offset = 0
): Collection

searchCognitiveKnowledgeSimple(
    string $query,
    int $batchLimit = 25,
    int $offset = 0
): Collection

updateCognitiveTrends(): bool
updateKnowledgeChunk(string $parentId, int $partIndex, string $newContent): bool
```

---

## 🎨 By Return Type

### Returns: Collection

**Analytics**
- `reportMigrations()`
- `snapshotPerformance()`
- `snapshotPerformanceForDays($days)`

**Campaign**
- `createCampaignWithContext(...)`
- `findRelatedCampaigns($id, $limit)`
- `getCampaignContexts($id, $includeInactive)`

**Context**
- `searchContexts($query, $type, $limit)`

**Knowledge**
- `autoRetrieveKnowledge(...)`
- `semanticAnalysis()`
- `semanticSearchAdvanced(...)`

**Operations**
- `generateAiSummary()`

**Permission**
- `validateTransactionContext()`
- `testSecurityContext()`

**PublicUtility**
- `cognitiveConsoleReport($mode)`
- `getAllReportSummaries($length)`
- `getLatestOfficialReport($domain)`
- `getLatestReportsByAllPhases()`
- `getOfficialReports()`
- `getReportSummaryByPhase($phase, $length)`
- `getReportsByPhase($phase)`
- `loadContextByPriority($domain, $category, $maxTokens)`
- `searchCognitiveKnowledge(...)`
- `searchCognitiveKnowledgeSimple(...)`

**Verification**
- `verifyPhase1Fixes()`
- `verifyPhase2Permissions()`
- `verifyRbacPolicies()`
- `verifyRlsFixes()`
- `analyzeTableSizes()`
- `verifyCacheAutomation()`

### Returns: bool

**Cache**: All cleanup/refresh methods
**Creative**: `validateBriefStructure()`, `linkBriefToContent()`, `refreshCreativeIndex()`
**Knowledge**: `cleanupOldEmbeddings()`
**Operations**: All operational methods
**Permission**: `checkPermission()`, `checkPermissionWithTransaction()`, `initTransactionContext()`
**PublicUtility**: Most utility functions

### Returns: string (UUID)

**DevTask**: `createDevTask(...)`
**Knowledge**: `registerKnowledge(...)`
**PublicUtility**: `createDevTask(...)`, `registerKnowledge(...)`, `registerChunkedKnowledge(...)`
**Embedding**: `generateEmbedding($text)`, `generateMockEmbedding($text)`

### Returns: object|null (JSONB)

**AIAnalytics**: `recommendFocus()`
**Creative**: `generateBriefSummary($id)`
**DevTask**: All run/prepare methods
**Embedding**: `batchUpdateEmbeddings()`, `updateSingleEmbedding()`
**Knowledge**: `autoAnalyzeKnowledge()`, `smartContextLoader()`, `generateSystemReport()`, `verifyInstallation()`
**Marketing**: All generate methods
**Staging**: `generateBriefSummaryLegacy()`

### Returns: int

**Audit**: `purgeOldAuditLogs($days)`

### Returns: ?string (nullable)

**Permission**: `getCurrentUserId()`, `getCurrentUserIdFromTransaction()`, `getCurrentOrgId()`, `getCurrentOrgIdFromTransaction()`
**PublicUtility**: `reconstructKnowledge($parentId)`
**Verification**: `verifyOptionalImprovements()`

---

## 🔥 Common Patterns

### Pattern: Check Permission Then Act

```php
if ($permissions->checkPermission($userId, $orgId, 'action')) {
    // Perform action
}
```

### Pattern: Search and Iterate

```php
$results = $repository->searchMethod(...);
foreach ($results as $item) {
    // Process item
}
```

### Pattern: Generate Content

```php
$content = $marketing->generateCreativeContent(...);
if ($content) {
    // Use content
}
```

### Pattern: Cleanup Job

```php
$cache->cleanupExpiredSessions();
$cache->cleanupOldCacheEntries();
$audit->purgeOldAuditLogs(90);
```

### Pattern: Knowledge Search

```php
$results = $knowledge->semanticSearchAdvanced(
    query: $query,
    category: 'marketing',
    limit: 10
);
```

---

**Last Updated**: 2025-01-12
**Total Methods**: 100+
