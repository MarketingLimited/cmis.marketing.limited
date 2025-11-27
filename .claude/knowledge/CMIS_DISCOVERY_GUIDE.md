# CMIS Discovery Guide
**Version:** 2.1
**Last Updated:** 2025-11-27
**Purpose:** Teaching agents HOW to discover CMIS knowledge dynamically through executable commands
**Prerequisites:** Read `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md` and `DISCOVERY_PROTOCOLS.md`
**Framework:** META_COGNITIVE_FRAMEWORK v2.0

---

## ⚠️ IMPORTANT: Environment Configuration for Discovery

**All discovery commands that access the database MUST use `.env` credentials.**

```bash
# Read database configuration before discovery
cat .env | grep DB_

# Extract for use in discovery queries
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Use in discovery SQL commands
PGPASSWORD="$DB_PASSWORD" psql \
  -h "$DB_HOST" \
  -U "$DB_USERNAME" \
  -d "$DB_DATABASE" \
  -c "SELECT * FROM pg_tables WHERE schemaname LIKE 'cmis%';"
```

**Discovery Principle:** Never assume database names or credentials. Always discover from `.env` first.

---

## 🎯 PHILOSOPHY: DISCOVERY OVER DOCUMENTATION

**Wrong Approach:**
```
❌ "CMIS has 148+ tables across 12 schemas"
❌ "Uses Alpine.js 3.13.5 and Laravel 12"
❌ "Supports Meta, Google, TikTok platforms"
```

**Correct Approach:**
```
✅ "How to discover current table count and schema organization"
✅ "How to identify frontend and backend stack versions"
✅ "How to find which platforms are currently integrated"
```

---

## 📚 DISCOVERY PATTERNS BY DOMAIN

### 1. Project Architecture Discovery

**Discover Multi-Tenancy Implementation:**
```sql
-- Find RLS helper functions
SELECT
    proname as function_name,
    pg_get_functiondef(p.oid) as definition
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'cmis'
  AND proname LIKE '%context%' OR proname LIKE '%org%'
ORDER BY proname;

-- Check which tables have RLS enabled
SELECT
    schemaname,
    tablename,
    rowsecurity as rls_enabled
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  AND rowsecurity = true
ORDER BY schemaname, tablename;
```

**Discover Schema Organization:**
```sql
-- Count tables per schema
SELECT
    table_schema,
    COUNT(*) as table_count,
    pg_size_pretty(SUM(pg_total_relation_size(quote_ident(table_schema) || '.' || quote_ident(table_name)))) as total_size
FROM information_schema.tables
WHERE table_schema LIKE 'cmis%'
  AND table_type = 'BASE TABLE'
GROUP BY table_schema
ORDER BY table_count DESC;

-- Find purpose of each schema from comments
SELECT
    nspname as schema_name,
    obj_description(oid, 'pg_namespace') as description
FROM pg_namespace
WHERE nspname LIKE 'cmis%'
ORDER BY nspname;
```

### 2. Technology Stack Discovery

**Discover Backend Stack:**
```bash
# Laravel version
cat composer.json | jq '.require["laravel/framework"]'

# PHP version requirement
cat composer.json | jq '.require.php'

# Database details
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "SELECT version();"

# Check for pgvector
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "SELECT extversion FROM pg_extension WHERE extname = 'vector';"

# Authentication mechanism
grep -r "Sanctum\|Passport\|JWT" config/auth.php app/Http/Kernel.php
```

**Discover Frontend Stack:**
```bash
# Frontend dependencies
cat package.json | jq '{
  alpine: .dependencies["alpinejs"],
  tailwind: .devDependencies["tailwindcss"],
  chartjs: .dependencies["chart.js"],
  vite: .devDependencies["vite"],
  axios: .dependencies["axios"]
}'

# Build tool
cat package.json | jq '.scripts.dev'
```

**Discover AI/ML Stack:**
```bash
# Find embedding provider
grep -r "Gemini\|OpenAI\|embedding" app/Services/AI/ config/services.php | head -10

# Check vector dimensions
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT DISTINCT
    pg_catalog.format_type(atttypid, atttypmod) as vector_type
FROM pg_attribute
WHERE atttypid = (SELECT oid FROM pg_type WHERE typname = 'vector')
LIMIT 1;
"
```

### 3. Business Domain Discovery

**Discover Core Models:**
```bash
# Find all models
find app/Models -name "*.php" | wc -l

# Discover model organization
ls -la app/Models/*/

# Find models by domain
for dir in app/Models/*/; do
    echo "=== $(basename $dir) ==="
    ls -1 $dir | head -5
done
```

**Discover Model Relationships:**
```bash
# Find belongs to relationships
grep -r "belongsTo\|hasMany\|hasOne" app/Models/ | head -20

# Discover pivot tables
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'cmis'
  AND table_name LIKE '%\_%\_%'  -- Pivot table pattern
ORDER BY table_name;
"
```

**Discover Domain Boundaries:**
```sql
-- Find tables by domain (schema = domain in CMIS)
SELECT
    table_schema as domain,
    COUNT(*) as entity_count,
    ARRAY_AGG(table_name ORDER BY table_name) as entities
FROM information_schema.tables
WHERE table_schema LIKE 'cmis%'
  AND table_type = 'BASE TABLE'
GROUP BY table_schema
ORDER BY entity_count DESC;
```

### 4. Integration Discovery

**Discover Platform Integrations:**
```bash
# Find connector implementations
find app/Services -name "*Connector.php" -o -name "*Platform*.php" | sort

# Check configured platforms
grep -A 5 "meta\|google\|tiktok\|linkedin\|twitter\|snapchat" config/services.php
```

```sql
-- Find active integrations
SELECT
    platform,
    COUNT(*) as integration_count,
    COUNT(DISTINCT org_id) as org_count,
    COUNT(CASE WHEN is_active = true THEN 1 END) as active_count
FROM cmis.integrations
WHERE deleted_at IS NULL
GROUP BY platform
ORDER BY integration_count DESC;
```

**Discover OAuth Flows:**
```bash
# Find OAuth routes
grep -r "oauth\|callback" routes/api.php

# Discover token storage
grep -A 10 "access_token\|refresh_token" database/migrations/*integrations*.php
```

### 5. Feature Discovery

**Discover Available Features:**
```bash
# Find all routes
php artisan route:list --columns=Method,URI,Name | grep api

# Discover feature flags
grep -r "Feature::\|Config::get.*feature" app/ config/features.php

# Find scheduled jobs
grep -A 20 "schedule.*function" app/Console/Kernel.php
```

**Discover Queue Jobs:**
```bash
# Find all jobs
find app/Jobs -name "*.php" | sort

# Check queue configuration
cat config/queue.php | jq '.connections | keys'

# Discover job patterns
for job in app/Jobs/*.php; do
    echo "=== $(basename $job) ==="
    grep -A 2 "class.*implements" $job
done
```

---

## 🔍 PATTERN RECOGNITION TECHNIQUES

### Pattern 1: Identifying Architecture Patterns

**Repository Pattern:**
```bash
# Check if repository pattern is used
ls -la app/Repositories/

# Read the AI guide for repositories
cat app/Repositories/AI_AGENT_GUIDE.md | head -50
```

**Service Layer Pattern:**
```bash
# Find service classes
find app/Services -name "*.php" | wc -l

# Discover service organization
ls -la app/Services/*/
```

**Event-Driven Pattern:**
```bash
# Find events and listeners
ls -la app/Events/ app/Listeners/

# Discover event subscriptions
grep -r "Event::listen\|protected \$listen" app/Providers/EventServiceProvider.php
```

### Pattern 2: Understanding Data Flow

**Request Lifecycle:**
```bash
# 1. Discover middleware chain
cat app/Http/Kernel.php | grep -A 20 "middlewareGroups"

# 2. Find route definitions
grep -A 5 "Route::group" routes/api.php | head -20

# 3. Discover controller patterns
find app/Http/Controllers -name "*.php" | head -10
cat app/Http/Controllers/API/CampaignController.php | grep -A 3 "public function"
```

**Data Persistence:**
```sql
-- Understand how data flows through layers:
-- 1. Controller receives request
-- 2. Service layer processes business logic
-- 3. Repository layer handles data access
-- 4. Model interacts with database
-- 5. RLS enforces security
-- 6. Response returns to controller

-- Verify RLS is working:
SELECT current_setting('cmis.current_org_id', true) as current_org;
```

### Pattern 3: Security Architecture

**Discover Authentication:**
```bash
# Find auth configuration
cat config/auth.php | jq '.guards'

# Discover middleware
grep -r "auth:sanctum\|auth:api" routes/api.php | head -10

# Check sanctum configuration
cat config/sanctum.php | jq '.middleware'
```

**Discover Authorization:**
```bash
# Find policies
ls -la app/Policies/

# Discover permission system
find app/Models -name "*Permission*.php"
find database/migrations -name "*permission*.php"
```

```sql
-- Discover permission structure
SELECT
    table_name,
    column_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND (table_name LIKE '%permission%' OR table_name LIKE '%role%')
ORDER BY table_name, ordinal_position;
```

---

## 📖 LEARNING WORKFLOWS

### Workflow 1: Understanding a New Feature Area

**When asked about a feature you don't know:**

1. **Discover Routes:**
```bash
php artisan route:list | grep -i "feature-keyword"
```

2. **Find Controllers:**
```bash
find app/Http/Controllers -name "*FeatureKeyword*.php"
```

3. **Discover Models:**
```bash
find app/Models -name "*FeatureKeyword*.php"
```

4. **Check Database:**
```sql
SELECT table_name
FROM information_schema.tables
WHERE table_schema LIKE 'cmis%'
  AND table_name LIKE '%feature_keyword%';
```

5. **Find Services:**
```bash
find app/Services -name "*FeatureKeyword*.php"
```

6. **Read Tests:**
```bash
find tests -name "*FeatureKeyword*.php"
```

### Workflow 2: Diagnosing Issues

**When troubleshooting a problem:**

1. **Check Recent Changes:**
```bash
git log --since="7 days ago" --oneline -- path/to/relevant/files
```

2. **Discover Related Code:**
```bash
grep -r "error-keyword" app/ tests/ | head -20
```

3. **Check Database State:**
```sql
-- Verify data integrity
-- Check recent records
-- Analyze patterns
```

4. **Review Logs:**
```bash
tail -100 storage/logs/laravel.log | grep -i "error\|exception"
```

5. **Test Hypothesis:**
```bash
php artisan tinker
# Run diagnostic queries
```

### Workflow 3: Implementing New Features

**When building something new:**

1. **Find Similar Implementations:**
```bash
find app -name "*Similar*.php"
```

2. **Discover Patterns:**
```bash
grep -A 20 "class.*Controller" app/Http/Controllers/API/*Similar*.php
```

3. **Check Database Schema:**
```sql
\d+ cmis.similar_table
```

4. **Review Tests:**
```bash
find tests -name "*Similar*Test.php"
```

5. **Follow Conventions:**
```bash
# Apply discovered patterns to new feature
```

---

## 🎓 SUCCESS CRITERIA FOR DISCOVERY

**Successful Discovery:**
- ✅ Can find current state without asking user
- ✅ Discovered information is accurate (from code/database)
- ✅ Can adapt to project changes automatically
- ✅ Understands patterns, not just facts
- ✅ Can infer missing information from patterns

**Failed Discovery:**
- ❌ Assumes facts without verification
- ❌ Uses outdated information from documentation
- ❌ Can't find information when asked
- ❌ Memorizes specifics instead of patterns
- ❌ Breaks when project structure changes

---

## 🚀 QUICK REFERENCE: ESSENTIAL DISCOVERIES

### Project Structure
```bash
ls -la app/              # Application code
ls -la database/         # Migrations, seeders
ls -la .claude/          # AI agent configuration
cat composer.json | jq '.require | keys'  # PHP dependencies
cat package.json | jq '.dependencies | keys'  # JS dependencies
```

### Database Exploration
```sql
-- Schema overview
\dn

-- Tables per schema
SELECT nspname, COUNT(*) FROM pg_class c
JOIN pg_namespace n ON n.oid = c.relnamespace
WHERE nspname LIKE 'cmis%' AND relkind = 'r'
GROUP BY nspname ORDER BY count DESC;

-- Current org context
SELECT current_setting('cmis.current_org_id', true);
SELECT current_setting('cmis.current_user_id', true);
```

### Code Navigation
```bash
# Find by pattern
grep -r "pattern" app/

# List files by type
find app/Models -name "*.php" | wc -l
find app/Services -name "*.php" | wc -l
find app/Jobs -name "*.php" | wc -l

# Read specific file
cat app/Models/Core/Campaign.php | head -50
```

---

## 🔍 Quick Reference

| I Want To Discover... | Use This Command | Knowledge File |
|----------------------|------------------|----------------|
| Database schemas | `SELECT nspname FROM pg_namespace WHERE nspname LIKE 'cmis%'` (use .env) | DISCOVERY_PROTOCOLS.md |
| Frontend stack | `cat package.json \| jq '.dependencies'` | Codebase Discovery |
| Laravel version | `cat composer.json \| jq '.require["laravel/framework"]'` | Codebase Discovery |
| Model structure | `find app/Models -name "*.php" \| wc -l` | Laravel Discovery |
| Platform integrations | `find app/Services -name "*Platform*.php"` | Platform Discovery |
| RLS policies | `SELECT COUNT(*) FROM pg_policies WHERE schemaname LIKE 'cmis%'` (use .env) | Multi-Tenancy Discovery |
| AI capabilities | `grep -r "vector\|embedding" app/` | AI Capabilities Discovery |

---

## 📚 Related Knowledge

**Prerequisites:**
- **META_COGNITIVE_FRAMEWORK.md** - Adaptive intelligence and discovery principles
- **DISCOVERY_PROTOCOLS.md** - Comprehensive executable discovery commands

**Related Files:**
- **CMIS_PROJECT_KNOWLEDGE.md** - Core project architecture overview
- **CMIS_SQL_INSIGHTS.md** - Database discovery patterns
- **CMIS_DATA_PATTERNS.md** - Data structure discovery
- **LARAVEL_CONVENTIONS.md** - Laravel-specific discovery patterns
- **MULTI_TENANCY_PATTERNS.md** - RLS discovery patterns

**See Also:**
- **CLAUDE.md** - Main project guidelines with environment configuration

---

**Last Updated:** 2025-11-27
**Version:** 2.1
**Maintained By:** CMIS AI Agent Development Team
**Framework:** META_COGNITIVE_FRAMEWORK
**Approach:** Dynamic Discovery > Static Documentation

*"The best knowledge is knowing how to learn. Discover fresh, use .env always."*
