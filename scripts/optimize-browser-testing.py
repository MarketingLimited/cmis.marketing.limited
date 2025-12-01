#!/usr/bin/env python3
"""
Replace duplicated browser testing sections in agent files with a reference
to the shared file, while preserving agent-specific content.
"""

import os
import re
import glob

AGENTS_DIR = '/home/cmis-test/public_html/.claude/agents'

# The generic content to remove (everything between the header and agent-specific section)
GENERIC_SECTION_PATTERN = r'''## 🌐 Browser Testing Integration \(MANDATORY\)

\*\*📖 Full Guide:\*\* `\.claude/knowledge/BROWSER_TESTING_GUIDE\.md`

### CMIS Test Suites

\| Test Suite \| Command \| Use Case \|
\|------------|---------|----------|
\| \*\*Mobile Responsive\*\* \| `node scripts/browser-tests/mobile-responsive-comprehensive\.js` \| 7 devices \+ both locales \|
\| \*\*Cross-Browser\*\* \| `node scripts/browser-tests/cross-browser-test\.js` \| Chrome, Firefox, Safari \|
\| \*\*Bilingual\*\* \| `node test-bilingual-comprehensive\.cjs` \| All pages in AR/EN \|
\| \*\*Quick Mode\*\* \| Add `--quick` flag \| Fast testing \(5 pages\) \|

### Quick Commands

```bash
# Mobile responsive \(quick\)
node scripts/browser-tests/mobile-responsive-comprehensive\.js --quick

# Cross-browser \(quick\)
node scripts/browser-tests/cross-browser-test\.js --quick

# Single browser
node scripts/browser-tests/cross-browser-test\.js --browser chrome
```

### Test Environment

- \*\*URL\*\*: https://cmis-test\.kazaaz\.com/
- \*\*Auth\*\*: `admin@cmis\.test` / `password`
- \*\*Languages\*\*: Arabic \(RTL\), English \(LTR\)

### Issues Checked Automatically

\*\*Mobile:\*\* Horizontal overflow, touch targets, font sizes, viewport meta, RTL/LTR
\*\*Browser:\*\* CSS support, broken images, SVG rendering, JS errors, layout metrics
'''

# The replacement reference
REFERENCE_TEXT = '''## 🌐 Browser Testing

**📖 See:** `.claude/agents/_shared/browser-testing-integration.md`

'''

def process_file(filepath):
    """Process a single agent file."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if file has the duplicated section
    if '## 🌐 Browser Testing Integration (MANDATORY)' not in content:
        return False, "No duplication found"

    # Replace the generic section with the reference
    # Keep the "When This Agent Should Use" section
    new_content = re.sub(
        GENERIC_SECTION_PATTERN,
        REFERENCE_TEXT,
        content,
        flags=re.MULTILINE
    )

    if new_content == content:
        return False, "Pattern not matched"

    # Write the updated content
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)

    return True, f"Saved {len(content) - len(new_content)} bytes"

def main():
    """Process all agent files."""
    agent_files = glob.glob(os.path.join(AGENTS_DIR, '*.md'))

    # Exclude shared files
    agent_files = [f for f in agent_files if '/_shared/' not in f]

    total = len(agent_files)
    updated = 0
    failed = 0
    bytes_saved = 0

    print(f"Processing {total} agent files...")
    print()

    for filepath in sorted(agent_files):
        filename = os.path.basename(filepath)
        success, message = process_file(filepath)

        if success:
            updated += 1
            # Extract bytes saved from message
            if 'bytes' in message:
                saved = int(message.split()[1])
                bytes_saved += saved
            print(f"✅ {filename}: {message}")
        elif "No duplication found" in message:
            print(f"⏭️  {filename}: Already optimized or no browser section")
        else:
            failed += 1
            print(f"❌ {filename}: {message}")

    print()
    print("=" * 50)
    print(f"Total files: {total}")
    print(f"Updated: {updated}")
    print(f"Skipped: {total - updated - failed}")
    print(f"Failed: {failed}")
    print(f"Bytes saved: {bytes_saved:,} ({bytes_saved / 1024:.1f} KB)")

if __name__ == '__main__':
    main()
