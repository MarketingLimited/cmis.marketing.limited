# CMIS Project Knowledge - Discovery Guide
## Cognitive Marketing Information System - Teaching Agents HOW to Learn the System

**Last Updated:** 2025-11-18
**Framework:** META_COGNITIVE_FRAMEWORK v2.0
**Philosophy:** Discovery Over Documentation

---

## 🎓 PHILOSOPHY: ADAPTIVE INTELLIGENCE

**Not:** "CMIS has 189 tables"
**But:** "How do I discover what tables exist in CMIS?"

**Not:** "Alpine.js 3.13.5 is used"
**But:** "How do I identify the frontend stack?"

**Not:** "This is how multi-tenancy works"
**But:** "How do I discover the multi-tenancy implementation?"

---

## 🔍 DISCOVERING PROJECT ESSENCE

### Question 1: What is this project?

**Discovery Commands:**

```bash
# Project identity
cat composer.json | jq '.name, .description'
cat package.json | jq '.name, .description'

# Purpose from README
head -50 README.md | grep -A 10 "##\|#"

# Core purpose from route structure
grep -o "Route::.*" routes/api.php | head -20
# Inference: Routes reveal primary features
```

**Pattern Recognition:**

```bash
# If routes contain 'campaigns', 'social', 'integrations':
# → Campaign management platform

# If models contain 'AdAccount', 'SocialPost':
# → Marketing automation system

# If migrations use pgvector:
# → AI-powered platform
```

### Question 2: What makes this project unique?

**Discovery Protocol:**

```bash
# 1. Check for unusual database features
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT nspname FROM pg_namespace WHERE nspname LIKE 'cmis%';
"
# If multiple schemas → Domain-driven design

# 2. Check for RLS policies
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT schemaname, tablename, COUNT(*)
FROM pg_policies
WHERE schemaname LIKE 'cmis%'
GROUP BY schemaname, tablename
LIMIT 10;
"
# If many policies → RLS-based multi-tenancy

# 3. Check for AI/ML features
grep -r "vector\|embedding\|semantic" app/ database/ | wc -l
# If high count → AI-powered features

# 4. Check for platform integrations
find app -name "*Connector.php" -o -name "*Platform*.php" | head -10
# Reveals integration strategy
```

---

## 🏗️ ARCHITECTURAL DISCOVERY

### Discovering Multi-Tenancy Implementation

**Step 1: Identify the Pattern**

```bash
# Check routes for org_id
grep -c "org_id" routes/api.php
# High count → Multi-tenant architecture

# Check middleware
ls -la app/Http/Middleware/*Context*.php
ls -la app/Http/Middleware/*Org*.php
```

**Step 2: Discover the Mechanism**

```sql
-- Find context functions
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    proname as function_name,
    prosrc as source_code
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'cmis'
  AND (proname LIKE '%context%' OR proname LIKE '%org%')
ORDER BY proname;
"
```

**Step 3: Trace the Flow**

```bash
# Find middleware that sets context
grep -A 20 "class.*Context.*Middleware" app/Http/Middleware/*.php

# Expected pattern:
# - Middleware extracts org_id from route
# - Middleware calls DB function to set context
# - All subsequent queries filtered by RLS
```

**For Complete Multi-Tenancy Patterns:**
→ See `.claude/knowledge/MULTI_TENANCY_PATTERNS.md`

### Discovering Database Architecture

**Schema Discovery:**

```sql
-- List all schemas
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    nspname as schema_name,
    nspowner::regrole as owner
FROM pg_namespace
WHERE nspname NOT LIKE 'pg_%'
  AND nspname != 'information_schema'
ORDER BY nspname;
"

-- Tables per schema
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    COUNT(*) as table_count
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
GROUP BY schemaname
ORDER BY table_count DESC;
"
```

**Pattern Inference:**

```bash
# If schemas include: cmis_marketing, cmis_analytics, cmis_ai_analytics
# → Domain-driven schema organization
# → Each schema represents a business domain

# If most tables have org_id column:
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    table_schema,
    table_name
FROM information_schema.columns
WHERE column_name = 'org_id'
  AND table_schema LIKE 'cmis%'
LIMIT 20;
"
# → Multi-tenant data model
```

### Discovering Technology Stack

**Backend Stack:**

```bash
# Framework and version
cat composer.json | jq '.require["laravel/framework"]'

# PHP version requirement
cat composer.json | jq '.require.php'

# Database driver
cat composer.json | jq '.require | with_entries(select(.key | contains("pgsql") or contains("postgres")))'

# Key packages
cat composer.json | jq '.require | keys[]' | grep -i "sanctum\|queue\|cache"
```

**Frontend Stack:**

```bash
# Frontend framework
cat package.json | jq '.dependencies | keys[]' | grep -i "alpine\|vue\|react\|svelte"

# CSS framework
cat package.json | jq '.dependencies | keys[]' | grep -i "tailwind\|bootstrap"

# Build tool
cat package.json | jq '.devDependencies | keys[]' | grep -i "vite\|webpack\|mix"

# Charts/visualization
cat package.json | jq '.dependencies | keys[]' | grep -i "chart\|graph\|viz"
```

**AI/ML Stack:**

```bash
# Check for vector extensions
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT * FROM pg_extension WHERE extname LIKE '%vector%';
"

# Check embedding services
grep -r "class.*Embedding" app/Services/
grep -r "Gemini\|OpenAI\|Anthropic" app/ config/

# Check vector dimensions
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    table_schema,
    table_name,
    column_name,
    data_type
FROM information_schema.columns
WHERE data_type LIKE '%vector%'
LIMIT 10;
"
```

---

## 📊 BUSINESS DOMAIN DISCOVERY

### Discovering Core Entities

**Model Discovery:**

```bash
# List all models
find app/Models -name "*.php" | wc -l

# Organize by directory (domain separation?)
find app/Models -type d

# Most important models (by size/complexity)
wc -l app/Models/**/*.php | sort -nr | head -20
```

**Relationship Discovery:**

```bash
# Find models with most relationships
grep -r "public function.*belongsTo\|hasMany\|belongsToMany" app/Models/ | cut -d: -f1 | sort | uniq -c | sort -nr | head -10

# Example: Discover Campaign relationships
grep "public function" app/Models/Core/Campaign.php | grep -v "__construct"
# Shows: what Campaign relates to
```

**Table Discovery:**

```sql
-- Find largest tables (business importance)
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 20;
"
```

### Discovering Business Workflows

**Route Analysis:**

```bash
# Campaign workflow
grep -A 2 "campaigns" routes/api.php | head -30
# Reveals: create, update, publish, sync operations

# Integration workflow
grep -A 2 "integration" routes/api.php | head -30
# Reveals: connect, sync, disconnect operations

# Social media workflow
grep -A 2 "social" routes/api.php | head -30
# Reveals: post, schedule, publish operations
```

**Job Discovery:**

```bash
# Find background jobs
find app/Jobs -name "*.php"

# Job naming reveals workflow
ls app/Jobs/ | grep -o "[A-Z][a-z]*" | sort | uniq -c | sort -nr
# Example: Sync*, Process*, Publish* → Main operations

# Check job queues
grep -r "onQueue" app/Jobs/ | grep -o "onQueue('[^']*')" | sort | uniq
# Reveals: priority and separation of concerns
```

---

## 🔑 PATTERN DISCOVERY

### Discovering the Repository Pattern

```bash
# Check for repositories
test -d app/Repositories && echo "Repository pattern: ACTIVE" || echo "Repository pattern: NOT USED"

# Count implementations
find app/Repositories -name "*Repository.php" | wc -l
find app/Repositories -name "*Interface.php" | wc -l

# Check service provider bindings
grep -A 5 "bind.*Repository" app/Providers/*.php
```

**For Complete Pattern Recognition:**
→ See `.claude/knowledge/PATTERN_RECOGNITION.md`

### Discovering the Service Layer

```bash
# Check for services
test -d app/Services && echo "Service layer: ACTIVE" || echo "Service layer: NOT USED"

# Service organization
ls -la app/Services/

# Service usage in controllers
grep -r "private.*Service" app/Http/Controllers/ | head -10
# Shows: dependency injection pattern
```

### Discovering Platform Integration Patterns

```bash
# Find integration connectors
find app -name "*Connector.php" -o -name "*Platform*.php"

# Check for factory pattern
grep -r "class.*Factory" app/

# Discover OAuth flow
grep -r "oauth\|callback\|token" routes/api.php

# Webhook handling
grep -r "webhook" routes/api.php
ls app/Http/Controllers/*Webhook*.php
```

---

## 🚀 API DISCOVERY

### Discovering Route Structure

```bash
# Total routes
php artisan route:list | wc -l

# API routes only
php artisan route:list | grep "api/" | wc -l

# Route patterns
php artisan route:list | grep "api/" | grep -o "api/[^/]*/[^/]*" | sort | uniq

# Multi-tenant pattern check
php artisan route:list | grep -c "org_id"
# High count → org-scoped architecture
```

### Discovering Middleware Chain

```bash
# List middleware
ls -la app/Http/Middleware/

# Check kernel middleware
cat app/Http/Kernel.php | grep -A 30 "protected \$middlewareGroups"

# Route middleware
grep "middleware(" routes/api.php | grep -o "middleware('[^']*')" | sort | uniq
```

### Discovering Rate Limits

```bash
# Find rate limiter configuration
grep -A 10 "RateLimiter::for" app/Providers/*.php

# Find throttle middleware usage
grep -r "throttle:" routes/ app/Http/
```

---

## 🎨 FRONTEND DISCOVERY

### Discovering Frontend Architecture

**View Structure:**

```bash
# Main layouts
ls -la resources/views/layouts/

# Component organization
find resources/views/components -name "*.blade.php" | wc -l

# Largest views (complexity)
wc -l resources/views/**/*.blade.php | sort -nr | head -10
```

**JavaScript Framework:**

```bash
# Check for Alpine.js
grep -r "x-data\|x-init\|x-show" resources/views/ | wc -l
# High count → Alpine.js used

# Check for Vue
grep -r "v-if\|v-for\|v-model" resources/views/ | wc -l

# Check for React
grep -r "useState\|useEffect" resources/js/ | wc -l
```

**Asset Build System:**

```bash
# Build configuration
cat vite.config.js || cat webpack.mix.js || cat webpack.config.js

# Scripts
cat package.json | jq '.scripts'
```

---

## 🔒 SECURITY PATTERN DISCOVERY

### Discovering Authentication

```bash
# Auth driver
cat config/auth.php | grep -A 5 "guards"

# API authentication
grep -r "auth:sanctum\|auth:api" routes/

# Token generation
grep -r "createToken\|personalAccessToken" app/
```

### Discovering Authorization

```bash
# Policies
ls -la app/Policies/

# Gates
grep -A 10 "Gate::define" app/Providers/AuthServiceProvider.php

# Permission checks in controllers
grep -r "authorize\|can(" app/Http/Controllers/ | head -10
```

### Discovering RLS Policies

```sql
-- All RLS policies
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename,
    policyname,
    cmd,
    qual
FROM pg_policies
WHERE schemaname LIKE 'cmis%'
ORDER BY tablename, cmd
LIMIT 20;
"

-- Tables WITH RLS enabled
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  AND rowsecurity = true
LIMIT 20;
"

-- Tables WITHOUT RLS (potential issues)
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  AND rowsecurity = false
ORDER BY tablename;
"
```

---

## 🧪 TEST DISCOVERY

### Discovering Test Organization

```bash
# Test structure
ls -la tests/

# Test counts by type
find tests/Feature -name "*Test.php" | wc -l
find tests/Unit -name "*Test.php" | wc -l

# Most tested components
find tests -name "*Test.php" -exec basename {} \; | sed 's/Test.php//' | sort | uniq -c | sort -nr | head -10
```

### Discovering Test Patterns

```bash
# Check for database traits
grep -r "RefreshDatabase\|DatabaseTransactions" tests/ | wc -l

# Check for factory usage
grep -r "::factory()" tests/ | wc -l

# Check for multi-tenant testing
grep -r "createOrganization\|org_id" tests/ | head -10
```

---

## 📚 DOCUMENTATION DISCOVERY

### Locating Documentation

```bash
# Find all markdown files
find . -name "*.md" -not -path "./node_modules/*" -not -path "./vendor/*" | wc -l

# Documentation categories
find . -name "*.md" -not -path "./node_modules/*" -not -path "./vendor/*" | grep -o "/[^/]*\.md" | sed 's|/||' | sort | uniq

# Key docs
ls -lh *.md | sort -k5 -hr | head -10
```

### Discovering Progress Status

```bash
# Look for progress indicators
grep -r "percent\|progress\|status" *.md | grep -i "complete\|done\|%"

# Look for roadmaps
ls *ROADMAP* *PROGRESS* *STATUS* 2>/dev/null

# Check git commits
git log --oneline -20
# Recent activity reveals current focus
```

---

## ⚡ PERFORMANCE & OPTIMIZATION DISCOVERY

### Discovering Caching Strategy

```bash
# Cache driver
cat config/cache.php | grep -A 5 "default"

# Cache usage in code
grep -r "Cache::get\|Cache::put\|Cache::remember" app/ | wc -l

# Model caching
grep -r "cacheable\|cache" app/Models/ | head -10
```

### Discovering Queue Configuration

```bash
# Queue driver
cat config/queue.php | grep -A 5 "default"

# Queue names
grep -r "onQueue" app/Jobs/ | grep -o "onQueue('[^']*')" | sort | uniq

# Failed job handling
grep -r "failed\|retry" app/Jobs/ | head -10
```

### Discovering Database Optimization

```sql
-- Indexes
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname LIKE 'cmis%'
ORDER BY tablename, indexname
LIMIT 20;
"

-- Table sizes (performance indicators)
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as total_size,
    pg_size_pretty(pg_relation_size(schemaname||'.'||tablename)) as table_size,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename) - pg_relation_size(schemaname||'.'||tablename)) as index_size
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 10;
"
```

---

## 🎯 WORKFLOW: UNDERSTANDING A NEW FEATURE

### Example: Understanding Campaign Management

**Step 1: Find the Model**

```bash
find app/Models -name "*Campaign*"
cat app/Models/Core/Campaign.php | head -50
```

**Step 2: Discover Routes**

```bash
grep -A 5 "campaigns" routes/api.php
```

**Step 3: Find Controller**

```bash
find app/Http/Controllers -name "*Campaign*"
cat app/Http/Controllers/API/CampaignController.php | grep "public function"
```

**Step 4: Check Database**

```sql
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
\d cmis.campaigns
"
```

**Step 5: Find Related Components**

```bash
# Service layer
ls app/Services/*Campaign*

# Repository
ls app/Repositories/*Campaign*

# Jobs
ls app/Jobs/*Campaign*

# Tests
ls tests/**/*Campaign*
```

**Step 6: Understand Relationships**

```bash
grep "public function" app/Models/Core/Campaign.php | grep "belongsTo\|hasMany\|morphMany"
```

---

## 🔍 WORKFLOW: DIAGNOSING AN ISSUE

### Example: Multi-Tenant Data Leak

**Step 1: Verify RLS Context**

```sql
-- Check if context is being set
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT cmis.get_current_user_id(), cmis.get_current_org_id();
"
```

**Step 2: Check Middleware**

```bash
# Find context middleware
grep -A 30 "class.*Context" app/Http/Middleware/*Context*.php

# Check if it's registered
cat app/Http/Kernel.php | grep -i context
```

**Step 3: Verify RLS Policies**

```sql
-- Check table has RLS enabled
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables
WHERE tablename = 'campaigns';
"

-- Check policies exist
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    policyname,
    cmd,
    qual
FROM pg_policies
WHERE tablename = 'campaigns';
"
```

**Step 4: Test Query**

```sql
-- Set context manually
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT cmis.init_transaction_context('<user_id>', '<org_id>');
SELECT * FROM cmis.campaigns LIMIT 5;
"
```

---

## 🚀 WORKFLOW: IMPLEMENTING A NEW FEATURE

### Example: Adding a New Multi-Tenant Resource

**Step 1: Discover Existing Pattern**

```bash
# Find similar resource
grep -l "org_id" app/Models/Core/*.php | head -1

# Study its implementation
# - Model traits
# - Migration structure
# - RLS policies
# - Controller pattern
# - Routes
```

**Step 2: Follow the Pattern**

```bash
# Create migration following discovered pattern
ls -lt database/migrations/*create*table.php | head -1
# Copy pattern: UUID, org_id, timestamps, RLS

# Create model following discovered pattern
cat app/Models/Core/Campaign.php
# Copy pattern: HasUuids, SoftDeletes, schema, relationships

# Create controller following discovered pattern
cat app/Http/Controllers/API/CampaignController.php
# Copy pattern: Constructor injection, thin methods, responses
```

**For Laravel Conventions:**
→ See `.claude/knowledge/LARAVEL_CONVENTIONS.md`

**Step 3: Verify RLS**

```sql
-- After migration, verify RLS is enabled
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT rowsecurity FROM pg_tables WHERE tablename = 'your_new_table';
"

-- Verify policies exist
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT COUNT(*) FROM pg_policies WHERE tablename = 'your_new_table';
"
# Should be 4 (SELECT, INSERT, UPDATE, DELETE)
```

**Step 4: Test Multi-Tenancy**

```php
// Test that RLS works
public function test_resource_respects_org_isolation()
{
    $org1 = Org::factory()->create();
    $org2 = Org::factory()->create();

    YourResource::factory()->create(['org_id' => $org1->id]);
    YourResource::factory()->create(['org_id' => $org2->id]);

    $user = User::factory()->create();
    $user->orgs()->attach($org1->id);

    $response = $this->actingAs($user)
        ->getJson("/api/orgs/{$org1->id}/your-resources");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data'); // Should see only org1's resource
}
```

---

## 📋 QUICK REFERENCE: DISCOVERY COMMANDS

### Project Overview

```bash
# What is this project?
cat composer.json | jq '.name, .description'
head -20 README.md

# What's the stack?
cat composer.json | jq '.require | keys[]' | head -10
cat package.json | jq '.dependencies | keys[]' | head -10

# How big is it?
find app -name "*.php" | wc -l
find resources/views -name "*.blade.php" | wc -l
```

### Architecture

```sql
-- Schemas
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT nspname FROM pg_namespace WHERE nspname LIKE 'cmis%';
"

-- Tables
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT schemaname, COUNT(*) FROM pg_tables WHERE schemaname LIKE 'cmis%' GROUP BY schemaname;
"

-- RLS policies
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT COUNT(*) FROM pg_policies WHERE schemaname LIKE 'cmis%';
"
```

### Code Patterns

```bash
# Repository pattern?
test -d app/Repositories && echo "YES" || echo "NO"

# Service layer?
test -d app/Services && echo "YES" || echo "NO"

# Event-driven?
test -d app/Events && test -d app/Listeners && echo "YES" || echo "NO"

# UUID or auto-increment?
grep -r "HasUuids" app/Models/ | wc -l
```

### Routes & APIs

```bash
# Total API routes
php artisan route:list | grep "api/" | wc -l

# Multi-tenant pattern
php artisan route:list | grep -c "org_id"

# Rate limits
grep -A 5 "RateLimiter::for" app/Providers/*.php
```

---

## 🎓 LEARNING RESOURCES

### Core Knowledge Files

1. **CMIS_DISCOVERY_GUIDE.md** - Meta-guide for discovering CMIS architecture
2. **MULTI_TENANCY_PATTERNS.md** - PostgreSQL RLS patterns
3. **PATTERN_RECOGNITION.md** - Architectural pattern identification
4. **LARAVEL_CONVENTIONS.md** - Project-specific Laravel conventions

### Supplementary Knowledge

- **CMIS_DATA_PATTERNS.md** - Data examples and seeding patterns
- **CMIS_REFERENCE_DATA.md** - Reference table structures
- **CMIS_SQL_INSIGHTS.md** - Database patterns and insights

### Specialized Agents

- **cmis-context-awareness.md** - RLS context specialist
- **cmis-orchestrator.md** - Multi-agent coordination
- **cmis-multi-tenancy.md** - Multi-tenancy specialist
- **cmis-campaign-expert.md** - Campaign management specialist
- **cmis-social-publishing.md** - Social media specialist
- **cmis-platform-integration.md** - Platform integration specialist
- **cmis-ui-frontend.md** - Frontend specialist
- **cmis-ai-semantic.md** - AI/ML specialist

---

## ⚠️ CRITICAL PRINCIPLES

### 1. Always Discover, Never Assume

```bash
# ❌ WRONG: Assume Laravel defaults
# "Models use auto-increment IDs"
# "Routes follow standard REST"

# ✅ RIGHT: Discover actual implementation
grep -r "incrementing" app/Models/ | head -1
php artisan route:list | grep "api/" | head -10
```

### 2. Context is King

```bash
# ❌ WRONG: Query without context
SELECT * FROM cmis.campaigns;

# ✅ RIGHT: Always set context first
SELECT cmis.init_transaction_context(user_id, org_id);
SELECT * FROM cmis.campaigns;
```

### 3. Follow Existing Patterns

```bash
# Before implementing anything:
# 1. Find similar existing feature
# 2. Study its implementation
# 3. Copy the pattern
# 4. Adapt to new use case
```

### 4. Verify Multi-Tenancy

```bash
# After any changes:
# 1. Check RLS is enabled
# 2. Verify policies exist
# 3. Test with multiple orgs
# 4. Ensure data isolation
```

---

## 📊 PROJECT STATUS DISCOVERY

### How to Check Completion Status

```bash
# Look for progress files
ls *PROGRESS* *STATUS* *COMPLETE* 2>/dev/null

# Check recent commits
git log --oneline -20

# Count implementations vs plans
find app -name "*.php" | wc -l
grep -r "TODO\|FIXME" app/ | wc -l
```

### How to Identify Current Phase

```bash
# Check roadmap
cat ROADMAP*.md 2>/dev/null | head -50

# Check recent work
git log --since="1 week ago" --oneline

# Check active branches
git branch -a
```

---

## 🎯 WHEN TO USE THIS GUIDE

### Use This Guide When:

✅ Starting work on CMIS for the first time
✅ Need to understand a specific feature
✅ Implementing a new feature
✅ Debugging multi-tenancy issues
✅ Need to verify patterns and conventions
✅ Want to understand project architecture
✅ Need to check project status

### Don't Use This Guide For:

❌ Quick reference (use other knowledge files)
❌ Specific SQL queries (use CMIS_SQL_INSIGHTS.md)
❌ Data patterns (use CMIS_DATA_PATTERNS.md)
❌ Laravel conventions (use LARAVEL_CONVENTIONS.md)
❌ Multi-tenancy specifics (use MULTI_TENANCY_PATTERNS.md)

---

**Version:** 2.0 - Discovery-Oriented Knowledge
**Framework:** META_COGNITIVE_FRAMEWORK
**Approach:** Discover > Verify > Apply

*"Understanding comes from discovery, not documentation."*
