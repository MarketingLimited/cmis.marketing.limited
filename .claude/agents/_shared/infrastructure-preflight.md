# Shared Infrastructure Pre-Flight Checks
**Version:** 1.2
**Last Updated:** 2025-11-27

This is a shared module for all agents that interact with database or testing infrastructure.

## 📋 Table of Contents

1. [Environment Configuration](#️-important-environment-configuration-first)
2. [Critical Agent Best Practices](#-critical-agent-best-practices)
3. [Pre-Flight Infrastructure Validation](#-critical-pre-flight-infrastructure-validation)
4. [PostgreSQL Issues & Solutions](#-common-postgresql-issues--solutions)
5. [Pre-Flight Checklist](#-pre-flight-checklist)
6. [Validation Script](#-quick-validation-script)
7. [When to Run Checks](#-when-to-run-pre-flight-checks)
8. [Related Resources](#-related-resources)

## ⚠️ IMPORTANT: Environment Configuration First

**ALWAYS read database configuration from `.env` file before running any commands.**

```bash
# Read current environment configuration
cat .env | grep DB_

# Extract values for use in scripts
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
```

**Note:** Database name, credentials, and connection details vary by environment. Never assume or hardcode these values.

---

## 🎯 CRITICAL: Agent Best Practices

**ALL Claude Code agents MUST follow these practices when working on CMIS codebase:**

### 1. File Size Limits (MANDATORY)

**Rule: Code files MUST NOT exceed 499 lines.**

```bash
# Before editing any file, check line count
wc -l path/to/file.php

# ✅ ACCEPTABLE: File has 350 lines
# ❌ UNACCEPTABLE: File has 520 lines - MUST refactor first
```

**Why:** Files exceeding 499 lines:
- Become difficult to maintain and understand
- Violate Single Responsibility Principle (SRP)
- Make code reviews harder
- Increase cognitive load
- Create merge conflicts

**When you encounter a file >499 lines:**

1. **STOP** - Do not make changes to the existing large file
2. **ANALYZE** - Identify logical separation points (classes, concerns, responsibilities)
3. **REFACTOR** - Split into smaller, focused files (<499 lines each)
4. **VERIFY** - Ensure all tests still pass
5. **PROCEED** - Now make your intended changes

**Refactoring Patterns for Large Files:**

```php
// BEFORE: UserController.php (850 lines) ❌

class UserController extends Controller
{
    // 200 lines of authentication logic
    // 300 lines of profile management
    // 250 lines of settings management
    // 100 lines of notification preferences
}

// AFTER: Split into focused controllers ✅

// UserAuthController.php (180 lines)
class UserAuthController extends Controller
{
    // Authentication logic only
}

// UserProfileController.php (280 lines)
class UserProfileController extends Controller
{
    // Profile management only
}

// UserSettingsController.php (230 lines)
class UserSettingsController extends Controller
{
    // Settings management only
}

// UserNotificationController.php (95 lines)
class UserNotificationController extends Controller
{
    // Notification preferences only
}
```

**For Services (>499 lines):**

Extract helper classes, separate concerns:

```php
// BEFORE: CampaignService.php (650 lines) ❌

// AFTER: Split into focused services ✅
// CampaignService.php (250 lines) - Core campaign operations
// CampaignValidationService.php (180 lines) - Validation logic
// CampaignMetricsService.php (220 lines) - Metrics calculation
```

**Agents to use for refactoring:**
- Use `laravel-refactor-specialist` agent for complex refactoring tasks
- Use `laravel-code-quality` agent to verify SRP compliance after refactoring

---

### 2. API Integration Research (MANDATORY)

**Rule: ALWAYS research latest API documentation before implementing external platform integrations.**

**Platforms requiring research:**
- Meta (Facebook/Instagram) Ads API
- Google Ads API
- TikTok Ads API
- LinkedIn Ads API
- Twitter (X) Ads API
- Snapchat Ads API
- Any external API integration

**Before implementing ANY platform API feature:**

```bash
# Step 1: Use WebSearch tool to find latest API version
WebSearch: "Meta Marketing API latest version 2025"
WebSearch: "Google Ads API migration guide 2025"

# Step 2: Use WebFetch to read official documentation
WebFetch: "https://developers.facebook.com/docs/marketing-api/versions"
WebFetch: "https://developers.google.com/google-ads/api/docs/release-notes"

# Step 3: Check for breaking changes and deprecations
WebSearch: "Meta Marketing API breaking changes 2025"
WebSearch: "Google Ads API deprecated features"
```

**Why API research is critical:**
- APIs change frequently (Meta updates quarterly)
- Deprecated endpoints stop working without warning
- New features may simplify implementation
- Rate limits and quotas change
- Authentication methods evolve (OAuth 2.0 → OAuth 2.1)
- Field names and data structures change

**Example - Meta API Research Checklist:**

```markdown
Before implementing Meta feature:
- [ ] Check current API version (e.g., v19.0 vs v20.0)
- [ ] Verify endpoint still exists and not deprecated
- [ ] Check required permissions/scopes
- [ ] Verify field names haven't changed
- [ ] Check rate limits (current: 200 calls/hour per user)
- [ ] Review webhook signature verification method
- [ ] Check for new required fields
- [ ] Verify OAuth redirect URI requirements
```

**Documentation to always check:**
- **Meta:** https://developers.facebook.com/docs/marketing-api
- **Google Ads:** https://developers.google.com/google-ads/api
- **TikTok:** https://business-api.tiktok.com/portal/docs
- **LinkedIn:** https://learn.microsoft.com/en-us/linkedin/marketing/
- **Twitter/X:** https://developer.twitter.com/en/docs/twitter-ads-api
- **Snapchat:** https://marketingapi.snapchat.com/docs/

**When to re-research:**
- Before adding new API integration
- When fixing API-related bugs
- Every 3-6 months for active integrations
- When receiving API error responses
- Before major feature releases

---

### 3. Large File Handling (MANDATORY)

**Rule: Use specialized tools and strategies when working with files >800 lines.**

**Detection:**

```bash
# Find all large files in codebase
find app/ -name "*.php" -exec wc -l {} \; | awk '$1 > 800 {print $1, $2}' | sort -rn

# Check specific file
wc -l app/Services/SomeService.php
```

**Strategies for reading large files:**

```php
// ❌ WRONG: Reading entire file at once
Read tool: app/Models/LargeModel.php  // All 1,200 lines

// ✅ CORRECT: Read with offset and limit
Read tool: app/Models/LargeModel.php (offset: 0, limit: 300)    // Lines 1-300
Read tool: app/Models/LargeModel.php (offset: 300, limit: 300)  // Lines 301-600
Read tool: app/Models/LargeModel.php (offset: 600, limit: 300)  // Lines 601-900
Read tool: app/Models/LargeModel.php (offset: 900, limit: 300)  // Lines 901-1200
```

**Strategies for editing large files:**

```php
// ❌ WRONG: Trying to edit without reading first
Edit tool: app/Services/LargeService.php

// ✅ CORRECT: Read → Identify section → Edit specific section
// 1. Read to understand structure
Read tool: app/Services/LargeService.php (offset: 0, limit: 100)  // Read headers

// 2. Use Grep to find exact location
Grep: "public function processMetrics" in app/Services/LargeService.php

// 3. Read specific section
Read tool: app/Services/LargeService.php (offset: 450, limit: 50)

// 4. Make targeted edit
Edit tool: old_string="exact unique string from line 465-480", new_string="updated code"
```

**Best practices for large files:**

1. **Use Grep first** - Find exact location before reading
2. **Read in chunks** - Use offset/limit parameters
3. **Edit precisely** - Use unique old_string with context
4. **Consider refactoring** - If >499 lines, split first (see File Size Limits)

**Tools optimization for large files:**

```bash
# Find specific function/method quickly
Grep: "public function methodName" --output_mode=content -A 20

# Find class definition and read just that section
Grep: "class LargeClass" --output_mode=content
# Then read that specific offset

# Count methods in large class
Grep: "public function\|private function\|protected function" --output_mode=count
```

---

### 4. Tool Usage Optimization (MANDATORY)

**Rule: Use the most efficient tool for each task to maximize speed and quality.**

**Tool Selection Matrix:**

| Task | ❌ WRONG Tool | ✅ CORRECT Tool | Why |
|------|--------------|----------------|-----|
| Find files by pattern | Bash: `find` | **Glob** | Faster, optimized for codebase |
| Search code content | Bash: `grep -r` | **Grep** | Supports regex, context, filtering |
| Read file | Bash: `cat` | **Read** | Line numbers, offset/limit support |
| Edit file | Bash: `sed` | **Edit** | Safer, validates before/after |
| Write new file | Bash: `echo >` | **Write** | Proper encoding, validation |
| Run tests | Bash one-by-one | **Bash parallel** | Run multiple concurrently |
| Database queries | Bash: `psql` loop | **Grep** + **Read** discovery | More efficient pattern |

**Parallel Tool Execution:**

```bash
# ❌ SLOW: Sequential tool calls
Read: app/Models/Campaign.php
# Wait...
Read: app/Models/AdAccount.php
# Wait...
Read: app/Models/Creative.php
# Wait...

# ✅ FAST: Parallel tool calls (in single message)
# Use multiple tool calls in ONE message:
Read: app/Models/Campaign.php
Read: app/Models/AdAccount.php
Read: app/Models/Creative.php
# All execute simultaneously!
```

**Efficient Code Search:**

```bash
# ❌ SLOW: Bash grep
bash: grep -r "CampaignRepository" app/

# ✅ FAST: Grep tool with file filtering
Grep: "CampaignRepository" --type=php --output_mode=files_with_matches

# ✅ FASTER: Grep with glob pattern
Grep: "CampaignRepository" --glob="**/*Repository.php" --output_mode=files_with_matches
```

**Efficient File Discovery:**

```bash
# ❌ SLOW: Bash find
bash: find app/Services -name "*Service.php"

# ✅ FAST: Glob tool
Glob: "app/Services/**/*Service.php"

# ✅ FASTER: Specific pattern
Glob: "app/Services/Platform/*Service.php"
```

**Efficient Multi-File Operations:**

```bash
# Task: Read all repository files
# ❌ SLOW: One at a time
Read: app/Repositories/CampaignRepository.php
# Wait for response...
Read: app/Repositories/AdAccountRepository.php
# Wait for response...

# ✅ FAST: Find first, then parallel read
Glob: "app/Repositories/*Repository.php"
# Get list: [Campaign, AdAccount, Creative, Budget, Metric]
# Then in ONE message:
Read: app/Repositories/CampaignRepository.php
Read: app/Repositories/AdAccountRepository.php
Read: app/Repositories/CreativeRepository.php
Read: app/Repositories/BudgetRepository.php
Read: app/Repositories/MetricRepository.php
```

**Tool Performance Tips:**

1. **Glob before Grep** - Find files first, then search within
2. **Grep with filters** - Use `--type` and `--glob` to narrow search
3. **Parallel reads** - Read multiple files in one message
4. **Limit scope** - Use specific paths, not broad wildcards
5. **Use Task agent** - For complex, multi-step explorations

**When to use Task agent:**

```bash
# ❌ Don't use Task for simple operations
Task: "Read app/Models/Campaign.php"  # Just use Read directly!

# ✅ Use Task for complex workflows
Task: "Explore the campaign metrics calculation logic across repositories, services, and models"
# This requires multiple tools, inference, and exploration

# ✅ Use Task for broad discovery
Task: "Find all platform integration services and analyze their OAuth implementation patterns"
# Requires Glob, Grep, Read, and analysis
```

**Available Tools Reference:**

- **Glob** - File pattern matching (e.g., `**/*.php`)
- **Grep** - Content search with regex, filters, context
- **Read** - Read files with line numbers, offset, limit
- **Edit** - Safe file editing with before/after validation
- **Write** - Create new files
- **Bash** - Terminal commands (use for git, composer, npm, etc.)
- **Task** - Launch specialized agents for complex tasks
- **WebSearch** - Search web for latest information
- **WebFetch** - Fetch and analyze web pages
- **TodoWrite** - Track task progress

**Quality Tips:**

1. **Always Read before Edit** - Never edit blindly
2. **Use unique old_string** - Include context for Edit tool
3. **Parallel when possible** - Multiple independent operations in one message
4. **Grep before Read** - Find exact location first
5. **Glob for discovery** - Find files by pattern
6. **Task for complexity** - Use specialized agents for multi-step work

---

## 🚀 CRITICAL: Pre-Flight Infrastructure Validation

**⚠️ ALL agents working with database or tests MUST run these checks FIRST:**

### Quick Pre-Flight Command

```bash
# Run automated pre-flight checks
./scripts/test-preflight.sh

# If script doesn't exist, run manual checks below
```

### Manual Pre-Flight Checks

#### 1. PostgreSQL Server Validation
```bash
# Check if PostgreSQL is running
service postgresql status 2>&1 | grep -qi "active\|running\|online" && echo "✅ Running" || echo "❌ Not running"

# Start if not running
if ! service postgresql status 2>&1 | grep -qi "active\|running\|online"; then
    echo "Starting PostgreSQL..."
    service postgresql start
    sleep 2
fi

# Verify connection using .env credentials
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT 1;" >/dev/null 2>&1 && echo "✅ Can connect" || echo "❌ Cannot connect"
```

#### 2. Composer Dependencies Validation
```bash
# Check if vendor directory exists
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Verify installation
test -f vendor/autoload.php && echo "✅ Dependencies installed" || echo "❌ Dependencies missing"
```

#### 3. Database Role Validation
```bash
# Read database username from .env
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Check if required role exists (using postgres superuser)
psql -h 127.0.0.1 -U postgres -d postgres -c "\du" 2>&1 | grep -q "$DB_USERNAME" || {
    echo "Creating '$DB_USERNAME' database role..."
    psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE ROLE $DB_USERNAME WITH LOGIN SUPERUSER PASSWORD '$DB_PASSWORD';"
}
```

#### 4. Environment Variables Check
```bash
# Ensure not using remote database for local work
if printenv | grep -q "^DB_HOST=2\.59\.156\.237"; then
    echo "⚠️  Remote DB detected. For local work, unset:"
    echo "unset DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD"
fi
```

## 🔧 Common PostgreSQL Issues & Solutions

### Issue: PostgreSQL Not Running

**Symptoms:**
- "connection to server failed"
- "could not connect to server"

**Solutions:**
```bash
# 1. Start PostgreSQL
service postgresql start

# 2. If SSL certificate errors:
sed -i 's/^ssl = on/ssl = off/' /etc/postgresql/*/main/postgresql.conf
service postgresql restart

# 3. If permission errors:
chmod 640 /etc/ssl/private/ssl-cert-snakeoil.key
chown root:ssl-cert /etc/ssl/private/ssl-cert-snakeoil.key
```

### Issue: Authentication Failed

**Symptoms:**
- "Peer authentication failed"
- "FATAL: password authentication failed"

**Solutions:**
```bash
# Switch to trust authentication for local development
sed -i 's/peer/trust/g' /etc/postgresql/*/main/pg_hba.conf
sed -i 's/scram-sha-256/trust/g' /etc/postgresql/*/main/pg_hba.conf
service postgresql reload
```

### Issue: Role Does Not Exist

**Symptoms:**
- "role 'begin' does not exist"
- "role 'postgres' does not exist"

**Solutions:**
```bash
# Create missing role
psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE ROLE begin WITH LOGIN SUPERUSER PASSWORD '123@Marketing@321';"

# Verify roles
psql -h 127.0.0.1 -U postgres -d postgres -c "\du"
```

### Issue: Extension Not Available

**Symptoms:**
- "extension 'vector' is not available"
- "extension 'uuid-ossp' is not available"

**Solutions:**
```bash
# Install pgvector
apt-get update && apt-get install -y postgresql-*-pgvector
service postgresql restart

# Install uuid-ossp (usually included)
psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";"
```

### Issue: Database Does Not Exist

**Symptoms:**
- "database '...' does not exist"
- "database '..._test' does not exist"

**Solutions:**
```bash
# Read database name from .env
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)

# Create main database
psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE $DB_DATABASE;"

# Create parallel test databases (for parallel testing)
# These follow the pattern: {DB_DATABASE}_test_1, {DB_DATABASE}_test_2, etc.
for i in {1..15}; do
    psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE ${DB_DATABASE}_test_$i;"
done
```

## 📋 Pre-Flight Checklist

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
- [ ] Composer dependencies are installed (vendor/ exists)
- [ ] Required database roles exist (check `.env` for DB_USERNAME)
- [ ] Required extensions are available (pgvector, uuid-ossp)
- [ ] Database exists (check `.env` for DB_DATABASE)
- [ ] Test databases exist (if running tests)
- [ ] Environment variables are correct (not pointing to remote DB)

## 🎯 Quick Validation Script

```bash
#!/bin/bash
echo "=== Infrastructure Pre-Flight Check ==="

# Read .env configuration
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# PostgreSQL
service postgresql status >/dev/null 2>&1 && echo "✅ PostgreSQL running" || echo "❌ PostgreSQL not running"

# Composer
test -f vendor/autoload.php && echo "✅ Composer dependencies" || echo "❌ Composer dependencies missing"

# Connection (using .env credentials)
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;" >/dev/null 2>&1 && echo "✅ Database connection ($DB_DATABASE)" || echo "❌ Cannot connect to database ($DB_DATABASE)"

# Role
psql -h 127.0.0.1 -U postgres -d postgres -c "\du" 2>&1 | grep -q "$DB_USERNAME" && echo "✅ Database role ($DB_USERNAME)" || echo "❌ Missing database role ($DB_USERNAME)"

echo "=== Check Complete ==="
```

## 🚨 When to Run Pre-Flight Checks

Run pre-flight checks when:
1. **Starting ANY new task** - Check file sizes, tool availability
2. **Before editing files** - Verify file <500 lines
3. **API integration work** - Research latest API documentation
4. **Working with large files** - Use Read with offset/limit
5. **Starting database work** - Validate PostgreSQL connection
6. **Before running migrations** - Ensure database is accessible
7. **Before executing tests** - Verify test infrastructure
8. **After system restart** - Re-validate all services
9. **When encountering errors** - Run pre-flight diagnostics
10. **Before database schema changes** - Verify permissions and backups

## 📚 Related Resources

**Agents:**
- **Refactoring:** `.claude/agents/laravel-refactor-specialist.md` - For files >499 lines
- **Code Quality:** `.claude/agents/laravel-code-quality.md` - SRP verification
- **Testing:** `.claude/agents/laravel-testing.md` - Test execution
- **Database:** `.claude/agents/laravel-db-architect.md` - Database work
- **DevOps:** `.claude/agents/laravel-devops.md` - Infrastructure

**Knowledge Base:**
- **API Integration:** `.claude/knowledge/GOOGLE_AI_INTEGRATION.md`
- **Platform Setup:** `.claude/knowledge/PLATFORM_SETUP_WORKFLOW.md`
- **Laravel Conventions:** `.claude/knowledge/LARAVEL_CONVENTIONS.md`
- **Discovery Protocols:** `.claude/knowledge/DISCOVERY_PROTOCOLS.md`

**Scripts:**
- **Pre-Flight Script:** `scripts/test-preflight.sh`

---

## 🎯 Quick Reference

| Need To... | Action | Tool/Command |
|------------|--------|--------------|
| Check file size | Verify <500 lines | `wc -l path/to/file.php` |
| Refactor large file | Split into modules | Use `laravel-refactor-specialist` agent |
| Research API | Find latest docs | WebSearch + WebFetch |
| Read large file | Use offset/limit | Read tool with parameters |
| Find files | Pattern matching | Glob tool `**/*.php` |
| Search code | Regex search | Grep tool with filters |
| Parallel operations | Multiple in one msg | Multiple tool calls |
| Complex exploration | Multi-step task | Task agent (Explore) |

---

**Remember:**
- ✅ **File size <500 lines** - Refactor if larger
- ✅ **Research APIs first** - Always use latest docs
- ✅ **Use right tools** - Glob/Grep/Read not Bash
- ✅ **Parallel operations** - Speed and efficiency
- ✅ **Infrastructure checks** - Prevent 90% of errors

**Last Updated:** 2025-11-27
**Version:** 1.2
**Maintained By:** CMIS AI Agent Development Team
