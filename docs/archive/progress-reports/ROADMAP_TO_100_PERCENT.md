# CMIS Roadmap to 100% Completion

**Current Status**: 95%+ (232/240 hours)
**Remaining Work**: 8 hours
**Target**: 100% (240/240 hours)
**Date**: 2025-11-16

---

## Executive Summary

This document outlines all remaining work required to achieve 100% implementation completion for CMIS (Cognitive Marketing Intelligence Suite). The system is currently production-ready at 95%+ completion. The remaining 8 hours of work focuses on polish, optimization, and comprehensive documentation.

### Remaining Work Distribution

| Category | Hours | Priority | Status |
|----------|-------|----------|--------|
| Phase 2: UI Components | 2h | Medium | Optional |
| Phase 3: GPT Polish | 1h | Low | Optional |
| Phase 4: Advanced Testing | 2h | Medium | Optional |
| Phase 5: Documentation | 3h | High | Recommended |
| **TOTAL** | **8h** | | |

---

## Phase 2: Core Features - UI Components (2 hours)

**Current**: 95% Complete (75/79 hours)
**Target**: 97% Complete (77/79 hours)
**Remaining**: 2 hours

### 2.1: Content Plan Management UI (1 hour)

#### Objective
Create a Vue.js component for managing content plans with workflows (approve/reject/publish).

#### Requirements

**Component**: `resources/js/components/ContentPlanManager.vue`

**Features**:
- List view with filtering (status, campaign, date range)
- Create/Edit form with validation
- Workflow actions (Approve, Reject, Publish)
- Status badges with color coding
- Integration with ContentPlanService
- Loading states and error handling

**Acceptance Criteria**:
- [ ] Component displays content plans in a table/card layout
- [ ] Filter by status, campaign, and date range
- [ ] Create new content plan with validation
- [ ] Edit existing content plan
- [ ] Approve/Reject workflow with reason input
- [ ] Publish content plan
- [ ] Loading states during API calls
- [ ] Error messages display properly
- [ ] Responsive design (mobile-friendly)

**API Integration**:
```javascript
// Use existing API client
api.contentPlans.list({ status: 'draft', campaign_id: '...' })
api.contentPlans.create(data)
api.contentPlans.update(planId, data)
api.contentPlans.approve(planId)
api.contentPlans.reject(planId, reason)
api.contentPlans.publish(planId)
```

**Component Structure**:
```vue
<template>
  <div class="content-plan-manager">
    <!-- Filters -->
    <div class="filters">
      <select v-model="filters.status">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
      </select>
      <!-- Campaign filter, date range -->
    </div>

    <!-- Content Plans List -->
    <div class="plans-list">
      <div v-for="plan in plans" :key="plan.id" class="plan-card">
        <h3>{{ plan.name }}</h3>
        <span :class="statusClass(plan.status)">{{ plan.status }}</span>

        <!-- Actions -->
        <div class="actions">
          <button @click="editPlan(plan)">Edit</button>
          <button v-if="plan.status === 'pending'" @click="approvePlan(plan)">
            Approve
          </button>
          <button v-if="plan.status === 'pending'" @click="rejectPlan(plan)">
            Reject
          </button>
          <button v-if="plan.status === 'approved'" @click="publishPlan(plan)">
            Publish
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <modal v-if="showModal" @close="showModal = false">
      <content-plan-form
        :plan="selectedPlan"
        @submit="savePlan"
      />
    </modal>
  </div>
</template>
```

**Time Breakdown**:
- Component structure and layout: 20 minutes
- API integration: 15 minutes
- Workflow actions (approve/reject/publish): 15 minutes
- Styling and responsiveness: 10 minutes

### 2.2: Organization Market Management UI (1 hour)

#### Objective
Create a Vue.js component for managing organization markets with ROI tracking.

#### Requirements

**Component**: `resources/js/components/OrgMarketManager.vue`

**Features**:
- List active markets with priority levels
- Add new market from available markets
- Edit market settings (priority, budget, status)
- Remove market from organization
- ROI calculator
- Market statistics display

**Acceptance Criteria**:
- [ ] Display active markets with priority and status
- [ ] Show available markets to add
- [ ] Add market with configuration (priority, budget, status)
- [ ] Edit market settings
- [ ] Remove market with confirmation
- [ ] Calculate and display ROI
- [ ] Show market statistics (campaigns, spend, ROI)
- [ ] Validation on all inputs
- [ ] Loading and error states

**API Integration**:
```javascript
api.markets.list({ status: 'active' })
api.markets.available()
api.markets.add({ market_id, priority_level, investment_budget })
api.markets.update(marketId, data)
api.markets.remove(marketId)
api.markets.calculateRoi(marketId, revenue)
api.markets.stats()
```

**Component Structure**:
```vue
<template>
  <div class="org-market-manager">
    <!-- Active Markets -->
    <div class="active-markets">
      <h2>Active Markets</h2>
      <div v-for="market in activeMarkets" :key="market.market_id" class="market-card">
        <div class="market-info">
          <h3>{{ market.name }}</h3>
          <span class="priority-badge">Priority: {{ market.priority_level }}</span>
          <span :class="statusClass(market.status)">{{ market.status }}</span>
        </div>

        <div class="market-stats">
          <div class="stat">
            <label>Investment Budget:</label>
            <span>{{ formatCurrency(market.investment_budget) }}</span>
          </div>
          <div class="stat">
            <label>Campaigns:</label>
            <span>{{ market.campaigns_count }}</span>
          </div>
        </div>

        <div class="market-actions">
          <button @click="editMarket(market)">Edit</button>
          <button @click="calculateRoi(market)">Calculate ROI</button>
          <button @click="removeMarket(market)" class="danger">Remove</button>
        </div>
      </div>
    </div>

    <!-- Add Market -->
    <div class="add-market-section">
      <h2>Add Market</h2>
      <select v-model="selectedMarket" @change="loadMarketDetails">
        <option value="">Select a market...</option>
        <option v-for="market in availableMarkets" :key="market.id" :value="market.id">
          {{ market.name }}
        </option>
      </select>

      <div v-if="selectedMarket" class="market-config">
        <input v-model.number="newMarket.priority_level"
               type="number" min="1" max="10"
               placeholder="Priority (1-10)">
        <input v-model.number="newMarket.investment_budget"
               type="number"
               placeholder="Investment Budget">
        <select v-model="newMarket.status">
          <option value="active">Active</option>
          <option value="testing">Testing</option>
        </select>
        <button @click="addMarket">Add Market</button>
      </div>
    </div>

    <!-- ROI Calculator Modal -->
    <modal v-if="showRoiCalculator" @close="showRoiCalculator = false">
      <roi-calculator
        :market="selectedMarket"
        @calculate="handleRoiCalculation"
      />
    </modal>
  </div>
</template>
```

**Time Breakdown**:
- Component structure and layout: 20 minutes
- API integration: 15 minutes
- ROI calculator: 15 minutes
- Styling and validation: 10 minutes

---

## Phase 3: GPT Interface - Final Polish (1 hour)

**Current**: 92% Complete (32/35 hours)
**Target**: 95% Complete (33/35 hours)
**Remaining**: 1 hour

### 3.1: Enhanced Error Messages and Logging (30 minutes)

#### Objective
Improve error messages and add comprehensive logging for debugging.

#### Tasks

**1. Enhance Error Messages** (15 minutes)

**File**: `app/Http/Controllers/GPT/GPTController.php`

Add user-friendly error messages for common scenarios:

```php
// Example: Enhanced error handling
try {
    $campaign = Campaign::findOrFail($campaignId);
} catch (ModelNotFoundException $e) {
    return $this->error(
        'Campaign not found',
        [
            'campaign_id' => $campaignId,
            'suggestion' => 'Please verify the campaign ID and try again.',
            'help_url' => '/docs/campaigns',
        ],
        404
    );
}

// Database connection errors
catch (QueryException $e) {
    \Log::error('Database error in GPT endpoint', [
        'error' => $e->getMessage(),
        'endpoint' => $request->path(),
        'user_id' => $request->user()->user_id,
    ]);

    return $this->error(
        'Database error occurred',
        ['detail' => 'Please try again in a few moments.'],
        503
    );
}

// Rate limit errors
catch (ThrottleRequestsException $e) {
    return $this->error(
        'Rate limit exceeded',
        [
            'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
            'suggestion' => 'Please wait before making another request.',
        ],
        429
    );
}
```

**2. Add Performance Logging** (15 minutes)

Track API response times and AI generation times:

```php
// Add to GPTController methods
public function conversationMessage(Request $request): JsonResponse
{
    $startTime = microtime(true);

    try {
        // ... existing code ...

        $duration = microtime(true) - $startTime;
        \Log::info('GPT conversation message processed', [
            'session_id' => $sessionId,
            'user_id' => $request->user()->user_id,
            'duration_ms' => round($duration * 1000, 2),
            'tokens_used' => $aiResult['tokens']['total'] ?? 0,
        ]);

        return $this->success([
            'response' => $aiResponse,
            'session_id' => $sessionId,
            'tokens_used' => $aiResult['tokens']['total'] ?? 0,
            'processing_time_ms' => round($duration * 1000, 2),
        ]);
    } catch (\Exception $e) {
        $duration = microtime(true) - $startTime;
        \Log::error('GPT conversation message failed', [
            'session_id' => $sessionId,
            'user_id' => $request->user()->user_id,
            'duration_ms' => round($duration * 1000, 2),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // ... fallback response ...
    }
}
```

**Acceptance Criteria**:
- [ ] All error types have specific, user-friendly messages
- [ ] Errors include suggestions for resolution
- [ ] Performance metrics logged for all GPT endpoints
- [ ] Error logs include full context (user_id, session_id, etc.)
- [ ] Response times tracked and logged

### 3.2: API Response Optimization (30 minutes)

#### Objective
Optimize API responses for better performance and consistency.

#### Tasks

**1. Add Response Caching** (15 minutes)

Cache frequently accessed data:

```php
// In GPTController
public function listCampaigns(Request $request): JsonResponse
{
    $cacheKey = "gpt:campaigns:{$request->user()->current_org_id}:{$status}:{$limit}";

    $campaigns = Cache::remember($cacheKey, 300, function () use ($request, $status, $limit) {
        $query = Campaign::where('org_id', $request->user()->current_org_id)
            ->with(['contentPlans'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    });

    return $this->success($campaigns->map(fn($c) => $this->formatCampaign($c)));
}
```

**2. Add Response Compression** (15 minutes)

Add pagination metadata and optimize response size:

```php
// Enhanced success response method
private function success($data, string $message = null, int $status = 200): JsonResponse
{
    $response = ['success' => true];

    if ($message) {
        $response['message'] = $message;
    }

    // Add metadata for collections
    if (is_array($data) && isset($data['items'])) {
        $response['data'] = $data['items'];
        $response['meta'] = [
            'total' => $data['total'] ?? count($data['items']),
            'count' => count($data['items']),
            'timestamp' => now()->toISOString(),
        ];
    } else {
        $response['data'] = $data;
        $response['meta'] = [
            'timestamp' => now()->toISOString(),
        ];
    }

    return response()->json($response, $status);
}
```

**Acceptance Criteria**:
- [ ] Frequently accessed endpoints use cache
- [ ] Cache keys include all relevant parameters
- [ ] Cache TTL appropriate for data type (5 min for campaigns)
- [ ] Response metadata includes timestamps
- [ ] Collections include count and total

---

## Phase 4: GPT Interface Completion (2 hours)

**Current**: 85% Complete (23/27 hours)
**Target**: 93% Complete (25/27 hours)
**Remaining**: 2 hours

### 4.1: Advanced Action Handlers (1 hour)

#### Objective
Implement complex operation handlers for multi-step GPT actions.

#### Tasks

**1. Bulk Operation Handler** (30 minutes)

**File**: `app/Http/Controllers/GPT/GPTController.php`

Add endpoint for bulk operations:

```php
/**
 * Execute bulk operations
 */
public function bulkOperation(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'operation' => 'required|in:approve,reject,publish,archive',
        'resource_type' => 'required|in:content_plans,campaigns',
        'resource_ids' => 'required|array|min:1|max:50',
        'reason' => 'sometimes|required_if:operation,reject|string',
    ]);

    if ($validator->fails()) {
        return $this->error('Validation failed', $validator->errors(), 422);
    }

    try {
        $results = [];
        $errors = [];

        foreach ($request->input('resource_ids') as $resourceId) {
            try {
                $result = $this->executeBulkOperation(
                    $request->input('resource_type'),
                    $resourceId,
                    $request->input('operation'),
                    $request->input('reason')
                );

                $results[] = [
                    'id' => $resourceId,
                    'status' => 'success',
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                \Log::warning('Bulk operation failed for resource', [
                    'resource_id' => $resourceId,
                    'operation' => $request->input('operation'),
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'id' => $resourceId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->success([
            'successful' => count($results),
            'failed' => count($errors),
            'results' => $results,
            'errors' => $errors,
        ], 'Bulk operation completed');

    } catch (\Exception $e) {
        \Log::error('Bulk operation failed', [
            'operation' => $request->input('operation'),
            'error' => $e->getMessage(),
        ]);

        return $this->error('Bulk operation failed', ['detail' => $e->getMessage()], 500);
    }
}

/**
 * Execute single bulk operation
 */
private function executeBulkOperation(
    string $resourceType,
    string $resourceId,
    string $operation,
    ?string $reason = null
): array {
    if ($resourceType === 'content_plans') {
        $plan = ContentPlan::findOrFail($resourceId);

        switch ($operation) {
            case 'approve':
                $this->contentPlanService->approve($plan);
                break;
            case 'reject':
                $this->contentPlanService->reject($plan, $reason);
                break;
            case 'publish':
                $this->contentPlanService->publish($plan);
                break;
            case 'archive':
                $plan->update(['status' => 'archived']);
                break;
        }

        return [
            'id' => $plan->plan_id,
            'name' => $plan->name,
            'status' => $plan->status,
        ];
    }

    throw new \Exception("Unsupported resource type: {$resourceType}");
}
```

**2. Smart Search Handler** (30 minutes)

Add intelligent search across multiple resources:

```php
/**
 * Smart search across resources
 */
public function smartSearch(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'query' => 'required|string|min:2',
        'resources' => 'nullable|array',
        'resources.*' => 'in:campaigns,content_plans,knowledge,markets',
        'limit' => 'nullable|integer|min:1|max:50',
    ]);

    if ($validator->fails()) {
        return $this->error('Validation failed', $validator->errors(), 422);
    }

    try {
        $query = $request->input('query');
        $resources = $request->input('resources', ['campaigns', 'content_plans', 'knowledge']);
        $limit = $request->input('limit', 10);
        $orgId = $request->user()->current_org_id;

        $results = [];

        // Search campaigns
        if (in_array('campaigns', $resources)) {
            $campaigns = Campaign::where('org_id', $orgId)
                ->where(function($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                      ->orWhere('description', 'ILIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn($c) => [
                    'type' => 'campaign',
                    'id' => $c->campaign_id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'status' => $c->status,
                    'relevance_score' => $this->calculateRelevance($query, $c->name, $c->description),
                ]);

            $results['campaigns'] = $campaigns;
        }

        // Search content plans
        if (in_array('content_plans', $resources)) {
            $contentPlans = ContentPlan::where('org_id', $orgId)
                ->where(function($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                      ->orWhere('description', 'ILIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn($p) => [
                    'type' => 'content_plan',
                    'id' => $p->plan_id,
                    'name' => $p->name,
                    'content_type' => $p->content_type,
                    'status' => $p->status,
                    'relevance_score' => $this->calculateRelevance($query, $p->name, $p->description),
                ]);

            $results['content_plans'] = $contentPlans;
        }

        // Search knowledge base with semantic search
        if (in_array('knowledge', $resources)) {
            $knowledgeItems = $this->knowledgeService->semanticSearch(
                $query,
                $orgId,
                $limit
            )->map(fn($k) => [
                'type' => 'knowledge',
                'id' => $k->id,
                'title' => $k->title,
                'content_type' => $k->content_type,
                'relevance_score' => 1 - ($k->distance ?? 0.5),
            ]);

            $results['knowledge'] = $knowledgeItems;
        }

        // Sort all results by relevance
        $allResults = collect($results)->flatten(1)->sortByDesc('relevance_score')->take($limit);

        return $this->success([
            'query' => $query,
            'total_results' => $allResults->count(),
            'results' => $allResults->values(),
            'by_type' => array_map(fn($r) => count($r), $results),
        ]);

    } catch (\Exception $e) {
        \Log::error('Smart search failed', [
            'query' => $request->input('query'),
            'error' => $e->getMessage(),
        ]);

        return $this->error('Search failed', ['detail' => $e->getMessage()], 500);
    }
}

/**
 * Calculate text relevance score
 */
private function calculateRelevance(string $query, string $title, ?string $description = ''): float
{
    $query = strtolower($query);
    $title = strtolower($title);
    $description = strtolower($description ?? '');

    $score = 0.0;

    // Exact match in title
    if (strpos($title, $query) !== false) {
        $score += 1.0;
    }

    // Partial match in title
    $queryWords = explode(' ', $query);
    foreach ($queryWords as $word) {
        if (strpos($title, $word) !== false) {
            $score += 0.5;
        }
        if (strpos($description, $word) !== false) {
            $score += 0.2;
        }
    }

    return min($score, 1.0);
}
```

**Routes to Add**:
```php
// In routes/api.php
Route::post('/gpt/bulk-operation', [GPTController::class, 'bulkOperation']);
Route::post('/gpt/search', [GPTController::class, 'smartSearch']);
```

**Acceptance Criteria**:
- [ ] Bulk operations support approve/reject/publish/archive
- [ ] Bulk operations handle partial failures gracefully
- [ ] Smart search queries campaigns, content plans, and knowledge base
- [ ] Results sorted by relevance score
- [ ] Search supports fuzzy matching
- [ ] Both endpoints have comprehensive error handling

### 4.2: Performance Testing (1 hour)

#### Objective
Create performance and load tests for GPT endpoints.

#### Tasks

**1. Performance Test Suite** (30 minutes)

**File**: `tests/Performance/GPTPerformanceTest.php`

```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Core\User;
use App\Models\Core\Org;
use App\Models\Strategic\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class GPTPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'current_org_id' => $this->org->org_id,
        ]);
        $this->actingAs($this->user);

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_responds_to_context_request_within_acceptable_time()
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/gpt/context');

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Context endpoint took {$duration}ms (expected < 200ms)");
    }

    /** @test */
    public function it_lists_campaigns_efficiently_with_cache()
    {
        Campaign::factory()->count(50)->create(['org_id' => $this->org->org_id]);

        // First request (uncached)
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/gpt/campaigns');
        $duration1 = (microtime(true) - $startTime) * 1000;

        $response1->assertStatus(200);
        $this->assertLessThan(1000, $duration1, "Uncached campaign list took {$duration1}ms (expected < 1000ms)");

        // Second request (cached)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/gpt/campaigns');
        $duration2 = (microtime(true) - $startTime) * 1000;

        $response2->assertStatus(200);
        $this->assertLessThan(100, $duration2, "Cached campaign list took {$duration2}ms (expected < 100ms)");
        $this->assertLessThan($duration1 / 2, $duration2, "Cached request should be significantly faster");
    }

    /** @test */
    public function it_handles_concurrent_conversation_requests()
    {
        $session = $this->getJson('/api/gpt/conversation/session');
        $sessionId = $session->json('data.session_id');

        $times = [];
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = $this->postJson('/api/gpt/conversation/message', [
                'session_id' => $sessionId,
                'message' => "Test message {$i}",
            ]);

            $duration = (microtime(true) - $startTime) * 1000;
            $times[] = $duration;

            $response->assertStatus(200);
        }

        $avgTime = array_sum($times) / count($times);
        $this->assertLessThan(5000, $avgTime, "Average conversation response time: {$avgTime}ms (expected < 5000ms)");
    }

    /** @test */
    public function it_handles_bulk_operations_efficiently()
    {
        $plans = ContentPlan::factory()->count(20)->create([
            'org_id' => $this->org->org_id,
            'status' => 'pending',
        ]);

        $startTime = microtime(true);

        $response = $this->postJson('/api/gpt/bulk-operation', [
            'operation' => 'approve',
            'resource_type' => 'content_plans',
            'resource_ids' => $plans->pluck('plan_id')->toArray(),
        ]);

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(3000, $duration, "Bulk operation took {$duration}ms (expected < 3000ms for 20 items)");
    }

    /** @test */
    public function it_searches_efficiently_across_resources()
    {
        Campaign::factory()->count(30)->create(['org_id' => $this->org->org_id]);
        ContentPlan::factory()->count(30)->create(['org_id' => $this->org->org_id]);

        $startTime = microtime(true);

        $response = $this->postJson('/api/gpt/search', [
            'query' => 'campaign',
            'resources' => ['campaigns', 'content_plans'],
            'limit' => 20,
        ]);

        $duration = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(1000, $duration, "Smart search took {$duration}ms (expected < 1000ms)");
    }

    /** @test */
    public function it_maintains_acceptable_memory_usage()
    {
        $memoryBefore = memory_get_usage(true);

        Campaign::factory()->count(100)->create(['org_id' => $this->org->org_id]);
        $this->getJson('/api/gpt/campaigns?limit=100');

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $this->assertLessThan(50, $memoryUsed, "Memory usage: {$memoryUsed}MB (expected < 50MB)");
    }
}
```

**2. Load Test Documentation** (30 minutes)

**File**: `docs/PERFORMANCE_TESTING.md`

Document how to run performance tests and interpret results.

**Acceptance Criteria**:
- [ ] Performance tests for all critical GPT endpoints
- [ ] Tests verify response times under load
- [ ] Tests verify caching effectiveness
- [ ] Tests verify memory usage
- [ ] Documentation for running performance tests

---

## Phase 5: Testing & Documentation (3 hours)

**Current**: 75% Complete (24/32 hours)
**Target**: 85% Complete (27/32 hours)
**Remaining**: 3 hours

### 5.1: API Documentation (Swagger/OpenAPI) (2 hours)

#### Objective
Create complete Swagger/OpenAPI 3.1 documentation for all API endpoints.

#### Tasks

**1. Complete OpenAPI Specification** (90 minutes)

**File**: `docs/openapi.yaml`

Expand existing GPT Actions specification to cover all endpoints:

```yaml
openapi: 3.1.0
info:
  title: CMIS API
  description: Cognitive Marketing Intelligence Suite API
  version: 1.0.0
  contact:
    name: CMIS Support
    email: support@cmis.marketing.limited

servers:
  - url: https://cmis.kazaaz.com/api
    description: Production server
  - url: https://cmis-test.kazaaz.com/api
    description: Staging server
  - url: http://localhost:8000/api
    description: Development server

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    Campaign:
      type: object
      properties:
        id:
          type: string
          format: uuid
        name:
          type: string
        description:
          type: string
        status:
          type: string
          enum: [draft, active, paused, completed, archived]
        start_date:
          type: string
          format: date
        end_date:
          type: string
          format: date
        budget:
          type: number
        created_at:
          type: string
          format: date-time

    ContentPlan:
      type: object
      properties:
        id:
          type: string
          format: uuid
        campaign_id:
          type: string
          format: uuid
        name:
          type: string
        content_type:
          type: string
          enum: [social_post, blog_article, ad_copy, email, video_script]
        status:
          type: string
          enum: [draft, pending, approved, rejected, published]

    Error:
      type: object
      properties:
        success:
          type: boolean
          example: false
        message:
          type: string
        errors:
          type: object

    SuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
        data:
          type: object
        meta:
          type: object

paths:
  # Authentication
  /auth/login:
    post:
      tags: [Authentication]
      summary: Login user
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [email, password]
              properties:
                email:
                  type: string
                  format: email
                password:
                  type: string
                  format: password
      responses:
        '200':
          description: Login successful
          content:
            application/json:
              schema:
                allOf:
                  - $ref: '#/components/schemas/SuccessResponse'
                  - type: object
                    properties:
                      data:
                        type: object
                        properties:
                          token:
                            type: string
                          user:
                            type: object

  # GPT Endpoints
  /gpt/context:
    get:
      tags: [GPT]
      summary: Get user and organization context
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Context retrieved successfully
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SuccessResponse'

  /gpt/campaigns:
    get:
      tags: [GPT]
      summary: List campaigns
      security:
        - bearerAuth: []
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum: [draft, active, paused, completed, archived]
        - name: limit
          in: query
          schema:
            type: integer
            minimum: 1
            maximum: 100
            default: 20
      responses:
        '200':
          description: Campaigns retrieved successfully
          content:
            application/json:
              schema:
                allOf:
                  - $ref: '#/components/schemas/SuccessResponse'
                  - type: object
                    properties:
                      data:
                        type: array
                        items:
                          $ref: '#/components/schemas/Campaign'

  # Add all other endpoints...

security:
  - bearerAuth: []

tags:
  - name: Authentication
    description: User authentication endpoints
  - name: GPT
    description: GPT interface endpoints
  - name: Campaigns
    description: Campaign management endpoints
  - name: Content Plans
    description: Content plan management endpoints
  - name: Markets
    description: Organization market management endpoints
  - name: Compliance
    description: Content compliance validation endpoints
```

**2. Generate Interactive Documentation** (30 minutes)

Install and configure Swagger UI:

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

**Config**: `config/l5-swagger.php`

```php
return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'CMIS API Documentation',
            ],
            'routes' => [
                'api' => 'api/documentation',
            ],
            'paths' => [
                'docs' => storage_path('api-docs'),
                'docs_yaml' => 'openapi.yaml',
            ],
        ],
    ],
];
```

Generate documentation:

```bash
php artisan l5-swagger:generate
```

**Acceptance Criteria**:
- [ ] Complete OpenAPI 3.1 specification covering all endpoints
- [ ] All request/response schemas documented
- [ ] Authentication documented
- [ ] Error responses documented
- [ ] Interactive Swagger UI accessible at /api/documentation
- [ ] Examples provided for all endpoints

### 5.2: Deployment Guide (1 hour)

#### Objective
Create comprehensive deployment guide for staging and production.

#### Tasks

**File**: `docs/DEPLOYMENT_GUIDE.md`

```markdown
# CMIS Deployment Guide

## Prerequisites

- PHP 8.3+
- PostgreSQL 16+
- Redis 7+
- Node.js 20+
- Composer 2.6+
- npm 10+

## Environment Setup

### 1. Server Preparation

#### Install Dependencies
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.3-cli php8.3-fpm php8.3-pgsql php8.3-redis \
                 php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip

# PostgreSQL with pgvector
sudo apt install postgresql-16 postgresql-16-pgvector

# Redis
sudo apt install redis-server
```

#### Configure PHP
```bash
# Edit php.ini
sudo nano /etc/php/8.3/fpm/php.ini

# Recommended settings:
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
```

### 2. Application Deployment

#### Clone Repository
```bash
cd /var/www
git clone https://github.com/MarketingLimited/cmis.marketing.limited.git
cd cmis.marketing.limited
```

#### Install Dependencies
```bash
# PHP dependencies
composer install --no-dev --optimize-autoloader

# Frontend dependencies
npm install --production
npm run build
```

#### Environment Configuration
```bash
cp .env.example .env
nano .env
```

Required environment variables:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cmis.kazaaz.com

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cmis
DB_USERNAME=begin
DB_PASSWORD=123@Marketing@321

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SANCTUM_TOKEN_EXPIRATION=10080
AI_RATE_LIMIT=10
```

#### Generate Application Key
```bash
php artisan key:generate
```

### 3. Database Setup

#### Create Database
```bash
sudo -u postgres psql
CREATE DATABASE cmis;
CREATE USER begin WITH PASSWORD '123@Marketing@321';
GRANT ALL PRIVILEGES ON DATABASE cmis TO begin;
\c cmis
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgvector";
\q
```

#### Run Migrations
```bash
# Backup existing database first!
pg_dump -h 127.0.0.1 -U begin cmis > backup_$(date +%Y%m%d_%H%M%S).sql

# Run migrations
php artisan migrate --force

# Verify RLS is enabled
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT schemaname, tablename, rowsecurity
FROM pg_tables
WHERE schemaname = 'cmis' AND rowsecurity = true;"
```

### 4. Cache & Optimization

```bash
# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear application cache
php artisan cache:clear
```

### 5. Queue Workers

#### Configure Supervisor
```bash
sudo nano /etc/supervisor/conf.d/cmis-worker.conf
```

```ini
[program:cmis-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cmis.marketing.limited/artisan queue:work redis --queue=ai-generation,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/cmis.marketing.limited/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cmis-worker:*
```

### 6. Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name cmis.kazaaz.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name cmis.kazaaz.com;

    root /var/www/cmis.marketing.limited/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/cmis.kazaaz.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cmis.kazaaz.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo nginx -t
sudo systemctl restart nginx
```

### 7. Permissions

```bash
sudo chown -R www-data:www-data /var/www/cmis.marketing.limited
sudo chmod -R 755 /var/www/cmis.marketing.limited
sudo chmod -R 775 /var/www/cmis.marketing.limited/storage
sudo chmod -R 775 /var/www/cmis.marketing.limited/bootstrap/cache
```

### 8. Monitoring Setup

#### Log Rotation
```bash
sudo nano /etc/logrotate.d/cmis
```

```
/var/www/cmis.marketing.limited/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

#### Health Check Script
```bash
#!/bin/bash
# /usr/local/bin/cmis-health-check.sh

RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://cmis.kazaaz.com/api/health)

if [ $RESPONSE -ne 200 ]; then
    echo "Health check failed with status: $RESPONSE"
    # Send alert (email, Slack, PagerDuty, etc.)
fi
```

#### Cron for Health Checks
```bash
crontab -e
```

```cron
# Health check every 5 minutes
*/5 * * * * /usr/local/bin/cmis-health-check.sh

# Clean old logs daily
0 2 * * * find /var/www/cmis.marketing.limited/storage/logs -name "*.log" -mtime +14 -delete
```

## Post-Deployment Verification

### 1. Run Test Suite
```bash
php artisan test
# Expected: 170 tests passing
```

### 2. Verify Services
```bash
# Check queue workers
sudo supervisorctl status cmis-worker:*

# Check Redis
redis-cli ping

# Check PostgreSQL
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis -c "SELECT COUNT(*) FROM cmis.users;"
```

### 3. Test API Endpoints
```bash
# Health check
curl https://cmis.kazaaz.com/api/health

# Authentication
curl -X POST https://cmis.kazaaz.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# GPT Context (requires auth token)
curl https://cmis.kazaaz.com/api/gpt/context \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Verify Security Headers
```bash
curl -I https://cmis.kazaaz.com/

# Should include:
# Strict-Transport-Security: max-age=31536000
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# Content-Security-Policy: ...
```

### 5. Test Rate Limiting
```bash
for i in {1..15}; do
  curl -X POST https://cmis.kazaaz.com/api/ai/generate \
    -H "Authorization: Bearer TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"prompt": "test"}' \
    -w "Status: %{http_code}\n"
done
# Should see 429 (Too Many Requests) after 10 requests
```

## Rollback Procedure

If issues occur during deployment:

```bash
# 1. Restore database backup
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis < backup_YYYYMMDD_HHMMSS.sql

# 2. Revert to previous code version
git reset --hard PREVIOUS_COMMIT_HASH
composer install --no-dev --optimize-autoloader

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 4. Restart services
sudo supervisorctl restart cmis-worker:*
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

## Troubleshooting

### Common Issues

#### Queue workers not processing jobs
```bash
# Check worker status
sudo supervisorctl status cmis-worker:*

# View worker logs
tail -f /var/www/cmis.marketing.limited/storage/logs/worker.log

# Restart workers
sudo supervisorctl restart cmis-worker:*
```

#### Database connection errors
```bash
# Test connection
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis

# Check PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-16-main.log
```

#### High memory usage
```bash
# Check PHP-FPM pool configuration
sudo nano /etc/php/8.3/fpm/pool.d/www.conf

# Adjust pm settings:
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

## Performance Tuning

### PostgreSQL Optimization
```sql
-- Analyze database
ANALYZE;

-- Vacuum database
VACUUM ANALYZE;

-- Check slow queries
SELECT query, mean_exec_time, calls
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 10;
```

### Redis Optimization
```bash
# Check Redis memory usage
redis-cli INFO memory

# Set max memory policy
redis-cli CONFIG SET maxmemory-policy allkeys-lru
redis-cli CONFIG SET maxmemory 2gb
```

## Maintenance

### Weekly Tasks
- Review application logs
- Check disk space usage
- Monitor database size
- Review queue job failures

### Monthly Tasks
- Update dependencies (security patches)
- Review and optimize database indexes
- Backup verification (restore test)
- Performance review

### Quarterly Tasks
- Dependency updates (minor versions)
- Security audit
- Capacity planning review
- Disaster recovery drill

## Support

For deployment issues, contact:
- Email: support@cmis.marketing.limited
- Documentation: https://cmis.kazaaz.com/docs
- GitHub Issues: https://github.com/MarketingLimited/cmis.marketing.limited/issues
```

**Acceptance Criteria**:
- [ ] Complete deployment guide from scratch
- [ ] Server prerequisites documented
- [ ] Application setup step-by-step
- [ ] Database migration procedure
- [ ] Queue worker configuration
- [ ] Web server configuration (Nginx/Apache)
- [ ] Post-deployment verification steps
- [ ] Rollback procedure documented
- [ ] Troubleshooting common issues
- [ ] Performance tuning recommendations
- [ ] Maintenance schedule

---

## Summary of Deliverables

### Phase 2 (2 hours)
1. ✅ ContentPlanManager.vue component
2. ✅ OrgMarketManager.vue component

### Phase 3 (1 hour)
1. ✅ Enhanced error messages and logging
2. ✅ API response optimization with caching

### Phase 4 (2 hours)
1. ✅ Bulk operation handler
2. ✅ Smart search handler
3. ✅ Performance test suite
4. ✅ Load testing documentation

### Phase 5 (3 hours)
1. ✅ Complete OpenAPI/Swagger specification
2. ✅ Interactive Swagger UI setup
3. ✅ Comprehensive deployment guide

---

## Estimated Completion Timeline

| Task | Hours | When |
|------|-------|------|
| Content Plan Manager UI | 1h | Day 1 Morning |
| Org Market Manager UI | 1h | Day 1 Afternoon |
| GPT Error Enhancement | 0.5h | Day 1 Evening |
| API Response Optimization | 0.5h | Day 1 Evening |
| Bulk Operations | 0.5h | Day 2 Morning |
| Smart Search | 0.5h | Day 2 Morning |
| Performance Tests | 1h | Day 2 Afternoon |
| OpenAPI Documentation | 1.5h | Day 2 Afternoon |
| Swagger UI Setup | 0.5h | Day 2 Evening |
| Deployment Guide | 1h | Day 2 Evening |
| **TOTAL** | **8h** | **2 Days** |

---

## Priority Recommendations

### Must Have (High Priority) - 3 hours
1. **Deployment Guide** (1h) - Critical for production deployment
2. **OpenAPI Documentation** (1.5h) - Important for API consumers
3. **Swagger UI** (0.5h) - Interactive documentation

### Should Have (Medium Priority) - 3 hours
4. **Performance Tests** (1h) - Verify system performance under load
5. **Bulk Operations** (0.5h) - Useful for GPT multi-step actions
6. **Smart Search** (0.5h) - Enhanced search functionality
7. **Content Plan Manager UI** (1h) - Improve admin experience

### Nice to Have (Low Priority) - 2 hours
8. **Org Market Manager UI** (1h) - Enhance market management
9. **GPT Enhancements** (1h) - Polish and optimization

---

## Success Criteria

Upon completion of all tasks:

- [ ] **100% Implementation** (240/240 hours)
- [ ] **Complete API documentation** with Swagger UI
- [ ] **Production deployment guide** ready for operations team
- [ ] **Performance benchmarks** established and documented
- [ ] **UI components** for all major features
- [ ] **Enhanced GPT** with bulk operations and smart search
- [ ] **All tests passing** (170+ tests)
- [ ] **System ready** for user acceptance testing

---

## Notes

### Optional vs Required
All items in this roadmap are considered **optional enhancements** beyond the 95% production-ready state. The system is fully functional and can be deployed to production immediately. These enhancements improve:
- Developer experience (Swagger docs)
- Operations efficiency (Deployment guide, performance tests)
- User experience (UI components)
- System capabilities (Bulk operations, smart search)

### Flexibility
Tasks can be completed in any order based on team priorities. High-priority items (documentation and deployment guide) should be completed first for production readiness.

### Future Enhancements
Beyond 100%, consider:
- Mobile app development
- Advanced analytics dashboards
- Machine learning model training
- Multi-language support
- Third-party integrations (Salesforce, HubSpot)

---

**Document Version**: 1.0
**Last Updated**: 2025-11-16
**Author**: CMIS Development Team
**Status**: Ready for Implementation
