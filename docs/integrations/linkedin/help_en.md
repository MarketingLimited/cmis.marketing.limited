# 📘 Platform API Help

## Overview
This command provides direct access to the **<Platform> API** through Artisan.  
It supports data fetching, analytics inspection, and synchronization with CMIS.

## Usage
```bash
php artisan <platform>:api [account|page|company] [operation] [options]
```

## Options
| Option | Description | Example |
|--------|--------------|----------|
| `--limit` | Limit the number of posts retrieved | `--limit=5` |
| `--from`  | Start date (YYYY-MM-DD) | `--from=2025-01-01` |
| `--to`    | End date (YYYY-MM-DD) | `--to=2025-01-31` |
| `--metric` | Specific metric to analyze | `--metric=engagement` |
| `--sort` | Sort posts by a specific metric | `--sort=likes` |
| `--debug` | Display debug logs | `--debug` |
| `--debug-full` | Enable detailed logging | `--debug-full` |

## Examples
Fetch 10 recent posts:
```bash
php artisan <platform>:api posts --limit=10
```

Fetch posts within a date range:
```bash
php artisan <platform>:api posts --from=2025-01-01 --to=2025-01-31
```

Fetch most engaging posts:
```bash
php artisan <platform>:api posts --metric=engagement --sort=desc
```

---

### 🔹 Sync Command
Synchronize your <Platform> data with CMIS:
```bash
php artisan <platform>:sync
```
This command will:
- Fetch the latest posts and analytics
- Store them in CMIS tables (`social_posts`, `social_post_metrics`)
- Update account insights and KPIs

---

**End of Sync Help**
