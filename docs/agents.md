# دليل الوكلاء - Documentation Layer (docs/)

## 1. Purpose (الغرض)

طبقة Documentation توفر **مركز شامل للتوثيق** في CMIS:
- **400+ Documentation Files**: توثيق كامل لجميع جوانب المشروع
- **Organized by Topic**: تنظيم حسب الميزات، المراحل، التكامل، الاختبار
- **Active & Archive**: فصل بين التوثيق الحالي والتاريخي
- **Multi-Language**: دعم العربية والإنجليزية
- **Living Documentation**: يتم تحديثه باستمرار مع تطور المشروع

## 2. Owned Scope (النطاق المملوك)

### Documentation Organization

```
docs/
├── README.md                           # Documentation hub (index to all docs)
│
├── active/                             # Current active documentation
│   ├── analysis/                      # Latest analysis reports (10 files)
│   │   ├── CMIS-Comprehensive-Application-Analysis-2025-11-20.md
│   │   ├── CMIS-Critical-Issues-Tracker-2025-11-20.md
│   │   ├── CMIS-Quick-Reference-2025-11-20.md
│   │   ├── TODO-UPDATE-REPORT-2025-11-20.md (147 TODO items)
│   │   ├── BROKEN-LINKS-REPORT-2025-11-20.md (63 broken links)
│   │   └── ...
│   │
│   ├── reports/                       # Active progress reports (12 files)
│   │   ├── session-summary-2025-11-20.md
│   │   ├── test-suite-40-percent-assessment-2025-11-20.md
│   │   ├── test-fixes-report-2025-11-20.md
│   │   └── ...
│   │
│   ├── plans/                         # Current planning documents
│   ├── NEXT_STEPS.md                 # Immediate action items
│   └── SESSION_COMPLETE_2025-11-20.md # Latest session completion
│
├── features/                          # Feature-specific documentation
│   ├── ai-semantic/                   # AI & Semantic Search (15 files)
│   │   ├── README.md
│   │   ├── implementation-guide.md
│   │   ├── code-examples.md
│   │   ├── quick-reference.md
│   │   ├── semantic_search_api.md
│   │   └── ...
│   │
│   ├── social-publishing/             # Social Media Publishing (4 files)
│   │   ├── README.md
│   │   ├── implementation-guide.md
│   │   ├── analysis-report.md
│   │   └── critical-issues.md
│   │
│   ├── database/                      # Database Architecture (5 files)
│   │   ├── README.md
│   │   ├── overview.md
│   │   ├── analysis-report.md
│   │   └── quick-actions.md
│   │
│   └── frontend/                      # Frontend Documentation (3 files)
│       ├── README.md
│       └── overview.md
│
├── phases/                            # Implementation phases (26 phases)
│   ├── README.md                      # Phase documentation hub
│   │
│   ├── completed/                     # Completed phases (0-8)
│   │   ├── duplication-elimination/  # Code quality initiative
│   │   │   ├── COMPREHENSIVE-DUPLICATION-ELIMINATION-FINAL-REPORT.md
│   │   │   └── phase-summaries/
│   │   ├── phase-3/                   # BaseModel conversion
│   │   ├── phase-4/                   # Platform services
│   │   ├── phase-5/                   # Social models
│   │   ├── phase-6/                   # Content plans
│   │   ├── phase-7/                   # Controller enhancement
│   │   └── phase-8/                   # Documentation cleanup
│   │
│   ├── in-progress/                   # Current work (Phase 2-3)
│   │
│   └── planned/                       # Future phases (11-26)
│       ├── analytics/                 # Analytics phases
│       ├── automation/                # Automation phases
│       ├── platform/                  # Platform extensions
│       └── ...
│
├── testing/                           # Testing documentation (39 files)
│   ├── README.md                      # Testing hub
│   │
│   ├── current/                       # Current test status
│   │   └── test-suite-status.md      # 201 tests, 33.4% pass rate
│   │
│   ├── guides/                        # Testing guides (8 files)
│   │   ├── testing-framework-overview.md
│   │   ├── parallel-testing-guide.md
│   │   ├── multi-tenancy-testing.md
│   │   └── e2e-testing-strategies.md
│   │
│   └── history/                       # Historical test reports
│       ├── test-analysis/             # Test analysis reports
│       └── test-fixes/                # Test fix reports
│
├── integrations/                      # Platform integration guides
│   ├── instagram/                     # Instagram (5 files)
│   ├── facebook/                      # Meta/Facebook
│   ├── linkedin/                      # LinkedIn
│   ├── tiktok/                        # TikTok
│   └── google/                        # Google Ads
│
├── deployment/                        # Deployment & DevOps (5 files)
│   ├── setup-guide.md
│   ├── database-setup.md
│   ├── devops_maintenance_checklist.md
│   ├── SECURITY_IMPLEMENTATION_GUIDE.md
│   └── system_recovery_plan.md
│
├── api/                               # API Documentation
│   ├── openapi.yaml                   # OpenAPI specification
│   └── ...
│
├── guides/                            # User & developer guides
│   ├── development/                   # Development guides
│   ├── quick-start.md
│   └── start-here.md                  # دليل شامل بالعربية
│
├── reference/                         # Technical reference
│   ├── project-full-tree.txt         # Complete project structure
│   └── project-latest-tree.txt       # Latest structure
│
├── reports/                           # Strategic reports
│   ├── master-action-plan.md
│   ├── executive-summary.md
│   ├── gap-analysis.md
│   ├── technical-audit.md
│   └── ...
│
└── archive/                           # Historical documentation
    ├── sessions-2025-11/              # Recent sessions (6 files)
    ├── test-results/                  # Historical test results (8 files)
    ├── progress-reports-2025-11/     # Progress reports (5 files)
    ├── reports-2025-11/              # Reports archive (8 files)
    ├── phases/                        # Archived phase docs
    ├── audits/                        # Historical audits
    └── sessions/                      # Old session reports
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Main Documentation Hub
- `README.md`: Central index to ALL documentation (413 lines)
  - Navigation by role (Executive, PM, Developer, DevOps, QA)
  - Navigation by topic (Features, API, Testing, Deployment)
  - Quick links to most important docs

### Active Documentation (Current Work)
- `active/analysis/CMIS-Comprehensive-Application-Analysis-2025-11-20.md`: Complete app analysis
- `active/analysis/TODO-UPDATE-REPORT-2025-11-20.md`: 147 TODO items tracked
- `active/analysis/BROKEN-LINKS-REPORT-2025-11-20.md`: 63 broken links documented
- `active/reports/test-suite-40-percent-assessment-2025-11-20.md`: Test suite status
- `active/NEXT_STEPS.md`: Immediate action items

### Feature Documentation
- `features/ai-semantic/README.md`: AI integration overview
- `features/ai-semantic/implementation-guide.md`: Step-by-step AI implementation
- `features/ai-semantic/code-examples.md`: Working code samples
- `features/social-publishing/README.md`: Social publishing capabilities
- `features/database/README.md`: Database architecture

### Phase Documentation
- `phases/README.md`: Complete phase roadmap (26 phases)
- `phases/completed/duplication-elimination/COMPREHENSIVE-DUPLICATION-ELIMINATION-FINAL-REPORT.md`: 13,100 lines saved
- `phases/completed/phase-7/`: Controller enhancement (111 controllers)
- `phases/completed/phase-8/`: Documentation cleanup

### Testing Documentation
- `testing/README.md`: Complete testing hub
- `testing/current/test-suite-status.md`: Current status (201 tests, 33.4% pass)
- `testing/guides/testing-framework-overview.md`: How to write tests
- `testing/guides/parallel-testing-guide.md`: Parallel execution
- `testing/guides/multi-tenancy-testing.md`: RLS testing

### Integration Guides
- `integrations/instagram/`: Instagram API integration
- `integrations/facebook/`: Meta/Facebook integration
- `integrations/linkedin/`: LinkedIn integration
- `integrations/tiktok/`: TikTok integration

### Deployment
- `deployment/setup-guide.md`: Complete setup walkthrough
- `deployment/database-setup.md`: PostgreSQL + RLS setup
- `deployment/SECURITY_IMPLEMENTATION_GUIDE.md`: Security best practices
- `deployment/system_recovery_plan.md`: Disaster recovery

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Documentation Generation Tools
- **Markdown**: All docs in GitHub-flavored markdown
- **Mermaid**: Diagrams in markdown (architecture, flows)
- **PlantUML**: Complex diagrams (class diagrams)
- **OpenAPI**: API specification (Swagger)

### Documentation Viewers
- **GitHub**: Native markdown rendering
- **VS Code**: Markdown preview
- **MkDocs** (planned): Static site generation
- **Swagger UI**: API documentation viewer

### External References
- **Laravel Docs**: https://laravel.com/docs
- **PostgreSQL RLS**: https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- **pgvector**: https://github.com/pgvector/pgvector
- **Alpine.js**: https://alpinejs.dev

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Documentation Organization Rules

#### ✅ Naming Conventions
```
- Root docs: UPPERCASE.md (README.md, QUICK_START.md)
- Regular docs: kebab-case.md (database-setup.md, api-integration.md)
- Feature READMEs: features/feature-name/README.md
- Archive files: Include dates (session-summary-2025-11-20.md)
- Reports: UPPERCASE or Title-Case (CMIS-Quick-Reference-2025-11-20.md)
```

#### ✅ Directory Structure
```
docs/
├── active/           # Current, frequently updated
├── features/         # Feature-specific (stable)
├── phases/           # Phase documentation (append-only)
├── testing/          # Testing docs (evolving)
├── archive/          # Historical (read-only)
└── reference/        # Technical reference (stable)
```

#### ✅ Archive Policy
- Move to `archive/` after 30 days of inactivity
- Keep in `active/` if referenced frequently
- Maintain date stamps: `YYYY-MM-DD` or `2025-11-20`
- Update README.md index when archiving

### Content Standards

#### Documentation Headers
```markdown
# Title

**Last Updated:** 2025-11-22
**Version:** 2.1.0
**Status:** Active | Archived | Draft

## Overview
Brief description...

## Table of Contents
- [Section 1](#section-1)
- [Section 2](#section-2)
```

#### Code Examples
```markdown
### Example: Creating a Service

```php
namespace App\Services\Campaign;

class CampaignService
{
    public function create(array $data): Campaign
    {
        // Implementation
    }
}
```
```

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Viewing Documentation

```bash
# View in GitHub (best)
# Navigate to: https://github.com/your-org/cmis/tree/main/docs

# View locally in VS Code
code docs/README.md

# Generate static site (planned)
mkdocs serve
```

### Validating Links

```bash
# Check for broken links (manual)
# See: docs/active/analysis/BROKEN-LINKS-REPORT-2025-11-20.md

# Planned: Automated link checker
npm run docs:check-links
```

### Generating API Docs

```bash
# Generate OpenAPI/Swagger docs
php artisan l5-swagger:generate

# View API docs
# http://localhost/api/documentation
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Add New Feature Documentation

1. **Create feature directory**:
   ```bash
   mkdir -p docs/features/your-feature
   ```

2. **Create README.md**:
   ```markdown
   # Your Feature

   ## Overview
   Brief description

   ## Implementation
   Step-by-step guide

   ## API Reference
   API endpoints

   ## Examples
   Code examples
   ```

3. **Update main index**:
   - Add to `docs/README.md` under relevant section

### Archive Old Documentation

1. **Move to archive**:
   ```bash
   mv docs/active/old-report.md docs/archive/reports-2025-11/
   ```

2. **Update README.md**:
   - Remove from "Active" section
   - Add to "Archive" section with date

### Create Progress Report

```bash
# Create in active/reports/
touch docs/active/reports/session-summary-$(date +%Y-%m-%d).md
```

### Update Test Documentation

```bash
# Update current test status
vim docs/testing/current/test-suite-status.md

# Add to testing guides
vim docs/testing/guides/new-testing-technique.md
```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Issues

1. **Broken Links**
   - 63 broken links documented in `BROKEN-LINKS-REPORT-2025-11-20.md`
   - Most are due to restructuring
   - Fix plan documented in report

2. **Date Confusion**
   - Some archived files have wrong dates
   - Always verify `Last Updated` in file header
   - Use ISO format: `YYYY-MM-DD`

3. **Duplicate Documentation**
   - Same content in multiple places
   - Check before creating new docs
   - Prefer linking over duplicating

4. **Outdated Content**
   - Check `Last Updated` date
   - If > 3 months old, verify accuracy
   - Update or archive as needed

### 🎯 Best Practices

1. **Always Update README.md**
   - When adding new documentation
   - When archiving old docs
   - Keep the index accurate

2. **Use Relative Links**
   ```markdown
   ✅ [API Guide](api/README.md)
   ❌ [API Guide](/docs/api/README.md)
   ❌ [API Guide](https://github.com/.../docs/api/README.md)
   ```

3. **Include Date Stamps**
   - For reports: `session-summary-2025-11-20.md`
   - For analysis: `TODO-UPDATE-REPORT-2025-11-20.md`
   - In file headers: `**Last Updated:** 2025-11-22`

4. **Organize by Topic, Not Date**
   - Use `features/` not `docs/2025-11/`
   - Archive dated content in `archive/sessions-2025-11/`

5. **Write for Multiple Audiences**
   - Executives: Executive summary first
   - Developers: Code examples and API references
   - DevOps: Deployment and configuration
   - QA: Testing procedures

### 📊 Statistics

- **Total Documentation Files**: 400+
- **Active Reports**: 22 files
- **Archived Reports**: 50+ files
- **Feature Docs**: 27 files
- **Testing Docs**: 39 files
- **Phase Docs**: 40+ files
- **Integration Guides**: 5 platforms
- **Deployment Guides**: 5 files

### 🔗 Related Documentation

- **Project Knowledge**: `.claude/CMIS_PROJECT_KNOWLEDGE.md`
- **Agent Guides**: `.claude/agents/README.md`
- **Main README**: `../README.md`
- **System Runtime**: `../system/gpt_runtime_readme.md`

### 📝 Recent Updates (2025-11-22)

**Major Restructure:**
- ✅ Created [Phase Documentation Hub](phases/) - All 26 phases organized
- ✅ Created [Testing Documentation Hub](testing/) - 39 test files organized
- ✅ Fixed documentation issues (wrong dates, duplicates, misplaced files)
- ✅ Moved 40+ PHASE files into structured hierarchy
- ✅ Moved 39 test files into organized structure
- ✅ Removed empty directories and cleaned up root

**Previous Updates (2025-11-20):**
- ✅ Archived 19 old files from root directory
- ✅ Updated 147 TODO items with accurate status
- ✅ Documented 63 broken links with fix plan
- ✅ Consolidated 16 reports → 8 active reports

See `active/DOCUMENTATION_UPDATE_COMPLETE_2025-11-20.md` for details.
