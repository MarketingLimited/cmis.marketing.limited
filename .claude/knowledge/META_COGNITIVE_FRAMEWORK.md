# Meta-Cognitive Framework for CMIS AI Agents
## Teaching Agents How to Learn, Not What to Know

**Version:** 2.0
**Last Updated:** 2025-11-18
**Purpose:** Revolutionary shift from literal documentation to adaptive intelligence
**Status:** ACTIVE - All agents MUST use this framework

---

## 🎯 Core Paradigm Shift

### The Old Way (Brittle)

```markdown
❌ "CMIS has 189 tables across 12 schemas"
❌ "Campaign model is at app/Models/Core/Campaign.php"
❌ "Frontend uses Alpine.js 3.13.5"
❌ "There are 10 supported channels"
```

**Problem:** This becomes outdated the moment code changes.

### The New Way (Adaptive)

```markdown
✅ "To discover current table count: Query information_schema"
✅ "To find Campaign model: Use Laravel conventions + directory exploration"
✅ "To identify frontend framework: Check package.json dependencies"
✅ "To list supported channels: Query cmis.channels table"
```

**Benefit:** Always accurate, regardless of code evolution.

---

## 🧠 The Three Laws of Adaptive Intelligence

### Law 1: Discovery Over Documentation

**Principle:** Never state facts that can become outdated. Instead, teach how to discover current facts.

**Application:**
```markdown
Instead of: "The database has X tables"
Provide: "Execute this query to count tables: SELECT COUNT(*)..."

Instead of: "This file is located at path/to/file.php"
Provide: "Search for the file using: find app -name 'FileName.php'"

Instead of: "Current Laravel version is X"
Provide: "Check version with: composer show laravel/framework"
```

### Law 2: Patterns Over Examples

**Principle:** Teach architectural patterns that can be recognized and applied, not specific code examples.

**Application:**
```markdown
Instead of: [Shows exact controller code]
Provide: "Laravel controllers follow this pattern:
          1. Dependency injection in constructor
          2. Type-hinted method parameters
          3. Return Response objects
          Examine existing controllers to see current style"

Instead of: [Shows specific migration]
Provide: "CMIS migrations follow these conventions:
          1. Schema-qualified table names (cmis.*)
          2. UUID primary keys
          3. org_id for multi-tenancy
          4. Soft deletes with deleted_by
          Look at recent migrations for current patterns"
```

### Law 3: Inference Over Assumption

**Principle:** When uncertain, infer from codebase rather than assume from documentation.

**Application:**
```markdown
If documentation says X but code suggests Y:
1. Trust the code
2. Acknowledge the discrepancy
3. Recommend documentation update
4. Proceed with code-based understanding

Example:
"The documentation mentions Alpine.js, but package.json shows Vue 3.
 I'll provide guidance based on Vue 3, which is the active framework."
```

---

## 🔍 Discovery Protocol Framework

### The Five-Step Discovery Process

For ANY question about the CMIS codebase, follow this process:

```
┌─────────────────────────────────────────────┐
│ Step 1: IDENTIFY THE DOMAIN                │
│ What aspect of CMIS is this question about? │
└───────────────┬─────────────────────────────┘
                │
┌───────────────▼─────────────────────────────┐
│ Step 2: CHOOSE DISCOVERY METHOD             │
│ How can I programmatically find the answer? │
└───────────────┬─────────────────────────────┘
                │
┌───────────────▼─────────────────────────────┐
│ Step 3: EXECUTE DISCOVERY                   │
│ Run commands, queries, or file explorations │
└───────────────┬─────────────────────────────┘
                │
┌───────────────▼─────────────────────────────┐
│ Step 4: ANALYZE RESULTS                     │
│ What patterns emerge from the data?         │
└───────────────┬─────────────────────────────┘
                │
┌───────────────▼─────────────────────────────┐
│ Step 5: APPLY UNDERSTANDING                 │
│ How does this inform my recommendation?     │
└─────────────────────────────────────────────┘
```

### Example Application

**Question:** "How should I query campaigns for an organization?"

**Step 1: Identify Domain**
- Domain: Data Access + Multi-Tenancy
- Relevant concepts: PostgreSQL RLS, org context, Campaign model

**Step 2: Choose Discovery Method**
- Method 1: Database schema examination
- Method 2: Middleware analysis
- Method 3: Model inspection
- Method 4: Repository pattern check

**Step 3: Execute Discovery**

```bash
# Check if SetDatabaseContext middleware exists
ls -la app/Http/Middleware/ | grep -i context

# Examine Campaign model structure
cat app/Models/Core/Campaign.php | grep -A 5 "class Campaign"

# Check for repository pattern
ls -la app/Repositories/ | grep -i campaign

# Query database for RLS policies
psql -c "SELECT policyname FROM pg_policies WHERE tablename = 'campaigns'"
```

**Step 4: Analyze Results**
- ✅ Middleware exists: SetDatabaseContext.php
- ✅ Campaign model exists
- ✅ Repository exists: CampaignRepository
- ✅ RLS policies active on campaigns table

**Step 5: Apply Understanding**

```markdown
Based on discovery, the recommended approach is:

1. Rely on RLS (Row-Level Security)
   - Context is set automatically by middleware
   - Never manually filter by org_id

2. Use Repository Pattern
   - CampaignRepository exists with proper methods
   - Dependency inject the repository
   - Call repository methods instead of direct Eloquent

Example:
```php
// ✅ CORRECT
public function __construct(
    private CampaignRepository $campaigns
) {}

public function index() {
    // RLS automatically filters by org
    $campaigns = $this->campaigns->findAll();
}

// ❌ WRONG
$campaigns = Campaign::where('org_id', $orgId)->get();
```
```

---

## 🎓 Domain-Specific Discovery Patterns

### Pattern 1: Database Schema Discovery

**When to use:** Questions about table structure, relationships, constraints

**Discovery Commands:**

```sql
-- List all CMIS schemas
SELECT schema_name
FROM information_schema.schemata
WHERE schema_name LIKE 'cmis%';

-- Count tables per schema
SELECT table_schema, COUNT(*) as table_count
FROM information_schema.tables
WHERE table_schema LIKE 'cmis%'
GROUP BY table_schema;

-- Examine specific table structure
\d cmis.campaigns

-- Find foreign key relationships
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table,
    ccu.column_name AS foreign_column
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_schema = 'cmis';

-- Discover RLS policies
SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual
FROM pg_policies
WHERE schemaname LIKE 'cmis%';

-- Find check constraints (enums, validations)
SELECT
    conname as constraint_name,
    conrelid::regclass as table_name,
    pg_get_constraintdef(oid) as definition
FROM pg_constraint
WHERE contype = 'c'  -- check constraint
  AND connamespace = 'cmis'::regnamespace;
```

**Pattern Recognition:**
1. Schema organization reflects domain boundaries
2. UUID primary keys indicate distributed system design
3. org_id + RLS = multi-tenant architecture
4. deleted_at + deleted_by = soft delete pattern
5. jsonb columns = flexible schema areas

### Pattern 2: Laravel Conventions Discovery

**When to use:** Questions about code organization, naming, structure

**Discovery Commands:**

```bash
# Find all models and their organization
find app/Models -type d -maxdepth 2

# Count models per domain
find app/Models -name "*.php" -type f | xargs dirname | sort | uniq -c

# Discover service layer organization
ls -la app/Services/

# Find all repositories
find app/Repositories -name "*Repository.php"

# Check for middleware
ls -la app/Http/Middleware/

# Examine route organization
ls -la routes/

# Check controller structure
find app/Http/Controllers -type d

# Discover current Laravel version
composer show laravel/framework | grep versions

# Check PHP version
php -v

# Find all migrations
ls -la database/migrations/ | wc -l

# Check recent migrations for current patterns
ls -t database/migrations/ | head -5
```

**Pattern Recognition:**
1. app/Models/{Domain}/ = Domain-driven model organization
2. app/Services/{Domain}/ = Service layer by domain
3. app/Repositories/{Domain}/ = Repository pattern implementation
4. SetDatabaseContext middleware = RLS context management
5. UUID factory traits = distributed ID generation

### Pattern 3: Technology Stack Discovery

**When to use:** Questions about frontend framework, dependencies, versions

**Discovery Commands:**

```bash
# Check frontend dependencies
cat package.json | grep -A 20 "dependencies"

# Find frontend framework
cat resources/js/app.js | head -30

# Check build tool
cat package.json | grep -i vite

# Examine CSS framework
cat resources/css/app.css | head -10

# PHP dependencies
cat composer.json | grep -A 30 "require"

# Check testing framework
cat composer.json | grep -i phpunit

# Queue driver
cat .env.example | grep QUEUE_CONNECTION

# Cache driver
cat .env.example | grep CACHE_DRIVER

# Database driver and version
cat .env.example | grep DB_
```

**Pattern Recognition:**
1. Vite + Alpine.js + Tailwind = Modern Laravel frontend stack
2. Redis for queue & cache = High-performance setup
3. PostgreSQL with pgvector = AI-powered database
4. Sanctum = API authentication
5. PHPUnit = Testing framework

### Pattern 4: Feature Implementation Discovery

**When to use:** Questions about "How does X work?" or "Where is Y implemented?"

**Discovery Commands:**

```bash
# Search for feature by keyword
grep -r "keyword" app/ --include="*.php"

# Find service class for feature
find app/Services -name "*Feature*Service.php"

# Locate controller handling feature
find app/Http/Controllers -name "*Feature*Controller.php"

# Find related models
find app/Models -name "*Feature*.php"

# Check for related jobs
find app/Jobs -name "*Feature*.php"

# Look for events
find app/Events -name "*Feature*.php"

# Search routes
cat routes/api.php | grep -i "feature"

# Find related migrations
ls database/migrations/ | grep -i "feature"
```

**Pattern Recognition:**
1. Service classes contain business logic
2. Controllers orchestrate services
3. Repositories handle data access
4. Jobs handle async operations
5. Events enable decoupling

### Pattern 5: AI & Embeddings Discovery

**When to use:** Questions about AI features, vector search, embeddings

**Discovery Commands:**

```sql
-- Check pgvector extension
SELECT * FROM pg_extension WHERE extname = 'vector';

-- Find tables with vector columns
SELECT table_schema, table_name, column_name
FROM information_schema.columns
WHERE data_type = 'USER-DEFINED'
  AND udt_name = 'vector';

-- Check embedding cache
SELECT COUNT(*) FROM cmis_knowledge.embeddings_cache;

-- Examine embedding dimensions
SELECT
    DISTINCT vector_dims(embedding) as dimensions,
    model_version
FROM cmis_knowledge.embeddings_cache;

-- Check AI service configuration
```

```bash
# Find AI services
find app/Services -name "*AI*" -o -name "*Embedding*" -o -name "*Semantic*"

# Check Gemini integration
grep -r "Gemini" app/Services/ --include="*.php"

# Find vector-related routes
grep -r "vector\|embedding" routes/

# Check rate limiting config
cat config/services.php | grep -A 10 "gemini"
```

**Pattern Recognition:**
1. pgvector extension = Semantic search capability
2. 768-dimensional vectors = Google Gemini embeddings
3. EmbeddingsCache table = Performance optimization
4. Rate limiting = API quota management
5. Async jobs = Background embedding generation

---

## 🔧 Adaptive Behavior Protocols

### Protocol 1: When Documentation Conflicts with Code

**Situation:** You discover that the codebase has evolved beyond what's documented.

**Response Pattern:**

```markdown
1. **Acknowledge the Discrepancy**
   "I notice the documentation states [X], but examining the codebase reveals [Y]"

2. **Trust the Code**
   "The current implementation shows [actual pattern found in code]"

3. **Provide Accurate Guidance**
   "Based on the current codebase, here's the recommended approach..."

4. **Flag for Update**
   "Note: Knowledge base should be updated to reflect this evolution"

5. **Proceed Effectively**
   "Here's how to implement your feature using the current architecture..."
```

**Example:**

```markdown
User: "The docs say we use Alpine.js. How do I add a new component?"

Discovery: package.json shows Vue 3.4.0, not Alpine.js

Response:
"I see the platform has migrated from Alpine.js to Vue 3.x.
 Looking at resources/js/, I can see you're using Vue 3 Composition API.

 Here's how to create a component in Vue 3:

 [Provides Vue 3 guidance, not Alpine.js]

 Note: The knowledge base mentions Alpine.js but the codebase uses Vue 3.
 I've based my guidance on the actual technology stack."
```

### Protocol 2: When Assumptions Need Validation

**Situation:** You need to make a recommendation but aren't certain about current state.

**Response Pattern:**

```markdown
1. **State the Assumption**
   "Based on typical Laravel conventions, I would expect [X]"

2. **Propose Validation**
   "Let me verify this by examining [specific file/command]"

3. **Execute Discovery**
   [Run appropriate discovery command]

4. **Report Findings**
   "I've confirmed that [actual state]"

5. **Provide Validated Guidance**
   "Therefore, the recommended approach is..."
```

### Protocol 3: When Multiple Patterns Exist

**Situation:** You discover the codebase uses multiple approaches for similar problems.

**Response Pattern:**

```markdown
1. **Identify the Patterns**
   "I see two approaches in the codebase:
    - Pattern A: [found in X locations]
    - Pattern B: [found in Y locations]"

2. **Analyze Context**
   "Pattern A appears to be used for [context]
    Pattern B is used when [different context]"

3. **Recommend Based on Context**
   "For your use case [user's situation], Pattern [A/B] is more appropriate because..."

4. **Explain the Reasoning**
   "This pattern is consistent with the team's approach to [domain]"
```

---

## 🎯 Pattern Recognition Catalog

### Recognizing CMIS-Specific Patterns

#### Pattern: Multi-Tenant Data Access

**Recognition Triggers:**
- Questions about querying data
- Questions about data visibility
- Questions about organization isolation

**Discovery Process:**
```bash
# Check for org_id in table
\d cmis.table_name

# Check for RLS policies
SELECT policyname FROM pg_policies WHERE tablename = 'table_name';

# Check for context middleware
ls app/Http/Middleware/ | grep -i context
```

**When Detected, Always:**
1. Emphasize RLS over manual filtering
2. Verify context is set in middleware chain
3. Never suggest manual org_id filtering
4. Explain database-level security benefit

#### Pattern: Repository-Based Data Access

**Recognition Triggers:**
- Questions about database operations
- Questions about data access
- Questions about CRUD operations

**Discovery Process:**
```bash
# Check for repository
find app/Repositories -name "*Repository.php" | grep -i [Domain]

# Check AI_AGENT_GUIDE.md
cat app/Repositories/AI_AGENT_GUIDE.md | grep -A 10 "[Domain]"
```

**When Detected, Always:**
1. Recommend repository over direct Eloquent
2. Show dependency injection pattern
3. Reference repository method signatures
4. Explain return type handling

#### Pattern: Event-Driven Architecture

**Recognition Triggers:**
- Questions about triggering actions
- Questions about decoupling
- Questions about async operations

**Discovery Process:**
```bash
# Find events
find app/Events -name "*.php"

# Find listeners
find app/Listeners -name "*.php"

# Check event service provider
cat app/Providers/EventServiceProvider.php
```

**When Detected, Always:**
1. Recommend events over tight coupling
2. Explain event-listener pattern
3. Show how to dispatch events
4. Mention async queue processing

#### Pattern: Job-Based Async Processing

**Recognition Triggers:**
- Questions about heavy operations
- Questions about background tasks
- Questions about long-running processes

**Discovery Process:**
```bash
# Find jobs
find app/Jobs -name "*.php"

# Check queue configuration
cat config/queue.php

# Check for horizon or supervisor
ls -la artisan | grep -i queue
```

**When Detected, Always:**
1. Recommend jobs for heavy operations
2. Explain queue benefits
3. Show dispatch patterns
4. Mention retry and backoff strategies

---

## 📊 Self-Assessment Checklist

Before providing any guidance, ask yourself:

- [ ] Did I discover current state rather than assume from docs?
- [ ] Did I examine actual code/schema before recommending?
- [ ] Did I identify relevant architectural patterns?
- [ ] Did I verify my assumptions programmatically?
- [ ] Did I account for CMIS-specific patterns (RLS, repositories)?
- [ ] Will my guidance remain valid if code is refactored?
- [ ] Am I teaching discovery methods, not stating facts?
- [ ] Did I flag any documentation discrepancies?

**If you answered NO to any item, re-evaluate your response using this framework.**

---

## 🚀 Advanced: Meta-Cognitive Evolution

### Learning from Each Interaction

As an AI agent, after every interaction:

1. **Record Discoveries**
   What new patterns did I find in the codebase?

2. **Update Internal Model**
   How does this change my understanding of CMIS architecture?

3. **Identify Knowledge Gaps**
   What did I assume that turned out wrong?

4. **Refine Discovery Methods**
   Which discovery commands were most effective?

5. **Share Insights**
   What should be added to this framework for future agents?

### Continuous Improvement Loop

```
User Question
      ↓
Apply Discovery Protocol
      ↓
Examine Actual Code/Schema
      ↓
Identify Patterns
      ↓
Provide Guidance
      ↓
Record Learnings
      ↓
Evolve Understanding ──→ [Back to User Question]
```

---

## 📚 Integration with Other Knowledge Files

This framework works in conjunction with:

1. **DISCOVERY_PROTOCOLS.md** - Specific commands for each discovery type
2. **PATTERN_RECOGNITION.md** - Detailed pattern library
3. **LARAVEL_CONVENTIONS.md** - Framework-specific patterns
4. **CMIS_PRINCIPLES.md** - Business domain principles
5. **MULTI_TENANCY_PATTERNS.md** - RLS and org context patterns

**Always consult these files, but apply THIS framework's methodology.**

---

## ⚡ Quick Reference Card

```
╔══════════════════════════════════════════════════════════════╗
║           ADAPTIVE INTELLIGENCE QUICK REFERENCE               ║
╠══════════════════════════════════════════════════════════════╣
║                                                               ║
║  NEVER: State facts that can become outdated                  ║
║  ALWAYS: Teach how to discover current facts                  ║
║                                                               ║
║  NEVER: Show specific code examples as truth                  ║
║  ALWAYS: Teach patterns and how to find current examples      ║
║                                                               ║
║  NEVER: Assume documentation is current                       ║
║  ALWAYS: Verify against actual codebase                       ║
║                                                               ║
║  NEVER: Memorize file paths or counts                         ║
║  ALWAYS: Show commands to discover paths/counts               ║
║                                                               ║
║  WHEN IN DOUBT: Execute discovery, trust code over docs       ║
║                                                               ║
╚══════════════════════════════════════════════════════════════╝
```

---

## 🎓 Conclusion

This meta-cognitive framework transforms AI agents from **documentation readers** to **intelligent discoverers**.

**Old agents:** "The system has X, located at Y, version Z"
**New agents:** "Let me discover what the system currently has, where it's located, and what version it's using"

**The result:** Agents that remain accurate and useful regardless of how the codebase evolves.

**Your mission as an AI agent:** Apply this framework to every interaction. Discover, don't assume. Teach patterns, not facts. Adapt continuously.

---

**Framework Version:** 2.0
**Last Updated:** 2025-11-18
**Maintained By:** CMIS AI Agent Development Team
**Status:** MANDATORY for all agents

*"Intelligence isn't knowing all the answers - it's knowing how to find them."*
