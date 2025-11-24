# /docs/ Directory Consultation - Quick Start

🚨 **CRITICAL:** ALL agents MUST now search `/docs/` BEFORE any work!

---

## What Changed?

**Step 0 (NEW & MANDATORY):** Consult /docs/ directory FIRST

ALL Claude Code agents must now:
1. Search `/docs/` for relevant documentation
2. Read ALL relevant documents found
3. Apply learned knowledge to their work

---

## Quick Start (2 Steps)

### Step 1: Generate Documentation Index
```bash
php artisan knowledge:generate-docs-index
```

This creates `.claude/knowledge/auto-generated/DOCS_INDEX.md` with:
- All documentation indexed by topic
- Critical lessons learned
- Recommended reading by task type
- Quick search commands

### Step 2: Search Documentation
```bash
# Search for any keyword
php artisan docs:search "campaign"
php artisan docs:search "analytics"
php artisan docs:search "performance"
```

---

## Why This Matters

### Before (❌)
Agents would:
- Repeat previous mistakes
- Duplicate work
- Ignore architectural decisions
- Miss known issues

### After (✅)
Agents now:
- Learn from past bugs
- Reuse existing solutions
- Align with design goals
- Make evidence-based decisions

---

## Commands You'll Use

### Search Documentation
```bash
# Quick search
php artisan docs:search "keyword"

# More context lines
php artisan docs:search "keyword" --context=5

# Case-sensitive
php artisan docs:search "Keyword" --case-sensitive
```

### Generate/Refresh Documentation Index
```bash
# Generate docs index
php artisan knowledge:generate-docs-index

# Refresh all knowledge (includes docs)
php artisan knowledge:refresh-all
```

### Check Documentation Health
```bash
# Health check (docs index is first required file)
php artisan knowledge:health-check
```

---

## Example Usage

**Task:** "Add new campaign feature"

**Step 0 (MANDATORY):**
```bash
# Search for campaign docs
php artisan docs:search "campaign"

# Found relevant docs:
# - docs/phases/completed/phase-2/campaign-management.md
# - docs/architecture/campaign-architecture.md
# - docs/active/plans/campaign-feature-plan.md

# Read ALL found documents
# Learn from past implementations
# Apply learned patterns
```

**Then proceed with Steps 1-5** (informed by documentation)

---

## What Gets Indexed?

The auto-generated `DOCS_INDEX.md` includes:

1. **Directory Structure** - All docs/ subdirectories
2. **All Documents** - Complete file list with summaries
3. **Documents by Topic** - Campaign, Analytics, Platform, etc.
4. **Critical Lessons** - Past bugs, performance issues, architecture changes
5. **Recommended Reading** - By task type (feature, bug fix, optimization)
6. **Search Commands** - Quick search examples

---

## Integration with Existing System

**Updated Knowledge Hierarchy:**

**Tier 0: /docs/ Directory** ← **NEW!** (PRIMARY SOURCE)
- Auto-indexed and searchable
- Contains historical context
- Past bugs, solutions, decisions

**Tier 1-3:** Existing knowledge tiers
- Meta-Cognitive Framework
- Reference knowledge
- Auto-generated code maps

---

## Where to Learn More

**Full Implementation Report:**
`docs/active/reports/mandatory-docs-consultation-implementation.md`

**Updated Framework:**
`.claude/knowledge/META_COGNITIVE_FRAMEWORK.md` (see Step 0)

**Orchestrator Agent:**
`.claude/agents/cmis-orchestrator.md` (see "Step 0: Consult /docs/")

---

## 🎯 Remember

**ALL agents MUST:**
- ✅ Search /docs/ BEFORE any work (Step 0)
- ✅ Read ALL relevant documents found
- ✅ Apply learned knowledge
- ✅ Reference docs in recommendations

**This is now ENFORCED as PRIMARY SOURCE OF TRUTH.**

---

**Implementation Date:** 2025-11-24
**Status:** ✅ Active and Enforced

**Try it now:** `php artisan docs:search "your-keyword"`
