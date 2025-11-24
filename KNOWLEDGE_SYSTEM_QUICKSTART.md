# Auto-Updating Knowledge System - Quick Start Guide

🎉 **Your auto-updating knowledge system is ready!**

This guide will get you started in 5 minutes.

---

## What Was Built?

A complete auto-updating knowledge system that ensures all Claude Code agents have accurate, current information about your CMIS codebase:

✅ **Auto-discovers** all files, models, services, and database schemas
✅ **Auto-updates** on every git commit (via hooks)
✅ **Daily refresh** ensures completeness
✅ **Health monitoring** validates knowledge accuracy

---

## Quick Start (3 Steps)

### Step 1: Install Git Hooks (2 minutes)

```bash
cd /home/cmis-test/public_html
bash .claude/hooks/install-hooks.sh
```

This installs a post-commit hook that auto-updates knowledge when you commit changes.

---

### Step 2: Generate Initial Knowledge (2 minutes)

```bash
php artisan knowledge:refresh-all
```

This generates all knowledge maps from your current codebase:
- Codebase map (models, controllers, services)
- Database schema map (tables, RLS policies, indexes)
- Model relationship graph (visual trees)
- Service layer map (Controller → Service → Repository flows)

---

### Step 3: Verify Everything Works (1 minute)

```bash
php artisan knowledge:health-check
```

You should see:
```
✅ CODEBASE_MAP.md (45.2 KB)
   ⏰ Fresh: 0.1 hours old

✅ DATABASE_SCHEMA_MAP.md (67.8 KB)
   ⏰ Fresh: 0.1 hours old

✅ MODEL_RELATIONSHIP_GRAPH.md (32.1 KB)
   ⏰ Fresh: 0.1 hours old

✅ SERVICE_LAYER_MAP.md (28.4 KB)
   ⏰ Fresh: 0.1 hours old

🎉 All knowledge files are healthy!
```

---

## How It Works

### Auto-Update on Commit

When you commit code changes, the system detects what changed and updates relevant knowledge:

```bash
# You commit a new model
git commit -m "Add CustomerSegment model"

# Hook automatically:
# 1. Detects app/Models/CustomerSegment.php changed
# 2. Runs: php artisan knowledge:generate-model-graph --quiet
# 3. Updates: MODEL_RELATIONSHIP_GRAPH.md
# 4. Auto-commits the updated knowledge

# Claude Code agents now see your new model!
```

| You Changed | System Updates |
|------------|----------------|
| `app/Models/*` | MODEL_RELATIONSHIP_GRAPH.md |
| `app/Services/*` | SERVICE_LAYER_MAP.md |
| `app/Http/Controllers/*` | CODEBASE_MAP.md |
| `database/migrations/*` | DATABASE_SCHEMA_MAP.md |
| `app/Repositories/*` | SERVICE_LAYER_MAP.md |

---

## Commands You'll Use

### Refresh All Knowledge
```bash
php artisan knowledge:refresh-all
```
Run this after major changes or periodically to ensure everything is current.

### Check Knowledge Health
```bash
php artisan knowledge:health-check
```
Verifies all knowledge files are present, fresh, and valid.

### Generate Specific Map
```bash
php artisan knowledge:generate-codebase-map
php artisan knowledge:generate-schema-map
php artisan knowledge:generate-model-graph
php artisan knowledge:generate-service-map
```
Use these for targeted updates.

---

## What Gets Generated?

All knowledge files are in: `.claude/knowledge/auto-generated/`

### 1. CODEBASE_MAP.md
Complete map of all code files:
- 244 models (BaseModel adoption, traits)
- 111 controllers (ApiResponse usage)
- 47 services (repository connections)
- All middleware and traits

### 2. DATABASE_SCHEMA_MAP.md
Complete database documentation:
- 12 schemas, 148+ tables
- All columns, types, defaults
- Foreign key relationships
- RLS policy status
- Indexes

### 3. MODEL_RELATIONSHIP_GRAPH.md
Visual model relationships:
```
Organization (parent)
├── Campaign
│   ├── ContentPlan (hasMany)
│   │   └── ContentItem (hasMany)
│   ├── Budget (hasMany)
│   └── Metrics (morphMany)
```

### 4. SERVICE_LAYER_MAP.md
Service layer flows:
```
Controller
  ↓ injects
Service
  ↓ uses
Repository
  ↓ queries
Model (via RLS)
  ↓ interacts with
Database
```

---

## How Agents Use This

When you ask questions, agents consult auto-generated knowledge:

**Example:**

**You:** "What relationships does the Campaign model have?"

**Agent Process:**
1. Opens `MODEL_RELATIONSHIP_GRAPH.md`
2. Finds Campaign section
3. Reads current relationships
4. Gives accurate answer (never guesses!)

**Result:** Agents always have current, accurate information.

---

## Optional: Daily Scheduled Refresh

For extra reliability, add daily refresh to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('knowledge:refresh-all')
             ->dailyAt('02:00')
             ->appendOutputTo(storage_path('logs/knowledge-refresh.log'));
}
```

This ensures knowledge is refreshed even if hooks miss something.

---

## Troubleshooting

### Knowledge not updating after commits?

```bash
# Check if hook is installed
ls -la .git/hooks/post-commit

# If missing, reinstall
bash .claude/hooks/install-hooks.sh
```

### Knowledge files are stale?

```bash
# Manual refresh
php artisan knowledge:refresh-all
```

### Need help?

```bash
# Check command help
php artisan knowledge:refresh-all --help
php artisan knowledge:health-check --help
```

---

## Where to Learn More

**Full Implementation Report:**
`docs/active/reports/knowledge-auto-update-system-implementation.md`

**Agent Documentation:**
`.claude/agents/cmis-knowledge-maintainer.md`

**Auto-Generated Knowledge Directory:**
`.claude/knowledge/auto-generated/README.md`

---

## 🎯 That's It!

You now have a fully automated knowledge system. As you develop:

1. ✅ **Commit code** as usual
2. ✅ **Hooks auto-update** knowledge
3. ✅ **Agents stay current** automatically

**No manual documentation updates needed!**

---

**Questions?**

Ask any Claude Code agent:
- "Show me the current model relationships"
- "What's in the database schema?"
- "Generate updated codebase map"
- "Check knowledge health"

The `cmis-knowledge-maintainer` agent handles all knowledge-related requests.

---

**Enjoy your auto-updating knowledge system! 🚀**
