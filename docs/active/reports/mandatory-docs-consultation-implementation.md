# Mandatory /docs/ Directory Consultation - Implementation Report

**Date:** 2025-11-24
**Implemented By:** Claude Code
**Priority:** 🚨 **CRITICAL** - PRIMARY SOURCE OF TRUTH
**Status:** ✅ Complete and Enforced

---

## 🎯 Executive Summary

Implemented **mandatory /docs/ directory consultation** as **Step 0** in the agent discovery process. ALL Claude Code agents MUST now search and read relevant documentation in `/docs/` BEFORE starting any implementation, debugging, planning, or analysis work.

**Critical Achievement:** Agents now make evidence-based decisions informed by:
- Past bug fixes and solutions
- Architectural decisions and reasoning
- Previous implementation patterns
- Known issues and workarounds
- Design goals and constraints

---

## 🚨 Why This is Critical

### The Problem (Before)

Agents would:
- ❌ Repeat previous mistakes
- ❌ Duplicate work already done
- ❌ Ignore architectural decisions
- ❌ Miss known issues and workarounds
- ❌ Work without historical context

### The Solution (After)

Agents now:
- ✅ Avoid repeating previous mistakes
- ✅ Reuse existing solutions and patterns
- ✅ Align with established design goals
- ✅ Make evidence-based decisions
- ✅ Build on prior work, not duplicate it

---

## 📦 What Was Implemented

### 1. Updated META_COGNITIVE_FRAMEWORK

**File:** `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md`

**Changes:**
- Added **Step 0: CONSULT /docs/ DIRECTORY (REQUIRED)** as the very first step
- Changed from "Five-Step" to "Six-Step Discovery Process"
- Added comprehensive guidance on what to read and why
- Included example showing Step 0 in action

**New Process:**
```
Step 0: CONSULT /docs/ DIRECTORY (REQUIRED)
  ↓
Step 1: IDENTIFY THE DOMAIN
  ↓
Step 2: CHOOSE DISCOVERY METHOD
  ↓
Step 3: EXECUTE DISCOVERY
  ↓
Step 4: ANALYZE RESULTS
  ↓
Step 5: APPLY UNDERSTANDING
```

---

### 2. Updated cmis-orchestrator Agent

**File:** `.claude/agents/cmis-orchestrator.md`

**Changes:**
- Added **"0. 🚨 MANDATORY: Consult /docs/ Directory FIRST"** section
- Placed BEFORE all other coordination steps
- Included search commands and examples
- Emphasized as PRIMARY SOURCE OF TRUTH

**Key Addition:**
```markdown
### 0. 🚨 MANDATORY: Consult /docs/ Directory FIRST
**PRIMARY SOURCE OF TRUTH**

**EVERY agent MUST search /docs/ before ANY work:**

[Search commands and examples]
```

---

### 3. Updated cmis-knowledge-maintainer Agent

**File:** `.claude/agents/cmis-knowledge-maintainer.md`

**Changes:**
- Added `/docs/` as the first item in "Core Mission"
- Designated as "PRIMARY SOURCE OF TRUTH"
- Added new auto-generated file: **DOCS_INDEX.md**
- Included documentation discovery in responsibilities

**New Auto-Generated File:**
```markdown
#### A. **DOCS_INDEX.md** (Documentation Directory Map) - PRIMARY SOURCE

Comprehensive index of ALL documentation including:
- Directory structure
- All documents list
- Documents by topic
- Critical lessons learned
- Recommended reading by task type
- Quick search commands
```

---

### 4. Created DiscoverDocsDirectory Job

**File:** `app/Jobs/Knowledge/DiscoverDocsDirectory.php`

**Purpose:** Auto-generates comprehensive documentation index from `/docs/` directory.

**Features:**
- Scans entire /docs/ directory
- Indexes all markdown files
- Groups documents by topic (Campaign, Analytics, Platform Integration, etc.)
- Extracts critical lessons learned (bugs, performance, architecture)
- Provides recommended reading by task type
- Generates search commands

**Output:** `.claude/knowledge/auto-generated/DOCS_INDEX.md`

---

### 5. Created docs:search Command

**File:** `app/Console/Commands/DocsSearch.php`

**Command:** `php artisan docs:search "keyword"`

**Features:**
- Quick documentation search from command line
- Supports case-insensitive search (default)
- Context lines (--context=3 by default)
- Highlighted keyword matches
- Top files by match count
- Match statistics

**Example Usage:**
```bash
# Search for campaign-related docs
php artisan docs:search "campaign"

# Case-sensitive search
php artisan docs:search "Campaign" --case-sensitive

# More context lines
php artisan docs:search "performance" --context=5
```

**Example Output:**
```
🔍 Searching documentation for: "campaign"

📄 docs/phases/completed/phase-2/campaign-management.md
   15: Campaign management includes content planning, budget allocation...
   42: The Campaign model uses BaseModel and HasOrganization traits...

📄 docs/architecture/campaign-architecture.md
   8: Campaign architecture follows the Repository pattern...
   23: Campaign validation changed in November 2024...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Found 24 matches across 6 files

📊 Top files:
   12 matches - docs/phases/completed/phase-2/campaign-management.md
   8 matches - docs/architecture/campaign-architecture.md
   4 matches - docs/active/plans/campaign-feature-plan.md
```

---

### 6. Created knowledge:generate-docs-index Command

**File:** `app/Console/Commands/GenerateDocsIndex.php`

**Command:** `php artisan knowledge:generate-docs-index`

**Purpose:** Generates comprehensive documentation index.

**Output:** `.claude/knowledge/auto-generated/DOCS_INDEX.md`

**Usage:**
```bash
# Generate docs index
php artisan knowledge:generate-docs-index

# Quiet mode (for scripts)
php artisan knowledge:generate-docs-index --quiet
```

---

### 7. Updated RefreshAllKnowledge Command

**File:** `app/Console/Commands/RefreshAllKnowledge.php`

**Changes:**
- Added `knowledge:generate-docs-index` as **FIRST** command
- Ensures docs index is always generated before other knowledge maps
- Reinforces docs/ as PRIMARY SOURCE OF TRUTH

**New Command Order:**
```php
$commands = [
    'knowledge:generate-docs-index',     // PRIMARY SOURCE - Always first!
    'knowledge:generate-codebase-map',
    'knowledge:generate-schema-map',
    'knowledge:generate-model-graph',
    'knowledge:generate-service-map',
];
```

---

### 8. Updated KnowledgeHealthCheck Command

**File:** `app/Console/Commands/KnowledgeHealthCheck.php`

**Changes:**
- Added `DOCS_INDEX.md` to required files list (first position)
- Added command mapping for docs-index
- Ensures docs index is checked during health verification

---

## 🔍 How It Works

### Agent Workflow (With Step 0)

**Example Task:** "Add new analytics feature"

#### Step 0: Consult /docs/ FIRST (MANDATORY)
```bash
# 1. Agent searches for relevant docs
php artisan docs:search "analytics"

# 2. Agent finds relevant documents
# - docs/phases/completed/phase-2/analytics-implementation.md
# - docs/architecture/unified-metrics.md
# - docs/active/analysis/analytics-performance-report.md

# 3. Agent reads ALL relevant documents

# 4. Agent learns from documentation:
# - Analytics uses unified_metrics table (don't create new table!)
# - Previous N+1 query issues (use eager loading)
# - Caching strategy already established
# - Real-time analytics requires queue jobs
```

#### Step 1-5: Continue with normal discovery process
Now informed by historical context and past decisions.

---

## 📊 Generated DOCS_INDEX.md Structure

The auto-generated docs index includes:

### 1. Directory Structure
```markdown
## 📚 Documentation Directory Structure

### `docs/active/`
- **Purpose:** Current work (plans, reports, analysis, progress)
- **Files:** 24 documents

**Subdirectories:**
- `plans/` (8 files)
- `reports/` (10 files)
- `analysis/` (6 files)

[... continues for all directories ...]
```

### 2. All Documents List
```markdown
## 📄 All Documentation Files

**Total Documents:** 127

### `docs/active/analysis/`

- **performance-audit-2024-11.md** (45.2 KB)
  - Path: `docs/active/analysis/performance-audit-2024-11.md`
  - Modified: 2024-11-18 10:30:00
  - Summary: *Performance Audit November 2024*

[... continues for all files ...]
```

### 3. Documents by Topic
```markdown
## 🔍 Documents by Topic

### Campaign

**Keywords:** campaign, content plan, budget

**Related Documents:**
- `docs/phases/completed/phase-2/campaign-management.md`
- `docs/architecture/campaign-architecture.md`
- `docs/active/plans/campaign-feature-plan.md`

[... continues for all topics ...]
```

### 4. Critical Lessons Learned
```markdown
## ⚠️ Critical Lessons Learned

### Past Bugs and Fixes

- **docs/phases/completed/bug-fixes/campaign-validation-fix.md:45**
  > Bug: Campaign validation failed for multi-line descriptions

- **docs/active/reports/weekly-progress-2024-11-18.md:127**
  > Fixed: N+1 query issue in campaign listing (added eager loading)

[... continues with extracted lessons ...]
```

### 5. Recommended Reading by Task Type
```markdown
## 🎯 Recommended Reading by Task Type

### Adding New Feature

**Must read before starting:**

- docs/architecture/*.md
- docs/guides/development/*.md
- Search docs/ for similar past features

[... continues for all task types ...]
```

### 6. Quick Search Commands
```markdown
## 🔍 Quick Search Commands

### Search for Keyword

```bash
# Search all docs for keyword
grep -r "keyword" docs/ --include="*.md"

[... continues with all search commands ...]
```

---

## 🎯 Benefits Achieved

### ✅ Evidence-Based Decisions
Agents now make decisions informed by:
- Historical context
- Past solutions
- Architectural decisions
- Known issues and workarounds

### ✅ No Repeated Mistakes
Agents learn from:
- Past bug fixes
- Performance issues solved
- Architecture changes
- Previous refactoring efforts

### ✅ Aligned with Project Goals
Agents understand:
- Design goals and constraints
- Strategic direction
- Established patterns
- Project history

### ✅ Faster, Better Work
Agents can:
- Reuse existing solutions
- Build on prior work
- Avoid architectural conflicts
- Make informed trade-offs

---

## 🚀 Usage Examples

### Example 1: Agent Starting New Feature

**Task:** "Add campaign scheduling feature"

**Step 0: Agent searches docs first**
```bash
php artisan docs:search "campaign"
php artisan docs:search "scheduling"
```

**Agent finds:**
- `docs/phases/completed/phase-2/campaign-management.md`
  - Learned: Campaign validation patterns
  - Learned: Repository structure
  - Learned: Multi-tenancy requirements

- `docs/architecture/campaign-architecture.md`
  - Learned: Campaign model relationships
  - Learned: Service layer patterns
  - Learned: Testing requirements

**Result:** Agent implements feature aligned with established patterns, avoiding past mistakes.

---

### Example 2: Agent Fixing Bug

**Task:** "Fix slow campaign listing"

**Step 0: Agent searches docs first**
```bash
php artisan docs:search "performance"
php artisan docs:search "campaign"
php artisan docs:search "slow"
```

**Agent finds:**
- `docs/active/analysis/performance-audit-2024-11.md`
  - Learned: N+1 query issues already identified
  - Learned: Solution: eager loading relationships
  - Learned: Caching strategy for campaign lists

- `docs/phases/completed/bug-fixes/campaign-n-plus-one-fix.md`
  - Learned: Exact solution implemented before
  - Learned: Code examples and patterns

**Result:** Agent applies known solution, doesn't reinvent wheel.

---

### Example 3: Agent Refactoring Code

**Task:** "Refactor campaign validation logic"

**Step 0: Agent searches docs first**
```bash
php artisan docs:search "campaign validation"
php artisan docs:search "refactor"
```

**Agent finds:**
- `docs/phases/completed/phase-2/campaign-refactor-2024.md`
  - Learned: Validation changed November 2024
  - Learned: New patterns to follow
  - Learned: Breaking changes to avoid

- `docs/architecture/validation-patterns.md`
  - Learned: Established validation patterns
  - Learned: FormRequest structure
  - Learned: Error handling conventions

**Result:** Agent refactors using current patterns, not outdated ones.

---

## 📚 Commands Summary

### Search Documentation
```bash
# Quick search
php artisan docs:search "keyword"

# With more context
php artisan docs:search "keyword" --context=5

# Case-sensitive
php artisan docs:search "Keyword" --case-sensitive
```

### Generate Documentation Index
```bash
# Generate docs index
php artisan knowledge:generate-docs-index

# Quiet mode
php artisan knowledge:generate-docs-index --quiet
```

### Refresh All Knowledge (includes docs)
```bash
# Refresh all (docs index generated FIRST)
php artisan knowledge:refresh-all
```

### Check Knowledge Health (includes docs)
```bash
# Health check (docs index is first required file)
php artisan knowledge:health-check
```

---

## 🎓 Best Practices for Agents

### DO ✅

1. **ALWAYS search /docs/ before ANY work**
   ```bash
   php artisan docs:search "relevant-keyword"
   ```

2. **Read ALL relevant documents found**
   - Don't just skim titles
   - Read full context
   - Note dates and versions

3. **Apply learned knowledge**
   - Use established patterns
   - Avoid past mistakes
   - Align with design goals

4. **Reference docs in recommendations**
   - "As documented in docs/architecture/..."
   - "Following pattern from docs/phases/completed/..."

### DON'T ❌

1. **Never skip Step 0**
   - Step 0 is MANDATORY
   - Always search docs first
   - No exceptions

2. **Don't assume knowledge is outdated**
   - Trust the docs
   - Docs are PRIMARY SOURCE OF TRUTH
   - Verify with code if uncertain

3. **Don't duplicate past solutions**
   - Check for existing implementations
   - Reuse established patterns
   - Build on prior work

---

## 🔧 Integration with Existing System

### Knowledge Hierarchy (Updated)

**Tier 0: Documentation Directory** ← **NEW!**
- `.claude/knowledge/auto-generated/DOCS_INDEX.md` (PRIMARY SOURCE)
- `docs/` directory (all documentation)

**Tier 1: Meta-Cognitive Framework**
- `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md`
- `.claude/knowledge/DISCOVERY_PROTOCOLS.md`

**Tier 2: Reference Knowledge**
- `.claude/knowledge/CMIS_PROJECT_KNOWLEDGE.md`
- `.claude/knowledge/MULTI_TENANCY_PATTERNS.md`
- `.claude/knowledge/CMIS_DATA_PATTERNS.md`

**Tier 3: Auto-Generated Knowledge**
- `.claude/knowledge/auto-generated/CODEBASE_MAP.md`
- `.claude/knowledge/auto-generated/DATABASE_SCHEMA_MAP.md`
- `.claude/knowledge/auto-generated/MODEL_RELATIONSHIP_GRAPH.md`
- `.claude/knowledge/auto-generated/SERVICE_LAYER_MAP.md`

---

## 📈 Expected Impact

### Short Term (Immediate)
- ✅ Agents make better-informed decisions
- ✅ Fewer repeated mistakes
- ✅ Better alignment with project goals

### Medium Term (1-2 months)
- ✅ Faster development (less rework)
- ✅ Higher quality code (learned from past)
- ✅ Better documentation awareness

### Long Term (3+ months)
- ✅ Institutional knowledge preserved
- ✅ New agents quickly learn project history
- ✅ Consistent patterns across codebase

---

## ✅ Implementation Checklist

- [x] Updated META_COGNITIVE_FRAMEWORK with Step 0
- [x] Updated cmis-orchestrator with mandatory docs consultation
- [x] Updated cmis-knowledge-maintainer to include docs discovery
- [x] Created DiscoverDocsDirectory job
- [x] Created docs:search command
- [x] Created knowledge:generate-docs-index command
- [x] Updated RefreshAllKnowledge to include docs index (first!)
- [x] Updated KnowledgeHealthCheck to verify docs index
- [x] Documented entire system

---

## 🚀 Getting Started

### Step 1: Generate Initial Documentation Index
```bash
php artisan knowledge:generate-docs-index
```

### Step 2: Verify Documentation Index Created
```bash
ls -lh .claude/knowledge/auto-generated/DOCS_INDEX.md
```

### Step 3: Try Searching Documentation
```bash
php artisan docs:search "campaign"
```

### Step 4: Include in Regular Knowledge Refresh
```bash
# Full refresh (docs index is now included)
php artisan knowledge:refresh-all
```

---

## 🎉 Conclusion

**ALL Claude Code agents now MUST consult /docs/ directory BEFORE starting any work.**

This ensures:
- ✅ Evidence-based decisions
- ✅ No repeated mistakes
- ✅ Aligned with project goals
- ✅ Faster, better work

**The /docs/ directory is now enforced as the PRIMARY SOURCE OF TRUTH for all agents.**

---

**Implementation Date:** 2025-11-24
**Status:** ✅ Complete and Enforced
**Priority:** 🚨 CRITICAL
**Next Step:** Generate docs index and start using docs:search command
