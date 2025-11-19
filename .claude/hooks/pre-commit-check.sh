#!/bin/bash
# Pre-commit hook to check for common issues before committing
# This hook is designed for CMIS Laravel project

set -e

echo "🔍 Running pre-commit checks..."

# Check for debugging statements
echo "  → Checking for debugging code..."
if git diff --cached --name-only | grep -E '\.(php|js|blade\.php)$' | xargs grep -nE '(dd\(|dump\(|var_dump|console\.log|debugger)' 2>/dev/null; then
    echo "❌ Found debugging statements in staged files!"
    echo "   Please remove dd(), dump(), var_dump(), console.log(), or debugger before committing."
    exit 1
fi

# Check for TODO/FIXME without issue references
echo "  → Checking for TODO/FIXME comments..."
if git diff --cached --name-only | grep -E '\.(php|js|blade\.php)$' | xargs grep -nE '(TODO|FIXME):(?! #[0-9]+)' 2>/dev/null; then
    echo "⚠️  Warning: Found TODO/FIXME without issue reference"
    echo "   Consider adding issue reference: // TODO: #123 description"
fi

# Check for hardcoded credentials or secrets
echo "  → Scanning for potential secrets..."
SECRET_PATTERNS=(
    "password.*=.*['\"][^'\"]['\"]"
    "api[_-]?key.*=.*['\"][^'\"]['\"]"
    "secret.*=.*['\"][^'\"]['\"]"
    "token.*=.*['\"][^'\"]['\"]"
    "PGPASSWORD=[^$]"
)

for pattern in "${SECRET_PATTERNS[@]}"; do
    if git diff --cached --name-only | xargs grep -inE "$pattern" 2>/dev/null; then
        echo "❌ Potential hardcoded secret found!"
        echo "   Pattern: $pattern"
        echo "   Please use environment variables or Laravel's encrypted storage."
        exit 1
    fi
done

# Check for .env file in staged files
if git diff --cached --name-only | grep -qE '^\.env$'; then
    echo "❌ Attempting to commit .env file!"
    echo "   This file contains secrets and should never be committed."
    exit 1
fi

# Check PHP syntax errors
echo "  → Checking PHP syntax..."
for file in $(git diff --cached --name-only | grep '\.php$'); do
    if [ -f "$file" ]; then
        php -l "$file" > /dev/null 2>&1 || {
            echo "❌ PHP syntax error in $file"
            exit 1
        }
    fi
done

# Check for migrations without down() method
echo "  → Checking migrations..."
for file in $(git diff --cached --name-only | grep 'database/migrations/.*\.php$'); do
    if [ -f "$file" ]; then
        if ! grep -q "public function down()" "$file"; then
            echo "⚠️  Warning: Migration $file missing down() method"
        fi
        # Check for RLS policies in new tables
        if grep -q "Schema::create" "$file"; then
            if ! grep -qE "(ENABLE ROW LEVEL SECURITY|CREATE POLICY)" "$file"; then
                echo "⚠️  Warning: New table in $file may be missing RLS policies"
                echo "   CMIS requires RLS policies on all tables for multi-tenancy"
            fi
        fi
    fi
done

# Check Laravel code style (if pint is available)
if [ -f "vendor/bin/pint" ]; then
    echo "  → Running Laravel Pint..."
    vendor/bin/pint --test $(git diff --cached --name-only | grep '\.php$' | tr '\n' ' ') 2>/dev/null || {
        echo "⚠️  Code style issues found. Run 'vendor/bin/pint' to fix."
    }
fi

echo "✅ Pre-commit checks passed!"
exit 0
