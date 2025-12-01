---
description: Generate and analyze test coverage report for CMIS
---

Generate comprehensive test coverage report for CMIS:

## Step 1: Check Prerequisites

```bash
# Verify PHPUnit is available
test -f vendor/bin/phpunit && echo "✅ PHPUnit available" || echo "❌ PHPUnit not installed"

# Check for Xdebug (required for coverage)
php -m | grep -i xdebug && echo "✅ Xdebug available" || echo "⚠️ Xdebug not found - coverage may be limited"
```

## Step 2: Run Tests with Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html coverage/ --coverage-text 2>&1

# If Xdebug not available, try PCOV
# vendor/bin/phpunit --coverage-html coverage/ --coverage-text --coverage-filter app/
```

## Step 3: Analyze Coverage Results

Parse coverage output and report:

### Overall Metrics
- Total Lines of Code
- Lines Covered
- Coverage Percentage

### By Directory
```
app/Models/       XX% coverage
app/Services/     XX% coverage
app/Http/         XX% coverage
app/Repositories/ XX% coverage
```

### Low Coverage Areas (< 70%)
List files/directories with coverage below 70%:
1. [File/Directory] - XX%
2. [File/Directory] - XX%

### Critical Uncovered Code
Identify uncovered code in critical areas:
- Multi-tenancy (RLS context)
- Platform integrations (OAuth, webhooks)
- AI/Embedding services

## Step 4: Generate Recommendations

Based on coverage analysis:

1. **High Priority** - Uncovered critical business logic
2. **Medium Priority** - Partially covered services
3. **Low Priority** - Utility code with low coverage

## Step 5: Summary Report

```
=== CMIS Test Coverage Report ===
Overall Coverage: XX%

By Component:
  Models:       XX% (XXX/XXX lines)
  Services:     XX% (XXX/XXX lines)
  Controllers:  XX% (XXX/XXX lines)
  Repositories: XX% (XXX/XXX lines)

Low Coverage Alerts:
  ⚠️ [File] - XX% (critical)
  ⚠️ [File] - XX% (needs attention)

Recommendations:
  1. Add tests for [specific area]
  2. Increase coverage in [component]
```

## Notes

- Coverage report saved to `coverage/` directory
- Open `coverage/index.html` in browser for detailed view
- Focus on business logic, not Laravel framework code
- RLS-related code should have 100% coverage
