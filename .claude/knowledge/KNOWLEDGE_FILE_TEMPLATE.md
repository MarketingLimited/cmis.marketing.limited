# Knowledge File Title
**Version:** 1.0
**Last Updated:** YYYY-MM-DD
**Purpose:** Clear one-line description of what this file teaches
**Prerequisites:** List knowledge files that should be read first (if any)
**Framework:** META_COGNITIVE_FRAMEWORK v2.0 (if applicable)

---

## ⚠️ IMPORTANT: Environment Configuration

**If this file includes database queries or configuration examples, add this section:**

**ALWAYS read from `.env` for environment-specific values. NEVER use hardcoded credentials or database names.**

```bash
# Read environment configuration
cat .env | grep DB_

# Extract for use in commands
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Use in PostgreSQL commands
PGPASSWORD="$DB_PASSWORD" psql \
  -h "$DB_HOST" \
  -U "$DB_USERNAME" \
  -d "$DB_DATABASE" \
  -c "YOUR SQL QUERY HERE;"
```

**Laravel Configuration:**
```php
// ✅ CORRECT: Use config() helper
$value = config('services.service_name.key');

// ❌ WRONG: Hardcoded values
$value = 'hardcoded-value';

// ❌ WRONG: env() outside config files (breaks config caching)
$value = env('SERVICE_KEY');
```

**Best Practices:**
- ✅ Use `config()` in application code
- ✅ Use `env()` ONLY in config files
- ✅ Extract from `.env` for bash/psql commands
- ❌ NEVER hardcode database names, credentials, or API keys
- ❌ NEVER commit `.env` to version control

---

## 📑 Table of Contents

**Add table of contents for files >800 lines:**

1. [Main Section 1](#main-section-1)
2. [Main Section 2](#main-section-2)
3. [Main Section 3](#main-section-3)
...
10. [Quick Reference](#-quick-reference)
11. [Related Knowledge](#-related-knowledge)

---

## 🎯 Purpose

Detailed explanation of what this knowledge file contains and why it exists.

**Target Audience:** Who should read this file (all agents, specific agents, developers)

**When to Use:** Specific scenarios when this knowledge is needed

---

## 📋 Main Content Sections

Organize content into clear, logical sections with descriptive headings.

### Section 1: [Topic Name]

Content here...

### Section 2: [Topic Name]

Content here...

---

## 💡 Best Practices

List key best practices, patterns, or conventions covered in this file:

1. **Practice 1** - Description
2. **Practice 2** - Description
3. **Practice 3** - Description

---

## ⚠️ Common Pitfalls

**Pitfall 1: [Description]**
```
❌ Wrong approach
✅ Correct approach
```

**Pitfall 2: [Description]**
```
❌ Wrong approach
✅ Correct approach
```

---

## 🔍 Quick Reference

| I Need To... | Solution | Details/Section |
|--------------|----------|-----------------|
| Task 1 | Quick command or pattern | Link to section |
| Task 2 | Quick command or pattern | Link to section |
| Task 3 | Quick command or pattern | Link to section |
| Task 4 | Quick command or pattern | Link to section |

---

## 📚 Related Knowledge

**Prerequisites:**
- **FILE_NAME.md** - Why read this first / what it provides

**Related Files:**
- **FILE_NAME.md** - When to use / what it covers
- **FILE_NAME.md** - When to use / what it covers
- **FILE_NAME.md** - When to use / what it covers

**See Also:**
- **CLAUDE.md** - Main project guidelines
- **DISCOVERY_PROTOCOLS.md** - Discovery methodology
- **META_COGNITIVE_FRAMEWORK.md** - Adaptive intelligence principles

---

## 🎯 Key Takeaways

1. **Takeaway 1** - Brief description
2. **Takeaway 2** - Brief description
3. **Takeaway 3** - Brief description
4. **Takeaway 4** - Brief description
5. **Takeaway 5** - Brief description

---

**Last Updated:** YYYY-MM-DD
**Version:** X.Y
**Maintained By:** CMIS AI Agent Development Team

*"Memorable quote that summarizes the file's philosophy or key message."*

---

## 📝 Template Usage Guidelines

### Required Sections
- Header with version, date, purpose, prerequisites
- Environment configuration (if applicable)
- Table of contents (if >800 lines)
- Purpose section
- Main content sections
- Quick Reference table
- Related Knowledge section
- Footer with metadata

### Optional Sections
- Best Practices (recommended)
- Common Pitfalls (recommended)
- Key Takeaways (recommended)
- Examples (as needed)
- Workflows (as needed)

### Version Numbering
- **Major version (X.0)** - Significant restructuring or new major content
- **Minor version (X.Y)** - Content updates, refinements, new sections
- Always update "Last Updated" date when making changes

### File Naming
- Use descriptive, uppercase names with underscores: `TOPIC_NAME.md`
- Place in `.claude/knowledge/` directory
- Add to README.md knowledge base index

### Content Guidelines
1. **Discovery over documentation** - Teach "how to find" not "what is"
2. **Environment-agnostic** - Never hardcode values, always use `.env`
3. **Cross-reference** - Link to related knowledge files
4. **Actionable** - Include executable commands and practical examples
5. **Maintained** - Keep version dates current, archive outdated content

### Code Examples
```bash
# Use .env for all database connections
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)"
```

```php
// Use config() in Laravel code
$apiKey = config('services.google_ai.api_key');

// NEVER hardcode
$apiKey = 'AIza...'; // ❌ WRONG
```

### Testing Your Knowledge File
- [ ] All database commands use `.env`
- [ ] No hardcoded credentials or database names
- [ ] Cross-references are accurate
- [ ] Quick reference table is complete
- [ ] Examples are executable and tested
- [ ] Version and date are current
- [ ] File is added to README.md index

---

**Template Version:** 1.0
**Created:** 2025-11-27
**Purpose:** Standard structure for all CMIS knowledge files
