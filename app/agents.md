# دليل الوكلاء - Application Layer (app/)

## 1. Purpose (الغرض)

طبقة التطبيق (Application Layer) هي القلب النابض لـ CMIS، تحتوي على:
- **Business Logic**: في Services/
- **Data Models**: في Models/ (244 models across 51 domains)
- **Data Access**: في Repositories/
- **HTTP Interface**: في Http/ (Controllers, Middleware, Requests, Resources)
- **Platform Integrations**: في Integrations/ (Meta, Google, TikTok, etc.)
- **CLI Commands**: في Console/
- **Background Jobs**: في Jobs/
- **Event System**: في Events/ و Listeners/

## 2. Owned Scope (النطاق المملوك)

### Directory Structure
```
app/
├── Models/               # 244 models (51 domains)
│   ├── Core/            # Organization, User, Permission
│   ├── Campaign/        # Campaign management
│   ├── Platform/        # Ad platform entities
│   ├── Social/          # Social media entities
│   ├── AI/              # Embeddings, semantic search
│   ├── Analytics/       # Metrics, reports
│   └── ... (46 more domains)
│
├── Services/            # Business logic layer
│   ├── AI/              # EmbeddingOrchestrator, semantic search
│   ├── AdPlatforms/     # Meta, Google, TikTok, LinkedIn, Twitter, Snapchat
│   ├── Analytics/       # Analytics processing
│   ├── Campaign/        # Campaign management services
│   ├── Connectors/      # 14 platform connectors with unified interface
│   └── ... (30+ service domains)
│
├── Repositories/        # Data access layer
│   ├── Contracts/       # Repository interfaces
│   ├── CMIS/           # Core repositories
│   ├── Analytics/      # Analytics repositories
│   └── ...
│
├── Http/               # HTTP interface
│   ├── Controllers/    # 111+ API controllers
│   ├── Middleware/     # Auth, RLS, Rate limiting
│   ├── Requests/       # Form request validation
│   └── Resources/      # API resource transformers
│
├── Integrations/       # Platform OAuth & API clients
│   ├── Meta/           # Facebook/Instagram
│   ├── Google/         # Google Ads
│   ├── TikTok/         # TikTok Marketing
│   ├── LinkedIn/       # LinkedIn Ads
│   ├── Twitter/        # Twitter/X
│   └── Base/           # Base OAuth & API clients
│
├── Console/            # Artisan commands
│   └── Commands/       # 30+ custom CLI commands
│
├── Jobs/               # Queue jobs
├── Events/             # Application events
├── Listeners/          # Event listeners
├── Notifications/      # Notification channels
├── Policies/           # Authorization policies
├── Providers/          # Service providers
├── Exceptions/         # Custom exceptions
├── Traits/             # Reusable traits
├── Support/            # Helper utilities
└── Validators/         # Custom validators
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Core Models
- `Models/BaseModel.php`: Base model (UUID, RLS, SoftDeletes)
- `Models/Concerns/HasOrganization.php`: Multi-tenancy trait
- `Models/Core/Organization.php`: Root entity for multi-tenancy
- `Models/Core/User.php`: User authentication & authorization

### Core Services
- `Services/CMIS/OrganizationContextService.php`: RLS context management
- `Services/AI/EmbeddingOrchestrator.php`: AI embeddings orchestration
- `Services/AdPlatforms/AbstractAdPlatform.php`: Base class for platform integrations

### Core HTTP
- `Http/Controllers/Concerns/ApiResponse.php`: Standardized API responses
- `Http/Middleware/SetOrganizationContext.php`: RLS middleware
- `Http/Kernel.php`: HTTP middleware pipeline

### Support Files
- `Support/helpers.php`: Global helper functions

### Service Providers
- `Providers/AppServiceProvider.php`: Main service provider
- `Providers/AuthServiceProvider.php`: Authorization logic
- `Providers/RouteServiceProvider.php`: Route configuration

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Internal Dependencies Flow
```
Controllers → Services → Repositories → Models → Database
     ↓
Middleware (SetOrganizationContext)
     ↓
RLS Context (init_transaction_context)
```

### External Dependencies
- Laravel Framework (HTTP, Database, Queue, Cache)
- Guzzle (HTTP client for platform APIs)
- Redis (Caching & Queue)
- PostgreSQL (Database with RLS)

### Interface Contracts
- `Repositories/Contracts/*`: Repository interfaces
- `Services/AdPlatforms/Contracts/*`: Platform service contracts
- `Services/Connectors/Contracts/*`: Connector interfaces

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Architectural Patterns

#### 1. Repository + Service Pattern
```php
// ✅ Always follow this flow:
Controller → Service (business logic) → Repository (data access) → Model
```

#### 2. Trait-Based Reusability
- Use `ApiResponse` trait in controllers
- Use `HasOrganization` trait in models
- Use `HasRLSPolicies` trait in migrations

#### 3. Template Method Pattern (Platform Services)
```php
// AdPlatforms use Template Method pattern
abstract class AbstractAdPlatform
{
    abstract protected function authenticate(): void;
    abstract protected function refreshToken(): void;
}
```

### Naming Conventions

#### Models
- Singular names: `Campaign`, `User`, `Organization`
- Namespace by domain: `App\Models\Campaign\Campaign`
- Extend `BaseModel`, use `HasOrganization` trait

#### Services
- Suffix with `Service`: `CampaignService`, `AnalyticsService`
- Namespace by domain: `App\Services\Campaign\CampaignService`
- Constructor injection for dependencies

#### Repositories
- Suffix with `Repository`: `CampaignRepository`
- Implement contract interfaces
- Return models or collections

#### Controllers
- Suffix with `Controller`: `CampaignController`
- Use `ApiResponse` trait
- Keep thin (< 50 lines per method)

## 6. Module Statistics

- **Total Files**: 712 PHP files
- **Models**: 244 models (51 domains)
- **Services**: 100+ service classes
- **Repositories**: 50+ repository classes
- **Controllers**: 111+ controllers
- **Middleware**: 15+ middleware classes
- **Commands**: 30+ Artisan commands
- **Connectors**: 14 platform connectors
- **Integrations**: 5 OAuth clients

## 7. Sub-Module Agents

للحصول على معلومات تفصيلية حول كل وحدة فرعية:

- **Models**: `app/Models/agents.md`
- **Services**: `app/Services/agents.md`
- **Connectors**: `app/Services/Connectors/agents.md`
- **Integrations**: `app/Integrations/agents.md`
- **Repositories**: `app/Repositories/agents.md`
- **HTTP**: `app/Http/agents.md`
- **Console**: `app/Console/agents.md`
