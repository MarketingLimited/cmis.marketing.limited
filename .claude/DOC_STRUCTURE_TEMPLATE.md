# CMIS Documentation Structure Template

> **Note:** This is a template showing the recommended documentation structure.
> The `cmis-doc-organizer` agent will automatically create and maintain this structure.

## Overview

This document defines the standard documentation structure for the CMIS project. All AI agents should generate documentation in these organized locations rather than the root directory.

## Directory Structure

```
docs/
├── README.md                    # Master documentation index (auto-generated)
│
├── active/                      # 🔥 Current active documentation
│   ├── plans/                   # Active implementation plans
│   │   └── .gitkeep
│   ├── reports/                 # Current reports
│   │   └── .gitkeep
│   ├── analysis/                # Ongoing analyses
│   │   └── .gitkeep
│   └── progress/                # Progress tracking
│       └── .gitkeep
│
├── archive/                     # 📦 Completed/historical documentation
│   ├── plans/                   # Old implementation plans
│   │   └── .gitkeep
│   ├── reports/                 # Historical reports
│   │   └── .gitkeep
│   ├── sessions/                # Session summaries
│   │   └── .gitkeep
│   └── analyses/                # Past analyses
│       └── .gitkeep
│
├── api/                         # 📚 API documentation
│   └── .gitkeep
│
├── architecture/                # 🏗️ System architecture
│   └── .gitkeep
│
├── guides/                      # 📖 User and developer guides
│   ├── setup/                   # Setup and installation
│   │   └── .gitkeep
│   ├── development/             # Development guidelines
│   │   └── .gitkeep
│   └── deployment/              # Deployment guides
│       └── .gitkeep
│
└── reference/                   # 📋 Reference materials
    ├── database/                # Database schemas and docs
    │   └── .gitkeep
    ├── models/                  # Model documentation
    │   └── .gitkeep
    └── apis/                    # API references
        └── .gitkeep
```

## Directory Purposes

### `/docs/active/`
**Purpose:** Current, in-progress documentation that is actively being used and updated.

**What goes here:**
- Current implementation plans
- Active sprint reports
- Ongoing analysis documents
- Real-time progress tracking

**Lifecycle:** Documents move to `archive/` when completed or superseded.

### `/docs/archive/`
**Purpose:** Completed or historical documentation preserved for reference.

**What goes here:**
- Completed implementation plans (e.g., PHASE_1_COMPLETE.md)
- Historical progress reports
- Old session summaries
- Superseded analyses

**Naming Convention:** Keep original name + add date archived if needed.

### `/docs/api/`
**Purpose:** API documentation and references.

**What goes here:**
- REST API documentation
- GraphQL schemas
- API usage examples
- Endpoint references
- Integration guides

### `/docs/architecture/`
**Purpose:** System architecture and design documentation.

**What goes here:**
- System overview
- Database schema documentation
- Microservices architecture
- Integration patterns
- Design decisions

### `/docs/guides/`
**Purpose:** How-to guides for users and developers.

**Subdirectories:**
- `setup/` - Installation and configuration
- `development/` - Development workflows and standards
- `deployment/` - Deployment procedures

### `/docs/reference/`
**Purpose:** Quick reference materials and lookups.

**Subdirectories:**
- `database/` - Database schemas, migrations log
- `models/` - Model documentation
- `apis/` - Third-party API references

## Agent Guidelines

### For ALL AI Agents

When generating documentation, follow these rules:

#### ✅ DO:
1. **Create docs in appropriate directories**
   ```
   docs/active/plans/feature-x-implementation.md
   docs/api/rest-api-v2.md
   docs/guides/setup/local-development.md
   ```

2. **Use descriptive names**
   ```
   ✅ current-implementation-plan.md
   ✅ weekly-progress-report.md
   ✅ user-authentication-guide.md
   ```

3. **Archive when complete**
   ```
   docs/active/plans/feature-x.md
   → docs/archive/plans/feature-x-2024-11-18.md
   ```

4. **Update the docs index**
   - Add entry to `docs/README.md`
   - Include description and purpose

#### ❌ DON'T:
1. **Create docs in root directory**
   ```
   ❌ /NEW_FEATURE_PLAN.md
   ❌ /IMPLEMENTATION_SUMMARY.md
   ❌ /PROGRESS_REPORT.md
   ```

2. **Create generic names**
   ```
   ❌ report.md
   ❌ analysis.md
   ❌ notes.md
   ```

3. **Let docs accumulate without archiving**
4. **Create duplicate documents**
5. **Ignore the index (README.md)**

## Document Types & Locations

| Document Type | Active Location | Archive Location |
|--------------|----------------|-----------------|
| Implementation Plans | `docs/active/plans/` | `docs/archive/plans/` |
| Progress Reports | `docs/active/reports/` | `docs/archive/reports/` |
| Session Summaries | `docs/active/reports/` | `docs/archive/sessions/` |
| Analyses | `docs/active/analysis/` | `docs/archive/analyses/` |
| API Docs | `docs/api/` | N/A (update in place) |
| Architecture Docs | `docs/architecture/` | N/A (version in git) |
| Setup Guides | `docs/guides/setup/` | N/A (update in place) |
| Development Guides | `docs/guides/development/` | N/A (update in place) |

## Naming Conventions

### Active Documents
Format: `{purpose}-{description}.md` (lowercase with hyphens)

Examples:
- `implementation-ai-search-feature.md`
- `progress-weekly-sprint-5.md`
- `analysis-performance-audit.md`

### Archive Documents
Format: Keep original name, optionally add date

Examples:
- `implementation-phase-1-2024-11-18.md`
- `PHASE_1_COMPLETE-archived.md`
- `session-summary-2024-11-10.md`

### Permanent Docs
Format: `{topic}-{type}.md`

Examples:
- `rest-api-reference.md`
- `database-schema.md`
- `local-setup-guide.md`

## Auto-Organization Rules

The `cmis-doc-organizer` agent automatically organizes documents based on these rules:

### Archive Triggers
Documents are automatically archived if:
1. Name contains `COMPLETE`, `COMPLETED`, `FINISHED`
2. Name matches `PHASE_*_COMPLETE*`
3. Last modified > 30 days AND marked as session/temporary
4. Content indicates completion

### Classification Patterns
```javascript
Patterns that trigger automatic classification:

"*_PLAN.md" → docs/active/plans/
"*_COMPLETE.md" → docs/archive/plans/
"*_REPORT.md" → docs/active/reports/ (or archive if old)
"*_ANALYSIS.md" → docs/active/analysis/
"SESSION_*.md" → docs/archive/sessions/
"API_*.md" → docs/api/
"ARCHITECTURE_*.md" → docs/architecture/
"*_GUIDE.md" → docs/guides/
```

## Master Index (docs/README.md)

The master index is auto-generated and includes:

1. **Quick Navigation** - Links to main sections
2. **Active Documentation** - Current plans, reports, analyses
3. **API Documentation** - API references
4. **Architecture** - System design docs
5. **Guides** - How-to guides
6. **Reference** - Quick lookup materials
7. **Archive** - Historical documentation with counts

Example structure:
```markdown
# CMIS Documentation

## 🔥 Active Documentation
### Current Plans (3)
- [Feature X Implementation](active/plans/feature-x.md)
...

## 📦 Archive
- [Completed Plans](archive/plans/) - 12 documents
- [Historical Reports](archive/reports/) - 23 documents
...
```

## Usage with cmis-doc-organizer

### Initial Setup
```bash
# Create the directory structure
@cmis-doc-organizer create documentation structure
```

### Regular Organization
```bash
# Organize all root documentation
@cmis-doc-organizer organize all documentation

# Archive completed work
@cmis-doc-organizer archive completed documents

# Update index
@cmis-doc-organizer update documentation index
```

### Maintenance Schedule
- **After each session:** Move new docs from root
- **Weekly:** Archive old session reports
- **Monthly:** Consolidate duplicate reports
- **Quarterly:** Major documentation review

## Integration with Git

All documentation is version controlled:

```bash
# Documentation changes are committed separately
git add docs/
git commit -m "docs: [description of doc changes]"
```

## Benefits of This Structure

1. ✅ **Clean Root Directory** - Only essential project files
2. ✅ **Easy Navigation** - Clear categorization
3. ✅ **Historical Tracking** - Archive preserves history
4. ✅ **Discoverability** - Comprehensive index
5. ✅ **Automation** - AI agents maintain structure
6. ✅ **Scalability** - Handles growing documentation

## Migration from Old Structure

If you have existing documentation in root:

```bash
@cmis-doc-organizer migrate existing documentation
```

The agent will:
1. Scan all .md and .txt files in root
2. Classify each document
3. Move to appropriate directory
4. Create archive for old documents
5. Generate master index
6. Provide migration report

## Questions?

See `.claude/agents/DOC_ORGANIZER_GUIDE.md` for detailed usage guide.

---

**Template Version:** 1.0
**Created:** 2024-11-18
**Maintained by:** cmis-doc-organizer agent
