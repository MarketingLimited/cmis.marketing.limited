# CMIS Claude Code Framework

**Version:** 3.0 - Optimized for Claude Code 2025
**Last Updated:** 2025-11-19
**Project:** CMIS - Campaign Management & Integration System

---

## 📁 Directory Structure

```
.claude/
├── README.md                    # This file - Framework overview
├── settings.local.json          # Project-specific settings
├── optimize-agents.sh           # Agent optimization script
│
├── agents/                      # Specialized AI agents (21 total)
│   ├── README.md               # Agent catalog and usage guide
│   ├── cmis-orchestrator.md   # Master coordinator
│   ├── cmis-context-awareness.md
│   ├── cmis-multi-tenancy.md
│   ├── cmis-platform-integration.md
│   ├── cmis-ai-semantic.md
│   ├── cmis-campaign-expert.md
│   ├── cmis-ui-frontend.md
│   ├── cmis-social-publishing.md
│   ├── laravel-*.md            # Laravel specialists (13 agents)
│   └── _shared/                # Shared agent resources
│
├── knowledge/                   # Project documentation (10 files)
│   ├── README.md               # Knowledge base guide
│   ├── META_COGNITIVE_FRAMEWORK.md
│   ├── DISCOVERY_PROTOCOLS.md
│   ├── CMIS_PROJECT_KNOWLEDGE.md
│   ├── MULTI_TENANCY_PATTERNS.md
│   ├── CMIS_DATA_PATTERNS.md
│   ├── CMIS_SQL_INSIGHTS.md
│   ├── CMIS_REFERENCE_DATA.md
│   ├── LARAVEL_CONVENTIONS.md
│   ├── PATTERN_RECOGNITION.md
│   └── CMIS_DISCOVERY_GUIDE.md
│
├── commands/                    # Slash commands (5 commands)
│   ├── README.md               # Command documentation
│   ├── test.md                 # /test - Run tests
│   ├── migrate.md              # /migrate - Database migrations
│   ├── audit-rls.md            # /audit-rls - RLS audit
│   ├── optimize-db.md          # /optimize-db - Performance
│   └── create-agent.md         # /create-agent - New agent wizard
│
├── hooks/                       # Automation hooks
│   ├── README.md               # Hook documentation
│   ├── session-start.sh        # Session startup info
│   └── pre-commit-check.sh     # Pre-commit validation
│
└── memory/                      # Session memory (auto-managed)
    └── .gitkeep
```

---

## 🚀 Quick Start

### For Developers
1. Read `CLAUDE.md` in project root - Essential project guidelines
2. Browse `.claude/agents/README.md` - Find specialized agents
3. Check `.claude/knowledge/CMIS_PROJECT_KNOWLEDGE.md` - Understand architecture
4. Use `/test`, `/migrate`, `/audit-rls` commands for common tasks

### For AI Agents
1. Consult `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md` - Learn adaptive intelligence
2. Use `.claude/knowledge/DISCOVERY_PROTOCOLS.md` - Execute discovery commands
3. Reference `.claude/knowledge/` files - Access project patterns
4. Never hardcode facts - Always discover current state

---

## 🤖 Specialized Agents

### Core CMIS Agents (8)
- **cmis-orchestrator** - Multi-domain coordination
- **cmis-context-awareness** - Architecture & patterns expert
- **cmis-multi-tenancy** - RLS & data isolation specialist
- **cmis-platform-integration** - OAuth & webhooks expert
- **cmis-ai-semantic** - pgvector & embeddings specialist
- **cmis-campaign-expert** - Campaign domain expert
- **cmis-ui-frontend** - Alpine.js & Tailwind specialist
- **cmis-social-publishing** - Social media management

### Laravel Specialists (13)
- **laravel-architect** - High-level architecture
- **laravel-tech-lead** - Code review & guidance
- **laravel-code-quality** - Quality & refactoring
- **laravel-security** - Security audit & compliance
- **laravel-performance** - Performance optimization
- **laravel-db-architect** - Database & migrations
- **laravel-testing** - Testing strategy
- **laravel-devops** - DevOps & CI/CD
- **laravel-api-design** - API consistency
- **laravel-auditor** - System audit
- **laravel-documentation** - Documentation
- **laravel-refactor-specialist** - Code refactoring
- And more...

See `.claude/agents/README.md` for complete catalog.

---

## 📚 Knowledge Base

### Core Framework
- **META_COGNITIVE_FRAMEWORK** - Adaptive intelligence principles
- **DISCOVERY_PROTOCOLS** - Executable discovery commands

### Project Knowledge
- **CMIS_PROJECT_KNOWLEDGE** - Architecture & domains
- **MULTI_TENANCY_PATTERNS** - RLS patterns
- **CMIS_DATA_PATTERNS** - Data modeling
- **CMIS_SQL_INSIGHTS** - SQL patterns

### Development
- **LARAVEL_CONVENTIONS** - Coding standards
- **PATTERN_RECOGNITION** - Common patterns
- **CMIS_DISCOVERY_GUIDE** - Discovery workflows

See `.claude/knowledge/README.md` for complete guide.

---

## ⚙️ Configuration

### settings.local.json
Project-specific Claude Code settings:
- **Model:** Default `sonnet`, subagents use `haiku` for cost savings
- **Permissions:** Tool access control with deny/ask lists
- **Memory:** Enabled for context persistence
- **Thinking:** Always-on extended thinking mode

### Key Settings
```json
{
  "model": "sonnet",
  "alwaysThinkingEnabled": true,
  "cleanupPeriodDays": 30,
  "permissions": {
    "allow": ["Task", "Bash", "Read", "Write", ...],
    "deny": ["Bash(rm -rf /*)", ...],
    "ask": ["Bash(git push --force:*)", ...]
  }
}
```

---

## 🔧 Slash Commands

Quick workflows for common tasks:

| Command | Purpose |
|---------|---------|
| `/test` | Run test suite with analysis |
| `/migrate` | Database migrations with RLS checks |
| `/audit-rls` | Comprehensive RLS audit |
| `/optimize-db` | Performance analysis |
| `/create-agent` | New agent wizard |

Add custom commands in `.claude/commands/`. See README there for format.

---

## 🪝 Hooks

Automation scripts for common events:

### session-start.sh
Runs when Claude Code starts:
- Shows git status
- Checks environment setup
- Displays quick reference
- Lists available agents

### pre-commit-check.sh
Runs before git commits:
- Scans for debugging code
- Checks for secrets
- Validates PHP syntax
- Verifies RLS policies
- Runs code style checks

Configure hooks in `settings.local.json`. See `.claude/hooks/README.md` for details.

---

## 🎯 Framework Philosophy

### Adaptive Intelligence
Focus on **discovery** over **documentation**:

❌ Static facts (get outdated):
```
"CMIS has 189 tables"
```

✅ Discovery commands (always current):
```bash
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema LIKE 'cmis%';
```

### Three Laws
1. **Discovery Over Documentation** - Execute to discover truth
2. **Patterns Over Examples** - Learn pattern, apply to any case
3. **Inference Over Assumption** - Verify before stating

### Model Selection
- **Haiku** - Lightweight agents (documentation, routing)
- **Sonnet** - Complex agents (architecture, security, debugging)

Optimizes cost while maintaining quality.

---

## 📊 Statistics

- **Total Agents:** 21 specialized agents
- **Knowledge Files:** 10 comprehensive guides
- **Slash Commands:** 5 common workflows
- **Hooks:** 2 automation scripts
- **Framework Version:** 3.0 (2025 optimized)

---

## 🔄 Maintenance

### Regular Tasks
- **Weekly:** Update knowledge base with new patterns
- **Monthly:** Review agent effectiveness
- **Quarterly:** Major framework updates

### Adding New Content

**New Agent:**
```bash
# Use the wizard
/create-agent

# Or manually create in .claude/agents/
# Include YAML frontmatter with name, description, model
# Reference knowledge base files
# Focus on discovery, not facts
```

**New Command:**
```bash
# Create .claude/commands/mycommand.md
# Add YAML frontmatter with description
# Include step-by-step instructions
# Update .claude/commands/README.md
```

**New Knowledge:**
```bash
# Add to .claude/knowledge/
# Focus on HOW TO DISCOVER, not WHAT IS
# Provide executable commands
# Cross-reference related files
# Update .claude/knowledge/README.md
```

---

## 🚨 Critical Rules

### Multi-Tenancy (ALWAYS)
- ✅ Respect RLS policies in all queries
- ✅ Use schema-qualified table names
- ✅ Test with multiple organizations
- ❌ NEVER bypass RLS with manual filtering
- ❌ NEVER hard-delete records

### Security
- ✅ Scan for secrets before commits
- ✅ Use encrypted storage for credentials
- ✅ Rate limit AI operations
- ❌ NEVER commit .env files
- ❌ NEVER expose credentials in code

### Code Quality
- ✅ Follow Repository + Service pattern
- ✅ Write tests for business logic
- ✅ Use Laravel conventions (PSR-12)
- ❌ NEVER put business logic in controllers
- ❌ NEVER hardcode configuration

---

## 📖 Essential Reading

**Start here:**
1. `CLAUDE.md` (project root) - Project guidelines
2. `.claude/agents/README.md` - Agent catalog
3. `.claude/knowledge/CMIS_PROJECT_KNOWLEDGE.md` - Architecture

**For development:**
- `.claude/knowledge/LARAVEL_CONVENTIONS.md`
- `.claude/knowledge/MULTI_TENANCY_PATTERNS.md`
- `.claude/knowledge/PATTERN_RECOGNITION.md`

**For agents:**
- `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md`
- `.claude/knowledge/DISCOVERY_PROTOCOLS.md`

---

## 🆘 Getting Help

### Find Information
```bash
# Search knowledge base
grep -r "search term" .claude/knowledge/

# List all agents
ls .claude/agents/*.md

# Show available commands
ls .claude/commands/
```

### Common Questions

**Which agent to use?**
→ Start with `.claude/agents/README.md` or use `cmis-orchestrator`

**How to add RLS to new table?**
→ See `.claude/knowledge/MULTI_TENANCY_PATTERNS.md`

**How to optimize query?**
→ See `.claude/knowledge/CMIS_SQL_INSIGHTS.md`

**How to create custom command?**
→ See `.claude/commands/README.md`

---

## 🎓 Learning Path

### Week 1: Foundation
1. Read CLAUDE.md
2. Explore agents/README.md
3. Study CMIS_PROJECT_KNOWLEDGE.md
4. Try /test, /migrate commands

### Week 2: Deep Dive
1. Study MULTI_TENANCY_PATTERNS.md
2. Review LARAVEL_CONVENTIONS.md
3. Practice with agents
4. Create custom command

### Week 3: Advanced
1. Study META_COGNITIVE_FRAMEWORK.md
2. Learn DISCOVERY_PROTOCOLS.md
3. Create custom agent
4. Optimize workflows

---

## 🔧 Optimization Tools

### optimize-agents.sh
Script to update agent model selections:
```bash
./.claude/optimize-agents.sh
```

Updates lightweight agents to haiku, complex agents to sonnet.

---

## 📝 Version History

**v3.0 (2025-11-19) - Claude Code 2025 Optimization**
- Created CLAUDE.md with project guidelines
- Optimized settings.local.json for security
- Added hooks for automation
- Created slash commands for workflows
- Organized knowledge base
- Added memory persistence
- Documented everything

**v2.0 (2025-11-18) - CMIS-Specific Agents**
- Added 21 specialized agents
- Created adaptive intelligence framework
- Implemented discovery protocols

**v1.0 (Earlier) - Initial Setup**
- Basic agent structure
- Initial knowledge base

---

## 🌟 Best Practices

1. **Always discover** - Use discovery commands, don't assume
2. **Use specialized agents** - They know the domain
3. **Follow patterns** - Consistency matters
4. **Test multi-tenancy** - Every feature must respect RLS
5. **Document as you go** - Keep knowledge base current
6. **Optimize for cost** - Use haiku for lightweight tasks
7. **Security first** - Scan, validate, encrypt

---

## 🚀 Next Steps

After reading this:
1. Read `CLAUDE.md` in project root
2. Explore specialized agents in `agents/`
3. Try slash commands: `/test`, `/migrate`
4. Study knowledge base in `knowledge/`
5. Create your first custom command
6. Share improvements with team

---

**Remember:** This framework is designed for **adaptive intelligence**. It focuses on discovery, patterns, and inference - not memorization. The goal is to build AI agents that understand HOW to find answers, not agents that memorize outdated facts.

**Project Status:** 49% Complete - Phase 2 (Platform Integration)
**Framework Status:** Production-ready, actively maintained

---

**Created by:** CMIS Development Team
**Optimized for:** Claude Code 2025
**License:** Project-specific
**Support:** See project documentation
