# دليل الوكلاء - CMIS Project

## 1. Purpose (الغرض)

**CMIS** (Cognitive Marketing Intelligence Suite) هو نظام إدارة حملات تسويقية متكامل مبني على Laravel 12، يوفر:
- **Multi-tenancy**: عزل البيانات بين المؤسسات باستخدام PostgreSQL Row-Level Security (RLS)
- **Platform Integration**: تكامل مع Meta, Google Ads, TikTok, LinkedIn, Twitter, Snapchat
- **AI-Powered Analytics**: بحث دلالي عبر pgvector + Google Gemini embeddings
- **Campaign Management**: إدارة شاملة للحملات الإعلانية والمحتوى
- **Social Publishing**: نشر وإدارة المحتوى عبر منصات التواصل الاجتماعي

## 2. Owned Scope (النطاق المملوك)

المشروع يمتلك:
- **712 PHP files**: طبقات Application, Services, Repositories, HTTP
- **244 Models**: موزعة على 51 domain (AI, Campaign, Platform, Social, Analytics, etc.)
- **45 Migrations**: بناء قاعدة بيانات مع RLS policies
- **201 Tests**: Unit, Feature, Integration, E2E (Playwright)
- **Database**: 12 PostgreSQL schemas, 197 tables, pgvector extensions
- **Frontend**: Alpine.js + Tailwind CSS + Chart.js
- **26 Specialized Agents**: في `.claude/agents/` للمهام المتخصصة

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Configuration Files
- `composer.json`: Laravel 12, PHP 8.2+, dependencies
- `package.json`: Alpine.js, Chart.js, Playwright, Vite
- `phpunit.xml`: PHPUnit configuration (Unit, Feature, Integration tests)
- `playwright.config.ts`: Playwright E2E testing configuration
- `.env.example`: Environment variables template
- `CLAUDE.md`: دليل المشروع الشامل للوكلاء

### Application Entry Points
- `public/index.php`: Laravel application entry point
- `artisan`: CLI entry point for Artisan commands
- `routes/api.php`: 142KB API routes (primary API interface)
- `routes/web.php`: Web interface routes
- `app/Http/Kernel.php`: HTTP middleware pipeline
- `app/Console/Kernel.php`: Scheduled tasks & commands

### Database Entry Points
- `database/migrations/`: Schema migrations with RLS policies
- `database/seeders/DatabaseSeeder.php`: Data seeding entry point
- `database/schema.sql`: Complete database schema

### Core Models
- `app/Models/BaseModel.php`: Base model with UUID, RLS, soft deletes
- `app/Models/Core/Organization.php`: Multi-tenancy root entity
- `app/Models/Core/User.php`: User authentication & authorization
- `app/Models/Campaign/Campaign.php`: Campaign management core

### Core Services
- `app/Services/AI/EmbeddingOrchestrator.php`: AI embeddings orchestration
- `app/Services/AdPlatforms/`: Platform integration services (Meta, Google, TikTok, etc.)
- `app/Services/CMIS/OrganizationContextService.php`: Multi-tenancy context management

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### External Dependencies

#### Backend (PHP/Laravel)
- **Laravel Framework**: ^12.0 (Core framework)
- **Guzzle**: HTTP client للتكامل مع APIs خارجية
- **Sanctum**: ^4.2 (API token authentication)
- **Predis**: Redis client للتخزين المؤقت

#### Database
- **PostgreSQL**: 15+ (مع pgvector extension)
- **Redis**: للـ caching والـ queue

#### Testing
- **PHPUnit**: ^11.5.3 (Unit & Feature tests)
- **Playwright**: ^1.40.0 (E2E tests)
- **Mockery**: ^1.6 (Mocking framework)
- **Paratest**: ^7.8 (Parallel test execution)

#### Frontend
- **Alpine.js**: ^3.13.5 (Reactive UI framework)
- **Chart.js**: ^4.4.1 (Analytics charts)
- **Axios**: ^1.11.0 (HTTP client)
- **Tailwind CSS**: ^3.4.1 (Utility-first CSS)
- **Vite**: ^7.0.7 (Build tool)

#### API Documentation
- **L5-Swagger**: ^9.0 (OpenAPI documentation)
- **Scribe**: ^5.5 (API documentation generator)

### Platform Integrations (External APIs)
- **Meta Business Suite API**: Facebook/Instagram ads
- **Google Ads API**: Google advertising platform
- **TikTok Marketing API**: TikTok ads
- **LinkedIn Marketing API**: LinkedIn ads
- **Twitter API**: Twitter/X integration
- **Snapchat Marketing API**: Snapchat ads
- **Google Gemini API**: AI embeddings & semantic search

### Internal Interfaces

#### Service Layer Pattern
```php
// Services تعتمد على Repositories
Services → Repositories → Models → Database
```

#### Controller Pattern
```php
// Controllers تستخدم Services وترجع JSON responses
Controllers → Services → Repositories
          ↓
    ApiResponse Trait (standardized responses)
```

#### Multi-tenancy Flow
```php
// كل request يمر عبر SetOrganizationContext middleware
Request → Middleware (SetOrganizationContext)
       → init_transaction_context(org_id)
       → RLS policies enforce data isolation
```

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### ⚠️ Critical Rules (يجب الالتزام بها دائماً)

#### Multi-Tenancy (RLS)
- ✅ **ALWAYS** استخدم `init_transaction_context(org_id)` قبل أي عملية DB
- ✅ **ALWAYS** استخدم schema-qualified table names: `cmis.campaigns`
- ❌ **NEVER** استخدم hard-delete (استخدم soft deletes فقط)
- ❌ **NEVER** bypass RLS بـ manual `WHERE org_id = ?` filtering
- ✅ اختبر بـ multiple organizations للتحقق من data isolation

#### Model Patterns
- ✅ **ALWAYS** extend `BaseModel` (not `Model` directly)
- ✅ **ALWAYS** use `HasOrganization` trait for org relationships
- ✅ Models تستخدم UUID primary keys (via `BaseModel`)
- ✅ Models تطبق `OrgScope` global scope تلقائياً

#### Controller Patterns
- ✅ **ALWAYS** use `ApiResponse` trait for JSON responses
- ✅ Keep controllers thin - delegate business logic to Services
- ❌ **NEVER** put business logic in controllers

#### Repository + Service Pattern
```php
// ✅ Correct pattern
Controller → Service (business logic) → Repository (data access) → Model

// ❌ Wrong pattern
Controller → Model directly
```

#### Migration Patterns
- ✅ **ALWAYS** use `HasRLSPolicies` trait in migrations
- ✅ Create RLS policies with `$this->enableRLS('cmis.table_name')`
- ✅ Drop RLS policies in `down()` method
- ❌ **NEVER** create tables without RLS policies

### Code Quality Standards

#### PSR-12 Compliance
- Follow Laravel conventions
- Use type hints for all method parameters and return types
- Document complex algorithms

#### Security
- ✅ Validate platform webhook signatures
- ✅ Store credentials in Laravel encrypted storage
- ✅ Rate limit AI operations (30/min, 500/hour for Gemini)
- ✅ Sanitize all user inputs
- ❌ **NEVER** commit `.env` files or credentials

#### Testing Requirements
- ✅ Write tests for **ALL** business logic
- ✅ Test multi-tenancy isolation
- ✅ Mock platform integrations
- Current test suite: 201 tests with 33.4% pass rate (actively improving)

### Database Patterns

#### RLS Policy Pattern
```sql
-- All tables have RLS policies
CREATE POLICY org_isolation_policy ON cmis.table_name
    USING (org_id = current_setting('app.current_org_id')::uuid);
```

#### Soft Delete Pattern
```php
// All models use soft deletes
use SoftDeletes;
protected $dates = ['deleted_at'];
```

### Response Standardization

#### API Responses (via ApiResponse trait)
```php
// Success
return $this->success($data, 'Message');

// Error
return $this->error('Error message', 400);

// Created
return $this->created($data, 'Resource created');

// Pagination
return $this->paginated($paginator, 'Success');
```

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Initial Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Database setup (PostgreSQL required)
# Configure .env with DB credentials:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=cmis
# DB_USERNAME=begin
# DB_PASSWORD=123@Marketing@321

# 4. Run migrations
php artisan migrate:fresh --seed

# 5. Build frontend assets
npm run build
```

### Development Workflow

```bash
# Run development server (all services)
composer dev
# This runs: Laravel server + Queue + Logs + Vite (concurrently)

# OR run individually:
php artisan serve          # Laravel dev server (http://localhost:8000)
php artisan queue:work     # Queue worker
php artisan pail           # Real-time logs
npm run dev                # Vite dev server
```

### Testing

```bash
# Run all PHPUnit tests
composer test
# OR
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --testsuite=Integration

# Run parallel tests (faster)
vendor/bin/paratest

# Run E2E tests (Playwright)
npm run test:e2e
npm run test:e2e:ui        # With UI
npm run test:e2e:debug     # Debug mode

# Run all tests (PHPUnit + Playwright)
npm run test:all

# Code coverage
vendor/bin/phpunit --coverage-html build/coverage/html
```

### Database Operations

```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status

# Database console (PostgreSQL)
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis

# Check RLS context (in psql)
SELECT current_setting('app.current_org_id', true);
```

### Cache & Optimization

```bash
# Clear all caches
php artisan optimize:clear

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Generate IDE helpers
php artisan ide-helper:generate
```

### Custom Commands (Slash Commands)

```bash
# Available in Claude Code
/test              # Run Laravel test suite
/migrate           # Run migrations with safety checks
/audit-rls         # Audit RLS policies
/optimize-db       # Optimize database performance
/create-agent      # Create new specialized agent
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Working with Models

#### Create New Model
1. Extend `BaseModel` (not `Model`)
2. Use `HasOrganization` trait if table has `org_id`
3. Define `$fillable`, `$casts`, relationships
4. Create corresponding migration with RLS policies

```php
namespace App\Models\YourDomain;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;

class YourModel extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.your_table';
    protected $fillable = ['name', 'org_id'];

    // Relationships...
}
```

### Working with Migrations

#### Create Migration with RLS
```php
use Database\Migrations\Concerns\HasRLSPolicies;

class CreateYourTable extends Migration
{
    use HasRLSPolicies;

    public function up()
    {
        Schema::create('cmis.your_table', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            // ... columns
        });

        $this->enableRLS('cmis.your_table');
    }

    public function down()
    {
        $this->disableRLS('cmis.your_table');
        Schema::dropIfExists('cmis.your_table');
    }
}
```

### Working with Controllers

#### Create API Controller
```php
use App\Http\Controllers\Concerns\ApiResponse;

class YourController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = YourModel::all();
        return $this->success($data, 'Data retrieved successfully');
    }

    public function store(Request $request)
    {
        $model = YourModel::create($request->validated());
        return $this->created($model, 'Resource created');
    }
}
```

### Working with Services

#### Create Service
```php
namespace App\Services\YourDomain;

class YourService
{
    public function __construct(
        protected YourRepository $repository
    ) {}

    public function processData(array $data): Model
    {
        // Business logic here
        return $this->repository->create($data);
    }
}
```

### Working with Tests

#### Create Feature Test
```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Core\Organization;

class YourTest extends TestCase
{
    public function test_multi_tenancy_isolation()
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Test RLS isolation
        $this->actingAsOrganization($org1);
        // ... assertions
    }
}
```

### Using Specialized Agents

عندما تواجه مهمة معقدة، استخدم الوكلاء المتخصصين من `.claude/agents/`:

- **Multi-tenancy issues**: Use `cmis-multi-tenancy` agent
- **Platform integration**: Use `cmis-platform-integration` agent
- **AI/semantic search**: Use `cmis-ai-semantic` agent
- **Database migrations**: Use `laravel-db-architect` agent
- **Testing**: Use `laravel-testing` agent
- **Code quality**: Use `laravel-code-quality` agent
- **Complex orchestration**: Use `cmis-orchestrator` agent

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Pitfalls

1. **Forgetting RLS Context**
   - Problem: Queries return no data or wrong org's data
   - Solution: Always call `init_transaction_context(org_id)` in middleware/services

2. **Unqualified Table Names**
   - Problem: Table not found errors
   - Solution: Use schema-qualified names: `cmis.table_name`

3. **Missing Indexes**
   - Problem: Slow queries
   - Solution: Add indexes for foreign keys and frequently searched columns

4. **Hardcoded Org Filtering**
   - Problem: Bypasses RLS, breaks isolation
   - Solution: Let RLS handle filtering, don't add manual `WHERE org_id = ?`

5. **Platform Token Expiration**
   - Problem: API calls fail after token expires
   - Solution: Implement refresh token logic in platform services

6. **AI Rate Limits**
   - Problem: Gemini API rate limit errors
   - Solution: Queue AI operations, don't run synchronously (30/min, 500/hour)

7. **N+1 Query Problems**
   - Problem: Too many database queries
   - Solution: Use eager loading: `Model::with(['relation1', 'relation2'])->get()`

8. **Test Database Isolation**
   - Problem: Tests interfere with each other
   - Solution: Use `RefreshDatabase` trait, test in transactions

### 🎯 Best Practices

- **Always prefer editing existing files** over creating new ones
- **Use Repository + Service pattern** for complex business logic
- **Write tests first** for critical features (TDD)
- **Document complex algorithms** with inline comments
- **Use type hints** for all parameters and return types
- **Follow PSR-12** coding standards
- **Keep controllers thin** (< 50 lines per method)
- **Avoid over-engineering** - YAGNI principle

### 📊 Project Status (as of 2025-11-22)

- **Completion**: ~55-60%
- **Test Pass Rate**: 33.4% (actively improving)
- **Code Quality**: 13,100 lines of duplication eliminated (Nov 2025)
- **Current Phase**: Platform Integration & AI Features (Phase 2-3)

### 📚 Documentation References

- **Main Docs**: `docs/README.md`
- **Project Guide**: `CLAUDE.md`
- **Phase Docs**: `docs/phases/`
- **Agent Guides**: `.claude/agents/README.md`
- **Testing Docs**: `docs/testing/`

---

## 🗺️ Agent Map (خريطة الوكلاء)

لمزيد من التفاصيل حول كل وحدة، راجع ملفات `agents.md` في المجلدات الفرعية:

| Module | Path | Focus |
|--------|------|-------|
| **Application Layer** | `app/agents.md` | نظرة عامة على طبقة التطبيق |
| **Models** | `app/Models/agents.md` | 244 نموذج عبر 51 domain |
| **Services** | `app/Services/agents.md` | Business logic & platform integrations |
| **Connectors** | `app/Services/Connectors/agents.md` | Unified platform connector interface (14 platforms) |
| **Integrations** | `app/Integrations/agents.md` | OAuth & API clients (low-level) |
| **Repositories** | `app/Repositories/agents.md` | Data access layer patterns |
| **HTTP Layer** | `app/Http/agents.md` | Controllers, Middleware, Requests |
| **Console** | `app/Console/agents.md` | Artisan commands |
| **Database** | `database/agents.md` | Migrations, Seeders, Factories, RLS |
| **Frontend** | `resources/agents.md` | Alpine.js components, Blade views |
| **Routes** | `routes/agents.md` | API & Web routing (500+ endpoints) |
| **Tests** | `tests/agents.md` | Unit, Feature, Integration, E2E |
| **Scripts** | `scripts/agents.md` | Deployment & upgrade automation |
| **System Tools** | `system/agents.md` | GPT runtime & database optimization |

---

**تم التحديث**: 2025-11-22
**الإصدار**: 3.2 - Post Duplication Elimination
