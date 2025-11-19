#!/bin/bash
#
# Claude Code Framework Optimization Script
# Optimizes all files in .claude/ directory based on 2025 best practices
#

set -e

echo "🚀 Claude Code Framework Optimization"
echo "======================================"
echo ""

# Create docs directory structure if it doesn't exist
echo "📁 Creating documentation structure..."
mkdir -p docs/archive/plans
mkdir -p docs/archive/reports
mkdir -p docs/archive/analysis
mkdir -p docs/active/plans
mkdir -p docs/active/reports
mkdir -p docs/active/analysis

# Step 1: Remove duplicate files from .claude/ root
echo ""
echo "🧹 Step 1: Removing duplicate files from .claude/ root..."

DUPLICATES=(
    "CMIS_PROJECT_KNOWLEDGE.md"
    "CMIS_DATA_PATTERNS.md"
    "CMIS_SQL_INSIGHTS.md"
    "CMIS_REFERENCE_DATA.md"
)

for file in "${DUPLICATES[@]}"; do
    if [ -f ".claude/$file" ]; then
        echo "   Removing .claude/$file (exists in .claude/knowledge/)"
        rm ".claude/$file"
    fi
done

# Step 2: Archive old documentation
echo ""
echo "📦 Step 2: Archiving old documentation files..."

# Archive analysis report
if [ -f ".claude/ANALYSIS_MASTER_REPORT.md" ]; then
    echo "   Moving ANALYSIS_MASTER_REPORT.md to docs/archive/analysis/"
    mv ".claude/ANALYSIS_MASTER_REPORT.md" "docs/archive/analysis/master-analysis-2024-11-18.md"
fi

# Archive implementation docs
if [ -f ".claude/IMPLEMENTATION_QUICKSTART.md" ]; then
    echo "   Moving IMPLEMENTATION_QUICKSTART.md to docs/archive/plans/"
    mv ".claude/IMPLEMENTATION_QUICKSTART.md" "docs/archive/plans/implementation-quickstart-2024-11.md"
fi

# Archive templates
TEMPLATES=(
    "DOC_STRUCTURE_TEMPLATE.md"
    "AGENT_DOC_GUIDELINES_TEMPLATE.md"
    "AGENT_USAGE_DOC_ORGANIZER.md"
)

for template in "${TEMPLATES[@]}"; do
    if [ -f ".claude/$template" ]; then
        echo "   Moving $template to docs/archive/"
        mv ".claude/$template" "docs/archive/$template"
    fi
done

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "📊 Summary:"
echo "   - Removed 4 duplicate files from .claude/ root"
echo "   - Archived 5 documentation files to docs/"
echo "   - Next: Run Python script to add YAML frontmatter to agents"
echo ""
echo "Run: python3 .claude/add-frontmatter.py"
