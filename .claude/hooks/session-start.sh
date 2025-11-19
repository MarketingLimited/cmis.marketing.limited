#!/bin/bash
# Session start hook - runs when Claude Code session begins
# Displays project status and important information

echo "🚀 CMIS Project - Claude Code Session Started"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Show current branch
BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
echo "📍 Current Branch: $BRANCH"

# Show git status
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    CHANGED=$(git status --porcelain | wc -l)
    echo "📝 Working Directory: $CHANGED file(s) changed"
else
    echo "📝 Working Directory: Clean"
fi

echo ""

# Check if Laravel env is configured
if [ ! -f ".env" ]; then
    echo "⚠️  Warning: .env file not found"
    echo "   Run: cp .env.example .env && php artisan key:generate"
fi

# Check if dependencies are installed
if [ ! -d "vendor" ]; then
    echo "⚠️  Warning: Composer dependencies not installed"
    echo "   Run: composer install"
fi

if [ ! -d "node_modules" ]; then
    echo "⚠️  Warning: NPM dependencies not installed"
    echo "   Run: npm install"
fi

echo ""
echo "📚 Quick Reference:"
echo "   • CLAUDE.md - Project guidelines"
echo "   • .claude/agents/README.md - Available AI agents"
echo "   • .claude/CMIS_PROJECT_KNOWLEDGE.md - Technical docs"
echo ""
echo "🤖 Specialized Agents Available:"
echo "   • cmis-orchestrator - Multi-domain coordination"
echo "   • cmis-multi-tenancy - RLS & data isolation expert"
echo "   • cmis-platform-integration - OAuth & webhooks"
echo "   • cmis-ai-semantic - pgvector & embeddings"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
