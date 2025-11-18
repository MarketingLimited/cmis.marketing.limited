# CMIS Project Knowledge Base
## Cognitive Marketing Information System - Domain Knowledge for AI Agents

**Last Updated:** 2025-11-18
**Project:** CMIS (Campaign Management & Integration System)
**Version:** Laravel 12 - PostgreSQL 16
**Status:** Production-Ready (49% Complete)

---

## 🎯 PROJECT ESSENCE

### What is CMIS?

**CMIS (Cognitive Marketing Information System)** is an enterprise-grade, AI-powered marketing automation platform designed for agencies and enterprises managing multi-tenant campaigns across multiple digital advertising platforms.

**Core Purpose:**
- Unified campaign management across Meta, Google, TikTok, LinkedIn, Twitter, Snapchat
- AI-powered insights with semantic search using pgvector
- Multi-tenant SaaS architecture with PostgreSQL Row-Level Security (RLS)
- Social media scheduling and publishing automation
- Real-time analytics and performance tracking
- Creative asset management with AI tagging

---

## 🏗️ ARCHITECTURAL FOUNDATION

### 1. Multi-Tenancy Architecture (CRITICAL)

**THIS IS THE MOST IMPORTANT CONCEPT IN CMIS**

```
Every single operation in CMIS happens within an ORGANIZATION context.
PostgreSQL Row-Level Security (RLS) enforces data isolation at database level.
```

**How it Works:**

```sql
-- At start of every request, middleware sets context:
SELECT cmis.init_transaction_context(user_id, org_id);

-- After that, ALL queries are automatically filtered:
SELECT * FROM cmis.campaigns;  -- Returns ONLY current org's campaigns
```

**Request Flow:**

```
1. User authenticates → gets JWT token with user_id
2. Request includes org_id in route: /api/orgs/{org_id}/campaigns
3. Middleware validates user belongs to that org
4. Middleware sets database context: init_transaction_context(user_id, org_id)
5. Controller executes → all queries automatically filtered by RLS
6. Response returns → context cleared for next request
```

**Critical Rules for AI Agents:**

✅ **ALWAYS** include `org_id` in organization-scoped routes
✅ **ALWAYS** verify user has access to the org before operations
✅ **NEVER** bypass RLS in production code
✅ **USE** system user ID (`CMIS_SYSTEM_USER_ID`) for automated operations
❌ **NEVER** write queries that manually filter by org_id (RLS does it automatically)

### 2. Database Schema Organization

**12 Specialized Schemas (not just `public`):**

| Schema | Purpose | Example Tables |
|--------|---------|----------------|
| `cmis` | Core entities | users, orgs, campaigns, integrations |
| `cmis_marketing` | Marketing | campaigns, briefs, content_plans |
| `cmis_knowledge` | AI & Knowledge | knowledge_items, embeddings |
| `cmis_ai_analytics` | AI Analytics | ai_recommendations, embeddings_cache |
| `cmis_analytics` | Performance | performance_metrics, kpi_calculations |
| `cmis_ops` | Operations | sync_logs, etl_logs |
| `cmis_security` | Security | permissions, audit_trails |
| `cmis_audit` | Compliance | audit_events, compliance_logs |
| `cmis_system_health` | Monitoring | health_checks |
| `operations` | Platform ops | Platform-specific logs |
| `archive` | Historical | Archived records |
| `lab` | Experimental | Test features |

**Total:** 189 tables across 12 schemas

### 3. Technology Stack

```yaml
Backend:
  Framework: Laravel 12
  PHP: 8.3+
  Database: PostgreSQL 16 with pgvector extension
  Cache: Redis
  Queue: Redis-backed Laravel queues
  Auth: Laravel Sanctum (API tokens)

Frontend:
  Framework: Alpine.js 3.13.5
  CSS: Tailwind CSS 3.4.1
  Charts: Chart.js 4.4.1
  Build: Vite 7.0.7
  HTTP: Axios 1.11.0

AI/ML:
  Embeddings: Google Gemini (768-dimensional vectors)
  Vector DB: pgvector extension
  Similarity: Cosine similarity search

Infrastructure:
  Web Server: Apache/Nginx
  Queue Workers: Supervisor-managed
  Scheduler: Laravel scheduler (cron)
  Testing: PHPUnit 11.5.3, Playwright 1.40.0
```

---

## 📊 BUSINESS DOMAINS

### Core Domains in CMIS

#### 1. **Organization Management**
**Models:** `Org`, `UserOrg`, `OrgMarket`, `OrgDataset`
**Purpose:** Multi-tenant organization structure
**Key Features:**
- User invitations and role assignment
- Market relationships (Saudi, UAE, etc.)
- Organization-specific settings
- Subscription and billing management

#### 2. **Campaign Management**
**Models:** `Campaign`, `CampaignGroup`, `CampaignOffering`, `CampaignContextLink`
**Purpose:** End-to-end campaign lifecycle
**Key Features:**
- Campaign creation and editing
- Budget tracking and allocation
- Status management (Draft, Active, Paused, Completed)
- Performance metrics integration
- A/B testing frameworks

#### 3. **Creative & Content**
**Models:** `CreativeAsset`, `CreativeBrief`, `ContentPlan`, `ContentItem`
**Purpose:** Asset and content management
**Key Features:**
- Digital asset library
- Version control
- AI-powered tagging
- Approval workflows
- Format optimization

#### 4. **Social Media Management**
**Models:** `SocialAccount`, `SocialPost`, `PostMetric`, `SocialSchedule`
**Purpose:** Social media scheduling and publishing
**Key Features:**
- Multi-platform posting (Meta, LinkedIn, TikTok, Twitter)
- Publishing queue management
- Best time analyzer (AI-powered)
- Engagement tracking
- Approval workflows

#### 5. **Ad Platform Integration**
**Models:** `AdAccount`, `AdCampaign`, `AdSet`, `AdEntity`, `AdMetric`
**Purpose:** Ad campaign management across platforms
**Supported Platforms:**
- Meta (Facebook & Instagram) ✅
- Google Ads ✅
- TikTok ✅
- LinkedIn ✅
- Twitter/X ✅
- Snapchat ✅

**Integration Pattern:**

```php
// Factory pattern for platform-specific operations
$connector = AdPlatformFactory::make($integration);
$connector->syncCampaigns($orgId);
$connector->publishPost($postData);
$connector->getMetrics($campaignId);
```

#### 6. **AI & Knowledge Management**
**Models:** `KnowledgeItem`, `EmbeddingsCache`, `SemanticSearchLog`, `AIRecommendation`
**Purpose:** AI-powered insights and semantic search
**Key Features:**
- Vector embeddings (768-dimensional)
- Semantic search via pgvector
- Content recommendations
- Predictive analytics
- Automated learning

**AI Operation Flow:**

```php
// 1. Generate embeddings
$embedding = EmbeddingOrchestrator::embed($text);

// 2. Store in cache (by MD5 hash)
EmbeddingsCache::store($hash, $embedding);

// 3. Semantic search
$results = SemanticSearchService::search($query, $limit);
// Returns: similarity scores via cosine similarity
```

**Rate Limits:**
- AI operations: 30/minute, 500/hour per user
- Gemini API: 10 requests/minute per user

#### 7. **Analytics & Reporting**
**Models:** `PerformanceMetric`, `KPI`, `CampaignAnalytics`, `ChannelMetric`
**Purpose:** Performance tracking and insights
**Key Features:**
- Real-time dashboards
- Custom KPI definitions
- ROI and attribution modeling
- PDF report generation
- Temporal data analysis

#### 8. **Security & Compliance**
**Models:** `Permission`, `Role`, `UserPermission`, `AuditLog`, `ComplianceRule`
**Purpose:** Access control and audit trails
**Key Features:**
- RBAC (Role-Based Access Control)
- RLS (Row-Level Security) policies
- Comprehensive audit logging
- Compliance tracking
- Permission caching

**Permission Pattern:**

```php
// Database-level permission check
SELECT cmis.check_permission(user_id, 'cmis.campaigns.create', org_id);

// Application-level check
if ($user->hasPermission('cmis.campaigns.create')) {
    // Allowed
}

// Policy-based authorization
$this->authorize('create', Campaign::class);
```

---

## 🔑 CRITICAL PATTERNS & CONCEPTS

### 1. Campaign Context System

**Unique to CMIS:** EAV (Entity-Attribute-Value) pattern for flexible campaign fields

```
Campaign → Context → FieldDefinition → FieldValue
                   → FieldAlias
```

**Three Context Types:**
1. **Base Context** - Fundamental campaign data
2. **Creative Context** - Creative and messaging
3. **Value Context** - Pricing and value proposition

**Usage:**

```php
// Link campaign to context
CampaignContextLink::create([
    'campaign_id' => $campaignId,
    'context_id' => $contextId,
    'context_type' => 'base' // or 'creative' or 'value'
]);

// Dynamic fields per organization
FieldDefinition::create([
    'entity_type' => 'campaign',
    'field_name' => 'target_roas',
    'org_id' => $orgId
]);
```

### 2. Platform Integration Lifecycle

**OAuth Flow (Meta, Google, TikTok, LinkedIn):**

```
1. User clicks "Connect Platform"
2. Redirected to platform OAuth page
3. User authorizes → Platform redirects back with code
4. Backend exchanges code for access token + refresh token
5. Store tokens in `integrations` table (encrypted)
6. Set up webhook endpoint for real-time updates
7. Initial sync: fetch accounts, campaigns, metrics
8. Ongoing: webhook updates + scheduled syncs
```

**Webhook Handling:**

```php
// Public endpoint with signature verification
Route::post('/webhooks/{platform}', [WebhookController::class, 'handle'])
    ->middleware(['throttle:webhooks', 'verify.webhook.signature']);

// Verify signature
$signature = hash_hmac('sha256', $payload, $secret);
if (!hash_equals($signature, $receivedSignature)) {
    abort(401, 'Invalid signature');
}
```

**Token Refresh:**

```php
// Automatic refresh when token expires
if ($integration->isTokenExpired()) {
    $connector = AdPlatformFactory::make($integration);
    $newToken = $connector->refreshToken();
    $integration->update(['access_token' => $newToken]);
}
```

### 3. Queue & Job System

**Redis-backed Laravel queues for heavy operations**

**Key Jobs:**

```php
// Embedding generation (AI operation)
ProcessEmbeddingJob::dispatch($text, $orgId)->onQueue('ai');

// Social media publishing
PublishScheduledPostJob::dispatch($post)->onQueue('publishing');

// Platform data sync
SyncPlatformDataJob::dispatch($integration, $orgId)->onQueue('sync');

// Bulk operations
SyncMetaAdsJob::dispatch($orgId)->onQueue('sync');
```

**Job Pattern:**

```php
class ProcessEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 240]; // Exponential backoff

    public function handle()
    {
        // Job inherits organization context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)",
            [$this->userId, $this->orgId]);

        // Perform heavy operation
        $embedding = EmbeddingOrchestrator::embed($this->text);

        // Store result
        EmbeddingsCache::store($this->hash, $embedding);
    }
}
```

**Scheduled Tasks:**

```bash
# Laravel scheduler (runs every minute)
* * * * * cd /path/to/cmis && php artisan schedule:run

# Scheduled commands:
- sync:platforms daily at 02:00
- embeddings:cleanup weekly
- analytics:aggregate hourly
- reports:generate daily
```

### 4. Repository Pattern

**Abstracts data access logic for testability**

**Pattern:**

```php
// Interface
interface CampaignRepositoryInterface
{
    public function find(string $id): ?Campaign;
    public function create(array $data): Campaign;
    public function update(string $id, array $data): Campaign;
    public function delete(string $id): bool;
}

// Concrete implementation
class CampaignRepository implements CampaignRepositoryInterface
{
    public function find(string $id): ?Campaign
    {
        // RLS automatically applies org filter
        return Campaign::find($id);
    }
}

// Service provider binding
$this->app->bind(
    CampaignRepositoryInterface::class,
    CampaignRepository::class
);

// Usage in controller
public function __construct(
    private CampaignRepositoryInterface $campaigns
) {}
```

**Available Repositories (14):**
- CampaignRepository
- ContextRepository
- CreativeRepository
- PermissionRepository
- AnalyticsRepository
- KnowledgeRepository
- EmbeddingRepository
- OperationsRepository
- AuditRepository
- CacheRepository
- MarketingRepository
- SocialMediaRepository
- NotificationRepository
- VerificationRepository

---

## 🚀 API STRUCTURE

### Route Organization

**Base Pattern:** `/api/orgs/{org_id}/{resource}`

**Example Routes:**

```php
// Authentication (no org_id needed)
POST /api/auth/login
POST /api/auth/register
GET  /api/auth/user

// Webhooks (public, signature-verified)
POST /api/webhooks/meta
POST /api/webhooks/google

// Organization-scoped resources
GET    /api/orgs/{org_id}/campaigns
POST   /api/orgs/{org_id}/campaigns
GET    /api/orgs/{org_id}/campaigns/{campaign_id}
PUT    /api/orgs/{org_id}/campaigns/{campaign_id}
DELETE /api/orgs/{org_id}/campaigns/{campaign_id}

// AI operations (rate-limited)
POST /api/orgs/{org_id}/ai/embeddings
POST /api/orgs/{org_id}/ai/semantic-search
POST /api/orgs/{org_id}/ai/recommendations

// Platform integration
POST /api/orgs/{org_id}/integrations/meta/connect
POST /api/orgs/{org_id}/integrations/meta/sync
GET  /api/orgs/{org_id}/integrations/meta/accounts

// Social media
POST /api/orgs/{org_id}/social/posts
GET  /api/orgs/{org_id}/social/posts/scheduled
POST /api/orgs/{org_id}/social/posts/{post_id}/publish

// Analytics
GET /api/orgs/{org_id}/analytics/dashboard
GET /api/orgs/{org_id}/analytics/campaigns/{campaign_id}
POST /api/orgs/{org_id}/analytics/reports/generate
```

### Middleware Chain

```php
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])
    ->prefix('orgs/{org_id}')
    ->group(function () {
        // All org-scoped routes
    });
```

**Middleware Execution Order:**

1. `auth:sanctum` - Verify JWT token, load user
2. `validate.org.access` - Check user belongs to org
3. `set.db.context` - Set RLS context
4. `throttle:api` - Rate limiting (100/min per user+org)
5. `audit.logger` - Log request for compliance
6. Controller action executes
7. Response transformation via Resources

### Rate Limits

```php
// Defined in AppServiceProvider
RateLimiter::for('auth', fn() => Limit::perMinute(10)->by(request()->ip()));
RateLimiter::for('api', fn() => Limit::perMinute(100)->by(auth()->id() . '-' . request('org_id')));
RateLimiter::for('webhooks', fn() => Limit::perMinute(1000)->by(request()->ip()));
RateLimiter::for('heavy', fn() => Limit::perMinute(20)->by(auth()->id()));
RateLimiter::for('ai', fn() => [
    Limit::perMinute(30)->by(auth()->id()),
    Limit::perHour(500)->by(auth()->id())
]);
```

---

## 🎨 FRONTEND ARCHITECTURE

### Technology Stack

- **Framework:** Alpine.js 3.13.5 (reactive components)
- **CSS:** Tailwind CSS 3.4.1 (utility-first)
- **Charts:** Chart.js 4.4.1 (data visualization)
- **Build:** Vite 7.0.7 (fast HMR)
- **HTTP:** Axios 1.11.0 (API calls)

### View Structure

**Main Dashboard:** `resources/views/dashboard.blade.php` (16,975 lines - needs refactoring)

**Component Organization:**

```
resources/views/
├── layouts/
│   ├── app.blade.php          # Main layout
│   └── guest.blade.php        # Guest layout
├── components/
│   ├── navigation.blade.php
│   ├── sidebar.blade.php
│   └── [50+ reusable components]
├── campaigns/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── social/
├── analytics/
├── integrations/
└── [30+ other sections]
```

### Alpine.js Patterns

**Example Component:**

```html
<div x-data="campaignManager()" x-init="loadCampaigns()">
    <!-- Campaign list -->
    <template x-for="campaign in campaigns" :key="campaign.id">
        <div @click="selectCampaign(campaign)">
            <h3 x-text="campaign.name"></h3>
            <span x-text="formatBudget(campaign.budget)"></span>
        </div>
    </template>

    <!-- Loading state -->
    <div x-show="loading">Loading...</div>
</div>

<script>
function campaignManager() {
    return {
        campaigns: [],
        loading: false,

        async loadCampaigns() {
            this.loading = true;
            const response = await axios.get(`/api/orgs/${orgId}/campaigns`);
            this.campaigns = response.data.data;
            this.loading = false;
        },

        formatBudget(amount) {
            return new Intl.NumberFormat('ar-SA', {
                style: 'currency',
                currency: 'SAR'
            }).format(amount);
        }
    }
}
</script>
```

---

## 📦 HELPER FUNCTIONS

**Location:** `app/Support/helpers.php` (685 lines)

### Currency Helpers

```php
format_sar($amount)           // Format as SAR currency
format_usd($amount)           // Format as USD currency
format_currency($amount, $currency = 'SAR')
sar_to_usd($amount)          // Convert SAR to USD
usd_to_sar($amount)          // Convert USD to SAR
budget_percentage($spent, $total)
```

### Date Helpers

```php
format_date_arabic($date)     // Format in Arabic locale
format_date_human($date)      // Human-readable format
convert_timezone($date, $from, $to)
calculate_age($birthdate)
get_quarter($date)
format_iso8601($date)
```

### Validation Helpers

```php
is_valid_saudi_phone($phone)  // Validate Saudi phone numbers
is_valid_uuid($uuid)
is_valid_credit_card($number)
is_strong_password($password)
is_valid_hashtag($tag)
contains_arabic($text)
```

### URL Helpers

```php
add_utm_params($url, $params)
extract_utm_params($url)
is_secure_url($url)
remove_query_params($url, $params)
```

---

## 🔒 SECURITY CONSIDERATIONS

### 1. Authentication

**Laravel Sanctum** for API token authentication

```php
// Login
$token = $user->createToken('api-token')->plainTextToken;

// API Request
Authorization: Bearer {token}

// Token includes user_id, decoded by middleware
```

### 2. Authorization

**Three Layers:**

1. **Middleware** - `validate.org.access` ensures user belongs to org
2. **Policies** - Laravel policy classes for model authorization
3. **Database RLS** - PostgreSQL enforces data isolation

**Permission Check:**

```php
// Application level
Gate::allows('create-campaign', $org);

// Database level (via RLS policies)
-- Automatically applied to all queries
```

### 3. Input Validation

**Form Requests for all input:**

```php
class StoreCampaignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('create-campaign', $this->route('org_id'));
    }
}
```

### 4. Audit Logging

**Comprehensive audit trail:**

```php
// Logged automatically via middleware
AuditLog::create([
    'user_id' => auth()->id(),
    'org_id' => request('org_id'),
    'action' => 'campaign.created',
    'entity_type' => 'Campaign',
    'entity_id' => $campaign->id,
    'changes' => json_encode($campaign->getChanges()),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

---

## 🧪 TESTING STRATEGY

### Test Organization (197 test files)

```
tests/
├── Feature/              # Integration tests (API endpoints)
├── Unit/                 # Unit tests (isolated logic)
├── Integration/          # Multi-component integration
├── Performance/          # Load and performance tests
└── E2E/                  # Playwright end-to-end tests
```

### Testing Commands

```bash
# Backend tests
php artisan test
composer test

# Specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage

# E2E tests
npm run test:e2e          # Headless
npm run test:e2e:ui       # Interactive
npm run test:e2e:debug    # Debug mode
```

### Testing Patterns

**Multi-Tenancy Testing:**

```php
use Tests\Traits\CreatesOrganization;

class CampaignTest extends TestCase
{
    use CreatesOrganization;

    public function test_user_can_only_see_own_org_campaigns()
    {
        $org1 = $this->createOrganization();
        $org2 = $this->createOrganization();

        $user = User::factory()->create();
        $user->orgs()->attach($org1->id);

        Campaign::factory()->create(['org_id' => $org1->id]);
        Campaign::factory()->create(['org_id' => $org2->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/orgs/{$org1->id}/campaigns");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Should see only org1's campaign
    }
}
```

---

## 📚 DOCUMENTATION REFERENCES

### Existing Documentation Files (20+)

**Primary Docs:**
- `README.md` - Project overview (724 lines)
- `COMPLETE_IMPLEMENTATION_REPORT.md` - Implementation status
- `TECHNICAL_AUDIT_REPORT.md` - Technical audit findings

**Development Progress:**
- `OVERALL-PROGRESS-49-PERCENT.md` - Current progress
- `ROADMAP_TO_100_PERCENT.md` - Future plans
- `PHASE_1_COMPLETION_REPORT.md` - Phase 1 completion

**Technical Guides:**
- `API_INTEGRATION_PLAN.md` - Platform integration guide
- `SECURITY_IMPLEMENTATION_GUIDE.md` - Security best practices
- `VECTOR_EMBEDDINGS_V2_STATUS_REPORT.md` - AI embeddings guide
- `laravel_embedding_guidelines.md` - Embedding implementation

**Platform Help (Arabic & English):**
- `docs/social/facebook/help_en.md` & `help_ar.md`
- `docs/social/instagram/help_en.md`
- `docs/social/linkedin/help_en.md` & `help_ar.md`
- `docs/social/tiktok/help_en.md` & `help_ar.md`

---

## 🎓 CRITICAL LEARNING FOR AI AGENTS

### What Makes CMIS Unique

1. **PostgreSQL RLS-based Multi-Tenancy**
   - Not typical Laravel multi-tenancy
   - Database-level isolation, not application-level
   - Context must be set for every request

2. **12-Schema Database Organization**
   - Not just `public` schema
   - Domain-driven schema design
   - Requires schema-qualified table names

3. **Platform Integration Factory**
   - Unified interface for 6+ platforms
   - OAuth flow + webhook handling
   - Token refresh automation

4. **AI-Powered Semantic Search**
   - pgvector extension
   - 768-dimensional embeddings
   - Cosine similarity search

5. **Campaign Context System**
   - EAV pattern for flexibility
   - Three context types (Base, Creative, Value)
   - Dynamic field definitions per org

6. **Comprehensive Audit System**
   - All actions logged
   - Compliance tracking
   - Soft deletes preserve history

---

## ⚠️ COMMON PITFALLS TO AVOID

### For AI Agents Working with CMIS

❌ **DON'T:**
- Bypass RLS by querying without context
- Manually filter by org_id (RLS does it automatically)
- Make AI calls without checking rate limits
- Modify production data without audit logging
- Hard-delete records (use soft deletes)
- Expose secrets in logs or code
- Skip permission checks
- Ignore webhook signature verification

✅ **DO:**
- Always set organization context before queries
- Use repository pattern for data access
- Respect rate limits (especially AI operations)
- Use jobs for heavy operations
- Follow existing patterns and conventions
- Validate all inputs via Form Requests
- Check permissions before destructive operations
- Log all significant operations
- Test with multiple organizations
- Cache expensive operations (embeddings, analytics)

---

## 📊 KEY METRICS & STATISTICS

### Codebase Size
- **Total:** 27MB
- **PHP Files:** 597 files in `/app`
- **Models:** 199 models
- **Controllers:** 105 controllers
- **Migrations:** 25 files
- **Tables:** 189 tables
- **Tests:** 197 test files

### Progress Status
- **Overall Completion:** 49%
- **Completed Phases:** 0, 1, 2
- **In Progress:** 3, 4
- **Planned:** 5, 6

### API Endpoints
- **Routes:** 1,217 lines of API routes
- **Webhooks:** 6 platform webhooks
- **Rate Limits:** 5 different limit tiers

---

## 🎯 NEXT DEVELOPMENT PHASES

### Phase 3 - Analytics & AI (In Progress)
- Advanced analytics dashboards
- AI recommendations engine
- Predictive analytics
- Automated reporting

### Phase 4 - Ad Campaigns (In Progress)
- Multi-platform ad management
- Budget optimization
- A/B testing automation
- Cross-platform attribution

### Phase 5 - Advanced Features (Planned)
- Workflow automation
- Advanced permissions
- White-labeling
- API marketplace

### Phase 6 - Optimization (Planned)
- Performance tuning
- UI/UX refinement
- Mobile app
- Advanced integrations

---

**This knowledge base should be consulted by ALL AI agents before performing operations on CMIS.**

**Remember:** CMIS is not a generic Laravel project. It has unique patterns, especially around multi-tenancy and AI integration. Understanding these patterns is critical for effective assistance.
