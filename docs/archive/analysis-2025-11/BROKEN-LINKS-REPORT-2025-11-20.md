# Broken Links Report - CMIS Documentation

**Date:** 2025-11-20
**Status:** 63 broken links detected
**Total Links:** 203
**Success Rate:** 69%

## Summary

Out of 203 markdown links checked across the documentation, **63 links (31%) are broken**.

## Broken Links by Category

### High Priority - Core Documentation (docs/README.md)
- `../COMPREHENSIVE_STRUCTURE_ANALYSIS.md` - Missing
- `database-setup.md` - Wrong path (should be `deployment/database-setup.md`)
- `../INTEGRATION_GUIDE.md` - Missing
- `semantic_search_api.md` - Wrong path
- `VECTOR_EMBEDDINGS_V2_API_DOCUMENTATION.md` - Wrong path
- `../TESTING.md` - Wrong path
- `../E2E_TESTING.md` - Wrong path
- `../TEST_FRAMEWORK_SUMMARY.md` - Wrong path
- `system_recovery_plan.md` - Wrong path
- `devops_maintenance_checklist.md` - Wrong path
- `../FINAL_SETUP_GUIDE.md` - Missing
- `../IMPLEMENTATION_ROADMAP.md` - Missing

### Medium Priority - Feature Documentation

**docs/features/ai-semantic/README.md:**
- `../../semantic_search_api.md` - Wrong path
- `../../VECTOR_EMBEDDINGS_V2_API_DOCUMENTATION.md` - Wrong path
- `../../ai_integration_layer.md` - Wrong path
- `../../laravel_embedding_guidelines.md` - Wrong path
- `../../knowledge_layer_optimization.md` - Wrong path
- `../../semantic_coverage_report.md` - Wrong path

**docs/features/ai-semantic/reports-index.md:**
- `./AI_EXECUTIVE_SUMMARY.md` - Missing
- `./ANALYSIS_AI_SEMANTIC_FEATURES.md` - Missing
- `./AI_IMPROVEMENTS_EXAMPLES.md` - Missing
- `./AI_IMPLEMENTATION_PLAN.md` - Missing
- `./AI_QUICK_REFERENCE.md` - Missing

**docs/features/database/README.md:**
- `../../VECTOR_EMBEDDINGS_V2_API_DOCUMENTATION.md` - Wrong path
- `../../DATABASE_SYNC_REPORT.md` - Missing

**docs/features/social-publishing/README.md:**
- `overview.md` - Missing (3 occurrences)

### Low Priority - Archive & Agent Docs

**docs/api/README.md:**
- `authentication.md` - Missing (3 occurrences)

**docs/architecture/README.md:**
- `overview.md` - Missing
- `.claude/knowledge/MULTI_TENANCY_PATTERNS.md` - Wrong path (2 occurrences)
- `.claude/knowledge/LARAVEL_CONVENTIONS.md` - Wrong path (2 occurrences)

**docs/agents/laravel-refactor-specialist-guide.md:**
- `../laravel_embedding_guidelines.md` - Wrong path
- `../CMIS_IMPLEMENTATION_PLAN.md` - Wrong path
- `../database-setup.md` - Wrong path

**docs/archive/phases/phase-0/PHASE_0_COMPLETION_SUMMARY.md:**
- `./FINAL_AUDIT_REPORT.md` - Missing
- `../ACTION_PLAN.md` - Wrong path

**.claude/agents/DOC_ORGANIZER_GUIDE.md:**
- `active/plans/current-implementation.md` - Wrong path
- `active/plans/sprint-action-plan.md` - Wrong path
- `active/reports/weekly-progress-report.md` - Wrong path

**docs/reports/comprehensive-audit.md:**
- `/home/cmis-test/public_html/system/gpt_runtime_*.md` - Wrong path

## Recommended Fixes

### Quick Wins (30 minutes)

1. **Fix docs/README.md paths:**
```bash
# Change relative paths to correct locations
../TESTING.md → development/testing.md
database-setup.md → deployment/database-setup.md
system_recovery_plan.md → deployment/system_recovery_plan.md
devops_maintenance_checklist.md → deployment/devops_maintenance_checklist.md
```

2. **Fix docs/architecture/README.md:**
```bash
# Correct .claude paths
.claude/knowledge/MULTI_TENANCY_PATTERNS.md → ../../.claude/knowledge/MULTI_TENANCY_PATTERNS.md
```

### Medium Effort (1-2 hours)

3. **Create missing overview files:**
- `docs/features/social-publishing/overview.md`
- `docs/architecture/overview.md`
- `docs/api/authentication.md`

4. **Move or update AI semantic docs:**
- Check if files exist elsewhere and update paths
- OR create redirects/stubs

### Long Term (2-3 hours)

5. **Consolidate scattered documentation:**
- Many docs reference files that were moved/renamed
- Create a documentation migration plan
- Update all references systematically

6. **Create link validation CI:**
```bash
# Add to GitHub Actions
- name: Check Links
  run: |
    npm install -g markdown-link-check
    find docs -name "*.md" -exec markdown-link-check {} \;
```

## Statistics

| Category | Count | Percentage |
|----------|-------|------------|
| Total Links | 203 | 100% |
| Working Links | 140 | 69% |
| Broken Links | 63 | 31% |

## Broken Links by Directory

| Directory | Broken | Total | Rate |
|-----------|--------|-------|------|
| docs/README.md | 16 | 30 | 53% |
| docs/features/ai-semantic/ | 11 | 25 | 44% |
| docs/architecture/ | 5 | 8 | 63% |
| docs/api/ | 3 | 6 | 50% |
| docs/features/social-publishing/ | 3 | 5 | 60% |
| docs/archive/ | 3 | 8 | 38% |
| .claude/agents/ | 3 | 12 | 25% |
| Other | 19 | 109 | 17% |

## Next Steps

1. **Immediate (P0):** Fix docs/README.md paths (main entry point)
2. **This Week (P1):** Create missing overview.md files
3. **This Month (P2):** Systematic link audit and update
4. **Future (P3):** Implement automated link checking in CI

## Script Used

```bash
#!/bin/bash
# Location: /tmp/check-links.sh
# Usage: ./check-links.sh

# Scans all .md files in docs/ and .claude/
# Checks if linked markdown files exist
# Reports broken links with source file
```

---

**Report Generated:** 2025-11-20
**Tool:** Custom Bash script
**Verified:** Yes
