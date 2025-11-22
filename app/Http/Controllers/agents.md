# دليل الوكلاء - Controllers Layer (app/Http/Controllers/)

## 1. Purpose (الغرض)

طبقة Controllers توفر **HTTP Request Handling** و **API Endpoints**:
- **149 Controller Classes**: معالجة طلبات HTTP للحملات، التحليلات، المحتوى
- **ApiResponse Trait**: استجابات JSON موحدة عبر 111+ controllers
- **RESTful Architecture**: التزام بمبادئ REST API
- **Thin Controllers**: منطق الأعمال في Services، الكونترولرز رفيعة
- **33 Controller Domains**: منظمة حسب الوحدات الوظيفية

## 2. Owned Scope (النطاق المملوك)

### Controller Organization (149 Controllers)

```
app/Http/Controllers/
├── Concerns/
│   ├── ApiResponse.php            # Standardized JSON responses (111+ users)
│   └── ... (other traits)
│
├── Traits/
│   └── ... (legacy traits)
│
├── Campaign/                       # Campaign controllers
│   ├── CampaignController.php
│   ├── CampaignAnalyticsController.php
│   ├── CampaignOptimizationController.php
│   └── ...
│
├── Campaigns/                      # Alternative campaign namespace
│   └── ...
│
├── Analytics/                      # Analytics & reporting
│   ├── AnalyticsDashboardController.php
│   ├── MetricsController.php
│   ├── ReportsController.php
│   └── ...
│
├── AI/                             # AI features
│   ├── AIInsightsController.php
│   ├── AIAutomationController.php
│   ├── SemanticSearchController.php
│   └── ...
│
├── Social/                         # Social media
│   ├── SocialPostController.php
│   ├── SocialPublishingController.php
│   ├── SocialListeningController.php
│   └── ...
│
├── Content/                        # Content management
│   ├── ContentController.php
│   ├── ContentPlanController.php
│   └── ...
│
├── Creative/                       # Creative assets
│   ├── CreativeAssetController.php
│   ├── TemplateController.php
│   └── ...
│
├── Platform/                       # Platform integrations
│   ├── PlatformConnectionController.php
│   ├── PlatformSyncController.php
│   └── ...
│
├── AdPlatform/                     # Ad platform specific
│   ├── MetaAdsController.php
│   ├── GoogleAdsController.php
│   ├── TikTokAdsController.php
│   └── ...
│
├── Integration/                    # Integration management
│   ├── IntegrationController.php
│   ├── WebhookController.php
│   └── ...
│
├── OAuth/                          # OAuth flows
│   ├── OAuthController.php
│   ├── MetaOAuthController.php
│   ├── GoogleOAuthController.php
│   └── ...
│
├── Automation/                     # Automation features
│   ├── WorkflowController.php
│   ├── RuleEngineController.php
│   └── ...
│
├── Optimization/                   # Campaign optimization
│   ├── OptimizationController.php
│   ├── ABTestingController.php
│   └── ...
│
├── Admin/                          # Admin features
│   ├── AdminDashboardController.php
│   ├── FeatureFlagController.php
│   └── ...
│
├── Core/                           # Core system
│   ├── OrganizationController.php
│   ├── UserController.php
│   ├── PermissionController.php
│   └── ...
│
├── Auth/                           # Authentication
│   ├── LoginController.php
│   ├── RegisterController.php
│   ├── PasswordResetController.php
│   └── ...
│
├── Asset/                          # Media assets
│   ├── AssetController.php
│   ├── AssetLibraryController.php
│   └── ...
│
├── Audience/                       # Audience management
│   ├── AudienceController.php
│   ├── SegmentController.php
│   └── ...
│
├── Channels/                       # Marketing channels
│   └── ChannelController.php
│
├── Experiment/                     # A/B testing
│   ├── ExperimentController.php
│   └── ...
│
├── GPT/                            # GPT/AI operations
│   └── GPTController.php
│
├── Offerings/                      # Product offerings
│   └── OfferingController.php
│
├── Product/                        # Product management
│   └── ProductController.php
│
├── Service/                        # Service management
│   └── ServiceController.php
│
├── Settings/                       # Settings
│   └── SettingsController.php
│
├── Bundle/                         # Bundles
│   └── BundleController.php
│
├── Enterprise/                     # Enterprise features
│   └── EnterpriseController.php
│
├── API/                            # General API endpoints
│   └── ...
│
├── Api/                            # Alternative API namespace
│   └── ...
│
├── Web/                            # Web-specific controllers
│   └── ...
│
├── AdCampaignController.php        # Root-level controllers
├── AdCreativeController.php
├── ABTestingController.php
├── AdvancedSchedulingController.php
├── AnalyticsDashboardController.php
├── ApprovalController.php
├── AssetController.php
├── AudienceController.php
└── ... (70+ root-level controllers)
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Core Trait

#### ApiResponse Trait (`Concerns/ApiResponse.php`)
**Purpose**: Standardized JSON responses across 111+ controllers

**Methods**:
- `success($data, $message, $code = 200)`: Success response
- `error($message, $code = 400, $errors = null)`: Error response
- `created($data, $message)`: Resource created (201)
- `deleted($message)`: Resource deleted (200)
- `noContent()`: No content (204)
- `notFound($message)`: Not found (404)
- `unauthorized($message)`: Unauthorized (401)
- `forbidden($message)`: Forbidden (403)
- `validationError($errors, $message)`: Validation error (422)
- `serverError($message)`: Server error (500)
- `paginated($paginator, $message)`: Paginated response

**Usage**:
```php
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $campaigns = $this->service->getAllCampaigns();
        return $this->success($campaigns, 'Campaigns retrieved successfully');
    }

    public function store(Request $request)
    {
        $campaign = $this->service->createCampaign($request->validated());
        return $this->created($campaign, 'Campaign created successfully');
    }

    public function destroy($id)
    {
        $this->service->deleteCampaign($id);
        return $this->deleted('Campaign deleted successfully');
    }
}
```

### Major Controllers

#### CampaignController (`Campaign/CampaignController.php`)
**Purpose**: Campaign CRUD operations

**Key Methods**:
- `index()`: List all campaigns
- `store(Request $request)`: Create new campaign
- `show($id)`: Get campaign details
- `update(Request $request, $id)`: Update campaign
- `destroy($id)`: Delete campaign

**Example**:
```php
public function index()
{
    $campaigns = $this->campaignService->getAllCampaigns();
    return $this->success($campaigns, 'Campaigns retrieved successfully');
}

public function store(CampaignStoreRequest $request)
{
    $campaign = $this->campaignService->createCampaign($request->validated());
    return $this->created($campaign, 'Campaign created successfully');
}
```

#### AnalyticsDashboardController (`Analytics/AnalyticsDashboardController.php`)
**Purpose**: Analytics dashboard data

**Key Methods**:
- `index()`: Main dashboard
- `getCampaignMetrics($id)`: Campaign-specific metrics
- `getPerformanceTrends($id)`: Performance over time
- `getTopPerforming()`: Top campaigns

#### AIInsightsController (`AI/AIInsightsController.php`)
**Purpose**: AI-powered insights

**Key Methods**:
- `generateInsights($id)`: Generate AI insights for campaign
- `predictPerformance($id)`: Predict campaign performance
- `recommendOptimizations($id)`: AI optimization recommendations

#### OAuthController (`OAuth/OAuthController.php`)
**Purpose**: Platform OAuth flows

**Key Methods**:
- `redirect($platform)`: Redirect to platform authorization
- `callback($platform)`: Handle OAuth callback
- `refreshToken($integrationId)`: Refresh expired token
- `disconnect($integrationId)`: Disconnect integration

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Internal Dependencies
```
Request → Middleware → Controller → Service → Repository → Model
   ↓                      ↓
FormRequest          ApiResponse trait
```

### Service Layer Integration
```php
class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignService $campaignService,
        protected AnalyticsService $analyticsService
    ) {}

    public function index()
    {
        // Call service, not model directly
        $campaigns = $this->campaignService->getAllCampaigns();
        return $this->success($campaigns);
    }
}
```

### Request Validation
```php
// Use FormRequest for validation
public function store(CampaignStoreRequest $request)
{
    // $request->validated() already validated
    $campaign = $this->service->createCampaign($request->validated());
    return $this->created($campaign);
}
```

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Controller Pattern

#### ✅ Standard Controller Structure
```php
namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\Campaign\CampaignStoreRequest;
use App\Http\Requests\Campaign\CampaignUpdateRequest;
use App\Services\Campaign\CampaignService;

class CampaignController extends Controller
{
    use ApiResponse;

    /**
     * Constructor with dependency injection
     */
    public function __construct(
        protected CampaignService $campaignService
    ) {}

    /**
     * List all campaigns
     */
    public function index()
    {
        $campaigns = $this->campaignService->getAllCampaigns();
        return $this->success($campaigns, 'Campaigns retrieved successfully');
    }

    /**
     * Create new campaign
     */
    public function store(CampaignStoreRequest $request)
    {
        $campaign = $this->campaignService->createCampaign($request->validated());
        return $this->created($campaign, 'Campaign created successfully');
    }

    /**
     * Get campaign details
     */
    public function show(string $id)
    {
        $campaign = $this->campaignService->getCampaignById($id);

        if (!$campaign) {
            return $this->notFound('Campaign not found');
        }

        return $this->success($campaign, 'Campaign retrieved successfully');
    }

    /**
     * Update campaign
     */
    public function update(CampaignUpdateRequest $request, string $id)
    {
        $campaign = $this->campaignService->updateCampaign($id, $request->validated());

        if (!$campaign) {
            return $this->notFound('Campaign not found');
        }

        return $this->success($campaign, 'Campaign updated successfully');
    }

    /**
     * Delete campaign
     */
    public function destroy(string $id)
    {
        $deleted = $this->campaignService->deleteCampaign($id);

        if (!$deleted) {
            return $this->notFound('Campaign not found');
        }

        return $this->deleted('Campaign deleted successfully');
    }
}
```

### Controller Rules

#### ✅ DO:
- Use `ApiResponse` trait for all API controllers
- Inject services via constructor
- Use FormRequests for validation
- Keep controllers thin (< 200 lines)
- Return standardized JSON responses
- Document methods with PHPDoc

#### ❌ DON'T:
- Put business logic in controllers
- Access models directly (use services)
- Create custom response formats
- Hard-code error messages
- Skip validation
- Exceed 300 lines per controller

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Testing Controllers

```bash
# Run all controller tests
vendor/bin/phpunit tests/Feature/Controllers/

# Test specific controller
vendor/bin/phpunit tests/Feature/Controllers/CampaignControllerTest.php

# With coverage
vendor/bin/phpunit --coverage-html build/coverage tests/Feature/Controllers/
```

### Manual Testing

```bash
# Using curl
curl -X GET http://localhost/api/campaigns \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Using HTTPie
http GET localhost/api/campaigns \
  Authorization:"Bearer YOUR_TOKEN"
```

### Controller Test Pattern

```php
namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Campaign\Campaign;

class CampaignControllerTest extends TestCase
{
    public function test_index_returns_campaigns()
    {
        Campaign::factory()->count(5)->create();

        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => ['id', 'name', 'status']
                     ]
                 ]);
    }

    public function test_store_creates_campaign()
    {
        $data = [
            'name' => 'Test Campaign',
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/campaigns', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Campaign created successfully'
                 ]);

        $this->assertDatabaseHas('cmis.campaigns', ['name' => 'Test Campaign']);
    }

    public function test_destroy_deletes_campaign()
    {
        $campaign = Campaign::factory()->create();

        $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Campaign deleted successfully'
                 ]);

        $this->assertSoftDeleted('cmis.campaigns', ['id' => $campaign->id]);
    }
}
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Create New Controller

```bash
# Create controller with artisan
php artisan make:controller Campaign/CampaignController --api
```

```php
namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Campaign\CampaignService;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignService $campaignService
    ) {}

    public function index()
    {
        $campaigns = $this->campaignService->getAllCampaigns();
        return $this->success($campaigns, 'Campaigns retrieved successfully');
    }

    // ... other methods
}
```

### Add New Endpoint

```php
/**
 * Get campaign performance metrics
 */
public function getPerformanceMetrics(string $id)
{
    $metrics = $this->campaignService->getPerformanceMetrics($id);

    if (!$metrics) {
        return $this->notFound('Campaign not found');
    }

    return $this->success($metrics, 'Metrics retrieved successfully');
}
```

**Add route**:
```php
// routes/api.php
Route::get('campaigns/{id}/performance-metrics', [CampaignController::class, 'getPerformanceMetrics']);
```

### Create Controller Test

```bash
php artisan make:test Controllers/CampaignControllerTest --unit
```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Mistakes

1. **Business Logic in Controllers**
   ```php
   ❌ public function store() {
       $campaign = Campaign::create([...]);
       $campaign->metrics()->create([...]);
       // Complex logic here
   }

   ✅ public function store(Request $request) {
       $campaign = $this->service->createCampaign($request->validated());
       return $this->created($campaign);
   }
   ```

2. **Not Using ApiResponse Trait**
   ```php
   ❌ return response()->json(['success' => true, ...]);

   ✅ return $this->success($data, 'Success message');
   ```

3. **Missing Authorization**
   ```php
   ❌ public function destroy($id) {
       $this->service->deleteCampaign($id);
   }

   ✅ public function destroy($id) {
       $this->authorize('delete', Campaign::find($id));
       $this->service->deleteCampaign($id);
   }
   ```

### 🎯 Best Practices

1. **Use Resource Classes**
   ```php
   use App\Http\Resources\CampaignResource;

   public function show($id)
   {
       $campaign = $this->service->getCampaignById($id);
       return $this->success(new CampaignResource($campaign));
   }
   ```

2. **Pagination**
   ```php
   public function index()
   {
       $campaigns = $this->service->getAllCampaigns();
       return $this->paginated($campaigns, 'Campaigns retrieved');
   }
   ```

3. **Error Handling**
   ```php
   public function store(Request $request)
   {
       try {
           $campaign = $this->service->createCampaign($request->validated());
           return $this->created($campaign);
       } catch (\Exception $e) {
           return $this->serverError('Failed to create campaign');
       }
   }
   ```

### 📊 Statistics

- **Total Controllers**: 149 files
- **Using ApiResponse Trait**: 111+ controllers (75%)
- **Controller Domains**: 33 namespaces
- **Average Size**: ~150 lines per controller
- **RESTful Controllers**: 90%+

### 🔗 Related Files

- **Services**: `app/Services/` - Business logic layer
- **Requests**: `app/Http/Requests/` - Validation logic
- **Resources**: `app/Http/Resources/` - Response formatting
- **Middleware**: `app/Http/Middleware/` - Request filtering
- **Routes**: `routes/api.php`, `routes/web.php` - Route definitions
