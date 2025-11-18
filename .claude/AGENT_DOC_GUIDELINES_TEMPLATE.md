# Documentation Output Guidelines Template
# This section should be added to ALL AI agents

---

## 📝 DOCUMENTATION OUTPUT RULES

### Critical: Organized Documentation Structure

**IMPORTANT**: This agent MUST follow organized documentation structure. Never create documentation files in the project root directory.

### Documentation Paths by Type

| Documentation Type | Correct Path | Example |
|-------------------|-------------|---------|
| **Active Plans** | `docs/active/plans/` | `docs/active/plans/implementation-feature-x.md` |
| **Active Reports** | `docs/active/reports/` | `docs/active/reports/weekly-progress.md` |
| **Analyses** | `docs/active/analysis/` | `docs/active/analysis/performance-audit.md` |
| **API Docs** | `docs/api/` | `docs/api/rest-endpoints-reference.md` |
| **Architecture** | `docs/architecture/` | `docs/architecture/system-design.md` |
| **Setup Guides** | `docs/guides/setup/` | `docs/guides/setup/local-environment.md` |
| **Dev Guides** | `docs/guides/development/` | `docs/guides/development/coding-standards.md` |
| **Deployment** | `docs/guides/deployment/` | `docs/guides/deployment/production-deploy.md` |
| **Database Docs** | `docs/reference/database/` | `docs/reference/database/schema-overview.md` |

### Naming Conventions

**Active Documents**: Use lowercase with hyphens
```
✅ implementation-ai-search.md
✅ performance-analysis-report.md
✅ api-integration-guide.md

❌ IMPLEMENTATION.md
❌ Report.md
❌ new_doc.md
```

**Archival**: When work is completed
```
Move: docs/active/plans/feature-x.md
  To: docs/archive/plans/feature-x-2024-11-18.md
```

### What NOT to Do

❌ **NEVER create files in root:**
```
# WRONG - Do not do this!
/NEW_ANALYSIS.md
/IMPLEMENTATION_PLAN.md
/AUDIT_REPORT.md
/ARCHITECTURE_REVIEW.md
```

✅ **ALWAYS use organized paths:**
```
# CORRECT - Always do this!
docs/active/analysis/performance-audit.md
docs/active/plans/current-implementation.md
docs/archive/reports/audit-report-2024-11.md
docs/architecture/system-overview.md
```

### Integration with cmis-doc-organizer

This agent works alongside `cmis-doc-organizer`:
- This agent: Creates docs in correct locations
- `cmis-doc-organizer`: Maintains structure, archives old docs, consolidates duplicates

If you accidentally create docs in wrong location:
```
@cmis-doc-organizer organize all documentation
```

### Output Guidelines by Agent Type

#### For Analysis/Audit Agents:
```
Output Path: docs/active/analysis/
Filename: {topic}-{type}-{date}.md
Example: database-performance-audit-2024-11-18.md

When completed:
Move to: docs/archive/analyses/
```

#### For Implementation/Planning Agents:
```
Output Path: docs/active/plans/
Filename: {feature}-implementation.md
Example: ai-search-implementation.md

When completed:
Move to: docs/archive/plans/
```

#### For Report-Generating Agents:
```
Output Path: docs/active/reports/
Filename: {type}-report-{period}.md
Example: weekly-progress-report-2024-11-18.md

After 30 days:
Move to: docs/archive/reports/
```

#### For Documentation Agents:
```
Permanent Docs:
- API docs → docs/api/
- Architecture → docs/architecture/
- Guides → docs/guides/{setup|development|deployment}/
- Reference → docs/reference/{database|models|apis}/

These are versioned in git, not archived.
```

### Before Creating Any Documentation

1. **Check if directory exists:**
   ```bash
   # If creating API documentation
   test -d docs/api || mkdir -p docs/api
   ```

2. **Use appropriate filename:**
   ```
   # Not generic!
   ❌ report.md
   ❌ analysis.md

   # Descriptive!
   ✅ api-security-analysis.md
   ✅ database-optimization-report.md
   ```

3. **Update documentation index:**
   ```
   After creating a new document, it should be added to docs/README.md
   (or use @cmis-doc-organizer to update index automatically)
   ```

### When User Requests Documentation

**User Request**: "Create an implementation plan"

**Agent Response**:
```markdown
I'll create the implementation plan at the correct location:
`docs/active/plans/current-implementation.md`

[Creates file with content]

The plan has been created at docs/active/plans/current-implementation.md
You can find it in the active documentation directory.
```

**NOT**:
```markdown
❌ I'll create IMPLEMENTATION_PLAN.md in the root directory
```

### Template for Agent Output Messages

When creating documentation, always inform user of correct path:

```
✅ Created: docs/active/analysis/security-audit.md
✅ Created: docs/api/rest-api-v2-reference.md
✅ Created: docs/guides/setup/docker-environment.md

All documentation organized in docs/ directory structure.
```

### Collaboration Between Agents

When multiple agents work together:

1. **Architect Agent** → `docs/architecture/system-design.md`
2. **Security Agent** → `docs/active/analysis/security-audit.md`
3. **Documentation Agent** → Consolidates into `docs/guides/`
4. **Doc Organizer Agent** → Maintains structure, creates index

### Quick Reference

```bash
# Active work (current projects)
docs/active/
  ├── plans/      # Implementation plans
  ├── reports/    # Progress reports
  ├── analysis/   # Ongoing analyses
  └── progress/   # Sprint tracking

# Permanent documentation
docs/api/           # API references
docs/architecture/  # System design
docs/guides/        # How-to guides

# Completed work
docs/archive/
  ├── plans/      # Old plans
  ├── reports/    # Historical reports
  ├── sessions/   # Session summaries
  └── analyses/   # Past analyses
```

### Enforcement

These rules are **MANDATORY** for all agents. Violations will result in:
1. Documentation chaos
2. Difficulty finding information
3. Need for manual reorganization
4. Wasted time

**Remember**: Clean project structure starts with organized documentation! 🎯

---

**See Also**:
- `.claude/agents/cmis-doc-organizer.md` - Documentation organization agent
- `.claude/DOC_STRUCTURE_TEMPLATE.md` - Full structure template
- `.claude/AGENT_USAGE_DOC_ORGANIZER.md` - Usage guide
