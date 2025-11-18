# CMIS Discovery Protocols
## Comprehensive Command Reference for Dynamic Codebase Exploration

**Version:** 2.0
**Last Updated:** 2025-11-18
**Purpose:** Provide specific, executable commands for discovering current system state
**Prerequisite:** Read META_COGNITIVE_FRAMEWORK.md first

---

## 🎯 Purpose

This document provides **executable commands** for discovering CMIS platform's current state. Instead of memorizing facts, agents use these protocols to **discover facts dynamically**.

---

## 📋 Protocol Categories

1. [Database Schema Discovery](#database-schema-discovery)
2. [Laravel Structure Discovery](#laravel-structure-discovery)
3. [Frontend Stack Discovery](#frontend-stack-discovery)
4. [API Endpoint Discovery](#api-endpoint-discovery)
5. [Multi-Tenancy Discovery](#multi-tenancy-discovery)
6. [Service Layer Discovery](#service-layer-discovery)
7. [Repository Pattern Discovery](#repository-pattern-discovery)
8. [Event System Discovery](#event-system-discovery)
9. [Job Queue Discovery](#job-queue-discovery)
10. [AI & Embeddings Discovery](#ai--embeddings-discovery)
11. [Platform Integration Discovery](#platform-integration-discovery)
12. [Testing Infrastructure Discovery](#testing-infrastructure-discovery)

---

## 1. Database Schema Discovery

### Protocol DS-01: List All Schemas

```sql
-- Find all schemas in database
SELECT schema_name
FROM information_schema.schemata
ORDER BY schema_name;

-- Find CMIS-specific schemas
SELECT schema_name
FROM information_schema.schemata
WHERE schema_name LIKE 'cmis%'
ORDER BY schema_name;
```

**Pattern Recognition:**
- Schemas starting with `cmis` = CMIS domain schemas
- `public` schema = Laravel migrations default (usually avoided in CMIS)
- Schema count indicates domain organization sophistication

### Protocol DS-02: Count Tables Per Schema

```sql
-- Tables per schema
SELECT
    table_schema,
    COUNT(*) as table_count
FROM information_schema.tables
WHERE table_type = 'BASE TABLE'
  AND table_schema NOT IN ('pg_catalog', 'information_schema')
GROUP BY table_schema
ORDER BY table_count DESC;

-- Total tables in all CMIS schemas
SELECT COUNT(*) as total_cmis_tables
FROM information_schema.tables
WHERE table_schema LIKE 'cmis%';
```

**Pattern Recognition:**
- High table count in `cmis` schema = Core domain
- Specialized schemas (cmis_analytics, cmis_knowledge) = Domain separation
- Equal distribution vs single large schema = Architectural maturity

### Protocol DS-03: Examine Table Structure

```sql
-- Detailed table structure (PostgreSQL)
\d cmis.campaigns

-- Or using SQL
SELECT
    column_name,
    data_type,
    character_maximum_length,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND table_name = 'campaigns'
ORDER BY ordinal_position;

-- Check for common patterns
SELECT column_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND table_name = 'campaigns'
  AND column_name IN ('org_id', 'user_id', 'created_at', 'updated_at', 'deleted_at', 'deleted_by');
```

**Pattern Recognition:**
- `org_id UUID` = Multi-tenant table
- `deleted_at` + `deleted_by` = Soft delete with auditing
- `created_at` + `updated_at` = Timestamp tracking
- `id UUID` = Distributed system design
- JSONB columns = Flexible schema areas

### Protocol DS-04: Discover Foreign Key Relationships

```sql
-- All foreign keys for a table
SELECT
    tc.constraint_name,
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name,
    rc.update_rule,
    rc.delete_rule
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage ccu
    ON ccu.constraint_name = tc.constraint_name
    AND ccu.table_schema = tc.table_schema
JOIN information_schema.referential_constraints rc
    ON rc.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_schema = 'cmis'
  AND tc.table_name = 'campaigns';

-- Find all tables referencing a specific table
SELECT DISTINCT
    tc.table_name as referencing_table
FROM information_schema.table_constraints tc
JOIN information_schema.constraint_column_usage ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND ccu.table_name = 'orgs'
  AND ccu.table_schema = 'cmis';
```

**Pattern Recognition:**
- CASCADE delete = Dependent data deleted together
- SET NULL delete = Relationship broken but data kept
- RESTRICT delete = Prevents deletion (data integrity)
- Multiple tables referencing `orgs` = Multi-tenant architecture

### Protocol DS-05: Discover Indexes

```sql
-- List all indexes for a table
SELECT
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'cmis'
  AND tablename = 'campaigns'
ORDER BY indexname;

-- Find missing indexes on foreign keys
SELECT
    kcu.table_name,
    kcu.column_name
FROM information_schema.key_column_usage kcu
LEFT JOIN pg_indexes idx
    ON idx.tablename = kcu.table_name
    AND idx.indexdef LIKE '%' || kcu.column_name || '%'
WHERE kcu.table_schema = 'cmis'
  AND kcu.constraint_name LIKE '%_fkey'
  AND idx.indexname IS NULL;
```

**Pattern Recognition:**
- `org_id` indexed = Multi-tenant query optimization
- Composite indexes = Optimized for common query patterns
- Missing FK indexes = Performance opportunity
- Unique indexes = Data integrity constraints

### Protocol DS-06: Discover Row-Level Security (RLS) Policies

```sql
-- List all RLS policies
SELECT
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual,
    with_check
FROM pg_policies
WHERE schemaname LIKE 'cmis%'
ORDER BY schemaname, tablename, cmd;

-- Check if RLS is enabled on a table
SELECT
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables
WHERE schemaname = 'cmis'
  AND tablename = 'campaigns';

-- Count policies per table
SELECT
    tablename,
    COUNT(*) as policy_count
FROM pg_policies
WHERE schemaname = 'cmis'
GROUP BY tablename
ORDER BY policy_count DESC;
```

**Pattern Recognition:**
- 4 policies per table (SELECT, INSERT, UPDATE, DELETE) = Complete RLS coverage
- `cmis.get_current_org_id()` in policy = Org-based filtering
- `cmis.check_permission()` in policy = Permission-based access
- Missing policies = Security gap

### Protocol DS-07: Discover Check Constraints (Enums)

```sql
-- Find all check constraints
SELECT
    conname as constraint_name,
    conrelid::regclass as table_name,
    pg_get_constraintdef(oid) as definition
FROM pg_constraint
WHERE contype = 'c'
  AND connamespace = 'cmis'::regnamespace
ORDER BY conrelid::regclass::text;

-- Extract enum values from check constraint
SELECT
    conname,
    substring(pg_get_constraintdef(oid) from 'ARRAY\[(.*?)\]') as enum_values
FROM pg_constraint
WHERE contype = 'c'
  AND connamespace = 'cmis'::regnamespace
  AND pg_get_constraintdef(oid) LIKE '%ARRAY%';
```

**Pattern Recognition:**
- `CHECK (status = ANY (ARRAY[...]))` = Enum implementation
- Fixed value sets = Business rules enforced at DB level
- Status transitions = State machine pattern

### Protocol DS-08: Discover Database Functions

```sql
-- List all functions in CMIS schema
SELECT
    n.nspname as schema_name,
    p.proname as function_name,
    pg_get_functiondef(p.oid) as definition
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname LIKE 'cmis%'
ORDER BY n.nspname, p.proname;

-- Find RLS helper functions
SELECT proname
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'cmis'
  AND proname IN ('get_current_org_id', 'get_current_user_id', 'check_permission', 'init_transaction_context');
```

**Pattern Recognition:**
- `init_transaction_context()` = Context setting function
- `get_current_org_id()` = RLS policy helper
- `check_permission()` = Permission validation
- RETURN TABLE = Repository-friendly functions

---

## 2. Laravel Structure Discovery

### Protocol LS-01: Discover Directory Structure

```bash
# Overall app structure
tree app -L 2 -d

# Or using find
find app -type d -maxdepth 2 | sort

# Models organization
find app/Models -type d | sort

# Count files per model directory
for dir in app/Models/*/; do
    echo "$(find $dir -name '*.php' | wc -l) files in $(basename $dir)"
done | sort -rn

# Services organization
find app/Services -type d | sort

# Controllers organization
find app/Http/Controllers -type d | sort
```

**Pattern Recognition:**
- `app/Models/{Domain}/` = Domain-driven organization
- `app/Services/{Domain}/` = Service layer by domain
- `app/Repositories/{Domain}/` = Repository pattern
- Flat vs nested structure = Complexity indicator

### Protocol LS-02: Discover Laravel Version

```bash
# Laravel version
composer show laravel/framework | grep versions

# Or from artisan
php artisan --version

# PHP version
php -v

# All major dependencies
composer show | grep laravel
```

**Pattern Recognition:**
- Laravel 11+ = Latest features available
- Laravel 10 = LTS considerations
- PHP 8.3+ = Modern PHP features available

### Protocol LS-03: Discover Model Organization

```bash
# List all models
find app/Models -name "*.php" -type f | sort

# Count models per domain
find app/Models -name "*.php" -type f | xargs dirname | sort | uniq -c | sort -rn

# Find base model
find app/Models -name "BaseModel.php" -o -name "Model.php"

# Check for traits
find app/Models -name "*Trait.php"

# Check for scopes
find app/Models -type d -name "Scopes"
```

**Pattern Recognition:**
- BaseModel.php = Shared functionality
- High model count in domain = Complex domain
- Traits = Reusable functionality
- Scopes directory = Query scopes organization

### Protocol LS-04: Discover Middleware Chain

```bash
# List all middleware
ls -la app/Http/Middleware/

# Find context-setting middleware
ls -la app/Http/Middleware/ | grep -i context

# Check middleware registration
cat app/Http/Kernel.php | grep -A 50 "protected \$middleware"

# Route-specific middleware
cat app/Http/Kernel.php | grep -A 50 "protected \$middlewareGroups"
```

**Pattern Recognition:**
- `SetDatabaseContext` = RLS context management
- `ValidateOrgAccess` = Multi-tenant authorization
- Middleware order matters for RLS
- `auth:sanctum` = API authentication

### Protocol LS-05: Discover Service Providers

```bash
# List all service providers
find app/Providers -name "*.php"

# Check registered providers
cat config/app.php | grep -A 100 "'providers'"

# Repository bindings
grep -r "bind\|singleton" app/Providers/ --include="*.php"
```

**Pattern Recognition:**
- RepositoryServiceProvider = Repository pattern implementation
- EventServiceProvider = Event-listener bindings
- Custom providers = Domain-specific bootstrapping

### Protocol LS-06: Discover Configuration Files

```bash
# List all config files
ls -la config/

# Check database configuration
cat config/database.php | grep -A 20 "connections"

# Queue configuration
cat config/queue.php | grep -A 10 "connections"

# Cache configuration
cat config/cache.php | grep -A 10 "stores"

# Services configuration (APIs, OAuth)
cat config/services.php | head -50
```

**Pattern Recognition:**
- Multiple queue connections = Domain-specific queues
- Redis cache = High-performance caching
- PostgreSQL = Primary database
- API credentials in services.php = External integrations

---

## 3. Frontend Stack Discovery

### Protocol FS-01: Discover JavaScript Framework

```bash
# Check package.json dependencies
cat package.json | jq '.dependencies'

# Or grep for common frameworks
cat package.json | grep -E "vue|react|alpine|svelte"

# Check main JS file
head -30 resources/js/app.js

# Check for framework-specific directories
ls -la resources/js/components/ 2>/dev/null || echo "No components directory"
ls -la resources/js/views/ 2>/dev/null || echo "No views directory"
```

**Pattern Recognition:**
- `alpine` in dependencies = Alpine.js
- `vue` + `@vue` packages = Vue.js
- `react` + `react-dom` = React
- No major framework = Vanilla JS or library-based

### Protocol FS-02: Discover CSS Framework

```bash
# Check for CSS framework
cat package.json | grep -E "tailwind|bootstrap|bulma"

# Check CSS entry point
head -20 resources/css/app.css

# Check for PostCSS config (Tailwind indicator)
cat postcss.config.js 2>/dev/null || echo "No PostCSS config"

# Check for Tailwind config
ls -la tailwind.config.js 2>/dev/null || echo "No Tailwind config"
```

**Pattern Recognition:**
- `tailwindcss` in package.json + `@tailwind` directives = Tailwind CSS
- `bootstrap` in package.json = Bootstrap
- PostCSS config presence = Build-time CSS processing

### Protocol FS-03: Discover Build Tool

```bash
# Check for build tool
cat package.json | grep -E "vite|webpack|mix|parcel"

# Check for config files
ls -la vite.config.js webpack.config.js webpack.mix.js 2>/dev/null

# Check build scripts
cat package.json | jq '.scripts'
```

**Pattern Recognition:**
- `vite` = Modern, fast build tool (Laravel 9+)
- `laravel-mix` = Traditional Laravel build tool
- `npm run dev` script = Development build
- `npm run build` script = Production build

### Protocol FS-04: Discover UI Libraries

```bash
# Check for chart libraries
cat package.json | grep -E "chart|apex|d3|echarts"

# Check for form libraries
cat package.json | grep -E "form|validation"

# Check for utility libraries
cat package.json | grep -E "axios|lodash|dayjs|moment"

# Check for icon libraries
cat package.json | grep -E "icon|lucide|hero|font-awesome"
```

**Pattern Recognition:**
- `chart.js` or `apexcharts` = Data visualization
- `axios` = HTTP client
- `dayjs` or `moment` = Date manipulation
- Icon libraries = UI polish level

---

## 4. API Endpoint Discovery

### Protocol API-01: List All Routes

```bash
# List all API routes
php artisan route:list --path=api

# Filter by method
php artisan route:list --method=GET --path=api

# Filter by name
php artisan route:list --name=campaigns

# Show only route URIs
php artisan route:list --path=api --columns=uri,method

# Count routes
php artisan route:list --path=api --json | jq '. | length'
```

**Pattern Recognition:**
- `/api/orgs/{org_id}/*` = Org-scoped routes
- Middleware column shows security chain
- Resource routes = RESTful API design
- Route names = Named route conventions

### Protocol API-02: Discover Route Organization

```bash
# List route files
ls -la routes/

# Check main API routes
head -50 routes/api.php

# Check for route groups
cat routes/api.php | grep -B 2 -A 10 "Route::group\|Route::prefix"

# Check middleware usage
cat routes/api.php | grep "middleware"
```

**Pattern Recognition:**
- Prefixed route groups = API versioning or organization
- Middleware in routes = Security requirements
- Multiple route files = Domain separation
- API resource routes = RESTful conventions

### Protocol API-03: Discover API Resources (Transformers)

```bash
# Find API resources
find app/Http/Resources -name "*.php" 2>/dev/null || echo "No Resources directory"

# Count resources
find app/Http/Resources -name "*.php" 2>/dev/null | wc -l

# Check resource collections
find app/Http/Resources -name "*Collection.php"
```

**Pattern Recognition:**
- Resource files = Consistent API responses
- Collection resources = Array transformations
- Resource per model = Comprehensive API design

---

## 5. Multi-Tenancy Discovery

### Protocol MT-01: Verify RLS Implementation

```bash
# Check for context middleware
ls -la app/Http/Middleware/ | grep -i context

# Examine middleware implementation
cat app/Http/Middleware/SetDatabaseContext.php | grep -A 10 "handle"

# Check route middleware
cat routes/api.php | grep "set.db.context\|SetDatabaseContext"
```

**Pattern Recognition:**
- Middleware calls `init_transaction_context()` = RLS setup
- Middleware applied to org-scoped routes = Multi-tenant protection
- Order matters: auth → validate.org → set.context

### Protocol MT-02: Discover Org-Scoped Routes

```bash
# Find org-scoped routes
php artisan route:list | grep "{org_id}"

# Count org-scoped vs non-org routes
echo "Org-scoped: $(php artisan route:list | grep '{org_id}' | wc -l)"
echo "Non-org: $(php artisan route:list | grep -v '{org_id}' | wc -l)"

# Check route parameter validation
grep -r "Route::bind\|whereUuid" routes/ app/Providers/
```

**Pattern Recognition:**
- High percentage of org-scoped routes = Multi-tenant centric
- Consistent org_id parameter = Convention adherence
- UUID validation = Security best practice

### Protocol MT-03: Discover User-Org Relationships

```sql
-- Check user-org pivot table
\d cmis.user_orgs

-- Query user org access
SELECT COUNT(DISTINCT org_id) as org_count_per_user
FROM cmis.user_orgs
GROUP BY user_id;

-- Check for role assignment
SELECT DISTINCT role_id
FROM cmis.user_orgs;
```

**Pattern Recognition:**
- Pivot table = Many-to-many relationship
- Role in pivot = Per-org role assignment
- Multiple orgs per user = Multi-tenant access

---

## 6. Service Layer Discovery

### Protocol SL-01: Discover Service Organization

```bash
# List all services
find app/Services -name "*.php" -type f | sort

# Services by domain
find app/Services -type d -maxdepth 1 | tail -n +2 | while read dir; do
    count=$(find "$dir" -name "*.php" | wc -l)
    echo "$count services in $(basename $dir)"
done | sort -rn

# Find service interfaces
find app/Services -name "*Interface.php" -o -name "*Contract.php"
```

**Pattern Recognition:**
- Services per domain = Domain complexity
- Service interfaces = Testability and flexibility
- Deep directory nesting = Feature granularity

### Protocol SL-02: Discover Service Dependencies

```bash
# Check constructor injection in service
grep -A 20 "__construct" app/Services/CampaignService.php

# Find service provider bindings
grep -r "bind.*Service" app/Providers/

# Check for service facades
find app/Facades -name "*.php" 2>/dev/null
```

**Pattern Recognition:**
- Constructor injection = Proper DI usage
- Interface type hints = Loose coupling
- Many dependencies = Complex service (consider splitting)

### Protocol SL-03: Discover Service Responsibilities

```bash
# List methods in a service
grep "public function" app/Services/CampaignService.php

# Count methods per service
for service in app/Services/*.php; do
    count=$(grep -c "public function" "$service")
    echo "$count methods in $(basename $service)"
done | sort -rn
```

**Pattern Recognition:**
- Many methods (>20) = God service (refactor candidate)
- Method names reveal responsibilities
- CRUD methods = Data-centric service
- Business logic methods = Domain service

---

## 7. Repository Pattern Discovery

### Protocol RP-01: Discover Repository Implementation

```bash
# Check if repositories exist
ls -la app/Repositories/ 2>/dev/null || echo "No Repositories directory"

# List all repositories
find app/Repositories -name "*Repository.php" | sort

# Find repository interfaces/contracts
find app/Repositories -type d -name "Contracts" -o -name "Interfaces"

# Check for repository guide
ls -la app/Repositories/*GUIDE*.md app/Repositories/*README*.md 2>/dev/null
```

**Pattern Recognition:**
- Repositories directory exists = Pattern implemented
- Contracts directory = Interface-based design
- AI_AGENT_GUIDE.md = AI-aware documentation

### Protocol RP-02: Check Repository Bindings

```bash
# Find repository service provider
find app/Providers -name "*Repository*Provider.php"

# Check bindings
cat app/Providers/RepositoryServiceProvider.php 2>/dev/null | grep "bind"

# Verify binding in config
cat config/app.php | grep "RepositoryServiceProvider"
```

**Pattern Recognition:**
- Interface → Implementation binding = Proper DI
- Multiple bindings = Multiple repositories
- Provider registered = Pattern active

### Protocol RP-03: Examine Repository Methods

```bash
# If AI_AGENT_GUIDE.md exists
cat app/Repositories/AI_AGENT_GUIDE.md | grep -A 5 "METHOD"

# Otherwise, examine a repository directly
grep "public function" app/Repositories/CMIS/CampaignRepository.php | head -20
```

**Pattern Recognition:**
- `find*()` methods = Query operations
- `create*()` methods = Creation operations
- Return type hints = Type safety
- Collection returns = Multiple results

---

## 8. Event System Discovery

### Protocol ES-01: Discover Events

```bash
# List all events
find app/Events -name "*.php" | sort

# Count events
find app/Events -name "*.php" | wc -l

# Check for domain organization
find app/Events -type d
```

**Pattern Recognition:**
- Events directory exists = Event-driven architecture
- Domain subdirectories = Organized by domain
- Event naming (CampaignCreated) = Past tense convention

### Protocol ES-02: Discover Listeners

```bash
# List all listeners
find app/Listeners -name "*.php" | sort

# Check event-listener registration
cat app/Providers/EventServiceProvider.php | grep -A 100 "protected \$listen"
```

**Pattern Recognition:**
- Listeners per event = Level of integration
- Multiple listeners = Decoupled actions
- Job listeners = Async processing

### Protocol ES-03: Discover Event Usage

```bash
# Search for event dispatching
grep -r "event(\|Event::dispatch" app/ --include="*.php" | head -20

# Find models with events
grep -r "protected \$dispatchesEvents" app/Models/ --include="*.php"
```

**Pattern Recognition:**
- event() helper = Simple dispatching
- Model events = ORM integration
- Service events = Business logic events

---

## 9. Job Queue Discovery

### Protocol JQ-01: Discover Jobs

```bash
# List all jobs
find app/Jobs -name "*.php" | sort

# Count jobs
find app/Jobs -name "*.php" | wc -l

# Check implements ShouldQueue
grep -l "implements ShouldQueue" app/Jobs/*.php
```

**Pattern Recognition:**
- Jobs directory size = Async usage level
- ShouldQueue interface = Queued jobs
- Job naming conventions = Purpose clarity

### Protocol JQ-02: Discover Queue Configuration

```bash
# Check queue config
cat config/queue.php | grep -A 10 "connections"

# Check .env for queue driver
cat .env.example | grep QUEUE

# Find queue names
grep -r "->onQueue" app/ --include="*.php" | grep -o "onQueue('[^']*" | sort -u
```

**Pattern Recognition:**
- Redis queue = High performance
- Multiple queue names = Priority separation
- Database queue = Simpler setup

### Protocol JQ-03: Discover Job Dispatching

```bash
# Find job dispatching
grep -r "::dispatch\|dispatch(" app/ --include="*.php" | grep -v "Event::dispatch" | head -20

# Check for delayed dispatching
grep -r "->delay\|->later" app/ --include="*.php"
```

**Pattern Recognition:**
- Dispatch from services = Async business logic
- Delay usage = Scheduled tasks
- Chain usage = Workflow orchestration

---

## 10. AI & Embeddings Discovery

### Protocol AI-01: Discover pgvector Setup

```sql
-- Check for pgvector extension
SELECT * FROM pg_extension WHERE extname = 'vector';

-- Get extension version
SELECT extversion FROM pg_extension WHERE extname = 'vector';

-- Find tables with vector columns
SELECT
    table_schema,
    table_name,
    column_name,
    udt_name
FROM information_schema.columns
WHERE udt_name = 'vector'
ORDER BY table_schema, table_name;
```

**Pattern Recognition:**
- pgvector installed = Vector search capable
- Vector columns = Embedding storage
- 768 dimensions = Google Gemini embeddings
- 1536 dimensions = OpenAI embeddings

### Protocol AI-02: Discover Embedding Services

```bash
# Find embedding-related services
find app/Services -name "*Embedding*" -o -name "*AI*" -o -name "*Semantic*"

# Check for Gemini integration
grep -r "Gemini\|gemini" app/Services/ config/ --include="*.php"

# Find embedding jobs
find app/Jobs -name "*Embedding*" -o -name "*Semantic*"
```

**Pattern Recognition:**
- EmbeddingOrchestrator = Centralized embedding service
- Gemini service = Google AI integration
- Embedding jobs = Async generation

### Protocol AI-03: Discover AI Configuration

```bash
# Check AI services config
cat config/services.php | grep -A 10 "gemini\|openai\|ai"

# Check rate limiting
cat config/services.php | grep -A 5 "rate_limit"

# Check .env for API keys
cat .env.example | grep -i "AI_\|GEMINI_\|OPENAI_"
```

**Pattern Recognition:**
- API key configuration = External AI service
- Rate limits = Quota management
- Multiple AI services = Fallback or comparison

### Protocol AI-04: Discover Semantic Search Implementation

```bash
# Find semantic search services
grep -r "semantic\|similarity" app/Services/ --include="*.php" -l

# Check for similarity search queries
grep -r "<=>" app/ --include="*.php"

# Find embedding cache
ls -la app/Models/ | grep -i "embedding\|cache"
```

**Pattern Recognition:**
- `<=>` operator = pgvector cosine similarity
- Embedding cache = Performance optimization
- SemanticSearchService = Search abstraction

---

## 11. Platform Integration Discovery

### Protocol PI-01: Discover Platform Connectors

```bash
# List platform services
ls -la app/Services/AdPlatforms/

# Find platform interfaces
find app/Services -path "*/Contracts/*" -o -path "*/Interfaces/*" | grep -i platform

# Check for factory pattern
find app/Services -name "*Factory.php" | grep -i platform
```

**Pattern Recognition:**
- AdPlatformFactory = Factory pattern
- Platform subdirectories = Multiple integrations
- Abstract classes = Shared functionality

### Protocol PI-02: Discover OAuth Configuration

```bash
# Check OAuth config
cat config/services.php | grep -A 15 -B 2 "meta\|google\|linkedin\|tiktok"

# Find OAuth controllers
find app/Http/Controllers -name "*Integration*" -o -name "*OAuth*" -o -name "*Connect*"

# Check for OAuth routes
cat routes/api.php | grep -i "oauth\|connect\|callback"
```

**Pattern Recognition:**
- OAuth credentials in services.php = Platform setup
- Callback routes = OAuth flow implementation
- Scopes configuration = Permission requests

### Protocol PI-03: Discover Webhook Handling

```bash
# Find webhook controllers
find app/Http/Controllers -name "*Webhook*"

# Check webhook routes
cat routes/api.php | grep -i webhook

# Find webhook middleware
ls -la app/Http/Middleware/ | grep -i webhook
```

**Pattern Recognition:**
- Webhook routes = Real-time integration
- Signature verification = Security
- Webhook jobs = Async processing

---

## 12. Testing Infrastructure Discovery

### Protocol TI-01: Discover Test Organization

```bash
# List test directories
find tests -type d | sort

# Count tests
find tests -name "*Test.php" | wc -l

# Tests by type
echo "Feature: $(find tests/Feature -name '*Test.php' | wc -l)"
echo "Unit: $(find tests/Unit -name '*Test.php' | wc -l)"
echo "Integration: $(find tests/Integration -name '*Test.php' 2>/dev/null | wc -l)"
```

**Pattern Recognition:**
- Feature tests > Unit tests = Integration-heavy testing
- Integration tests = Complex workflow testing
- E2E tests = User flow testing

### Protocol TI-02: Discover Test Configuration

```bash
# Check PHPUnit config
cat phpunit.xml | grep -A 5 "testsuites"

# Check for Pest
cat composer.json | grep -i pest

# Check test database
cat phpunit.xml | grep -i "DB_\|DATABASE"
```

**Pattern Recognition:**
- Multiple test suites = Organized testing
- Pest = Modern testing framework
- In-memory database = Fast tests

### Protocol TI-03: Discover Factory & Seeder Usage

```bash
# List factories
find database/factories -name "*.php" | wc -l

# List seeders
find database/seeders -name "*.php"

# Check for test-specific seeders
grep -r "DatabaseSeeder\|TestSeeder" tests/
```

**Pattern Recognition:**
- Factories per model = Test data generation
- Test seeders = Consistent test data
- Factory states = Data variation

---

## 🎯 Quick Reference: Choose Your Protocol

| I Need To... | Use Protocol | Command Hint |
|--------------|--------------|--------------|
| Count database tables | DS-02 | `SELECT COUNT(*) FROM information_schema.tables WHERE...` |
| Find a model's location | LS-03 | `find app/Models -name "ModelName.php"` |
| Discover frontend framework | FS-01 | `cat package.json | jq '.dependencies'` |
| List API routes | API-01 | `php artisan route:list --path=api` |
| Check RLS policies | DS-06 | `SELECT * FROM pg_policies WHERE...` |
| Find services | SL-01 | `find app/Services -name "*.php"` |
| Discover repositories | RP-01 | `find app/Repositories -name "*Repository.php"` |
| List events | ES-01 | `find app/Events -name "*.php"` |
| Find jobs | JQ-01 | `find app/Jobs -name "*.php"` |
| Check pgvector | AI-01 | `SELECT * FROM pg_extension WHERE extname = 'vector'` |
| Discover platforms | PI-01 | `ls app/Services/AdPlatforms/` |
| Count tests | TI-01 | `find tests -name "*Test.php" | wc -l` |

---

## 💡 Usage Patterns

### Pattern 1: Full System Discovery

When starting fresh with CMIS:

```bash
# 1. Database
psql -c "SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'cmis%'"
psql -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema LIKE 'cmis%'"

# 2. Laravel
composer show laravel/framework | grep versions
php artisan route:list --path=api | wc -l

# 3. Frontend
cat package.json | jq '.dependencies | keys[]'

# 4. Structure
find app -type d -maxdepth 2

# 5. Tests
find tests -name "*Test.php" | wc -l
```

### Pattern 2: Feature-Specific Discovery

When investigating a specific feature:

```bash
# 1. Find related models
find app/Models -name "*FeatureName*.php"

# 2. Find related services
find app/Services -name "*FeatureName*.php"

# 3. Find related controllers
find app/Http/Controllers -name "*FeatureName*.php"

# 4. Find related routes
php artisan route:list | grep -i "feature"

# 5. Find related migrations
ls database/migrations/ | grep -i "feature"

# 6. Check database schema
psql -c "\d cmis.feature_table_name"
```

### Pattern 3: Troubleshooting Discovery

When debugging an issue:

```bash
# 1. Check recent changes
git log --oneline --since="1 week ago" | head -20

# 2. Find related files
grep -r "error message" app/ --include="*.php"

# 3. Check logs
tail -100 storage/logs/laravel.log

# 4. Verify configuration
php artisan config:show

# 5. Check queue status
php artisan queue:failed
```

---

## 🚀 Conclusion

These discovery protocols empower agents to:

1. **Discover current state** rather than assume from docs
2. **Verify assumptions** before making recommendations
3. **Adapt to changes** in codebase structure
4. **Provide accurate guidance** regardless of evolution

**Next Step:** Apply these protocols using the META_COGNITIVE_FRAMEWORK methodology.

---

**Protocol Version:** 2.0
**Last Updated:** 2025-11-18
**Maintained By:** CMIS AI Agent Development Team
**Companion Document:** META_COGNITIVE_FRAMEWORK.md

*"The best knowledge is not what you memorize, but what you can discover."*
