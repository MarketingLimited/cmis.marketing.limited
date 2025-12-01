---
description: Run PHP code linting with PHPCS, PHPStan, and Laravel Pint
---

Run comprehensive code linting for CMIS codebase:

## Step 1: Check Available Tools

First, verify which linting tools are installed:

```bash
# Check for PHP CodeSniffer
test -f vendor/bin/phpcs && echo "✅ PHPCS available" || echo "❌ PHPCS not installed"

# Check for PHPStan
test -f vendor/bin/phpstan && echo "✅ PHPStan available" || echo "❌ PHPStan not installed"

# Check for Laravel Pint
test -f vendor/bin/pint && echo "✅ Pint available" || echo "❌ Pint not installed"
```

## Step 2: Run Available Linters

### PHP CodeSniffer (PSR-12 compliance)
```bash
vendor/bin/phpcs app/ --standard=PSR12 --report=summary --colors 2>/dev/null || echo "PHPCS not available or failed"
```

### PHPStan (Static Analysis)
```bash
vendor/bin/phpstan analyse app/ --level=5 --no-progress 2>/dev/null || echo "PHPStan not available or failed"
```

### Laravel Pint (Code Style)
```bash
vendor/bin/pint --test 2>/dev/null || echo "Pint not available or failed"
```

## Step 3: Report Results

For each tool that ran:
1. Count total issues found
2. Categorize by severity (error, warning, info)
3. List top 5 most common issues
4. Suggest fixes for critical issues

## Step 4: Auto-Fix Option

If issues found, ask user:
- "Would you like to auto-fix code style issues with Pint?"
- If yes: `vendor/bin/pint`

## Important Notes

- Focus on `app/` directory (main application code)
- Ignore `vendor/`, `node_modules/`, `storage/`
- Report issues without modifying files unless user approves
- For CMIS-specific patterns, check RLS compliance in database queries

## Quick Summary Format

```
=== CMIS Code Quality Report ===
PHPCS:   X errors, Y warnings
PHPStan: X errors (level 5)
Pint:    X files need formatting

Top Issues:
1. [Issue type] - X occurrences
2. [Issue type] - X occurrences
...
```
