# CMIS AI Agents Framework
## Specialized AI Agents for CMIS (Cognitive Marketing Information System)

**Last Updated:** 2025-11-18
**Project:** CMIS - Campaign Management & Integration System
**Version:** 2.0 - CMIS-Specific Agents

---

## 🎯 OVERVIEW

This directory contains **specialized AI agents** custom-built for the CMIS project. Unlike generic Laravel agents, these agents have deep knowledge of:

- CMIS's unique PostgreSQL RLS-based multi-tenancy
- 12-schema database architecture (189 tables)
- Platform integrations (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat)
- AI-powered semantic search via pgvector
- Campaign Context System (EAV pattern)
- Social media management and publishing
- Real-time analytics and performance tracking

**Total Agents:** 21 specialized agents
**Project Knowledge Base:** `.claude/CMIS_PROJECT_KNOWLEDGE.md`

---

## 🤖 CMIS-SPECIFIC AGENTS

### 📁 Utility Agents

#### **cmis-doc-organizer** - Documentation Organization Specialist 🆕
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

**Purpose:** Expert in integrating Meta, Google, TikTok, LinkedIn, Twitter, Snapchat.

**Use when:**
- OAuth flow issues
- Webhook not working
- Token refresh failing
- Adding new platform
- Sync job problems

**Handles:**
- AdPlatformFactory pattern
- Webhook signature verification
- Token management
- Data synchronization

**Example:**
```
"Meta webhook verification failing"
→ Diagnoses signature verification, provides fix
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

#### 7. **cmis-ui-frontend** - UI/UX & Frontend Specialist
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

#### 8. **cmis-social-publishing** - Social Media & Publishing Expert
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

### 🏗️ Updated Laravel Agents (CMIS-Aware)

#### 9. **laravel-architect** - CMIS-Updated
**Purpose:** High-level architecture review with CMIS context

**Now includes:**
- RLS multi-tenancy patterns
- 12-schema organization
- CMIS-specific design patterns

---

#### 10. **laravel-tech-lead** - CMIS-Updated
**Purpose:** Code review and implementation guidance

**Now includes:**
- CMIS best practices
- Multi-tenancy in code reviews
- Platform integration patterns

---

#### 11. **laravel-code-quality** - CMIS-Updated
**Purpose:** Code quality and refactoring

**Now includes:**
- CMIS-specific code smells
- Repository pattern enforcement
- Service layer best practices

---

#### 12. **laravel-security** - CMIS-Updated
**Purpose:** Security audit and compliance

**Now includes:**
- RLS security audit
- Platform OAuth security
- CMIS permission system

---

#### 13. **laravel-performance** - CMIS-Updated
**Purpose:** Performance optimization

**Now includes:**
- pgvector optimization
- Multi-schema query performance
- RLS performance considerations

---

#### 14. **laravel-db-architect** - Already CMIS-Specific
**Purpose:** Database architecture and migrations

**Specializes in:**
- PostgreSQL + pgvector
- Multi-schema migrations
- RLS policy implementation

---

#### 15. **laravel-testing** - CMIS-Updated
**Purpose:** Testing strategy and coverage

**Now includes:**
- Multi-tenancy testing patterns
- Platform integration mocking
- AI feature testing

---

#### 16. **laravel-devops** - CMIS-Updated
**Purpose:** DevOps and CI/CD

**Now includes:**
- PostgreSQL deployment
- pgvector setup
- Platform credential management

---

#### 17. **laravel-api-design** - CMIS-Updated
**Purpose:** API design and consistency

**Now includes:**
- Org-scoped routing patterns
- Platform webhook endpoints
- AI rate-limited endpoints

---

#### 18. **laravel-auditor** - CMIS-Updated
**Purpose:** Comprehensive system audit

**Now includes:**
- CMIS-specific audit checklist
- Multi-tenancy verification
- Platform integration health

---

#### 19. **laravel-documentation** - CMIS-Updated
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
| **Architecture Review** | laravel-architect | cmis-context-awareness |
| **Code Review** | laravel-tech-lead | laravel-code-quality |
| **Performance** | laravel-performance | cmis-ai-semantic |
| **Security Audit** | laravel-security | cmis-multi-tenancy |
| **Database** | laravel-db-architect | cmis-multi-tenancy |
| **Testing** | laravel-testing | cmis-context-awareness |
| **Documentation Management** | cmis-doc-organizer | laravel-documentation |
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

❌ **NEVER:**
- Bypass RLS with manual org filtering
- Ignore multi-tenancy implications
- Give generic Laravel advice for CMIS-specific patterns
- Hard-delete records (use soft deletes)
- Expose secrets or credentials

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

**Project Status:** 49% Complete - Actively Developing
**Next Phases:** AI Analytics (Phase 3), Ad Campaigns (Phase 4)

---

**Created:** 2025-11-18
**Framework Version:** 2.0 - CMIS-Specific
**Total Lines of Agent Knowledge:** 15,000+ lines
