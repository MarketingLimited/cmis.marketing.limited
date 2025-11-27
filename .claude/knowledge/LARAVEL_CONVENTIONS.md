# Laravel Conventions for CMIS
**Version:** 2.1
**Last Updated:** 2025-11-27
**Purpose:** Discover and apply Laravel conventions as implemented in CMIS through dynamic discovery
**Prerequisites:** Read `.claude/knowledge/DISCOVERY_PROTOCOLS.md` for discovery methodology
**Framework:** META_COGNITIVE_FRAMEWORK v2.0

---

## ⚠️ IMPORTANT: Environment Configuration

**Configuration values (database, cache, services) vary by environment. ALWAYS read from `.env` or `config()` files.**

### Laravel Configuration Best Practices

```bash
# Read environment variables
cat .env | grep -E "DB_|CACHE_|QUEUE_|APP_"

# Access in code - Use config() helper
config('database.connections.pgsql.database')  # NOT env('DB_DATABASE')
config('app.name')

# Access in commands - Extract from .env
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
```

**Key Principles:**
- ✅ Use `config()` in application code (cached, safe)
- ✅ Use `env()` ONLY in config files
- ✅ Extract from `.env` for bash/psql commands
- ❌ NEVER hardcode database names, credentials, or URLs
- ❌ NEVER use `env()` outside config files (breaks config caching)

---

## 🎯 PRINCIPLE: DISCOVER PROJECT CONVENTIONS

**Not:** "Laravel uses X convention"
**But:** "Discover how THIS project implements Laravel conventions"

Every Laravel project adapts conventions. Discover how CMIS does it.

---

## 📋 DISCOVERY CHECKLIST

### 1. Routing Conventions

**Discovery:**
```bash
# Check API routing structure
cat routes/api.php | head -50

# Identify patterns
grep -c "Route::get" routes/api.php
grep -c "Route::post" routes/api.php
grep -c "Route::group" routes/api.php
```

**Questions to Answer:**
- Are routes grouped by domain?
- Is versioning used (v1, v2)?
- What middleware is standard?
- Are route names used?

**Example Discovery:**
```bash
# Check route structure
cat routes/api.php | grep "Route::group" | head -5

# If routes use org_id:
# Pattern: /api/orgs/{org_id}/resource
# Convention: Multi-tenant routing

# If routes are versioned:
# Pattern: /api/v1/resource
# Convention: API versioning
```

### 2. Controller Conventions

**Discovery:**
```bash
# Controller organization
ls -la app/Http/Controllers/

# Naming patterns
find app/Http/Controllers -name "*Controller.php" | head -10

# Check if API namespace used
test -d app/Http/Controllers/API && echo "API namespace: YES" || echo "API namespace: NO"
```

**Patterns to Check:**
```php
// Single action controllers?
grep -r "class.*Controller" app/Http/Controllers/ | grep "function __invoke"

// Resource controllers?
grep -r "public function index\|public function store" app/Http/Controllers/ | wc -l

// API controllers return JSON?
grep -r "return response()->json" app/Http/Controllers/API/ | wc -l
```

**Convention Discovery:**
```bash
# Check a representative controller
cat app/Http/Controllers/API/CampaignController.php

# Identify patterns:
# 1. Thin controllers? (< 200 lines)
# 2. Service injection?
# 3. Form request validation?
# 4. Resource responses?
# 5. Exception handling?
```

### 3. Model Conventions

**Discovery:**
```bash
# Model organization
ls -la app/Models/

# Check for subdirectories (domain organization)
find app/Models -type d | sort

# Naming convention
find app/Models -name "*.php" | head -20
```

**Patterns to Check:**
```php
// UUID or auto-increment?
grep -r "use HasUuids\|\$incrementing = false" app/Models/ | wc -l

// Soft deletes?
grep -r "use SoftDeletes" app/Models/ | wc -l

// Fillable vs guarded?
grep -r "\$fillable\|\$guarded" app/Models/ | head -10

// Timestamps?
grep -r "\$timestamps = false" app/Models/ | wc -l
```

**Convention Example:**
```bash
# Check Campaign model conventions
cat app/Models/Core/Campaign.php

# Extract patterns:
# - Uses UUID? Check: use HasUuids
# - Uses soft deletes? Check: use SoftDeletes
# - Primary key name? Check: $primaryKey
# - Table name? Check: $table or infer from class name
```

### 4. Migration Conventions

**Discovery:**
```bash
# List migrations
ls -la database/migrations/ | head -20

# Check naming patterns
ls database/migrations/ | grep -o "create_.*_table" | head -10

# Timestamp format
ls database/migrations/ | head -1 | grep -o "^[0-9_]*"
```

**Patterns to Check:**
```php
// Foreign key conventions
grep -r "foreign\|references" database/migrations/ | head -10

// Index conventions
grep -r "index\|unique" database/migrations/ | head -10

// UUID vs bigIncrements
grep -r "uuid\|bigIncrements" database/migrations/ | head -10

// Schema names (multi-schema?)
grep -r "Schema::create\|DB::statement.*CREATE TABLE" database/migrations/ | grep -o "Schema::create('[^']*'" | head -10
```

### 5. Validation Conventions

**Discovery:**
```bash
# Form Requests exist?
test -d app/Http/Requests && echo "Form Requests: YES" || echo "Form Requests: NO"

# Check validation pattern
find app/Http/Requests -name "*.php" | head -5

# Or inline validation?
grep -r "->validate(" app/Http/Controllers/ | wc -l
```

**Pattern Check:**
```php
// If Form Requests exist:
cat app/Http/Requests/StoreCampaignRequest.php

// Identify patterns:
# - Custom messages?
# - Authorization in request?
# - Custom rules?

// If inline validation:
grep -A 10 "->validate(" app/Http/Controllers/API/CampaignController.php | head -20
```

### 6. Response Conventions

**Discovery:**
```bash
# API Resources used?
test -d app/Http/Resources && echo "API Resources: YES" || echo "API Resources: NO"

# Check response patterns
grep -r "return response()->json" app/Http/Controllers/API/ | head -10

# Success/error response format
grep -A 5 "return response()->json" app/Http/Controllers/API/CampaignController.php | head -20
```

**Pattern Examples:**
```php
// Standard success response
return response()->json([
    'data' => $resource,
    'message' => 'Success'
], 201);

// Standard error response
return response()->json([
    'error' => 'Error message',
    'details' => $errors
], 422);

// Or using API Resources:
return CampaignResource::collection($campaigns);
```

### 7. Service Layer Conventions

**Discovery:**
```bash
# Services exist?
test -d app/Services && echo "Services: YES" || echo "Services: NO"

# Organization
ls -la app/Services/

# Naming pattern
find app/Services -name "*.php" | grep -o "/[^/]*Service.php" | head -10
```

**Pattern Check:**
```php
// Check service structure
cat app/Services/CampaignService.php

// Identify patterns:
# - Constructor injection?
# - Repository usage?
# - Transaction handling?
# - Event dispatching?
# - Error handling?
```

### 8. Repository Conventions

**Discovery:**
```bash
# Repositories exist?
test -d app/Repositories && echo "Repositories: YES" || echo "Repositories: NO"

# Check for interfaces
find app/Repositories -name "*Interface.php" | wc -l

# Check for implementations
find app/Repositories -name "*Repository.php" | wc -l
```

**Pattern Check:**
```bash
# If repositories exist, check binding
grep -A 10 "bind.*Repository" app/Providers/*ServiceProvider.php
```

### 9. Testing Conventions

**Discovery:**
```bash
# Test structure
ls -la tests/

# Feature vs Unit split
ls -la tests/Feature/ tests/Unit/

# Test naming
find tests -name "*Test.php" | head -10

# Check test patterns
grep -r "use.*RefreshDatabase\|use.*DatabaseTransactions" tests/ | wc -l
```

**Pattern Check:**
```php
// Check a test file
cat tests/Feature/CampaignTest.php

# Identify patterns:
# - Uses RefreshDatabase?
# - Factory usage?
# - API testing patterns?
# - Assertion style?
```

---

## 🔍 CMIS-SPECIFIC CONVENTIONS

### Convention 1: Multi-Tenant Route Structure

**Discovery:**
```bash
grep -A 3 "Route::prefix" routes/api.php | head -20
```

**Expected Pattern:**
```php
// All org-scoped routes include org_id
Route::prefix('orgs/{org_id}')->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index']);
});

// Pattern: /api/orgs/{org_id}/resource
```

### Convention 2: Database Context Middleware

**Discovery:**
```bash
# Find context middleware
ls -la app/Http/Middleware/*Context*.php

# Check middleware registration
grep -A 5 "set.db.context\|SetDatabaseContext" app/Http/Kernel.php
```

**Expected Pattern:**
```php
// Middleware sets RLS context
DB::statement(
    'SELECT cmis.init_transaction_context(?, ?)',
    [$userId, $orgId]
);
```

### Convention 3: UUID Primary Keys

**Discovery:**
```bash
# Check models use UUID
grep -r "use HasUuids" app/Models/ | wc -l

# Check migrations use UUID
grep -r "->uuid(" database/migrations/ | wc -l
```

**Expected Pattern:**
```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Campaign extends Model {
    use HasUuids;

    protected $primaryKey = 'campaign_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
```

### Convention 4: Schema-Qualified Tables

**Discovery:**
```bash
# Check models specify schema
grep -r "protected \$table = 'cmis\." app/Models/ | head -10
```

**Expected Pattern:**
```php
class Campaign extends Model {
    protected $table = 'cmis.campaigns';
}
```

### Convention 5: Job Context Handling

**Discovery:**
```bash
# Check jobs set context
grep -A 5 "init_transaction_context" app/Jobs/*.php | head -20
```

**Expected Pattern:**
```php
public function handle() {
    DB::statement(
        'SELECT cmis.init_transaction_context(?, ?)',
        [config('cmis.system_user_id'), $this->orgId]
    );

    // Job logic...
}
```

---

## 🎓 CONVENTION DISCOVERY WORKFLOW

### Step 1: Identify Convention Category

```bash
# Routing conventions
cat routes/api.php | head -50

# Model conventions
ls -la app/Models/ && cat app/Models/Core/Campaign.php | head -30

# Controller conventions
cat app/Http/Controllers/API/CampaignController.php | grep "public function"
```

### Step 2: Find Representative Examples

```bash
# Find the most common controller
find app/Http/Controllers -name "*Controller.php" | head -1

# Find the most common model
find app/Models -name "*.php" | head -1

# Find the most common service
find app/Services -name "*Service.php" | head -1
```

### Step 3: Extract Patterns

```bash
# From controller:
# - How is validation done?
# - How are responses formatted?
# - What dependencies are injected?

# From model:
# - What traits are used?
# - How are relationships defined?
# - Are timestamps enabled?

# From service:
# - How is business logic organized?
# - How are transactions handled?
# - Are events dispatched?
```

### Step 4: Verify Consistency

```bash
# Check if pattern is consistent across codebase
grep -r "pattern-indicator" app/ | wc -l

# If high count, pattern is a convention
# If low count, pattern is an exception
```

---

## 🚀 QUICK CONVENTION CHECKS

```bash
# Multi-tenancy?
grep -r "org_id" routes/api.php | wc -l  # Should be high

# UUID?
grep -r "HasUuids" app/Models/ | wc -l  # Should be high

# RLS?
grep -r "init_transaction_context" app/ | wc -l  # Should be high

# API Resources?
test -d app/Http/Resources && echo "YES" || echo "NO"

# Form Requests?
test -d app/Http/Requests && echo "YES" || echo "NO"

# Service Layer?
test -d app/Services && echo "YES" || echo "NO"

# Repository Pattern?
test -d app/Repositories && echo "YES" || echo "NO"

# Event-Driven?
test -d app/Events && test -d app/Listeners && echo "YES" || echo "NO"
```

---

## 📊 CONVENTION COMPLIANCE CHECK

```bash
# Check if new code follows discovered conventions

# 1. Route follows pattern?
grep "orgs/{org_id}" routes/api.php | grep "your-new-route"

# 2. Model uses UUID?
grep -A 5 "class YourNewModel" app/Models/ | grep "HasUuids"

# 3. Controller is thin?
wc -l app/Http/Controllers/API/YourNewController.php  # Should be < 200

# 4. Context set in jobs?
grep "init_transaction_context" app/Jobs/YourNewJob.php

# 5. RLS enabled on table?
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT rowsecurity FROM pg_tables WHERE tablename = 'your_new_table';"
```

---

## ⚠️ ANTI-PATTERNS TO AVOID

### Don't Assume Generic Laravel Conventions

```bash
# ❌ WRONG: Assume standard Laravel
# "Models go in app/Models"
# "Controllers go in app/Http/Controllers"
# "Use auto-increment IDs"

# ✅ RIGHT: Discover actual structure
ls -la app/Models/
ls -la app/Http/Controllers/
grep -r "incrementing" app/Models/ | head -1
```

### Don't Use Outdated Conventions

```bash
# ❌ WRONG: Use old Laravel patterns from memory
# "Use Route::resource for REST"
# "Put all logic in controllers"
# "Use integer IDs"

# ✅ RIGHT: Check what version and patterns are used
cat composer.json | jq '.require["laravel/framework"]'
grep -r "Route::resource\|Route::apiResource" routes/
```

### Don't Break Project Consistency

```bash
# Before writing new code, check existing patterns:

# If 90% of models use UUID:
grep -r "HasUuids" app/Models/ | wc -l
# Use UUID for new models too

# If all routes have org_id:
grep "orgs/{org_id}" routes/api.php | wc -l
# Add org_id to new routes too

# If all jobs set context:
grep "init_transaction_context" app/Jobs/ | wc -l
# Set context in new jobs too
```

---

## 🔍 Quick Reference

| I Need To... | Discovery Command | Section |
|--------------|-------------------|---------|
| Check routing pattern | `cat routes/api.php \| head -50` | Routing Conventions |
| Find controller organization | `ls -la app/Http/Controllers/` | Controller Conventions |
| Verify UUID usage | `grep -r "HasUuids" app/Models/ \| wc -l` | UUID Primary Keys |
| Check RLS middleware | `grep "init_transaction_context" app/Http/Middleware/` | Database Context Middleware |
| Find service layer pattern | `cat app/Services/CampaignService.php` | Service Layer Conventions |
| Verify multi-tenancy routes | `grep "orgs/{org_id}" routes/api.php \| wc -l` | Multi-Tenant Route Structure |
| Check validation pattern | `test -d app/Http/Requests && echo "Form Requests" \|\| echo "Inline"` | Validation Conventions |
| Find test structure | `ls -la tests/Feature/ tests/Unit/` | Testing Conventions |

---

## 📚 Related Knowledge

**Prerequisites:**
- **DISCOVERY_PROTOCOLS.md** - Discovery methodology and executable commands
- **META_COGNITIVE_FRAMEWORK.md** - Adaptive intelligence principles

**Related Files:**
- **CMIS_PROJECT_KNOWLEDGE.md** - Core project architecture and patterns
- **PATTERN_RECOGNITION.md** - Common architectural patterns in CMIS
- **MULTI_TENANCY_PATTERNS.md** - RLS and multi-tenancy implementation
- **CMIS_DATA_PATTERNS.md** - Data modeling patterns

**See Also:**
- **CLAUDE.md** - Main project guidelines with Laravel standards
- **CMIS_DISCOVERY_GUIDE.md** - How to discover features in codebase

---

**Last Updated:** 2025-11-27
**Version:** 2.1
**Maintained By:** CMIS AI Agent Development Team
**Framework:** META_COGNITIVE_FRAMEWORK
**Approach:** Discover > Verify > Apply

*"Conventions are discovered, not assumed. Use config(), not env()."*
