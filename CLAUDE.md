# CMIS Project Guidelines for Claude Code

**Last Updated:** 2025-11-22 (Documentation Restructure)
**Project:** CMIS - Cognitive Marketing Information System
**Framework Version:** 3.2 - Post Duplication Elimination (13,100 lines saved)

---

## 🎯 Project Overview

CMIS is a Laravel-based Campaign Management & Integration System with:
- **Multi-tenancy:** PostgreSQL Row-Level Security (RLS)
- **Database:** 12 schemas, 197 tables, pgvector for AI
- **Codebase:** 712 PHP files, 244 Models (51 domains), 45 migrations, 201 tests
- **Platforms:** Meta, Google, TikTok, LinkedIn, Twitter, Snapchat
- **AI:** Semantic search via Google Gemini + pgvector
- **Frontend:** Alpine.js, Tailwind CSS, Chart.js

---

## 🚨 Critical Rules

### Multi-Tenancy (ALWAYS RESPECT)
- ✅ ALL database operations MUST respect RLS policies
- ✅ Use schema-qualified table names: `cmis.campaigns`, `cmis_meta.ad_accounts`
- ✅ NEVER bypass RLS with manual org_id filtering
- ✅ Test with multiple organizations for data isolation
- ❌ NEVER hard-delete records (use soft deletes)

### Database Operations
- ✅ Use migrations for ALL schema changes
- ✅ Add RLS policies to new tables via migrations
- ✅ Use `init_transaction_context(org_id)` in Laravel
- ✅ Qualify all table names with schema prefix
- ❌ NEVER create tables without RLS policies

### Security
- ✅ Validate all platform webhook signatures
- ✅ Store credentials in Laravel encrypted storage
- ✅ Rate limit AI operations (30/min, 500/hour for Gemini)
- ✅ Sanitize all user inputs
- ❌ NEVER commit .env files or credentials

### Code Quality
- ✅ Follow Repository + Service pattern
- ✅ Use Laravel conventions (PSR-12)
- ✅ Write tests for ALL business logic
- ✅ Document complex algorithms
- ❌ NEVER put business logic in controllers

### Standardized Patterns (NEW - 2025-11-21)
- ✅ **Controllers:** Use `ApiResponse` trait for all JSON responses
- ✅ **Models:** Use `HasOrganization` trait for org relationships
- ✅ **Models:** Extend `BaseModel` for UUID and RLS setup
- ✅ **Migrations:** Use `HasRLSPolicies` trait for RLS policies
- ❌ NEVER create duplicate response/relationship patterns
- ❌ NEVER create stub services (use mocking in tests)

---

## ⚙️ Environment Configuration

### IMPORTANT: Always Check .env for Current Configuration

**NEVER use hardcoded database names or environment values.** Always check the `.env` file for the current environment configuration.

### How to Get Database Name and Connection Info

```bash
# View database configuration
cat .env | grep DB_

# Common variables:
# DB_CONNECTION=pgsql          # Database driver
# DB_HOST=127.0.0.1            # Database host
# DB_PORT=5432                 # Database port
# DB_DATABASE=<actual-db-name> # Database name (varies by environment)
# DB_USERNAME=<username>        # Database username
# DB_PASSWORD=<password>        # Database password
```

### Key Environment Variables

| Variable | Purpose | Example |
|----------|---------|---------|
| `DB_DATABASE` | Database name (environment-specific) | Check `.env` |
| `DB_HOST` | Database server host | `127.0.0.1` |
| `DB_PORT` | Database server port | `5432` |
| `DB_USERNAME` | Database user | Check `.env` |
| `DB_PASSWORD` | Database password | Check `.env` (never commit!) |
| `DB_SCHEMA_SEARCH_PATH` | PostgreSQL schema search path | `public,cmis,cmis_refactored,...` |
| `APP_ENV` | Application environment | `local`, `staging`, `production` |
| `APP_DEBUG` | Debug mode | `true` (local), `false` (production) |

### Database Connection Commands

```bash
# Connect to PostgreSQL (use .env values)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)"

# Or use Laravel tinker
php artisan tinker
>>> DB::connection()->getDatabaseName()  # Shows current database name
```

### Configuration Files

- **`.env`** - Environment-specific configuration (NEVER commit to git)
- **`config/database.php`** - Database connection definitions (reads from `.env`)
- **`config/app.php`** - Application configuration (reads from `.env`)

### Best Practices

1. ✅ **Always read from `.env`** when you need database name or credentials
2. ✅ **Use `env()` helper** in config files: `env('DB_DATABASE', 'fallback')`
3. ✅ **Use `config()` helper** in application code: `config('database.connections.pgsql.database')`
4. ❌ **NEVER hardcode** database names like `cmis-test`, `cmis-prod`, etc.
5. ❌ **NEVER commit** `.env` file to version control
6. ❌ **NEVER assume** database names across environments

---

## 📁 Repository Structure

```
app/
├── Models/
│   ├── Core/          # Organizations, users, permissions
│   ├── Campaign/      # Campaigns, content, budgets
│   ├── Platform/      # Ad platforms, accounts, entities
│   ├── Social/        # Social media, posts, engagement
│   └── AI/            # Embeddings, semantic search
├── Services/          # Business logic layer
├── Repositories/      # Data access layer
└── Http/
    ├── Controllers/   # Keep thin - delegate to services
    └── Middleware/    # Auth, tenancy, rate limiting

database/
├── migrations/        # Version-controlled schema
└── seeders/          # Test data generators

.claude/
├── agents/           # Specialized AI agents (26 total)
├── knowledge/        # Project documentation
├── commands/         # Custom slash commands (5 commands)
├── hooks/            # Automation scripts
└── settings.local.json
```

---

## 🤖 Agent Usage

### When to Use Specialized Agents

| Task | Use Agent | Reason |
|------|-----------|--------|
| Multi-tenancy issues | `cmis-multi-tenancy` | RLS expert |
| Platform integration | `cmis-platform-integration` | OAuth/webhook specialist |
| AI/semantic search | `cmis-ai-semantic` | pgvector + Gemini expert |
| Campaign features | `cmis-campaign-expert` | Campaign domain expert |
| Frontend/UI | `cmis-ui-frontend` | Alpine.js + Tailwind specialist |
| Database work | `laravel-db-architect` | PostgreSQL + migrations |
| Complex tasks | `cmis-orchestrator` | Multi-agent coordinator |

### Agent Best Practices
- Use `haiku` model for lightweight agents (cost-effective)
- Use `sonnet` model for complex reasoning tasks
- Limit tool access to what each agent needs
- Check `.claude/agents/README.md` for full agent list

---

## 🔧 Development Workflow

### Git & Branching
- **Branch naming:** `claude/<feature-name>-<session-id>`
- **Commit format:** Clear, descriptive messages
- **Push policy:** Always use `git push -u origin <branch-name>`
- **NEVER** push to main/master directly

### Testing Requirements
- ✅ Unit tests for all service methods
- ✅ Feature tests for API endpoints
- ✅ Multi-tenancy isolation tests
- ✅ Platform integration mocking
- Run: `vendor/bin/phpunit`
- **Test Suite:** 201 test files with 33.4% pass rate (improving continuously)

### Database Migrations
```php
// NEW: Use HasRLSPolicies trait for standardized RLS policies
use Database\Migrations\Concerns\HasRLSPolicies;

class CreateNewTable extends Migration
{
    use HasRLSPolicies;

    public function up()
    {
        // 1. Create table
        Schema::create('cmis.new_table', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            // ... columns
        });

        // 2. Enable RLS with single line (replaces manual SQL)
        $this->enableRLS('cmis.new_table');
    }

    public function down()
    {
        $this->disableRLS('cmis.new_table');
        Schema::dropIfExists('cmis.new_table');
    }
}

// For custom RLS logic:
// $this->enableCustomRLS('cmis.table_name', 'custom_expression');
// $this->enablePublicRLS('cmis.public_table'); // For shared tables
```

### Code Review Checklist
- [ ] Multi-tenancy respected?
- [ ] RLS policies added?
- [ ] Tests written and passing?
- [ ] Security validated?
- [ ] Documentation updated?
- [ ] No hardcoded credentials?

---

## 🎨 Frontend Conventions

### Alpine.js Components
```html
<!-- Use x-data for component state -->
<div x-data="campaignDashboard()">
    <button @click="loadMetrics">Load</button>
    <div x-show="loading">Loading...</div>
</div>
```

### Tailwind Utilities
- Use `@apply` in components sparingly
- Prefer utility classes in templates
- Custom colors in `tailwind.config.js`

### Chart.js Integration
- Store in `resources/js/components/`
- Use Alpine.js for state management
- Async data loading via Axios

---

## 🎯 Standardized Traits & Patterns (NEW)

### Controllers: ApiResponse Trait
```php
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $campaigns = Campaign::all();
        return $this->success($campaigns, 'Campaigns retrieved successfully');
    }

    public function store(Request $request)
    {
        $campaign = Campaign::create($request->validated());
        return $this->created($campaign, 'Campaign created successfully');
    }

    public function destroy($id)
    {
        Campaign::findOrFail($id)->delete();
        return $this->deleted('Campaign deleted successfully');
    }
}

// Available methods:
// - success($data, $message, $code = 200)
// - error($message, $code = 400, $errors = null)
// - created($data, $message)
// - deleted($message)
// - notFound($message)
// - unauthorized($message)
// - forbidden($message)
// - validationError($errors, $message)
// - serverError($message)
// - paginated($paginator, $message)
```

### Models: HasOrganization Trait
```php
use App\Models\Concerns\HasOrganization;

class Campaign extends BaseModel
{
    use HasOrganization;

    // Now you have:
    // - org() relationship
    // - scopeForOrganization($orgId)
    // - belongsToOrganization($orgId)
    // - getOrganizationId()
}

// Usage:
$campaign->org; // Get organization
$campaigns = Campaign::forOrganization($orgId)->get();
```

### Models: BaseModel Pattern
```php
// ALWAYS extend BaseModel, not Model directly
use App\Models\BaseModel;

class YourModel extends BaseModel
{
    use HasOrganization;

    // BaseModel handles:
    // - UUID primary keys
    // - Automatic UUID generation
    // - RLS context awareness
    // - Common model patterns
}
```

---

## 🔌 Platform Integration

### OAuth Flow Pattern
1. Redirect to platform authorization URL
2. Handle callback with authorization code
3. Exchange code for access + refresh tokens
4. Store encrypted in `cmis_platform.platform_credentials`
5. Implement token refresh logic

### Webhook Handling
```php
// ALWAYS verify signatures
public function handleWebhook(Request $request)
{
    if (!$this->verifySignature($request)) {
        abort(403, 'Invalid signature');
    }
    // Process webhook...
}
```

---

## 🧠 AI & Semantic Search

### Embedding Generation
- **Service:** `EmbeddingOrchestrator`
- **Rate limits:** 30 requests/min, 500/hour
- **Model:** Google Gemini `text-embedding-004`
- **Storage:** pgvector in `cmis_ai.embeddings`

### Vector Search
```php
// Use cosine similarity for search
$results = DB::select("
    SELECT *,
           1 - (embedding <=> ?::vector) AS similarity
    FROM cmis_ai.embeddings
    WHERE embedding <=> ?::vector < 0.3
    ORDER BY embedding <=> ?::vector
    LIMIT 10
", [$queryVector, $queryVector, $queryVector]);
```

---

## 📊 Performance Guidelines

### Query Optimization
- Use indexes for frequently queried columns
- Add indexes on foreign keys
- Use `EXPLAIN ANALYZE` for slow queries
- Consider materialized views for dashboards

### Caching Strategy
- Cache platform data (5-15 min TTL)
- Cache embeddings (permanent until updated)
- Cache analytics (1-60 min based on frequency)
- Use Redis for session + cache

### N+1 Query Prevention
```php
// ALWAYS eager load relationships
$campaigns = Campaign::with(['org', 'contentPlans.items'])
    ->get();
```

---

## 🔍 Debugging Tips

### Multi-Tenancy Issues
```sql
-- Check current org context
SELECT current_setting('app.current_org_id', true);

-- Test RLS policy
SET app.current_org_id = 'org-uuid-here';
SELECT * FROM cmis.campaigns; -- Should only show this org's data
```

### Platform Integration
- Check webhook logs in `cmis_platform.webhook_logs`
- Verify token expiration dates
- Test with platform's debugging tools

### AI Operations
- Monitor rate limits in logs
- Check embedding dimensions (768 for Gemini)
- Verify vector index exists: `\d cmis_ai.embeddings`

---

## 📚 Essential Documentation

### Core Guidelines
- **Project Knowledge:** `.claude/CMIS_PROJECT_KNOWLEDGE.md`
- **Multi-Tenancy:** `.claude/knowledge/MULTI_TENANCY_PATTERNS.md`
- **Data Patterns:** `.claude/knowledge/CMIS_DATA_PATTERNS.md`
- **Agent Guide:** `.claude/agents/README.md`

### Documentation Hubs
- **Main Documentation:** `docs/README.md` - Complete documentation index
- **Phase Documentation:** `docs/phases/README.md` - All 26 implementation phases
- **Testing Documentation:** `docs/testing/README.md` - Complete testing guides
- **Active Analysis:** `docs/active/analysis/` - Current project analysis reports

---

## 🚀 Quick Commands

```bash
# Run tests
vendor/bin/phpunit

# Database refresh
php artisan migrate:fresh --seed

# Clear caches
php artisan optimize:clear

# Generate IDE helper
php artisan ide-helper:generate

# Database console (use .env values - see Environment Configuration section)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)"
```

---

## ⚠️ Common Pitfalls

1. **Forgetting RLS context** - Always call `init_transaction_context()`
2. **Unqualified table names** - Use `cmis.table_name`, not just `table_name`
3. **Missing indexes** - Add indexes for foreign keys and search columns
4. **Hardcoded org filtering** - Let RLS handle it, don't add `WHERE org_id = ?`
5. **Token expiration** - Implement refresh logic for platform tokens
6. **Rate limit violations** - Queue AI operations, don't run synchronously

---

## 📞 Support & Resources

- **Laravel Docs:** https://laravel.com/docs
- **PostgreSQL RLS:** https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- **pgvector:** https://github.com/pgvector/pgvector
- **Alpine.js:** https://alpinejs.dev
- **Tailwind:** https://tailwindcss.com

---

**Remember:** CMIS is NOT a generic Laravel app. Always consider multi-tenancy, platform integrations, and AI capabilities in your solutions!

---

## 🎉 Code Quality Improvements (2025-11-22)

### Duplication Elimination Initiative: ~13,100 Lines Saved

A comprehensive 8-phase initiative systematically eliminated duplicate code:

**Phase 0: Foundation (863 lines)**
- Created `HasOrganization` trait (99 models)
- Created `HasRLSPolicies` trait (migrations)
- Removed 5 stub service files

**Phase 1: Unified Metrics (2,000 lines)**
- Consolidated 10 metric tables → 1 `unified_metrics` table
- Polymorphic design with monthly partitioning

**Phase 2: Social Posts (1,500 lines)**
- Consolidated 5 social post tables → 1 `social_posts` table
- Platform-agnostic with JSONB metadata

**Phase 3: BaseModel Conversion (3,624 lines)**
- Converted 282+ models to extend `BaseModel`
- Removed duplicate UUID generation, boot() methods
- Applied `HasOrganization` trait systematically

**Phase 4: Platform Services (3,600 lines)**
- Documented excellent existing abstraction
- Template Method pattern with `AbstractAdPlatform`
- 6 platform implementations

**Phase 5: Social Models (400 lines)**
- Eliminated 5 duplicate social model files
- Established `App\Models\Social\` as canonical namespace

**Phase 6: Content Plans (300 lines)**
- Consolidated 2 duplicate ContentPlan models
- Merged all features into `App\Models\Creative\`

**Phase 7: Controller Enhancement (800 lines)**
- Applied `ApiResponse` trait to 111 controllers (75%)
- Refactored 129 response patterns
- 100% API response consistency

**Total Impact:**
- ✅ 13,100 lines of duplicate code eliminated
- ✅ 16 database tables → 2 unified tables (87.5% reduction)
- ✅ 12 duplicate models removed
- ✅ 111 controllers standardized
- ✅ 100% backward compatibility maintained
- ✅ 0 breaking changes

**Documentation:**
- See `docs/phases/completed/duplication-elimination/COMPREHENSIVE-DUPLICATION-ELIMINATION-FINAL-REPORT.md`
- Phase summaries in `docs/phases/completed/phase-*/`
- All phases organized in `docs/phases/` (completed/in-progress/planned)

---

## 📈 Project Status

**Current Phase:** Phase 2-3 (Platform Integration & AI Features) - In Progress
**Completion:** ~55-60% (Based on implemented features and codebase maturity)

### Completed Components:
- ✅ Core multi-tenancy architecture (RLS, context management)
- ✅ 244 Models across 51 business domains (282+ extend BaseModel)
- ✅ 45 database migrations with RLS policies
- ✅ Repository + Service pattern architecture
- ✅ AI infrastructure (embeddings, vector search)
- ✅ 26 specialized Claude Code agents
- ✅ Test suite foundation (201 tests, 33.4% passing - actively improving)
- ✅ **Code Quality Initiative: 13,100 lines of duplication eliminated (Nov 2025)**

### In Progress:
- 🔄 Platform connectors completion (Meta, Google, TikTok)
- 🔄 Analytics repository implementation (removing TODO stubs)
- 🔄 Test suite improvements (targeting 40-45% pass rate)
- 🔄 Frontend dashboard components

### Next Phase:
- 📋 Phase 3: Advanced AI analytics & predictive features
- 📋 Phase 4: Ad campaign orchestration & automation
- 📋 Production deployment & optimization

**Last Updated:** 2025-11-22
