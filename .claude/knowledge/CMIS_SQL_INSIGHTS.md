# CMIS Database Discovery Guide
**Version:** 2.1
**Last Updated:** 2025-11-27
**Purpose:** Learning database structure through SQL analysis and discovery commands
**Prerequisites:** Read `.claude/knowledge/DISCOVERY_PROTOCOLS.md` for discovery methodology
**Framework:** META_COGNITIVE_FRAMEWORK v2.0

---

## ⚠️ IMPORTANT: Environment Configuration

**ALWAYS read from `.env` for database credentials. NEVER use hardcoded values.**

### Quick .env Extraction

```bash
# Read database configuration
cat .env | grep DB_

# Extract for use in commands
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Use in PostgreSQL commands
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE"
```

**Note:** Database name varies by environment (e.g., `cmis-test`, `cmis-prod`). Always read from `.env`.

---

## 📑 Table of Contents

1. [Philosophy: Discover Database Patterns](#-philosophy-discover-database-patterns)
2. [Discovering RLS Policies](#-discovering-rls-policies)
3. [Discovering Constraints & Enums](#-discovering-constraints--enums)
4. [Discovering Foreign Key Relationships](#-discovering-foreign-key-relationships)
5. [Discovering AI/ML Features](#-discovering-aiml-features)
6. [Discovering Bilingual Support](#-discovering-bilingual-support)
7. [Discovering Soft Delete Patterns](#-discovering-soft-delete-patterns)
8. [Discovering Schema Organization](#-discovering-schema-organization)
9. [Discovering Indexes](#-discovering-indexes)
10. [Practical Discovery Workflows](#-practical-discovery-workflows)
11. [Discovery Commands Cheat Sheet](#-discovery-commands-cheat-sheet)
12. [Critical Patterns](#-critical-patterns)
13. [Key Takeaways](#-key-takeaways)

---

## 🎓 PHILOSOPHY: DISCOVER DATABASE PATTERNS

**Not:** "There are 27 RLS policies"
**But:** "How do I discover RLS policies?"

**Not:** "Here are all the constraints"
**But:** "How do I find constraints in the database?"

**Not:** "This is the schema structure"
**But:** "How do I explore schema organization?"

---

## 🔍 DISCOVERING RLS POLICIES

### How to Find RLS Policies

```bash
# Count total RLS policies
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT COUNT(*) as total_policies
      FROM pg_policies
      WHERE schemaname LIKE 'cmis%';"

# List all RLS policies
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT schemaname, tablename, policyname, cmd as command, qual as using_expression
      FROM pg_policies
      WHERE schemaname LIKE 'cmis%'
      ORDER BY tablename, cmd;"

# Policies for specific table
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT policyname, cmd, qual
      FROM pg_policies
      WHERE tablename = 'campaigns'
      ORDER BY cmd;"
```

### Pattern Recognition: RLS Policy Structure

**Discovery:** When you query policies, you'll find this pattern:

```sql
-- Standard 4-policy coverage
FOR SELECT: org_id = get_current_org_id() AND check_permission(user, org, 'table.view')
FOR INSERT: org_id = get_current_org_id() AND check_permission(user, org, 'table.create')
FOR UPDATE: org_id = get_current_org_id() AND check_permission(user, org, 'table.edit')
FOR DELETE: org_id = get_current_org_id() AND check_permission(user, org, 'table.delete')
```

**Two-Layer Security Pattern:**
1. **Organization filter:** `org_id = cmis.get_current_org_id()`
2. **Permission check:** `cmis.check_permission(user_id, org_id, 'permission_code')`

### Discovering RLS Helper Functions

```sql
-- Find RLS-related functions
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    proname as function_name,
    pg_get_function_arguments(p.oid) as arguments,
    pg_get_functiondef(p.oid) as definition
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'cmis'
  AND (
    proname LIKE '%context%'
    OR proname LIKE '%permission%'
    OR proname LIKE '%org%'
    OR proname LIKE '%user%'
  )
ORDER BY proname;
"
```

**Functions You'll Discover:**
- `cmis.get_current_org_id()` - Get org from transaction context
- `cmis.get_current_user_id()` - Get user from transaction context
- `cmis.check_permission(user_id, org_id, permission_code)` - Check permissions
- `cmis.init_transaction_context(user_id, org_id)` - Set context

---

## 📊 DISCOVERING CONSTRAINTS & ENUMS

### How to Find CHECK Constraints

```sql
-- List all constraints on a table
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    conname as constraint_name,
    pg_get_constraintdef(oid) as constraint_definition
FROM pg_constraint
WHERE conrelid = 'cmis.campaigns'::regclass
ORDER BY conname;
"

-- Find enum constraints (status fields, etc.)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    table_name,
    constraint_name,
    check_clause
FROM information_schema.check_constraints cc
JOIN information_schema.constraint_table_usage ctu
  ON cc.constraint_name = ctu.constraint_name
WHERE ctu.table_schema = 'cmis'
  AND check_clause LIKE '%ANY%ARRAY%'
ORDER BY table_name;
"
```

### Pattern Recognition: Status Enum Discovery

When you find a constraint like this:

```sql
CHECK (status = ANY (ARRAY[
    'draft'::text,
    'active'::text,
    'paused'::text,
    'completed'::text,
    'archived'::text
]))
```

**Pattern:** Fixed enum values enforced at database level

**Usage Pattern:**

```php
// Define constants in model
const STATUS_DRAFT = 'draft';
const STATUS_ACTIVE = 'active';
const STATUS_PAUSED = 'paused';
const STATUS_COMPLETED = 'completed';
const STATUS_ARCHIVED = 'archived';

// Use constants
$campaign->status = Campaign::STATUS_ACTIVE;
```

### Discovering PostgreSQL Enums

```sql
-- List all enum types
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    t.typname as enum_name,
    array_agg(e.enumlabel ORDER BY e.enumsortorder) as allowed_values
FROM pg_type t
JOIN pg_enum e ON t.oid = e.enumtypid
JOIN pg_namespace n ON t.typnamespace = n.oid
WHERE n.nspname = 'cmis'
GROUP BY t.typname
ORDER BY t.typname;
"
```

---

## 🔗 DISCOVERING FOREIGN KEY RELATIONSHIPS

### How to Find Foreign Keys

```sql
-- All foreign keys in cmis schema
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name,
    rc.delete_rule,
    rc.update_rule
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
  AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage AS ccu
  ON ccu.constraint_name = tc.constraint_name
  AND ccu.table_schema = tc.table_schema
JOIN information_schema.referential_constraints AS rc
  ON tc.constraint_name = rc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_schema = 'cmis'
ORDER BY tc.table_name, kcu.column_name;
"

-- Foreign keys for specific table
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    tc.constraint_name,
    kcu.column_name,
    ccu.table_name AS references_table,
    ccu.column_name AS references_column,
    rc.delete_rule
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
  ON ccu.constraint_name = tc.constraint_name
JOIN information_schema.referential_constraints AS rc
  ON tc.constraint_name = rc.constraint_name
WHERE tc.table_name = 'campaigns'
  AND tc.constraint_type = 'FOREIGN KEY'
ORDER BY kcu.column_name;
"
```

### Pattern Recognition: Cascade Behaviors

**Discovery:** When you analyze foreign keys, you'll find three patterns:

**1. CASCADE** - Dependent data deleted together:
```
campaigns.org_id → orgs.org_id (ON DELETE CASCADE)
→ Deleting org deletes all campaigns
```

**2. SET NULL** - Relationship broken, data preserved:
```
content_plans.campaign_id → campaigns.campaign_id (ON DELETE SET NULL)
→ Deleting campaign keeps plan, sets campaign_id = NULL
```

**3. RESTRICT** - Prevents deletion (default):
```
Most relationships use this to prevent accidental data loss
```

### Discovering Relationship Patterns

```sql
-- Tables that reference a given table
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT DISTINCT
    tc.table_name as dependent_table,
    kcu.column_name as foreign_key_column
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
  ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND ccu.table_name = 'campaigns'
  AND tc.table_schema = 'cmis'
ORDER BY tc.table_name;
"
```

---

## 🤖 DISCOVERING AI/ML FEATURES

### How to Find Vector Columns

```sql
-- Find all vector columns
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    table_schema,
    table_name,
    column_name,
    data_type,
    udt_name
FROM information_schema.columns
WHERE table_schema LIKE 'cmis%'
  AND udt_name = 'vector'
ORDER BY table_name, column_name;
"

-- Get vector dimensions
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
\d+ cmis_knowledge.embeddings_cache
"
# Look for vector(768) or similar
```

### Pattern Recognition: Embeddings Cache

When you query the embeddings cache table, you'll discover:

```sql
\d cmis_knowledge.embeddings_cache

-- Key columns you'll find:
source_table     - Which table the embedding is for
source_id        - ID of the record
source_field     - Which field was embedded
embedding        - vector(768) - The actual embedding
embedding_norm   - Pre-calculated norm for optimization
input_hash       - MD5 hash for cache lookup
quality_score    - Quality rating (0.00 to 1.00)
usage_count      - How often this embedding is used
model_version    - Which AI model generated it
```

**Pattern:** Caching strategy with quality tracking

**Usage Pattern:**

```php
// 1. Hash input
$hash = md5($text);

// 2. Check cache
$cached = EmbeddingsCache::where('input_hash', $hash)->first();

// 3. If miss, generate and store
if (!$cached) {
    $embedding = generateEmbedding($text);
    EmbeddingsCache::create([
        'input_hash' => $hash,
        'embedding' => $embedding,
        'quality_score' => calculateQuality($embedding),
        'usage_count' => 0,
    ]);
}

// 4. Increment usage
$cached->increment('usage_count');
```

### Discovering AI-Related Tables

```bash
# Find AI/ML related tables
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT tablename
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  AND (
    tablename LIKE '%embed%'
    OR tablename LIKE '%ai_%'
    OR tablename LIKE '%semantic%'
    OR tablename LIKE '%intent%'
    OR tablename LIKE '%generated%'
  )
ORDER BY schemaname, tablename;
"
```

---

## 🌍 DISCOVERING BILINGUAL SUPPORT

### How to Find Bilingual Columns

```sql
-- Find tables with Arabic (_ar) columns
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    table_name,
    column_name
FROM information_schema.columns
WHERE table_schema LIKE 'cmis%'
  AND column_name LIKE '%_ar'
ORDER BY table_name, column_name;
"

-- Find paired bilingual columns
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    table_name,
    array_agg(column_name ORDER BY column_name) as bilingual_columns
FROM information_schema.columns
WHERE table_schema = 'cmis_knowledge'
  AND (column_name LIKE '%_ar' OR column_name IN (
    SELECT REPLACE(column_name, '_ar', '')
    FROM information_schema.columns
    WHERE column_name LIKE '%_ar'
  ))
GROUP BY table_name
HAVING COUNT(*) > 1
ORDER BY table_name;
"
```

### Pattern Recognition: Bilingual Tables

**Discovery:** When you query knowledge tables, you'll find pairs:

```
intent_name      (English)
intent_name_ar   (Arabic)

related_keywords      (English array)
related_keywords_ar   (Arabic array)
```

**Pattern:** Parallel columns for English and Arabic

---

## 🔍 DISCOVERING SOFT DELETE PATTERNS

### How to Find Soft Delete Columns

```sql
-- Tables with soft delete
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    table_name,
    column_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name IN ('deleted_at', 'deleted_by')
GROUP BY table_name, column_name
ORDER BY table_name, column_name;
"

-- Tables with both deleted_at AND deleted_by (advanced soft delete)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    table_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name = 'deleted_at'
INTERSECT
SELECT
    table_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name = 'deleted_by'
ORDER BY table_name;
"
```

### Pattern Recognition: Enhanced Soft Delete

**Standard Soft Delete:**
```sql
deleted_at timestamp
```

**Enhanced Soft Delete (CMIS Pattern):**
```sql
deleted_at timestamp
deleted_by uuid  -- Tracks WHO deleted
```

**Usage Pattern:**

```php
// Wrong: Standard soft delete
$model->delete(); // Only sets deleted_at

// Right: Track deletor
$model->update([
    'deleted_at' => now(),
    'deleted_by' => auth()->id()
]);

// Or in model:
protected static function boot()
{
    parent::boot();

    static::deleting(function ($model) {
        $model->deleted_by = auth()->id();
        $model->save();
    });
}
```

---

## 📊 DISCOVERING SCHEMA ORGANIZATION

### How to Explore Schema Structure

```sql
-- List all schemas
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    nspname as schema_name,
    nspowner::regrole as owner
FROM pg_namespace
WHERE nspname LIKE 'cmis%'
  OR nspname IN ('archive', 'lab', 'operations')
ORDER BY nspname;
"

-- Table count per schema
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    schemaname,
    COUNT(*) as table_count
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  OR schemaname IN ('archive', 'lab', 'operations')
GROUP BY schemaname
ORDER BY table_count DESC;
"

-- Total database size
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    schemaname,
    pg_size_pretty(SUM(pg_total_relation_size(schemaname||'.'||tablename))) as schema_size
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
GROUP BY schemaname
ORDER BY SUM(pg_total_relation_size(schemaname||'.'||tablename)) DESC;
"
```

### Pattern Recognition: Domain-Driven Schemas

**Discovery:** When you list schemas, you'll see domain separation:

```
cmis              → Core entities (campaigns, orgs, users)
cmis_marketing    → Marketing features (briefs, content plans)
cmis_knowledge    → AI and knowledge management
cmis_analytics    → Performance analytics
cmis_ai_analytics → AI-powered analytics
cmis_audit        → Audit and compliance
cmis_ops          → Operations and ETL
cmis_security     → Security and permissions
cmis_system_health→ Monitoring
archive           → Historical data
lab               → Experimental features
```

**Pattern:** Schema per business domain

---

## 🔧 DISCOVERING INDEXES

### How to Find Indexes

```sql
-- List all indexes
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'cmis'
ORDER BY tablename, indexname;
"

-- Find vector indexes (for similarity search)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname LIKE 'cmis%'
  AND indexdef LIKE '%vector%'
ORDER BY tablename;
"

-- Missing indexes (tables without indexes on foreign keys)
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    t.schemaname,
    t.tablename,
    kcu.column_name as foreign_key_without_index
FROM information_schema.key_column_usage kcu
JOIN pg_tables t ON t.tablename = kcu.table_name
LEFT JOIN pg_indexes i ON i.tablename = kcu.table_name
  AND i.indexdef LIKE '%' || kcu.column_name || '%'
WHERE kcu.table_schema = 'cmis'
  AND t.schemaname = 'cmis'
  AND i.indexname IS NULL
ORDER BY t.tablename, kcu.column_name;
"
```

---

## 🎯 PRACTICAL DISCOVERY WORKFLOWS

### Workflow 1: Understanding a New Table

**Step 1: Table Structure**
```sql
\d cmis.table_name
```

**Step 2: Foreign Keys**
```sql
SELECT * FROM information_schema.table_constraints
WHERE table_name = 'table_name'
  AND constraint_type = 'FOREIGN KEY';
```

**Step 3: Constraints**
```sql
SELECT * FROM information_schema.check_constraints cc
JOIN information_schema.constraint_table_usage ctu
  ON cc.constraint_name = ctu.constraint_name
WHERE ctu.table_name = 'table_name';
```

**Step 4: Indexes**
```sql
SELECT * FROM pg_indexes
WHERE tablename = 'table_name';
```

**Step 5: RLS Policies**
```sql
SELECT * FROM pg_policies
WHERE tablename = 'table_name';
```

**Step 6: Sample Data**
```sql
SELECT * FROM cmis.table_name LIMIT 5;
```

### Workflow 2: Debugging RLS Issues

**Step 1: Check if RLS is enabled**
```sql
SELECT rowsecurity FROM pg_tables
WHERE tablename = 'campaigns';
```

**Step 2: List policies**
```sql
SELECT policyname, cmd, qual
FROM pg_policies
WHERE tablename = 'campaigns';
```

**Step 3: Check current context**
```sql
SELECT cmis.get_current_user_id(), cmis.get_current_org_id();
```

**Step 4: Test policy manually**
```sql
-- Set context
SELECT cmis.init_transaction_context('user_id', 'org_id');

-- Try query
SELECT * FROM cmis.campaigns LIMIT 1;
```

### Workflow 3: Discovering Performance Issues

**Step 1: Largest tables**
```sql
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables
WHERE schemaname = 'cmis'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 10;
```

**Step 2: Missing indexes**
```bash
# Run the missing indexes query from above
```

**Step 3: Slow queries (if enabled)**
```sql
SELECT
    query,
    calls,
    total_time,
    mean_time
FROM pg_stat_statements
WHERE query LIKE '%cmis%'
ORDER BY mean_time DESC
LIMIT 10;
```

---

## 📋 DISCOVERY COMMANDS CHEAT SHEET

```sql
-- RLS Policies
SELECT COUNT(*) FROM pg_policies WHERE schemaname LIKE 'cmis%';

-- Constraints
\d cmis.table_name

-- Foreign Keys
\d+ cmis.table_name

-- Enums
SELECT typname, enumlabel FROM pg_enum JOIN pg_type ON enumtypid = oid;

-- Vector Columns
SELECT table_name, column_name FROM information_schema.columns WHERE udt_name = 'vector';

-- Soft Delete Tables
SELECT table_name FROM information_schema.columns WHERE column_name = 'deleted_at' GROUP BY table_name;

-- Bilingual Columns
SELECT table_name, column_name FROM information_schema.columns WHERE column_name LIKE '%_ar';

-- Schema Statistics
SELECT schemaname, COUNT(*) FROM pg_tables WHERE schemaname LIKE 'cmis%' GROUP BY schemaname;

-- Indexes
SELECT tablename, indexname FROM pg_indexes WHERE schemaname = 'cmis';
```

---

## ⚠️ CRITICAL PATTERNS

### Pattern 1: Two-Layer Security (RLS + Permissions)

```php
// ❌ WRONG: Manual filtering
Campaign::where('org_id', $orgId)->get();

// ✅ RIGHT: RLS handles it
DB::statement('SELECT cmis.init_transaction_context(?, ?)', [auth()->id(), $orgId]);
Campaign::get(); // Automatically filtered
```

### Pattern 2: Three Context Types

```php
// ❌ WRONG: Single context
$campaign->context_id = $contextId;

// ✅ RIGHT: Three context types
$campaign->context_id = $baseContextId;      // Base
$campaign->creative_id = $creativeContextId; // Creative
$campaign->value_id = $valueContextId;       // Value
```

### Pattern 3: Enhanced Soft Delete

```php
// ❌ WRONG: Standard soft delete
$model->delete();

// ✅ RIGHT: Track deletor
$model->update([
    'deleted_at' => now(),
    'deleted_by' => auth()->id()
]);
```

### Pattern 4: Status Enum Enforcement

```php
// ❌ WRONG: Magic strings
$campaign->status = 'Active';

// ✅ RIGHT: Constants matching DB constraint
$campaign->status = Campaign::STATUS_ACTIVE; // 'active'
```

---

## 🎓 KEY TAKEAWAYS

1. **Use SQL discovery commands** - Don't assume structure
2. **RLS is two-layer** - Org + permissions
3. **Check constraints** - Enum values enforced at DB level
4. **Foreign key cascades matter** - Understand delete behavior
5. **Soft deletes track deletor** - `deleted_by` field
6. **Bilingual by design** - Arabic + English columns
7. **Vector embeddings** - AI/ML features throughout
8. **Schema organization** - Domain-driven design
9. **Quality scoring** - Track AI output quality
10. **Usage tracking** - Optimize popular operations

---

## 🔍 Quick Reference

| I Need To... | Discovery Command | Section |
|--------------|-------------------|---------|
| Count RLS policies | `SELECT COUNT(*) FROM pg_policies WHERE schemaname LIKE 'cmis%'` (use .env) | Discovering RLS Policies |
| Find foreign keys | `\d+ cmis.table_name` or query `information_schema.table_constraints` | Discovering Foreign Keys |
| List vector columns | `SELECT * FROM information_schema.columns WHERE udt_name = 'vector'` | Discovering AI/ML Features |
| Find soft delete tables | `SELECT table_name FROM information_schema.columns WHERE column_name = 'deleted_at'` | Discovering Soft Delete |
| Check RLS context | `SELECT cmis.get_current_user_id(), cmis.get_current_org_id();` | Workflow: Debugging RLS |
| Find missing indexes | Query foreign keys without indexes | Discovering Indexes |
| List all schemas | `SELECT nspname FROM pg_namespace WHERE nspname LIKE 'cmis%'` (use .env) | Discovering Schema Organization |
| Find bilingual columns | `SELECT * FROM information_schema.columns WHERE column_name LIKE '%_ar'` | Discovering Bilingual Support |

---

## 📚 Related Knowledge

**Prerequisites:**
- **DISCOVERY_PROTOCOLS.md** - Discovery methodology and executable commands
- **META_COGNITIVE_FRAMEWORK.md** - Adaptive intelligence principles

**Related Files:**
- **MULTI_TENANCY_PATTERNS.md** - Deep dive into RLS implementation patterns
- **CMIS_DATA_PATTERNS.md** - Data structure and modeling patterns
- **CMIS_DISCOVERY_GUIDE.md** - General discovery methodology for codebase
- **PATTERN_RECOGNITION.md** - Architectural and code patterns
- **CMIS_REFERENCE_DATA.md** - Quick reference for schemas and tables

**See Also:**
- **CLAUDE.md** - Main project guidelines with environment configuration
- **CMIS_PROJECT_KNOWLEDGE.md** - Core project architecture overview

---

**Last Updated:** 2025-11-27
**Version:** 2.1
**Maintained By:** CMIS AI Agent Development Team
**Framework:** META_COGNITIVE_FRAMEWORK
**Philosophy:** Discover Structure, Don't Memorize Facts

*"Database structure reveals system architecture. Use discovery commands, not assumptions."*
