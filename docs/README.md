# CMIS Documentation Hub

Welcome to the CMIS (Campaign Management & Intelligence System) documentation. This hub provides organized access to all documentation by role, task, and feature area.

## Quick Navigation

- **New to CMIS?** → [Getting Started](#getting-started)
- **Developer?** → [Development Guides](#development)
- **Need API docs?** → [API Documentation](#api-documentation)
- **Looking for features?** → [Features](#features)
- **DevOps/Deployment?** → [Deployment](#deployment)
- **Executive Summary?** → [Reports & Analysis](#reports--analysis)

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Architecture](#architecture)
3. [Features](#features)
4. [API Documentation](#api-documentation)
5. [Platform Integrations](#platform-integrations)
6. [Development](#development)
7. [Testing](#testing)
8. [Implementation Phases](#implementation-phases)
9. [Deployment](#deployment)
10. [Reference](#reference)
11. [Reports & Analysis](#reports--analysis)
12. [Historical Documentation](#historical-documentation)

---

## Getting Started

New to CMIS? Start here:

- **[Quick Start Guide](guides/quick-start.md)** - Get up and running quickly
- **[Main README](../README.md)** - Project overview and introduction
- **[Arabic Guide](guides/start-here.md)** - دليل شامل بالعربية
- **[RTL Implementation Guide](guides/RTL-IMPLEMENTATION-GUIDE.md)** - Right-to-left layout implementation
- **[Installation Guide](getting-started/)** - Detailed installation instructions
- **[Configuration Guide](getting-started/)** - System configuration

---

## Architecture

Understand CMIS architecture and design:

- **[Architecture Overview](architecture/)** - System architecture overview
- **[Database Schema](architecture/)** - Database design and schema
- **[Multi-Tenancy Architecture](architecture/)** - RLS and tenant isolation
- **[Service Architecture](architecture/)** - Service layer design
- **[Comprehensive Structure Analysis](../COMPREHENSIVE_STRUCTURE_ANALYSIS.md)** - Complete structural analysis

---

## Features

### AI & Semantic Features
**Location:** [`docs/features/ai-semantic/`](features/ai-semantic/)

- **[Executive Summary](features/ai-semantic/)** - AI capabilities overview
- **[Implementation Guide](features/ai-semantic/)** - How to implement AI features
- **[Code Examples](features/ai-semantic/)** - Working code examples
- **[Quick Reference](features/ai-semantic/)** - Quick reference guide
- **[Analysis Report](features/ai-semantic/)** - Detailed analysis

### Social Publishing
**Location:** [`docs/features/social-publishing/`](features/social-publishing/)

- **[Overview](features/social-publishing/)** - Social publishing capabilities
- **[Analysis Report](features/social-publishing/)** - Detailed analysis
- **[Critical Issues](features/social-publishing/)** - Known issues and fixes
- **[Implementation Guide](features/social-publishing/)** - Implementation steps

### Frontend
**Location:** [`docs/features/frontend/`](features/frontend/)

- **[Frontend Overview](features/frontend/)** - Frontend architecture
- **[Analysis Report](features/frontend/)** - Comprehensive analysis
- **[Fix Examples](features/frontend/)** - Common fixes and solutions
- **[Affected Files](features/frontend/)** - Files impacted by changes

### Database
**Location:** [`docs/features/database/`](features/database/)

- **[Database Overview](features/database/)** - Database architecture
- **[Analysis Report](features/database/)** - Database analysis
- **[Quick Actions](features/database/)** - Quick fixes and optimizations
- **[Setup Guide](database-setup.md)** - Database setup instructions

### Campaigns
**Location:** [`docs/features/campaigns/`](features/campaigns/)

- **[Campaign Management](features/campaigns/)** - Campaign features

---

## API Documentation

**Location:** [`docs/api/`](api/)

- **[API Overview](api/)** - API architecture and design
- **[OpenAPI Specification](openapi.yaml)** - Complete API specification
- **[Authentication](api/)** - API authentication guide
- **[Endpoints Reference](api/)** - All API endpoints
- **[Integration Guide](../INTEGRATION_GUIDE.md)** - How to integrate with CMIS API
- **[Semantic Search API](semantic_search_api.md)** - AI-powered search API
- **[Vector Embeddings V2](VECTOR_EMBEDDINGS_V2_API_DOCUMENTATION.md)** - Vector embeddings API

---

## Platform Integrations

**Location:** [`docs/integrations/`](integrations/)

Connect CMIS with social media and advertising platforms:

### Instagram
- **[Overview](integrations/instagram/)** - Instagram integration overview
- **[Artisan Commands](integrations/instagram/)** - CLI commands for Instagram
- **[AI Integration](integrations/instagram/)** - AI features for Instagram
- **[Debugging Guide](integrations/instagram/)** - Troubleshooting

### Meta (Facebook)
- **[Meta Integration](integrations/meta/)** - Facebook integration

### LinkedIn
- **[LinkedIn Integration](integrations/linkedin/)** - LinkedIn integration

### TikTok
- **[TikTok Integration](integrations/tiktok/)** - TikTok integration

### Google Ads
- **[Google Integration](integrations/google/)** - Google Ads integration

---

## Development

**Location:** [`docs/development/`](development/)

Guides for developers working on CMIS:

- **[Development Setup](development/)** - Local development environment
- **[Code Style](development/)** - Coding standards and conventions
- **[Git Workflow](development/)** - Git branching and workflow
- **[Contributing](development/)** - How to contribute to CMIS
- **[RTL Implementation Guide](guides/RTL-IMPLEMENTATION-GUIDE.md)** - Right-to-left layout implementation

### Related Documentation
- **[Repository Pattern Guide](../app/Repositories/README.md)** - Repository pattern usage
- **[AI Agent Guide](../app/Repositories/AI_AGENT_GUIDE.md)** - Working with AI agents
- **[Quick Reference](../app/Repositories/QUICK_REFERENCE.md)** - Development quick reference

---

## Testing

**Location:** [`docs/testing/`](testing/)

Comprehensive testing documentation:

- **[Testing Hub](testing/)** - Complete testing documentation hub
- **[Current Test Status](testing/current/)** - Latest test suite status (27 test files, legacy tests archived)
- **[Testing Guides](testing/guides/)** - How to write and run tests
  - Testing framework overview
  - Parallel testing guide
  - Multi-tenancy testing
  - E2E testing strategies
- **[Test History](testing/history/)** - Historical test reports and improvements

**Quick Commands:**
- `/test` - Run test suite via slash command
- `vendor/bin/phpunit` - Run PHPUnit tests

---

## Implementation Phases

**Location:** [`docs/phases/`](phases/)

All CMIS implementation phases organized by status:

### Completed Phases (0-8)
- **[Phase 0](phases/completed/phase-0/)** - Emergency Security Fixes ✅
- **[Phases 1-2](archive/phases/)** - Foundation & Core Features ✅
- **[Phases 3-8](phases/completed/)** - Code Quality Initiative (~13,100 lines saved) ✅
  - Phase 3: BaseModel Conversion (282+ models)
  - Phase 4: Platform Services Abstraction
  - Phase 5: Social Models Consolidation
  - Phase 6: Content Plans Consolidation
  - Phase 7: Controller Enhancement (111 controllers)
  - Phase 8: Documentation & Cleanup

**See:** [Comprehensive Duplication Elimination Report](phases/completed/duplication-elimination/)

### In Progress (Current)
- **[Platform Integration & AI Features](phases/in-progress/)** - Phase 2-3 (55-60% complete) 🔄

### Planned Phases (11-26)
- **[Analytics](phases/planned/analytics/)** - Phases 11-16, 26
- **[Automation](phases/planned/automation/)** - Phases 17, 25
- **[Platform Extensions](phases/planned/platform/)** - Phase 18
- **[Advanced Features](phases/planned/)** - Phases 19-24

**See:** [Phase Documentation Hub](phases/) for complete phase roadmap

---

## Deployment

**Location:** [`docs/deployment/`](deployment/)

Deployment and DevOps documentation:

- **[Deployment Guide](deployment/)** - How to deploy CMIS
- **[Database Setup](database-setup.md)** - Production database setup
- **[System Recovery](system_recovery_plan.md)** - Recovery procedures
- **[Maintenance Checklist](devops_maintenance_checklist.md)** - Regular maintenance tasks
- **[Monitoring](deployment/)** - System monitoring setup
- **[Final Setup Guide](../FINAL_SETUP_GUIDE.md)** - Complete setup walkthrough

---

## Reference

**Location:** [`docs/reference/`](reference/)

Technical reference documentation:

- **[Repositories](reference/)** - Repository reference
- **[Artisan Commands](reference/)** - All artisan commands
- **[Models](reference/)** - Model reference
- **[Helper Functions](reference/)** - Helper functions reference
- **[Project Structure Tree](reference/project-full-tree.txt)** - Complete project structure
- **[Latest Project Tree](reference/project-latest-tree.txt)** - Most recent project structure

---

## Reports & Analysis

### Current Active Reports

**Location:** [`docs/active/`](active/)

Latest reports and analysis (Dec 2025):

#### Analysis Reports
**Location:** [`docs/active/analysis/`](active/analysis/)

- **[Complete Implementation Report](active/analysis/COMPLETE-IMPLEMENTATION-REPORT.md)** - Implementation status
- **[Comprehensive Agent Architecture](active/analysis/COMPREHENSIVE-AGENT-ARCHITECTURE-PLAN.md)** - Agent system design
- **[Platform Integrations Audit](active/analysis/platform-integrations-comprehensive-audit.md)** - Platform analysis
- **[RBAC Comprehensive Analysis](active/analysis/rbac-comprehensive-analysis.md)** - Access control audit
- **[UX Product Issues](active/analysis/ux-product-issues-audit-report.md)** - UX improvements
- **[Code Review & Product Strategy](active/analysis/code-review-product-strategy-validation.md)** - Strategy validation

**Note:** Historical analysis files (2025-11-20/21) have been moved to [`archive/analysis-2025-11/`](archive/analysis-2025-11/)

#### Progress & Reports
**Location:** [`docs/active/reports/`](active/reports/)

- **[Code Quality Executive Summary](active/reports/code-quality-executive-summary.md)** - Quality metrics
- **[UX Fixes Comprehensive Report](active/reports/UX-FIXES-COMPREHENSIVE-REPORT.md)** - UX improvements
- **[Marketing Automation Analysis](active/reports/marketing-automation-analysis-2025-11-23.md)** - Automation features
- **[Social Publishing Fix Report](active/reports/social-publishing-fix-report-2025-11-22.md)** - Social features

**Note:** Historical session/test reports have been moved to [`archive/sessions-2025-11-late/`](archive/sessions-2025-11-late/)

#### Next Steps & Planning
- **[Next Steps](active/NEXT_STEPS.md)** - Immediate action items
- **[Implementation Roadmap](active/IMPLEMENTATION_ROADMAP.md)** - Project roadmap
- **[Redis Setup Guide](active/REDIS_SETUP_GUIDE.md)** - Redis configuration

### Strategic Reports

**Location:** [`docs/reports/`](reports/)

- **[Master Action Plan](reports/master-action-plan.md)** - Strategic action plan
- **[Executive Summary](reports/executive-summary.md)** - Executive overview
- **[Gap Analysis](reports/gap-analysis.md)** - Gap analysis report
- **[Technical Audit](reports/technical-audit.md)** - Technical audit findings
- **[Comprehensive Audit](reports/comprehensive-audit.md)** - Full audit report
- **[Project Reality Check](reports/project-reality-check.md)** - Reality check report (2025-11-12)
- **[System Interfaces Audit (AR)](reports/system-interfaces-audit-ar.md)** - تدقيق واجهات النظام

### Additional Reports
- **[Changelog](changelog.md)** - Change history and releases
- **[Database Sync Report](reports/database-sync.md)** - Database synchronization status

---

## Historical Documentation

**Location:** [`docs/archive/`](archive/)

Historical documentation for reference:

### Recent Archives (2025-11)
- **[Sessions Nov 2025](archive/sessions-2025-11/)** - Recent session summaries (6 files)
- **[Test Results](archive/test-results/)** - Historical test results (8 files)
- **[Progress Reports Nov 2025](archive/progress-reports-2025-11/)** - Recent progress reports (5 files)
- **[Reports Nov 2025](archive/reports-2025-11/)** - Consolidated reports archive (8 files)
  - `test-fixes/` - Test fix report versions
  - `test-analysis/` - Test analysis versions

### Phase Completion Reports
- **[Phase 0](archive/phases/phase-0/)** - Initial setup and planning
- **[Phase 1](archive/phases/phase-1/)** - Foundation phase
- **[Phase 2](archive/phases/phase-2/)** - Core features
- **[Phase 3](archive/phases/phase-3/)** - Advanced features
- **[Phase 4](archive/phases/phase-4/)** - Integration phase
- **[Phase 5](archive/phases/phase-5/)** - Optimization phase

### Session Reports
- **[Sessions](archive/sessions/)** - Historical development session summaries

### Progress Reports
- **[Progress Reports](archive/progress-reports/)** - Historical progress tracking

### Audit Archive
- **[Audits](archive/audits/)** - Historical audit reports

---

## Documentation by Role

### For Executives
- [Executive Summary](reports/) - High-level overview
- [Master Action Plan](reports/) - Strategic planning
- [Gap Analysis](reports/) - Current status and gaps

### For Project Managers
- [Implementation Roadmap](../IMPLEMENTATION_ROADMAP.md)
- [Master Action Plan](reports/)
- [Progress Reports](archive/progress-reports/)

### For Developers
- [Getting Started](getting-started/)
- [Development Guide](development/)
- [API Documentation](api/)
- [Testing Guide](../TESTING.md)
- [Repository Pattern](../app/Repositories/README.md)

### For DevOps Engineers
- [Deployment Guide](deployment/)
- [Database Setup](database-setup.md)
- [System Recovery](system_recovery_plan.md)
- [Maintenance Checklist](devops_maintenance_checklist.md)

### For QA Engineers
- [Testing Hub](testing/) - Complete testing documentation
- [Current Test Status](testing/current/) - Latest test results
- [Testing Guides](testing/guides/) - How to write tests

---

## AI Agents Documentation

**Note:** AI Agents documentation is maintained separately in the `.claude/` directory and is not included in this documentation hub.

For AI agents documentation, see:
- `.claude/agents/` - AI agent definitions
- `.claude/knowledge/` - Knowledge base
- `.claude/README.md` - Agent framework guide

---

## System Runtime Documentation

System runtime documentation is maintained separately in the `system/` directory:
- `system/gpt_runtime_readme.md` - Runtime overview
- `system/gpt_runtime_security.md` - Security documentation
- `system/gpt_runtime_map.md` - System map

---

## Contributing to Documentation

To contribute to documentation:

1. Follow the directory structure outlined above
2. Use clear, descriptive filenames (kebab-case)
3. Include a README.md in each feature directory
4. Update this index when adding new documentation
5. Archive historical documentation appropriately

---

## Documentation Standards

- **Root README files:** UPPERCASE.md (e.g., README.md, QUICK_START.md)
- **Regular docs:** kebab-case.md (e.g., database-setup.md, api-integration.md)
- **Feature READMEs:** features/feature-name/README.md
- **Archive files:** Include dates (e.g., 2024-11-phase-1-complete.md)

---

## Need Help?

- **Setup Issues?** → [Getting Started](#getting-started)
- **API Questions?** → [API Documentation](#api-documentation)
- **Development Help?** → [Development](#development)
- **Deployment Issues?** → [Deployment](#deployment)
- **Can't Find Something?** → Check [Historical Documentation](#historical-documentation)

---

**Last Updated:** 2025-12-01 ✅
**Version:** 2.2.0
**Maintained by:** CMIS Development Team

### Recent Documentation Updates (2025-12-01)

**Documentation Reorganization:**
- ✅ Archived 38 dated analysis files from Nov 2025 → `archive/analysis-2025-11/`
- ✅ Archived 16 test history files → `archive/test-history-2025-11/`
- ✅ Archived 4 session files → `archive/sessions-2025-11-late/`
- ✅ Updated test suite status (27 files, legacy tests archived)
- ✅ Updated CLAUDE.md with current project status
- ✅ Cleaned up active/ directory

**Recent Feature Updates (Nov 27 - Dec 1):**
- ✅ Profile Management module (VistaSocial-like)
- ✅ Queue Settings with time picker
- ✅ 3-level timezone inheritance for social scheduling
- ✅ Alpine.js lazy loading optimization
- ✅ i18n/RTL compliance improvements

### Previous Major Update (2025-11-22)

- Created consolidated Phase Documentation Hub (26 phases)
- Created consolidated Testing Documentation Hub
- Fixed documentation issues (duplicates, misplaced files)

See [archive/sessions-2025-11-late/](archive/sessions-2025-11-late/) for historical session details.
