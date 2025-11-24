#!/bin/bash

# CMIS Knowledge Hooks Installation Script
# This script installs git hooks for auto-updating knowledge maps

echo "🔧 Installing CMIS Knowledge Auto-Update Hooks..."
echo ""

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo "❌ Error: Not in a git repository root directory"
    echo "   Please run this script from the project root"
    exit 1
fi

# Check if .claude/hooks directory exists
if [ ! -d ".claude/hooks" ]; then
    echo "❌ Error: .claude/hooks directory not found"
    exit 1
fi

# Ensure .git/hooks directory exists
mkdir -p .git/hooks

# Install post-commit hook
HOOK_SOURCE=".claude/hooks/post-commit"
HOOK_DEST=".git/hooks/post-commit"

if [ ! -f "$HOOK_SOURCE" ]; then
    echo "❌ Error: Source hook not found: $HOOK_SOURCE"
    exit 1
fi

# Check if hook already exists
if [ -f "$HOOK_DEST" ]; then
    echo "⚠️  Warning: $HOOK_DEST already exists"
    echo ""
    echo "Options:"
    echo "  1. Backup and replace (recommended)"
    echo "  2. Skip installation"
    echo "  3. View existing hook"
    echo ""
    read -p "Choose option (1/2/3): " choice

    case $choice in
        1)
            # Backup existing hook
            BACKUP_FILE="${HOOK_DEST}.backup.$(date +%Y%m%d_%H%M%S)"
            cp "$HOOK_DEST" "$BACKUP_FILE"
            echo "✅ Backed up existing hook to: $BACKUP_FILE"
            ;;
        2)
            echo "⏭️  Skipping installation"
            exit 0
            ;;
        3)
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            cat "$HOOK_DEST"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo ""
            read -p "Continue with installation? (y/n): " continue
            if [ "$continue" != "y" ]; then
                echo "⏭️  Skipping installation"
                exit 0
            fi
            ;;
        *)
            echo "❌ Invalid choice"
            exit 1
            ;;
    esac
fi

# Copy hook to .git/hooks
cp "$HOOK_SOURCE" "$HOOK_DEST"

# Make hook executable
chmod +x "$HOOK_DEST"

echo "✅ post-commit hook installed: $HOOK_DEST"
echo ""

# Verify installation
if [ -x "$HOOK_DEST" ]; then
    echo "✅ Hook is executable"
else
    echo "⚠️  Warning: Hook may not be executable"
    echo "   Run: chmod +x $HOOK_DEST"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Installation Complete!"
echo ""
echo "The following hook has been installed:"
echo "  - post-commit: Auto-updates knowledge maps after commits"
echo ""
echo "Knowledge maps will be automatically updated when you commit changes to:"
echo "  - app/Models/*.php"
echo "  - app/Services/*.php"
echo "  - app/Http/Controllers/*.php"
echo "  - app/Repositories/*.php"
echo "  - database/migrations/*.php"
echo ""
echo "Updated knowledge files will be auto-committed with [skip ci] tag."
echo ""
echo "To manually refresh knowledge at any time, run:"
echo "  php artisan knowledge:refresh-all"
echo ""
echo "To check knowledge health, run:"
echo "  php artisan knowledge:health-check"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
