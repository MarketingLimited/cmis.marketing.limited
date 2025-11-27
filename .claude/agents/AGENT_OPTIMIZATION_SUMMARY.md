# Agent Optimization Summary
**Version:** 1.0
**Last Updated:** 2025-11-27
**Purpose:** Document comprehensive agent best practices optimization

---

## 🎯 Overview

All Claude Code agents for the CMIS platform now follow standardized best practices through the shared infrastructure file:

**Updated File:** `.claude/agents/_shared/infrastructure-preflight.md` (v1.1 → v1.2)

This optimization ensures all 200+ agents follow consistent, high-quality development practices.

---

## ✅ What Was Implemented

### 1. File Size Limits (MANDATORY)

**Rule:** Code files MUST NOT exceed 499 lines.

**Key Points:**
- Files >499 lines violate Single Responsibility Principle (SRP)
- Agents must check line count BEFORE editing: `wc -l path/to/file.php`
- If file >499 lines: STOP → ANALYZE → REFACTOR → VERIFY → PROCEED
- Use `laravel-refactor-specialist` agent for complex refactoring

**Refactoring Pattern:**
```php
// BEFORE: UserController.php (850 lines) ❌
// AFTER: Split into 4 focused controllers (<300 lines each) ✅
// - UserAuthController.php (180 lines)
// - UserProfileController.php (280 lines)
// - UserSettingsController.php (230 lines)
// - UserNotificationController.php (95 lines)
```

**Why This Matters:**
- Improves maintainability
- Reduces cognitive load
- Prevents merge conflicts
- Enforces clean architecture
- Makes code reviews easier

---

### 2. API Integration Research (MANDATORY)

**Rule:** ALWAYS research latest API documentation before implementing platform integrations.

**Platforms Covered:**
- Meta (Facebook/Instagram) Ads API
- Google Ads API
- TikTok Ads API
- LinkedIn Ads API
- Twitter (X) Ads API
- Snapchat Ads API

**Research Process:**
1. **WebSearch** - Find latest API version and release notes
2. **WebFetch** - Read official documentation
3. **WebSearch** - Check for breaking changes and deprecations

**Example - Meta API Checklist:**
- [ ] Check current API version (e.g., v19.0 vs v20.0)
- [ ] Verify endpoint not deprecated
- [ ] Check required permissions/scopes
- [ ] Verify field names unchanged
- [ ] Check rate limits (200 calls/hour per user)
- [ ] Review webhook signature verification
- [ ] Check for new required fields
- [ ] Verify OAuth redirect URI requirements

**Why This Matters:**
- APIs change frequently (Meta updates quarterly)
- Deprecated endpoints stop working without warning
- New features may simplify implementation
- Rate limits and quotas change
- Authentication methods evolve
- Field names and data structures change

---

### 3. Large File Handling (MANDATORY)

**Rule:** Use specialized tools and strategies for files >800 lines.

**Detection:**
```bash
# Find all large files
find app/ -name "*.php" -exec wc -l {} \; | awk '$1 > 800 {print $1, $2}' | sort -rn

# Check specific file
wc -l app/Services/SomeService.php
```

**Reading Strategy:**
```php
// ❌ WRONG: Read all 1,200 lines at once
Read tool: app/Models/LargeModel.php

// ✅ CORRECT: Read in chunks
Read tool: app/Models/LargeModel.php (offset: 0, limit: 300)    // Lines 1-300
Read tool: app/Models/LargeModel.php (offset: 300, limit: 300)  // Lines 301-600
Read tool: app/Models/LargeModel.php (offset: 600, limit: 300)  // Lines 601-900
Read tool: app/Models/LargeModel.php (offset: 900, limit: 300)  // Lines 901-1200
```

**Editing Strategy:**
1. Use **Grep** to find exact location
2. Read specific section with offset/limit
3. Make targeted edit with unique old_string

**Best Practices:**
- Use Grep first to find location
- Read in chunks (offset/limit)
- Edit precisely (unique old_string)
- Consider refactoring if >499 lines

**Why This Matters:**
- Prevents token exhaustion
- Faster operations
- More precise edits
- Better context management
- Reduces errors

---

### 4. Tool Usage Optimization (MANDATORY)

**Rule:** Use the most efficient tool for each task.

**Tool Selection Matrix:**

| Task | ❌ WRONG | ✅ CORRECT | Why |
|------|---------|-----------|-----|
| Find files | `Bash: find` | **Glob** | Faster, optimized |
| Search code | `Bash: grep -r` | **Grep** | Regex, filtering |
| Read file | `Bash: cat` | **Read** | Line numbers, offset |
| Edit file | `Bash: sed` | **Edit** | Safer, validates |
| Write file | `Bash: echo >` | **Write** | Encoding, validation |

**Parallel Execution:**
```bash
# ❌ SLOW: Sequential (3 separate messages)
Read: app/Models/Campaign.php
# Wait...
Read: app/Models/AdAccount.php
# Wait...
Read: app/Models/Creative.php

# ✅ FAST: Parallel (1 message with 3 tool calls)
Read: app/Models/Campaign.php
Read: app/Models/AdAccount.php
Read: app/Models/Creative.php
# All execute simultaneously!
```

**Performance Tips:**
1. **Glob before Grep** - Find files first, then search
2. **Grep with filters** - Use `--type` and `--glob`
3. **Parallel reads** - Multiple files in one message
4. **Limit scope** - Specific paths, not broad wildcards
5. **Use Task agent** - Complex multi-step explorations

**When to Use Task Agent:**
```bash
# ❌ Don't use for simple operations
Task: "Read app/Models/Campaign.php"  # Just use Read!

# ✅ Use for complex workflows
Task: "Explore campaign metrics calculation across repositories, services, models"
# Requires multiple tools, inference, exploration
```

**Available Tools:**
- **Glob** - File pattern matching
- **Grep** - Content search with regex
- **Read** - Read files with line numbers
- **Edit** - Safe file editing
- **Write** - Create new files
- **Bash** - Terminal commands (git, composer, npm)
- **Task** - Launch specialized agents
- **WebSearch** - Search web
- **WebFetch** - Fetch web pages
- **TodoWrite** - Track progress

**Quality Tips:**
1. Always Read before Edit
2. Use unique old_string with context
3. Parallel when possible
4. Grep before Read
5. Glob for discovery
6. Task for complexity

**Why This Matters:**
- 3-10x faster operations
- Lower token usage
- Higher quality results
- Better error handling
- More efficient workflow

---

## 📊 Impact Metrics

### Coverage
- **Files Updated:** 1 shared infrastructure file
- **Agents Affected:** 200+ agents (ALL agents reference shared infrastructure)
- **Best Practices Added:** 4 comprehensive sections
- **Code Examples:** 30+ practical examples
- **Documentation Size:** ~400 lines of best practices

### Agent Performance
- **File Size Compliance:** Now enforced at agent level
- **API Integration Quality:** Research-first approach
- **Large File Handling:** Optimized read/edit strategies
- **Tool Usage Efficiency:** 3-10x speed improvement potential
- **Error Prevention:** Proactive validation

### Code Quality
- **SRP Enforcement:** File size limits prevent bloat
- **API Accuracy:** Latest documentation required
- **Maintainability:** Smaller, focused files
- **Performance:** Parallel operations, efficient tools
- **Consistency:** All agents follow same standards

---

## 🎯 How It Works

### Shared Infrastructure Pattern

All agents reference the shared infrastructure file:

```markdown
# In ANY agent file (e.g., laravel-architect.md)

## 🚨 CRITICAL: SHARED INFRASTRUCTURE

**ALWAYS consult shared infrastructure first:**

Read: `.claude/agents/_shared/infrastructure-preflight.md`

This includes:
- File size limits (<500 lines)
- API integration research requirements
- Large file handling strategies
- Tool usage optimization
- Pre-flight validation checks
```

**How agents access these practices:**
1. Agent is invoked by Claude Code
2. Agent prompt includes reference to infrastructure-preflight.md
3. Agent reads infrastructure file FIRST
4. Agent applies all best practices from shared file
5. Agent executes task following guidelines

**Benefits of shared pattern:**
- ✅ Update once, apply to all agents
- ✅ Consistent practices across 200+ agents
- ✅ Easy to maintain and evolve
- ✅ Single source of truth
- ✅ Agents always have latest guidelines

---

## 📋 Pre-Flight Checklist (Updated)

Before starting ANY task:

**Agent Best Practices:**
- [ ] Check file line count before editing (must be <500 lines)
- [ ] If file >499 lines, refactor first using `laravel-refactor-specialist`
- [ ] For API work, research latest documentation using WebSearch/WebFetch
- [ ] For large files (>800 lines), use Read with offset/limit
- [ ] Use optimal tools (Glob, Grep, Read) instead of Bash commands
- [ ] Execute independent operations in parallel (multiple tools in one message)

**Database and Testing Work:**
- [ ] Read database configuration from `.env` file
- [ ] PostgreSQL server is running
- [ ] Can connect to PostgreSQL using `.env` credentials
- [ ] Composer dependencies are installed
- [ ] Required database roles exist
- [ ] Required extensions available (pgvector, uuid-ossp)
- [ ] Database exists
- [ ] Test databases exist (if running tests)
- [ ] Environment variables correct

---

## 🚀 When to Apply These Practices

**Always:**
1. **Starting ANY new task** - Check file sizes, tool availability
2. **Before editing files** - Verify file <500 lines
3. **API integration work** - Research latest API documentation
4. **Working with large files** - Use Read with offset/limit
5. **Multiple operations** - Use parallel tool execution

**Database/Testing Work:**
6. **Starting database work** - Validate PostgreSQL connection
7. **Before running migrations** - Ensure database accessible
8. **Before executing tests** - Verify test infrastructure
9. **After system restart** - Re-validate all services
10. **When encountering errors** - Run pre-flight diagnostics

---

## 🎯 Quick Reference

| Need To... | Action | Tool/Command |
|------------|--------|--------------|
| Check file size | Verify <500 lines | `wc -l path/to/file.php` |
| Refactor large file | Split into modules | `laravel-refactor-specialist` agent |
| Research API | Find latest docs | WebSearch + WebFetch |
| Read large file | Use offset/limit | Read tool with parameters |
| Find files | Pattern matching | Glob `**/*.php` |
| Search code | Regex search | Grep with filters |
| Parallel operations | Multiple in one msg | Multiple tool calls |
| Complex exploration | Multi-step task | Task agent (Explore) |

---

## 📚 Related Resources

**Updated Files:**
- `.claude/agents/_shared/infrastructure-preflight.md` (v1.1 → v1.2)

**Agents for Specific Tasks:**
- **Refactoring:** `.claude/agents/laravel-refactor-specialist.md`
- **Code Quality:** `.claude/agents/laravel-code-quality.md`
- **Testing:** `.claude/agents/laravel-testing.md`
- **Database:** `.claude/agents/laravel-db-architect.md`

**Knowledge Base:**
- **API Integration:** `.claude/knowledge/GOOGLE_AI_INTEGRATION.md`
- **Platform Setup:** `.claude/knowledge/PLATFORM_SETUP_WORKFLOW.md`
- **Laravel Conventions:** `.claude/knowledge/LARAVEL_CONVENTIONS.md`
- **Discovery Protocols:** `.claude/knowledge/DISCOVERY_PROTOCOLS.md`

---

## 💡 Key Takeaways

1. **File Size Matters** - Keep files <500 lines for maintainability
2. **Research First** - Always use latest API documentation
3. **Handle Large Files Smartly** - Use offset/limit for files >800 lines
4. **Use Right Tools** - Glob/Grep/Read instead of Bash commands
5. **Work in Parallel** - Multiple tool calls in one message
6. **Shared Infrastructure** - One update affects all 200+ agents

---

## 🎉 Success Criteria Met

✅ **File Size Limits** - Enforced at agent level with clear guidelines
✅ **API Research** - Mandatory research before implementation
✅ **Large File Handling** - Optimized read/edit strategies documented
✅ **Tool Optimization** - Performance tips and tool selection matrix
✅ **Comprehensive Examples** - 30+ practical code examples
✅ **Quick Reference** - Easy lookup table for common tasks
✅ **All Agents Covered** - Shared infrastructure affects 200+ agents

---

## 🔄 Continuous Improvement

**This document is versioned and maintained.**

**To update agent practices in the future:**
1. Edit `.claude/agents/_shared/infrastructure-preflight.md`
2. Update version number and date
3. All agents automatically get new practices
4. Document changes in this summary file

**Versioning:**
- **Major update (X.0)** - New mandatory practices, breaking changes
- **Minor update (X.Y)** - Refinements, clarifications, examples

---

**Last Updated:** 2025-11-27
**Version:** 1.0
**Maintained By:** CMIS AI Agent Development Team

*"Quality at scale through shared infrastructure."*
