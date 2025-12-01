#!/usr/bin/env python3
"""
Replace duplicated browser testing sections in agent files with a reference.
Uses exact string matching for reliability.
"""

import os
import glob

AGENTS_DIR = '/home/cmis-test/public_html/.claude/agents'

# The exact text to find and remove (the generic duplicated content)
OLD_TEXT = '''## 🌐 Browser Testing Integration (MANDATORY)

**📖 Full Guide:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

### CMIS Test Suites

| Test Suite | Command | Use Case |
|------------|---------|----------|
| **Mobile Responsive** | `node scripts/browser-tests/mobile-responsive-comprehensive.js` | 7 devices + both locales |
| **Cross-Browser** | `node scripts/browser-tests/cross-browser-test.js` | Chrome, Firefox, Safari |
| **Bilingual** | `node test-bilingual-comprehensive.cjs` | All pages in AR/EN |
| **Quick Mode** | Add `--quick` flag | Fast testing (5 pages) |

### Quick Commands

```bash
# Mobile responsive (quick)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (quick)
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser
node scripts/browser-tests/cross-browser-test.js --browser chrome
```

### Test Environment

- **URL**: https://cmis-test.kazaaz.com/
- **Auth**: `admin@cmis.test` / `password`
- **Languages**: Arabic (RTL), English (LTR)

### Issues Checked Automatically

**Mobile:** Horizontal overflow, touch targets, font sizes, viewport meta, RTL/LTR
**Browser:** CSS support, broken images, SVG rendering, JS errors, layout metrics
'''

# The replacement - just a reference
NEW_TEXT = '''## 🌐 Browser Testing

**📖 See:** `.claude/agents/_shared/browser-testing-integration.md`

'''

def process_file(filepath):
    """Process a single agent file."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if file has the duplicated section
    if OLD_TEXT not in content:
        return False, "No match"

    # Simple string replacement
    new_content = content.replace(OLD_TEXT, NEW_TEXT)

    if new_content == content:
        return False, "No change"

    # Write the updated content
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)

    bytes_saved = len(content) - len(new_content)
    return True, bytes_saved

def main():
    """Process all agent files."""
    agent_files = glob.glob(os.path.join(AGENTS_DIR, '*.md'))

    # Exclude shared files and README
    agent_files = [f for f in agent_files if '/_shared/' not in f and 'README' not in f]

    total = len(agent_files)
    updated = 0
    total_saved = 0

    print(f"Processing {total} agent files...\n")

    for filepath in sorted(agent_files):
        filename = os.path.basename(filepath)
        success, result = process_file(filepath)

        if success:
            updated += 1
            total_saved += result
            print(f"✅ {filename}: {result} bytes saved")

    print(f"\n{'=' * 50}")
    print(f"Total files: {total}")
    print(f"Updated: {updated}")
    print(f"Skipped: {total - updated}")
    print(f"Total saved: {total_saved:,} bytes ({total_saved / 1024:.1f} KB)")

if __name__ == '__main__':
    main()
