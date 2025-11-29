#!/bin/bash

###############################################################################
# Agent Browser Testing Update Script
#
# Discovers all Claude Code agents and lists their browser testing status
#
# Usage: bash scripts/update-agents-browser-testing.sh
###############################################################################

AGENTS_DIR=".claude/agents"
KNOWLEDGE_PATTERN="*.md"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Claude Code Agent Browser Testing Discovery"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Counters
TOTAL_AGENTS=0
UPDATED_AGENTS=0
NOT_UPDATED=0

# Exclude these documentation files
EXCLUDE_FILES=(
    "README.md"
    "USAGE_EXAMPLES.md"
    "AGENT_OPTIMIZATION_SUMMARY.md"
    "DOC_ORGANIZER_GUIDE.md"
)

# Temp files for lists
UPDATED_LIST=$(mktemp)
NOT_UPDATED_LIST=$(mktemp)

echo "📁 Scanning directory: $AGENTS_DIR"
echo ""

# Find all agent files
while IFS= read -r file; do
    filename=$(basename "$file")

    # Skip excluded files
    skip=false
    for exclude in "${EXCLUDE_FILES[@]}"; do
        if [[ "$filename" == "$exclude" ]]; then
            skip=true
            break
        fi
    done

    # Skip shared knowledge base files
    if [[ "$file" == *"_shared"* ]]; then
        skip=true
    fi

    if [[ "$skip" == true ]]; then
        continue
    fi

    ((TOTAL_AGENTS++))

    # Check if agent has browser testing section
    if grep -q "Browser Testing" "$file"; then
        ((UPDATED_AGENTS++))
        echo "$file" >> "$UPDATED_LIST"
        echo "✅ $filename"
    else
        ((NOT_UPDATED++))
        echo "$file" >> "$NOT_UPDATED_LIST"
        echo "❌ $filename"
    fi

done < <(find "$AGENTS_DIR" -name "$KNOWLEDGE_PATTERN" -type f | sort)

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Discovery Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Total agents discovered: $TOTAL_AGENTS"
echo "✅ Updated with browser testing: $UPDATED_AGENTS"
echo "❌ Not yet updated: $NOT_UPDATED"
echo ""

if [[ $UPDATED_AGENTS -gt 0 ]]; then
    PERCENTAGE=$((UPDATED_AGENTS * 100 / TOTAL_AGENTS))
    echo "Coverage: $PERCENTAGE%"
    echo ""
fi

# Show updated agents categorized
if [[ $UPDATED_AGENTS -gt 0 ]]; then
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ Updated Agents"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""

    # Laravel agents
    echo "📦 Laravel Agents:"
    grep "laravel-" "$UPDATED_LIST" | while read file; do
        echo "   - $(basename $file)"
    done
    echo ""

    # CMIS agents
    echo "🎯 CMIS Agents:"
    grep "cmis-" "$UPDATED_LIST" | while read file; do
        echo "   - $(basename $file)"
    done
    echo ""

    # Other agents
    echo "🔧 Other Agents:"
    grep -v "laravel-\|cmis-" "$UPDATED_LIST" | while read file; do
        echo "   - $(basename $file)"
    done
    echo ""
fi

# Show not updated agents
if [[ $NOT_UPDATED -gt 0 ]]; then
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "❌ Not Yet Updated ($NOT_UPDATED agents)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    cat "$NOT_UPDATED_LIST" | while read file; do
        echo "   - $(basename $file)"
    done
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 Next Steps"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "To update remaining agents:"
echo "  1. Review agent-specific use cases"
echo "  2. Add tailored browser testing section"
echo "  3. Re-run this script to verify"
echo ""
echo "Browser testing documentation:"
echo "  - CLAUDE.md → Browser Testing Environment"
echo "  - /scripts/browser-tests/README.md"
echo "  - .claude/agents/_shared/browser-testing-integration.md"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Cleanup
rm -f "$UPDATED_LIST" "$NOT_UPDATED_LIST"

exit 0
