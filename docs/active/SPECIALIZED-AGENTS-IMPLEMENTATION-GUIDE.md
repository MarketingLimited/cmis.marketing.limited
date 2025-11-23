# CMIS Specialized Agents Implementation Guide
## Feature-Level Agent Architecture - Complete Implementation Strategy

**Created:** 2025-11-23
**Status:** Phase 1 - Foundation Complete
**Progress:** 1/180+ agents completed
**Next Phase:** Continue systematic implementation

---

## 🎯 What Was Accomplished

### ✅ Phase 0: Planning & Architecture (COMPLETED)

#### 1. **Comprehensive Analysis**
- Analyzed all 51 CMIS domains
- Identified 60+ model directories
- Mapped 6 advertising platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat)
- Discovered 244 models across the codebase

#### 2. **Created Complete Architecture Plan**
**File:** `docs/active/analysis/COMPREHENSIVE-AGENT-ARCHITECTURE-PLAN.md`

**Total Planned Agents:** 180+ specialized agents

**Breakdown by Platform:**
- **Meta (Facebook/Instagram):** 20+ agents
  - Audience Management: 5 agents
  - Campaign Structure: 3 agents
  - Ad Set Features: 4 agents
  - Creative Features: 5 agents
  - Tracking & Attribution: 3 agents

- **Google Ads:** 25+ agents
  - Campaign Types: 6 agents
  - Bidding & Budget: 4 agents
  - Targeting: 5 agents
  - Quality & Optimization: 5 agents
  - Shopping & Feeds: 3 agents
  - Tracking & Analytics: 2 agents

- **TikTok Ads:** 15+ agents
  - Campaign Features: 4 agents
  - Creative Formats: 5 agents
  - Targeting: 4 agents
  - Tracking & Attribution: 2 agents

- **LinkedIn Ads:** 12+ agents
  - Campaign Features: 4 agents
  - B2B Targeting: 5 agents
  - Lead Generation: 2 agents
  - Tracking: 1 agent

- **Twitter Ads:** 10+ agents
  - Campaign Features: 3 agents
  - Targeting: 4 agents
  - Creative: 2 agents
  - Tracking: 1 agent

- **Snapchat Ads:** 12+ agents
  - Campaign Features: 5 agents
  - Targeting: 4 agents
  - Creative: 2 agents
  - Tracking: 1 agent

**Domain-Specific Agents:**
- **Campaign Domain:** 15+ agents (lifecycle, budget, context, templates, orchestration)
- **Audience Domain:** 10+ agents (segmentation, builder, sync, insights)
- **Creative Domain:** 12+ agents (asset management, content creation, templates, optimization)
- **Analytics Domain:** 18+ agents (metrics, forecasting, A/B testing, alerts, attribution)
- **Social Domain:** 8+ agents (publishing, content management, engagement)
- **Integration Domain:** 10+ agents (OAuth, webhooks, data sync)

#### 3. **Created Standardized Agent Template**
Every agent follows this structure:

```markdown
---
name: cmis-[platform]-[feature]
description: |
  Expert in [specific feature]
model: haiku  # or sonnet for complex agents
---

# CMIS [Platform] [Feature] Specialist V1.0

## 🚨 CRITICAL: LIVE API DISCOVERY
- WebSearch for latest API version
- WebFetch official documentation
- Discover current CMIS implementation

## 🎯 CORE MISSION
- Clear expertise definition
- 5-7 key responsibilities
- Superpower statement

## 🔍 DISCOVERY PROTOCOLS
- Protocol 1: API version & capabilities
- Protocol 2: Current implementation
- Protocol 3: Database schema

## 📋 AGENT ROUTING REFERENCE
- Keywords for routing
- When to use this agent
- Coordination with other agents

## 🎯 KEY PATTERNS
- Pattern 1: Primary use case (with RLS compliance)
- Pattern 2: Secondary use case
- Pattern 3+: Additional patterns

## 💡 DECISION TREE
- Visual routing logic

## 🎯 QUALITY ASSURANCE
- Checklist for verification

## 🚨 CRITICAL RULES
- ALWAYS do X
- NEVER do Y

## 📝 EXAMPLES
- Example 1: Common use case
- Example 2: Troubleshooting
- Example 3+: Advanced scenarios

## 📚 OFFICIAL DOCUMENTATION LINKS
- Primary and secondary links

## 🔧 TROUBLESHOOTING GUIDE
- Common issues and solutions
```

#### 4. **Created First Specialized Agent**
**File:** `.claude/agents/cmis-meta-audiences-custom.md`

**This agent demonstrates:**
✅ **Deep Specialization:** Focuses ONLY on Meta Custom Audiences
✅ **5 Sub-Patterns:**
- Customer List audiences (with SHA-256 hashing)
- Website Traffic audiences (Pixel-based)
- App Activity audiences
- Offline Activity audiences
- Engagement audiences (Page, Video, Lead Form, IG, Events)

✅ **Complete Implementation:**
- Full PHP code examples with RLS compliance
- Multi-tenant testing patterns
- Privacy compliance (GDPR, CCPA)
- Data hashing and normalization
- Match rate monitoring
- Error handling

✅ **LIVE API Discovery:**
- WebSearch for latest Meta Ads API version
- WebFetch for official documentation
- Grep/Glob for current CMIS implementation

✅ **Troubleshooting Guide:**
- Low match rate diagnosis (3 common causes)
- Audience creation failures
- Async processing issues

**Lines of Code:** 850+ lines of specialized knowledge

---

## 📐 Agent Naming Convention

### Standard Pattern
```
cmis-<platform>-<feature-category>-<specific-feature>.md
```

### Examples
```
✅ cmis-meta-audiences-custom.md          (Custom Audiences)
✅ cmis-meta-audiences-lookalike.md       (Lookalike Audiences)
✅ cmis-meta-audiences-advantage-plus.md  (Advantage+ Audiences)
✅ cmis-meta-placements-manual.md         (Manual Placements)
✅ cmis-meta-placements-advantage-plus.md (Advantage+ Placements)
✅ cmis-google-bidding-tcpa.md            (Target CPA Bidding)
✅ cmis-google-campaigns-pmax.md          (Performance Max)
✅ cmis-tiktok-creatives-video.md         (TikTok Video Ads)
```

### Rules
- All lowercase
- Hyphen-separated (not underscores)
- Platform prefix for platform-specific agents
- Clear feature hierarchy (category → specific)
- No abbreviations unless standard (CPA, ROAS, CBO, PMax)

---

## 🚀 Implementation Roadmap

### Phase 1: Core Platform Agents (Priority 1) - 4 Weeks

#### Week 1: Meta Audience & Campaign Agents (CURRENT PHASE)
**Status:** 1/8 completed ✅

Agents to create:
- [x] `cmis-meta-audiences-custom.md` ✅ COMPLETED
- [ ] `cmis-meta-audiences-lookalike.md`
- [ ] `cmis-meta-audiences-saved.md`
- [ ] `cmis-meta-audiences-advantage-plus.md`
- [ ] `cmis-meta-campaigns-objectives.md`
- [ ] `cmis-meta-campaigns-budget-optimization.md`
- [ ] `cmis-meta-campaigns-bidding.md`
- [ ] `cmis-meta-pixel-setup.md`

**Estimated Time per Agent:** 2-3 hours
**Total Week 1:** ~24 hours (3 days of focused work)

#### Week 2: Meta Creative & Placement Agents
Agents to create (8 agents):
- [ ] `cmis-meta-placements-manual.md`
- [ ] `cmis-meta-placements-advantage-plus.md`
- [ ] `cmis-meta-creatives-single-image.md`
- [ ] `cmis-meta-creatives-video.md`
- [ ] `cmis-meta-creatives-carousel.md`
- [ ] `cmis-meta-creatives-dynamic.md`
- [ ] `cmis-meta-creatives-advantage-plus.md`
- [ ] `cmis-meta-conversion-api.md`

**Total Week 2:** ~24 hours

#### Week 3: Google Search & Shopping Agents
Agents to create (8 agents):
- [ ] `cmis-google-campaigns-search.md`
- [ ] `cmis-google-campaigns-shopping.md`
- [ ] `cmis-google-bidding-tcpa.md`
- [ ] `cmis-google-bidding-troas.md`
- [ ] `cmis-google-targeting-keywords.md`
- [ ] `cmis-google-quality-score.md`
- [ ] `cmis-google-rsa.md`
- [ ] `cmis-google-shopping-feeds.md`

**Total Week 3:** ~24 hours

#### Week 4: Google Display & Performance Max Agents
Agents to create (8 agents):
- [ ] `cmis-google-campaigns-display.md`
- [ ] `cmis-google-campaigns-video.md`
- [ ] `cmis-google-campaigns-pmax.md`
- [ ] `cmis-google-targeting-audiences.md`
- [ ] `cmis-google-targeting-rlsa.md`
- [ ] `cmis-google-extensions.md`
- [ ] `cmis-google-conversion-tracking.md`
- [ ] `cmis-google-analytics-integration.md`

**Total Week 4:** ~24 hours

**Phase 1 Total:** 32 agents, ~96 hours (12 days)

---

### Phase 2: Secondary Platforms - 2 Weeks

#### Week 5: TikTok & LinkedIn Agents
**TikTok Agents (8):**
- [ ] `cmis-tiktok-campaigns-objectives.md`
- [ ] `cmis-tiktok-campaigns-spark.md`
- [ ] `cmis-tiktok-creatives-video.md`
- [ ] `cmis-tiktok-creatives-in-feed.md`
- [ ] `cmis-tiktok-targeting-interest.md`
- [ ] `cmis-tiktok-targeting-custom-audiences.md`
- [ ] `cmis-tiktok-pixel.md`
- [ ] `cmis-tiktok-shopping-ads.md`

**LinkedIn Agents (6):**
- [ ] `cmis-linkedin-campaigns-sponsored-content.md`
- [ ] `cmis-linkedin-campaigns-sponsored-messaging.md`
- [ ] `cmis-linkedin-targeting-job-titles.md`
- [ ] `cmis-linkedin-targeting-company.md`
- [ ] `cmis-linkedin-lead-gen-forms.md`
- [ ] `cmis-linkedin-insight-tag.md`

**Total Week 5:** 14 agents, ~42 hours

#### Week 6: Twitter & Snapchat Agents
**Twitter Agents (5):**
- [ ] `cmis-twitter-campaigns-promoted-tweets.md`
- [ ] `cmis-twitter-targeting-keywords.md`
- [ ] `cmis-twitter-targeting-conversation.md`
- [ ] `cmis-twitter-creatives-video.md`
- [ ] `cmis-twitter-pixel.md`

**Snapchat Agents (6):**
- [ ] `cmis-snapchat-campaigns-snap-ads.md`
- [ ] `cmis-snapchat-campaigns-ar-lenses.md`
- [ ] `cmis-snapchat-creatives-video.md`
- [ ] `cmis-snapchat-targeting-lifestyle.md`
- [ ] `cmis-snapchat-pixel.md`
- [ ] `cmis-snapchat-instant-forms.md`

**Total Week 6:** 11 agents, ~33 hours

**Phase 2 Total:** 25 agents, ~75 hours (9 days)

---

### Phase 3: Core Domain Agents - 3 Weeks

#### Week 7: Campaign & Budget Agents
**Campaign Lifecycle (5):**
- [ ] `cmis-campaigns-planning.md`
- [ ] `cmis-campaigns-execution.md`
- [ ] `cmis-campaigns-monitoring.md`
- [ ] `cmis-campaigns-optimization.md`
- [ ] `cmis-campaigns-reporting.md`

**Budget Management (4):**
- [ ] `cmis-budgets-allocation.md`
- [ ] `cmis-budgets-pacing.md`
- [ ] `cmis-budgets-forecasting.md`
- [ ] `cmis-budgets-optimization.md`

**Total Week 7:** 9 agents, ~27 hours

#### Week 8: Audience & Creative Agents
**Audience Management (6):**
- [ ] `cmis-audiences-segmentation.md`
- [ ] `cmis-audiences-builder.md`
- [ ] `cmis-audiences-sync.md`
- [ ] `cmis-audiences-insights.md`
- [ ] `cmis-audiences-enrichment-data.md`
- [ ] `cmis-audiences-enrichment-ai.md`

**Creative Management (6):**
- [ ] `cmis-assets-library.md`
- [ ] `cmis-content-plans.md`
- [ ] `cmis-content-briefs.md`
- [ ] `cmis-templates-video.md`
- [ ] `cmis-templates-copy.md`
- [ ] `cmis-creative-optimization.md`

**Total Week 8:** 12 agents, ~36 hours

#### Week 9: Analytics & Social Agents
**Analytics (9):**
- [ ] `cmis-metrics-definitions.md`
- [ ] `cmis-reports-templates.md`
- [ ] `cmis-forecasting-statistical.md`
- [ ] `cmis-experiments-design.md`
- [ ] `cmis-experiments-significance.md`
- [ ] `cmis-alerts-rules.md`
- [ ] `cmis-attribution-last-click.md`
- [ ] `cmis-attribution-linear.md`
- [ ] `cmis-attribution-data-driven.md`

**Social (4):**
- [ ] `cmis-social-scheduling.md`
- [ ] `cmis-social-publishing.md`
- [ ] `cmis-social-library.md`
- [ ] `cmis-social-engagement.md`

**Total Week 9:** 13 agents, ~39 hours

**Phase 3 Total:** 34 agents, ~102 hours (13 days)

---

### Phase 4: Integration & Remaining Agents - 2 Weeks

#### Week 10: OAuth & Integration Agents
**OAuth (6):**
- [ ] `cmis-oauth-meta.md`
- [ ] `cmis-oauth-google.md`
- [ ] `cmis-oauth-tiktok.md`
- [ ] `cmis-oauth-linkedin.md`
- [ ] `cmis-oauth-twitter.md`
- [ ] `cmis-oauth-snapchat.md`

**Webhooks & Sync (4):**
- [ ] `cmis-webhooks-meta.md`
- [ ] `cmis-webhooks-google.md`
- [ ] `cmis-webhooks-verification.md`
- [ ] `cmis-sync-platform.md`

**Total Week 10:** 10 agents, ~30 hours

#### Week 11: Remaining Domain Agents
**Remaining agents from all categories**

**Total Week 11:** Variable, ~30 hours

**Phase 4 Total:** ~60 hours (8 days)

---

### Phase 5: Documentation & Testing - 1 Week

#### Week 12: Finalization
- [ ] Update `.claude/agents/README.md` with all 180+ agents
- [ ] Create comprehensive agent catalog by category
- [ ] Test agent routing in cmis-orchestrator
- [ ] Create usage examples documentation
- [ ] Performance optimization
- [ ] Agent coordination testing

**Total Week 12:** ~40 hours (5 days)

---

## 📊 Overall Project Timeline

**Total Agents:** 180+ specialized agents
**Total Estimated Time:** ~400 hours
**Timeline:** 12 weeks (3 months) of focused work
**Completion Rate:** 1/180 = 0.5% complete

### Week-by-Week Breakdown
| Week | Focus | Agents | Hours |
|------|-------|--------|-------|
| 1 | Meta Audience & Campaign | 8 | 24 |
| 2 | Meta Creative & Placement | 8 | 24 |
| 3 | Google Search & Shopping | 8 | 24 |
| 4 | Google Display & PMax | 8 | 24 |
| 5 | TikTok & LinkedIn | 14 | 42 |
| 6 | Twitter & Snapchat | 11 | 33 |
| 7 | Campaign & Budget | 9 | 27 |
| 8 | Audience & Creative | 12 | 36 |
| 9 | Analytics & Social | 13 | 39 |
| 10 | OAuth & Integration | 10 | 30 |
| 11 | Remaining Agents | Variable | 30 |
| 12 | Documentation & Testing | N/A | 40 |
| **TOTAL** | **12 weeks** | **~180** | **~400** |

---

## 🎯 Quality Standards

Every agent must include:

### 1. **LIVE API Discovery (CRITICAL)**
```markdown
## 🚨 CRITICAL: LIVE API DISCOVERY

**BEFORE answering ANY question:**

### 1. Check Latest API Version
WebSearch("[Platform] [Feature] API latest version 2025")

### 2. Fetch Official Documentation
WebFetch("https://developers.[platform].com/docs/[api]")

### 3. Discover Current Implementation
Glob("**/app/Services/AdPlatforms/[Platform]*.php")
```

### 2. **RLS Compliance (MANDATORY)**
```php
// ALWAYS set org context
DB::statement("SELECT init_transaction_context(?)", [$orgId]);

// Models automatically respect RLS
$audiences = Audience::where('platform', 'meta')->get();
// ↑ Returns only this org's data (RLS enforcement)
```

### 3. **Complete Code Examples**
- Full implementation with error handling
- Multi-tenant testing patterns
- Privacy compliance checks

### 4. **Troubleshooting Guide**
- At least 3 common issues
- Diagnosis steps
- Solutions with code

### 5. **Official Documentation Links**
- Primary API docs
- Best practices guides
- Changelog/deprecation notices

---

## 📝 Writing an Agent: Step-by-Step Guide

### Step 1: Research & Discovery (30 minutes)
1. **Read Official Docs**
   - Platform developer documentation
   - API reference for the specific feature
   - Best practices guides

2. **WebSearch for Latest Info**
   - "[Platform] [Feature] API latest version 2025"
   - "[Platform] [Feature] best practices 2025"
   - "[Platform] [Feature] common issues"

3. **Discover CMIS Implementation**
   - Search codebase for existing implementation
   - Find related models, services, connectors
   - Check database schema and RLS policies

### Step 2: Agent File Creation (90 minutes)
1. **Copy Template** (from this guide or cmis-meta-audiences-custom.md)
2. **Fill YAML Frontmatter**
   - name, description, model (haiku or sonnet)
3. **Write Core Mission**
   - 5-7 key responsibilities
   - Superpower statement
4. **Create Discovery Protocols**
   - Protocol 1: API version & capabilities
   - Protocol 2: Current implementation
   - Protocol 3: Database schema
5. **Define Key Patterns**
   - Pattern 1: Primary use case (with full code)
   - Pattern 2+: Secondary use cases
   - Always include RLS compliance
   - Always include testing patterns
6. **Write Examples**
   - At least 3 concrete examples
   - Include error handling
   - Show multi-tenant isolation
7. **Create Troubleshooting Guide**
   - 3+ common issues with solutions
8. **Add Documentation Links**
   - Official API docs
   - Best practices
   - Changelog

### Step 3: Quality Check (30 minutes)
- [ ] LIVE API discovery included?
- [ ] RLS compliance in all code examples?
- [ ] Multi-tenant testing patterns?
- [ ] Privacy compliance (GDPR, CCPA)?
- [ ] Error handling?
- [ ] Official documentation links?
- [ ] Troubleshooting guide?
- [ ] Clear routing keywords?

### Step 4: Testing (30 minutes)
- [ ] Agent routes correctly from orchestrator
- [ ] Code examples are accurate
- [ ] Links to documentation work
- [ ] Agent coordinates with related agents

**Total Time per Agent:** 2.5-3 hours

---

## 🔄 Workflow Optimization Tips

### 1. **Batch Similar Agents**
- Write all Meta Audience agents together (shared patterns)
- Write all Google Bidding agents together
- Write all OAuth agents together

### 2. **Reuse Code Patterns**
- Copy RLS compliance code from previous agents
- Reuse testing patterns
- Share troubleshooting approaches

### 3. **Use AI Assistance**
- Ask Claude to help with research
- Generate initial code examples
- Refine based on CMIS patterns

### 4. **Parallel Work**
- Research multiple agents at once
- Write YAML frontmatter for multiple agents
- Fill in details incrementally

---

## 📚 Key Principles

### 1. **Single Responsibility**
Each agent handles ONE feature only:
✅ `cmis-meta-audiences-custom` - Custom Audiences ONLY
❌ `cmis-meta-audiences` - Too broad (covers all audience types)

### 2. **LIVE Discovery Over Static Knowledge**
✅ WebSearch for latest API version
❌ Hard-code API version (gets outdated)

### 3. **Platform-Specific, Never Generic**
✅ Meta Pixel tracking with server-side Conversion API
❌ Generic "pixel tracking" advice

### 4. **RLS Compliance is Non-Negotiable**
✅ `DB::statement("SELECT init_transaction_context(?)", [$orgId])`
❌ Manual `WHERE org_id = ?` filtering (bypasses RLS)

### 5. **Privacy First**
✅ SHA-256 hashing for customer data
✅ GDPR consent verification
❌ Upload unhashed PII

---

## 🎯 Success Metrics

### Agent Quality Metrics
- **API Currency:** 100% of agents use LIVE API discovery
- **RLS Compliance:** 100% of code examples respect multi-tenancy
- **Code Completeness:** 100% of agents have working code examples
- **Testing Coverage:** 100% of agents include testing patterns
- **Documentation:** 100% of agents link to official docs

### Implementation Metrics
- **Agents Created:** 1/180 (0.5%)
- **Phase 1 Progress:** 1/32 (3%)
- **Estimated Completion:** 12 weeks from start

---

## 🚀 Next Steps

### Immediate Next Steps (Week 1 Continuation):

1. **Create Lookalike Audiences Agent**
   - File: `.claude/agents/cmis-meta-audiences-lookalike.md`
   - Focus: Similarity algorithms, source audiences, expansion
   - Time: 2-3 hours

2. **Create Saved Audiences Agent**
   - File: `.claude/agents/cmis-meta-audiences-saved.md`
   - Focus: Detailed targeting, AND/OR logic, exclusions
   - Time: 2-3 hours

3. **Create Advantage+ Audiences Agent**
   - File: `.claude/agents/cmis-meta-audiences-advantage-plus.md`
   - Focus: Automatic expansion, ML optimization
   - Time: 2-3 hours

4. **Continue with Campaign Agents**
   - Campaign objectives, budget optimization, bidding strategies

### Git Workflow:
```bash
# Create feature branch for agents
git checkout -b claude/specialized-agents-phase1-[session-id]

# Add new agents
git add .claude/agents/*.md
git add docs/active/analysis/COMPREHENSIVE-AGENT-ARCHITECTURE-PLAN.md
git add docs/active/SPECIALIZED-AGENTS-IMPLEMENTATION-GUIDE.md

# Commit
git commit -m "feat: add specialized Meta Custom Audiences agent and implementation plan

- Created cmis-meta-audiences-custom.md (850+ lines)
- Comprehensive agent architecture plan (180+ agents)
- Implementation guide and timeline
- Progress: 1/180 agents (0.5%)"

# Push
git push -u origin claude/specialized-agents-phase1-[session-id]
```

---

## 📞 Questions & Support

### Common Questions:

**Q: Is it really necessary to have 180+ agents?**
A: Yes! Feature-level granularity ensures:
- Deep expertise per feature
- Easy maintenance when APIs change
- Clear responsibility boundaries
- Faster development (know exactly which agent to ask)

**Q: Should I use haiku or sonnet model?**
A:
- **haiku:** Simple features (pixel setup, basic targeting)
- **sonnet:** Complex features (bidding algorithms, ML optimization)

**Q: How do I test agent routing?**
A: Update `cmis-orchestrator.md` to include keywords for new agents, then test with sample questions.

**Q: What if a platform API changes?**
A: Update ONLY the affected agent(s) - this is the power of feature-level granularity!

---

## 📊 Current Status Summary

✅ **Completed:**
- Comprehensive architecture plan (180+ agents)
- Standardized agent template
- First specialized agent (cmis-meta-audiences-custom)
- Implementation guide and timeline

🔄 **In Progress:**
- Meta Audience agents (1/5 completed)

📋 **Next:**
- Complete Week 1 agents (7 more agents)
- Continue with Meta Creative & Placement agents (Week 2)

---

**Ready to continue? Let's build the most comprehensive, specialized agent framework for CMIS!** 🚀

**Progress:** 1/180 agents ● Phase 1: Week 1 ● 0.5% Complete
**Estimated Completion:** 12 weeks (3 months)
**Next Agent:** `cmis-meta-audiences-lookalike.md`
