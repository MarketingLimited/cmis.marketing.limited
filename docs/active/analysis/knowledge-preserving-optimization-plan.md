# Knowledge-Preserving Agent Optimization Plan
**Date:** 2025-12-01
**Priority:** Preserve ALL agent knowledge and skills

---

## Core Principle

```
┌─────────────────────────────────────────────────────────────────┐
│  NEVER DELETE KNOWLEDGE - ONLY RESTRUCTURE & OPTIMIZE           │
└─────────────────────────────────────────────────────────────────┘
```

---

## Current State Analysis

### Agent Content Breakdown

| Component | % of Agent File | Unique? | Action |
|-----------|-----------------|---------|--------|
| YAML header | 5% | Yes | Keep |
| Core mission/skills | 15% | Yes (VALUABLE) | **PRESERVE** |
| Key patterns/code | 20% | Yes (VALUABLE) | **PRESERVE** |
| Rules/guidelines | 10% | Yes (VALUABLE) | **PRESERVE** |
| Browser testing section | 40% | No (DUPLICATED) | Move to shared |
| Docs/links | 10% | Yes | Keep |

### Problem Identified

The browser testing section (~40 lines) is **duplicated across 200+ agents**!
- 200 agents × 40 lines = **8,000 lines of duplication**

---

## Strategy 1: Remove Duplication Only (Safest)

### Step 1: Create Shared Browser Testing Include

Currently in EVERY agent:
```markdown
## 🌐 Browser Testing Integration (MANDATORY)
... (40 lines duplicated 200+ times)
```

**Solution:** Already exists at `.claude/agents/_shared/browser-testing-integration.md`

### Step 2: Replace Duplication with Reference

**Before (each agent has 40 duplicated lines):**
```markdown
## 🌐 Browser Testing Integration (MANDATORY)

**📖 Full Guide:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

### CMIS Test Suites
| Test Suite | Command | Use Case |
... (40 more lines)
```

**After (single reference):**
```markdown
## 🌐 Browser Testing
**See:** `.claude/agents/_shared/browser-testing-integration.md`
```

### Impact
- **Removes:** ~8,000 lines of duplication
- **Preserves:** 100% of unique knowledge
- **Files affected:** 200+ agents
- **Risk:** Zero (only removing duplicates)

### Implementation Script

```bash
#!/bin/bash
# Remove duplicated browser testing section from all agents
# Replace with single-line reference

for agent in .claude/agents/cmis-*.md .claude/agents/laravel-*.md; do
    # Check if file has the duplicated section
    if grep -q "Browser Testing Integration (MANDATORY)" "$agent"; then
        # Remove the duplicated section (from ## 🌐 to next --- or end)
        sed -i '/## 🌐 Browser Testing Integration (MANDATORY)/,/^---$/d' "$agent"

        # Add single-line reference before last ---
        sed -i '/^---$/i ## 🌐 Browser Testing\n**See:** `.claude/agents/_shared/browser-testing-integration.md`\n' "$agent"
    fi
done
```

---

## Strategy 2: Model Optimization (No Knowledge Loss)

### Understanding Model Impact

```
┌─────────────────────────────────────────────────────────────────┐
│ Model = Processing Power                                         │
│ Agent File = Knowledge/Skills (UNCHANGED)                        │
│                                                                   │
│ Changing model does NOT change the knowledge in the file!        │
│ It only changes HOW the knowledge is processed/reasoned about.   │
└─────────────────────────────────────────────────────────────────┘
```

### Model Selection Criteria

| Agent Type | Knowledge Complexity | Reasoning Needed | Model |
|------------|---------------------|------------------|-------|
| Orchestrator | High | Multi-agent coordination | opus |
| Architecture | High | Complex decisions | opus |
| Micro-specialist | Low | Pattern matching | sonnet/haiku |
| Simple lookups | Low | Direct answers | haiku |

### Safe Model Downgrade Categories

**Can safely use haiku (knowledge preserved, just simpler reasoning):**

| Category | Agents | Why Safe |
|----------|--------|----------|
| OAuth agents | cmis-oauth-* (6) | Simple OAuth flow patterns |
| Webhook agents | cmis-webhooks-* (3) | Signature verification patterns |
| Platform sub-features | cmis-google-*, cmis-meta-* | Specific API patterns |
| Attribution sub-types | cmis-attribution-* (6) | Specific formulas |
| Audience sub-types | cmis-audience-* (8) | Specific targeting rules |

**Should stay on opus (complex reasoning needed):**

| Agent | Why Opus |
|-------|----------|
| cmis-orchestrator | Multi-agent coordination |
| cmis-predictive-analytics | Statistical reasoning |
| laravel-architect | Architecture decisions |
| laravel-security | Security analysis |
| cmis-ab-testing-specialist | Statistical significance |

### Testing Before Downgrade

```bash
# Test agent with haiku before committing
claude --model haiku "Using agent cmis-google-bidding-tcpa, explain Target CPA setup"

# Compare output quality with opus
claude --model opus "Using agent cmis-google-bidding-tcpa, explain Target CPA setup"

# If haiku output is acceptable, proceed with downgrade
```

---

## Strategy 3: Knowledge Library Architecture (Enhanced)

### Create Platform-Specific Knowledge Libraries

Instead of 15+ Google sub-agents each with partial knowledge:

```
.claude/knowledge/platforms/
├── google/
│   ├── README.md                    # Overview
│   ├── bidding-strategies.md        # Target CPA, ROAS, Max Conv
│   ├── campaign-types.md            # Search, Display, PMax, Shopping
│   ├── targeting-options.md         # Keywords, Audiences, RLSA
│   ├── quality-score.md             # QS optimization
│   └── api-reference.md             # API patterns
├── meta/
│   ├── campaign-objectives.md
│   ├── audience-types.md
│   ├── pixel-setup.md
│   └── api-reference.md
└── ... (other platforms)
```

### How Agents Reference Knowledge

**Enhanced agent structure:**
```markdown
---
name: cmis-google-ads-specialist
model: sonnet
---

# Google Ads Specialist

## 🎯 CORE MISSION
Expert in all Google Ads features

## 📚 KNOWLEDGE REFERENCES
- **Bidding:** `.claude/knowledge/platforms/google/bidding-strategies.md`
- **Campaigns:** `.claude/knowledge/platforms/google/campaign-types.md`
- **Targeting:** `.claude/knowledge/platforms/google/targeting-options.md`
- **Quality:** `.claude/knowledge/platforms/google/quality-score.md`

## 🔄 SUB-SPECIALISTS (for deep dives)
When user needs specific deep expertise, delegate to:
- cmis-google-bidding-tcpa (Target CPA expert)
- cmis-google-bidding-troas (Target ROAS expert)
- cmis-google-campaigns-pmax (Performance Max expert)

[Unique orchestration logic for Google Ads domain]
```

### Benefits
- **100% knowledge preserved** - nothing deleted
- **Better organization** - knowledge in logical structure
- **Reduced duplication** - shared knowledge files
- **Maintained specialization** - sub-agents still exist for deep dives

---

## Strategy 4: New Commands & Tools (Addition Only)

### New Commands (No Changes to Existing)

These ADD functionality without touching existing agents:

| Command | Purpose | Implementation |
|---------|---------|----------------|
| `/lint` | Code quality | New file |
| `/coverage` | Test coverage | New file |
| `/health` | System health | New file |
| `/i18n-audit` | i18n compliance | New file |
| `/fresh` | Fresh migrate | New file |
| `/perf` | Performance | New file |

### Command Template
```markdown
---
description: [Clear description]
---

[Detailed instructions that ADD capability without changing existing setup]
```

---

## Implementation Plan (Knowledge-Preserving)

### Phase 1: Remove Duplication Only (Week 1)
**Risk: ZERO - Only removing duplicates**

- [ ] Backup `.claude/agents/` directory
- [ ] Remove duplicated browser testing section from all agents
- [ ] Add single-line reference to shared file
- [ ] Verify all agents still work
- [ ] **Knowledge preserved: 100%**

### Phase 2: Safe Model Optimization (Week 2)
**Risk: LOW - Testing before each change**

- [ ] Test haiku with 5 simple agents
- [ ] If output quality acceptable, proceed
- [ ] Downgrade OAuth, webhook, simple lookup agents to haiku
- [ ] Keep complex reasoning agents on opus
- [ ] **Knowledge preserved: 100%**

### Phase 3: Knowledge Library Creation (Week 3)
**Risk: ZERO - Only adding files**

- [ ] Create `.claude/knowledge/platforms/` structure
- [ ] Extract unique knowledge from agents into library files
- [ ] Add references in main specialist agents
- [ ] Keep all sub-agents intact (no deletion)
- [ ] **Knowledge preserved: 100%**

### Phase 4: New Commands (Week 4)
**Risk: ZERO - Only adding new files**

- [ ] Create new command files
- [ ] Test each command
- [ ] Document in commands/README.md
- [ ] **Existing setup: Unchanged**

---

## What We Will NOT Do

❌ **Delete any agent files**
❌ **Remove unique knowledge from agents**
❌ **Merge agents without preserving all content**
❌ **Change agent logic or behavior**
❌ **Remove specialized capabilities**

---

## What We WILL Do

✅ **Remove duplicated boilerplate** (8,000+ lines of browser testing duplicates)
✅ **Optimize model selection** (after testing shows acceptable quality)
✅ **Create organized knowledge library** (addition, not replacement)
✅ **Add new commands** (pure additions)
✅ **Improve documentation** (additions only)

---

## Validation Checklist

Before any change:
- [ ] Agent file backed up
- [ ] Unique knowledge identified and preserved
- [ ] Test agent works after change
- [ ] Compare output quality (before/after)

After all changes:
- [ ] All 230 agents still exist
- [ ] All unique knowledge preserved
- [ ] Model changes tested and verified
- [ ] New commands working
- [ ] Documentation updated

---

## Cost Savings Estimate (Conservative)

Even with knowledge-preserving approach:

| Change | Savings |
|--------|---------|
| Downgrade 50 simple agents to haiku | ~30% |
| Downgrade 30 medium agents to sonnet | ~15% |
| Remove duplication (less tokens) | ~5% |
| **Total estimated savings** | **~50%** |

---

## Summary

```
┌─────────────────────────────────────────────────────────────────┐
│ APPROACH: Preserve-First Optimization                            │
│                                                                   │
│ ✅ All 230 agents remain                                         │
│ ✅ All unique knowledge preserved                                │
│ ✅ Remove only duplicates                                        │
│ ✅ Model changes after testing                                   │
│ ✅ Add capabilities (commands, knowledge library)                │
│ ❌ No deletion of specialized agents                             │
│ ❌ No merging that loses information                             │
└─────────────────────────────────────────────────────────────────┘
```

---

**Created:** 2025-12-01
**Philosophy:** Preserve ALL knowledge, optimize structure only
