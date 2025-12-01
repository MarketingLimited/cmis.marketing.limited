---
description: Quick project status - git, migrations, tests, and system health summary
---

# Project Status Command

Get a quick overview of the CMIS project state.

## Quick Status Check

### 1. Git Status
```bash
echo "=== GIT STATUS ===" && \
git status --short && \
echo "" && \
echo "Branch: $(git branch --show-current)" && \
echo "Last commit: $(git log -1 --oneline)" && \
echo "Uncommitted changes: $(git status --porcelain | wc -l)"
```

### 2. Database Status
```bash
echo "=== DATABASE STATUS ===" && \
php artisan migrate:status 2>/dev/null | tail -10 || echo "Run: php artisan migrate:status"
```

### 3. Test Status (Quick)
```bash
echo "=== TEST STATUS ===" && \
php artisan test --stop-on-failure 2>/dev/null | tail -20 || vendor/bin/phpunit --stop-on-failure 2>/dev/null | tail -20
```

### 4. Queue Status
```bash
echo "=== QUEUE STATUS ===" && \
php artisan queue:monitor 2>/dev/null || echo "Queue monitor not available"
```

### 5. Storage Status
```bash
echo "=== STORAGE STATUS ===" && \
du -sh storage/logs/ storage/app/ 2>/dev/null && \
echo "Log file size: $(du -sh storage/logs/laravel.log 2>/dev/null | cut -f1)"
```

### 6. Recent Errors
```bash
echo "=== RECENT ERRORS (last 5) ===" && \
grep -i "error\|exception" storage/logs/laravel.log 2>/dev/null | tail -5 || echo "No recent errors"
```

## Full Status Report

Run all checks and save to file:
```bash
{
  echo "# CMIS Project Status Report"
  echo "Generated: $(date)"
  echo ""
  echo "## Git"
  git status
  echo ""
  echo "## Migrations"
  php artisan migrate:status
  echo ""
  echo "## Tests"
  php artisan test --stop-on-failure
  echo ""
  echo "## Storage"
  du -sh storage/*
} > docs/active/analysis/status-$(date +%Y%m%d).md
```

## Common Issues & Quick Fixes

| Issue | Quick Fix |
|-------|-----------|
| Pending migrations | `php artisan migrate` |
| Failed tests | `php artisan test --filter=FailingTest` |
| Large log file | `truncate -s 0 storage/logs/laravel.log` |
| Cache issues | `/clean` command |
| Permission errors | `chmod -R 775 storage bootstrap/cache` |
