# CMIS AI Agents Framework
## Specialized AI Agents for CMIS (Cognitive Marketing Intelligence Suite)

**Last Updated:** 2025-12-04
**Project:** CMIS - Cognitive Marketing Intelligence Suite
**Version:** 3.2 - Model Tier Optimization + Browser Testing Deduplication

---

## 🎉 Recent Improvements (2025-12-01)

### Phase 2: Browser Testing Deduplication
- **222 agents optimized** - Removed duplicated browser testing content
- **244.8 KB saved** - All agents now reference shared file
- **Agent skills preserved** - Agent-specific testing guidance retained
- **Shared Reference:** `.claude/agents/_shared/browser-testing-integration.md`

### Phase 1: Model Tier Optimization
| Model | Cost | Use Case | Examples |
|-------|------|----------|----------|
| **opus** | $75/M | Complex reasoning, multi-step tasks | cmis-orchestrator, cmis-troubleshooting |
| **sonnet** | $15/M | Standard development tasks | cmis-campaign-expert, laravel-architect |
| **haiku** | $1/M | Simple lookups, light tasks | Most specialized agents |

**Cost Optimization:** 80%+ agents use haiku, reducing costs by ~90% while maintaining quality.

---

## 🎉 Agent Best Practices (2025-11-27)

**ALL agents now follow comprehensive best practices through shared infrastructure:**

### ✅ What's New

1. **File Size Limits (<500 lines)** - Agents check file size and refactor if needed
2. **API Research Requirements** - Must research latest docs before platform integration
3. **Large File Handling** - Optimized read/edit strategies for files >800 lines
4. **Tool Usage Optimization** - Parallel execution, proper tool selection (Glob/Grep/Read)

### 📊 Impact

- **Agents Affected:** 200+ agents (ALL agents reference shared infrastructure)
- **Performance:** 3-10x faster operations through parallel tool execution
- **Quality:** File size limits enforce Single Responsibility Principle
- **API Accuracy:** Research-first approach ensures latest documentation

**See:** `.claude/agents/AGENT_OPTIMIZATION_SUMMARY.md` for complete details

**Shared Infrastructure:** `.claude/agents/_shared/infrastructure-preflight.md` (v1.1 → v1.2)

---

## 🎯 OVERVIEW

This directory contains **specialized AI agents** custom-built for the CMIS project. Unlike generic Laravel agents, these agents have deep knowledge of:

- CMIS's unique PostgreSQL RLS-based multi-tenancy
- 12-schema database architecture (197 tables)
- Platform integrations (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat)
- AI-powered semantic search via pgvector
- Campaign Context System (EAV pattern)
- Social media management and publishing
- Real-time analytics and performance tracking
- 244 Models across 51 business domains
- 27 test files (legacy tests archived, new tests pending for recent features)

**Total Agents:** 47 specialized agents
**Project Knowledge Base:** `.claude/CMIS_PROJECT_KNOWLEDGE.md`

**🆕 NEW (2025-11-23):** 3 Advanced Analytics Agents (Predictive Analytics, A/B Testing, Alerts & Monitoring)
**🆕 NEW (2025-11-22):** 7 Ad Campaign & Platform Advertising Agents

---

## 🤖 CMIS-SPECIFIC AGENTS

### 📁 Utility Agents

#### **app-feasibility-researcher** - Dual-Mode App Analysis Expert V2.1 ⚡
**File:** `app-feasibility-researcher.md`

**Purpose:** DUAL-MODE agent - Evaluates NEW ideas AND analyzes EXISTING apps for weaknesses.

**Use when:**
- **MODE 1 (New Ideas):** Evaluating new app/feature proposals
- **MODE 1:** Need market research on similar solutions
- **MODE 1:** Assessing technical/business viability before building
- **MODE 2 (Existing Apps):** Finding نقاط الضعف (weakness points)
- **MODE 2:** Auditing current app/features for problems
- **MODE 2:** Getting health score and prioritized fix recommendations

**Handles:**

**MODE 1 (New Ideas):**
- Logic & coherence analysis
- Technical viability assessment
- Market research via web (competitors, trends)
- Implementation pattern discovery
- Alternative solution discovery
- Completability & risk assessment
- Comprehensive feasibility reporting

**MODE 2 (Existing Apps):**
- منطقية الفكرة (Idea logic analysis)
- منطقية الميزات (Feature logic analysis)
- منطقية الترابط (Relationship logic analysis)
- منطقية الهيكل (Architecture logic analysis)
- منطقية التنفيذ (Implementation logic analysis)
- منطقية الحاجة (Necessity logic analysis)
- إمكانية الإتمام (Completability assessment)
- إمكانية التفعيل (Deployment feasibility)
- إمكانية الاستخدام (Usability analysis)
- سرعة التنفيذ (Development speed logic)
- Overall health scoring (0-100)
- نقاط الضعف (Weakness point detection)
- Prioritized fix recommendations

**Key Features:**
- **Dual-mode detection**: Automatically identifies new idea vs. existing app with confirmation
- **Web-powered research**: Finds similar apps, competitors, trends using parallel searches
- **Weakness detection**: Finds نقاط الضعف with severity ratings and file:line locations
- **Health scoring**: 0-100 score with systematic calculation methodology
- **Data-driven**: Evidence-based with quality validation checklist
- **Structured reporting**: Organized docs with comprehensive templates
- **⚡ Optimized (V2.1)**: Parallel execution, time limits, quality gates, Bash tool for codebase analysis

**MODE 1 Example:**
```
"Analyze feasibility of AI-powered email automation tool"
→ Researches 15+ similar apps (Mailchimp, HubSpot, etc.)
→ Finds implementation patterns (SendGrid API, LLM integration)
→ Suggests alternative: Build as Gmail/Outlook plugin instead
→ Feasibility score: 7.5/10
→ Recommends: Proceed with caution, niche differentiation
→ Report: docs/active/analysis/app-feasibility-[name]-[date].md
```

**MODE 2 Example:**
```
"Analyze CMIS app and find all weaknesses"
→ Analyzes 10+ dimensions (idea, features, architecture, etc.)
→ Finds 2 critical, 5 high, 5 medium issues
→ Provides specific file:line locations for each issue
→ Overall health score: 71/100
→ Top 10 critical weaknesses with priorities
→ Recommends: Fix security issues IMMEDIATELY, then DevOps
→ Report: docs/active/analysis/app-weakness-analysis-[name]-[date].md
```

**Output:**
- **MODE 1:** `docs/active/analysis/app-feasibility-[name]-[date].md`
- **MODE 2:** `docs/active/analysis/app-weakness-analysis-[name]-[date].md`

---

#### **cmis-doc-organizer** - Documentation Organization Specialist
**File:** `cmis-doc-organizer.md`

**Purpose:** Automatically organize, maintain, and consolidate project documentation, preventing documentation chaos.

**Use when:**
- Documentation files scattered in root directory
- Need to archive old/completed documents
- Multiple duplicate or overlapping documents
- Creating organized documentation structure
- Regular documentation maintenance

**Handles:**
- Automatic classification of documents by type and status
- Moving documents to organized directory structure
- Archiving completed/outdated documentation
- Consolidating duplicate documents
- Creating comprehensive documentation index
- Maintaining clean project structure

**Key Features:**
- **Auto-classification**: Plans, Reports, Analyses, Guides, etc.
- **Smart archiving**: Automatically identifies completed work
- **Consolidation**: Merges duplicate/overlapping documents
- **Index generation**: Creates navigable documentation map
- **Continuous maintenance**: Keeps docs organized over time

**Example:**
```
"Organize all documentation files in the root directory"
→ Scans 70+ .md files, classifies, moves to docs/active or docs/archive
→ Creates comprehensive docs/README.md index
→ Reports organization summary
```

**See:** `DOC_ORGANIZER_GUIDE.md` for detailed usage guide.

---

### 🎯 Core CMIS Agents

#### 1. **cmis-orchestrator** - Master Coordinator
**File:** `cmis-orchestrator.md`

**Purpose:** Primary entry point that analyzes requests and routes to appropriate specialized agents.

**Use when:**
- Unsure which agent to use
- Complex multi-domain requests
- Need coordination between multiple agents

**Example:**
```
"I want to add AI-powered recommendations to social media posting"
→ Orchestrator coordinates: cmis-social-publishing + cmis-ai-semantic + cmis-ui-frontend
```

---

#### 2. **cmis-context-awareness** - Knowledge Expert
**File:** `cmis-context-awareness.md`

**Purpose:** Deep understanding of CMIS architecture, patterns, and business domains.

**Use when:**
- "How does [feature] work in CMIS?"
- "Where should I add [functionality]?"
- Need architectural guidance
- Understanding CMIS-specific patterns

**Key Knowledge:**
- All 10 business domains
- Multi-tenancy architecture
- Database schema (12 schemas)
- Service patterns
- Repository pattern

**Example:**
```
"How do I add a new feature to campaign management?"
→ Explains Campaign domain, related models, services, RLS implications
```

---

#### 3. **cmis-multi-tenancy** - RLS & Multi-Tenancy Specialist
**File:** `cmis-multi-tenancy.md`

**Purpose:** THE expert on PostgreSQL Row-Level Security and organization isolation.

**Use when:**
- Data isolation issues
- Adding RLS to new tables
- Multi-tenancy debugging
- Context management problems

**Critical for:**
- "Users seeing other org's data"
- Implementing new tables with RLS
- Understanding init_transaction_context()
- RLS policy creation

**Example:**
```
"How do I add RLS policies to a new table?"
→ Step-by-step migration with SQL policies, triggers, and testing
```

---

#### 4. **cmis-platform-integration** - Platform Integration Expert
**File:** `cmis-platform-integration.md`

**Purpose:** Expert in integrating Meta, Google, TikTok, LinkedIn, Twitter, Snapchat, and e-commerce platforms.

**Use when:**
- OAuth flow issues
- Webhook not working
- Token refresh failing
- Adding new platform
- Sync job problems
- E-commerce integration (Shopify, WooCommerce)

**Handles:**
- AdPlatformFactory pattern
- Webhook signature verification
- Token management
- Data synchronization
- E-commerce product catalog sync
- Inventory management integration

**Example:**
```
"Meta webhook verification failing"
→ Diagnoses signature verification, provides fix

"How do I integrate Shopify for product sync?"
→ OAuth flow for Shopify API
→ Product catalog synchronization
→ Inventory update handling
→ Multi-tenant isolation for e-commerce data
```

---

#### 5. **cmis-ai-semantic** - AI & Semantic Search Specialist
**File:** `cmis-ai-semantic.md`

**Purpose:** Expert in pgvector, Google Gemini API, and semantic search.

**Use when:**
- Implementing semantic search
- Vector embedding generation
- pgvector performance issues
- AI rate limit problems
- Similarity search queries

**Handles:**
- EmbeddingOrchestrator
- pgvector operations
- Gemini API integration
- Rate limiting (30/min, 500/hour)
- Cosine similarity search

**Example:**
```
"How do I implement semantic search for knowledge base?"
→ Complete implementation with pgvector, embeddings, and caching
```

---

#### 6. **cmis-campaign-expert** - Campaign Management Expert
**File:** `cmis-campaign-expert.md`

**Purpose:** Specialist in Campaign domain and lifecycle management.

**Use when:**
- Campaign-related features
- Campaign Context System (EAV)
- Budget tracking
- Campaign analytics
- Status management

**Handles:**
- FieldDefinition/FieldValue (EAV)
- Campaign lifecycle
- Budget tracking
- Performance metrics

**Example:**
```
"How do I add custom fields to campaigns?"
→ Explains EAV pattern with FieldDefinition, provides migration
```

---

#### 7. **cmis-analytics-expert** - Analytics & Reporting Expert V2.1 📊
**File:** `cmis-analytics-expert.md`

**Purpose:** Master of real-time analytics, attribution modeling, predictive analytics, and enterprise reporting.

**Use when:**
- Implementing real-time analytics dashboards
- Adding attribution models (6 models: last-click, first-click, linear, time-decay, position-based, data-driven)
- Building predictive analytics features
- Creating ROI analysis and profitability tracking
- Implementing KPI monitoring systems
- Generating forecasts and projections
- Setting up enterprise alerts

**Handles:**
- Real-time analytics (1m, 5m, 15m, 1h windows)
- Attribution credit distribution
- Predictive algorithms (moving average, linear regression, weighted)
- Customer lifetime value (LTV/CAC) calculations
- 30-day projections with confidence levels
- Anomaly detection (Z-score based)
- Chart.js integration for visualizations
- Performance optimization for analytics queries

**Key Features:**
- 6 attribution models implementation guidance
- Statistical algorithm patterns
- Multi-tenant analytics with RLS
- Real-time data refresh strategies
- Report scheduling and generation
- Alert evaluation and management

**Example:**
```
"How do I implement last-click attribution model?"
→ Provides attribution logic, database queries, and credit distribution patterns
→ Shows integration with unified_metrics table
→ Includes Chart.js visualization example
→ Performance optimization tips for attribution queries
```

**References:** `docs/analytics/`, `docs/phases/planned/analytics/`

---

#### 8. **cmis-experimentation** - A/B Testing & Experimentation Expert V2.1 🧪
**File:** `cmis-experimentation.md`

**Purpose:** Specialist in A/B testing, multivariate testing, and statistical analysis.

**Use when:**
- Implementing A/B testing features
- Designing experiment frameworks
- Creating variant assignment logic
- Calculating statistical significance
- Building winner determination logic
- Setting up feature flagging

**Handles:**
- A/B testing (2 variants)
- Multivariate testing
- Variant assignment algorithms
- Statistical significance testing
- Sample size calculation
- Experiment lifecycle management
- Feature flag integration

**Example:**
```
"How do I implement A/B testing for ad creatives?"
→ Provides experiment design pattern
→ Shows variant assignment logic
→ Includes statistical significance calculation
→ Winner determination criteria
→ Integration with feature flags
```

**References:** Phase 15 (A/B Testing) specification

---

#### 9. **cmis-predictive-analytics** - Predictive Analytics Expert 🔮 (v1.1 Enhanced)
**File:** `cmis-predictive-analytics.md`

**Purpose:** Master of machine learning forecasting, time series analysis, predictive modeling, and trend detection with intelligent library discovery and zero-dependency fallbacks.

**Use when:**
- Implementing ML-based forecasting (ARIMA, Prophet, LSTM) OR statistical forecasting
- Building campaign performance prediction models
- Creating trend detection and seasonality analysis
- Designing anomaly detection with ML algorithms
- Developing automated optimization recommendations
- Calculating confidence scoring and prediction intervals

**Handles:**
- Time series forecasting (ARIMA, Prophet, LSTM neural networks, Statistical methods)
- Campaign performance prediction
- Trend analysis (linear regression, seasonality detection)
- Anomaly detection (Z-score, isolation forest, seasonal)
- Budget optimization predictions
- Creative performance forecasting
- Adaptive traffic allocation (Thompson Sampling)
- **NEW:** Statistical forecasting (no external libraries required)
- **NEW:** ML library discovery and installation guidance

**Key Features (v1.1):**
- ✅ **Intelligent library discovery** - Checks what's installed before suggesting
- ✅ **Zero-dependency statistical forecasting** - Works WITHOUT external ML libraries
- ✅ **4 forecasting patterns**: Prophet, ARIMA, LSTM, Statistical (recommended)
- ✅ **Library installation guidance** - Clear prerequisites for each pattern
- ✅ **Decision tree** - Guides choice between ML vs. statistical approaches
- ✅ Multi-method anomaly detection with library-aware recommendations
- ✅ Confidence interval calculations for all forecasting methods
- ✅ Multi-tenant prediction isolation with RLS

**Example:**
```
"Forecast campaign performance for next 30 days"
→ Discovers installed ML libraries (none found)
→ Recommends Pattern 4: Statistical Forecasting (zero dependencies)
→ Uses Linear Regression or Moving Average
→ Provides predictions with 95% confidence intervals
→ Suggests Prophet installation for advanced seasonality (optional)
→ Generates actionable recommendations
```

**Recommendation Priority:**
1. ✅ **Statistical Forecasting** - Start here (90% of use cases, zero dependencies)
2. ✅ **Prophet** - If installed and complex seasonality needed
3. ⚠️ **ARIMA** - If statistical patterns insufficient
4. ⚠️ **LSTM** - Only for advanced multi-variate forecasting

**References:** `app/Services/Analytics/`, unified_metrics table, composer.json (library discovery)

---

#### 10. **cmis-ab-testing-specialist** - A/B Testing & Experimentation Specialist 🧪
**File:** `cmis-ab-testing-specialist.md`

**Purpose:** Expert in experiment design, variant management, statistical significance testing, and winner selection based on CMIS's actual ABTestingService implementation.

**Use when:**
- Creating A/B tests with proper statistical rigor
- Implementing variant management and traffic allocation
- Calculating statistical significance (Chi-square, t-tests)
- Building winner selection and automated rollout
- Designing multivariate experiments
- Implementing adaptive traffic allocation (Multi-Armed Bandit)

**Handles:**
- Experiment creation and variant management (discovered from ABTestingService.php)
- Chi-square statistical significance testing
- Sample size calculation with power analysis
- Confidence interval calculations for conversion rates
- Winner selection and rollout automation
- Multivariate testing with Bonferroni correction
- Adaptive traffic allocation (Thompson Sampling)

**Key Features:**
- Based on actual CMIS implementation (cmis.experiments, cmis.experiment_variants)
- Chi-square test with 95% confidence (critical value: 3.841)
- Minimum detectable effect validation
- Traffic allocation strategies (equal, weighted, adaptive)
- Beta distribution sampling for Bayesian A/B testing

**Example:**
```
"Create an A/B test for ad creative variants"
→ Calculates minimum sample size for 95% confidence
→ Creates control + variant experiment
→ Tracks impressions, clicks, conversions in real-time
→ Evaluates statistical significance using Chi-square
→ Automatically selects winner when significant
→ Provides rollout recommendations
```

**References:** `ABTestingService.php`, Phase 15 (A/B Testing), cmis.experiments tables

---

#### 11. **cmis-alerts-monitoring** - Alerts & Monitoring Specialist 🚨
**File:** `cmis-alerts-monitoring.md`

**Purpose:** Specialist in real-time alerting, threshold monitoring, anomaly detection, and multi-channel notification delivery based on CMIS's AlertEvaluationService.

**Use when:**
- Implementing threshold-based alert rules
- Creating anomaly detection alerts
- Building multi-channel notification systems
- Designing escalation policies
- Setting up cooldown and rate limiting
- Creating alert acknowledgment workflows

**Handles:**
- Alert rule creation and condition evaluation (discovered from AlertEvaluationService.php)
- Condition operators (gt, gte, lt, lte, eq, ne, change_pct)
- Multi-channel delivery (email, Slack, webhook, in-app)
- Cooldown period enforcement
- Alert acknowledgment and resolution
- Escalation policies with multiple levels
- Alert templates and pre-built configurations

**Key Features:**
- Based on actual CMIS implementation (cmis.alert_rules, cmis.alert_history)
- 7 condition operators for flexible rule definition
- Cooldown periods to prevent alert spam
- Severity levels (critical, high, medium, low)
- Multi-channel notification with delivery tracking
- Percentage change detection for anomalies

**Example:**
```
"Create alert for CTR drop below 2%"
→ Creates alert rule with threshold
→ Evaluates condition against real-time metrics
→ Respects 60-minute cooldown period
→ Delivers to email, Slack, and in-app
→ Tracks delivery status and acknowledgment
→ Triggers escalation if unacknowledged
```

**References:** `AlertEvaluationService.php`, Phase 13 (Real-Time Alerts), cmis.alert_rules tables

---

#### 12. **cmis-crm-specialist** - CRM & Lead Management Expert V2.1 📇
**File:** `cmis-crm-specialist.md`

**Purpose:** Specialist in contact management, lead tracking, scoring, and pipeline management.

**Use when:**
- Building CRM features
- Implementing lead scoring
- Creating pipeline management
- Designing contact databases
- Setting up lead nurturing
- Integrating with CRMs (Salesforce, HubSpot)

**Handles:**
- Contact management
- Lead lifecycle tracking
- Lead scoring algorithms
- MQL/SQL qualification
- Pipeline stage management
- Deal tracking
- Customer segmentation
- CRM integrations

**Example:**
```
"How do I implement lead scoring?"
→ Provides scoring algorithm design
→ Shows demographic + behavioral scoring
→ Includes score decay logic
→ Threshold-based qualification
→ Testing patterns for scoring
```

**References:** Contact models, LeadController

---

#### 10. **cmis-marketing-automation** - Marketing Automation Expert V2.1 🤖
**File:** `cmis-marketing-automation.md`

**Purpose:** Specialist in workflow automation, trigger-based campaigns, and marketing automation rules.

**Use when:**
- Building workflow automation systems
- Implementing trigger-based campaigns
- Creating drip campaign sequences
- Designing state machines for workflows
- Setting up job queue patterns
- Scheduling automated tasks
- Multi-platform orchestration

**Handles:**
- Workflow state machine design
- Event-driven automation
- Drip campaign implementation
- Job classes and queue optimization
- Scheduled tasks (Laravel Scheduler)
- Conditional logic workflows
- Retry logic with exponential backoff
- Multi-platform coordination

**Key Features:**
- State machine implementation patterns
- Trigger condition design
- Job chaining strategies
- Workflow testing approaches
- Performance optimization for automation
- Error recovery patterns

**Example:**
```
"How do I create a drip campaign workflow?"
→ Provides state machine design
→ Shows time-delayed sequence implementation
→ Includes conditional branching logic
→ Job queue integration patterns
→ Testing strategies for time-dependent workflows
```

**References:** `docs/phases/planned/automation/`

---

#### 11. **cmis-ui-frontend** - UI/UX & Frontend Specialist
**File:** `cmis-ui-frontend.md`

**Purpose:** Expert in Alpine.js, Tailwind CSS, Chart.js, and Blade templates.

**Use when:**
- Building UI components
- Frontend architecture questions
- Dashboard design
- Chart.js integration
- Responsive design

**Handles:**
- Alpine.js patterns
- Tailwind utilities
- Chart.js integration
- Component design

**Example:**
```
"How do I build a campaign analytics dashboard?"
→ Alpine.js component with Chart.js integration
```

---

### 📢 Ad Campaign & Platform Advertising Agents (NEW - 2025-11-22)

**🚀 MUST READ FIRST: Platform Setup Workflow**

📖 **Complete Setup Guide:** `.claude/knowledge/PLATFORM_SETUP_WORKFLOW.md`

**Before using ANY platform agent, read the setup workflow guide!** It explains:
- ✅ **Correct Order:** What to do first (OAuth), then second (token storage), etc.
- ✅ **Database Schema:** Where data is stored (`cmis_social.social_accounts`, `cmis.organizations`)
- ✅ **Token Management:** How tokens are stored (encrypted) and retrieved
- ✅ **Multi-Tenancy:** Each organization has its own platform accounts
- ✅ **RLS Context:** How to set org context for database operations
- ✅ **Complete Example:** From OAuth connection to campaign creation

**Why this matters:**
- Each **organization** can connect multiple accounts per platform
- Tokens are stored **encrypted** in `cmis_social.social_accounts` table
- You MUST set **RLS context** before any database operation
- OAuth flow is the FIRST step before creating campaigns

---

**🌐 LIVE API DISCOVERY FEATURE:**
All platform advertising agents (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat) include:
- ✅ **WebSearch integration** - Searches for latest API documentation before implementation
- ✅ **WebFetch capability** - Fetches official API docs from platform websites
- ✅ **Dynamic API version detection** - Never assumes outdated API versions
- ✅ **Adaptive discovery protocols** - Discovers current implementation patterns from codebase
- ✅ **Up-to-date guidance** - Always checks for latest platform changes and deprecations

**How it works:**
1. Agent receives question about platform API
2. Uses WebSearch to find latest API documentation
3. Uses WebFetch to read official platform docs
4. Discovers current implementation in your codebase
5. Provides guidance based on BOTH latest docs AND your actual code
6. Never relies on hardcoded/outdated API versions

**Example:**
```
User: "How do I create a Meta campaign?"
Agent:
  1. WebSearch("Meta Ads API latest version 2024")
  2. WebFetch("developers.facebook.com/docs/marketing-api")
  3. Discovers current connector in app/Services/AdPlatforms/MetaConnector.php
  4. Provides implementation using LATEST API version (not outdated v18.0)
```

---

#### 12. **cmis-ad-campaign-analyst** - Ad Campaign Analysis Expert 📊
**File:** `cmis-ad-campaign-analyst.md`

**Purpose:** Specialist in analyzing sponsored ad campaign performance, optimization, A/B testing, and ROI/ROAS analysis across all platforms.

**Use when:**
- Analyzing campaign performance metrics
- Providing optimization recommendations
- Evaluating A/B test results
- Optimizing budget allocation
- Analyzing creative performance
- Audience targeting effectiveness
- ROI/ROAS calculations

**Handles:**
- Performance analysis across all platforms
- Statistical A/B test evaluation
- Budget optimization algorithms
- Creative performance insights
- Audience segment analysis
- Conversion tracking and attribution
- Underperforming campaign diagnosis

**Key Features:**
- Data-driven recommendations (not generic benchmarks)
- Statistical significance testing for A/B tests
- Platform-specific performance analysis
- Unified metrics integration
- Multi-tenancy aware analytics

**Example:**
```
"Analyze performance of my Meta campaigns over the last 30 days"
→ Discovers actual metrics from unified_metrics table
→ Compares to platform-specific benchmarks
→ Identifies underperforming campaigns with specific fixes
→ Provides budget reallocation recommendations
```

---

#### 13. **cmis-meta-ads-specialist** - Meta (Facebook/Instagram) Advertising Expert 📱
**File:** `cmis-meta-ads-specialist.md`

**Purpose:** Expert in Meta Ads Manager API, Facebook/Instagram campaigns, pixel tracking, and audience management.

**Use when:**
- Creating Meta/Facebook/Instagram ad campaigns
- Implementing Meta Pixel tracking
- Managing Meta audiences and lookalikes
- Dynamic Product Ads (DPA) setup
- Meta webhook integration
- Campaign optimization for Meta platforms

**Handles:**
- Meta Ads Manager API v19.0+
- Campaign objectives (AWARENESS, CONSIDERATION, CONVERSION, TRAFFIC, ENGAGEMENT)
- Meta Pixel server-side and client-side tracking
- Custom Audiences and Lookalike Audiences
- Dynamic Product Catalogs and feeds
- Meta webhook signature verification
- Business Manager setup

**Example:**
```
"Set up conversion tracking with Meta Pixel"
→ Implements PageView, AddToCart, Purchase events
→ Server-side conversion API integration
→ Event verification and debugging
```

---

#### 14. **cmis-google-ads-specialist** - Google Ads Platform Expert 🔍
**File:** `cmis-google-ads-specialist.md`

**Purpose:** Expert in Google Ads API, Search/Display/Video/Shopping/Performance Max campaigns, and Smart Bidding.

**Use when:**
- Creating Google Ads campaigns (Search, Display, Video, Shopping)
- Implementing Google Tag Manager (GTM)
- Optimizing Quality Score
- Setting up Smart Bidding strategies
- Managing Google Shopping feeds
- Performance Max campaigns

**Handles:**
- Google Ads API integration
- 6 campaign types (Search, Display, Video, Shopping, Discovery, Performance Max)
- Smart Bidding (Target CPA, Target ROAS, Maximize Conversions)
- Quality Score optimization
- Keyword research and management
- Google Shopping feed validation
- Audience targeting and RLSA

**Example:**
```
"Create a Performance Max campaign with asset groups"
→ Complete setup with headlines, descriptions, images, videos
→ Audience signals configuration
→ Budget and bidding strategy
→ Asset group management
```

---

#### 15. **cmis-tiktok-ads-specialist** - TikTok Advertising Expert 📹
**File:** `cmis-tiktok-ads-specialist.md`

**Purpose:** Expert in TikTok Ads Manager API, video ad creation, Spark Ads, and TikTok Pixel implementation.

**Use when:**
- Creating TikTok ad campaigns
- Implementing TikTok Pixel tracking
- Creating video ads with TikTok specifications
- Setting up Spark Ads (boosting organic content)
- TikTok Shopping integration
- Campaign optimization for Gen Z audience

**Handles:**
- TikTok Ads Manager API
- Campaign objectives (REACH, TRAFFIC, ENGAGEMENT, APP_PROMOTION, CONVERSION, SALES)
- Video ad specifications (9:16 vertical, 3-60s duration)
- TikTok Pixel implementation
- Spark Ads service
- TikTok Shopping integration
- Audience targeting (demographics, interests, lookalikes)

**Example:**
```
"Create a TikTok video ad campaign for app installs"
→ Video specification guidelines (1080x1920, MP4, 15-34s)
→ App install objective setup
→ Creative best practices for TikTok
→ Audience targeting optimization
```

---

#### 16. **cmis-linkedin-ads-specialist** - LinkedIn B2B Advertising Expert 💼
**File:** `cmis-linkedin-ads-specialist.md`

**Purpose:** Expert in LinkedIn Marketing API, B2B targeting, Lead Gen Forms, and LinkedIn Insight Tag.

**Use when:**
- Creating LinkedIn ad campaigns
- B2B audience targeting (job titles, companies, industries)
- Implementing LinkedIn Insight Tag
- Setting up Lead Gen Forms
- LinkedIn conversion tracking
- Professional targeting strategies

**Handles:**
- LinkedIn Marketing API
- Ad formats (Sponsored Content, Sponsored Messaging, Text Ads, Dynamic Ads)
- B2B targeting (job titles, seniority, company size, industry)
- Matched Audiences for retargeting
- Lead Gen Forms with webhook integration
- LinkedIn Insight Tag implementation
- Conversion tracking and attribution

**Example:**
```
"Set up a Lead Gen Form for B2B lead capture"
→ Custom form fields configuration
→ Webhook integration for real-time leads
→ Targeting C-level executives in tech industry
→ Lead data extraction and storage
```

---

#### 17. **cmis-twitter-ads-specialist** - Twitter/X Advertising Expert 🐦
**File:** `cmis-twitter-ads-specialist.md`

**Purpose:** Expert in Twitter Ads API (X Ads API), Promoted Tweets/Accounts/Trends, and real-time engagement campaigns.

**Use when:**
- Creating Twitter/X ad campaigns
- Promoted Tweets, Accounts, or Trends
- Implementing Twitter Pixel
- Conversation targeting
- Real-time event marketing
- Twitter Card creation

**Handles:**
- Twitter Ads API (X Ads API)
- Campaign types (Promoted Tweets, Accounts, Trends)
- Twitter Pixel implementation
- Audience targeting (keywords, followers, interests)
- Tailored Audiences and lookalikes
- Twitter Card formats (Summary, Player, App)
- Video ads on Twitter
- Real-time engagement metrics

**Example:**
```
"Create a Promoted Tweet campaign for product launch"
→ Tweet engagement objectives
→ Keyword and interest targeting
→ Twitter Card with image/video
→ Real-time metrics tracking
```

---

#### 18. **cmis-snapchat-ads-specialist** - Snapchat Advertising Expert 👻
**File:** `cmis-snapchat-ads-specialist.md`

**Purpose:** Expert in Snapchat Marketing API, vertical video ads, AR Lenses, and Gen Z targeting strategies.

**Use when:**
- Creating Snapchat ad campaigns
- Implementing Snap Pixel
- Creating vertical video ads
- AR Lens and Filter campaigns
- Instant Forms for lead generation
- Gen Z audience targeting

**Handles:**
- Snapchat Marketing API
- Ad formats (Snap Ads, Story Ads, Collection Ads, AR Lenses, Filters)
- Snap Pixel implementation
- Audience targeting (Snap Lifestyle Categories, Custom Audiences, Lookalikes)
- Instant Forms integration
- Vertical video best practices (9:16)
- Campaign objectives (AWARENESS, APP_INSTALLS, TRAFFIC, CONVERSIONS, LEAD_GENERATION)

**Example:**
```
"Create a vertical video ad campaign for Gen Z audience"
→ 9:16 vertical video specifications
→ Snap Lifestyle Categories targeting
→ Swipe-Up action tracking
→ Instant Forms for lead capture
```

---

#### 19. **cmis-social-publishing** - Social Media & Publishing Expert
**File:** `cmis-social-publishing.md`

**Purpose:** Expert in social media scheduling, publishing, and engagement tracking.

**Use when:**
- Social post scheduling
- Multi-platform publishing
- Engagement metrics
- Content calendar
- Best time optimization

**Handles:**
- PublishingService
- Multi-platform posting
- Schedule management
- Metrics tracking
- AI-powered timing

**Example:**
```
"How do I implement scheduled posting to Instagram?"
→ Complete publishing workflow with jobs and metrics
```

---

#### 13. **cmis-content-manager** - Content Management Expert V2.1 📝
**File:** `cmis-content-manager.md`

**Purpose:** Specialist in content planning, creative asset management, template systems, and approval workflows.

**Use when:**
- Building content planning features
- Implementing asset management systems
- Creating template systems
- Designing approval workflows
- Managing content calendars
- Organizing creative assets
- Implementing version control for content

**Handles:**
- Content calendar design and management
- Asset library organization and search
- Template inheritance and rendering
- Multi-step approval workflows
- Version control and revision tracking
- Media optimization
- Content recycling and repurposing

**Key Features:**
- Editorial calendar patterns
- Approval state machine design
- Template variable substitution
- Asset metadata management
- Multi-tenant content isolation

**Example:**
```
"How do I implement a multi-step approval workflow?"
→ Provides state machine design (draft → review → approved → published)
→ Shows role-based approval logic
→ Includes notification integration
→ Database schema for approval tracking
→ Frontend UI patterns for approval interface
```

**References:** `ContentPlanService`, `ContentLibraryService`, Phase 6 docs

---

#### 14. **cmis-enterprise-features** - Enterprise Features Expert V2.1 🏢
**File:** `cmis-enterprise-features.md`

**Purpose:** Specialist in performance monitoring, enterprise alerts, advanced reporting, and production operations.

**Use when:**
- Implementing performance monitoring
- Setting up enterprise alert systems
- Building advanced reporting features
- Creating monitoring dashboards
- Designing report scheduling
- Implementing notification systems

**Handles:**
- Performance metric collection and analysis
- Alert rule design and evaluation
- Scheduled report generation
- Email/Slack notification integration
- Real-time monitoring dashboards
- Multi-tenant monitoring
- Alert lifecycle management

**Key Features:**
- Alert rule engine implementation
- Report generation pipelines
- Dashboard real-time updates
- Notification routing logic
- Performance profiling techniques

**Example:**
```
"How do I create CPU usage alerts?"
→ Provides alert rule definition pattern
→ Shows threshold-based evaluation logic
→ Includes severity classification
→ Notification delivery workflow
→ Alert acknowledgment tracking
```

**References:** Phase 13 (Real-Time Alerts), Phase 12 (Scheduled Reports)

---

#### 15. **cmis-rbac-specialist** - RBAC & Authorization Expert V2.1 🔐
**File:** `cmis-rbac-specialist.md`

**Purpose:** Specialist in role-based access control, permissions, Laravel policies, and authorization flows.

**Use when:**
- Implementing RBAC features
- Creating Laravel policies
- Designing permission systems
- Building authorization flows
- Implementing permission caching
- Debugging authorization issues

**Handles:**
- 2-level permission system (org + user)
- Laravel policy implementation
- Permission caching strategies
- Authorization middleware
- Role hierarchy design
- Multi-tenant authorization

**Key Features:**
- Policy implementation patterns
- Permission checking optimization
- Cache invalidation strategies
- Multi-tenant permission isolation
- Role assignment workflows

**Example:**
```
"How do I implement a policy for campaign updates?"
→ Provides BasePolicy extension pattern
→ Shows ownership and org verification
→ Includes permission checking logic
→ Caching integration
→ Testing patterns for policies
```

**References:** 12 Laravel policies, Permission/Role models, TODO report (95% complete)

---

### 🔐 Security & Compliance Agents

#### 16. **cmis-compliance-security** - Compliance & Security Auditing Expert V2.1 🔒
**File:** `cmis-compliance-security.md`

**Purpose:** Specialist in GDPR compliance, data privacy, security auditing, and regulatory requirements.

**Use when:**
- Implementing GDPR compliance features
- Creating audit trail systems
- Designing data retention policies
- Implementing right to be forgotten
- Building consent management
- Conducting security audits
- Creating privacy controls

**Handles:**
- GDPR/CCPA compliance
- Audit trail implementation
- Data retention and cleanup
- User data deletion workflows
- Data export (portability)
- Consent tracking
- Security vulnerability scanning
- Privacy by design

**Example:**
```
"How do I implement GDPR right to be forgotten?"
→ Provides user data identification strategy
→ Shows cascading deletion logic
→ Includes verification and reporting
→ Soft delete vs hard delete guidance
→ Compliance documentation requirements
```

**References:** GDPR requirements, Laravel security best practices

---

### 🎨 Code Quality & Standardization Agents (NEW - 2025-11-22)

#### 17. **cmis-model-architect** - Model Architecture Specialist
**File:** `cmis-model-architect.md`

**Purpose:** Ensures all models follow BaseModel pattern and standardized trait composition.

**Use when:**
- Auditing models for BaseModel compliance
- Migrating legacy models (Model → BaseModel)
- Guiding trait composition (HasOrganization, SoftDeletes)
- Standardizing relationship patterns
- Detecting model code smells

**Handles:**
- BaseModel adoption (282+ models, target 100%)
- HasOrganization trait usage (99+ models)
- Trait composition guidelines
- Relationship pattern standardization
- Model health checks and audits

**Key Achievements:**
- 282+ models extend BaseModel (100%+ adoption)
- Zero duplicate UUID generation code
- Consistent org() relationships via trait

**Example:**
```
"Audit all models for BaseModel compliance"
→ Finds 3 models extending Model directly
→ Provides migration workflow for each
→ Reports on HasOrganization trait coverage
→ Health report: docs/active/analysis/model-architecture-audit.md
```

---

#### 18. **cmis-data-consolidation** - Data Structure Consolidation Specialist
**File:** `cmis-data-consolidation.md`

**Purpose:** Identifies and eliminates duplicate data structures, preventing table proliferation.

**Use when:**
- Detecting duplicate table structures
- Consolidating similar tables into unified tables
- Designing polymorphic data patterns
- Preventing new table duplication
- Monitoring unified table health

**Handles:**
- Table consolidation strategies
- Polymorphic table design (unified_metrics, social_posts)
- JSONB for platform-specific data
- Data migration workflows
- Prevention protocols

**Key Achievements:**
- 10 metric tables → 1 unified_metrics (90% reduction)
- 5 social post tables → 1 social_posts (80% reduction)
- Total: 16 tables → 2 tables (87.5% reduction)
- Saved 3,500+ lines of duplicate code

**Example:**
```
"Analyze tables for consolidation opportunities"
→ Discovers 8 similar metric tables across platforms
→ Designs unified_metrics with polymorphic pattern
→ Provides migration plan and data consolidation workflow
→ Report: docs/active/analysis/data-consolidation-opportunities.md
```

---

#### 19. **laravel-controller-standardization** - Controller Response Standardization Specialist
**File:** `laravel-controller-standardization.md`

**Purpose:** Drives ApiResponse trait adoption from 75% to 100%, ensuring API consistency.

**Use when:**
- Auditing controllers for ApiResponse usage
- Migrating controllers to standardized responses
- Detecting manual response()->json() patterns
- Standardizing API response messages
- Enforcing API consistency

**Handles:**
- ApiResponse trait migration workflows
- Response pattern standardization
- Manual response detection and replacement
- API consistency enforcement
- Progress tracking to 100%

**Key Achievements:**
- 111 controllers using ApiResponse (75% adoption)
- Target: 100% (148 total controllers)
- Standardized response structure across all APIs
- Saved 800+ lines of duplicate response code

**Example:**
```
"Migrate CampaignController to use ApiResponse trait"
→ Detects 12 manual response()->json() calls
→ Adds ApiResponse trait to controller
→ Replaces all manual responses with trait methods
→ Standardizes response messages
→ Tests all endpoints for consistency
```

---

### 🏗️ Updated Laravel Agents (CMIS-Aware)

#### 20. **laravel-architect** - CMIS-Updated
**Purpose:** High-level architecture review with CMIS context

**Now includes:**
- RLS multi-tenancy patterns
- 12-schema organization
- CMIS-specific design patterns

---

#### 21. **laravel-tech-lead** - CMIS-Updated
**Purpose:** Code review and implementation guidance

**Now includes:**
- CMIS best practices
- Multi-tenancy in code reviews
- Platform integration patterns

---

#### 22. **laravel-code-quality** - CMIS-Updated
**Purpose:** Code quality and refactoring

**Now includes:**
- CMIS-specific code smells
- Repository pattern enforcement
- Service layer best practices

---

#### 23. **laravel-security** - CMIS-Updated
**Purpose:** Security audit and compliance

**Now includes:**
- RLS security audit
- Platform OAuth security
- CMIS permission system

---

#### 24. **laravel-performance** - CMIS-Updated
**Purpose:** Performance optimization

**Now includes:**
- pgvector optimization
- Multi-schema query performance
- RLS performance considerations

---

#### 25. **laravel-db-architect** - Already CMIS-Specific
**Purpose:** Database architecture and migrations

**Specializes in:**
- PostgreSQL + pgvector
- Multi-schema migrations
- RLS policy implementation

---

#### 26. **laravel-testing** - CMIS-Updated
**Purpose:** Testing strategy and coverage

**Now includes:**
- Multi-tenancy testing patterns
- Platform integration mocking
- AI feature testing

---

#### 27. **laravel-devops** - CMIS-Updated
**Purpose:** DevOps and CI/CD

**Now includes:**
- PostgreSQL deployment
- pgvector setup
- Platform credential management

---

#### 28. **laravel-api-design** - CMIS-Updated
**Purpose:** API design and consistency

**Now includes:**
- Org-scoped routing patterns
- Platform webhook endpoints
- AI rate-limited endpoints

---

#### 29. **laravel-auditor** - CMIS-Updated
**Purpose:** Comprehensive system audit

**Now includes:**
- CMIS-specific audit checklist
- Multi-tenancy verification
- Platform integration health

---

#### 30. **laravel-documentation** - CMIS-Updated
**Purpose:** Documentation and knowledge base

**Now includes:**
- CMIS domain documentation
- Multi-tenancy guides
- Platform integration docs

---

## 📊 AGENT SELECTION GUIDE

### By Task Type

| Task | Primary Agent | Supporting Agents |
|------|--------------|-------------------|
| **Understanding CMIS** | cmis-context-awareness | - |
| **Multi-Tenancy Issues** | cmis-multi-tenancy | laravel-db-architect |
| **Platform Integration** | cmis-platform-integration | laravel-security |
| **AI/Semantic Search** | cmis-ai-semantic | laravel-performance |
| **Campaign Features** | cmis-campaign-expert | cmis-context-awareness |
| **Frontend/UI** | cmis-ui-frontend | - |
| **Social Media** | cmis-social-publishing | cmis-platform-integration |
| **Model Architecture** | cmis-model-architect | laravel-code-quality |
| **Data Consolidation** | cmis-data-consolidation | laravel-db-architect |
| **Controller Standardization** | laravel-controller-standardization | laravel-api-design |
| **Trait Composition** | cmis-model-architect | laravel-code-quality |
| **API Response Consistency** | laravel-controller-standardization | laravel-api-design |
| **Architecture Review** | laravel-architect | cmis-context-awareness |
| **Code Review** | laravel-tech-lead | laravel-code-quality |
| **Performance** | laravel-performance | cmis-ai-semantic |
| **Security Audit** | laravel-security | cmis-multi-tenancy |
| **Database** | laravel-db-architect | cmis-multi-tenancy |
| **Testing** | laravel-testing | cmis-context-awareness |
| **Documentation Management** | cmis-doc-organizer | laravel-documentation |
| **App Idea Feasibility** | app-feasibility-researcher | - |
| **Predictive Analytics** | cmis-predictive-analytics | cmis-analytics-expert |
| **ML Forecasting** | cmis-predictive-analytics | cmis-analytics-expert |
| **A/B Testing** | cmis-ab-testing-specialist | cmis-experimentation |
| **Statistical Analysis** | cmis-ab-testing-specialist | cmis-analytics-expert |
| **Alerts & Monitoring** | cmis-alerts-monitoring | cmis-enterprise-features |
| **Notification Systems** | cmis-alerts-monitoring | cmis-marketing-automation |
| **Complex Multi-Domain** | cmis-orchestrator | [Multiple] |

---

## 🔄 TYPICAL WORKFLOWS

### Workflow 1: Adding New Feature

```
1. cmis-orchestrator → Analyzes requirement
2. cmis-context-awareness → Identifies domain and location
3. [Domain-specific agent] → Implements feature
4. laravel-tech-lead → Reviews implementation
5. laravel-testing → Creates tests
```

### Workflow 2: Debugging Multi-Tenancy Issue

```
1. cmis-multi-tenancy → Diagnoses RLS problem
2. laravel-db-architect → Checks database policies
3. laravel-security → Verifies authorization
4. laravel-testing → Adds isolation tests
```

### Workflow 3: Platform Integration

```
1. cmis-platform-integration → Implements OAuth
2. laravel-security → Secures credentials
3. cmis-social-publishing → Adds publishing logic
4. laravel-testing → Tests integration
```

### Workflow 4: AI Feature

```
1. cmis-ai-semantic → Implements embeddings
2. laravel-performance → Optimizes queries
3. cmis-ui-frontend → Builds interface
4. laravel-testing → Tests AI operations
```

### Workflow 5: Documentation Organization

```
1. cmis-doc-organizer → Scans and classifies documentation
2. cmis-doc-organizer → Moves files to organized structure
3. cmis-doc-organizer → Archives old/completed documents
4. cmis-doc-organizer → Creates comprehensive index
5. [Regular maintenance] → Run after major sessions
```

---

## 💡 USAGE EXAMPLES

### Example 1: Simple Question

**User:** "How does multi-tenancy work in CMIS?"

**Agent to use:** `cmis-context-awareness` or `cmis-multi-tenancy`

**Why:** Both can explain, but multi-tenancy agent gives deeper technical details.

---

### Example 2: Implementation Task

**User:** "I need to add semantic search to campaigns"

**Primary agent:** `cmis-ai-semantic`
**Supporting:** `cmis-campaign-expert`, `cmis-context-awareness`

**Why:** AI agent implements search, Campaign agent provides domain context.

---

### Example 3: Complex Feature

**User:** "Build a dashboard that shows AI-powered social media recommendations"

**Orchestrator coordinates:**
1. `cmis-social-publishing` - Data source
2. `cmis-ai-semantic` - AI recommendations
3. `cmis-ui-frontend` - Dashboard UI
4. `laravel-performance` - Optimization
5. `laravel-testing` - Test strategy

---

### Example 4: Documentation Chaos

**User:** "I have 70+ markdown files in my root directory and can't find anything"

**Agent to use:** `cmis-doc-organizer`

**Why:** Specialized in organizing, archiving, and indexing documentation.

**Result:**
- Clean root directory
- Organized docs/ structure with active/ and archive/
- Comprehensive documentation index
- Old documents properly archived

---

### Example 5: App Idea Evaluation

**User:** "Should we build a real-time collaboration tool for campaign planning?"

**Agent to use:** `app-feasibility-researcher`

**Why:** Comprehensive feasibility analysis with market research.

**Process:**
1. Analyzes logic and viability
2. Researches competitors (Figma, Miro, Notion, etc.)
3. Discovers implementation patterns
4. Suggests alternatives (integrate with existing tools)
5. Assesses completability and risks
6. Creates detailed feasibility report

**Result:**
- Feasibility score: 6/10
- Recommendation: Don't build standalone, integrate with existing tools
- Found 20+ similar solutions already exist
- Suggested better approach: Build as Figma/Miro plugin
- Saved months of development on non-viable approach

---

## 📚 LEARNING RESOURCES

### Essential Reading Order

1. **Start here:** `.claude/CMIS_PROJECT_KNOWLEDGE.md`
2. **Then:** `cmis-context-awareness.md`
3. **Then:** `cmis-multi-tenancy.md`
4. **Then:** Domain-specific agents as needed

### Key Concepts to Master

1. **PostgreSQL RLS** - Foundation of CMIS
2. **12-Schema Architecture** - Database organization
3. **Platform Integration Factory** - Multi-platform pattern
4. **Campaign Context System** - EAV flexibility
5. **pgvector Semantic Search** - AI capabilities

---

## 🚨 CRITICAL RULES

### For ALL Agents

✅ **ALWAYS:**
- Consult `CMIS_PROJECT_KNOWLEDGE.md`
- Respect RLS and org context
- Use schema-qualified table names
- Check rate limits for AI operations
- Provide CMIS-specific examples
- **CREATE DOCUMENTATION after ANY code changes** (see below)

❌ **NEVER:**
- Bypass RLS with manual org filtering
- Ignore multi-tenancy implications
- Give generic Laravel advice for CMIS-specific patterns
- Hard-delete records (use soft deletes)
- Expose secrets or credentials
- **Skip documentation for code changes**

### 📝 MANDATORY: Documentation After Code Changes (NEW - 2025-12-04)

**ALL agents MUST create documentation in `docs/` after ANY codebase editing.**

**📖 Complete Guide:** `.claude/agents/_shared/documentation-requirements.md`

**Quick Reference:**

| Change Type | Documentation Location |
|-------------|------------------------|
| New feature | `docs/features/<feature-name>.md` |
| Bug fix | `docs/fixes/<date>-<description>.md` |
| Refactoring | `docs/refactoring/<date>-<description>.md` |
| API changes | `docs/api/<endpoint-or-service>.md` |
| Database changes | `docs/database/<migration-description>.md` |
| Phase completion | `docs/phases/completed/phase-<N>/<summary>.md` |

**Workflow:**
1. Make code changes
2. Verify changes work (tests, logs)
3. Create documentation in appropriate `docs/` location
4. Commit code + documentation together

**DO NOT:**
- Wait for user to ask for documentation
- Skip documentation for "small" changes
- Commit code without documentation

---

## 📝 AGENT DEVELOPMENT

### Adding New Agent

1. Create `.md` file in `.claude/agents/`
2. Add YAML frontmatter with name, description, model
3. Reference `CMIS_PROJECT_KNOWLEDGE.md`
4. Provide CMIS-specific examples
5. Update this README
6. Test with real scenarios

### Updating Existing Agent

1. Read current agent file
2. Identify gaps in CMIS knowledge
3. Add CMIS-specific sections
4. Provide project-specific examples
5. Update README if capabilities changed

---

## 🔧 MAINTENANCE

**Review Schedule:**
- **Weekly:** Update knowledge base with new features
- **Monthly:** Review agent effectiveness
- **Quarterly:** Major agent capability updates

**Version Control:**
- All agents are version controlled in git
- Changes are documented in commit messages
- Agent updates trigger documentation updates

---

## 📊 METRICS & SUCCESS

**Agent Effectiveness Measured By:**
- Accuracy of routing (Orchestrator)
- Correctness of technical guidance
- Adherence to CMIS patterns
- User satisfaction

**Success Indicators:**
- Users get answers without multiple agent tries
- Solutions respect CMIS architecture
- Multi-tenancy never broken
- Code quality maintained

---

## 🆘 GETTING HELP

**If you're unsure which agent to use:**
→ Start with **cmis-orchestrator**

**If you need general understanding:**
→ Use **cmis-context-awareness**

**If you have a critical issue:**
→ Use domain-specific agent directly

**If you need multi-domain coordination:**
→ Let **cmis-orchestrator** coordinate

---

**Remember:** These agents are NOT generic. They are specialists trained on CMIS's unique architecture, patterns, and business domains. Use them wisely!

**Project Status:** 55-60% Complete - Phase 2-3 In Progress
**Latest Milestones:** Social Publishing, Profile Management, Timezone support, Alpine.js optimization
**Next Phases:** Complete AI Analytics (Phase 3), New test suite creation, Ad Campaign Orchestration

---

**Created:** 2025-11-18
**Framework Version:** 2.0 - CMIS-Specific
**Total Lines of Agent Knowledge:** 15,000+ lines
