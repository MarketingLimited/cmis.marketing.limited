# Knowledge System Commands Reference

**Last Updated:** 2025-11-24
**Version:** 1.0

Complete reference for all knowledge system commands in CMIS.

---

## 📚 Command Overview

| Command | Purpose | Output |
|---------|---------|--------|
| `docs:search` | Search documentation | Terminal output |
| `knowledge:generate-docs-index` | Generate docs index | DOCS_INDEX.md |
| `knowledge:generate-codebase-map` | Generate codebase map | CODEBASE_MAP.md |
| `knowledge:generate-schema-map` | Generate database schema map | DATABASE_SCHEMA_MAP.md |
| `knowledge:generate-model-graph` | Generate model relationship graph | MODEL_RELATIONSHIP_GRAPH.md |
| `knowledge:generate-service-map` | Generate service layer map | SERVICE_LAYER_MAP.md |
| `knowledge:refresh-all` | Refresh all knowledge maps | All 5 maps |
| `knowledge:health-check` | Verify knowledge health | Health report |

---

## 1. docs:search

**Purpose:** Quickly search all documentation for keywords.

**Syntax:**
```bash
php artisan docs:search <keyword> [options]
```

**Arguments:**
- `keyword` - The keyword to search for (required)

**Options:**
- `--context=N` - Number of context lines (default: 3)
- `--case-sensitive` - Enable case-sensitive search

**Examples:**
```bash
# Basic search
php artisan docs:search "campaign"

# More context
php artisan docs:search "performance" --context=5

# Case-sensitive
php artisan docs:search "Campaign" --case-sensitive
```

**Output Example:**
```
🔍 Searching documentation for: "campaign"

📄 reports/project-reality-check.md
   58:    - ✅ `CampaignPolicy.php` - مكتمل بمنطق حقيقي
   74: Route::middleware(['auth:sanctum', 'permission:cmis.campaigns.view'])
   75:     ->apiResource('campaigns', CampaignController::class);

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Found 24 matches across 6 files

📊 Top files:
   12 matches - docs/phases/completed/phase-2/campaign-management.md
   8 matches - docs/architecture/campaign-architecture.md
```

**Use Cases:**
- ✅ Before starting any new feature
- ✅ When encountering a bug
- ✅ When refactoring code
- ✅ To find past solutions

---

## 2. knowledge:generate-docs-index

**Purpose:** Generate comprehensive documentation index (PRIMARY SOURCE OF TRUTH).

**Syntax:**
```bash
php artisan knowledge:generate-docs-index
```

**Output File:** `.claude/knowledge/auto-generated/DOCS_INDEX.md`

**What It Generates:**
- Directory structure
- All documents list (with summaries)
- Documents by topic (Campaign, Analytics, etc.)
- Critical lessons learned (bugs, performance, architecture)
- Recommended reading by task type

**Example:**
```bash
php artisan knowledge:generate-docs-index

# Output:
# 🔍 Discovering documentation directory...
# ✅ Documentation index generated: .claude/knowledge/auto-generated/DOCS_INDEX.md
#    File size: 90.76 KB
#
# 💡 This file maps ALL documentation in /docs/
#    Agents consult this BEFORE starting any work.
```

**When to Run:**
- ✅ Daily (recommended)
- ✅ After adding new documentation
- ✅ After project milestones
- ✅ Before major refactoring

---

## 3. knowledge:generate-codebase-map

**Purpose:** Generate map of all codebase files and relationships.

**Syntax:**
```bash
php artisan knowledge:generate-codebase-map
```

**Output File:** `.claude/knowledge/auto-generated/CODEBASE_MAP.md`

**What It Generates:**
- All models (BaseModel adoption, traits)
- All controllers (ApiResponse usage)
- All services (dependencies)
- All repositories (model connections)
- All middleware
- All traits

**Example:**
```bash
php artisan knowledge:generate-codebase-map

# Output:
# 🔍 Discovering codebase structure...
# ✅ Codebase map generated: .claude/knowledge/auto-generated/CODEBASE_MAP.md
#    File size: 166.39 KB
```

**When to Run:**
- ✅ After adding new models/controllers/services
- ✅ After refactoring
- ✅ Daily (via scheduler)

---

## 4. knowledge:generate-schema-map

**Purpose:** Generate comprehensive database schema map.

**Syntax:**
```bash
php artisan knowledge:generate-schema-map
```

**Output File:** `.claude/knowledge/auto-generated/DATABASE_SCHEMA_MAP.md`

**What It Generates:**
- All schemas and tables
- Column definitions (types, defaults, nullability)
- Foreign key relationships
- RLS policy status
- Indexes
- Performance metrics

**Example:**
```bash
php artisan knowledge:generate-schema-map

# Output:
# 🔍 Discovering database schema...
# ✅ Schema map generated: .claude/knowledge/auto-generated/DATABASE_SCHEMA_MAP.md
#    File size: 364.02 KB
```

**When to Run:**
- ✅ After running migrations
- ✅ After schema changes
- ✅ Daily (via scheduler)

---

## 5. knowledge:generate-model-graph

**Purpose:** Generate visual model relationship graph.

**Syntax:**
```bash
php artisan knowledge:generate-model-graph
```

**Output File:** `.claude/knowledge/auto-generated/MODEL_RELATIONSHIP_GRAPH.md`

**What It Generates:**
- Model hierarchies by domain
- Relationship types (belongsTo, hasMany, etc.)
- Polymorphic relationships
- Multi-tenancy patterns
- Visual tree structures

**Example:**
```bash
php artisan knowledge:generate-model-graph

# Output:
# 🔍 Discovering model relationships...
# ✅ Model graph generated: .claude/knowledge/auto-generated/MODEL_RELATIONSHIP_GRAPH.md
#    File size: 57.99 KB
```

**When to Run:**
- ✅ After adding new models
- ✅ After changing relationships
- ✅ Daily (via scheduler)

---

## 6. knowledge:generate-service-map

**Purpose:** Generate service layer connection map.

**Syntax:**
```bash
php artisan knowledge:generate-service-map
```

**Output File:** `.claude/knowledge/auto-generated/SERVICE_LAYER_MAP.md`

**What It Generates:**
- Controller → Service → Repository → Model flows
- Dependency injection patterns
- Platform integration flows
- External API usage

**Example:**
```bash
php artisan knowledge:generate-service-map

# Output:
# 🔍 Discovering service layer connections...
# ✅ Service map generated: .claude/knowledge/auto-generated/SERVICE_LAYER_MAP.md
#    File size: 83.31 KB
```

**When to Run:**
- ✅ After adding new services/repositories
- ✅ After changing dependencies
- ✅ Daily (via scheduler)

---

## 7. knowledge:refresh-all

**Purpose:** Refresh ALL knowledge maps at once.

**Syntax:**
```bash
php artisan knowledge:refresh-all
```

**What It Does:**
Runs all 5 knowledge generation commands in order:
1. `knowledge:generate-docs-index` (PRIMARY SOURCE)
2. `knowledge:generate-codebase-map`
3. `knowledge:generate-schema-map`
4. `knowledge:generate-model-graph`
5. `knowledge:generate-service-map`

**Example:**
```bash
php artisan knowledge:refresh-all

# Output:
# 🔄 Refreshing all knowledge maps...
#
#    ✅ docs-index completed
#    ✅ codebase-map completed
#    ✅ schema-map completed
#    ✅ model-graph completed
#    ✅ service-map completed
#
# ✅ All knowledge maps refreshed successfully (3.15s)
#
# Generated files:
#    - CODEBASE_MAP.md (166.39 KB)
#    - DATABASE_SCHEMA_MAP.md (364.02 KB)
#    - DOCS_INDEX.md (90.76 KB)
#    - MODEL_RELATIONSHIP_GRAPH.md (57.99 KB)
#    - SERVICE_LAYER_MAP.md (83.31 KB)
```

**When to Run:**
- ✅ Daily (recommended - via scheduler)
- ✅ Before major releases
- ✅ After significant changes
- ✅ When knowledge seems stale

**Performance:**
- Average execution time: 3-5 seconds
- Generates ~750+ KB of knowledge

---

## 8. knowledge:health-check

**Purpose:** Verify health and freshness of all knowledge files.

**Syntax:**
```bash
php artisan knowledge:health-check
```

**What It Checks:**
- ✅ All required files exist
- ✅ Files have minimum size (not empty)
- ✅ Files are fresh (<24 hours old)
- ✅ Content structure is valid
- ✅ Coverage statistics

**Example:**
```bash
php artisan knowledge:health-check

# Output:
# 🏥 Running knowledge health check...
#
# ✅ DOCS_INDEX.md (90.76 KB)
#    ⏰ Fresh: 0 hours old
#
# ✅ CODEBASE_MAP.md (166.39 KB)
#    ⏰ Fresh: 0 hours old
#
# [... more files ...]
#
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 🎉 All knowledge files are healthy!
#
# 📊 Coverage Statistics:
#
#    Models in codebase: 300
#    Services in codebase: 194
#    Controllers in codebase: 199
#    Database tables: 327
```

**When to Run:**
- ✅ Daily (monitoring)
- ✅ Before deployments
- ✅ When agents seem to have outdated information
- ✅ After system changes

---

## 🔄 Automated Scheduling

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Full knowledge refresh daily at 2 AM
    $schedule->command('knowledge:refresh-all')
             ->dailyAt('02:00')
             ->appendOutputTo(storage_path('logs/knowledge-refresh.log'));

    // Health check every 6 hours
    $schedule->command('knowledge:health-check')
             ->everySixHours()
             ->appendOutputTo(storage_path('logs/knowledge-health.log'));
}
```

---

## 🎯 Quick Reference

### Daily Workflow
```bash
# Morning: Check knowledge health
php artisan knowledge:health-check

# If stale or after changes: Refresh
php artisan knowledge:refresh-all
```

### Before Starting Work
```bash
# 1. Search for relevant docs
php artisan docs:search "feature-name"

# 2. Read found documents
# 3. Check knowledge maps if needed
```

### After Making Changes
```bash
# Run refresh to update knowledge
php artisan knowledge:refresh-all

# Verify health
php artisan knowledge:health-check
```

---

## 📊 Command Performance

| Command | Avg Time | Output Size |
|---------|----------|-------------|
| docs:search | <1s | Terminal |
| generate-docs-index | ~0.5s | ~90 KB |
| generate-codebase-map | ~0.7s | ~170 KB |
| generate-schema-map | ~1.0s | ~365 KB |
| generate-model-graph | ~0.5s | ~60 KB |
| generate-service-map | ~0.5s | ~85 KB |
| **refresh-all** | **~3-5s** | **~770 KB** |
| health-check | <0.5s | Terminal |

---

## 🚨 Troubleshooting

### Issue: "Documentation directory not found"
**Solution:**
```bash
# Ensure docs/ directory exists
mkdir -p docs
```

### Issue: "Knowledge files are stale"
**Solution:**
```bash
# Force refresh
php artisan knowledge:refresh-all
```

### Issue: "Command fails with error"
**Solution:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check database connection (for schema-map)
php artisan db:show
```

---

## 📚 Related Documentation

- **Implementation Report:** `docs/active/reports/mandatory-docs-consultation-implementation.md`
- **Quick Start:** `DOCS_CONSULTATION_QUICKSTART.md`
- **Knowledge System:** `KNOWLEDGE_SYSTEM_QUICKSTART.md`
- **Agent Documentation:** `.claude/agents/cmis-knowledge-maintainer.md`

---

**Remember:** Always consult /docs/ BEFORE starting any work!
