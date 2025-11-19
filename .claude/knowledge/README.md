# CMIS Knowledge Base

Comprehensive documentation and patterns for the CMIS platform. This knowledge base uses an **Adaptive Intelligence Framework** - focusing on discovery over documentation, patterns over examples, and inference over assumptions.

## 📚 Core Knowledge Files

### META_COGNITIVE_FRAMEWORK.md
**Purpose:** Foundational framework for adaptive intelligence

The master framework that teaches:
- Three Laws of Adaptive Intelligence
- Discovery-first methodology
- Pattern recognition over memorization
- How to avoid documentation rot

**Use when:** Starting any task, need to understand how to think about CMIS

### DISCOVERY_PROTOCOLS.md
**Purpose:** Executable commands for discovering current system state

Contains 12 protocol categories with real commands:
- Database schema discovery
- Laravel structure discovery
- Frontend stack discovery
- API endpoint discovery
- Multi-tenancy verification
- Platform integration discovery
- And more...

**Use when:** Need to discover current state, verify system configuration

### CMIS_PROJECT_KNOWLEDGE.md
**Purpose:** Core project architecture and patterns

Comprehensive overview of:
- 10 business domains
- 12 database schemas
- Multi-tenancy architecture
- Platform integrations
- AI capabilities
- Development patterns

**Use when:** Understanding project structure, making architectural decisions

## 🗄️ Database & Patterns

### MULTI_TENANCY_PATTERNS.md
**Purpose:** PostgreSQL RLS patterns and best practices

Everything about CMIS's unique multi-tenancy:
- Row-Level Security (RLS) implementation
- Organization isolation patterns
- Context management
- Testing strategies
- Common pitfalls and solutions

**Use when:** Working with data isolation, debugging RLS issues, adding new tables

### CMIS_DATA_PATTERNS.md
**Purpose:** Data modeling patterns specific to CMIS

Patterns used across CMIS:
- EAV pattern for campaign context
- Platform integration data models
- AI embedding storage
- Social media data structures

**Use when:** Modeling new data structures, understanding relationships

### CMIS_SQL_INSIGHTS.md
**Purpose:** Advanced SQL patterns and optimizations

PostgreSQL-specific patterns:
- Complex joins across schemas
- pgvector usage patterns
- RLS-aware queries
- Performance optimization
- Analytics queries

**Use when:** Writing complex queries, optimizing performance

### CMIS_REFERENCE_DATA.md
**Purpose:** Schema reference and table catalog

Quick reference for:
- All 189 tables across 12 schemas
- Key relationships
- Important constraints
- Common queries

**Use when:** Looking up table names, understanding schema organization

## 🏗️ Development Patterns

### LARAVEL_CONVENTIONS.md
**Purpose:** Laravel coding standards for CMIS

CMIS-specific Laravel patterns:
- Repository pattern implementation
- Service layer organization
- Multi-tenancy in Eloquent models
- Testing conventions
- API design patterns

**Use when:** Writing new code, reviewing code, ensuring consistency

### PATTERN_RECOGNITION.md
**Purpose:** Common patterns across codebase

Recurring patterns in CMIS:
- Factory patterns (AdPlatformFactory)
- Service patterns (EmbeddingOrchestrator)
- Repository patterns
- Strategy patterns
- Observer patterns

**Use when:** Implementing similar features, refactoring code

### CMIS_DISCOVERY_GUIDE.md
**Purpose:** Guide for discovering CMIS capabilities

Step-by-step discovery workflows:
- How to find features
- How to trace code flow
- How to understand integrations
- How to debug issues

**Use when:** Exploring unfamiliar code, debugging, onboarding

## 📖 Knowledge Base Philosophy

### Adaptive Intelligence
Knowledge files focus on **HOW TO DISCOVER** rather than **WHAT IS**:

❌ **Static facts** (get outdated):
```
"CMIS has 189 tables"
"We use Alpine.js 3.13.5"
```

✅ **Discovery commands** (always current):
```bash
# Discover current table count
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema LIKE 'cmis%';

# Discover frontend dependencies
cat package.json | jq '.dependencies'
```

### Three Laws of Adaptive Intelligence

1. **Discovery Over Documentation**
   - Execute commands to discover truth
   - Don't memorize facts that change

2. **Patterns Over Examples**
   - Learn the pattern, apply to any case
   - Don't copy-paste examples blindly

3. **Inference Over Assumption**
   - Verify before stating
   - Discover rather than assume

## 🔄 Knowledge Base Maintenance

### Update Frequency
- **META_COGNITIVE_FRAMEWORK** - Rarely (fundamental principles)
- **DISCOVERY_PROTOCOLS** - As new discovery needs arise
- **CMIS_PROJECT_KNOWLEDGE** - When architecture changes
- **Pattern files** - When new patterns emerge
- **Reference files** - Generate from current state

### Adding New Knowledge

When adding new knowledge files:
1. **Focus on discovery** - Provide commands, not facts
2. **Identify patterns** - Teach the pattern, not the instance
3. **Cross-reference** - Link to related knowledge
4. **Version in git** - Track knowledge evolution
5. **Test validity** - Ensure discovery commands work

### Avoiding Documentation Rot

Traditional docs rot because facts change. Our approach:
```markdown
❌ Don't write: "The campaigns table has 15 columns"
✅ Do write: "Discover columns: \d+ cmis.campaigns"

❌ Don't write: "We have these 8 platform integrations..."
✅ Do write: "find app/Services/Platform -name '*Service.php'"

❌ Don't write: "Alpine.js version is 3.13.5"
✅ Do write: "cat package.json | jq .dependencies.alpinejs"
```

## 🎯 How to Use This Knowledge Base

### For New Features
1. Start with **META_COGNITIVE_FRAMEWORK** - Understand the approach
2. Use **DISCOVERY_PROTOCOLS** - Find current state
3. Check **PATTERN_RECOGNITION** - Find similar features
4. Reference **CMIS_PROJECT_KNOWLEDGE** - Understand domain
5. Apply **LARAVEL_CONVENTIONS** - Follow standards

### For Debugging
1. **DISCOVERY_PROTOCOLS** - Verify current state
2. **MULTI_TENANCY_PATTERNS** - Check RLS issues
3. **CMIS_SQL_INSIGHTS** - Analyze queries
4. **CMIS_DISCOVERY_GUIDE** - Trace code flow

### For Architecture Decisions
1. **CMIS_PROJECT_KNOWLEDGE** - Understand current architecture
2. **PATTERN_RECOGNITION** - Find existing patterns
3. **LARAVEL_CONVENTIONS** - Follow conventions
4. **MULTI_TENANCY_PATTERNS** - Consider RLS implications

### For Code Review
1. **LARAVEL_CONVENTIONS** - Check standards
2. **MULTI_TENANCY_PATTERNS** - Verify RLS compliance
3. **PATTERN_RECOGNITION** - Ensure pattern consistency
4. **CMIS_DATA_PATTERNS** - Validate data modeling

## 🔍 Finding Information

### Quick Search
```bash
# Search all knowledge files
grep -r "search term" .claude/knowledge/

# Find pattern examples
grep -r "Factory\|Repository\|Service" .claude/knowledge/

# Find RLS-related info
grep -r "RLS\|Row Level Security" .claude/knowledge/
```

### By Topic

| Topic | Primary File | Supporting Files |
|-------|-------------|------------------|
| Multi-tenancy | MULTI_TENANCY_PATTERNS | CMIS_PROJECT_KNOWLEDGE, CMIS_SQL_INSIGHTS |
| Database | CMIS_SQL_INSIGHTS | CMIS_REFERENCE_DATA, MULTI_TENANCY_PATTERNS |
| Architecture | CMIS_PROJECT_KNOWLEDGE | PATTERN_RECOGNITION, LARAVEL_CONVENTIONS |
| Development | LARAVEL_CONVENTIONS | PATTERN_RECOGNITION, CMIS_DISCOVERY_GUIDE |
| Discovery | DISCOVERY_PROTOCOLS | META_COGNITIVE_FRAMEWORK, CMIS_DISCOVERY_GUIDE |

## 🚀 Integration with Agents

All CMIS agents reference this knowledge base:

```markdown
---
name: your-agent
description: Your agent description
---

# Your Agent

## 🚨 CRITICAL: APPLY ADAPTIVE INTELLIGENCE

1. Consult `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md`
2. Use `.claude/knowledge/DISCOVERY_PROTOCOLS.md`
3. Reference `.claude/CMIS_PROJECT_KNOWLEDGE.md`

[Agent prompt continues...]
```

Agents use knowledge base for:
- Understanding current system state
- Applying consistent patterns
- Making informed decisions
- Avoiding outdated assumptions

## 📊 Knowledge Base Statistics

- **Total Files:** 10
- **Total Size:** ~200KB of knowledge
- **Categories:** 4 (Core, Database, Development, Discovery)
- **Update Frequency:** As needed, version controlled
- **Approach:** Discovery-first, pattern-focused

## 🆘 Support

**Can't find what you need?**
1. Check **CMIS_DISCOVERY_GUIDE** for how to discover
2. Use **DISCOVERY_PROTOCOLS** to find current state
3. Search across all knowledge files
4. Create new knowledge file if needed

**Knowledge seems outdated?**
1. Use discovery commands to find truth
2. Update file with new discovery commands
3. Commit changes
4. Focus on "how to find" not "what is"

---

**Remember:** This is a **living knowledge base**. It grows and evolves with the project. Focus on discovery, patterns, and inference - not memorization.

**Last Updated:** 2025-11-19
**Project:** CMIS - Campaign Management & Integration System
**Framework Version:** 3.0 - Optimized for Claude Code 2025
