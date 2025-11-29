#!/bin/bash

###############################################################################
# Update Agent Files with Comprehensive Browser Testing
#
# This script updates all Claude Code agent files with the new comprehensive
# browser testing section including mobile responsive and cross-browser tests.
#
# Usage: bash scripts/update-agents-comprehensive-testing.sh
###############################################################################

AGENTS_DIR=".claude/agents"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📱 Updating Agents with Comprehensive Browser Testing"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# New comprehensive browser testing section
read -r -d '' NEW_SECTION << 'ENDSECTION'
## 🌐 Browser Testing Integration (MANDATORY)

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

ENDSECTION

count=0
errors=0

for file in "$AGENTS_DIR"/*.md; do
    filename=$(basename "$file")

    # Skip shared directory files, README, and other non-agent files
    if [[ "$file" == *"_shared"* ]] || \
       [[ "$filename" == "README.md" ]] || \
       [[ "$filename" == "USAGE_EXAMPLES.md" ]] || \
       [[ "$filename" == "AGENT_OPTIMIZATION_SUMMARY.md" ]]; then
        continue
    fi

    # Check if file has old browser testing section
    if grep -q "## 🌐 Browser Testing Integration" "$file"; then
        # Get custom "When This Agent Should Use" section if exists
        custom_when=$(sed -n '/### When This Agent Should Use Browser Testing/,/^\*\*Documentation/p' "$file" 2>/dev/null | head -n -1)

        # Extract everything before browser testing section
        head_content=$(sed '/## 🌐 Browser Testing Integration/,$d' "$file")

        # Write updated file
        {
            echo "$head_content"
            echo ""
            echo "$NEW_SECTION"

            # Add custom section if exists, otherwise use generic
            if [ -n "$custom_when" ]; then
                echo "$custom_when"
            else
                echo "### When This Agent Should Use Browser Testing"
                echo ""
                echo "- Verify UI changes render correctly"
                echo "- Test visual output across devices and browsers"
                echo "- Validate RTL/LTR layouts for both languages"
            fi

            echo ""
            echo "**Documentation**: \`CLAUDE.md\` → Browser Testing Environment"
            echo "**Full Guide**: \`.claude/knowledge/BROWSER_TESTING_GUIDE.md\`"
            echo ""
            echo "---"
            echo ""
            echo "**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites"
        } > "${file}.tmp"

        mv "${file}.tmp" "$file"
        ((count++))
        echo "  ✅ $filename"
    fi
done

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Update Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Updated: $count agents"
echo ""
echo "New test suites now documented in all agents:"
echo "  📱 Mobile Responsive: 7 device profiles"
echo "  🌐 Cross-Browser: Chrome, Firefox, Safari"
echo "  🌍 Bilingual: Arabic (RTL) + English (LTR)"
echo "  ⚡ Quick Mode: --quick flag for fast testing"
echo ""
echo "Full documentation: .claude/knowledge/BROWSER_TESTING_GUIDE.md"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
