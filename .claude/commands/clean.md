---
description: Clear all Laravel caches (config, route, view, app, compiled)
---

# Clean Caches Command

Clear all Laravel caches to ensure fresh state.

## Quick Clean (Most Common)
```bash
php artisan optimize:clear
```

This clears:
- Configuration cache
- Route cache
- View cache
- Application cache
- Compiled files

## Individual Cache Commands (if needed)

### Configuration Cache
```bash
php artisan config:clear
php artisan config:cache  # Rebuild
```

### Route Cache
```bash
php artisan route:clear
php artisan route:cache  # Rebuild
```

### View Cache
```bash
php artisan view:clear
php artisan view:cache  # Rebuild (optional)
```

### Application Cache
```bash
php artisan cache:clear
```

### Event Cache
```bash
php artisan event:clear
php artisan event:cache  # Rebuild
```

## Full Reset (Development Only)

For complete reset during development:
```bash
php artisan optimize:clear && \
composer dump-autoload && \
php artisan package:discover && \
echo "All caches cleared and autoload regenerated"
```

## When to Use

- After changing `.env` file
- After updating `config/*.php` files
- After adding new routes
- After updating Blade templates (usually auto-cleared)
- After composer package updates
- When experiencing "class not found" errors
- Before running tests for clean state
