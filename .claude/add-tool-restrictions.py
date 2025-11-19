#!/usr/bin/env python3
"""
Add tool restrictions to agents that don't need full tool access
Based on Claude Code 2025 security best practices
"""

import re
from pathlib import Path

# Define tool restrictions for specific agents
TOOL_RESTRICTIONS = {
    'laravel-documentation.md': {
        'tools': 'Read,Write,Glob,Grep,WebFetch',
        'reason': 'Documentation agent only needs file reading/writing and search'
    },
    'cmis-doc-organizer.md': {
        'tools': 'Read,Write,Edit,Glob,Grep,Bash',
        'reason': 'Doc organizer needs file operations and basic bash for moving files'
    },
    'laravel-api-design.md': {
        'tools': 'Read,Glob,Grep,Write,Edit',
        'reason': 'API design agent primarily reviews and suggests code changes'
    },
}

def add_tools_to_frontmatter(file_path, tools, reason):
    """Add tools restriction to agent frontmatter"""

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already has frontmatter
    if not content.startswith('---'):
        print(f"   ⚠️  {file_path.name} missing frontmatter, skipping...")
        return False

    # Check if already has tools defined
    if re.search(r'^tools:', content, re.MULTILINE):
        print(f"   ⏭️  {file_path.name} already has tools defined, skipping...")
        return False

    # Find the end of frontmatter
    lines = content.split('\n')
    frontmatter_end = -1

    for i, line in enumerate(lines[1:], 1):  # Skip first ---
        if line.strip() == '---':
            frontmatter_end = i
            break

    if frontmatter_end == -1:
        print(f"   ❌ {file_path.name} has malformed frontmatter, skipping...")
        return False

    # Insert tools line before the closing ---
    lines.insert(frontmatter_end, f'tools: {tools}')

    # Write back
    new_content = '\n'.join(lines)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    print(f"   ✅ Added tools restriction to {file_path.name}")
    print(f"      Tools: {tools}")
    print(f"      Reason: {reason}")
    return True

def main():
    base_dir = Path('/home/user/cmis.marketing.limited/.claude/agents')

    print("🔒 Adding Tool Restrictions to Agents")
    print("=" * 60)
    print()

    updated_count = 0
    skipped_count = 0

    for filename, config in TOOL_RESTRICTIONS.items():
        file_path = base_dir / filename

        if not file_path.exists():
            print(f"   ⚠️  {filename} not found, skipping...")
            continue

        if add_tools_to_frontmatter(file_path, config['tools'], config['reason']):
            updated_count += 1
        else:
            skipped_count += 1

        print()

    print("=" * 60)
    print(f"✅ Complete! Updated {updated_count} files, skipped {skipped_count}")
    print()
    print("Tool restrictions improve:")
    print("  • Security - Limits what agents can execute")
    print("  • Focus - Agents work within their domain")
    print("  • Performance - Less tool overhead")
    print()

if __name__ == '__main__':
    main()
