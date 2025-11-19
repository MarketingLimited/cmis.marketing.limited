#!/bin/bash

# Script to optimize agent configurations with model selection and tool restrictions
# Based on Claude Code 2025 best practices

echo "Optimizing CMIS agents configuration..."

# Define lightweight agents that should use haiku (fast, cost-effective)
HAIKU_AGENTS=(
    "cmis-doc-organizer"
    "laravel-documentation"
    "USAGE_EXAMPLES"
    "DOC_ORGANIZER_GUIDE"
)

# Define complex agents that should use sonnet (complex reasoning)
SONNET_AGENTS=(
    "cmis-orchestrator"
    "cmis-context-awareness"
    "cmis-multi-tenancy"
    "cmis-platform-integration"
    "cmis-ai-semantic"
    "cmis-campaign-expert"
    "cmis-ui-frontend"
    "cmis-social-publishing"
    "laravel-architect"
    "laravel-tech-lead"
    "laravel-code-quality"
    "laravel-security"
    "laravel-performance"
    "laravel-db-architect"
    "laravel-testing"
    "laravel-devops"
    "laravel-api-design"
    "laravel-auditor"
    "laravel-refactor-specialist"
)

# Count changes
changed=0

# Update haiku agents
for agent in "${HAIKU_AGENTS[@]}"; do
    file=".claude/agents/${agent}.md"
    if [ -f "$file" ]; then
        if grep -q "^model: sonnet" "$file"; then
            sed -i 's/^model: sonnet/model: haiku/' "$file"
            echo "✓ Changed $agent to haiku (lightweight)"
            ((changed++))
        fi
    fi
done

# Ensure sonnet agents are correctly set
for agent in "${SONNET_AGENTS[@]}"; do
    file=".claude/agents/${agent}.md"
    if [ -f "$file" ]; then
        if grep -q "^model: haiku" "$file"; then
            sed -i 's/^model: haiku/model: sonnet/' "$file"
            echo "✓ Changed $agent to sonnet (complex)"
            ((changed++))
        fi
    fi
done

echo ""
echo "Optimization complete! Changed $changed agent configurations."
echo "Run 'git diff .claude/agents/' to review changes."
