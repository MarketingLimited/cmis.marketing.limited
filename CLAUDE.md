# CMIS Project Guidelines for Claude Code

**Last Updated:** 2025-12-01 (Documentation Reorganization, Test Suite Status Updated)
**Project:** CMIS - Cognitive Marketing Intelligence Suite
**Framework Version:** 3.2 - Post Duplication Elimination (13,100 lines saved)
**Languages:** Arabic (Default), English - Full RTL/LTR Support

---

## 🎯 Project Overview

CMIS is a Laravel-based Cognitive Marketing Intelligence Suite with:
- **Multi-tenancy:** PostgreSQL Row-Level Security (RLS)
- **Database:** 12 schemas, 197 tables, pgvector for AI
- **Codebase:** 712 PHP files, 244 Models (51 domains), 45 migrations, 27 test files (restructured)
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

### Database Operations (CRITICAL - STRICTLY ENFORCED)
- ✅ **ALWAYS** use migrations for ALL schema changes
- ✅ **ALWAYS** run `php artisan migrate:fresh --seed` after creating/modifying migrations
- ✅ **REPEAT** fresh migrations until successful - fix errors in migration files, not database
- ✅ Add RLS policies to new tables via migrations using `HasRLSPolicies` trait
- ✅ Use `init_transaction_context(org_id)` in Laravel for RLS
- ✅ Qualify all table names with schema prefix (e.g., `cmis.campaigns`)
- ❌ **NEVER** edit database directly with raw SQL (ALTER, UPDATE, INSERT, CREATE)
- ❌ **NEVER** use `DB::statement()` outside of migrations
- ❌ **NEVER** use `php artisan tinker` to modify schema or data
- ❌ **NEVER** create tables without RLS policies
- ❌ **NEVER** skip fresh migrations - always verify with clean slate

**Why Fresh Migrations?**
- Ensures migrations work correctly from scratch
- Catches missing dependencies and ordering issues
- Verifies RLS policies are applied correctly
- Maintains reproducible database state
- Prevents production migration failures

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

### Internationalization (i18n) & RTL/LTR (CRITICAL - NEW 2025-11-27)
**📖 See: `.claude/knowledge/I18N_RTL_REQUIREMENTS.md` for complete guidelines**

**MANDATORY PRE-IMPLEMENTATION AUDIT:**
- ✅ **BEFORE** implementing ANY feature, audit for hardcoded text
- ✅ **BEFORE** working on ANY page, check RTL/LTR compliance
- ✅ **FIX i18n issues FIRST**, then implement new features
- ✅ Use Laravel's `__('key')` for ALL text - ZERO hardcoded strings
- ✅ Use logical CSS properties: `ms-`, `me-`, `text-start` (NOT `ml-`, `mr-`, `text-left`)
- ✅ Support both Arabic (RTL, default) and English (LTR)
- ✅ Test BOTH languages before marking tasks complete
- ❌ **NEVER** add hardcoded text (English or Arabic)
- ❌ **NEVER** use directional CSS without RTL/LTR variants
- ❌ **NEVER** proceed with features if i18n issues exist

**Quick Audit Commands:**
```bash
# Find hardcoded text
grep -r -E "\b(Campaign|Dashboard|Save|Delete)\b" resources/views/ | grep -v "{{ __("

# Find directional CSS
grep -r -E "(ml-|mr-|text-left|text-right)" resources/views/
```

### Workflow & Git (CRITICAL - NEW 2025-11-30)
**📖 See: `.claude/knowledge/TROUBLESHOOTING_METHODOLOGY.md` → Section 8: Automatic Git Commits**

**MANDATORY AUTO-COMMIT:**
- ✅ **AFTER** verification passes (console clean, logs clean, tests passing), commit to git automatically
- ✅ **DO NOT** wait for user to ask - commit immediately after success
- ✅ Use conventional commits format: `feat:`, `fix:`, `refactor:`, `test:`, etc.
- ✅ Include verification status in commit message
- ✅ Add Claude Code attribution footer
- ❌ **NEVER** commit without verification
- ❌ **NEVER** commit if tests are failing
- ❌ **NEVER** commit secrets or .env files

**Quick Auto-Commit Command:**
```bash
# After verification passes
git add .
git commit -m "$(cat <<'EOF'
feat: Your feature description

- Change 1
- Change 2
- Verified: Browser console clean, Laravel logs clean
- Tests: X passing

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"
```

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
- **Test Suite:** 27 test files (legacy tests archived, new tests pending for recent features)

### Post-Implementation Verification (MANDATORY)
**CRITICAL:** After ANY code change, you MUST verify success. **DO NOT assume it works.**

**Required verification steps:**
1. ✅ **Browser Console Logs** - Use Playwright to capture and check for errors
2. ✅ **Laravel Logs** - Check `storage/logs/laravel.log` for exceptions
3. ✅ **Screenshots** - Visual verification of UI changes
4. ✅ **Functional Testing** - Test the feature with Playwright/headless browsers
5. ✅ **Create Automated Tests** - Add permanent Feature/Browser tests
6. ✅ **Update Related Tests** - Modify existing tests if behavior changed
7. ✅ **Auto-Commit to Git** - After verification passes, commit automatically (DO NOT wait for user)

**📖 Complete Guide:** `.claude/knowledge/TROUBLESHOOTING_METHODOLOGY.md` → Section 7: Post-Implementation Verification & Testing

**Quick verification + auto-commit workflow:**
```bash
# 1. Check Laravel logs
grep -i "error\|exception" storage/logs/laravel.log | tail -20

# 2. Run Playwright test
node scripts/verify-feature.cjs

# 3. Create/update tests
vendor/bin/phpunit tests/Feature/YourFeatureTest.php

# 4. If all passed, auto-commit
git add .
git commit -m "$(cat <<'EOF'
feat: Your feature description

- Change 1
- Change 2
- Verified: Browser console clean, Laravel logs clean
- Tests: X passing

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"
```

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
- [ ] **i18n compliance (NEW):** No hardcoded text? RTL/LTR optimized?
- [ ] **Both languages tested (NEW):** Arabic (RTL) and English (LTR)?
- [ ] **Verification completed (NEW):** Browser console, Laravel logs checked?
- [ ] **Automated tests created (NEW):** Feature/Browser tests added?
- [ ] **Related tests updated (NEW):** Existing tests modified if needed?
- [ ] **Git commit created (NEW):** Changes committed automatically after verification?
- [ ] Tests written and passing?
- [ ] Security validated?
- [ ] Documentation updated?
- [ ] No hardcoded credentials?

---

## 🌐 Browser Testing Environment

**📖 Full Guide:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

### Live Application
- **Test URL**: https://cmis-test.kazaaz.com/
- **Auth**: `admin@cmis.test` / `password`
- **Languages**: Arabic (RTL, default) and English (LTR)
- **Test Org ID**: `5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a`

### CMIS Test Suites (RECOMMENDED)

| Test Suite | Command | Purpose |
|------------|---------|---------|
| **Mobile Responsive** | `node scripts/browser-tests/mobile-responsive-comprehensive.js` | 7 devices, both locales |
| **Cross-Browser** | `node scripts/browser-tests/cross-browser-test.js` | Chrome, Firefox, Safari |
| **Bilingual** | `node test-bilingual-comprehensive.cjs` | All pages in AR/EN |
| **Quick Responsive** | `node scripts/browser-tests/responsive-test.js <url>` | Single page, 4 viewports |

### Quick Commands

```bash
# Mobile responsive (quick mode - 5 pages, 2 devices)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (quick mode)
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser test
node scripts/browser-tests/cross-browser-test.js --browser chrome

# Text dump for quick content check
lynx -dump https://cmis-test.kazaaz.com/login

# Quick screenshot
npx playwright screenshot https://cmis-test.kazaaz.com/ screenshot.png
```

### Device Profiles (Mobile Testing)

| Device | Resolution | Type |
|--------|------------|------|
| iPhone SE | 375x667 | Mobile |
| iPhone 14 | 390x844 | Mobile |
| iPhone 14 Pro Max | 430x932 | Mobile |
| Pixel 7 | 412x915 | Mobile |
| Galaxy S21 | 360x800 | Mobile |
| iPad Mini | 768x1024 | Tablet |
| iPad Pro | 1024x1366 | Tablet |

### Issues Checked

**Mobile Responsive Tests:**
- ✅ Horizontal overflow (content wider than viewport)
- ✅ Touch targets (minimum 44x44px)
- ✅ Font sizes (minimum 12px readable)
- ✅ Viewport meta tag presence
- ✅ RTL/LTR direction consistency

**Cross-Browser Tests:**
- ✅ CSS feature support (flexbox, grid, logical properties)
- ✅ Broken images
- ✅ SVG rendering
- ✅ JavaScript console errors
- ✅ Layout metrics comparison

### When Browser Testing is MANDATORY

| Scenario | Test Command |
|----------|--------------|
| UI/Layout changes | `node scripts/browser-tests/mobile-responsive-comprehensive.js --quick` |
| i18n/locale changes | `node test-bilingual-comprehensive.cjs` |
| Before release | All test suites (full mode) |
| RTL/LTR modifications | Mobile + Bilingual tests |
| New pages/features | Cross-browser + Mobile tests |
| Form implementations | Full bilingual test |

### Test Output Locations

```
test-results/
├── mobile-responsive/
│   ├── screenshots/
│   ├── results.json
│   └── REPORT.md
├── cross-browser/
│   ├── screenshots/chrome/
│   ├── screenshots/firefox/
│   ├── screenshots/safari--webkit-/
│   ├── results.json
│   └── REPORT.md
└── bilingual-web/
    ├── screenshots/
    ├── test-report.json
    └── SUMMARY.md
```

### Setting Locale for Testing

```javascript
// ALWAYS set locale cookie BEFORE navigation
await page.setCookie({
    name: 'app_locale',
    value: 'ar',  // or 'en'
    domain: 'cmis-test.kazaaz.com',
    path: '/'
});
await page.goto(url);
```

---

## 🎨 Frontend Conventions

### i18n & RTL/LTR Requirements (MANDATORY)
```html
<!-- ALWAYS use translation keys, NEVER hardcoded text -->
<div x-data="campaignDashboard()">
    <button @click="loadMetrics">{{ __('campaigns.load_button') }}</button>
    <div x-show="loading">{{ __('common.loading') }}</div>
</div>

<!-- ALWAYS use logical CSS properties for RTL/LTR support -->
<div class="ms-4 me-2 text-start">  <!-- ✅ CORRECT -->
<div class="ml-4 mr-2 text-left">   <!-- ❌ WRONG -->
```

### Alpine.js Components
```html
<!-- Use x-data for component state with translations -->
<div x-data="campaignDashboard()">
    <h1>{{ __('campaigns.dashboard_title') }}</h1>
    <button @click="loadMetrics">{{ __('campaigns.load_button') }}</button>
    <div x-show="loading">{{ __('common.loading') }}</div>
</div>
```

### Tailwind Utilities (RTL/LTR Aware)
- Use logical properties: `ms-`, `me-`, `ps-`, `pe-`, `text-start`, `text-end`
- NEVER use directional: `ml-`, `mr-`, `pl-`, `pr-`, `text-left`, `text-right`
- Use `@apply` in components sparingly
- Custom colors in `tailwind.config.js`
- Install `tailwindcss-rtl` plugin for RTL support

### Chart.js Integration
- Store in `resources/js/components/`
- Use Alpine.js for state management
- Async data loading via Axios
- Use translated labels: `labels: [__('common.january'), ...]`

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
- **i18n & RTL/LTR:** `.claude/knowledge/I18N_RTL_REQUIREMENTS.md` ⚠️ CRITICAL
- **Browser Testing:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md` ⚠️ MANDATORY
- **Troubleshooting Methodology:** `.claude/knowledge/TROUBLESHOOTING_METHODOLOGY.md` - Complete guide to testing, debugging, and fixing issues using Playwright, Laravel logs, browser DevTools, database tools, and all available diagnostic resources
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

1. **Hardcoded text (NEW)** - NEVER use hardcoded English/Arabic - use `__('key')` always
2. **Directional CSS (NEW)** - NEVER use `ml-`, `mr-`, `text-left` - use `ms-`, `me-`, `text-start`
3. **Skipping i18n audit (NEW)** - ALWAYS audit for i18n issues BEFORE implementing features
4. **Forgetting RLS context** - Always call `init_transaction_context()`
5. **Unqualified table names** - Use `cmis.table_name`, not just `table_name`
6. **Missing indexes** - Add indexes for foreign keys and search columns
7. **Hardcoded org filtering** - Let RLS handle it, don't add `WHERE org_id = ?`
8. **Token expiration** - Implement refresh logic for platform tokens
9. **Rate limit violations** - Queue AI operations, don't run synchronously

---

## 📞 Support & Resources

- **Laravel Docs:** https://laravel.com/docs
- **PostgreSQL RLS:** https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- **pgvector:** https://github.com/pgvector/pgvector
- **Alpine.js:** https://alpinejs.dev
- **Tailwind:** https://tailwindcss.com

---

**Remember:** CMIS is NOT a generic Laravel app. Always consider:
- 🌍 **i18n & RTL/LTR** (Arabic default, bilingual support)
- 🏢 **Multi-tenancy** (RLS policies, org context)
- 🔌 **Platform integrations** (OAuth, webhooks)
- 🤖 **AI capabilities** (embeddings, semantic search)

**⚠️ CRITICAL: Fix i18n issues BEFORE implementing new features!**

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
- ✅ Test suite restructured (27 test files, legacy tests archived, new tests pending)
- ✅ **Code Quality Initiative: 13,100 lines of duplication eliminated (Nov 2025)**

### In Progress:
- 🔄 Platform connectors completion (Meta, Google, TikTok)
- 🔄 Social Publishing features (Profile Management, Queue Settings, Timezone support)
- 🔄 Frontend dashboard components (Alpine.js lazy loading, RTL/LTR optimization)
- 🔄 New test suite creation (for recently implemented features)

### Next Phase:
- 📋 Phase 3: Advanced AI analytics & predictive features
- 📋 Phase 4: Ad campaign orchestration & automation
- 📋 Production deployment & optimization

**Last Updated:** 2025-12-01 (Documentation reorganization, test suite status updated)
