# Claude Code Optimization Action Plan
**Date:** 2025-12-01
**Analysis Type:** Comprehensive Agent, Knowledge & Commands Optimization
**Project:** CMIS Platform

---

## Executive Summary

This analysis identifies significant opportunities to optimize Claude Code agents for:
- **Cost reduction** (estimated 60-70% savings via model optimization)
- **Faster development** (new commands & streamlined workflows)
- **Higher quality** (consolidated expertise, reduced redundancy)
- **Better maintainability** (fewer agents, clearer responsibilities)

---

## Current State Analysis

### Agents
| Metric | Current Value | Issue |
|--------|---------------|-------|
| Total Agents | 230 files | Excessive - many redundant |
| Opus Model Usage | 223 agents (100%) | Very expensive |
| Haiku Model Usage | 0 agents | No cost optimization |
| Small Agents (<2.5KB) | 100+ agents | Should use haiku |
| Large Agents (>30KB) | 10 agents | May need splitting |

### Knowledge Base
| Metric | Current Value | Status |
|--------|---------------|--------|
| Total Files | 20 files | Adequate |
| Optimized (v2.1) | 11 files | Good (55%) |
| With Quick Reference | 11 files | Good |
| Cross-Referenced | 11 files | Good |

### Commands
| Metric | Current Value | Issue |
|--------|---------------|-------|
| Total Commands | 5 commands | Too few |
| Database Commands | 2 | Adequate |
| Testing Commands | 1 | Need more |
| Code Quality Commands | 0 | Missing |
| Deployment Commands | 0 | Missing |

---

## Priority 1: Cost Optimization (CRITICAL)

### 1.1 Model Tier Strategy

**Problem:** All 223 agents use opus model (~$75/million input tokens)

**Solution:** Implement 3-tier model strategy:

| Tier | Model | Cost | Use For | Target Count |
|------|-------|------|---------|--------------|
| **T1** | opus | High | Complex reasoning, architecture | 15-20 agents |
| **T2** | sonnet | Medium | Multi-step tasks, code review | 30-40 agents |
| **T3** | haiku | Low | Simple lookups, routing, docs | 100+ agents |

**Estimated Savings: 60-70%**

### 1.2 Agents for Opus (Keep)
```
# Complex reasoning agents - keep opus
cmis-orchestrator            # Multi-agent coordination
cmis-predictive-analytics    # ML/statistical reasoning
laravel-architect            # Architecture decisions
laravel-security             # Security analysis
cmis-ab-testing-specialist   # Statistical analysis
cmis-compliance-security     # Compliance reasoning
app-feasibility-researcher   # Complex analysis
laravel-tech-lead            # Code review
cmis-analytics-expert        # Analytics reasoning
cmis-ai-semantic             # AI/ML integration
laravel-db-architect         # Database architecture
laravel-refactor-specialist  # Refactoring decisions
cmis-model-architect         # Model architecture
cmis-data-consolidation      # Data architecture
cmis-experimentation         # Experiment design
```

### 1.3 Agents for Sonnet (Downgrade)
```
# Multi-step but less complex - downgrade to sonnet
laravel-testing              # Test writing
laravel-code-quality         # Code quality
laravel-performance          # Performance
cmis-campaign-expert         # Campaign domain
cmis-platform-integration    # Platform integration
cmis-multi-tenancy           # Multi-tenancy
cmis-content-manager         # Content management
cmis-enterprise-features     # Enterprise features
cmis-marketing-automation    # Automation
cmis-crm-specialist          # CRM features
cmis-rbac-specialist         # Authorization
cmis-context-awareness       # Project context
cmis-ui-frontend             # UI/Frontend
laravel-documentation        # Documentation
laravel-devops               # DevOps
laravel-api-design           # API design
laravel-auditor              # Auditing
cmis-alerts-monitoring       # Alerts
cmis-anomaly-detection       # Anomaly detection
# Platform specialists
cmis-meta-ads-specialist
cmis-google-ads-specialist
cmis-tiktok-ads-specialist
cmis-linkedin-ads-specialist
cmis-twitter-ads-specialist
cmis-snapchat-ads-specialist
cmis-ad-campaign-analyst
cmis-social-publishing
```

### 1.4 Agents for Haiku (Downgrade - Major Savings)
```
# Simple, focused agents - downgrade to haiku (100+ agents)
# All attribution agents
cmis-attribution-*           # 6 agents
# All audience insights agents
cmis-audience-insights-*     # 8 agents
# All audiences agents
cmis-audiences-*             # 6 agents
# All budgets agents
cmis-budgets-*               # 3 agents
# All campaigns sub-agents
cmis-campaigns-*             # 4 agents
# All Google sub-agents
cmis-google-*                # 15+ agents
# All Meta sub-agents
cmis-meta-*                  # 15+ agents
# All TikTok sub-agents
cmis-tiktok-*                # 8+ agents
# All LinkedIn sub-agents
cmis-linkedin-*              # 6+ agents
# All Twitter sub-agents
cmis-twitter-*               # 5+ agents
# All Snapchat sub-agents
cmis-snapchat-*              # 6+ agents
# All OAuth agents
cmis-oauth-*                 # 6 agents
# All webhooks agents
cmis-webhooks-*              # 3 agents
# Simple feature agents
cmis-audit-logging
cmis-notes-annotations
cmis-tagging-taxonomy
cmis-templates-*
cmis-social-*
cmis-content-*
cmis-auto-*
cmis-bulk-operations
cmis-export-import
cmis-brand-*
cmis-compliance-*
cmis-custom-events
# etc.
```

### 1.5 Implementation Script

Create `scripts/optimize-agent-models.sh`:
```bash
#!/bin/bash
# Optimize agent models for cost reduction

# Downgrade to haiku
for agent in .claude/agents/cmis-attribution-*.md \
             .claude/agents/cmis-audience-*.md \
             .claude/agents/cmis-audiences-*.md \
             .claude/agents/cmis-budgets-*.md \
             .claude/agents/cmis-campaigns-*.md \
             .claude/agents/cmis-google-*.md \
             .claude/agents/cmis-meta-*.md \
             .claude/agents/cmis-tiktok-*.md \
             .claude/agents/cmis-linkedin-*.md \
             .claude/agents/cmis-twitter-*.md \
             .claude/agents/cmis-snapchat-*.md \
             .claude/agents/cmis-oauth-*.md \
             .claude/agents/cmis-webhooks-*.md; do
    sed -i 's/model: opus/model: haiku/' "$agent"
done

# Downgrade to sonnet
for agent in laravel-testing laravel-code-quality cmis-campaign-expert; do
    sed -i 's/model: opus/model: sonnet/' ".claude/agents/${agent}.md"
done
```

---

## Priority 2: Agent Consolidation (HIGH)

### 2.1 Consolidation Opportunities

**Problem:** 230 agents with significant overlap and redundancy

**Current State:**
- 6 attribution agents (should be 1)
- 8 audience insights agents (should be 1-2)
- 15+ Google sub-agents (should be 1-2)
- 15+ Meta sub-agents (should be 1-2)
- Similar patterns for other platforms

**Recommendation:** Consolidate to ~50-60 focused agents

### 2.2 Consolidation Map

| Current Agents | Consolidated To | Reason |
|----------------|-----------------|--------|
| cmis-attribution-* (6) | cmis-attribution-specialist | Single attribution expert |
| cmis-audience-insights-* (8) | cmis-audience-insights | Single insights expert |
| cmis-audiences-* (6) | cmis-audiences-specialist | Single audience expert |
| cmis-google-* (15+) | cmis-google-ads-specialist | Already exists, enhance it |
| cmis-meta-* (15+) | cmis-meta-ads-specialist | Already exists, enhance it |
| cmis-tiktok-* (8+) | cmis-tiktok-ads-specialist | Already exists, enhance it |
| cmis-oauth-* (6) | cmis-platform-integration | Already handles OAuth |
| cmis-webhooks-* (3) | cmis-platform-integration | Already handles webhooks |
| cmis-budgets-* (3) | cmis-campaign-expert | Budget is part of campaigns |
| cmis-templates-* (2) | cmis-content-manager | Templates are content |

### 2.3 Recommended Agent Structure (~55 agents)

**Core Agents (10):**
1. cmis-orchestrator - Master coordinator
2. cmis-context-awareness - Project knowledge
3. cmis-multi-tenancy - RLS specialist
4. cmis-campaign-expert - Campaign domain (includes budgets)
5. cmis-platform-integration - All platforms OAuth/webhooks
6. cmis-ai-semantic - AI/ML features
7. cmis-analytics-expert - All analytics
8. cmis-content-manager - All content/templates
9. cmis-compliance-security - Security & compliance
10. cmis-ui-frontend - Frontend specialist

**Platform Specialists (6):**
11. cmis-meta-ads-specialist - Meta (consolidated)
12. cmis-google-ads-specialist - Google (consolidated)
13. cmis-tiktok-ads-specialist - TikTok (consolidated)
14. cmis-linkedin-ads-specialist - LinkedIn (consolidated)
15. cmis-twitter-ads-specialist - Twitter (consolidated)
16. cmis-snapchat-ads-specialist - Snapchat (consolidated)

**Advanced Features (10):**
17. cmis-attribution-specialist - All attribution models
18. cmis-audiences-specialist - All audience features
19. cmis-experimentation - A/B testing
20. cmis-predictive-analytics - ML forecasting
21. cmis-alerts-monitoring - All alerts
22. cmis-marketing-automation - Workflows
23. cmis-crm-specialist - CRM integration
24. cmis-social-publishing - Social features
25. cmis-enterprise-features - Enterprise features
26. cmis-rbac-specialist - Authorization

**Laravel Specialists (12):**
27. laravel-architect
28. laravel-tech-lead
29. laravel-db-architect
30. laravel-testing
31. laravel-code-quality
32. laravel-security
33. laravel-performance
34. laravel-devops
35. laravel-api-design
36. laravel-documentation
37. laravel-auditor
38. laravel-refactor-specialist

**Specialized (7):**
39. cmis-model-architect
40. cmis-data-consolidation
41. laravel-controller-standardization
42. cmis-trait-specialist
43. app-feasibility-researcher
44. cmis-doc-organizer
45. cmis-ad-campaign-analyst

**Utility Agents (10):**
- Archive remaining micro-agents to `.claude/agents/_archive/`

---

## Priority 3: New Commands (HIGH VALUE)

### 3.1 Recommended New Commands

| Command | Description | Impact |
|---------|-------------|--------|
| `/lint` | Run code linters (phpcs, phpstan) | Quality |
| `/coverage` | Generate test coverage report | Quality |
| `/analyze` | Static code analysis | Quality |
| `/build` | Build frontend assets | Development |
| `/fresh` | Fresh migrate + seed | Development |
| `/deploy` | Deploy to staging | DevOps |
| `/health` | Check system health | Operations |
| `/docs` | Generate API documentation | Documentation |
| `/i18n-audit` | Audit i18n compliance | Quality |
| `/rtl-check` | Check RTL/LTR compliance | Quality |
| `/security` | Security vulnerability scan | Security |
| `/perf` | Performance profiling | Performance |
| `/clean` | Clean up temporary files | Maintenance |
| `/sync` | Sync platform data | Operations |
| `/backup` | Backup database | Operations |

### 3.2 Command Implementations

**`/lint` - Code Linting**
```markdown
---
description: Run PHP code linting (PHPCS, PHPStan)
---

Run comprehensive code linting for CMIS:

1. Run PHP CodeSniffer: `vendor/bin/phpcs app/ --standard=PSR12`
2. Run PHPStan: `vendor/bin/phpstan analyse app/ --level=5`
3. Run Laravel Pint: `vendor/bin/pint --test`
4. Report violations by severity
5. Suggest auto-fixes where available

Focus on:
- PSR-12 compliance
- Type safety issues
- Code smells
- Security vulnerabilities
```

**`/coverage` - Test Coverage**
```markdown
---
description: Generate and analyze test coverage report
---

Generate test coverage report for CMIS:

1. Run PHPUnit with coverage: `vendor/bin/phpunit --coverage-html coverage/`
2. Analyze coverage by module
3. Identify low-coverage areas
4. Prioritize testing needs
5. Generate summary report

Report coverage for:
- Models
- Services
- Controllers
- Repositories
- Policies
```

**`/fresh` - Fresh Database**
```markdown
---
description: Fresh migrate and seed database with safety checks
---

Perform fresh database migration with safeguards:

1. Confirm this is not production environment
2. Run: `php artisan migrate:fresh --seed`
3. If fails, analyze migration errors
4. Verify RLS policies are created
5. Run smoke tests
6. Report completion status

Safety checks:
- Verify APP_ENV is not production
- Backup current database first
- Confirm with user before destructive action
```

**`/health` - System Health**
```markdown
---
description: Check system health and service status
---

Perform comprehensive health check:

1. Check PostgreSQL connection
2. Verify Redis connectivity
3. Check queue workers
4. Verify cron jobs
5. Check storage permissions
6. Test external API connections
7. Verify RLS context
8. Check cache status

Report status:
- Service health (green/yellow/red)
- Performance metrics
- Recommendations for issues
```

**`/i18n-audit` - Internationalization Audit**
```markdown
---
description: Audit codebase for i18n compliance
---

Audit CMIS for internationalization issues:

1. Find hardcoded text in views:
   ```bash
   grep -r -E "\b(Campaign|Dashboard|Save|Delete|Cancel|Submit)\b" resources/views/ | grep -v "{{ __("
   ```

2. Find directional CSS issues:
   ```bash
   grep -r -E "(ml-|mr-|text-left|text-right|pl-|pr-)" resources/views/
   ```

3. Check for missing translation keys:
   ```bash
   grep -roh "__('[^']*')" resources/views/ | sort | uniq
   ```

4. Generate compliance report
5. Prioritize fixes by severity
```

**`/rtl-check` - RTL/LTR Compliance**
```markdown
---
description: Check RTL/LTR layout compliance
---

Check RTL/LTR layout compliance:

1. Run browser tests in Arabic locale
2. Check for horizontal overflow
3. Verify logical CSS properties used
4. Test bidirectional text handling
5. Generate visual diff report

Commands:
```bash
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick
```

Report:
- Pages with RTL issues
- CSS violations
- Recommended fixes
```

**`/perf` - Performance Profiling**
```markdown
---
description: Profile application performance
---

Profile CMIS performance:

1. Enable Laravel Debugbar
2. Run key page requests
3. Analyze query times
4. Check N+1 queries
5. Review cache hit rates
6. Generate performance report

Focus areas:
- Database query optimization
- Cache effectiveness
- Memory usage
- Response times
```

---

## Priority 4: Knowledge Base Improvements

### 4.1 Recommended Additions

| File | Purpose | Priority |
|------|---------|----------|
| AGENT_MODEL_GUIDELINES.md | Model tier selection rules | High |
| COST_OPTIMIZATION.md | Cost-aware agent usage | High |
| COMMAND_DEVELOPMENT.md | How to create commands | Medium |
| TESTING_PATTERNS.md | Testing best practices | Medium |
| DEPLOYMENT_GUIDE.md | Deployment procedures | Medium |

### 4.2 Knowledge File: AGENT_MODEL_GUIDELINES.md
```markdown
# Agent Model Selection Guidelines
**Purpose:** Choose appropriate model tier for cost optimization

## Model Tiers

### Opus (Premium - $75/M input)
Use for:
- Complex architectural decisions
- Multi-agent coordination
- Statistical/ML reasoning
- Security analysis
- Code architecture

### Sonnet (Standard - $15/M input)
Use for:
- Multi-step implementations
- Code review
- Platform integrations
- Feature development
- Testing strategy

### Haiku (Economy - $1/M input)
Use for:
- Simple lookups
- Documentation
- Routing decisions
- Template-based tasks
- Single-step operations

## Decision Tree
1. Does task require complex reasoning? → Opus
2. Does task require multi-step execution? → Sonnet
3. Is task template-based or simple lookup? → Haiku
```

---

## Priority 5: Development Acceleration

### 5.1 Workflow Improvements

**Current Pain Points:**
1. No automated linting before commit
2. Manual test execution
3. No quick health checks
4. Missing deployment automation
5. Manual i18n verification

**Recommended Workflows:**

**Pre-Commit Workflow:**
```bash
/lint        # Check code quality
/test        # Run tests
/i18n-audit  # Check translations
git commit   # If all pass
```

**Pre-PR Workflow:**
```bash
/lint
/coverage
/security
/rtl-check
gh pr create
```

**Deployment Workflow:**
```bash
/test
/build
/health
/deploy
```

### 5.2 Hook Recommendations

Add hooks to `.claude/settings.hooks.json`:
```json
{
  "hooks": {
    "pre-commit": [
      "vendor/bin/pint --test",
      "vendor/bin/phpstan analyse --level=5"
    ],
    "post-implementation": [
      "/test --quick",
      "/lint --quiet"
    ]
  }
}
```

---

## Implementation Roadmap

### Phase 1: Cost Optimization (Week 1)
**Goal:** 60% cost reduction
- [ ] Implement model tier strategy
- [ ] Downgrade 100+ agents to haiku
- [ ] Downgrade 30+ agents to sonnet
- [ ] Keep 15-20 agents as opus
- [ ] Test all downgraded agents
- [ ] Monitor cost impact

### Phase 2: Agent Consolidation (Week 2)
**Goal:** Reduce from 230 to ~55 agents
- [ ] Create consolidated platform specialists
- [ ] Merge attribution agents
- [ ] Merge audience agents
- [ ] Archive redundant agents
- [ ] Update README with new structure
- [ ] Test consolidated agents

### Phase 3: New Commands (Week 3)
**Goal:** Add 15 productivity commands
- [ ] Create `/lint` command
- [ ] Create `/coverage` command
- [ ] Create `/fresh` command
- [ ] Create `/health` command
- [ ] Create `/i18n-audit` command
- [ ] Create `/rtl-check` command
- [ ] Create `/perf` command
- [ ] Create `/security` command
- [ ] Create `/deploy` command
- [ ] Create `/build` command

### Phase 4: Knowledge Enhancement (Week 4)
**Goal:** Complete knowledge base
- [ ] Create AGENT_MODEL_GUIDELINES.md
- [ ] Create COST_OPTIMIZATION.md
- [ ] Create COMMAND_DEVELOPMENT.md
- [ ] Update agent README
- [ ] Document consolidation

### Phase 5: Workflow Automation (Week 5)
**Goal:** Faster development cycles
- [ ] Implement pre-commit hooks
- [ ] Create workflow scripts
- [ ] Add IDE integration guide
- [ ] Document best practices

---

## Expected Outcomes

### Cost Savings
| Before | After | Savings |
|--------|-------|---------|
| 100% Opus | 7% Opus, 15% Sonnet, 78% Haiku | ~65-70% |

### Productivity Gains
| Metric | Before | After |
|--------|--------|-------|
| Agent Count | 230 | ~55 |
| Commands | 5 | 20 |
| Pre-commit automation | Manual | Automated |
| Health checks | Manual | `/health` |
| i18n validation | Manual | `/i18n-audit` |

### Quality Improvements
| Metric | Before | After |
|--------|--------|-------|
| Linting | Manual | Automated |
| Coverage tracking | None | `/coverage` |
| Security scanning | Manual | `/security` |
| RTL testing | Manual | `/rtl-check` |

---

## Quick Wins (Implement Immediately)

### 1. Downgrade Simple Agents to Haiku
```bash
# Run this to save ~50% cost immediately
for agent in .claude/agents/cmis-attribution-*.md \
             .claude/agents/cmis-audience-*.md \
             .claude/agents/cmis-oauth-*.md \
             .claude/agents/cmis-webhooks-*.md; do
    sed -i 's/model: opus/model: haiku/' "$agent"
done
```

### 2. Create `/lint` Command
```bash
cat > .claude/commands/lint.md << 'EOF'
---
description: Run code linting with PHPCS and PHPStan
---

Run code linting:
1. `vendor/bin/phpcs app/ --standard=PSR12 --report=summary`
2. `vendor/bin/phpstan analyse app/ --level=5`
3. Report issues found
4. Suggest fixes
EOF
```

### 3. Create `/health` Command
```bash
cat > .claude/commands/health.md << 'EOF'
---
description: Check system health status
---

Check CMIS system health:
1. PostgreSQL: `pg_isready -h $(grep DB_HOST .env | cut -d= -f2)`
2. Redis: `redis-cli ping`
3. Storage: `ls -la storage/logs`
4. Queue: `php artisan queue:work --once --quiet`
5. Report health status
EOF
```

---

## Appendix: Agent Model Assignment

### Opus Agents (15)
```
cmis-orchestrator
cmis-predictive-analytics
laravel-architect
laravel-security
cmis-ab-testing-specialist
cmis-compliance-security
app-feasibility-researcher
laravel-tech-lead
cmis-analytics-expert
cmis-ai-semantic
laravel-db-architect
laravel-refactor-specialist
cmis-model-architect
cmis-data-consolidation
cmis-experimentation
```

### Sonnet Agents (~25)
```
laravel-testing
laravel-code-quality
laravel-performance
cmis-campaign-expert
cmis-platform-integration
cmis-multi-tenancy
cmis-content-manager
cmis-enterprise-features
cmis-marketing-automation
cmis-crm-specialist
cmis-rbac-specialist
cmis-context-awareness
cmis-ui-frontend
laravel-documentation
laravel-devops
laravel-api-design
laravel-auditor
cmis-alerts-monitoring
cmis-anomaly-detection
cmis-meta-ads-specialist
cmis-google-ads-specialist
cmis-tiktok-ads-specialist
cmis-linkedin-ads-specialist
cmis-twitter-ads-specialist
cmis-snapchat-ads-specialist
cmis-ad-campaign-analyst
cmis-social-publishing
```

### Haiku Agents (~115)
All remaining agents not in above lists

---

**Created:** 2025-12-01
**Maintained By:** CMIS AI Agent Development Team
