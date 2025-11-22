# دليل الوكلاء - Services Layer (app/Services/)

## 1. Purpose (الغرض)

طبقة الخدمات (Services) تحتوي على **Business Logic** الكامل لـ CMIS:
- **100+ Service Classes** موزعة عبر domains مختلفة
- **Platform Integrations**: خدمات التكامل مع Meta, Google, TikTok, LinkedIn, Twitter, Snapchat
- **AI Services**: Embedding orchestration, semantic search, AI quota management
- **Campaign Services**: إدارة الحملات وأتمتتها
- **Analytics Services**: معالجة البيانات والتحليلات

## 2. Owned Scope (النطاق المملوك)

### Service Organization

```
app/Services/
├── AI/
│   ├── EmbeddingOrchestrator.php      # AI embeddings orchestration
│   ├── SemanticSearchService.php       # Vector similarity search
│   └── AIQuotaService.php              # Rate limiting for AI APIs
│
├── AdPlatforms/                         # Platform integration services
│   ├── AbstractAdPlatform.php          # Base class (Template Method pattern)
│   ├── Meta/
│   │   └── MetaAdService.php
│   ├── Google/
│   │   └── GoogleAdsService.php
│   ├── TikTok/
│   │   └── TikTokAdsService.php
│   ├── LinkedIn/
│   │   └── LinkedInAdsService.php
│   ├── Twitter/
│   │   └── TwitterAdsService.php
│   ├── Snapchat/
│   │   └── SnapchatAdsService.php
│   └── Contracts/
│       └── AdPlatformInterface.php
│
├── Campaign/
│   ├── CampaignService.php             # Campaign management
│   ├── CampaignOrchestrationService.php
│   └── CampaignOptimizationService.php
│
├── Analytics/
│   ├── MetricsService.php
│   ├── ReportingService.php
│   └── DashboardService.php
│
├── CMIS/
│   ├── OrganizationContextService.php  # RLS context management
│   └── Traits/
│       └── HandlesOrganizationContext.php
│
├── Publishing/
│   ├── SocialPublishingService.php
│   └── PublishingQueueService.php
│
├── Embedding/
│   └── Providers/
│       └── GeminiEmbeddingProvider.php
│
├── OAuth/
│   └── OAuthService.php                # Platform OAuth flows
│
├── Sync/
│   └── PlatformSyncService.php         # Platform data synchronization
│
├── RateLimiter/
│   └── RateLimiterService.php
│
├── Cache/
│   └── CacheService.php
│
├── Automation/
│   └── WorkflowAutomationService.php
│
├── Connectors/
│   ├── Contracts/
│   │   └── ConnectorInterface.php
│   └── Providers/
│
├── Dashboard/
│   └── DashboardDataService.php
│
├── Optimization/
│   └── CampaignOptimizationService.php
│
├── Listening/
│   └── SocialListeningService.php
│
├── Communication/
│   └── NotificationService.php
│
├── Onboarding/
│   └── UserOnboardingService.php
│
├── FeatureToggle/
│   └── FeatureFlagService.php
│
├── Integration/
│   └── IntegrationService.php
│
├── Platform/
│   └── PlatformConnectionService.php
│
├── Ads/
│   └── AdCreationService.php
│
├── AdCampaigns/
│   └── AdCampaignService.php
│
├── Social/
│   └── SocialMediaService.php
│
├── Orchestration/
│   └── FlowOrchestrationService.php
│
└── Enterprise/
    └── EnterpriseFeatureService.php
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Core Services
- `AI/EmbeddingOrchestrator.php`: Orchestrates AI embedding generation (Gemini API)
- `CMIS/OrganizationContextService.php`: Manages RLS context across requests
- `AdPlatforms/AbstractAdPlatform.php`: Base class for all platform services

### Platform Services (6 Platforms)
- `AdPlatforms/Meta/MetaAdService.php`: Facebook/Instagram ads
- `AdPlatforms/Google/GoogleAdsService.php`: Google Ads platform
- `AdPlatforms/TikTok/TikTokAdsService.php`: TikTok Marketing API
- `AdPlatforms/LinkedIn/LinkedInAdsService.php`: LinkedIn Ads
- `AdPlatforms/Twitter/TwitterAdsService.php`: Twitter/X ads
- `AdPlatforms/Snapchat/SnapchatAdsService.php`: Snapchat ads

### Business Services
- `Campaign/CampaignService.php`: Campaign lifecycle management
- `Analytics/MetricsService.php`: Metrics aggregation and processing
- `Publishing/SocialPublishingService.php`: Multi-platform publishing
- `OAuth/OAuthService.php`: Platform OAuth authentication flows

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Dependency Flow
```
Controllers → Services → Repositories → Models
                ↓
          External APIs (Guzzle)
          Cache (Redis)
          Queue (Redis/Database)
```

### External Dependencies
- **Guzzle**: HTTP client for platform API calls
- **Redis**: Caching & rate limiting
- **Google Gemini API**: AI embeddings
- **Platform APIs**: Meta, Google, TikTok, LinkedIn, Twitter, Snapchat

### Internal Dependencies
- **Repositories**: Data access layer
- **Models**: Eloquent models
- **Events**: Application events for async processing

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Service Pattern

#### ✅ Correct Service Structure
```php
namespace App\Services\YourDomain;

use App\Repositories\YourDomain\YourRepository;

class YourService
{
    /**
     * Constructor injection
     */
    public function __construct(
        protected YourRepository $repository,
        protected AnotherService $anotherService
    ) {}

    /**
     * Business logic method with type hints
     */
    public function processData(array $data): Model
    {
        // 1. Validation (if not done in FormRequest)
        // 2. Business logic
        // 3. Call repository for data operations
        // 4. Return result

        return $this->repository->create($data);
    }

    /**
     * Complex business logic
     */
    public function orchestrateWorkflow(Model $model): bool
    {
        // Multi-step business logic
        // Event dispatching
        // External API calls
        // Queue jobs if needed

        return true;
    }
}
```

### Template Method Pattern (Platform Services)

```php
// AbstractAdPlatform defines template
abstract class AbstractAdPlatform
{
    // Template method
    final public function executeAdCampaign(array $data): Campaign
    {
        $this->authenticate();
        $this->validateData($data);
        $campaign = $this->createCampaign($data);
        $this->trackMetrics($campaign);
        return $campaign;
    }

    // Abstract methods (implemented by subclasses)
    abstract protected function authenticate(): void;
    abstract protected function createCampaign(array $data): Campaign;
}

// Concrete implementation
class MetaAdService extends AbstractAdPlatform
{
    protected function authenticate(): void
    {
        // Meta-specific OAuth logic
    }

    protected function createCampaign(array $data): Campaign
    {
        // Meta-specific campaign creation
    }
}
```

### Service Rules

- ✅ **ONE** service per business domain
- ✅ Constructor injection for dependencies
- ✅ Type hints for all parameters and return types
- ✅ Services call Repositories (not Models directly)
- ✅ Return Models or Collections (not arrays)
- ❌ **NEVER** put services in Controllers
- ❌ **NEVER** access Models directly (use Repositories)

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Testing Services

```bash
# Test all services
vendor/bin/phpunit tests/Unit/Services/

# Test specific service domain
vendor/bin/phpunit tests/Unit/Services/Campaign/

# Test with mocking
vendor/bin/phpunit tests/Unit/Services/AI/
```

### Service Testing Pattern

```php
namespace Tests\Unit\Services\Campaign;

use Tests\TestCase;
use App\Services\Campaign\CampaignService;
use App\Repositories\Campaign\CampaignRepository;
use Mockery;

class CampaignServiceTest extends TestCase
{
    public function test_creates_campaign()
    {
        // Mock repository
        $repository = Mockery::mock(CampaignRepository::class);
        $repository->shouldReceive('create')
                   ->once()
                   ->with(['name' => 'Test'])
                   ->andReturn(new Campaign(['name' => 'Test']));

        // Test service
        $service = new CampaignService($repository);
        $campaign = $service->createCampaign(['name' => 'Test']);

        $this->assertEquals('Test', $campaign->name);
    }
}
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Create New Service

1. **Create service file**:
   ```php
   app/Services/YourDomain/YourService.php
   ```

2. **Implement service**:
   ```php
   namespace App\Services\YourDomain;

   class YourService
   {
       public function __construct(
           protected YourRepository $repository
       ) {}

       public function yourBusinessLogic(array $data): Model
       {
           // Implement business logic
           return $this->repository->create($data);
       }
   }
   ```

3. **Register in Service Provider** (if needed):
   ```php
   // app/Providers/AppServiceProvider.php
   $this->app->singleton(YourService::class);
   ```

4. **Create tests**:
   ```php
   tests/Unit/Services/YourDomain/YourServiceTest.php
   ```

### Add Platform Integration Service

1. **Extend AbstractAdPlatform**:
   ```php
   namespace App\Services\AdPlatforms\YourPlatform;

   use App\Services\AdPlatforms\AbstractAdPlatform;

   class YourPlatformService extends AbstractAdPlatform
   {
       protected function authenticate(): void
       {
           // OAuth implementation
       }

       protected function createCampaign(array $data): Campaign
       {
           // Platform-specific logic
       }
   }
   ```

2. **Add configuration**:
   ```php
   config/integrations/your-platform.php
   ```

3. **Implement OAuth flow**:
   ```php
   app/Services/OAuth/YourPlatformOAuth.php
   ```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Mistakes

1. **Services Directly Accessing Models**
   - ❌ `Campaign::create($data)` in service
   - ✅ `$this->repository->create($data)`

2. **Business Logic in Controllers**
   - ❌ Complex logic in controller methods
   - ✅ Delegate to services

3. **Missing Type Hints**
   - ❌ `public function process($data)`
   - ✅ `public function process(array $data): Model`

4. **Ignoring Rate Limits** (AI services)
   - ⚠️ Gemini API: 30 requests/min, 500/hour
   - ✅ Use `RateLimiterService` or queue jobs

5. **Not Using Transactions**
   - ⚠️ Multi-step operations without transactions
   - ✅ Use `DB::transaction()` for atomic operations

### 🎯 Best Practices

- **Keep Services Focused**: One responsibility per service
- **Use Dependency Injection**: Constructor injection
- **Queue Heavy Operations**: Use Jobs for long-running tasks
- **Cache Expensive Operations**: Use CacheService
- **Handle Exceptions**: Try-catch for external API calls
- **Log Important Events**: Use Laravel logging

### 📊 Statistics

- **Total Services**: 100+ service classes
- **Platform Services**: 6 (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat)
- **AI Services**: 5+ (Embeddings, Semantic Search, Quotas)
- **Campaign Services**: 10+ (Management, Optimization, Orchestration)
