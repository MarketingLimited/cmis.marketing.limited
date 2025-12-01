# Agent Model Selection Guide
**Version:** 1.0
**Last Updated:** 2025-12-01
**Purpose:** Guidelines for selecting appropriate Claude models for CMIS agents

---

## Quick Decision Tree

```
┌─────────────────────────────────────────────────────────────────┐
│                    MODEL SELECTION DECISION                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Does the agent require complex multi-step reasoning?            │
│    │                                                             │
│    ├─ YES → Does it involve architecture/security/ML?            │
│    │         │                                                   │
│    │         ├─ YES → Use OPUS                                   │
│    │         └─ NO  → Use SONNET                                 │
│    │                                                             │
│    └─ NO  → Does it follow clear patterns/templates?             │
│              │                                                   │
│              ├─ YES → Use SONNET                                 │
│              └─ NO  → Use HAIKU                                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Model Comparison

| Model | Cost | Speed | Best For |
|-------|------|-------|----------|
| **Opus 4.5** | $75/M tokens | Slower | Complex reasoning, planning, architecture |
| **Sonnet 4.5** | $15/M tokens | Balanced | Multi-step coding, feature development |
| **Haiku 4.5** | $1/M tokens | Fastest | Simple lookups, pattern matching |

---

## Tier Classification

### Tier 1: OPUS - Complex Reasoning Agents

**Characteristics:**
- Reference META_COGNITIVE_FRAMEWORK
- Use DISCOVERY_PROTOCOLS
- Require multi-agent coordination
- Involve statistical/ML reasoning
- Make architectural decisions
- Have 700+ unique content lines

**Examples:**
```
cmis-orchestrator           - Multi-agent coordination
cmis-predictive-analytics   - ML/statistical reasoning
cmis-platform-integration   - Complex OAuth flows
laravel-architect           - Architecture decisions
laravel-security            - Security analysis
cmis-ai-semantic            - AI/ML integration
cmis-analytics-expert       - Complex analytics
laravel-db-architect        - Database architecture
```

**Why Opus?**
- These agents need to "think" through complex problems
- They must synthesize information from multiple sources
- Decisions have significant architectural impact
- Errors could cause security or data integrity issues

---

### Tier 2: SONNET - Multi-Step Task Agents

**Characteristics:**
- Have clear patterns to follow
- Contain multiple code examples
- 100-700 unique content lines
- Don't require complex reasoning
- Execute well-defined workflows

**Examples:**
```
cmis-oauth-meta             - OAuth implementation patterns
cmis-meta-pixel-setup       - Pixel configuration steps
cmis-anomaly-detection      - Detection rule application
laravel-testing             - Test writing patterns
laravel-documentation       - Documentation generation
cmis-creative-optimization  - Optimization rules
cmis-budget-pacing          - Budget calculation rules
```

**Why Sonnet?**
- Patterns are clear, just need to apply them
- Good balance of capability and cost
- Fast enough for iterative development
- Handles multi-file operations well

---

### Tier 3: HAIKU - Simple Lookup Agents

**Characteristics:**
- Return factual information
- Have simple rules/lists
- 19-100 unique content lines
- Pattern matching only
- No complex reasoning needed

**Examples:**
```
cmis-linkedin-targeting-company      (19 lines unique)
cmis-google-campaigns-video          (21 lines unique)
cmis-tiktok-shopping-ads             (21 lines unique)
cmis-google-quality-score            (28 lines unique)
cmis-attribution-last-click          (33 lines unique)
cmis-oauth-google                    (OAuth pattern only)
cmis-webhooks-meta                   (Webhook pattern only)
```

**Why Haiku?**
- Information is factual, not reasoning
- Agent just needs to return documented patterns
- Speed is beneficial for quick lookups
- Cost savings are significant (75x cheaper than Opus)

---

## Category Guidelines

### Platform Sub-Agents → HAIKU

All specific platform feature agents:
```
cmis-google-bidding-*       - Bidding strategy patterns
cmis-google-campaigns-*     - Campaign type configs
cmis-google-targeting-*     - Targeting option lists
cmis-meta-creatives-*       - Creative format specs
cmis-meta-audiences-*       - Audience type definitions
cmis-tiktok-*               - TikTok feature specs
cmis-linkedin-*             - LinkedIn feature specs
cmis-twitter-*              - Twitter feature specs
cmis-snapchat-*             - Snapchat feature specs
```

### OAuth Agents → HAIKU

Standard OAuth flow patterns:
```
cmis-oauth-google
cmis-oauth-meta
cmis-oauth-tiktok
cmis-oauth-linkedin
cmis-oauth-twitter
cmis-oauth-snapchat
```

### Webhook Agents → HAIKU

Standard webhook handling:
```
cmis-webhooks-meta
cmis-webhooks-google
cmis-webhooks-verification
```

### Attribution Agents → HAIKU

Attribution model definitions:
```
cmis-attribution-last-click
cmis-attribution-linear
cmis-attribution-data-driven
cmis-attribution-multi-touch (SONNET - more complex)
```

### Utility Agents → HAIKU

Simple feature definitions:
```
cmis-audit-logging
cmis-notes-annotations
cmis-brand-safety
cmis-compliance-gdpr
cmis-compliance-ccpa
cmis-export-import
cmis-bulk-operations
```

### Platform Specialists → OPUS

Full platform expertise:
```
cmis-google-ads-specialist
cmis-meta-ads-specialist
cmis-tiktok-ads-specialist
cmis-linkedin-ads-specialist
cmis-twitter-ads-specialist
cmis-snapchat-ads-specialist
```

### Laravel Core → OPUS

Architectural decisions:
```
laravel-architect
laravel-security
laravel-db-architect
laravel-tech-lead
```

### Laravel Development → SONNET

Implementation work:
```
laravel-testing
laravel-documentation
laravel-code-quality
laravel-performance
laravel-devops
laravel-api-design
laravel-refactor-specialist
```

---

## Agent File Template

### For OPUS Agents
```yaml
---
name: agent-name
description: |
  Complex description with multiple capabilities.
  References META_COGNITIVE_FRAMEWORK.
  Handles architectural decisions.
model: opus
---
```

### For SONNET Agents
```yaml
---
name: agent-name
description: |
  Clear description of multi-step task handling.
  Follows established patterns.
model: sonnet
---
```

### For HAIKU Agents
```yaml
---
name: agent-name
description: Simple feature/lookup specialist.
model: haiku
---
```

---

## Cost Impact Analysis

### Current State (All Opus)
```
227 agents × opus ($75/M) = Maximum cost
```

### Optimized State
```
Tier 1: 39 agents × opus ($75/M)   = 17% of agents, ~50% of complex work
Tier 2: 62 agents × sonnet ($15/M) = 27% of agents, ~35% of work
Tier 3: 126 agents × haiku ($1/M)  = 56% of agents, ~15% of work

Estimated savings: 60-65%
```

---

## Migration Checklist

When downgrading an agent's model:

1. **Verify agent type:**
   - [ ] Does it use META_COGNITIVE_FRAMEWORK? → Keep opus
   - [ ] Does it use DISCOVERY_PROTOCOLS? → Keep opus
   - [ ] Does it have <100 unique lines? → Can use haiku
   - [ ] Does it follow clear patterns? → Can use sonnet

2. **Test before mass change:**
   - [ ] Test 5 agents of same type
   - [ ] Compare output quality
   - [ ] Check for reasoning failures
   - [ ] Verify all patterns still work

3. **Apply change:**
   ```bash
   sed -i 's/model: opus/model: haiku/' ".claude/agents/agent-name.md"
   ```

4. **Validate:**
   - [ ] Agent loads correctly
   - [ ] Key functions work
   - [ ] Output quality acceptable

---

## Exceptions

### Always Keep on OPUS
- Any agent doing security analysis
- Any agent making architectural decisions
- Any agent coordinating other agents
- Any agent doing ML/statistical reasoning
- Any agent with 1000+ unique content lines

### Consider SONNET Over HAIKU
- If agent has code examples to reason about
- If agent needs to modify multiple files
- If agent has decision trees
- If agent handles error cases

---

## Related Documentation

- **Agent Directory:** `.claude/agents/README.md`
- **Shared Infrastructure:** `.claude/agents/_shared/`
- **Project Guidelines:** `CLAUDE.md`
- **Comprehensive Analysis:** `docs/active/analysis/COMPREHENSIVE-CLAUDE-CODE-OPTIMIZATION-ANALYSIS.md`

---

**Last Updated:** 2025-12-01
**Maintained By:** CMIS AI Agent Development Team
