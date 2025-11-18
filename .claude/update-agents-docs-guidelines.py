#!/usr/bin/env python3
"""
Script to add Documentation Output Guidelines to all AI agents
"""

import os
import re
from pathlib import Path

# Documentation guidelines section to add
DOC_GUIDELINES = """
---

## 📝 DOCUMENTATION OUTPUT GUIDELINES

### ⚠️ CRITICAL: Organized Documentation Only

**This agent MUST follow organized documentation structure.**

### Documentation Output Rules

❌ **NEVER create documentation in root directory:**
```
# WRONG!
/ANALYSIS_REPORT.md
/IMPLEMENTATION_PLAN.md
/ARCHITECTURE_DOCS.md
```

✅ **ALWAYS use organized paths:**
```
# CORRECT!
docs/active/analysis/performance-analysis.md
docs/active/plans/feature-implementation.md
docs/architecture/system-design.md
docs/api/rest-api-reference.md
```

### Path Guidelines by Documentation Type

| Type | Path | Example |
|------|------|---------|
| **Active Plans** | `docs/active/plans/` | `ai-feature-implementation.md` |
| **Active Reports** | `docs/active/reports/` | `weekly-progress-report.md` |
| **Analyses** | `docs/active/analysis/` | `security-audit-2024-11.md` |
| **API Docs** | `docs/api/` | `rest-endpoints-v2.md` |
| **Architecture** | `docs/architecture/` | `database-architecture.md` |
| **Setup Guides** | `docs/guides/setup/` | `local-development.md` |
| **Dev Guides** | `docs/guides/development/` | `coding-standards.md` |
| **Database Ref** | `docs/reference/database/` | `schema-overview.md` |

### Naming Convention

Use **lowercase with hyphens**:
```
✅ performance-optimization-plan.md
✅ api-integration-guide.md
✅ security-audit-report.md

❌ PERFORMANCE_PLAN.md
❌ ApiGuide.md
❌ report_final.md
```

### When to Archive

Move completed work to `docs/archive/`:
```bash
# When completed
docs/active/plans/feature-x.md
  → docs/archive/plans/feature-x-2024-11-18.md

# After 30 days
docs/active/reports/progress-oct.md
  → docs/archive/reports/progress-oct-2024.md
```

### Agent Output Template

When creating documentation, inform user:
```
✅ Created documentation at:
   docs/active/analysis/performance-audit.md

✅ You can find this in the organized docs/ structure.
```

### Integration with cmis-doc-organizer

- **This agent**: Creates docs in correct locations
- **cmis-doc-organizer**: Maintains structure, archives, consolidates

If documentation needs organization:
```
@cmis-doc-organizer organize all documentation
```

### Quick Reference Structure

```
docs/
├── active/          # Current work
│   ├── plans/
│   ├── reports/
│   ├── analysis/
│   └── progress/
├── archive/         # Completed work
├── api/             # API documentation
├── architecture/    # System design
├── guides/          # How-to guides
└── reference/       # Quick reference
```

**See**: `.claude/AGENT_DOC_GUIDELINES_TEMPLATE.md` for full guidelines.

---
"""

def agent_needs_update(content):
    """Check if agent already has documentation guidelines"""
    return "DOCUMENTATION OUTPUT GUIDELINES" not in content and \
           "📝 DOCUMENTATION" not in content

def update_agent_file(filepath):
    """Add documentation guidelines to an agent file"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Skip if already updated
    if not agent_needs_update(content):
        return False

    # Skip specific files
    filename = os.path.basename(filepath)
    if filename in ['README.md', 'USAGE_EXAMPLES.md', 'cmis-doc-organizer.md',
                    'DOC_ORGANIZER_GUIDE.md']:
        return False

    # Add guidelines before the last line (usually just a newline or end marker)
    # Find the best insertion point
    lines = content.split('\n')

    # Try to insert before final newlines or at end
    insert_index = len(lines)
    for i in range(len(lines) - 1, max(0, len(lines) - 10), -1):
        if lines[i].strip() and not lines[i].startswith('**'):
            insert_index = i + 1
            break

    # Insert the guidelines
    lines.insert(insert_index, DOC_GUIDELINES)
    updated_content = '\n'.join(lines)

    # Write back
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(updated_content)

    return True

def main():
    """Update all agent files"""
    agents_dir = Path('.claude/agents')

    if not agents_dir.exists():
        print("❌ .claude/agents directory not found!")
        return

    updated_count = 0
    skipped_count = 0

    # Get all .md files
    agent_files = sorted(agents_dir.glob('*.md'))

    print(f"Found {len(agent_files)} agent files to process...")
    print()

    for agent_file in agent_files:
        filename = agent_file.name

        try:
            if update_agent_file(agent_file):
                print(f"✅ Updated: {filename}")
                updated_count += 1
            else:
                print(f"⏭️  Skipped: {filename} (already has guidelines or excluded)")
                skipped_count += 1
        except Exception as e:
            print(f"❌ Error updating {filename}: {e}")

    print()
    print("=" * 60)
    print(f"✅ Updated: {updated_count} agents")
    print(f"⏭️  Skipped: {skipped_count} agents")
    print(f"📊 Total: {len(agent_files)} agents")
    print("=" * 60)

if __name__ == '__main__':
    main()
