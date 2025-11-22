# دليل الوكلاء - HTTP Layer (app/Http/)

## 1. Purpose

طبقة HTTP تدير جميع requests/responses:
- **111+ Controllers**: API endpoints
- **15+ Middleware**: Auth, RLS, Rate limiting
- **Form Requests**: Input validation
- **API Resources**: Response formatting

## 2. Owned Scope

```
app/Http/
├── Controllers/          # 111+ controllers
│   ├── Concerns/
│   │   └── ApiResponse.php   # Standardized responses
│   ├── AI/              # AI endpoints
│   ├── Campaign/        # Campaign management
│   ├── Analytics/       # Analytics endpoints
│   ├── Platform/        # Platform integrations
│   ├── OAuth/           # OAuth flows
│   └── ... (25+ domains)
│
├── Middleware/          # 15+ middleware
│   ├── SetOrganizationContext.php  # RLS context
│   ├── Authenticate.php
│   ├── RateLimiter.php
│   └── ...
│
├── Requests/            # Form validation
│   ├── AI/
│   ├── Campaign/
│   └── ...
│
└── Resources/           # API transformers
    ├── Campaign/
    ├── Creative/
    └── ...
```

## 3. Key Files

- `Controllers/Concerns/ApiResponse.php`: Trait for standardized JSON responses
- `Middleware/SetOrganizationContext.php`: Sets RLS context for multi-tenancy
- `Kernel.php`: Middleware pipeline configuration

## 4. Patterns

### Controller Pattern (with ApiResponse)
```php
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(protected CampaignService $service) {}

    public function index()
    {
        $campaigns = $this->service->getAllCampaigns();
        return $this->success($campaigns, 'Campaigns retrieved');
    }

    public function store(CampaignRequest $request)
    {
        $campaign = $this->service->createCampaign($request->validated());
        return $this->created($campaign, 'Campaign created');
    }
}
```

### Middleware Pattern
```php
class SetOrganizationContext
{
    public function handle(Request $request, Closure $next)
    {
        $orgId = auth()->user()->org_id;
        DB::statement("SELECT init_transaction_context(?)", [$orgId]);
        return $next($request);
    }
}
```

## 5. Common Tasks

- **Create Controller**: Keep thin, delegate to Services
- **Add Middleware**: Register in `Kernel.php`
- **Validate Input**: Use FormRequest classes
- **Format Output**: Use API Resources or ApiResponse trait

## 6. Notes

- ✅ **Always** use ApiResponse trait
- ✅ Keep controllers < 50 lines per method
- ✅ Use FormRequests for validation
- ❌ **Never** put business logic in controllers
